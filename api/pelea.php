<?php
declare(strict_types=1);

/**
 * API de peleas (responde JSON).
 *   accion=iniciar   -> crea la pelea y devuelve su id
 *   accion=finalizar -> recibe los daños de cada golpe, los valida y los guarda
 *
 * El usuario sale SIEMPRE de la sesión del servidor. Nombres, grados y HP salen del
 * catálogo del servidor, y cada daño se valida contra ATK/DEF: el navegador no puede
 * inventar peleas ni daños imposibles.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/catalogo.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responder(int $codigo, array $datos): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, ['ok' => false, 'error' => 'Método no permitido']);
}

$usuario = usuario_actual();
if ($usuario === null) {
    responder(401, ['ok' => false, 'error' => 'Sesión no iniciada']);
}

if (!csrf_valido($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    responder(403, ['ok' => false, 'error' => 'Token inválido']);
}

$crudo = file_get_contents('php://input', false, null, 0, 16384);
$datos = json_decode($crudo === false ? '' : $crudo, true);
if (!is_array($datos)) {
    responder(400, ['ok' => false, 'error' => 'Datos inválidos']);
}

$accion = is_string($datos['accion'] ?? null) ? $datos['accion'] : '';

try {
    $hechiceros = catalogo_hechiceros();
    $enemigos   = catalogo_enemigos();

    /* ---------------- HISTORIAL (lista + totales del usuario logueado) ---------------- */
    if ($accion === 'historial') {
        $st = db()->prepare(
            'SELECT id, iniciada_en, hechicero_nombre, hechicero_grado, enemigo_nombre, enemigo_grado,
                    resultado, turnos, dano_causado, dano_recibido
             FROM peleas WHERE usuario_id = :u AND resultado <> \'en_curso\' ORDER BY id DESC LIMIT 100'
        );
        $st->execute([':u' => $usuario['id']]);
        $peleas = $st->fetchAll();

        $sr = db()->prepare(
            'SELECT COUNT(*) AS total,
                    COALESCE(SUM(resultado = \'victoria\'), 0) AS victorias,
                    COALESCE(SUM(resultado = \'derrota\'), 0)  AS derrotas,
                    COALESCE(SUM(dano_causado), 0)  AS causado,
                    COALESCE(SUM(dano_recibido), 0) AS recibido
             FROM peleas WHERE usuario_id = :u AND resultado <> \'en_curso\''
        );
        $sr->execute([':u' => $usuario['id']]);
        $r = $sr->fetch();
        responder(200, [
            'ok' => true,
            'peleas' => $peleas,
            'resumen' => [
                'total' => (int)$r['total'], 'victorias' => (int)$r['victorias'], 'derrotas' => (int)$r['derrotas'],
                'causado' => (int)$r['causado'], 'recibido' => (int)$r['recibido'],
            ],
        ]);
    }

    /* ---------------- DETALLE (golpe por golpe de una pelea propia) ---------------- */
    if ($accion === 'detalle') {
        $id = filter_var($datos['pelea_id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false) {
            responder(400, ['ok' => false, 'error' => 'Datos inválidos']);
        }
        $st = db()->prepare(
            'SELECT id, iniciada_en, hechicero_nombre, enemigo_nombre, resultado, turnos, dano_causado, dano_recibido
             FROM peleas WHERE id = :id AND usuario_id = :u'
        );
        $st->execute([':id' => $id, ':u' => $usuario['id']]);
        $pelea = $st->fetch();
        if (!$pelea) {
            responder(404, ['ok' => false, 'error' => 'Pelea no encontrada']);
        }
        $sg = db()->prepare(
            'SELECT turno, atacante_nombre, defensor_nombre, dano, hp_defensor_restante
             FROM pelea_golpes WHERE pelea_id = :p ORDER BY orden'
        );
        $sg->execute([':p' => $id]);
        responder(200, ['ok' => true, 'pelea' => $pelea, 'golpes' => $sg->fetchAll()]);
    }

    /* ---------------- INICIAR ---------------- */
    if ($accion === 'iniciar') {
        $hid = is_string($datos['hechicero_id'] ?? null) ? $datos['hechicero_id'] : '';
        $eid = is_string($datos['enemigo_id'] ?? null) ? $datos['enemigo_id'] : '';
        if (!isset($hechiceros[$hid], $enemigos[$eid])) {
            responder(400, ['ok' => false, 'error' => 'Personaje o enemigo desconocido']);
        }
        $h = $hechiceros[$hid];
        $e = $enemigos[$eid];

        $ins = db()->prepare(
            'INSERT INTO peleas
               (usuario_id, hechicero_id, hechicero_nombre, hechicero_grado,
                enemigo_id, enemigo_nombre, enemigo_grado,
                hp_hechicero_inicial, hp_enemigo_inicial)
             VALUES (:u, :hid, :hn, :hg, :eid, :en, :eg, :hh, :eh)'
        );
        $ins->execute([
            ':u' => $usuario['id'], ':hid' => $hid, ':hn' => $h['nombre'], ':hg' => $h['grado'],
            ':eid' => $eid, ':en' => $e['nombre'], ':eg' => $e['grado'],
            ':hh' => $h['hp'], ':eh' => $e['hp'],
        ]);
        responder(200, ['ok' => true, 'pelea_id' => (int)db()->lastInsertId()]);
    }

    /* ---------------- FINALIZAR ---------------- */
    if ($accion === 'finalizar') {
        $peleaId = filter_var($datos['pelea_id'] ?? null, FILTER_VALIDATE_INT);
        $danos   = $datos['danos'] ?? null;
        $abandon = ($datos['abandonada'] ?? false) === true;

        if ($peleaId === false || !is_array($danos) || count($danos) > 400 || $danos !== array_values($danos)) {
            responder(400, ['ok' => false, 'error' => 'Datos inválidos']);
        }

        $pdo = db();
        $pdo->beginTransaction();

        // Solo se puede cerrar una pelea propia y que siga en curso (FOR UPDATE evita cierres dobles).
        $sel = $pdo->prepare('SELECT * FROM peleas WHERE id = :id AND usuario_id = :u AND resultado = \'en_curso\' FOR UPDATE');
        $sel->execute([':id' => $peleaId, ':u' => $usuario['id']]);
        $pelea = $sel->fetch();
        if (!$pelea) {
            $pdo->rollBack();
            responder(404, ['ok' => false, 'error' => 'Pelea no encontrada']);
        }

        $h = $hechiceros[$pelea['hechicero_id']];
        $e = $enemigos[$pelea['enemigo_id']];

        // Misma regla que el juego: empieza el más rápido (empate: el hechicero) y se alternan.
        $primero = ($h['spd'] >= $e['spd']) ? 'hechicero' : 'enemigo';
        $hpH = $h['hp'];
        $hpE = $e['hp'];
        $golpes = [];
        $danoCausado = 0;
        $danoRecibido = 0;
        $terminada = false;

        foreach ($danos as $i => $dano) {
            if ($terminada || !is_int($dano)) {
                throw new UnexpectedValueException('Golpes inválidos');
            }
            $ataca = (($i % 2) === 0) ? $primero : ($primero === 'hechicero' ? 'enemigo' : 'hechicero');
            $atq   = $ataca === 'hechicero' ? $h : $e;
            $def   = $ataca === 'hechicero' ? $e : $h;
            [$min, $max] = rango_dano($atq, $def);
            if ($dano < $min || $dano > $max) {
                throw new UnexpectedValueException('Daño fuera de rango');
            }

            if ($ataca === 'hechicero') {
                $hpE = max(0, $hpE - $dano);
                $restante = $hpE;
                $danoCausado += $dano;
            } else {
                $hpH = max(0, $hpH - $dano);
                $restante = $hpH;
                $danoRecibido += $dano;
            }
            if ($restante === 0) {
                $terminada = true;
            }
            $golpes[] = [
                'orden' => $i + 1, 'turno' => intdiv($i, 2) + 1, 'atacante' => $ataca,
                'an' => $atq['nombre'], 'dn' => $def['nombre'], 'dano' => $dano, 'hp' => $restante,
            ];
        }

        if ($terminada) {
            $resultado = ($hpE === 0) ? 'victoria' : 'derrota';
        } else {
            $resultado = 'abandonada';   // se reinició o cerró antes de que alguien cayera
        }
        if (!$abandon && !$terminada) {
            throw new UnexpectedValueException('La pelea no terminó');
        }

        $insG = $pdo->prepare(
            'INSERT INTO pelea_golpes (pelea_id, orden, turno, atacante, atacante_nombre, defensor_nombre, dano, hp_defensor_restante)
             VALUES (:p, :o, :t, :a, :an, :dn, :d, :hp)'
        );
        foreach ($golpes as $g) {
            $insG->execute([
                ':p' => $peleaId, ':o' => $g['orden'], ':t' => $g['turno'], ':a' => $g['atacante'],
                ':an' => $g['an'], ':dn' => $g['dn'], ':d' => $g['dano'], ':hp' => $g['hp'],
            ]);
        }

        $upd = $pdo->prepare(
            'UPDATE peleas SET resultado = :r, turnos = :t, dano_causado = :dc, dano_recibido = :dr,
                    hp_hechicero_final = :hh, hp_enemigo_final = :he, finalizada_en = NOW()
             WHERE id = :id'
        );
        $upd->execute([
            ':r' => $resultado, ':t' => intdiv(count($golpes) + 1, 2), ':dc' => $danoCausado, ':dr' => $danoRecibido,
            ':hh' => $hpH, ':he' => $hpE, ':id' => $peleaId,
        ]);
        $pdo->commit();

        responder(200, ['ok' => true, 'resultado' => $resultado, 'dano_causado' => $danoCausado, 'dano_recibido' => $danoRecibido]);
    }

    responder(400, ['ok' => false, 'error' => 'Acción desconocida']);

} catch (UnexpectedValueException $ex) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('[Drag&Kaisen] Pelea rechazada: ' . $ex->getMessage());
    responder(422, ['ok' => false, 'error' => 'Pelea no válida']);
} catch (Throwable $ex) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('[Drag&Kaisen] ' . $ex->getMessage());
    // 42S02 = la tabla no existe: falta volver a importar sql/database.sql
    if ($ex instanceof PDOException && ($ex->errorInfo[0] ?? '') === '42S02') {
        responder(500, ['ok' => false, 'error' => 'Faltan las tablas de peleas: volvé a importar sql/database.sql en phpMyAdmin.']);
    }
    responder(500, ['ok' => false, 'error' => 'Error del servidor']);
}
