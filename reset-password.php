<?php
require_once __DIR__.'/includes/bootstrap.php';
if(current_user()){ header('Location: index.php'); exit; }
ensure_password_recovery_schema();
$recovery=$_SESSION['password_recovery']??null;
if(!$recovery || empty($recovery['user_id']) || empty($recovery['verified']) || (int)($recovery['expires_at']??0)<time()){ clear_password_recovery(); header('Location: forgot-password.php'); exit; }
$error=''; $done=false;
if($_SERVER['REQUEST_METHOD']==='POST'){
    $new=(string)($_POST['password']??''); $confirm=(string)($_POST['confirm_password']??'');
    if(strlen($new)<10) $error='La nueva contraseña debe tener al menos 10 caracteres.';
    elseif($new!==$confirm) $error='Las contraseñas no coinciden.';
    else{
        $user=row('SELECT id FROM app_users WHERE id=? AND is_active=1 LIMIT 1',[(int)$recovery['user_id']]);
        if(!$user) $error='La cuenta ya no está disponible.';
        else{
            exec_sql('UPDATE app_users SET password_hash=?,password_reset_code_hash=NULL,password_reset_expires_at=NULL WHERE id=?',[password_hash($new,PASSWORD_DEFAULT),(int)$user['id']]);
            clear_password_recovery(); $done=true;
        }
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>DEVIOZ ARQWAR · Nueva contraseña</title><link href="assets/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="assets/css/app.css"></head><body class="gm-body auth-body"><main class="container auth-shell py-4 py-md-5"><section class="auth-card panel"><div class="auth-brand"><span class="auth-brand-devioz">DEVIOZ</span><span class="auth-brand-arqwar">ARQWAR</span></div><div class="auth-security-line"><span class="auth-pulse"></span><span>CANAL SEGURO · ACTUALIZACIÓN DE CREDENCIALES</span></div><div class="eyebrow">// SEGURIDAD DE CUENTA</div><?php if($done):?><h1>Recuperar contraseña</h1><p class="auth-subtitle">Tu contraseña fue actualizada correctamente.</p><div class="auth-success-big">✓ <span>Contraseña actualizada</span></div><a class="btn btn-primary btn-lg auth-primary auth-blue" href="login.php">↪&nbsp; Ir al inicio de sesión</a><?php else:?><h1>Nueva contraseña</h1><p class="auth-subtitle">Código verificado. Ahora crea una nueva contraseña para tu cuenta.</p><?php if($error):?><div class="alert alert-danger auth-alert"><?=e($error)?></div><?php endif;?><form method="post" class="auth-form"><div class="auth-field"><label>Nueva contraseña</label><input class="form-control form-control-lg" type="password" name="password" minlength="10" autocomplete="new-password" required placeholder="Mínimo 10 caracteres"></div><div class="auth-field"><label>Confirmar contraseña</label><input class="form-control form-control-lg" type="password" name="confirm_password" minlength="10" autocomplete="new-password" required placeholder="Repite la nueva contraseña"></div><button class="btn btn-primary btn-lg auth-primary auth-blue">🔒&nbsp; Actualizar contraseña</button></form><?php endif;?><div class="auth-link-center"><a href="login.php">← Volver al inicio de sesión</a></div><div class="auth-footer"><span>© DEVIOZ S.A.C. · ARQWAR</span></div></section></main></body></html>
