<?php
require_once __DIR__.'/includes/bootstrap.php';
require_once __DIR__.'/includes/arqwar-identity.php';
login_required_html();
ensure_password_recovery_schema();
$user=current_user(); $msg=''; $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!hash_equals(csrf_token(), $_POST['csrf']??'')){ $error='Solicitud inválida.'; }
    else{
        $email=normalize_email((string)($_POST['email']??''));
        $current=(string)($_POST['current_password']??'');
        $new=(string)($_POST['new_password']??'');
        $confirm=(string)($_POST['confirm_password']??'');
        $dbUser=row('SELECT * FROM app_users WHERE id=?',[(int)$user['id']]);
        if(!$dbUser || !filter_var($email,FILTER_VALIDATE_EMAIL)) $error='Escribe un correo electrónico válido.';
        elseif($email!==normalize_email((string)($dbUser['email']??'')) && row('SELECT id FROM app_users WHERE email=? AND id<>? LIMIT 1',[$email,(int)$user['id']])) $error='Ese correo ya está asociado a otra cuenta.';
        if(!$error && !password_verify($current,$dbUser['password_hash'])) $error='La contraseña actual no es correcta.';
        elseif(!$error && strlen($new)<10) $error='La nueva contraseña debe tener al menos 10 caracteres.';
        elseif(!$error && $new!==$confirm) $error='La confirmación no coincide.';
        elseif(!$error){ exec_sql('UPDATE app_users SET email=?,password_hash=? WHERE id=?',[$email,password_hash($new,PASSWORD_DEFAULT),(int)$user['id']]); $_SESSION['user']['email']=$email; $user=current_user(); $msg='Correo y contraseña actualizados correctamente.'; }
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ARQWAR · Mi cuenta</title><link href="assets/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="assets/css/app.css"><link rel="stylesheet" href="assets/css/arqwar-identity.css?v=20261028-2">
</head><body class="gm-body">
<nav class="navbar navbar-dark border-bottom border-secondary-subtle"><div class="container-fluid"><?= arqwar_brand_html('MI CUENTA','compact','game-master.php') ?><div class="d-flex gap-2"><a class="btn btn-sm btn-outline-light" href="game-master.php">Game Master</a><?php if(is_admin()):?><a class="btn btn-sm btn-danger" href="admin.php">Admin CRUD</a><?php endif;?><a class="btn btn-sm btn-outline-secondary" href="logout.php">Salir</a></div></div></nav>
<main class="container py-5" style="max-width:760px"><div class="panel p-4"><div class="eyebrow">USUARIO ACTIVO</div><h1 class="h3"><?=e($user['display_name'])?></h1><p class="text-secondary">@<?=e($user['username'])?> · <span class="badge <?=($user['role']??'operator')==='admin'?'text-bg-danger':'text-bg-info'?>"><?=($user['role']??'operator')==='admin'?'Administrador':'General / operador'?></span></p><?php if($msg): ?><div class="alert alert-success"><?=e($msg)?></div><?php endif; ?><?php if($error): ?><div class="alert alert-danger"><?=e($error)?></div><?php endif; ?><hr><h2 class="h5">Datos de recuperación y contraseña</h2><form method="post" class="row g-3"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><div class="col-12"><label class="form-label">Correo electrónico</label><input class="form-control" type="email" name="email" value="<?=e($user['email']??'')?>" autocomplete="email" required><div class="form-text">Este correo se utilizará para enviarte códigos de recuperación.</div></div><div class="col-12"><label class="form-label">Contraseña actual</label><input class="form-control" type="password" name="current_password" required></div><div class="col-md-6"><label class="form-label">Nueva contraseña</label><input class="form-control" type="password" name="new_password" minlength="10" required></div><div class="col-md-6"><label class="form-label">Confirmar</label><input class="form-control" type="password" name="confirm_password" minlength="10" required></div><div class="col-12"><button class="btn btn-warning">Guardar nueva contraseña</button></div></form></div></main></body></html>
