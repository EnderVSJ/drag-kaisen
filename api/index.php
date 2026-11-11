<?php
declare(strict_types=1);

/**
 * PUNTO DE ENTRADA de la API REST (front controller).
 *
 * Todos los pedidos a  http://localhost/drag-kaisen/api/...  llegan acá.
 * Este archivo busca en routes/api.php qué ruta coincide con el verbo y la dirección
 * pedidos y llama al controlador correspondiente.
 *
 * (api/pelea.php sigue funcionando aparte: es la API interna que usa el juego.)
 */
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

/** Envía una respuesta JSON con su código HTTP y termina. */
function responder_json(int $codigo, array $datos): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

/** Lee el cuerpo (body) del pedido. Acepta JSON (raw) o formulario (form-data / x-www-form-urlencoded). */
function leer_body_json(): array
{
    if (!empty($_POST)) {
        return $_POST;
    }
    $crudo = file_get_contents('php://input', false, null, 0, 16384);
    if ($crudo === false || trim($crudo) === '') {
        return [];
    }
    $datos = json_decode($crudo, true);
    if (!is_array($datos)) {
        responder_json(400, ['ok' => false, 'mensaje' => 'El body no es un JSON válido. En Postman: Body > raw > JSON.']);
    }
    return $datos;
}

// Seguridad: por defecto la API solo acepta pedidos desde esta misma PC (ver API_SOLO_LOCAL en .env).
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (API_SOLO_LOCAL && !in_array($ip, ['127.0.0.1', '::1'], true)) {
    responder_json(403, ['ok' => false, 'mensaje' => 'La API solo acepta pedidos locales (API_SOLO_LOCAL=true en .env).']);
}

// ---- Averiguar la ruta pedida: /usuarios, /usuarios/5, etc. ----
$metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if (!empty($_SERVER['PATH_INFO'])) {
    $ruta = $_SERVER['PATH_INFO'];                     // .../api/index.php/usuarios
} elseif (isset($_GET['ruta']) && is_string($_GET['ruta'])) {
    $ruta = $_GET['ruta'];                             // .../api/index.php?ruta=/usuarios
} else {
    $uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    $ruta = (strpos($uri, $base) === 0) ? substr($uri, strlen($base)) : $uri;   // .../api/usuarios
}
$ruta = '/' . trim(rawurldecode($ruta), '/');

// ---- Buscar la ruta en routes/api.php ----
$rutas = require __DIR__ . '/../routes/api.php';
$metodosDeLaRuta = [];

try {
    foreach ($rutas as [$verbo, $patron, [$clase, $accion]]) {
        $regex = '#^' . str_replace('\{id\}', '(\d+)', preg_quote($patron, '#')) . '$#';
        if (!preg_match($regex, $ruta, $m)) {
            continue;
        }
        if ($verbo !== $metodo) {
            $metodosDeLaRuta[] = $verbo;
            continue;
        }
        $controlador = new $clase();
        isset($m[1]) ? $controlador->$accion((int)$m[1]) : $controlador->$accion();
        exit;
    }

    if ($metodosDeLaRuta) {
        header('Allow: ' . implode(', ', $metodosDeLaRuta));
        responder_json(405, ['ok' => false, 'mensaje' => "El método $metodo no está permitido en $ruta"]);
    }
    responder_json(404, ['ok' => false, 'mensaje' => "Ruta no encontrada: $metodo $ruta"]);

} catch (Throwable $e) {
    error_log('[Drag&Kaisen API] ' . $e->getMessage());
    responder_json(500, ['ok' => false, 'mensaje' => 'Error del servidor. ¿Está MySQL encendido y la base importada?']);
}
