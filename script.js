'use strict';

/* ---------- Datos base: personajes y enemigos ---------- */
const players = [
    // Nivel 1 (grado 1) - 5 personajes
    { id:'p1', name:'Panda', img:'assets/img/hech/panda.jpg', level:1, hp:120, atk:18, def:6, spd:12 },
    { id:'p2', name:'Nobara', img:'assets/img/hech/nobara.jpg', level:1, hp:110, atk:20, def:5, spd:14 },
    { id:'p3', name:'Toji', img:'assets/img/hech/toji.jpg', level:1, hp:100, atk:22, def:4, spd:16 },
    { id:'p4', name:'Hakari', img:'assets/img/hech/hakari.jpg', level:1, hp:125, atk:16, def:7, spd:11 },
    { id:'p5', name:'Maki', img:'assets/img/hech/maki.jpg', level:1, hp:130, atk:15, def:8, spd:10 },

    // Nivel 2 (grado 2) - 4 personajes
    { id:'p6', name:'Gojo', img:'assets/img/hech/gojo.jpg', level:2, hp:180, atk:26, def:10, spd:13 },
    { id:'p7', name:'Yuji', img:'assets/img/hech/yuji.jpg', level:2, hp:150, atk:28, def:9, spd:14 },
    { id:'p8', name:'Geto', img:'assets/img/hech/geto.jpg', level:2, hp:170, atk:24, def:12, spd:12 },
    { id:'p9', name:'Choso', img:'assets/img/hech/choso.jpg', level:2, hp:155, atk:27, def:11, spd:13 },
    { id:'p10', name:'Inumaki', img:'assets/img/hech/inumaki.jpg', level:3, hp:155, atk:22, def:16, spd:15 },
    { id:'p11', name:'Sukuna', img:'assets/img/hech/sukuna.jpg', level:3, hp:170, atk:24, def:18, spd:14 },
     { id:'p12', name:'Megumi', img:'assets/img/hech/megumi.jpg', level:3, hp:165, atk:28, def:17, spd:16 },

    // Nivel 3 (grado 3) - 1 personaje
    { id:'p13', name:'Nah, Id Swim', img:'assets/img/hech/gojosupremo.jpg', level:3, hp:220, atk:32, def:20, spd:13 },
];

const enemies = [
    { id:'e1', name:'Maldición 1', img:'assets/img/mald/caballomaldicion.png', level:1, hp:100, atk:14, def:4, spd:10 },
    { id:'e2', name:'Maldición 2', img:'assets/img/mald/goonig curse.png', level:1, hp:110, atk:16, def:5, spd:11 },
    { id:'e3', name:'Maldición 3', img:'assets/img/mald/la maldicion del amor.png', level:1, hp:95, atk:18, def:3, spd:13 },
    { id:'e4', name:'Maldición 4', img:'assets/img/mald/locust guy.png', level:1, hp:120, atk:12, def:6, spd:9 },

    { id:'e5', name:'Maldición 5', img:'assets/img/mald/maldicion de la comedia.png', level:2, hp:150, atk:24, def:9, spd:12 },
    { id:'e6', name:'Maldición 6', img:'assets/img/mald/maldicion de las ghormigas.png', level:2, hp:160, atk:22, def:10, spd:11 },
    { id:'e7', name:'Maldición 7', img:'assets/img/mald/maldicion lucianezca.png', level:2, hp:155, atk:26, def:8, spd:13 },
    { id:'e8', name:'Maldición 8', img:'assets/img/mald/pedocurse.png', level:2, hp:200, atk:20, def:12, spd:10 },

    { id:'e9', name:'Maldición 9', img:'assets/img/mald/polentaaa.png', level:3, hp:210, atk:34, def:15, spd:14 },
    { id:'e10', name:'Maldición 10', img:'assets/img/mald/zapacusre.png', level:3, hp:220, atk:32, def:18, spd:13 },
];

/* Estado actual */
let currentEnemy = null;
let currentPlayer = null;
let battleRunning = false;
let playerBaseStats = null;
let enemyBaseStats = null;
let battleInterval = null;   // temporizador de la batalla en curso
let peleaActual = null;      // { promise, golpes } de la pelea que se está registrando

/* --- UI referencias --- */
// ¡NUEVA REFERENCIA UNIFICADA!
const allPlayersList = document.getElementById('allPlayersList'); 
const enemyListEl = document.getElementById('enemyList');
const enemyCard = document.getElementById('enemyCard');
const playerCard = document.getElementById('playerCard');
const dropZone = document.getElementById('dropZone');
const logContent = document.getElementById('logContent');
const playerStats = document.getElementById('playerStats');
const enemyStats = document.getElementById('enemyStats');
const randomEnemyBtn = document.getElementById('randomEnemyBtn');
const resetBtn = document.getElementById('resetBtn');
const musicToggle = document.getElementById('musicToggle');
const bgMusic = document.getElementById('bgMusic');
const startBattleBtn = document.getElementById('startBattleBtn'); 


/* --- INTRO Y MÚSICA (Sin cambios) --- */
const introVideo = document.getElementById('introVideo');
const introDiv = document.getElementById('intro');
const pantallaInicio = document.getElementById('pantallaInicio');
const btnInicio = document.getElementById('btnInicio');

if (introVideo) {
    introVideo.addEventListener('ended', () => {
        introDiv.style.display = 'none';
        pantallaInicio.classList.remove('oculto');
    });
}
if (btnInicio) {
    btnInicio.addEventListener('click', () => {
        pantallaInicio.style.animation = 'fadeOut 14s ease forwards';
        setTimeout(() => {
            pantallaInicio.classList.add('oculto');
        }, 1000);
    });
}
musicToggle.addEventListener('change', ()=>{
    if(musicToggle.checked){
        bgMusic.play().catch(()=>{/* autoplay bloqueado en algunos navegadores */});
    } else {
        bgMusic.pause();
    }
});


/* --- REGISTRO DE PELEAS EN LA BASE DE DATOS --- */
const csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

async function enviarApi(cuerpo){
    let r;
    try{
        r = await fetch('api/pelea.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
            body: JSON.stringify(cuerpo)
        });
    }catch(err){
        return { ok: false, error: 'No se pudo conectar con el servidor.' };
    }
    try{
        return await r.json();
    }catch(err){
        return { ok: false, error: `Respuesta inválida del servidor (HTTP ${r.status}). ¿Está la carpeta api/ copiada y MySQL encendido?` };
    }
}

// Al empezar: el servidor crea la pelea y devuelve su id (o null si falla).
function iniciarPeleaServidor(hechiceroId, enemigoId){
    return enviarApi({ accion: 'iniciar', hechicero_id: hechiceroId, enemigo_id: enemigoId })
        .then(r => (r.ok ? r.pelea_id : null));
}

// Al terminar (o abandonar): se manda el daño de cada golpe y el servidor lo valida y guarda.
async function finalizarPeleaServidor(peleaPromise, golpes, abandonada){
    const id = await peleaPromise;
    if(id === null){
        log('⚠️ No se pudo registrar la pelea en la base de datos.');
        return;
    }
    const r = await enviarApi({ accion: 'finalizar', pelea_id: id, danos: golpes, abandonada: abandonada });
    if(!r.ok){
        log('⚠️ No se pudo guardar el resultado de la pelea.');
    } else if(!abandonada){
        log(`💾 Pelea guardada en el historial (daño causado: ${r.dano_causado}, recibido: ${r.dano_recibido}).`);
    }
}

/* --- HISTORIAL DE PELEAS (ventana con tabla) --- */
const historialBtn = document.getElementById('historialBtn');
const historialModal = document.getElementById('historialModal');
const historialHead = document.getElementById('historialHead');
const historialBody = document.getElementById('historialBody');
const historialResumen = document.getElementById('historialResumen');
const historialTitulo = document.getElementById('historialTitulo');
const historialVolver = document.getElementById('historialVolver');
const historialCerrar = document.getElementById('historialCerrar');
const ETIQUETA_RESULTADO = { victoria: 'Victoria', derrota: 'Derrota', abandonada: 'Abandonada' };

function tablaEncabezado(columnas){
    historialHead.innerHTML = '';
    const tr = document.createElement('tr');
    columnas.forEach(c => {
        const th = document.createElement('th');
        th.textContent = c;
        tr.appendChild(th);
    });
    historialHead.appendChild(tr);
}

function tablaFila(valores, claseResultado){
    const tr = document.createElement('tr');
    valores.forEach((v, i) => {
        const td = document.createElement('td');
        td.textContent = v;
        if(claseResultado && i === 3) td.className = 'res-' + claseResultado;
        tr.appendChild(td);
    });
    historialBody.appendChild(tr);
    return tr;
}

function tablaVacia(columnas, texto){
    const tr = document.createElement('tr');
    const td = document.createElement('td');
    td.colSpan = columnas;
    td.textContent = texto;
    tr.appendChild(td);
    historialBody.appendChild(tr);
}

async function mostrarHistorial(){
    historialTitulo.textContent = 'Historial de peleas';
    historialVolver.hidden = true;
    historialResumen.textContent = 'Cargando...';
    historialBody.innerHTML = '';
    const columnas = ['Fecha', 'Hechicero', 'Maldición', 'Resultado', 'Turnos', 'Daño causado', 'Daño recibido', ''];
    tablaEncabezado(columnas);

    const r = await enviarApi({ accion: 'historial' });
    if(!r.ok){
        historialResumen.textContent = 'No se pudo cargar el historial: ' + (r.error || 'error desconocido');
        return;
    }
    const s = r.resumen;
    historialResumen.textContent =
        `Peleas: ${s.total} · Victorias: ${s.victorias} · Derrotas: ${s.derrotas} · ` +
        `Daño causado total: ${s.causado} · Daño recibido total: ${s.recibido}`;

    if(r.peleas.length === 0){
        tablaVacia(columnas.length, 'Todavía no peleaste. ¡Elegí un hechicero y una maldición y empezá una batalla!');
        return;
    }
    r.peleas.forEach(p => {
        const tr = tablaFila([
            p.iniciada_en,
            `${p.hechicero_nombre} (G${p.hechicero_grado})`,
            `${p.enemigo_nombre} (G${p.enemigo_grado})`,
            ETIQUETA_RESULTADO[p.resultado] || p.resultado,
            p.turnos,
            p.dano_causado,
            p.dano_recibido,
            ''
        ], p.resultado);
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = 'Ver golpes';
        btn.addEventListener('click', () => mostrarDetalle(p.id));
        tr.lastElementChild.appendChild(btn);
    });
}

async function mostrarDetalle(id){
    historialVolver.hidden = false;
    historialResumen.textContent = 'Cargando...';
    historialBody.innerHTML = '';
    const columnas = ['Turno', 'Atacante', 'Defensor', 'Daño', 'HP restante del defensor'];
    tablaEncabezado(columnas);

    const r = await enviarApi({ accion: 'detalle', pelea_id: id });
    if(!r.ok){
        historialResumen.textContent = 'No se pudo cargar la pelea: ' + (r.error || 'error desconocido');
        return;
    }
    const p = r.pelea;
    historialTitulo.textContent = `${p.hechicero_nombre} vs ${p.enemigo_nombre} — ${ETIQUETA_RESULTADO[p.resultado] || p.resultado}`;
    historialResumen.textContent =
        `${p.iniciada_en} · Daño causado: ${p.dano_causado} · Daño recibido: ${p.dano_recibido} · Turnos: ${p.turnos}`;
    if(r.golpes.length === 0){
        tablaVacia(columnas.length, 'Sin golpes registrados.');
        return;
    }
    r.golpes.forEach(g => tablaFila([g.turno, g.atacante_nombre, g.defensor_nombre, g.dano, g.hp_defensor_restante]));
}

function abrirHistorial(){
    historialModal.hidden = false;
    mostrarHistorial();
}
function cerrarHistorial(){
    historialModal.hidden = true;
}
if(historialBtn && historialModal){
    historialBtn.addEventListener('click', abrirHistorial);
    historialCerrar.addEventListener('click', cerrarHistorial);
    historialVolver.addEventListener('click', mostrarHistorial);
    historialModal.addEventListener('click', (e) => { if(e.target === historialModal) cerrarHistorial(); });
    document.addEventListener('keydown', (e) => { if(e.key === 'Escape' && !historialModal.hidden) cerrarHistorial(); });
}

/* --- FUNCIONES DE UTILIDAD (Sin cambios) --- */
function cloneEntity(entity){
    return JSON.parse(JSON.stringify(entity));
}

function log(text){
    const time = new Date().toLocaleTimeString();
    const node = document.createElement('div');
    node.textContent = `[${time}] ${text}`;
    logContent.prepend(node);
}

function computeDamage(att, def){
    const base = Math.max(1, att.atk - Math.floor(def.def/2));
    const variation = 0.9 + Math.random()*0.2;
    return Math.max(1, Math.round(base * variation));
}

function checkBattleReadiness() {
    if (currentPlayer && currentEnemy && !battleRunning) {
        startBattleBtn.disabled = false;
        startBattleBtn.style.backgroundColor = '#2ecc71';
        startBattleBtn.style.color = 'white';
    } else {
        startBattleBtn.disabled = true;
        startBattleBtn.style.backgroundColor = '#ddd';
        startBattleBtn.style.color = '#333';
    }
}

/* --- RENDERIZADO DE STATS CON BARRA DE VIDA (Sin cambios) --- */
function statsHtml(entity, maxHp){
    const currentHp = entity.hp;
    const maxHealth = maxHp || currentHp; 
    const healthPercent = (currentHp / maxHealth) * 100;
    
    const finalPercent = Math.max(0, healthPercent);

    return `
        <div class="healthBar">
            <div class="healthBar-fill" style="width: ${finalPercent}%;"></div>
        </div>
        <div style="text-align:center;font-size:14px;font-weight:700;">HP: ${currentHp} / ${maxHealth}</div>
        <div style="margin-top:8px;"><strong>ATK:</strong> ${entity.atk}</div>
        <div><strong>DEF:</strong> ${entity.def}</div>
        <div><strong>SPD:</strong> ${entity.spd}</div>
        <div><strong>Grado:</strong> ${entity.level}</div>
    `;
}

/* --- CREACIÓN Y RENDERIZADO DE CARTAS --- */
function createEntityCard(entity, isPlayer=true){
    const div = document.createElement('div');
    div.className = 'entity';
    div.draggable = isPlayer;
    div.dataset.id = entity.id;

    const img = document.createElement('img');
    img.src = entity.img;
    img.alt = entity.name;
    div.appendChild(img);

    const name = document.createElement('div');
    name.className = 'name';
    name.textContent = entity.name;
    div.appendChild(name);

    const lvl = document.createElement('div');
    lvl.className = 'levelTag';
    lvl.textContent = 'Grado ' + entity.level;
    div.appendChild(lvl);

    if(isPlayer){
        div.addEventListener('dragstart', (e)=>{
            e.dataTransfer.setData('text/plain', entity.id);
            setTimeout(()=> div.classList.add('dragging'), 5);
        });
        div.addEventListener('dragend', ()=> div.classList.remove('dragging'));
    }

    div.addEventListener('click', ()=>{
        if(battleRunning) {
             log('⚠️ ¡Batalla en curso! Espera a que termine o reinicia.');
             return;
        }
        if(isPlayer){
            setPlayer(entity.id);
        } else {
            setEnemy(entity.id);
        }
    });

    return div;
}

// FUNCIÓN DE RENDERIZADO DE LISTAS ACTUALIZADA
function renderLists(){
    // Limpiar el contenedor único de hechiceros
    allPlayersList.innerHTML = '';
    enemyListEl.innerHTML = '';

    // Rellena la lista única de hechiceros
    players.forEach(p=>{
        const card = createEntityCard(p, true);
        allPlayersList.appendChild(card);
    });

    // Rellena la lista de enemigos
    enemies.forEach(e=>{
        const card = createEntityCard(e, false);
        enemyListEl.appendChild(card);
    });
}

/* --- SELECCIÓN Y VISTAS (Sin cambios) --- */
function setEnemy(enemyId){
    const e = enemies.find(x=>x.id===enemyId);
    if(!e) return;
    currentEnemy = cloneEntity(e);
    enemyBaseStats = cloneEntity(e);
    renderEnemyCard();
    log(`Enemigo elegido: ${currentEnemy.name}`);
    checkBattleReadiness();
}

function setPlayer(playerId){
    const p = players.find(x=>x.id===playerId);
    if(!p) return;
    currentPlayer = cloneEntity(p);
    playerBaseStats = cloneEntity(p);
    renderPlayerCard();
    log(`Seleccionaste: ${currentPlayer.name}`);
    checkBattleReadiness();
}

function renderEnemyCard(){
    if(!currentEnemy){
        enemyCard.classList.add('empty');
        enemyCard.innerHTML = 'No hay enemigo';
        enemyStats.innerHTML = '';
        return;
    }
    enemyCard.classList.remove('empty');
    enemyCard.innerHTML = `
        <img src="${currentEnemy.img}" alt="${currentEnemy.name}" style="width:120px;height:120px;object-fit:cover;border-radius:8px">
        <div style="font-weight:700;margin-top:8px">${currentEnemy.name}</div>
    `;
    enemyStats.innerHTML = enemyBaseStats ? statsHtml(currentEnemy, enemyBaseStats.hp) : '';
}

function renderPlayerCard(){
    if(!currentPlayer){
        playerCard.classList.add('empty');
        playerCard.innerHTML = 'No seleccionado';
        playerStats.innerHTML = '';
        return;
    }
    playerCard.classList.remove('empty');
    playerCard.innerHTML = `
        <img src="${currentPlayer.img}" alt="${currentPlayer.name}" style="width:120px;height:120px;object-fit:cover;border-radius:8px">
        <div style="font-weight:700;margin-top:8px">${currentPlayer.name}</div>
    `;
    playerStats.innerHTML = playerBaseStats ? statsHtml(currentPlayer, playerBaseStats.hp) : '';
}

/* --- DRAG & DROP HANDLERS (Sin cambios) --- */
dropZone.addEventListener('dragover', (e)=>{
    e.preventDefault();
    dropZone.classList.add('dragover');
});
dropZone.addEventListener('dragleave', ()=> dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', (e)=>{
    e.preventDefault();
    dropZone.classList.remove('dragover');
    const id = e.dataTransfer.getData('text/plain');
    if(!id) return;
    const p = players.find(x=>x.id===id);
    if(!p) return;
    
    if(battleRunning) {
        log('⚠️ ¡Batalla en curso! Reinicia para elegir un nuevo hechicero.');
        return;
    }
    
    setPlayer(id);
    log('Hechicero listo. Selecciona un enemigo y presiona "Iniciar Batalla".');
});

/* --- BOTONES --- */
// FUNCIÓN ACTUALIZADA: Elegir enemigo aleatorio del mismo grado
randomEnemyBtn.addEventListener('click', ()=>{
    if(battleRunning) {
        log('⚠️ ¡Batalla en curso! Reinicia si quieres cambiar de enemigo.');
        return;
    }
    if(!currentPlayer) {
        log('⚠️ Debes seleccionar un Hechicero primero para elegir una Maldición de su mismo Grado.');
        return;
    }

    const currentLevel = currentPlayer.level;
    const sameLevelEnemies = enemies.filter(e => e.level === currentLevel);

    if (sameLevelEnemies.length === 0) {
        log(`❌ No hay Maldiciones disponibles de Grado ${currentLevel}.`);
        return;
    }

    // Elegimos un enemigo al azar del grupo filtrado
    const idx = Math.floor(Math.random() * sameLevelEnemies.length);
    setEnemy(sameLevelEnemies[idx].id);
});

resetBtn.addEventListener('click', resetBattle);

startBattleBtn.addEventListener('click', () => {
    if (!startBattleBtn.disabled) {
        startBattle();
    }
});


/* --- LÓGICA DE COMBATE (Sin cambios) --- */
function startBattle(){
    if(battleRunning){
        log('Ya hay una batalla en curso.');
        return;
    }
    if(!currentEnemy || !currentPlayer){
        log('Falta enemigo o jugador.');
        return;
    }

    battleRunning = true;
    checkBattleReadiness();
    log(`🔥 ¡Comienza la batalla! ${currentPlayer.name} vs ${currentEnemy.name}`);
    
    renderPlayerCard();
    renderEnemyCard();

    let p = currentPlayer;
    let e = currentEnemy;

    let attacker = (p.spd >= e.spd) ? p : e;
    let defender = (attacker === p) ? e : p;
    let turnCount = 0;

    // Registro en la base de datos: un id de pelea y el daño de cada golpe, en orden
    const golpes = [];
    const peleaPromise = iniciarPeleaServidor(p.id, e.id);
    peleaActual = { promise: peleaPromise, golpes: golpes };

    const interval = setInterval(()=>{
        if(!battleRunning){ clearInterval(interval); return; }

        turnCount++;
        log(`--- Turno ${turnCount} ---`);

        // Ataque 1
        let dmg1 = computeDamage(attacker, defender);
        defender.hp = Math.max(0, defender.hp - dmg1);
        golpes.push(dmg1);
        log(`⚔️ ${attacker.name} ataca a ${defender.name} y causa ${dmg1} de daño. (HP: ${defender.hp})`);

        playerStats.innerHTML = statsHtml(p, playerBaseStats.hp);
        enemyStats.innerHTML = statsHtml(e, enemyBaseStats.hp);

        if(defender.hp <= 0){
            clearInterval(interval);
            battleRunning = false;
            checkBattleReadiness();
            log(defender === e ? `🏆 ¡Victoria! ${p.name} derrotó a ${e.name}.` : `💀 Derrota... ${p.name} fue vencido por ${e.name}.`);
            celebrate(defender === e ? 'win' : 'lose');
            peleaActual = null;
            finalizarPeleaServidor(peleaPromise, golpes, false);
            return;
        }

        // Contraataque
        [attacker, defender] = [defender, attacker];
        
        let dmg2 = computeDamage(attacker, defender);
        defender.hp = Math.max(0, defender.hp - dmg2);
        golpes.push(dmg2);
        log(`💥 ${attacker.name} contraataca por ${dmg2}. (HP: ${defender.hp})`);

        playerStats.innerHTML = statsHtml(p, playerBaseStats.hp);
        enemyStats.innerHTML = statsHtml(e, enemyBaseStats.hp);

        if(defender.hp <= 0){
            clearInterval(interval);
            battleRunning = false;
            checkBattleReadiness();
            log(defender === e ? `🏆 ¡Victoria! ${p.name} derrotó a ${e.name}.` : `💀 Derrota... ${p.name} fue vencido por ${e.name}.`);
            celebrate(defender === e ? 'win' : 'lose');
            peleaActual = null;
            finalizarPeleaServidor(peleaPromise, golpes, false);
            return;
        }

        [attacker, defender] = [defender, attacker];
        
    }, 1500);
    battleInterval = interval;
}

/* --- EFECTOS VISUALES Y REINICIO (Sin cambios) --- */
function celebrate(result){
    const drop = dropZone;
    if(result === 'win'){
        drop.style.borderColor = '#2ecc71';
        setTimeout(()=> drop.style.borderColor = '', 1200);
    } else {
        drop.style.borderColor = '#e74c3c';
        setTimeout(()=> drop.style.borderColor = '', 1200);
    }
}

function resetBattle(){
    // Si se reinicia en medio de una pelea, se guarda como "abandonada" con los golpes que hubo
    if(battleInterval){ clearInterval(battleInterval); battleInterval = null; }
    if(battleRunning && peleaActual){
        finalizarPeleaServidor(peleaActual.promise, peleaActual.golpes, true);
    }
    peleaActual = null;

    currentEnemy = null;
    currentPlayer = null;
    battleRunning = false;
    renderEnemyCard();
    renderPlayerCard();
    logContent.innerHTML = '';
    log('Batalla reiniciada. Selecciona tus luchadores.');
    checkBattleReadiness();
}

/* --- INICIO (Sin cambios) --- */
function init(){
    renderLists();
    renderEnemyCard();
    renderPlayerCard();
    checkBattleReadiness();
    if(musicToggle.checked){
        bgMusic.play().catch(()=>{/*ignorar autoplay bloqueo*/});
    }
}

init();