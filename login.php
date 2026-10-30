<?php
require_once __DIR__.'/includes/bootstrap.php';
ensure_password_recovery_schema();
if (current_user()) { header('Location: index.php'); exit; }
$error='';
$next=trim((string)($_GET['next']??'index.php'));
if(preg_match('/^https?:\/\//i',$next))$next='index.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $username=trim((string)($_POST['username']??''));
    $password=(string)($_POST['password']??'');
    $next=trim((string)($_POST['next']??$next));
    if(preg_match('/^https?:\/\//i',$next))$next='index.php';
    try{
        if(attempt_login($username,$password)){ current_participant(true); header('Location: '.$next); exit; }
        $error='Usuario o contraseña incorrectos.';
    }catch(Throwable $e){ $error='No se pudo validar el acceso. Verifica que MySQL esté iniciado y ARQWAR instalado.'; }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>DEVIOZ ARQWAR · Iniciar sesión</title>
<link href="assets/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="gm-body auth-body">
<main class="container auth-shell py-4 py-md-5">
  <section class="auth-card panel auth-login-card">
    <div class="auth-circuit"><span></span><span></span><span></span></div>
    <div class="auth-brand"><span class="auth-brand-devioz">DEVIOZ</span><span class="auth-brand-arqwar">ARQWAR</span></div>
    <div class="auth-security-line"><span class="auth-pulse"></span><span>SISTEMA DE ACCESO · CANAL ENCRIPTADO</span></div><div class="eyebrow">// CENTRO DE OPERACIONES</div>
    <h1>Iniciar sesión</h1>
    <p class="auth-subtitle">Accede al panel principal de ARQWAR. Desde allí podrás crear partidas, administrar equipos, consultar rankings y entrar a la Arena TV.</p>
    <?php if($error): ?><div class="alert alert-danger auth-alert"><?=e($error)?></div><?php endif; ?>
    <form method="post" class="auth-form">
      <input type="hidden" name="next" value="<?=e($next)?>">
      <div class="auth-field"><label for="username">Usuario</label><input id="username" class="form-control form-control-lg" name="username" value="<?=e($_POST['username']??'')?>" autocomplete="username" autofocus required placeholder="Ingresa tu usuario"></div>
      <div class="auth-field"><label for="password">Contraseña</label><input id="password" class="form-control form-control-lg" type="password" name="password" autocomplete="current-password" required placeholder="Ingresa tu contraseña"></div>
      <div class="auth-forgot"><a href="forgot-password.php">¿Olvidaste tu contraseña?</a><span>RECUPERACIÓN SEGURA</span></div>
      <button class="btn btn-warning btn-lg auth-primary" type="submit">↪&nbsp; Entrar a ARQWAR</button>
    </form>
    <div class="auth-status-strip"><span class="auth-status-dot"></span><span>ARQWAR ONLINE</span><span>•</span><span>ACCESO PROTEGIDO</span></div><div class="auth-register-box"><strong>¿Aún no tienes una cuenta?</strong><span>Regístrate para jugar y vincular tu perfil de participante.</span><a href="register.php?next=<?=urlencode($next)?>">Crear cuenta en ARQWAR</a></div>
    <div class="auth-footer"><a href="index.php">← Volver al inicio</a><span>© DEVIOZ S.A.C. · ARQWAR</span></div>
  </section>
</main>
</body></html>
