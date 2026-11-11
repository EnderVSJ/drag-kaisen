<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/Usuario.php';

no_guardar_cache();

// Si ya hay sesión iniciada, directo al juego
if (usuario_actual() !== null) {
    header('Location: index.php');
    exit;
}

// Hash de relleno: se usa cuando el usuario no existe para que la respuesta
// tarde lo mismo y no se pueda averiguar qué usuarios están registrados.
const HASH_RELLENO = '$2b$12$bhpQIrJ8m2nUO4yGTXh8X.M.TKhjZxbBj71ePIdHNmWrvNQZ4yB.G';

$modo    = (($_GET['modo'] ?? '') === 'registro') ? 'registro' : 'login';
$error   = '';
$mensaje = '';
$usuarioIngresado = '';

if (isset($_GET['ok']))   { $mensaje = '¡Cuenta creada! Ya podés iniciar sesión.'; }
if (isset($_GET['salio'])) { $mensaje = 'Cerraste sesión correctamente.'; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion   = is_string($_POST['accion'] ?? null) ? $_POST['accion'] : '';
    $usuario  = is_string($_POST['usuario'] ?? null) ? trim($_POST['usuario']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $usuarioIngresado = $usuario;

    try {
        if (!csrf_valido($_POST['csrf'] ?? null)) {
            $error = 'El formulario caducó. Probá de nuevo.';
            $modo  = ($accion === 'registro') ? 'registro' : 'login';

        } elseif ($accion === 'registro') {
            // ---------- REGISTRO ----------
            $modo      = 'registro';
            $confirmar = is_string($_POST['confirmar'] ?? null) ? $_POST['confirmar'] : '';

            if ($usuario === '' || $password === '' || $confirmar === '') {
                $error = 'Completá todos los campos.';
            } elseif (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $usuario)) {
                $error = 'El usuario debe tener entre 3 y 20 caracteres: letras, números o guion bajo (_).';
            } elseif (strlen($password) < 6) {
                $error = 'La contraseña debe tener al menos 6 caracteres.';
            } elseif (strlen($password) > 72) {
                $error = 'La contraseña es demasiado larga (máximo 72 caracteres).';
            } elseif ($password !== $confirmar) {
                $error = 'Las contraseñas no coinciden.';
            } else {
                if (Usuario::existeNombre($usuario)) {
                    $error = 'Ese nombre de usuario ya está registrado. Elegí otro.';
                } else {
                    try {
                        Usuario::crear($usuario, $password);   // INSERT en models/Usuario.php
                        header('Location: login.php?ok=1');
                        exit;
                    } catch (PDOException $e) {
                        // 1062 = nombre duplicado (por si dos personas se registran a la vez)
                        if ((int)($e->errorInfo[1] ?? 0) === 1062) {
                            $error = 'Ese nombre de usuario ya está registrado. Elegí otro.';
                        } else {
                            throw $e;
                        }
                    }
                }
            }

        } elseif ($accion === 'login') {
            // ---------- INICIO DE SESIÓN ----------
            $modo = 'login';

            if ($usuario === '' || $password === '') {
                $error = 'Ingresá tu usuario y tu contraseña.';
            } else {
                $fila = Usuario::buscarParaLogin($usuario);   // SELECT en models/Usuario.php

                $hash = $fila ? $fila['password_hash'] : HASH_RELLENO;
                $ok   = password_verify($password, $hash);

                if ($fila && $ok) {
                    session_regenerate_id(true);               // nuevo ID de sesión al entrar
                    $_SESSION['usuario_id']     = (int)$fila['id'];
                    $_SESSION['usuario_nombre'] = (string)$fila['usuario'];
                    header('Location: index.php');
                    exit;
                }
                $error = 'Usuario o contraseña incorrectos.';
            }
        }
    } catch (Throwable $e) {
        error_log('[Drag&Kaisen] ' . $e->getMessage());
        $error = 'Ocurrió un problema en el servidor. Intentá de nuevo en unos minutos.';
    }
}

$token = csrf_token();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= $modo === 'registro' ? 'Crear cuenta' : 'Iniciar sesión' ?> - Drag &amp; Kaisen</title>
  <link rel="icon" type="image/png" href="assets/img/fondo/logo.png">
  <link rel="stylesheet" href="estilos.css" />
</head>
<body class="pagina-auth">

  <main class="auth-contenedor">
    <img src="assets/img/fondo/logo.png" alt="Drag &amp; Kaisen" class="auth-logo">

    <section class="auth-caja">
      <nav class="auth-tabs">
        <a href="login.php" class="<?= $modo === 'login' ? 'activa' : '' ?>">Iniciar sesión</a>
        <a href="login.php?modo=registro" class="<?= $modo === 'registro' ? 'activa' : '' ?>">Crear cuenta</a>
      </nav>

      <?php if ($error !== ''): ?>
        <p class="auth-msg auth-error" role="alert"><?= h($error) ?></p>
      <?php elseif ($mensaje !== ''): ?>
        <p class="auth-msg auth-ok"><?= h($mensaje) ?></p>
      <?php endif; ?>

      <?php if ($modo === 'registro'): ?>
        <form method="post" action="login.php?modo=registro" autocomplete="off">
          <input type="hidden" name="accion" value="registro">
          <input type="hidden" name="csrf" value="<?= h($token) ?>">

          <label for="usuario">Nombre de usuario</label>
          <input type="text" id="usuario" name="usuario" required minlength="3" maxlength="20"
                 pattern="[A-Za-z0-9_]{3,20}" value="<?= h($usuarioIngresado) ?>"
                 autocapitalize="off" spellcheck="false">
          <small>3 a 20 caracteres: letras, números o _</small>

          <label for="password">Contraseña</label>
          <input type="password" id="password" name="password" required minlength="6" maxlength="72"
                 autocomplete="new-password">
          <small>Mínimo 6 caracteres</small>

          <label for="confirmar">Repetir contraseña</label>
          <input type="password" id="confirmar" name="confirmar" required minlength="6" maxlength="72"
                 autocomplete="new-password">

          <button type="submit">Registrarme</button>
        </form>
      <?php else: ?>
        <form method="post" action="login.php">
          <input type="hidden" name="accion" value="login">
          <input type="hidden" name="csrf" value="<?= h($token) ?>">

          <label for="usuario">Nombre de usuario</label>
          <input type="text" id="usuario" name="usuario" required maxlength="20"
                 value="<?= h($usuarioIngresado) ?>" autocomplete="username"
                 autocapitalize="off" spellcheck="false" autofocus>

          <label for="password">Contraseña</label>
          <input type="password" id="password" name="password" required maxlength="72"
                 autocomplete="current-password">

          <button type="submit">Entrar</button>
        </form>
      <?php endif; ?>
    </section>
  </main>

</body>
</html>
