<?php
require_once __DIR__.'/includes/bootstrap.php';
if(current_user()){ header('Location: index.php'); exit; }
ensure_password_recovery_schema();
$recovery=$_SESSION['password_recovery']??null;
if(!$recovery || empty($recovery['user_id']) || (int)($recovery['expires_at']??0)<time()){ clear_password_recovery(); header('Location: forgot-password.php'); exit; }
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $code=preg_replace('/\D+/','',(string)($_POST['code']??''));
    if(strlen($code)!==6) $error='Ingresa el código de 6 dígitos recibido por correo.';
    else{
        $user=row('SELECT id,password_reset_code_hash,password_reset_expires_at FROM app_users WHERE id=? AND is_active=1 LIMIT 1',[(int)$recovery['user_id']]);
        $valid=$user && !empty($user['password_reset_code_hash']) && !empty($user['password_reset_expires_at']) && strtotime((string)$user['password_reset_expires_at'])>=time() && hash_equals((string)$user['password_reset_code_hash'],hash('sha256',$code));
        if(!$valid) $error='El código es incorrecto o ya expiró. Solicita uno nuevo.';
        else{ $_SESSION['password_recovery']['verified']=true; $_SESSION['password_recovery']['verified_at']=time(); header('Location: reset-password.php'); exit; }
    }
}
$masked=preg_replace('/(^.).*(@.*$)/','$1********$2',(string)$recovery['email']);
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>DEVIOZ ARQWAR · Verificar código</title><link href="assets/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="assets/css/app.css"></head><body class="gm-body auth-body"><main class="container auth-shell py-4 py-md-5"><section class="auth-card panel"><div class="auth-brand"><span class="auth-brand-devioz">DEVIOZ</span><span class="auth-brand-arqwar">ARQWAR</span></div><div class="auth-security-line"><span class="auth-pulse"></span><span>CANAL SEGURO · VERIFICACIÓN DE IDENTIDAD</span></div><div class="eyebrow">// VERIFICACIÓN</div><h1>Recuperar contraseña</h1><p class="auth-subtitle">Ingresa el código de 6 dígitos enviado a <strong><?=e($masked)?></strong>.</p><?php if($error):?><div class="alert alert-danger auth-alert"><?=e($error)?></div><?php endif;?><form method="post" class="auth-form" id="codeForm"><div class="auth-field"><label for="code">Código de verificación</label><input id="code" class="form-control form-control-lg auth-code-input" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" name="code" autocomplete="one-time-code" autofocus required placeholder="000000"></div><button class="btn btn-primary btn-lg auth-primary auth-blue">✓&nbsp; Confirmar código</button></form><div class="auth-link-center"><a href="forgot-password.php">← Solicitar otro código</a></div><div class="auth-footer"><span>© DEVIOZ S.A.C. · ARQWAR</span></div></section></main></body></html>
