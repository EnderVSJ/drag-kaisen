<?php
require_once __DIR__ . '/includes/auth.php';
$usuario = requerir_login();   // sin sesión => redirige a login.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Drag & Kaisen</title>
  <meta name="csrf-token" content="<?= h(csrf_token()) ?>">
  <link rel="icon" type="image/png" href="assets/img/fondo/logo.png">
  <link rel="stylesheet" href="estilos.css?v=<?= (int)@filemtime(__DIR__ . '/estilos.css') ?>" />
</head>
<body>

  <!-- 🔥 INTRO CON VIDEO -->
  <div id="intro">
    <video id="introVideo" autoplay muted>
      <source src="assets/vid/intro.mp4" type="video/mp4">
      Tu navegador no soporta videos.
    </video>
  </div>

  <!-- 🌓 PANTALLA DE INICIO -->
  <div id="pantallaInicio" class="oculto">
    <img src="assets/img/fondo/logo.png" alt="Logo Jujutsu Kaisen" class="logo">
      <button id="btnInicio" type="button">
        <img src="assets/img/fondo/jugar.png" alt="Iniciar juego" class="boton-img">
      </button>
  </div>

  <!-- Background image (colocar assets/background.jpg) -->
  <div id="app">
    <header>
      <h1>Drag and Kaisen</h1>
      <div class="controls">
        <span class="usuario-activo">👤 <?= h($usuario['nombre']) ?></span>
        <button type="button" id="historialBtn">Historial</button>
        <form method="post" action="logout.php" class="form-salir">
          <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
          <button type="submit" id="logoutBtn">Cerrar sesión</button>
        </form>
        <button id="randomEnemyBtn">Elegir enemigo aleatorio</button>
        <button id="resetBtn">Reiniciar batalla</button>
        <label>
          <input type="checkbox" id="musicToggle" checked>
          Música
        </label>
      </div>
    </header>

  <main>
<section id="leftPanel">
    <h2>Tus Hechiceros</h2>

    <div class="levels">
        <div class="level" data-level="all">
            <h3>Todos los Grados</h3>
            <div class="list vertical" id="allPlayersList"></div>
        </div>
    </div>
</section>

<section id="battlePanel">
  <h2>Campo de batalla</h2>
  <div id="battlefield">
    <div id="enemyContainer">
      <p class="label">Enemigo</p>
      <div id="enemyCard" class="card empty">No hay enemigo</div>
    </div>

    <div id="dropZone" class="dropzone">
      <p>Arrastra tu personaje aquí para luchar</p>
    </div>

    <div id="playerContainer">
      <p class="label">Tu Hechicero</p>
      <div id="playerCard" class="card empty">No seleccionado</div>
    </div>
  </div>

  <div style="text-align: center; margin: 10px 0;">
    <button id="startBattleBtn" disabled style="padding: 10px 20px; font-size: 16px; font-weight: bold; border-radius: 8px; cursor: pointer;">
      Iniciar Batalla
    </button>
  </div>

  <div id="hud">
    <div id="statsPanel">
      <div id="playerStats" class="stats"></div>
      <div id="vsTag">VS</div>
      <div id="enemyStats" class="stats"></div>
    </div>

    <div id="log" class="log">
      <div class="logTitle">Registro de combate</div>
      <div id="logContent"></div>
    </div>
  </div>
</section>

  <aside id="rightPanel">
    <h2>Lista de Maldiciones</h2>
    <div id="enemyList" class="list vertical"></div>
  </aside>
</main>

    <footer>
      <small>Drag & Kaisen</small>
    </footer>
  </div>

  <audio id="bgMusic" loop>
    <source src="assets/aud/intro.mp3" type="audio/mpeg">
    <!-- Si no tenés música, poné un archivo llamado music.mp3 en assets/ o sacá la etiqueta -->
  </audio>

  <!-- 📜 HISTORIAL DE PELEAS (ventana con tabla) -->
  <div id="historialModal" class="modal" hidden>
    <div class="modal-caja" role="dialog" aria-modal="true" aria-labelledby="historialTitulo">
      <div class="modal-cabecera">
        <h2 id="historialTitulo">Historial de peleas</h2>
        <div>
          <button type="button" id="historialVolver" hidden>← Volver</button>
          <button type="button" id="historialCerrar">Cerrar</button>
        </div>
      </div>
      <p id="historialResumen" class="hist-sub"></p>
      <div class="hist-tabla-wrap">
        <table class="hist-tabla">
          <thead id="historialHead"></thead>
          <tbody id="historialBody"></tbody>
        </table>
      </div>
    </div>
  </div>

  <script src="script.js?v=<?= (int)@filemtime(__DIR__ . '/script.js') ?>"></script>
</body>
</html>
