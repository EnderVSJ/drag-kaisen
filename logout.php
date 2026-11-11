<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

no_guardar_cache();

// Solo se cierra sesión con un formulario POST válido (evita cierres forzados desde otros sitios).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido($_POST['csrf'] ?? null)) {
    destruir_sesion();
    header('Location: login.php?salio=1');
    exit;
}

header('Location: index.php');
exit;
