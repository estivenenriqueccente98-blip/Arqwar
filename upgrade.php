<?php
$config = require __DIR__ . '/config/config.php';
date_default_timezone_set($config['timezone']);
session_start();
$upgradeUser=$_SESSION['user']??null;
$legacyUpgrade=false;
try {
    $cProbe=$config['db'];
    $probe=@new mysqli($cProbe['host'],$cProbe['user'],$cProbe['pass'],$cProbe['name'],(int)$cProbe['port']);
    if(!$probe->connect_errno){
        $t=@$probe->query("SHOW TABLES LIKE 'app_users'");
        if($t && $t->num_rows>0){ $r=@$probe->query("SHOW COLUMNS FROM app_users LIKE 'role'"); $legacyUpgrade=($r && $r->num_rows===0); }
        $probe->close();
    }
} catch(Throwable $e){}
if(!$legacyUpgrade && (!$upgradeUser || (($upgradeUser['role']??'operator')!=='admin' && ($upgradeUser['username']??'')!=='admin'))){ http_response_code(403); echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><link href="assets/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="assets/css/app.css"></head><body class="gm-body"><main class="container py-5"><div class="panel p-4"><h1 class="h3">Actualización restringida</h1><p>Inicia sesión con una cuenta administrativa antes de ejecutar el upgrade.</p><a class="btn btn-warning" href="login.php">Ir al login</a></div></main></body></html>'; exit; }
$msg=''; $ok=false;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $c=$config['db'];
    try {
        $mysqli = new mysqli($c['host'],$c['user'],$c['pass'],$c['name'],(int)$c['port']);
        if ($mysqli->connect_errno) throw new RuntimeException($mysqli->connect_error);
        $mysqli->set_charset($c['charset']);
        $addCol=function(string $table,string $col,string $ddl) use($mysqli){
            $r=$mysqli->query("SHOW COLUMNS FROM `$table` LIKE '".$mysqli->real_escape_string($col)."'");
            if(!$r || $r->num_rows===0){ if(!$mysqli->query("ALTER TABLE `$table` ADD COLUMN $ddl")) throw new RuntimeException($mysqli->error); }
        };
        $mysqli->query("CREATE TABLE IF NOT EXISTS app_users (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(80) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, display_name VARCHAR(120) NOT NULL, role ENUM('admin','operator') NOT NULL DEFAULT 'operator', is_active TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB");
        $mysqli->query("CREATE TABLE IF NOT EXISTS team_profiles (id INT AUTO_INCREMENT PRIMARY KEY, alias VARCHAR(100) NOT NULL UNIQUE, description VARCHAR(255) NULL, theme_key VARCHAR(30) NOT NULL DEFAULT 'blue', is_active TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB");
        $mysqli->query("CREATE TABLE IF NOT EXISTS participants (id INT AUTO_INCREMENT PRIMARY KEY, alias VARCHAR(80) NOT NULL UNIQUE, full_name VARCHAR(140) NULL, specialty VARCHAR(120) NULL, avatar_emoji VARCHAR(16) NOT NULL DEFAULT '👤', is_active TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB");
        $mysqli->query("CREATE TABLE IF NOT EXISTS team_members (id INT AUTO_INCREMENT PRIMARY KEY, team_profile_id INT NOT NULL, participant_id INT NULL, name VARCHAR(120) NOT NULL, role VARCHAR(100) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB");
        $mysqli->query("CREATE TABLE IF NOT EXISTS dice_rolls (id BIGINT AUTO_INCREMENT PRIMARY KEY, game_id INT NOT NULL, team_id INT NOT NULL, round_no INT NOT NULL, lucky_number TINYINT NOT NULL DEFAULT 6, result TINYINT NOT NULL, won_extra_card TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_dice_team_round (game_id, team_id, round_no)) ENGINE=InnoDB");
        $mysqli->query("CREATE TABLE IF NOT EXISTS participant_points (id BIGINT AUTO_INCREMENT PRIMARY KEY, participant_id INT NOT NULL, game_id INT NULL, team_id INT NULL, placement_id INT NULL, source_type VARCHAR(40) NOT NULL, points INT NOT NULL, description VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(participant_id), INDEX(game_id), INDEX(team_id), INDEX(placement_id)) ENGINE=InnoDB");
        $mysqli->query("CREATE TABLE IF NOT EXISTS game_rooms (id INT AUTO_INCREMENT PRIMARY KEY, game_id INT NOT NULL UNIQUE, name VARCHAR(120) NOT NULL, password_hash VARCHAR(255) NULL, is_private TINYINT(1) NOT NULL DEFAULT 0, is_active TINYINT(1) NOT NULL DEFAULT 1, created_by INT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE, FOREIGN KEY (created_by) REFERENCES app_users(id) ON DELETE SET NULL) ENGINE=InnoDB");
        $mysqli->query("CREATE TABLE IF NOT EXISTS room_players (id BIGINT AUTO_INCREMENT PRIMARY KEY, room_id INT NOT NULL, app_user_id INT NOT NULL, participant_id INT NULL, joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, last_seen DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_room_user (room_id,app_user_id), INDEX idx_room_seen (room_id,last_seen), FOREIGN KEY (room_id) REFERENCES game_rooms(id) ON DELETE CASCADE, FOREIGN KEY (app_user_id) REFERENCES app_users(id) ON DELETE CASCADE, FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE SET NULL) ENGINE=InnoDB");
        $addCol('app_users','email',"email VARCHAR(190) NULL AFTER display_name");
        $addCol('app_users','password_reset_code_hash',"password_reset_code_hash VARCHAR(64) NULL AFTER email");
        $addCol('app_users','password_reset_expires_at',"password_reset_expires_at DATETIME NULL AFTER password_reset_code_hash");
        $addCol('app_users','role',"role ENUM('admin','operator') NOT NULL DEFAULT 'operator' AFTER display_name");
        $addCol('participants','user_id','user_id INT NULL AFTER id');
        $mysqli->query("UPDATE participants p JOIN app_users u ON u.username=p.alias SET p.user_id=u.id WHERE p.user_id IS NULL");
        $participantUserIdx=$mysqli->query("SHOW INDEX FROM participants WHERE Key_name='uq_participant_user'");
        if($participantUserIdx && $participantUserIdx->num_rows===0){ @ $mysqli->query("ALTER TABLE participants ADD UNIQUE KEY uq_participant_user (user_id)"); }
        $participantUserFk=$mysqli->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='participants' AND CONSTRAINT_NAME='fk_participant_user'");
        if($participantUserFk && $participantUserFk->num_rows===0){ @ $mysqli->query("ALTER TABLE participants ADD CONSTRAINT fk_participant_user FOREIGN KEY (user_id) REFERENCES app_users(id) ON DELETE SET NULL"); }
        $usersNoParticipant=$mysqli->query("SELECT u.id,u.username,u.display_name FROM app_users u LEFT JOIN participants p ON p.user_id=u.id WHERE u.is_active=1 AND p.id IS NULL ORDER BY u.id");
        if($usersNoParticipant){
            while($usr=$usersNoParticipant->fetch_assoc()){
                $uid=(int)$usr['id']; $alias=$usr['username']; $display=$usr['display_name'];
                $st=$mysqli->prepare("SELECT id,user_id FROM participants WHERE alias=? LIMIT 1"); $st->bind_param('s',$alias); $st->execute(); $existing=$st->get_result()->fetch_assoc(); $st->close();
                if($existing){
                    if($existing['user_id']===null){ $st=$mysqli->prepare("UPDATE participants SET user_id=?,full_name=? WHERE id=?"); $st->bind_param('isi',$uid,$display,$existing['id']); $st->execute(); $st->close(); }
                }else{
                    $roleLabel='Usuario ARQWAR'; $st=$mysqli->prepare("INSERT INTO participants(user_id,alias,full_name,specialty) VALUES(?,?,?,?)"); $st->bind_param('isss',$uid,$alias,$display,$roleLabel); $st->execute(); $st->close();
                }
            }
        }
        $addCol('team_members','participant_id','participant_id INT NULL AFTER team_profile_id');
        $idxIndividual=$mysqli->query("SHOW INDEX FROM teams WHERE Key_name='uq_game_owner_user'"); if($idxIndividual && $idxIndividual->num_rows===0){ @$mysqli->query("ALTER TABLE teams ADD UNIQUE KEY uq_game_owner_user (game_id,owner_user_id)"); }
        $idxIndividual2=$mysqli->query("SHOW INDEX FROM teams WHERE Key_name='idx_game_participant'"); if($idxIndividual2 && $idxIndividual2->num_rows===0){ @$mysqli->query("ALTER TABLE teams ADD KEY idx_game_participant (game_id,participant_id)"); }
        $addCol('games','lucky_number','lucky_number TINYINT NOT NULL DEFAULT 6 AFTER max_rounds');
        $addCol('games','mode',"mode ENUM('teams','individual') NOT NULL DEFAULT 'teams' AFTER status");
        $addCol('teams','profile_id','profile_id INT NULL AFTER game_id');
        $addCol('teams','owner_user_id','owner_user_id INT NULL AFTER profile_id');
        $addCol('teams','participant_id','participant_id INT NULL AFTER owner_user_id');
        $addCol('teams','slot_no','slot_no INT NOT NULL DEFAULT 1 AFTER theme_key');
        $addCol('teams','final_score','final_score INT NULL AFTER sla');
        $addCol('teams','final_rank','final_rank INT NULL AFTER final_score');
        $addCol('teams','is_winner','is_winner TINYINT(1) NOT NULL DEFAULT 0 AFTER final_rank');
        $addCol('team_game_cards','decision_points','decision_points INT NOT NULL DEFAULT 0 AFTER played_round');
        $addCol('team_game_cards','responder_participant_id','responder_participant_id INT NULL AFTER card_id');
        $addCol('team_game_cards','pronunciation_correct','pronunciation_correct TINYINT(1) NOT NULL DEFAULT 0 AFTER responder_participant_id');
        $addCol('team_game_cards','pronunciation_bonus','pronunciation_bonus INT NOT NULL DEFAULT 0 AFTER pronunciation_correct');
        $addCol('team_game_cards','individual_points','individual_points INT NOT NULL DEFAULT 0 AFTER pronunciation_bonus');
        $addCol('dice_rolls','lucky_number','lucky_number TINYINT NOT NULL DEFAULT 6 AFTER round_no');
        $addCol('participant_points','placement_id','placement_id INT NULL AFTER team_id');
        $addCol('scenarios','area',"area VARCHAR(80) NOT NULL DEFAULT 'Cloud & Infra' AFTER id");
        $addCol('scenarios','focus_json','focus_json JSON NULL AFTER requirements_json');
        $addCol('cards','lifecycle_note','lifecycle_note VARCHAR(255) NULL AFTER complexity');
        $addCol('incidents','technical_detail','technical_detail TEXT NULL AFTER description');
        $addCol('incidents','nontechnical_example','nontechnical_example TEXT NULL AFTER technical_detail');
        $addCol('incidents','business_impact','business_impact TEXT NULL AFTER nontechnical_example');
        $addCol('incidents','response_hint','response_hint TEXT NULL AFTER business_impact');

        $mysqli->query("INSERT IGNORE INTO game_rooms(game_id,name,is_private,is_active,created_by) SELECT g.id,CONCAT('Sala de ',g.name),0,1,NULL FROM games g WHERE g.status<>'finished'");

        // Mantiene estructuras y catálogo introducidos por v1.3/v1.4.
        $idx=$mysqli->query("SHOW INDEX FROM scenarios WHERE Key_name='uq_scenario_name'");
        if($idx && $idx->num_rows===0){ @ $mysqli->query("ALTER TABLE scenarios ADD UNIQUE KEY uq_scenario_name (name)"); }
        $seed='';
        foreach(['seed_v1_3.sql','seed_v1_4.sql'] as $sf){$path=__DIR__.'/sql/'.$sf;if(is_file($path))$seed.=file_get_contents($path)."\n";}
        if($seed!==''){
            if(!$mysqli->multi_query($seed)) throw new RuntimeException($mysqli->error);
            do { if($res=$mysqli->store_result())$res->free(); if(!$mysqli->more_results())break; } while($mysqli->next_result());
            if($mysqli->errno) throw new RuntimeException($mysqli->error);
        }

        // v1.6: dos perfiles de acceso claramente separados. Se restablecen las claves integradas.
        $adminHash='$2y$12$9kOCoGOPcJqMBrjIY2e8oOMG8RGYo6zxinMpCrbMrrtNsbXvfR7RW';
        $generalHash='$2y$12$JOj88iEg9D2LHp4Dc0.DrufOfcXtxvGnJG7WIn5XPiP7thyHBNRLy';
        $st=$mysqli->prepare("INSERT INTO app_users(username,password_hash,display_name,role,is_active) VALUES('admin',?,'Administrador ARQWAR','admin',1) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash),display_name=VALUES(display_name),role='admin',is_active=1");$st->bind_param('s',$adminHash);$st->execute();$st->close();
        $st=$mysqli->prepare("INSERT INTO app_users(username,password_hash,display_name,role,is_active) VALUES('general',?,'Operador General','operator',1) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash),display_name=VALUES(display_name),role='operator',is_active=1");$st->bind_param('s',$generalHash);$st->execute();$st->close();

        $oldMembers=$mysqli->query("SELECT DISTINCT name,role FROM team_members WHERE name<>''");
        if($oldMembers){ while($m=$oldMembers->fetch_assoc()){ $alias=$m['name']; $role=$m['role']; $st=$mysqli->prepare("INSERT IGNORE INTO participants(alias,full_name,specialty) VALUES(?,?,?)");$st->bind_param('sss',$alias,$alias,$role);$st->execute();$st->close(); } }
        $mysqli->query("UPDATE team_members tm JOIN participants p ON p.alias=tm.name SET tm.participant_id=p.id WHERE tm.participant_id IS NULL");
        $mysqli->query("INSERT IGNORE INTO team_profiles(alias,description,theme_key) SELECT DISTINCT name,'Migrado desde una partida anterior',theme_key FROM teams WHERE name<>''");
        $mysqli->query("UPDATE teams t JOIN team_profiles tp ON tp.alias=t.name SET t.profile_id=tp.id WHERE t.profile_id IS NULL");
        foreach(($mysqli->query("SELECT DISTINCT game_id FROM teams ORDER BY game_id")?->fetch_all(MYSQLI_ASSOC) ?? []) as $g){
            $gid=(int)$g['game_id'];$res=$mysqli->query("SELECT id FROM teams WHERE game_id=$gid ORDER BY id");$slot=1;
            while($row=$res->fetch_assoc()){$tid=(int)$row['id'];$mysqli->query("UPDATE teams SET slot_no=$slot WHERE id=$tid");$slot++;}
        }
        if((($upgradeUser['username']??'')==='admin')){ $_SESSION['user']['role']='admin'; $_SESSION['user']['display_name']='Administrador ARQWAR'; }
        $ok=true;$msg='Actualización ARQWAR v1.10 aplicada. Se agregó recuperación de contraseña por correo y se mantuvo la vinculación 1 cuenta = 1 participante. Se agregó la vinculación automática 1 cuenta = 1 participante, manteniendo equipos, chat, puntos e historial existentes.';
    } catch(Throwable $e){$msg='Error: '.$e->getMessage();}
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Actualizar DEVIOZ ARQWAR</title><link href="assets/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="assets/css/app.css"></head><body class="bg-dark text-light"><main class="container py-5" style="max-width:900px"><div class="brand mb-4">DEVIOZ ARQWAR <span>UPGRADE v1.10</span></div><div class="panel p-4"><h1 class="h3">Actualizar ARQWAR existente</h1><p>Conserva tus partidas, equipos, participantes y rankings. v1.8 conserva tus datos y agrega la vinculacion automatica entre cuentas y participantes, ademas de mantener los dos niveles de acceso y la consola administrativa CRUD.</p><div class="alert alert-warning small"><b>Importante:</b> al ejecutar este upgrade se restablecen las credenciales integradas de <code>admin</code> y <code>general</code> a las indicadas abajo.</div><?php if($msg): ?><div class="alert <?=$ok?'alert-success':'alert-danger'?>"><?=htmlspecialchars($msg)?></div><?php endif; ?><form method="post"><button class="btn btn-warning btn-lg">Actualizar a v1.8</button> <a class="btn btn-outline-light btn-lg" href="index.php">Volver</a></form><?php if($ok): ?><hr><div class="login-hint"><b>Accesos ARQWAR</b><span>General: <code>general</code> / <code>Arqwar2026!</code></span><span>Administrador: <code>admin</code> / <code>AdminArqwar2026!</code></span></div><a class="btn btn-success mt-3" href="login.php">Ir al login</a><?php endif; ?><hr><p class="text-secondary small mb-0">Para una instalación limpia usa <a href="setup.php">setup.php</a>.</p></div></main></body></html>
