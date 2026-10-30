<?php
require_once __DIR__.'/includes/bootstrap.php';
require_once __DIR__.'/includes/arqwar-identity.php';
$gameId=(int)($_GET['game_id']??0); if(!$gameId){try{$g=latest_game();$gameId=(int)($g['id']??0);}catch(Throwable $e){}}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ARQWAR · Arena</title><link href="assets/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="assets/css/app.css"><link rel="stylesheet" href="assets/css/arqwar-identity.css?v=20261028-2">
</head>
<body class="arena-body"><div id="arena" class="arena-shell"><header class="arena-header"><div><?= arqwar_brand_html('ARENA TV','arena') ?><div id="arenaScenario" class="text-secondary">Cargando misión...</div></div><div class="arena-round"><span class="eyebrow">RONDA</span><strong id="arenaRound">--/--</strong></div><div class="text-end"><button class="btn btn-sm btn-outline-light" onclick="document.documentElement.requestFullscreen?.()">Pantalla completa</button><div id="arenaClock" class="arena-clock">--:--</div></div></header><section id="missionBar" class="mission-bar"></section><section id="incidentBanner" class="incident-banner"></section><section id="diceBanner" class="arena-event-strip d-none"></section><section id="playFeedback" class="arena-feedback d-none"></section><main id="arenaTeams" class="arena-teams"></main><footer class="arena-footer"><div id="arenaLogs"></div></footer></div><script>window.ARQWAR={gameId:<?=$gameId?>};</script><script src="assets/js/arena.js"></script></body></html>
