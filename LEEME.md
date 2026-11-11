# Drag & Kaisen — Registro e inicio de sesión

## Archivos
**Nuevos**
- `login.php` – pantalla de iniciar sesión / crear cuenta.
- `logout.php` – cierra la sesión.
- `includes/config.php` – **datos de conexión a MySQL (lo único que hay que editar)**.
- `includes/db.php` – conexión PDO.
- `includes/auth.php` – sesiones, protección de páginas, anti-CSRF.
- `sql/database.sql` – crea la base y la tabla (para phpMyAdmin).
- `includes/.htaccess`, `sql/.htaccess` – bloquean el acceso desde el navegador.

**Modificados**
- `index.html` → **`index.php`**: ahora exige sesión; se agregó el nombre del usuario y el botón "Cerrar sesión";
  se corrigió el botón de inicio (faltaba `<button id="btnInicio">`) y la ruta `intro.MP3` → `intro.mp3`.
- `estilos.css`: estilos nuevos al final (el resto no se tocó).

**Sin cambios:** la carpeta `assets/`. (`script.js` e `index.php` se modificaron de nuevo para el registro de peleas, ver abajo.)

## Instalación local (XAMPP / WAMP)
1. Iniciá **Apache** y **MySQL** desde el panel de XAMPP/WAMP.
2. Copiá la carpeta del proyecto en:
   - XAMPP: `C:\xampp\htdocs\drag-kaisen`
   - WAMP: `C:\wamp64\www\drag-kaisen`
3. Abrí `http://localhost/phpmyadmin` → pestaña **Importar** → **Elegir archivo** → `sql/database.sql` → **Importar**.
   Se crea la base `drag_kaisen` con la tabla `usuarios`.
4. Abrí el archivo `.env` (en la raíz). Por defecto usa `root` y contraseña vacía (típico de XAMPP/WAMP).
   Si tu MySQL tiene otra contraseña, cambiala ahí.
5. Abrí en el navegador: **http://localhost/drag-kaisen/**

## Cómo probar
1. Te lleva a `login.php`. Entrá a **Crear cuenta**, registrá un usuario (ej. `prueba1`) y una contraseña.
2. Registrá de nuevo `prueba1` (o `PRUEBA1`) → debe decir que ya existe.
3. Iniciá sesión con la contraseña correcta → entrás al juego. Con una incorrecta → "Usuario o contraseña incorrectos".
4. Con la sesión abierta, abrí `http://localhost/drag-kaisen/login.php` → te devuelve al juego.
5. Tocá **Cerrar sesión**, luego el botón "Atrás" del navegador o abrí `index.php` → te manda a `login.php`.
6. Probá `http://localhost/drag-kaisen/includes/config.php` y `.../sql/database.sql` → no deben mostrar nada / dar "403 Forbidden".

## Verificar que las contraseñas no están en texto plano
En phpMyAdmin → base `drag_kaisen` → tabla `usuarios` → **Examinar**.
La columna `password_hash` debe verse como `$2y$10$....` (60 caracteres), no como la contraseña que escribiste.

## Subirlo a un hosting
1. Creá la base MySQL desde el panel del hosting e importá `sql/database.sql`
   (si el hosting no deja crear bases con `CREATE DATABASE`, borrá las dos primeras sentencias del `.sql`
   —`CREATE DATABASE` y `USE`— y elegí la base antes de importar).
2. Editá `includes/config.php` con el host, nombre de base, usuario y contraseña que te da el hosting.
3. Subí todo el contenido de la carpeta (incluida `includes/`) por FTP.
4. Usá **HTTPS** (la cookie de sesión se marca "secure" automáticamente cuando el sitio usa HTTPS).
5. Borrá `sql/` del servidor una vez importada la base.
6. Nunca subas `config.php` a un repositorio público (GitHub, etc.).

## Registro de peleas y daño (NUEVO)
Cada pelea queda guardada en la base de datos, con el daño de cada golpe.

**Archivos nuevos / modificados**
- `sql/database.sql` – ahora también crea las tablas `peleas` (una fila por pelea) y `pelea_golpes` (una fila por golpe).
- `api/pelea.php` – recibe del juego el inicio y el fin de cada pelea (JSON) y la guarda; también devuelve el historial y el detalle de cada pelea.
- `includes/catalogo.php` – stats de hechiceros y maldiciones del lado del servidor (tienen que coincidir con `script.js`).
- Botón **Historial** (en el encabezado del juego, igual que los demás botones): abre una ventana con una tabla de tus peleas, los totales de daño y, con "Ver golpes", el detalle golpe por golpe.
- `script.js` – al iniciar la batalla avisa al servidor y al terminar manda el daño de cada golpe.
- `index.php` – token CSRF para la API, el botón "Historial" y la ventana con la tabla.

**Qué se guarda**
- `peleas`: usuario, hechicero y enemigo (nombre y grado), HP inicial y final de cada uno, resultado (`victoria`, `derrota`, `abandonada`), turnos, daño total causado y recibido, fecha de inicio y de fin.
- `pelea_golpes`: orden, turno, quién atacó, quién defendió, daño del golpe y HP que le quedó al defensor.
- Si reiniciás la batalla a la mitad, queda como `abandonada` con los golpes que hubo hasta ese momento.

**Si ya tenías la base creada:** volvé a importar `sql/database.sql` en phpMyAdmin. Usa `IF NOT EXISTS`, así que no borra los usuarios; solo agrega las tablas nuevas.

**Cómo probar**
1. Iniciá sesión, elegí un hechicero y una maldición y tocá **Iniciar Batalla**.
2. Al terminar, en el registro de combate aparece "💾 Pelea guardada en el historial".
3. Tocá **Historial**: ves la tabla con tus peleas y el daño causado/recibido; "Ver golpes" muestra cada golpe en otra tabla ("← Volver" regresa a la lista, "Cerrar" o Esc cierran la ventana).
4. En phpMyAdmin → base `drag_kaisen` → tablas `peleas` y `pelea_golpes` → **Examinar**.

**Seguridad del registro**
- El usuario sale siempre de la sesión, nunca de lo que manda el navegador; la API exige sesión y token CSRF.
- Todas las consultas son preparadas (PDO), sin concatenar datos.
- El servidor no confía en el navegador: nombre, grado y HP salen de `includes/catalogo.php`, y cada daño se valida contra el ATK/DEF de ambos (si no es posible, la pelea se rechaza y no se guarda).
- Solo se puede cerrar una pelea propia y una sola vez; el historial solo muestra las peleas del usuario logueado.
- Si cambiás los stats en `script.js`, cambialos también en `includes/catalogo.php`.


## Estructura MVC y API REST (NUEVO — para Postman)

```
drag-kaisen/
├── .env                 ← datos de conexión a MySQL (NO se sube a GitHub)
├── .env.example         ← modelo del .env, sin datos reales
├── .gitignore           ← le dice a Git qué NO subir (.env, logs...)
├── config/config.php    ← lee el .env y define DB_HOST, DB_NAME, DB_USER, DB_PASS
├── routes/api.php       ← RUTAS: verbo + dirección → controlador
├── controllers/UsuarioController.php ← CONTROLADOR: valida y arma la respuesta JSON
├── models/Usuario.php   ← MODELO: consultas SQL (SELECT, INSERT, DELETE)
├── api/index.php        ← punto de entrada de la API (router)
├── includes/db.php      ← conexión PDO a MySQL
└── index.php, login.php, script.js, estilos.css ← VISTAS del juego
```

### Endpoints
| Verbo  | URL                                              | Qué hace            |
|--------|--------------------------------------------------|---------------------|
| GET    | http://localhost/drag-kaisen/api/usuarios        | lista usuarios      |
| GET    | http://localhost/drag-kaisen/api/usuarios/1      | muestra el usuario 1|
| POST   | http://localhost/drag-kaisen/api/usuarios        | crea un usuario     |
| DELETE | http://localhost/drag-kaisen/api/usuarios/1      | borra el usuario 1  |

Body del POST (Postman → Body → raw → JSON):
```json
{ "usuario": "santiago", "password": "123456" }
```
Si `/api/usuarios` da "404 Not Found" de Apache (mod_rewrite apagado), usá
`http://localhost/drag-kaisen/api/index.php/usuarios`, que funciona siempre.

Por seguridad la API solo responde a pedidos hechos desde la misma PC (`API_SOLO_LOCAL=true` en `.env`).
