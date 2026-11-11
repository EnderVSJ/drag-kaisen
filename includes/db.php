<?php
declare(strict_types=1);

/**
 * Devuelve la conexión PDO (se crea una sola vez).
 * Si falla, el detalle técnico va al log del servidor, nunca a la pantalla.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // consultas preparadas reales
            ]
        );
    } catch (PDOException $e) {
        error_log('[Drag&Kaisen] Error de conexión a MySQL: ' . $e->getMessage());
        throw new RuntimeException('No se pudo conectar a la base de datos.');
    }

    return $pdo;
}
