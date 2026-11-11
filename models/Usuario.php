<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

/**
 * MODELO Usuario
 *
 * Es la ÚNICA parte del proyecto que habla con la tabla `usuarios` de MySQL.
 * Acá están todas las consultas SQL (SELECT, INSERT, DELETE) de usuarios.
 * Todas son consultas preparadas (PDO): los datos nunca se pegan dentro del SQL.
 */
class Usuario
{
    /** Columnas que se pueden mostrar (NUNCA se devuelve el password_hash). */
    private const COLUMNAS_PUBLICAS = 'id, usuario, creado_en';

    /** SELECT: todos los usuarios. */
    public static function todos(): array
    {
        $st = db()->query('SELECT ' . self::COLUMNAS_PUBLICAS . ' FROM usuarios ORDER BY id');
        return array_map([self::class, 'formatear'], $st->fetchAll());
    }

    /** SELECT: un usuario por su id (o null si no existe). */
    public static function buscarPorId(int $id): ?array
    {
        $st = db()->prepare('SELECT ' . self::COLUMNAS_PUBLICAS . ' FROM usuarios WHERE id = :id');
        $st->execute([':id' => $id]);
        $fila = $st->fetch();
        return $fila ? self::formatear($fila) : null;
    }

    /** SELECT: un usuario por su nombre, con el hash (solo para el login, uso interno). */
    public static function buscarParaLogin(string $usuario): ?array
    {
        $st = db()->prepare('SELECT id, usuario, password_hash FROM usuarios WHERE usuario = :u LIMIT 1');
        $st->execute([':u' => $usuario]);
        $fila = $st->fetch();
        return $fila ?: null;
    }

    /** ¿Ya existe ese nombre de usuario? (no distingue mayúsculas, por la collation de la tabla) */
    public static function existeNombre(string $usuario): bool
    {
        $st = db()->prepare('SELECT 1 FROM usuarios WHERE usuario = :u LIMIT 1');
        $st->execute([':u' => $usuario]);
        return (bool)$st->fetchColumn();
    }

    /**
     * INSERT: crea un usuario nuevo y devuelve su id.
     * La contraseña se guarda como hash (password_hash), nunca en texto plano.
     * Lanza PDOException con código 1062 si el nombre ya existe.
     */
    public static function crear(string $usuario, string $password): int
    {
        $st = db()->prepare('INSERT INTO usuarios (usuario, password_hash) VALUES (:u, :h)');
        $st->execute([
            ':u' => $usuario,
            ':h' => password_hash($password, PASSWORD_DEFAULT),
        ]);
        return (int)db()->lastInsertId();
    }

    /** DELETE: borra un usuario por id. Devuelve true si borró algo. (Sus peleas se borran en cascada.) */
    public static function eliminar(int $id): bool
    {
        $st = db()->prepare('DELETE FROM usuarios WHERE id = :id');
        $st->execute([':id' => $id]);
        return $st->rowCount() > 0;
    }

    /** Reglas de validación compartidas por el formulario de registro y la API. Devuelve el error o null. */
    public static function validar(string $usuario, string $password): ?string
    {
        if ($usuario === '' || $password === '') {
            return 'Completá todos los campos.';
        }
        if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $usuario)) {
            return 'El usuario debe tener entre 3 y 20 caracteres (letras, números o guion bajo).';
        }
        if (strlen($password) < 6) {
            return 'La contraseña debe tener al menos 6 caracteres.';
        }
        if (strlen($password) > 72) {
            return 'La contraseña es demasiado larga (máximo 72 caracteres).';
        }
        return null;
    }

    private static function formatear(array $fila): array
    {
        return [
            'id'        => (int)$fila['id'],
            'usuario'   => (string)$fila['usuario'],
            'creado_en' => (string)$fila['creado_en'],
        ];
    }
}
