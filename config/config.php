<?php
declare(strict_types=1);

/**
 * CONFIGURACIÓN DEL PROYECTO
 *
 * Lee el archivo ".env" (en la raíz del proyecto) y define las constantes de conexión
 * a la base de datos. Los datos secretos (usuario y contraseña de MySQL) NO están
 * escritos acá: viven solo en ".env", que no se sube a GitHub.
 */

/** Lee un archivo .env (líneas CLAVE=valor) y devuelve un array. */
function cargar_env(string $ruta): array
{
    $vars = [];
    if (!is_readable($ruta)) {
        return $vars;
    }
    foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
        $linea = trim($linea);
        if ($linea === '' || $linea[0] === '#' || strpos($linea, '=') === false) {
            continue;                                   // comentario o línea vacía
        }
        [$clave, $valor] = array_map('trim', explode('=', $linea, 2));
        // Quita comillas si el valor viene como "valor" o 'valor'
        if (strlen($valor) >= 2 && ($valor[0] === '"' || $valor[0] === "'") && substr($valor, -1) === $valor[0]) {
            $valor = substr($valor, 1, -1);
        }
        $vars[$clave] = $valor;
    }
    return $vars;
}

$env = cargar_env(dirname(__DIR__) . '/.env');

/** Devuelve una variable de entorno: primero la del .env, si no la del servidor, si no el valor por defecto. */
function env_valor(array $env, string $clave, string $porDefecto = ''): string
{
    if (array_key_exists($clave, $env)) {
        return $env[$clave];
    }
    $sistema = getenv($clave);
    return $sistema === false ? $porDefecto : $sistema;
}

if (!defined('DB_HOST')) {
    define('DB_HOST', env_valor($env, 'DB_HOST', 'localhost'));
    define('DB_PORT', env_valor($env, 'DB_PORT', '3306'));
    define('DB_NAME', env_valor($env, 'DB_NAME', 'drag_kaisen'));
    define('DB_USER', env_valor($env, 'DB_USER', 'root'));
    define('DB_PASS', env_valor($env, 'DB_PASS', ''));
    define('API_SOLO_LOCAL', strtolower(env_valor($env, 'API_SOLO_LOCAL', 'true')) !== 'false');
}
unset($env);
