<?php
require_once __DIR__.'/includes/bootstrap.php';
login_required_html(); ensure_room_schema();
$gameId=(int)($_GET['game_id']??0); $room=$gameId?get_room($gameId):null; $error=''; $game=$gameId?get_game($gameId):null; $individualMode=$game && (($game['mode']??'teams')==='individual');
if(!$room){http_response_code(404);?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ARQWAR · Sala</title><link href="assets/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="assets/css/app.css?v=20261014-avatar"></head><body class="gm-body"><main class="container py-5" style="max-width:760px"><div class="panel p-4"><div class="eyebrow">SALA NO DISPONIBLE</div><h1 class="h3">La sala no existe o fue eliminada.</h1><a class="btn btn-warning" href="rooms.php">Volver a salas</a></div></main></body></html><?php exit;}
$u=current_user(); $roomId=(int)$room['id']; $isGM=is_admin() || (int)$room['created_by']===(int)$u['id'];
if($isGM){room_grant_access($roomId,'gm');$_SESSION['room_context']=['room_id'=>$roomId,'game_id'=>$gameId,'role'=>'gm'];}
elseif($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='enter'){
    try{
        verify_csrf();
        if($room['game_status']==='finished') throw new RuntimeException('Esta partida ya finalizó.');
        if(!$individualMode && !room_can_enter($gameId)) throw new RuntimeException('No perteneces a un equipo de esta partida.');
        if((int)$room['is_private']===1 && !password_verify((string)($_POST['password']??''),(string)$room['password_hash'])) throw new RuntimeException('Contraseña de sala incorrecta.');
        room_grant_access($roomId,'player');
        if($individualMode) ensure_individual_player($gameId,(int)$u['id']);
        $_SESSION['room_context']=['room_id'=>$roomId,'game_id'=>$gameId,'role'=>'player']; header('Location: room.php?game_id='.$gameId); exit;
    }catch(Throwable $e){$error=$e->getMessage();}
}
elseif(!room_session_open($roomId)){
    if($room['game_status']==='finished') $error='Esta partida ya finalizó.';
    elseif(!$individualMode && !room_can_enter($gameId)) $error='No perteneces a un equipo de esta partida.';
    elseif((int)$room['is_private']===0){
        room_grant_access($roomId,'player');
        if($individualMode) ensure_individual_player($gameId,(int)$u['id']);
        $_SESSION['room_context']=['room_id'=>$roomId,'game_id'=>$gameId,'role'=>'player'];
    }
}
$role=room_access_role($roomId);
if(!$isGM && $role!=='player'){
    ?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ARQWAR · <?=e($room['name'])?></title><link href="assets/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="assets/css/app.css?v=20261014-avatar"></head><body class="gm-body"><main class="container py-5" style="max-width:760px"><div class="panel p-4"><div class="eyebrow">SALA PROTEGIDA</div><h1 class="h3 mb-2"><?=e($room['name'])?></h1><p class="text-secondary"><?=e($error?:'Ingresa la contraseña para continuar.')?></p><?php if(!$error || str_contains($error,'contraseña')):?><form method="post" class="d-grid gap-3"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="enter"><label class="form-label">Contraseña de sala</label><input class="form-control form-control-lg" type="password" name="password" required autofocus><button class="btn btn-warning btn-lg">Ingresar a la sala</button></form><?php endif;?><a class="btn btn-outline-light mt-3" href="rooms.php">Volver a salas</a></div></main></body></html><?php exit;
}
if($room['game_status']==='finished' && !$isGM){?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ARQWAR · Sala finalizada</title><link href="assets/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="assets/css/app.css?v=20261014-avatar"></head><body class="gm-body"><main class="container py-5" style="max-width:760px"><div class="panel p-4"><div class="eyebrow">PARTIDA FINALIZADA</div><h1 class="h3"><?=e($room['name'])?></h1><p class="text-secondary">Esta sala ya no admite nuevas acciones.</p><a class="btn btn-warning" href="rooms.php">Volver a salas</a></div></main></body></html><?php exit;}
room_mark_player($roomId);
$roomMode=true; $roomRole=$isGM?'gm':'player'; $roomTeamId=$isGM?0:(room_player_team_id($gameId)??0);
require __DIR__.'/game-master.php';
