<?php
require_once __DIR__.'/includes/bootstrap.php';
require_once __DIR__.'/includes/avatars.php';
admin_required_html();

$sections=[
 'participants'=>'Participantes','teams'=>'Equipos','games'=>'Batallas / partidas','scenarios'=>'Casuísticas',
 'cards'=>'Cartas / herramientas','incidents'=>'Incidentes','combos'=>'Combos','users'=>'Usuarios'
];
$section=$_GET['section']??'participants'; if(!isset($sections[$section]))$section='participants';
$msg='';$error='';
function pstr(string $k,string $d=''): string{return trim((string)($_POST[$k]??$d));}
function pint(string $k,int $d=0): int{return (int)($_POST[$k]??$d);}
function pfloat(string $k,float $d=0): float{return (float)($_POST[$k]??$d);}
function redirect_admin(string $section,string $flag='saved'): never {header('Location: admin.php?section='.urlencode($section).'&'.$flag.'=1');exit;}
function valid_json_or_null(string $v): ?string {if(trim($v)==='')return null; json_decode($v,true,512,JSON_THROW_ON_ERROR); return $v;}

if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  if(!hash_equals(csrf_token(),$_POST['csrf']??''))throw new RuntimeException('Solicitud inválida.');
  $entity=pstr('entity');$action=pstr('action');$id=pint('id');
  if($entity==='participants'){
   if($action==='save'){
    $alias=pstr('alias');if(strlen($alias)<2)throw new RuntimeException('Alias inválido.');
    $vals=[$alias,pstr('full_name')?:null,pstr('specialty')?:null,arqwar_avatar_key(pstr('avatar_emoji','cyber_skull')),pint('is_active',1)];
    if($id){$vals[]=$id;exec_sql('UPDATE participants SET alias=?,full_name=?,specialty=?,avatar_emoji=?,is_active=? WHERE id=?',$vals);}else exec_sql('INSERT INTO participants(alias,full_name,specialty,avatar_emoji,is_active) VALUES(?,?,?,?,?)',$vals);
    redirect_admin('participants');
   }
   if($action==='delete'){exec_sql('DELETE FROM participants WHERE id=?',[$id]);redirect_admin('participants','deleted');}
  }
  if($entity==='teams'){
   if($action==='save'){
    $alias=pstr('alias');if(strlen($alias)<2)throw new RuntimeException('Alias inválido.');
    $vals=[$alias,pstr('description')?:null,pstr('theme_key','blue'),pint('is_active',1)];
    if($id){$vals[]=$id;exec_sql('UPDATE team_profiles SET alias=?,description=?,theme_key=?,is_active=? WHERE id=?',$vals);}else exec_sql('INSERT INTO team_profiles(alias,description,theme_key,is_active) VALUES(?,?,?,?)',$vals);
    redirect_admin('teams');
   }
   if($action==='add_member'){
    $pid=pint('participant_id');$p=row('SELECT * FROM participants WHERE id=?',[$pid]);if(!$p)throw new RuntimeException('Participante inválido.');
    exec_sql('INSERT INTO team_members(team_profile_id,participant_id,name,role) VALUES(?,?,?,?)',[$id,$pid,$p['alias'],$p['specialty']]);redirect_admin('teams');
   }
   if($action==='remove_member'){exec_sql('DELETE FROM team_members WHERE id=?',[pint('member_id')]);redirect_admin('teams');}
   if($action==='delete'){exec_sql('DELETE FROM team_profiles WHERE id=?',[$id]);redirect_admin('teams','deleted');}
  }
  if($entity==='games'){
   if($action==='save'){
    $status=pstr('status','setup');if(!in_array($status,['setup','running','finished'],true))$status='setup';
    exec_sql('UPDATE games SET name=?,scenario_id=?,status=?,current_round=?,max_rounds=? WHERE id=?',[pstr('name'),pint('scenario_id'),$status,max(0,pint('current_round')),max(1,pint('max_rounds',8)),$id]);redirect_admin('games');
   }
   if($action==='delete'){exec_sql('DELETE FROM participant_points WHERE game_id=?',[$id]);exec_sql('DELETE FROM games WHERE id=?',[$id]);redirect_admin('games','deleted');}
  }
  if($entity==='scenarios'){
   if($action==='save'){
    $focus=valid_json_or_null(pstr('focus_json'));$req=valid_json_or_null(pstr('requirements_json'));
    $vals=[pstr('area','Cloud & Infra'),pstr('name'),pstr('client_name'),pstr('description'),pfloat('budget_monthly'),pint('min_security'),pint('min_availability'),pint('min_scalability'),pint('min_performance'),pint('min_reliability'),pint('min_devops'),pfloat('sla_target',99.9),pstr('user_load'),$req,$focus,pint('is_active',1)];
    if(!$vals[1]||!$vals[2])throw new RuntimeException('Nombre y cliente son obligatorios.');
    if($id){$vals[]=$id;exec_sql('UPDATE scenarios SET area=?,name=?,client_name=?,description=?,budget_monthly=?,min_security=?,min_availability=?,min_scalability=?,min_performance=?,min_reliability=?,min_devops=?,sla_target=?,user_load=?,requirements_json=?,focus_json=?,is_active=? WHERE id=?',$vals);}else exec_sql('INSERT INTO scenarios(area,name,client_name,description,budget_monthly,min_security,min_availability,min_scalability,min_performance,min_reliability,min_devops,sla_target,user_load,requirements_json,focus_json,is_active) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',$vals);
    redirect_admin('scenarios');
   }
   if($action==='delete'){if(row('SELECT id FROM games WHERE scenario_id=? LIMIT 1',[$id]))throw new RuntimeException('No se puede eliminar: esta casuística ya fue usada. Puedes desactivarla.');exec_sql('DELETE FROM scenarios WHERE id=?',[$id]);redirect_admin('scenarios','deleted');}
  }
  if($entity==='cards'){
   if($action==='save'){
    $vals=[pstr('code'),pstr('name'),pstr('category'),pstr('provider','Agnostic'),pstr('description'),pfloat('monthly_cost'),pint('security'),pint('availability'),pint('scalability'),pint('performance'),pint('reliability'),pint('devops'),max(1,min(10,pint('complexity',1))),pstr('lifecycle_note')?:null,pint('is_active',1)];
    if(!$vals[0]||!$vals[1])throw new RuntimeException('Código y nombre son obligatorios.');
    if($id){$vals[]=$id;exec_sql('UPDATE cards SET code=?,name=?,category=?,provider=?,description=?,monthly_cost=?,security=?,availability=?,scalability=?,performance=?,reliability=?,devops=?,complexity=?,lifecycle_note=?,is_active=? WHERE id=?',$vals);}else exec_sql('INSERT INTO cards(code,name,category,provider,description,monthly_cost,security,availability,scalability,performance,reliability,devops,complexity,lifecycle_note,is_active) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',$vals);
    redirect_admin('cards');
   }
   if($action==='delete'){if(row('SELECT id FROM team_game_cards WHERE card_id=? LIMIT 1',[$id]))throw new RuntimeException('No se puede eliminar: la carta tiene historial de partidas. Puedes desactivarla.');exec_sql('DELETE FROM cards WHERE id=?',[$id]);redirect_admin('cards','deleted');}
  }
  if($entity==='incidents'){
   if($action==='save'){
    $req=valid_json_or_null(pstr('requirements_json'))??'{}';
    $vals=[pstr('code'),pstr('name'),pstr('description'),pstr('technical_detail')?:null,pstr('nontechnical_example')?:null,pstr('business_impact')?:null,pstr('response_hint')?:null,max(30,pint('duration_sec',120)),$req,pint('success_rep_bonus',5),pint('fail_rep_penalty',10),pfloat('sla_penalty',0.02),pstr('incident_type','general'),pint('is_active',1)];
    if(!$vals[0]||!$vals[1])throw new RuntimeException('Código y nombre son obligatorios.');
    if($id){$vals[]=$id;exec_sql('UPDATE incidents SET code=?,name=?,description=?,technical_detail=?,nontechnical_example=?,business_impact=?,response_hint=?,duration_sec=?,requirements_json=?,success_rep_bonus=?,fail_rep_penalty=?,sla_penalty=?,incident_type=?,is_active=? WHERE id=?',$vals);}else exec_sql('INSERT INTO incidents(code,name,description,technical_detail,nontechnical_example,business_impact,response_hint,duration_sec,requirements_json,success_rep_bonus,fail_rep_penalty,sla_penalty,incident_type,is_active) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)',$vals);
    redirect_admin('incidents');
   }
   if($action==='delete'){if(row('SELECT id FROM games WHERE active_incident_id=? LIMIT 1',[$id]))throw new RuntimeException('No se puede eliminar mientras esté asociado a una partida activa.');exec_sql('DELETE FROM incidents WHERE id=?',[$id]);redirect_admin('incidents','deleted');}
  }
  if($entity==='combos'){
   if($action==='save'){
    $required=valid_json_or_null(pstr('required_codes_json'))??'[]';$bonus=valid_json_or_null(pstr('bonus_json'))??'{}';
    $vals=[pstr('name'),pstr('description'),$required,$bonus,pfloat('cost_delta'),pint('priority',50),pint('is_active',1)];
    if($id){$vals[]=$id;exec_sql('UPDATE combos SET name=?,description=?,required_codes_json=?,bonus_json=?,cost_delta=?,priority=?,is_active=? WHERE id=?',$vals);}else exec_sql('INSERT INTO combos(name,description,required_codes_json,bonus_json,cost_delta,priority,is_active) VALUES(?,?,?,?,?,?,?)',$vals);
    redirect_admin('combos');
   }
   if($action==='delete'){exec_sql('DELETE FROM combos WHERE id=?',[$id]);redirect_admin('combos','deleted');}
  }
  if($entity==='users'){
   if($action==='save'){
    $username=pstr('username');$display=pstr('display_name');$role=pstr('role','operator');if(!in_array($role,['admin','operator'],true))$role='operator';$active=pint('is_active',1);$password=(string)($_POST['password']??'');
    if(!$username||!$display)throw new RuntimeException('Usuario y nombre son obligatorios.');
    ensure_account_participant_schema();
    ensure_password_recovery_schema();
    $email=normalize_email((string)($_POST['email']??''));
    if($email!=='' && !filter_var($email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('El correo no es válido.');
    if($email!=='' && row('SELECT id FROM app_users WHERE email=? AND id<>? LIMIT 1',[$email,$id?:0])) throw new RuntimeException('Ese correo ya está asociado a otra cuenta.');
    if($id){
      if($id===(int)current_user()['id'] && ($role!=='admin' || !$active)) throw new RuntimeException('No puedes quitarte tu propio rol administrador ni desactivar tu sesión actual.');
      exec_sql('UPDATE app_users SET username=?,display_name=?,email=?,role=?,is_active=? WHERE id=?',[$username,$display,$email?:null,$role,$active,$id]);
      if($password!==''){if(strlen($password)<10)throw new RuntimeException('La contraseña debe tener al menos 10 caracteres.');exec_sql('UPDATE app_users SET password_hash=? WHERE id=?',[password_hash($password,PASSWORD_DEFAULT),$id]);}
      participant_for_user($id,true,['id'=>$id,'username'=>$username,'display_name'=>$display]);
    }else{
      if(strlen($password)<10)throw new RuntimeException('La contraseña debe tener al menos 10 caracteres.');
      exec_sql('INSERT INTO app_users(username,password_hash,display_name,email,role,is_active) VALUES(?,?,?,?,?,?)',[$username,password_hash($password,PASSWORD_DEFAULT),$display,$email?:null,$role,$active]);
      $newUserId=(int)db()->lastInsertId();
      participant_for_user($newUserId,true,['id'=>$newUserId,'username'=>$username,'display_name'=>$display]);
    }
    redirect_admin('users');
   }
   if($action==='delete'){if($id===(int)current_user()['id'])throw new RuntimeException('No puedes eliminar tu propia cuenta mientras estás conectado.');exec_sql('DELETE FROM app_users WHERE id=?',[$id]);redirect_admin('users','deleted');}
  }
 }catch(Throwable $e){$error=$e->getMessage();}
}
if(isset($_GET['saved']))$msg='Cambios guardados.';if(isset($_GET['deleted']))$msg='Registro eliminado.';
$editId=(int)($_GET['edit']??0);
$edit=null;if($editId){$table=['participants'=>'participants','teams'=>'team_profiles','games'=>'games','scenarios'=>'scenarios','cards'=>'cards','incidents'=>'incidents','combos'=>'combos','users'=>'app_users'][$section];$edit=row("SELECT * FROM `$table` WHERE id=?",[$editId]);}
function checkedv($v): string{return (int)$v===1?'checked':'';}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ARQWAR · Administración</title><link href="assets/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="assets/css/app.css"><link rel="stylesheet" href="assets/css/avatar-3d.css?v=3"><style>.admin-table td,.admin-table th{vertical-align:middle}.admin-form{position:sticky;top:1rem}.admin-nav{overflow:auto;white-space:nowrap}.admin-danger{border-color:#8b2431!important}.small-json{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.78rem}</style></head><body class="gm-body">
<nav class="navbar navbar-dark border-bottom border-secondary-subtle"><div class="container-fluid"><a class="navbar-brand brand" href="index.php">ARQWAR <span>ADMIN CRUD</span></a><div class="d-flex gap-2"><span class="badge text-bg-danger align-self-center">ADMIN</span><a class="btn btn-sm btn-outline-light" href="index.php">Inicio</a><a class="btn btn-sm btn-outline-secondary" href="logout.php">Salir</a></div></div></nav>
<main class="container-fluid py-4 px-lg-4"><div class="admin-nav d-flex gap-2 mb-4"><?php foreach($sections as $key=>$label):?><a class="btn btn-sm <?=$section===$key?'btn-warning':'btn-outline-secondary'?>" href="admin.php?section=<?=$key?>"><?=e($label)?></a><?php endforeach;?></div>
<div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-3"><div><div class="eyebrow">CONTROL TOTAL</div><h1 class="h3 mb-0"><?=e($sections[$section])?></h1><p class="text-secondary mb-0">Crear, editar, activar/desactivar y eliminar registros maestros.</p></div><a class="btn btn-outline-info" href="admin.php?section=<?=$section?>">+ Nuevo / limpiar formulario</a></div>
<?php if($msg):?><div class="alert alert-success"><?=e($msg)?></div><?php endif;?><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<div class="row g-4"><div class="col-xl-4"><section class="panel p-3 admin-form"><h2 class="h5"><?php if($section==='games'&&!$edit):?>Administrar batallas existentes<?php else:?><?=$edit?'Editar #'.(int)$edit['id']:'Crear nuevo'?><?php endif;?></h2>
<form method="post" class="row g-2"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="entity" value="<?=e($section)?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?=$editId?>">
<?php if($section==='participants'): ?>
<div class="col-12"><label class="form-label">Alias</label><input class="form-control" name="alias" value="<?=e($edit['alias']??'')?>" required></div><div class="col-12"><label class="form-label">Nombre</label><input class="form-control" name="full_name" value="<?=e($edit['full_name']??'')?>"></div><div class="col-12"><label class="form-label">Especialidad</label><input class="form-control" name="specialty" value="<?=e($edit['specialty']??'')?>"></div><div class="col-6">
  <label class="form-label">Avatar</label>
  <?php $adminAvatarKey=arqwar_avatar_key((string)($edit['avatar_emoji']??'cyber_skull')); ?>
  <div class="d-flex gap-2 align-items-center">
    <span id="adminAvatarPreview"><?=arqwar_avatar_html($adminAvatarKey,'admin-avatar-preview')?></span>
    <select class="form-select" name="avatar_emoji" id="adminAvatarSelect" aria-label="Seleccionar avatar">
      <?php foreach(arqwar_avatar_catalog() as $key=>$avatarMeta): ?>
      <option value="<?=e($key)?>" data-accent="<?=arqwar_avatar_accent($key)?>" <?=$key===$adminAvatarKey?'selected':''?>><?=e($avatarMeta['name'])?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div><div class="col-6"><label class="form-label">Activo</label><select class="form-select" name="is_active"><option value="1" <?=($edit['is_active']??1)?'selected':''?>>Sí</option><option value="0" <?=isset($edit['is_active'])&&!$edit['is_active']?'selected':''?>>No</option></select></div>
<?php elseif($section==='teams'): ?>
<div class="col-12"><label class="form-label">Alias</label><input class="form-control" name="alias" value="<?=e($edit['alias']??'')?>" required></div><div class="col-12"><label class="form-label">Descripción</label><textarea class="form-control" name="description"><?=e($edit['description']??'')?></textarea></div><div class="col-6"><label class="form-label">Tema</label><select class="form-select" name="theme_key"><?php foreach(['blue','red','green','purple','orange','cyan','indigo','teal','gold','pink','slate','lime'] as $v):?><option <?=$v===($edit['theme_key']??'blue')?'selected':''?>><?=$v?></option><?php endforeach;?></select></div><div class="col-6"><label class="form-label">Activo</label><select class="form-select" name="is_active"><option value="1" <?=($edit['is_active']??1)?'selected':''?>>Sí</option><option value="0" <?=isset($edit['is_active'])&&!$edit['is_active']?'selected':''?>>No</option></select></div>
<?php elseif($section==='games'): $scs=rows('SELECT id,area,name FROM scenarios ORDER BY area,name'); ?>
<div class="col-12"><label class="form-label">Nombre</label><input class="form-control" name="name" value="<?=e($edit['name']??'')?>" required></div><div class="col-12"><label class="form-label">Casuística</label><select class="form-select" name="scenario_id"><?php foreach($scs as $s):?><option value="<?=$s['id']?>" <?=($edit['scenario_id']??0)==$s['id']?'selected':''?>><?=e($s['area'].' · '.$s['name'])?></option><?php endforeach;?></select></div><div class="col-6"><label class="form-label">Estado</label><select class="form-select" name="status"><?php foreach(['setup','running','finished'] as $v):?><option <?=$v===($edit['status']??'setup')?'selected':''?>><?=$v?></option><?php endforeach;?></select></div><div class="col-3"><label class="form-label">Ronda</label><input type="number" class="form-control" name="current_round" value="<?=e((string)($edit['current_round']??0))?>"></div><div class="col-3"><label class="form-label">Máx.</label><input type="number" class="form-control" name="max_rounds" value="<?=e((string)($edit['max_rounds']??8))?>"></div><?php if(!$edit):?><div class="col-12"><div class="alert alert-info small mb-0">Las batallas nuevas se crean con el flujo completo de área, casuística y equipos.</div><a class="btn btn-warning w-100 mt-2" href="game-master.php?new=1">Crear nueva batalla</a></div><?php endif;?>
<?php elseif($section==='scenarios'): ?>
<?php $areas=['Desarrollo','Data','DevOps','MLOps','FinOps','RPA','Cybersecurity','Cloud & Infra','AI & GenAI','Observability & SRE']; ?><div class="col-12"><label class="form-label">Área</label><select class="form-select" name="area"><?php foreach($areas as $v):?><option <?=$v===($edit['area']??'Cloud & Infra')?'selected':''?>><?=e($v)?></option><?php endforeach;?></select></div><div class="col-6"><label class="form-label">Casuística</label><input class="form-control" name="name" value="<?=e($edit['name']??'')?>" required></div><div class="col-6"><label class="form-label">Cliente</label><input class="form-control" name="client_name" value="<?=e($edit['client_name']??'')?>" required></div><div class="col-12"><label class="form-label">Descripción</label><textarea class="form-control" rows="3" name="description"><?=e($edit['description']??'')?></textarea></div><div class="col-6"><label class="form-label">Presupuesto</label><input type="number" step=".01" class="form-control" name="budget_monthly" value="<?=e((string)($edit['budget_monthly']??3000))?>"></div><div class="col-6"><label class="form-label">Carga</label><input class="form-control" name="user_load" value="<?=e($edit['user_load']??'')?>"></div><?php foreach(['security'=>'SEC','availability'=>'AVL','scalability'=>'SCL','performance'=>'PER','reliability'=>'REL','devops'=>'DEV'] as $k=>$lab):?><div class="col-4"><label class="form-label"><?=$lab?></label><input type="number" min="0" max="100" class="form-control" name="min_<?=$k?>" value="<?=e((string)($edit['min_'.$k]??60))?>"></div><?php endforeach;?><div class="col-6"><label class="form-label">SLA objetivo</label><input type="number" step=".001" class="form-control" name="sla_target" value="<?=e((string)($edit['sla_target']??99.9))?>"></div><div class="col-6"><label class="form-label">Activo</label><select class="form-select" name="is_active"><option value="1" <?=($edit['is_active']??1)?'selected':''?>>Sí</option><option value="0" <?=isset($edit['is_active'])&&!$edit['is_active']?'selected':''?>>No</option></select></div><div class="col-12"><label class="form-label">Requisitos JSON</label><textarea class="form-control small-json" name="requirements_json" rows="2"><?=e($edit['requirements_json']??'{}')?></textarea></div><div class="col-12"><label class="form-label">Foco JSON</label><textarea class="form-control small-json" name="focus_json" rows="2"><?=e($edit['focus_json']??'[]')?></textarea></div>
<?php elseif($section==='cards'): ?>
<div class="col-5"><label class="form-label">Código</label><input class="form-control" name="code" value="<?=e($edit['code']??'')?>" required></div><div class="col-7"><label class="form-label">Nombre</label><input class="form-control" name="name" value="<?=e($edit['name']??'')?>" required></div><div class="col-6"><label class="form-label">Categoría</label><input class="form-control" name="category" value="<?=e($edit['category']??'')?>"></div><div class="col-6"><label class="form-label">Proveedor</label><input class="form-control" name="provider" value="<?=e($edit['provider']??'Agnostic')?>"></div><div class="col-12"><label class="form-label">Descripción</label><textarea class="form-control" name="description" rows="2"><?=e($edit['description']??'')?></textarea></div><div class="col-4"><label class="form-label">Costo</label><input type="number" step=".01" class="form-control" name="monthly_cost" value="<?=e((string)($edit['monthly_cost']??0))?>"></div><div class="col-4"><label class="form-label">Complejidad</label><input type="number" min="1" max="10" class="form-control" name="complexity" value="<?=e((string)($edit['complexity']??1))?>"></div><div class="col-4"><label class="form-label">Activo</label><select class="form-select" name="is_active"><option value="1" <?=($edit['is_active']??1)?'selected':''?>>Sí</option><option value="0" <?=isset($edit['is_active'])&&!$edit['is_active']?'selected':''?>>No</option></select></div><?php foreach(['security'=>'SEC','availability'=>'AVL','scalability'=>'SCL','performance'=>'PER','reliability'=>'REL','devops'=>'DEV'] as $k=>$lab):?><div class="col-4"><label class="form-label"><?=$lab?></label><input type="number" class="form-control" name="<?=$k?>" value="<?=e((string)($edit[$k]??0))?>"></div><?php endforeach;?><div class="col-12"><label class="form-label">Nota lifecycle</label><input class="form-control" name="lifecycle_note" value="<?=e($edit['lifecycle_note']??'')?>"></div>
<?php elseif($section==='incidents'): ?>
<div class="col-5"><label class="form-label">Código</label><input class="form-control" name="code" value="<?=e($edit['code']??'')?>" required></div><div class="col-7"><label class="form-label">Nombre</label><input class="form-control" name="name" value="<?=e($edit['name']??'')?>" required></div><div class="col-6"><label class="form-label">Tipo</label><input class="form-control" name="incident_type" value="<?=e($edit['incident_type']??'general')?>"></div><div class="col-6"><label class="form-label">Duración (s)</label><input type="number" class="form-control" name="duration_sec" value="<?=e((string)($edit['duration_sec']??120))?>"></div><?php foreach(['description'=>'Descripción','technical_detail'=>'Detalle técnico','nontechnical_example'=>'Ejemplo no técnico','business_impact'=>'Impacto negocio','response_hint'=>'Pista'] as $k=>$lab):?><div class="col-12"><label class="form-label"><?=$lab?></label><textarea class="form-control" name="<?=$k?>" rows="2"><?=e($edit[$k]??'')?></textarea></div><?php endforeach;?><div class="col-12"><label class="form-label">Requisitos JSON</label><textarea class="form-control small-json" name="requirements_json" rows="2"><?=e($edit['requirements_json']??'{}')?></textarea></div><div class="col-4"><label class="form-label">Bonus REP</label><input type="number" class="form-control" name="success_rep_bonus" value="<?=e((string)($edit['success_rep_bonus']??5))?>"></div><div class="col-4"><label class="form-label">Penalización REP</label><input type="number" class="form-control" name="fail_rep_penalty" value="<?=e((string)($edit['fail_rep_penalty']??10))?>"></div><div class="col-4"><label class="form-label">Penalización SLA</label><input type="number" step=".001" class="form-control" name="sla_penalty" value="<?=e((string)($edit['sla_penalty']??.02))?>"></div><div class="col-12"><label class="form-label">Activo</label><select class="form-select" name="is_active"><option value="1" <?=($edit['is_active']??1)?'selected':''?>>Sí</option><option value="0" <?=isset($edit['is_active'])&&!$edit['is_active']?'selected':''?>>No</option></select></div>
<?php elseif($section==='combos'): ?>
<div class="col-12"><label class="form-label">Nombre</label><input class="form-control" name="name" value="<?=e($edit['name']??'')?>" required></div><div class="col-12"><label class="form-label">Descripción</label><textarea class="form-control" name="description"><?=e($edit['description']??'')?></textarea></div><div class="col-12"><label class="form-label">Códigos requeridos JSON</label><textarea class="form-control small-json" name="required_codes_json" rows="2"><?=e($edit['required_codes_json']??'[]')?></textarea></div><div class="col-12"><label class="form-label">Bonus JSON</label><textarea class="form-control small-json" name="bonus_json" rows="2"><?=e($edit['bonus_json']??'{}')?></textarea></div><div class="col-4"><label class="form-label">Costo delta</label><input type="number" step=".01" class="form-control" name="cost_delta" value="<?=e((string)($edit['cost_delta']??0))?>"></div><div class="col-4"><label class="form-label">Prioridad</label><input type="number" class="form-control" name="priority" value="<?=e((string)($edit['priority']??50))?>"></div><div class="col-4"><label class="form-label">Activo</label><select class="form-select" name="is_active"><option value="1" <?=($edit['is_active']??1)?'selected':''?>>Sí</option><option value="0" <?=isset($edit['is_active'])&&!$edit['is_active']?'selected':''?>>No</option></select></div>
<?php elseif($section==='users'): ?>
<div class="col-12"><label class="form-label">Usuario</label><input class="form-control" name="username" value="<?=e($edit['username']??'')?>" required></div><div class="col-12"><label class="form-label">Nombre visible</label><input class="form-control" name="display_name" value="<?=e($edit['display_name']??'')?>" required></div><div class="col-12"><label class="form-label">Correo electrónico</label><input class="form-control" type="email" name="email" value="<?=e($edit['email']??'')?>" placeholder="usuario@gmail.com"></div><div class="col-6"><label class="form-label">Rol</label><select class="form-select" name="role"><option value="operator" <?=($edit['role']??'operator')==='operator'?'selected':''?>>General / operador</option><option value="admin" <?=($edit['role']??'')==='admin'?'selected':''?>>Administrador</option></select></div><div class="col-6"><label class="form-label">Activo</label><select class="form-select" name="is_active"><option value="1" <?=($edit['is_active']??1)?'selected':''?>>Sí</option><option value="0" <?=isset($edit['is_active'])&&!$edit['is_active']?'selected':''?>>No</option></select></div><div class="col-12"><label class="form-label"><?=$edit?'Nueva contraseña (opcional)':'Contraseña'?></label><input type="password" class="form-control" name="password" <?=$edit?'':'required'?> minlength="10"></div>
<?php endif; ?>
<?php if($section!=='games'||$edit):?><div class="col-12"><button class="btn btn-warning w-100"><?=$edit?'Guardar cambios':'Crear registro'?></button></div><?php endif;?></form>
<?php if($section==='teams' && $edit): $people=rows('SELECT * FROM participants WHERE is_active=1 ORDER BY alias');$members=rows('SELECT tm.*,COALESCE(p.alias,tm.name) alias FROM team_members tm LEFT JOIN participants p ON p.id=tm.participant_id WHERE tm.team_profile_id=? ORDER BY alias',[$editId]);?><hr><h3 class="h6">Integrantes</h3><?php foreach($members as $m):?><div class="d-flex justify-content-between align-items-center border-bottom py-2"><span><?=e($m['alias'])?></span><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="entity" value="teams"><input type="hidden" name="action" value="remove_member"><input type="hidden" name="member_id" value="<?=$m['id']?>"><button class="btn btn-sm btn-outline-danger">Quitar</button></form></div><?php endforeach;?><form method="post" class="d-flex gap-2 mt-3"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="entity" value="teams"><input type="hidden" name="action" value="add_member"><input type="hidden" name="id" value="<?=$editId?>"><select class="form-select form-select-sm" name="participant_id"><?php foreach($people as $p):?><option value="<?=$p['id']?>"><?=e($p['alias'])?></option><?php endforeach;?></select><button class="btn btn-sm btn-outline-info">Agregar</button></form><?php endif;?>
</section></div><div class="col-xl-8"><section class="panel p-3"><div class="table-responsive"><table class="table table-dark table-hover align-middle admin-table mb-0"><thead><tr><th>ID</th><th>Registro</th><th>Detalle</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead><tbody>
<?php
if($section==='participants')$items=rows('SELECT p.*,u.username linked_username,u.display_name linked_display_name FROM participants p LEFT JOIN app_users u ON u.id=p.user_id ORDER BY p.id DESC');
elseif($section==='teams')$items=rows('SELECT tp.*,(SELECT COUNT(*) FROM team_members tm WHERE tm.team_profile_id=tp.id) members FROM team_profiles tp ORDER BY id DESC');
elseif($section==='games')$items=rows('SELECT g.*,s.area,s.name scenario_name,(SELECT COUNT(*) FROM teams t WHERE t.game_id=g.id) teams_count FROM games g JOIN scenarios s ON s.id=g.scenario_id ORDER BY g.id DESC');
elseif($section==='scenarios')$items=rows('SELECT * FROM scenarios ORDER BY area,name');
elseif($section==='cards')$items=rows('SELECT * FROM cards ORDER BY id DESC LIMIT 1000');
elseif($section==='incidents')$items=rows('SELECT * FROM incidents ORDER BY id DESC');
elseif($section==='combos')$items=rows('SELECT * FROM combos ORDER BY priority DESC,id DESC');
else $items=rows('SELECT * FROM app_users ORDER BY id');
foreach($items as $it):
 if($section==='participants'){$title=$it['alias'];$detail=($it['full_name']?:'').' · '.($it['specialty']?:'Sin especialidad').' · '.(!empty($it['linked_username'])?'Cuenta: @'.$it['linked_username']:'Sin cuenta vinculada');$active=$it['is_active'];}
 elseif($section==='teams'){$title=$it['alias'];$detail=$it['members'].' integrantes · '.($it['description']?:'Sin descripción');$active=$it['is_active'];}
 elseif($section==='games'){$title=$it['name'];$detail=$it['area'].' · '.$it['scenario_name'].' · '.$it['teams_count'].' equipos · ronda '.$it['current_round'].'/'.$it['max_rounds'];$active=$it['status']!=='finished';}
 elseif($section==='scenarios'){$title=$it['name'];$detail=$it['area'].' · '.$it['client_name'].' · $'.number_format((float)$it['budget_monthly'],0);$active=$it['is_active'];}
 elseif($section==='cards'){$title=$it['name'];$detail=$it['category'].' · '.$it['provider'].' · '.$it['code'];$active=$it['is_active'];}
 elseif($section==='incidents'){$title=$it['name'];$detail=$it['incident_type'].' · '.$it['code'];$active=$it['is_active'];}
 elseif($section==='combos'){$title=$it['name'];$detail='Prioridad '.$it['priority'].' · delta $'.$it['cost_delta'];$active=$it['is_active'];}
 else {$title=$it['username'];$detail=$it['display_name'].' · '.($it['role']==='admin'?'Administrador':'General / operador');$active=$it['is_active'];}
?><tr><td>#<?=$it['id']?></td><td><b><?=e($title)?></b></td><td class="small text-secondary"><?=e($detail)?></td><td><span class="badge <?=$active?'text-bg-success':'text-bg-secondary'?>"><?=$active?'Activo':'Inactivo / finalizado'?></span></td><td class="text-end"><a class="btn btn-sm btn-outline-info" href="admin.php?section=<?=$section?>&edit=<?=$it['id']?>">Editar</a> <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar permanentemente este registro? Esta acción no se puede deshacer.');"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="entity" value="<?=$section?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$it['id']?>"><button class="btn btn-sm btn-outline-danger">Eliminar</button></form></td></tr><?php endforeach;?>
</tbody></table></div></section></div></div></main><script>
const adminAvatarSelect=document.getElementById('adminAvatarSelect');
adminAvatarSelect?.addEventListener('change',()=>{
  const img=document.querySelector('#adminAvatarPreview img');
  const badge=document.querySelector('#adminAvatarPreview .arqwar-avatar');
  const selected=adminAvatarSelect.selectedOptions[0];
  if(img)img.src='assets/img/avatars-3d/'+adminAvatarSelect.value+'-icon.webp';
  if(badge&&selected){
    badge.style.setProperty('--avatar-neon',selected.dataset.accent||'#00eaff');
    badge.title=selected.textContent;
    badge.dataset.avatarKey=adminAvatarSelect.value;
  }
});
</script></body></html>
