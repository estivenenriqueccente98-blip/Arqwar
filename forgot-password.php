<?php
require_once __DIR__.'/includes/bootstrap.php';
if(current_user()){ header('Location: index.php'); exit; }
ensure_password_recovery_schema();
$error=''; $msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $email=normalize_email((string)($_POST['email']??''));
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)) $error='Escribe un correo electrónico válido.';
    else{
        $user=row('SELECT id,display_name,email FROM app_users WHERE email=? AND is_active=1 LIMIT 1',[$email]);
        if($user){
            $code=(string)random_int(100000,999999);
            $hash=hash('sha256',$code);
            exec_sql('UPDATE app_users SET password_reset_code_hash=?,password_reset_expires_at=DATE_ADD(NOW(),INTERVAL 10 MINUTE) WHERE id=?',[$hash,(int)$user['id']]);
            if(send_arqwar_recovery_email($email,(string)$user['display_name'],$code)){
                $_SESSION['password_recovery']=['user_id'=>(int)$user['id'],'email'=>$email,'expires_at'=>time()+600,'verified'=>false];
                header('Location: verify-code.php'); exit;
            }
            exec_sql('UPDATE app_users SET password_reset_code_hash=NULL,password_reset_expires_at=NULL WHERE id=?',[(int)$user['id']]);
            $error='No se pudo enviar el código. Configura Gmail SMTP en config/config.php usando tu correo y una contraseña de aplicación de Google. Luego reinicia Apache y vuelve a intentarlo.';
        }else{
            // No revela si el correo existe para evitar enumeración de cuentas.
            $msg='Si el correo está registrado en DEVIOZ ARQWAR, recibirás un código de 6 dígitos en unos instantes.';
        }
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>DEVIOZ ARQWAR · Recuperar contraseña</title><link href="assets/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="assets/css/app.css"></head><body class="gm-body auth-body"><main class="container auth-shell py-4 py-md-5"><section class="auth-card panel"><div class="auth-brand"><span class="auth-brand-devioz">DEVIOZ</span><span class="auth-brand-arqwar">ARQWAR</span></div><div class="auth-security-line"><span class="auth-pulse"></span><span>CANAL SEGURO · RECUPERACIÓN DE ACCESO</span></div><div class="eyebrow">// SEGURIDAD DE CUENTA</div><h1>Recuperar contraseña</h1><p class="auth-subtitle">Ingresa el correo registrado en tu cuenta DEVIOZ ARQWAR y te enviaremos un código de verificación.</p><?php if($error):?><div class="alert alert-danger auth-alert"><?=e($error)?></div><?php endif;?><?php if($msg):?><div class="alert alert-success auth-alert"><?=e($msg)?></div><?php endif;?><form method="post" class="auth-form"><div class="auth-field"><label for="email">Correo electrónico</label><input id="email" class="form-control form-control-lg" type="email" name="email" value="<?=e($_POST['email']??'')?>" autocomplete="email" required placeholder="tu-correo@gmail.com"></div><button class="btn btn-primary btn-lg auth-primary auth-blue">➤&nbsp; Enviar código</button></form><div class="auth-trust"><span>🔐 Código de un solo uso</span><span>⏱ Válido 10 minutos</span><span>✉ Gmail / SMTP seguro</span></div><div class="auth-link-center"><a href="login.php">← Volver al inicio de sesión</a></div><div class="auth-footer"><span>© DEVIOZ S.A.C. · ARQWAR</span></div></section></main></body></html>
