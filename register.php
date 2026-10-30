<?php
require_once __DIR__.'/includes/bootstrap.php';
if(current_user()){header('Location: index.php');exit;}
$error='';$next=trim((string)($_GET['next']??'index.php'));
if(preg_match('/^https?:\/\//i',$next))$next='index.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
    ensure_account_participant_schema();
    ensure_password_recovery_schema();
    $name=trim((string)($_POST['display_name']??''));
    $username=trim((string)($_POST['username']??''));
    $email=normalize_email((string)($_POST['email']??''));
    $password=(string)($_POST['password']??'');
    $confirm=(string)($_POST['confirm_password']??'');
    $next=trim((string)($_POST['next']??$next));
    if(preg_match('/^https?:\/\//i',$next))$next='index.php';
    try{
        if(strlen($name)<2)throw new RuntimeException('Escribe tu nombre.');
        if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Escribe un correo electrónico válido.');
        if(!preg_match('/^[A-Za-z0-9_-]{3,80}$/',$username))throw new RuntimeException('El usuario debe tener 3 a 80 caracteres y usar solo letras, números, guion o guion bajo.');
        if(strlen($password)<10)throw new RuntimeException('La contraseña debe tener al menos 10 caracteres.');
        if($password!==$confirm)throw new RuntimeException('Las contraseñas no coinciden.');
        if(row('SELECT id FROM app_users WHERE username=? LIMIT 1',[$username]))throw new RuntimeException('Ese usuario ya existe.');
        if(row('SELECT id FROM app_users WHERE email=? LIMIT 1',[$email]))throw new RuntimeException('Ese correo ya está registrado.');
        $existingParticipant=row('SELECT id,user_id FROM participants WHERE alias=? LIMIT 1',[$username]);
        if($existingParticipant && $existingParticipant['user_id']!==null && $existingParticipant['user_id']!=='')throw new RuntimeException('Ese usuario ya está vinculado a otro participante. Elige otro.');
        db()->beginTransaction();
        exec_sql('INSERT INTO app_users(username,password_hash,display_name,email,role,is_active) VALUES(?,?,?,?,\'operator\',1)',[$username,password_hash($password,PASSWORD_DEFAULT),$name,$email]);
        $uid=(int)db()->lastInsertId();
        if($existingParticipant){
            exec_sql('UPDATE participants SET user_id=?,full_name=? WHERE id=?',[$uid,$name,(int)$existingParticipant['id']]);
        }else{
            exec_sql('INSERT INTO participants(user_id,alias,full_name,specialty) VALUES(?,?,?,?)',[$uid,$username,$name,'Usuario ARQWAR']);
        }
        db()->commit();
        attempt_login($username,$password);
        header('Location: '.$next);exit;
    }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();$error=$e->getMessage();}
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>DEVIOZ ARQWAR · Crear cuenta</title><link href="assets/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="assets/css/app.css"></head>
<body class="gm-body auth-body"><main class="container auth-shell py-4 py-md-5"><section class="auth-card panel">
<div class="auth-brand"><span class="auth-brand-devioz">DEVIOZ</span><span class="auth-brand-arqwar">ARQWAR</span></div><div class="eyebrow">// REGISTRO DE ACCESO</div><h1>Crear cuenta</h1><p class="auth-subtitle">Regístrate para entrar a ARQWAR. Tu cuenta quedará vinculada automáticamente a un participante ARQWAR.</p>
<?php if($error):?><div class="alert alert-danger auth-alert"><?=e($error)?></div><?php endif;?>
<form method="post" class="auth-form"><input type="hidden" name="next" value="<?=e($next)?>">
<div class="auth-field"><label>Nombre completo</label><input class="form-control form-control-lg" name="display_name" value="<?=e($_POST['display_name']??'')?>" autocomplete="name" required></div>
<div class="auth-field"><label>Usuario</label><input class="form-control form-control-lg" name="username" value="<?=e($_POST['username']??'')?>" autocomplete="username" required></div>
<div class="auth-field"><label>Correo electrónico</label><input class="form-control form-control-lg" type="email" name="email" value="<?=e($_POST['email']??'')?>" autocomplete="email" required placeholder="tu-correo@gmail.com"><small class="auth-help">Lo utilizaremos para recuperar tu contraseña.</small></div>
<div class="auth-field"><label>Contraseña</label><input class="form-control form-control-lg" type="password" name="password" minlength="10" autocomplete="new-password" required></div>
<div class="auth-field"><label>Confirmar contraseña</label><input class="form-control form-control-lg" type="password" name="confirm_password" minlength="10" autocomplete="new-password" required></div>
<button class="btn btn-warning btn-lg auth-primary">Crear mi cuenta</button></form>
<div class="auth-register-box"><strong>¿Ya tienes una cuenta?</strong><span>Inicia sesión con tu usuario y contraseña.</span><a href="login.php?next=<?=urlencode($next)?>">Ir a iniciar sesión</a></div>
<div class="auth-footer"><a href="index.php">← Volver al inicio</a><span>© DEVIOZ S.A.C. · ARQWAR</span></div>
</section></main></body></html>
