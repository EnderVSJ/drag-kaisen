<?php
declare(strict_types=1);

/**
 * Funciones de sesión y autenticación. Se incluye al inicio de cada página PHP.
 */

// Nunca mostrar errores internos de PHP al visitante (quedan en el log del servidor).
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/** Inicia la sesión PHP con cookies seguras. */
function iniciar_sesion_segura(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);

    ini_set('session.use_strict_mode', '1');   // rechaza IDs de sesión inventados
    ini_set('session.use_only_cookies', '1');  // el ID nunca viaja en la URL
    ini_set('session.gc_maxlifetime', '7200'); // la sesión caduca tras 2 h sin actividad

    session_name('DRAGKAISENSESSID');
    session_set_cookie_params([
        'lifetime' => 0,          // dura mientras el navegador siga abierto
        'path'     => '/',
        'secure'   => $https,     // solo por HTTPS cuando el sitio use HTTPS
        'httponly' => true,       // JavaScript no puede leer la cookie
        'samesite' => 'Lax',
    ]);
    session_start();
}

/** Evita que el navegador guarde en caché páginas privadas (botón "Atrás" tras cerrar sesión). */
function no_guardar_cache(): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

/** Usuario logueado o null. La identidad sale SIEMPRE de la sesión del servidor. */
function usuario_actual(): ?array
{
    iniciar_sesion_segura();
    if (isset($_SESSION['usuario_id'], $_SESSION['usuario_nombre'])) {
        return [
            'id'     => (int)$_SESSION['usuario_id'],
            'nombre' => (string)$_SESSION['usuario_nombre'],
        ];
    }
    return null;
}

/** Protege una página: si no hay sesión, redirige al login y corta la ejecución. */
function requerir_login(): array
{
    no_guardar_cache();
    $usuario = usuario_actual();
    if ($usuario === null) {
        header('Location: login.php');
        exit;
    }
    return $usuario;
}

/** Token anti-CSRF (impide que otro sitio envíe formularios en nombre del usuario). */
function csrf_token(): string
{
    iniciar_sesion_segura();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_valido(?string $token): bool
{
    iniciar_sesion_segura();
    return is_string($token)
        && !empty($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $token);
}

/** Escapa texto para mostrarlo en HTML. */
function h(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Cierra la sesión por completo (datos + cookie + sesión en el servidor). */
function destruir_sesion(): void
{
    iniciar_sesion_segura();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}
