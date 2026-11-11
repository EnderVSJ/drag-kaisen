<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Usuario.php';

/**
 * CONTROLADOR de Usuarios
 *
 * Recibe el pedido que llega por la ruta, valida los datos, le pide al Modelo
 * (models/Usuario.php) que lea o guarde en la base y arma la RESPUESTA JSON
 * con el código HTTP y el mensaje que ve Postman / el navegador.
 * Si querés cambiar un mensaje de respuesta de la API, se cambia ACÁ.
 */
class UsuarioController
{
    /** GET /usuarios  -> lista de usuarios */
    public function index(): void
    {
        $usuarios = Usuario::todos();
        responder_json(200, [
            'ok'       => true,
            'mensaje'  => 'Lista de usuarios',
            'cantidad' => count($usuarios),
            'datos'    => $usuarios,
        ]);
    }

    /** GET /usuarios/{id}  -> un usuario */
    public function show(int $id): void
    {
        $usuario = Usuario::buscarPorId($id);
        if ($usuario === null) {
            responder_json(404, ['ok' => false, 'mensaje' => "No existe un usuario con id $id"]);
        }
        responder_json(200, ['ok' => true, 'mensaje' => 'Usuario encontrado', 'datos' => $usuario]);
    }

    /** POST /usuarios  -> crea un usuario. Body JSON: {"usuario": "...", "password": "..."} */
    public function store(): void
    {
        $body     = leer_body_json();
        $usuario  = is_string($body['usuario'] ?? null) ? trim($body['usuario']) : '';
        $password = is_string($body['password'] ?? null) ? $body['password'] : '';

        $error = Usuario::validar($usuario, $password);
        if ($error !== null) {
            responder_json(400, ['ok' => false, 'mensaje' => $error]);
        }
        if (Usuario::existeNombre($usuario)) {
            responder_json(409, ['ok' => false, 'mensaje' => 'Ese nombre de usuario ya está registrado.']);
        }

        try {
            $id = Usuario::crear($usuario, $password);
        } catch (PDOException $e) {
            if ((int)($e->errorInfo[1] ?? 0) === 1062) {   // duplicado (dos altas al mismo tiempo)
                responder_json(409, ['ok' => false, 'mensaje' => 'Ese nombre de usuario ya está registrado.']);
            }
            throw $e;
        }

        responder_json(201, [
            'ok'      => true,
            'mensaje' => 'Usuario creado correctamente',
            'datos'   => Usuario::buscarPorId($id),
        ]);
    }

    /** DELETE /usuarios/{id}  -> borra un usuario */
    public function destroy(int $id): void
    {
        if (!Usuario::eliminar($id)) {
            responder_json(404, ['ok' => false, 'mensaje' => "No existe un usuario con id $id"]);
        }
        responder_json(200, ['ok' => true, 'mensaje' => "Usuario $id eliminado correctamente"]);
    }
}
