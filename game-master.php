<?php

require_once __DIR__.'/includes/bootstrap.php';
require_once __DIR__.'/includes/arqwar-identity.php';
require_once __DIR__.'/includes/arqwar-mascot.php';
require_once __DIR__.'/includes/avatars.php';

login_required_html();

$user=current_user();
$roomMode=$roomMode??false; $roomId=$roomId??0; $roomRole=$roomRole??'gm'; $roomTeamId=$roomTeamId??0;
$roomPlayer=$roomMode && $roomRole==='player';

$dbError=null;
$scenarios=[];
$cards=[];
$incidents=[];
$teamProfiles=[];
$gameId=(int)($_GET['game_id']??0);
$forceNew=isset($_GET['new']);
$game=null;
$areas=[];

try{

    $scenarios=rows('SELECT * FROM scenarios WHERE is_active=1 ORDER BY area,name');

    $areas=array_values(array_unique(array_column($scenarios,'area')));

    $cards=rows('SELECT * FROM cards WHERE is_active=1 ORDER BY category,name');

    $incidents=rows('SELECT * FROM incidents WHERE is_active=1 ORDER BY incident_type,name');

    $teamProfiles=rows('SELECT tp.*,(SELECT COUNT(*) FROM team_members tm WHERE tm.team_profile_id=tp.id) member_count FROM team_profiles tp WHERE tp.is_active=1 ORDER BY tp.alias');

    if(!$forceNew && !$gameId){
        $latest=latest_game();
        $gameId=(int)($latest['id']??0);
    }

    if(!$forceNew && $gameId){
        $game=get_game($gameId);
    }

}catch(Throwable $e){
    $dbError=$e->getMessage();
}

$activeRoom=$game?get_room($gameId):null;
$individualMode=$game && (($game['mode']??'teams')==='individual');
$isGameCreator=$activeRoom && (int)($activeRoom['created_by']??0)===(int)($user['id']??0);
if($individualMode && $isGameCreator && ($game['status']??'finished')!=='finished'){
    $creatorPlayer=ensure_individual_player($gameId,(int)($user['id']??0));
    if($creatorPlayer) $roomTeamId=(int)$creatorPlayer['id'];
}
$myParticipant=current_participant(false);
$csrf=csrf_token();

$cardsClient=array_map(fn($c)=>[
    'id'=>(int)$c['id'],
    'code'=>$c['code'],
    'name'=>$c['name'],
    'category'=>$c['category'],
    'provider'=>$c['provider'],
    'monthly_cost'=>(float)$c['monthly_cost'],
    'security'=>(int)$c['security'],
    'availability'=>(int)$c['availability'],
    'scalability'=>(int)$c['scalability'],
    'performance'=>(int)$c['performance'],
    'reliability'=>(int)$c['reliability'],
    'devops'=>(int)$c['devops'],
    'complexity'=>(int)$c['complexity'],
    'description'=>$c['description'],
    'lifecycle_note'=>$c['lifecycle_note']??null
],$cards);

?>

<!doctype html>
<html lang="es">

<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ARQWAR · <?= $roomMode?'Sala':'Game Master' ?></title>

<link href="assets/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/app.css?v=20261014-avatar">
<?php if($game): ?><link rel="stylesheet" href="assets/css/arqwar-mesa.css?v=20261028-1"><?php endif; ?>
<?php if(!$game): ?><link rel="stylesheet" href="assets/css/arqwar-launcher.css?v=20261027-v1">
<?php endif; ?>
<link rel="stylesheet" href="assets/css/arqwar-identity.css?v=20261028-2"><link rel="stylesheet" href="assets/css/avatar-3d.css?v=3">
<?php if($game): ?><link rel="stylesheet" href="assets/css/arqwar-mascot.css?v=20261029-1"><?php endif; ?>
</head>

<body class="gm-body <?= !$game ? 'launcher-body' : '' ?>">

<?php if($game): ?>
<nav class="navbar navbar-dark border-bottom border-secondary-subtle">

<div class="container-fluid">

<?= arqwar_brand_html($roomMode?'SALA':'GAME MASTER') ?>

<div class="d-flex gap-2 flex-wrap">

<a class="btn btn-sm btn-outline-light" href="index.php">
Inicio
</a>

<a class="btn btn-sm btn-outline-info" href="rooms.php">
Salas
</a>

<?php if($activeRoom): ?><a class="btn btn-sm btn-outline-info" href="room.php?game_id=<?=$gameId?>">Abrir sala</a><?php endif; ?>

<a class="btn btn-sm btn-outline-info" target="_blank" href="arena.php<?= $gameId?'?game_id='.$gameId:'' ?>">
Arena
</a>

<a class="btn btn-sm btn-outline-info" href="<?=is_admin()?'participants.php':'mi-perfil.php'?>">
<?=is_admin()?'Participantes':'Mi perfil'?>
</a>

<a class="btn btn-sm btn-outline-warning" href="teams.php">
Equipos
</a>

<a class="btn btn-sm btn-outline-success" href="ranking.php">
Ranking
</a>

<a class="btn btn-sm btn-outline-success" href="winners.php">
Ganadores
</a>

<a class="btn btn-sm btn-outline-light" href="cards.php">
Cartas
</a>

<a class="btn btn-sm btn-outline-light" href="account.php">
Mi cuenta
</a>

<?php if(is_admin()):?>

<a class="btn btn-sm btn-danger" href="admin.php">
Admin CRUD
</a>

<?php if($roomMode): ?><button class="btn btn-sm btn-outline-danger" id="deleteRoomBtn">Eliminar sala</button><?php endif; ?>

<?php endif;?>

<a class="btn btn-sm btn-outline-secondary" href="logout.php">
Salir
</a>

</div>
</div>
</nav>
<?php else: ?>
<?= arqwar_sidebar_html('nueva', true, 0, false) ?>
<?php endif; ?>

<main class="container-fluid py-3">

<?php if($dbError): ?>

<div class="alert alert-danger">
No se pudo conectar a MySQL:
<?=e($dbError)?>.

<a href="setup.php">Instalar base</a>
o
<a href="upgrade.php">actualizar</a>.
</div>

<?php endif; ?>


<div id="toastArea"></div>


<?php if(!$game): ?>

<div class="launcher-shell">

  <section class="launcher-hero">
    <div class="launcher-hero-copy">
      <div class="launcher-kicker"><span class="pulse-dot"></span> MODO DE JUEGO · CENTRO DE DESAFÍOS</div>
      <h1>NUEVA <span>PARTIDA</span></h1>
      <p>Configura una experiencia de aprendizaje estratégico basada en casuísticas reales de Arquitectura TI.</p>
      <div class="launcher-pill-row">
        <span>✦ Casos reales</span><span>◈ Aprendizaje práctico</span><span>◉ Decisiones estratégicas</span>
      </div>
    </div>
    <div class="launcher-hero-art" aria-hidden="true"></div>
    <div class="launcher-hero-scan" aria-hidden="true"></div>
  </section>

  <section class="launcher-mode-section">
    <div class="launcher-section-head">
      <div><div class="launcher-kicker">01 · MODALIDAD</div><h2>Selecciona cómo quieres jugar</h2></div>
      <div class="launcher-live"><span></span> SISTEMA ONLINE</div>
    </div>
    <div class="launcher-mode-grid">
      <button type="button" class="launcher-mode-card is-active" data-launcher-mode="teams">
        <div class="launcher-mode-media launcher-team-art"><div class="mode-orb">◉</div><div class="mode-squad">◈ ◈ ◈ ◈</div></div>
        <div class="launcher-mode-body">
          <div class="launcher-mode-tag">MODO 01 · COOPERATIVO</div><h3>BATALLA <strong>POR EQUIPOS</strong></h3>
          <p>Forma un equipo de 2 a 8 participantes y resuelve casuísticas de Arquitectura TI en escenarios competitivos.</p>
          <div class="launcher-mode-stats"><span>👥 2–8 jugadores</span><span>◎ Estrategia</span><span>🏆 Ranking</span></div>
          <div class="launcher-select-btn">Seleccionar modo <b>→</b></div>
        </div>
      </button>
      <button type="button" class="launcher-mode-card launcher-mode-individual" data-launcher-mode="individual">
        <div class="launcher-mode-media launcher-solo-art"><div class="mode-orb">◉</div><div class="mode-avatar">◒</div></div>
        <div class="launcher-mode-body">
          <div class="launcher-mode-tag">MODO 02 · COMPETITIVO</div><h3>BATALLA <strong>INDIVIDUAL</strong></h3>
          <p>Enfréntate individualmente a casuísticas de Arquitectura TI y demuestra tus conocimientos en cada ronda.</p>
          <div class="launcher-mode-stats"><span>👤 1 jugador</span><span>⌁ Desafíos</span><span>🏆 XP individual</span></div>
          <div class="launcher-select-btn">Seleccionar modo <b>→</b></div>
        </div>
      </button>
    </div>
  </section>

  <form id="createGameForm" class="launcher-config">
    <input type="hidden" name="csrf" value="<?=e($csrf)?>"><input type="hidden" name="action" value="create_game">
    <select name="game_mode" id="gameModeSelect" class="launcher-mode-select-hidden" aria-hidden="true" tabindex="-1"><option value="teams" selected>Batalla por equipos</option><option value="individual">Batalla individual</option></select>

    <div class="launcher-config-head">
      <div><div class="launcher-kicker">02 · CONFIGURACIÓN</div><h2>Construye el escenario de la batalla</h2><p>Elige el área, la casuística, los participantes y las reglas de acceso.</p></div>
      <a class="launcher-admin-link" href="teams.php">⚙ Administrar equipos</a>
    </div>

    <div class="launcher-stepbar">
      <div class="launcher-step is-active"><b>01</b><span>Área</span></div><i>›</i><div class="launcher-step"><b>02</b><span>Casuística</span></div><i>›</i><div class="launcher-step"><b>03</b><span>Jugadores</span></div><i>›</i><div class="launcher-step"><b>04</b><span>Partida</span></div>
    </div>

    <div class="launcher-config-grid">
      <section class="launcher-panel launcher-scenario-panel">
        <div class="launcher-panel-title"><span class="launcher-icon">01</span><div><b>Área tecnológica</b><small>Define el dominio del desafío</small></div></div>
        <label class="launcher-label" for="scenarioAreaSelect">Área / categoría general</label>
        <select class="form-select form-select-lg launcher-control" id="scenarioAreaSelect" required><option value="">Seleccionar área...</option><?php foreach($areas as $area): ?><option value="<?=e($area)?>"><?=e($area)?></option><?php endforeach; ?></select>
        <div class="launcher-help">Desarrollo · Data · DevOps · MLOps · FinOps · RPA · Ciberseguridad · Cloud · IA · SRE</div>
        <div class="launcher-panel-title mt-4"><span class="launcher-icon">02</span><div><b>Casuística / problemática</b><small>El reto se filtra según el área elegida</small></div></div>
        <select class="form-select form-select-lg launcher-control" id="scenarioSelect" name="scenario_id" required disabled><option value="">Primero selecciona un área...</option><?php foreach($scenarios as $s): ?><option value="<?=$s['id']?>" data-area="<?=e($s['area'])?>" data-client="<?=e($s['client_name'])?>" data-description="<?=e($s['description'])?>" data-budget="<?=number_format($s['budget_monthly'],0,'.','')?>" data-load="<?=e($s['user_load'])?>" data-sla="<?=e($s['sla_target'])?>"><?=e($s['name'])?></option><?php endforeach; ?></select>
        <div id="scenarioPreview" class="launcher-scenario-preview"><div class="launcher-empty">Selecciona un área y una casuística para visualizar el reto.</div></div>
      </section>

      <section class="launcher-panel launcher-rules-panel">
        <div class="launcher-panel-title"><span class="launcher-icon">03</span><div><b>Jugadores y reglas</b><small>Configura quién participa</small></div></div>
        <div id="gameModeHint" class="launcher-mode-hint"><b>Por equipos:</b> selecciona entre 2 y 8 equipos. Cada equipo tendrá su propio panel, dado y cartas.</div>
        <div id="teamModeOptions">
          <div class="launcher-subhead"><span>Equipos disponibles</span><small id="selectedTeamsCount">0 equipos seleccionados</small></div>
          <?php if(count($teamProfiles)<2): ?><div class="alert alert-warning launcher-alert">Necesitas al menos 2 equipos activos para una batalla por equipos. También puedes crear una batalla individual.</div><?php endif; ?>
          <div class="launcher-team-grid">
            <?php foreach($teamProfiles as $t): ?><label class="launcher-team-option team-select-option"><input class="form-check-input" type="checkbox" name="team_profile_ids[]" value="<?=$t['id']?>"><span class="launcher-team-check"></span><span class="launcher-team-copy"><b><?=e($t['alias'])?></b><small><?=$t['member_count']?> participante(s)</small></span><span class="launcher-team-signal"></span></label><?php endforeach; ?>
          </div>
        </div>
        <div class="launcher-rule-card"><span>🎲</span><div><b>Dado estratégico</b><small id="diceRuleText">Cada equipo elige un número del 1 al 6 justo antes de su lanzamiento en cada ronda.</small></div><strong>1–6</strong></div>
      </section>
    </div>

    <section class="launcher-panel launcher-details-panel">
      <div class="launcher-panel-title"><span class="launcher-icon">04</span><div><b>Identidad y acceso de la partida</b><small>Estos datos aparecerán en la sala</small></div></div>
      <div class="launcher-detail-grid">
        <div><label class="launcher-label">Nombre de la partida</label><input class="form-control launcher-control" name="name" value="Batalla de Arquitectura"></div>
        <div><label class="launcher-label">Nombre de la sala</label><input class="form-control launcher-control" name="room_name" value="Sala de Batalla de Arquitectura" maxlength="120" required></div>
        <div><label class="launcher-label">Rondas</label><input class="form-control launcher-control" type="number" min="3" max="12" name="max_rounds" value="8"></div>
        <div><label class="launcher-label">Acceso</label><select class="form-select launcher-control" name="room_private" id="roomPrivateSelect"><option value="1" selected>Privada · con contraseña</option><option value="0">Pública · sin contraseña</option></select></div>
        <div class="launcher-password-field"><label class="launcher-label">Contraseña de la sala</label><input class="form-control launcher-control" type="password" name="room_password" id="roomPasswordInput" minlength="4" maxlength="120" placeholder="Mínimo 4 caracteres" required></div>
        <div class="launcher-access-note">🔐 <b>Sala protegida</b><span>Los jugadores deberán ingresar la contraseña para entrar cuando el acceso sea privado.</span></div>
      </div>
    </section>

    <div class="launcher-submit-bar"><div><span class="launcher-kicker">ARQWAR · READY CHECK</span><b>Todo listo para desplegar el escenario.</b><small>La lógica de creación, dados, cartas, rondas y ranking permanece sin cambios.</small></div><button class="launcher-create-btn" type="submit"><span>CREAR PARTIDA</span><b>→</b></button></div>
  </form>
</div>

<?php else: ?>


<div class="row g-3">


<div class="col-xl-3">

<div class="panel p-3 sticky-xl-top" style="top:1rem">

<div class="d-flex justify-content-between align-items-start">

<div>

<div class="eyebrow">
PARTIDA #<?=$gameId?>
</div>

<h1 class="h4 mb-1">
<?=e($game['name'])?>
</h1>

<div class="text-secondary small">
<?=e($game['scenario_area'])?>
·
<?=e($game['scenario_name'])?>
</div>

</div>

<span id="gmStatus" class="badge text-bg-secondary">
<?=e($game['status'])?>
</span>

</div>


<hr>


<div class="small text-secondary mb-2">
Ronda
</div>

<div class="display-6">

<span id="gmRound">
<?=$game['current_round']?>
</span>/

<span id="gmMaxRound">
<?=$game['max_rounds']?>
</span>

</div>


<div class="dice-rule-card mt-3">

<span>
🎲 DADO ESTRATÉGICO
</span>

<strong>
1–6
</strong>

<small>
Cada equipo elige su número de suerte justo antes de lanzar.
Puede cambiarlo en cada ronda.
</small>

</div>


<div class="d-grid gap-2 mt-3">

<button
class="btn btn-success js-action"
data-action="start_game"
<?= ($roomPlayer || !$isGameCreator)?'disabled title="Solo el creador de la partida puede iniciarla."':'' ?>>
Iniciar partida
</button>

<button
class="btn btn-warning js-action"
data-action="next_round"
<?= $roomPlayer?'disabled title="Solo el Game Master puede cambiar de ronda."':'' ?>>
Resolver incidente / Siguiente ronda
</button>

<button
class="btn btn-outline-danger js-action"
data-action="finish_game"
<?= ($roomPlayer || !$isGameCreator)?'disabled title="Solo el creador de la partida puede finalizarla."':'' ?>>
Finalizar y guardar ranking
</button>

</div>


<hr>

<h2 class="h6">
Lanzar incidente
</h2>


<form id="incidentForm" class="d-grid gap-2">

<input
type="hidden"
name="action"
value="trigger_incident">

<input
type="hidden"
name="game_id"
value="<?=$gameId?>">

<input
type="hidden"
name="csrf"
value="<?=e($csrf)?>">


<select class="form-select" name="incident_id" <?= $roomPlayer?'disabled':'' ?>>

<?php

$lastType='';

foreach($incidents as $i):

if($lastType!==$i['incident_type']){

if($lastType!=='') echo '</optgroup>';

echo '<optgroup label="'.e(strtoupper($i['incident_type'])).'">';

$lastType=$i['incident_type'];

}

?>

<option value="<?=$i['id']?>">
<?=e($i['name'])?>
</option>

<?php endforeach;

if($lastType!=='') echo '</optgroup>';

?>

</select>


<button class="btn btn-outline-warning" <?= $roomPlayer?'disabled title="Solo el Game Master puede activar incidentes."':'' ?>>
Activar incidente
</button>

</form>


<hr>

<?php if($roomMode): ?>
<a class="btn btn-outline-light w-100" href="rooms.php?leave=1">Salir de sala</a>
<?php else: ?>
<a class="btn btn-outline-light w-100" href="game-master.php?new=1">Nueva partida</a>
<?php endif; ?>

</div>

</div>


<div class="col-xl-9">

<?php if($roomMode): ?><div class="alert <?= $roomPlayer?'alert-info':'alert-warning' ?> py-2 mb-3"><b><?= $roomPlayer?'MODO JUGADOR':'MODO GAME MASTER' ?></b> · <?php if($roomPlayer): ?><?= $individualMode?'Solo puedes lanzar el dado y administrar las cartas de tu propia casilla. La partida se sincroniza para todos.':'Solo puedes lanzar el dado, registrar y retirar cartas de tu propio equipo. La partida se sincroniza para todos.' ?><?php else: ?>Control total de la sala.<?php endif; ?></div><?php endif; ?>

<div id="gmScenario"
class="panel p-3 mb-3">
</div>

<div id="gmIncident"
class="mb-3">
</div>

<!-- Mesa virtual: una por partida. Los puestos se generan desde api/state.php. -->
<section id="arqwarBattleTable" class="arqwar-mesa panel mb-3" aria-label="Mesa virtual de batalla ARQWAR">
  <header class="arqwar-mesa-head">
    <div>
      <span class="arqwar-mesa-kicker">// TABLERO TÁCTICO · PARTIDA #<?=$gameId?></span>
      <h2>Mesa de batalla <span>ARQWAR</span></h2>
      <p>Casillas independientes para cada jugador o equipo, sus cartas físicas y su dado estratégico.</p>
    </div>
    <div class="arqwar-mesa-head-info" id="arqwarMesaInfo" aria-live="polite">
      <span>Preparando mesa...</span>
    </div>
  </header>
  <div class="arqwar-mesa-stage" id="arqwarMesaStage">
    <div class="arqwar-mesa-players" id="arqwarMesaPlayers">
      <span class="arqwar-mesa-loading">Cargando las casillas de la partida...</span>
    </div>
  </div>
  <footer class="arqwar-mesa-foot">
    <span><b>✦</b> Las casillas aparecen automáticamente al crear una partida o entrar un jugador.</span>
    <span><b>▣</b> El dado y las cartas usan las acciones reales del juego.</span>
  </footer>
</section>

<!-- Los paneles originales se conservan, accesibles cuando se necesiten estadísticas avanzadas. -->
<details class="arqwar-mesa-details" id="arqwarTeamDetails">
  <summary><span>▦ &nbsp; Ver fichas completas, métricas y controles de jugadores</span><span class="arqwar-details-chevron" aria-hidden="true">⌄</span></summary>
  <div id="gmTeams" class="row g-3 mt-1"></div>
</details>


<div class="panel p-3 mt-3">
<h2 class="h5">Registro de partida</h2>
<div id="gmLogs" class="log-list"></div>
</div>

<div class="panel p-3 mt-3 arq-bot__game-panel" id="gameChat" role="dialog" aria-modal="false" aria-labelledby="gameChatTitle" hidden>
<div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
<div><div class="eyebrow">COMUNICACIÓN</div><h2 class="h5 mb-0" id="gameChatTitle">Chat de la partida</h2></div>
<div id="chatTabs" class="d-flex gap-1 flex-wrap"></div>
<button type="button" class="arq-bot__close" id="arqBotCloseGameChat" aria-label="Cerrar chat de la partida">×</button>
</div>
<div id="chatMessages" class="chat-messages mt-3"></div>
<form id="chatForm" class="chat-compose mt-2">
<div class="chat-identity" id="chatIdentity"><span class="chat-identity-dot">●</span><span>Enviando como <b><?=e($user['display_name'])?></b></span></div>
<div class="chat-input-wrap">
<button type="button" class="chat-emoji-btn" id="emojiToggle" title="Insertar emoji">😊</button>
<input id="chatInput" class="form-control" maxlength="500" placeholder="Escribe un mensaje..." autocomplete="off" required>
<div class="emoji-picker" id="emojiPicker" hidden>
<div class="emoji-picker-title">Caritas y emojis</div>
<div class="emoji-grid">
<?php foreach(['😀','😃','😄','😁','😆','😅','😂','🤣','😊','😇','🙂','🙃','😉','😌','😍','🥰','😘','😎','🤓','🧐','🤩','🥳','😏','😅','😢','😭','😡','🤬','😱','😴','🤔','🙄','😮','😲','🤯','😬','👍','👎','👏','🙌','🙏','💪','🔥','⚡','❤️','💙','💜','💚','💛','✨','🎉','🎯','🏆','🚀','🤖','💻','☁️','🛡️','🐉'] as $emoji): ?>
<button type="button" class="emoji-item" data-emoji="<?=e($emoji)?>"><?=e($emoji)?></button>
<?php endforeach; ?>
</div>
</div>
</div>
<button class="btn btn-info chat-send-btn">Enviar</button>
</form>
</div>

</div>

</div>


<div class="modal fade" id="playCardModal" tabindex="-1">

<div class="modal-dialog modal-xl">

<div class="modal-content bg-dark text-light border-secondary">

<div class="modal-header">

<h5 class="modal-title">
Registrar carta física
</h5>

<button
class="btn-close btn-close-white"
data-bs-dismiss="modal">
</button>

</div>


<form id="playCardForm">

<div class="modal-body">

<input type="hidden" name="action" value="play_card">

<input type="hidden" name="game_id" value="<?=$gameId?>">

<input type="hidden" name="team_id" id="playTeamId">

<input type="hidden" name="card_id" id="selectedCardId">

<input type="hidden" name="csrf" value="<?=e($csrf)?>">


<label class="form-label fw-bold" for="cardSearch">
Buscar servicio o herramienta
</label>

<div class="input-group input-group-lg mb-3">

<span class="input-group-text bg-dark text-light border-secondary">
🔎
</span>

<input
class="form-control"
type="search"
id="cardSearch"
autocomplete="off"
placeholder="Ej.: Azure Spring Apps, Databricks, UiPath, MLflow, Firewall...">

</div>


<div class="row g-3 mb-3">

<div class="col-md-6">

<label class="form-label fw-bold">
Participante que responde
</label>

<?php if($roomPlayer): ?>
<div class="form-control d-flex align-items-center gap-2" style="min-height:38px;">
    <?=arqwar_avatar_html((string)($myParticipant['avatar_emoji']??'cyber_skull'),'game-player-avatar')?>
    <b><?=e($myParticipant['full_name']??$user['display_name'])?></b>
    <span class="text-secondary small">· Tu cuenta</span>
</div>
<input type="hidden" name="participant_id" id="responderSelect" value="<?=e((string)($myParticipant['id']??0))?>">
<?php else: ?>
<select
class="form-select"
name="participant_id"
id="responderSelect"
required>
<option value="">Selecciona participante...</option>
</select>
<?php endif; ?>

</div>


<div class="col-md-6">

<label class="form-label fw-bold">
Pronunciación
</label>

<div class="pronunciation-answer">

<button
class="btn btn-outline-info"
type="button"
id="speakCardBtn"
disabled>
🔊 Reproducir servicio
</button>


<div
class="pronunciation-question d-none"
id="pronunciationQuestion">

<span>
¿El participante pronunció bien?
</span>

<label>

<input
type="radio"
name="pronunciation_correct"
value="1"
required
disabled>

Sí (+5 XP)

</label>


<label>

<input
type="radio"
name="pronunciation_correct"
value="0"
required
disabled>

No

</label>

</div>

</div>

</div>

</div>


<div class="row g-3">

<div class="col-lg-7">

<div
id="cardPickerList"
class="card-picker-list"
role="listbox"
aria-label="Cartas disponibles">
</div>

</div>


<div class="col-lg-5">

<div
id="selectedCardPreview"
class="selected-card-preview">

<div class="empty-zone">
Busca y selecciona una carta. Aquí verás su costo, atributos, ciclo de vida y descripción antes de desplegarla.
</div>

</div>

</div>

</div>

</div>


<div class="modal-footer">

<button
type="button"
class="btn btn-outline-light"
data-bs-dismiss="modal">
Cancelar
</button>

<button
class="btn btn-warning"
id="deployCardBtn"
disabled>
Desplegar carta y evaluar
</button>

</div>

</form>

</div>
</div>
</div>


<div class="modal fade" id="feedbackModal" tabindex="-1">

<div class="modal-dialog modal-lg modal-dialog-centered">

<div class="modal-content bg-dark text-light border-secondary">

<div class="modal-header">

<h5 class="modal-title">
Feedback de arquitectura
</h5>

<button
class="btn-close btn-close-white"
data-bs-dismiss="modal">
</button>

</div>

<div
class="modal-body"
id="feedbackBody">
</div>

<div class="modal-footer">

<button
type="button"
class="btn btn-warning"
data-bs-dismiss="modal">
Continuar partida
</button>

</div>

</div>
</div>
</div>


<div class="modal fade" id="diceModal" tabindex="-1">

<div class="modal-dialog modal-md modal-dialog-centered">

<div class="modal-content bg-dark text-light border-secondary">

<div class="modal-header">

<h5 class="modal-title">
Lanzamiento de dado
</h5>

<button
class="btn-close btn-close-white"
data-bs-dismiss="modal">
</button>

</div>

<div
class="modal-body text-center"
id="diceBody">
</div>

<div class="modal-footer">

<button
type="button"
class="btn btn-warning w-100"
data-bs-dismiss="modal">
Aceptar resultado
</button>

</div>

</div>
</div>
</div>


<div
class="modal fade"
id="winnerModal"
tabindex="-1"
data-bs-backdrop="static"
data-bs-keyboard="false">

<div class="modal-dialog modal-lg modal-dialog-centered">

<div class="modal-content winner-modal-content">

<div
class="modal-body text-center p-4 p-lg-5"
id="winnerBody">
</div>

<div class="modal-footer justify-content-center">

<button
type="button"
class="btn btn-warning btn-lg px-5"
id="winnerAcceptBtn">
Aceptar y volver al inicio
</button>

</div>

</div>
</div>
</div>


<?php endif; ?>

</main>


<script>
window.ARQWAR={
    gameId:<?=$gameId?>,
    csrf:'<?=e($csrf)?>',
    roomMode:<?= $roomMode?'true':'false' ?>,
    roomRole:'<?=e($roomRole)?>',
    roomId:<?=$roomId?>,
    myTeamId:<?=$roomTeamId?>,
    individualMode:<?= $individualMode?'true':'false' ?>,
    isGameCreator:<?= $isGameCreator?'true':'false' ?>,
    myParticipantId:<?= (int)($myParticipant['id']??0) ?>,
    myDisplayName:'<?=e($myParticipant['full_name']??$user['display_name'])?>',
    myAvatar:'<?=e($myParticipant['avatar_emoji']??'👤')?>',
    avatarNames:<?=json_encode(array_map(static fn($m) => $m['name'],arqwar_avatar_catalog()),JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>,
    cards:<?=json_encode(
        $cardsClient,
        JSON_UNESCAPED_UNICODE|
        JSON_UNESCAPED_SLASHES|
        JSON_HEX_TAG|
        JSON_HEX_AMP|
        JSON_HEX_APOS|
        JSON_HEX_QUOT
    )?>
};
</script>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/gm.js?v=20261028-3d"></script>
<?php if(!$game): ?><script src="assets/js/arqwar-launcher.js?v=20261027-v1"></script><?php endif; ?>

<?php if($roomMode): ?>
<script>
setInterval(async()=>{
  const fd=new FormData(); fd.set('action','heartbeat'); fd.set('room_id','<?=$roomId?>');
  const r=await fetch('api/room.php',{method:'POST',body:fd}).catch(()=>null);
  if(r&&!r.ok) location.href='rooms.php?leave=1';
},30000);
</script>
<?php endif; ?>

<?php if(!$game): ?>

<script>

(()=>{

const area=document.getElementById('scenarioAreaSelect');
const sel=document.getElementById('scenarioSelect');
const preview=document.getElementById('scenarioPreview');

const all=[
    ...sel.querySelectorAll('option[data-area]')
].map(o=>({
    value:o.value,
    area:o.dataset.area,
    text:o.textContent,
    client:o.dataset.client,
    description:o.dataset.description,
    budget:o.dataset.budget,
    load:o.dataset.load,
    sla:o.dataset.sla
}));


function render(){

    const a=area.value;

    sel.innerHTML='<option value="">Seleccionar casuística...</option>';

    all
    .filter(x=>x.area===a)
    .forEach(x=>{

        const o=document.createElement('option');

        o.value=x.value;
        o.textContent=x.text;
        o.dataset.client=x.client;
        o.dataset.description=x.description;
        o.dataset.budget=x.budget;
        o.dataset.load=x.load;
        o.dataset.sla=x.sla;

        sel.appendChild(o);

    });

    sel.disabled=!a;

    preview.innerHTML=
        '<div class="empty-zone">'+
        (
            a
            ? 'Selecciona una casuística de '+a+'.'
            : 'Selecciona un área y una casuística para ver el reto.'
        )+
        '</div>';
}


area.addEventListener('change',render);


sel.addEventListener('change',()=>{

    const o=sel.selectedOptions[0];

    if(!o?.value)return;

    preview.innerHTML=`

        <div>

            <div class="eyebrow">
                ${area.value} · ${o.dataset.client}
            </div>

            <h3>${o.textContent}</h3>

            <p>
                ${o.dataset.description}
            </p>

        </div>

        <div class="scenario-preview-kpis">

            <span>
                Budget
                <b>$${Number(o.dataset.budget).toLocaleString()}</b>
            </span>

            <span>
                SLA
                <b>${o.dataset.sla}%</b>
            </span>

            <span>
                Carga
                <b>${o.dataset.load}</b>
            </span>

        </div>

    `;

});


const checks=[
    ...document.querySelectorAll(
        'input[name="team_profile_ids[]"]'
    )
];

const counter=document.getElementById('selectedTeamsCount');
const mode=document.getElementById('gameModeSelect');
const teamOptions=document.getElementById('teamModeOptions');
const modeHint=document.getElementById('gameModeHint');

function syncGameMode(){
    const individual=mode?.value==='individual';
    if(teamOptions) teamOptions.style.display=individual?'none':'';
    checks.forEach(c=>{ c.checked=individual?false:c.checked; c.disabled=individual; });
    if(counter) counter.textContent=individual?'Los jugadores se incorporan automáticamente a la sala':'0 equipos seleccionados';
    if(modeHint){
        modeHint.innerHTML=individual
            ? '<b>Individual:</b> no selecciones equipos. Cada usuario que entre a la sala obtiene automáticamente su propia casilla, dado, cartas, nombre y color. Solo puede modificar su propio jugador.'
            : '<b>Por equipos:</b> selecciona entre 2 y 8 equipos. Cada equipo tendrá su propio panel, dado y cartas.';
    }
    const diceText=document.getElementById('diceRuleText');
    if(diceText) diceText.textContent=individual
        ? 'el número de suerte ya no es fijo. Cada jugador elige un número del 1 al 6 justo antes de su lanzamiento en cada ronda.'
        : 'el número de suerte ya no es fijo. Cada equipo elige un número del 1 al 6 justo antes de su lanzamiento en cada ronda.';
}

checks.forEach(c=>c.addEventListener('change',()=>{
    const n=checks.filter(x=>x.checked).length;
    if(counter) counter.textContent=n+' equipo'+(n===1?'':'s')+' seleccionado'+(n===1?'':'s');
    if(n>8){ c.checked=false; if(counter) counter.textContent='Máximo 8 equipos por partida'; }
}));
mode?.addEventListener('change',syncGameMode);
syncGameMode();

document.getElementById('createGameForm')?.addEventListener('submit',e=>{
    const n=checks.filter(x=>x.checked).length;
    if(mode?.value==='individual') return;
    if(n<2){
        e.preventDefault();
        alert('Selecciona al menos 2 equipos para una batalla por equipos.');
    }
});

})();

</script>

<?php endif; ?>

<?php if(!$game): ?>
<script>
(()=>{
 const p=document.getElementById('roomPrivateSelect'),w=document.getElementById('roomPasswordInput');
 if(!p||!w)return;
 const sync=()=>{const on=p.value==='1';w.disabled=!on;w.required=on;if(!on)w.value='';};
 p.addEventListener('change',sync);sync();
})();
</script>
<?php endif; ?>

<div
id="introPartida"
style="
display:none;
position:fixed;
inset:0;
z-index:999999;
background:#000;
">

<video
id="videoIntro"
style="
width:100vw;
height:100vh;
object-fit:cover;
"
playsinline
muted
preload="auto">

<source
src="assets/video/intro-partida.webm"
type="video/webm">

</video>

</div>


<script>
// La reproducción de inicio se sincroniza desde assets/js/gm.js con el estado de la partida.
<?php if($roomMode): ?>
document.getElementById('deleteRoomBtn')?.addEventListener('click',async()=>{
    if(!confirm('¿Eliminar esta sala? La partida se conservará, pero la sala dejará de estar disponible.')) return;
    const fd=new FormData(); fd.set('action','delete'); fd.set('room_id','<?=$roomId?>'); fd.set('csrf','<?=e($csrf)?>');
    const r=await fetch('api/room.php',{method:'POST',body:fd}); const j=await r.json().catch(()=>({ok:false,error:'Respuesta inválida'}));
    if(!j.ok){alert(j.error||'No se pudo eliminar la sala.');return;}
    location.href='rooms.php?leave=1';
});
<?php endif; ?>

</script>
<?php if($game): ?>
<?= arqwar_mascot_html('game') ?>
<script src="assets/js/arqwar-mascot.js?v=20261029-1" defer></script>
<?php endif; ?>
</body>
</html>