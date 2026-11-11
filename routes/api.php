<?php
declare(strict_types=1);

/**
 * RUTAS de la API REST
 *
 * Cada ruta une: VERBO HTTP + DIRECCIÓN  ->  Controlador y método que la atiende.
 * {id} es un número (por ejemplo /usuarios/3).
 *
 *   GET    /usuarios        -> lista todos los usuarios
 *   GET    /usuarios/{id}   -> muestra un usuario
 *   POST   /usuarios        -> crea un usuario nuevo
 *   DELETE /usuarios/{id}   -> elimina un usuario
 */
require_once __DIR__ . '/../controllers/UsuarioController.php';

return [
    ['GET',    '/usuarios',      [UsuarioController::class, 'index']],
    ['GET',    '/usuarios/{id}', [UsuarioController::class, 'show']],
    ['POST',   '/usuarios',      [UsuarioController::class, 'store']],
    ['DELETE', '/usuarios/{id}', [UsuarioController::class, 'destroy']],
];
