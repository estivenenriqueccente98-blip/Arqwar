<?php
require_once __DIR__.'/includes/bootstrap.php';
require_once __DIR__.'/includes/arqwar-identity.php';
$cards=[];$err=null;try{$cards=rows('SELECT * FROM cards WHERE is_active=1 ORDER BY category,name');}catch(Throwable $e){$err=$e->getMessage();}
function brand_mark(array $c): array {
    $name=strtolower((string)($c['name']??''));
    $provider=strtolower((string)($c['provider']??''));
    $map=[
        'power bi'=>['BI','brand-microsoft'],'power automate'=>['PA','brand-microsoft'],'azure'=>['AZ','brand-azure'],
        'aws'=>['AWS','brand-aws'],'google'=>['G','brand-google'],'gcp'=>['G','brand-google'],'oracle'=>['OR','brand-oracle'],
        'dell'=>['DE','brand-dell'],'informatica'=>['IN','brand-informatica'],'snowflake'=>['SF','brand-snowflake'],
        'databricks'=>['DB','brand-databricks'],'docker'=>['DK','brand-docker'],'kubernetes'=>['K8','brand-kubernetes'],
        'cloudflare'=>['CF','brand-cloudflare'],'microsoft'=>['MS','brand-microsoft'], 'agnostic'=>['AG','brand-generic']
    ];
    foreach($map as $key=>$val){if(str_contains($name,$key)||str_contains($provider,$key)) return $val;}
    $abbr=strtoupper(substr(preg_replace('/[^a-z0-9]/i','',$c['name']?:$c['provider']),0,2) ?: 'AR');
    return [$abbr,'brand-generic'];
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ARQWAR · Cartas</title><link href="assets/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="assets/css/app.css"><link rel="stylesheet" href="assets/css/arqwar-identity.css?v=20261028-2">
</head><body class="gm-body arqwar-glow-bg"><nav class="navbar navbar-dark border-bottom border-secondary-subtle"><div class="container"><?= arqwar_brand_html('CATÁLOGO') ?><div class="d-flex gap-2"><a class="btn btn-sm btn-outline-light" href="index.php">Inicio</a><a class="btn btn-sm btn-outline-light" href="game-master.php">Game Master</a><?php if(current_user() && is_admin()):?><a class="btn btn-sm btn-danger" href="admin.php?section=cards">Admin CRUD</a><?php endif;?></div></div></nav><main class="container py-4"><?php if($err): ?><div class="alert alert-danger"><?=e($err)?></div><?php endif; ?><div class="d-flex justify-content-between align-items-end mb-3 flex-wrap gap-3"><div><h1 class="h3">Catálogo de cartas</h1><p class="text-secondary mb-0"><?=count($cards)?> servicios y herramientas disponibles. Los costos son valores simulados del juego, no precios comerciales.</p></div><input id="cardFilter" class="form-control glass-search" style="max-width:320px" placeholder="Buscar carta..."></div><div class="row g-3" id="cardGrid"><?php foreach($cards as $c): list($mark,$markClass)=brand_mark($c); ?><div class="col-sm-6 col-lg-4 col-xl-3 card-search" data-search="<?=e(strtolower($c['name'].' '.$c['provider'].' '.$c['category'].' '.$c['code'].' '.($c['description']??'').' '.($c['lifecycle_note']??'')))?>"><article class="tech-card h-100 arq-card depth-card"><div class="tech-card-top"><div class="d-flex align-items-center gap-2"><span class="brand-mark <?=$markClass?>"><?=e($mark)?></span><div><span class="badge text-bg-secondary"><?=e($c['category'])?></span><div class="small text-secondary mt-1"><?=e($c['provider'])?></div></div></div><span class="tech-cost">$<?=number_format($c['monthly_cost'],0)?></span></div><h2 class="mt-3"><?=e($c['name'])?></h2><p><?=e($c['description'])?></p><div class="stat-chips"><span>SEC +<?=$c['security']?></span><span>AVL +<?=$c['availability']?></span><span>SCL +<?=$c['scalability']?></span><span>PER +<?=$c['performance']?></span><span>REL +<?=$c['reliability']?></span><span>DEV +<?=$c['devops']?></span></div><?php if(!empty($c['lifecycle_note'])): ?><div class="lifecycle-note mt-2"><b>⚠ Ciclo de vida</b><span><?=e($c['lifecycle_note'])?></span></div><?php endif; ?><div class="small text-secondary mt-2">Código: <?=e($c['code'])?> · Complejidad <?=$c['complexity']?>/10 · costo simulado</div></article></div><?php endforeach; ?></div></main><script>
(()=>{
 const input=document.getElementById('cardFilter');
 if(!input)return;
 const normalize=s=>String(s||'').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g,'');
 const aliases={scaling:['scaling','autoscal','scale'],cluster:['cluster','kubernetes','orchestration'],bi:['bi','analytics','looker','tableau','power bi'],rpa:['rpa','automation','uipath','power automate']};
 input.addEventListener('input',e=>{
   const raw=normalize(e.target.value).trim();
   const tokens=raw.split(/\s+/).filter(Boolean);
   document.querySelectorAll('.card-search').forEach(x=>{
     const hay=normalize(x.dataset.search);
     const ok=!tokens.length||tokens.every(t=>{const choices=aliases[t]||[t];return choices.some(v=>hay.includes(v));});
     x.style.display=ok?'':'none';
   });
 });
})();
</script></body></html>