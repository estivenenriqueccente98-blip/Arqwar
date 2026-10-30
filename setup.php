<?php
$config = require __DIR__ . '/config/config.php';
date_default_timezone_set($config['timezone']);
session_start();
$installed=false;
try {
    $c0=$config['db'];
    $probe=@new mysqli($c0['host'],$c0['user'],$c0['pass'],$c0['name'],(int)$c0['port']);
    if(!$probe->connect_errno){ $r=@$probe->query("SHOW TABLES LIKE 'app_users'"); if($r && $r->num_rows>0){$n=@$probe->query("SELECT COUNT(*) n FROM app_users");$installed=(bool)($n && (int)($n->fetch_assoc()['n']??0)>0);} $probe->close(); }
} catch(Throwable $e){}
if($installed && (($_SESSION['user']['role']??'operator')!=='admin')){ http_response_code(403); echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><link href="assets/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="assets/css/app.css"></head><body class="gm-body"><main class="container py-5"><div class="panel p-4"><h1 class="h3">Instalador restringido</h1><p>ARQWAR ya está instalado. Solo un administrador puede reinstalar la base.</p><a class="btn btn-warning" href="login.php">Ir al login</a></div></main></body></html>'; exit; }
$msg=''; $ok=false;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $c=$config['db'];
    try {
        $mysqli = new mysqli($c['host'],$c['user'],$c['pass'],'',(int)$c['port']);
        if ($mysqli->connect_errno) throw new RuntimeException($mysqli->connect_error);
        $mysqli->set_charset($c['charset']);
        $sql=file_get_contents(__DIR__.'/sql/arqwar.sql');
        if (!$mysqli->multi_query($sql)) throw new RuntimeException($mysqli->error);
        do {
            if ($res=$mysqli->store_result()) $res->free();
            if (!$mysqli->more_results()) break;
        } while ($mysqli->next_result());
        if ($mysqli->errno) throw new RuntimeException($mysqli->error);
        $roomsSql=__DIR__.'/sql/rooms.sql';
        if(is_file($roomsSql) && !$mysqli->multi_query(file_get_contents($roomsSql))) throw new RuntimeException($mysqli->error);
        if(is_file($roomsSql)){do{if($res=$mysqli->store_result())$res->free();if(!$mysqli->more_results())break;}while($mysqli->next_result());if($mysqli->errno)throw new RuntimeException($mysqli->error);}
        $chatSql=__DIR__.'/sql/chat.sql';
        if(is_file($chatSql) && !$mysqli->multi_query(file_get_contents($chatSql))) throw new RuntimeException($mysqli->error);
        if(is_file($chatSql)){do{if($res=$mysqli->store_result())$res->free();if(!$mysqli->more_results())break;}while($mysqli->next_result());if($mysqli->errno)throw new RuntimeException($mysqli->error);}
        $ok=true; $msg='Base de datos ARQWAR v1.6 instalada correctamente.';
    } catch (Throwable $e) { $msg='Error: '.$e->getMessage(); }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Instalar ARQWAR</title><link href="assets/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="assets/css/app.css"></head>
<body class="bg-dark text-light"><main class="container py-5" style="max-width:760px"><div class="brand mb-4">ARQWAR <span>INSTALLER</span></div>
<div class="panel p-4"><h1 class="h3">Instalación local</h1><p>Este instalador recreará la base <code>arqwar</code> y cargará cartas, combos, incidentes, escenarios, login, participantes persistentes, equipos, scoring contextual, dado con número de suerte por lanzamiento, pronunciación, rankings, incidentes didácticos y un catálogo de 647 herramientas y servicios, roles General/Administrador y consola CRUD de ARQWAR v1.6. <b>Si ya tienes una versión anterior y quieres conservar tus partidas, usa <a href="upgrade.php">upgrade.php</a>.</b></p>
<?php if($msg): ?><div class="alert <?= $ok?'alert-success':'alert-danger' ?>"><?=htmlspecialchars($msg)?></div><?php endif; ?>
<dl class="row small"><dt class="col-sm-4">MySQL</dt><dd class="col-sm-8"><?=htmlspecialchars($config['db']['host'])?>:<?=$config['db']['port']?></dd><dt class="col-sm-4">Usuario</dt><dd class="col-sm-8"><?=htmlspecialchars($config['db']['user'])?></dd><dt class="col-sm-4">Base</dt><dd class="col-sm-8"><?=htmlspecialchars($config['db']['name'])?></dd></dl>
<form method="post"><button class="btn btn-warning btn-lg">Instalar / reinstalar ARQWAR</button> <a class="btn btn-outline-light btn-lg" href="index.php">Volver</a></form><?php if($ok): ?><div class="login-hint mt-4"><b>Accesos iniciales</b><span>General: <code>general</code> / <code>Arqwar2026!</code></span><span>Admin: <code>admin</code> / <code>AdminArqwar2026!</code></span><small>El administrador puede editar y eliminar datos desde Administración CRUD.</small></div><a class="btn btn-success mt-3" href="login.php">Ir al login</a><?php endif; ?>
<p class="text-secondary small mt-4 mb-0">XAMPP suele usar <code>root</code> sin contraseña. Si tu MySQL usa otras credenciales, edita <code>config/config.php</code>.</p></div></main></body></html>
