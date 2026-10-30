<?php
require_once __DIR__.'/includes/bootstrap.php';
admin_required_html();
ensure_password_recovery_schema();
$u=current_user();
$msg=''; $ok=false;
if($_SERVER['REQUEST_METHOD']==='POST'){
    $to=normalize_email((string)($_POST['email']??''));
    if(!filter_var($to,FILTER_VALIDATE_EMAIL)) $msg='Escribe un correo valido.';
    else{
        $code=(string)random_int(100000,999999);
        $ok=send_arqwar_recovery_email($to,(string)$u['display_name'],$code);
        $msg=$ok ? 'Correo de prueba enviado correctamente a '.$to.'. Revisa Recibidos y Spam.' : 'No se pudo enviar. Revisa config/config.php: host smtp.gmail.com, puerto 587, TLS, correo Gmail y contraseña de aplicacion.';
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ARQWAR · Prueba SMTP</title><link href="assets/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="assets/css/app.css"></head><body class="gm-body auth-body"><main class="container auth-shell py-4 py-md-5"><section class="auth-card panel"><div class="auth-brand"><span class="auth-brand-devioz">DEVIOZ</span><span class="auth-brand-arqwar">ARQWAR</span></div><div class="auth-security-line"><span class="auth-pulse"></span><span>DIAGNOSTICO · SMTP GMAIL</span></div><div class="eyebrow">// CANAL DE CORREO</div><h1>Probar envio de correo</h1><p class="auth-subtitle">Usa esta pantalla para comprobar la configuracion SMTP antes de probar la recuperacion de contraseña.</p><?php if($msg):?><div class="alert <?= $ok?'alert-success':'alert-danger' ?> auth-alert"><?=e($msg)?></div><?php endif;?><form method="post" class="auth-form"><div class="auth-field"><label for="email">Correo de destino</label><input id="email" class="form-control form-control-lg" type="email" name="email" required placeholder="tu-correo@gmail.com" value="<?=e($_POST['email']??($u['email']??''))?>"></div><button class="btn btn-primary btn-lg auth-primary auth-blue" type="submit">✉&nbsp; Enviar correo de prueba</button></form><div class="auth-register-box"><strong>Configuracion actual</strong><span>Servidor: smtp.gmail.com · Puerto: 587 · Seguridad: STARTTLS</span><span>Autenticacion: contraseña de aplicacion de Google</span></div><div class="auth-link-center"><a href="login.php">← Volver al inicio de sesion</a></div><div class="auth-footer"><span>Solo administrador</span><span>© DEVIOZ S.A.C. · ARQWAR</span></div></section></main></body></html>
