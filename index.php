<?php 
require_once __DIR__.'/includes/bootstrap.php';
require_once __DIR__.'/includes/arqwar-identity.php';
require_once __DIR__.'/includes/arqwar-mascot.php'; 
$dbOk=true; $latest=null; $user=current_user(); $stats=['teams'=>0,'participants'=>0,'scenarios'=>0,'cards'=>0,'games'=>0]; 
try { 
    $latest=latest_game(); 
    $stats['teams']=(int)(row('SELECT COUNT(*) n FROM team_profiles WHERE is_active=1')['n']??0); 
    $stats['participants']=(int)(row('SELECT COUNT(*) n FROM participants WHERE is_active=1')['n']??0); 
    $stats['scenarios']=(int)(row('SELECT COUNT(*) n FROM scenarios WHERE is_active=1')['n']??0); 
    $stats['cards']=(int)(row('SELECT COUNT(*) n FROM cards WHERE is_active=1')['n']??0); 
    $stats['games']=(int)(row("SELECT COUNT(*) n FROM games WHERE status='finished'")['n']??0); 
} catch(Throwable $e) { $dbOk=false; } 
?> 
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ARQWAR</title>
<link href="assets/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/app.css">
<link rel="stylesheet" href="assets/css/arqwar-mascot.css?v=20261029-1">
<style>
body.home-body{
margin:0!important;
min-height:100vh!important;
background:
radial-gradient(circle at 8% 16%,rgba(0,229,255,.16),transparent 28%),
radial-gradient(circle at 88% 82%,rgba(125,68,255,.22),transparent 34%),
linear-gradient(135deg,#03111b 0%,#040a16 50%,#08031a 100%)!important;
background-attachment:fixed!important;
color:#ecfaff!important;
position:relative!important;
overflow-x:hidden!important;
}
body.home-body:before{
content:"";
position:fixed;
inset:0;
z-index:0;
pointer-events:none;
background-image:
linear-gradient(rgba(45,225,255,.035) 1px,transparent 1px),
linear-gradient(90deg,rgba(45,225,255,.035) 1px,transparent 1px);
background-size:38px 38px;
}
body.home-body:after{
content:"";
position:fixed;
inset:0;
z-index:0;
pointer-events:none;
background:
radial-gradient(circle at 15% 30%,rgba(0,255,255,.05),transparent 10%),
radial-gradient(circle at 75% 65%,rgba(133,73,255,.08),transparent 18%);
}
body.home-body main.container{
position:relative;
z-index:1;
max-width:1120px!important;
padding-top:38px!important;
padding-bottom:38px!important;
}
body.home-body .hero-card{
position:relative!important;
overflow:hidden!important;
border-radius:16px!important;
border:1px solid rgba(69,219,255,.46)!important;
background:
linear-gradient(145deg,rgba(13,40,54,.96),rgba(7,15,29,.98) 55%,rgba(26,13,47,.97))!important;
box-shadow:
0 0 25px rgba(40,227,255,.11),
0 0 60px rgba(128,69,255,.10),
0 25px 70px rgba(0,0,0,.55),
inset 0 1px 0 rgba(255,255,255,.05)!important;
}
body.home-body .hero-card:before{
content:"";
position:absolute;
top:0;
left:4%;
right:4%;
height:2px;
background:linear-gradient(90deg,transparent,#2be8ff,#9e6cff,#2be8ff,transparent);
box-shadow:0 0 14px #2be8ff;
}
body.home-body .hero-card:after{
content:"";
position:absolute;
width:420px;
height:420px;
right:-200px;
bottom:-240px;
border-radius:50%;
background:radial-gradient(circle,rgba(129,70,255,.18),transparent 68%);
pointer-events:none;
}
body.home-body .brand{
font-weight:950!important;
letter-spacing:.10em!important;
color:#dffcff!important;
text-shadow:
0 0 5px rgba(52,235,255,.95),
0 0 18px rgba(52,235,255,.42),
3px 0 12px rgba(147,86,255,.22)!important;
}
body.home-body .brand span{
color:#9bb5cf!important;
font-size:.48em!important;
font-weight:600!important;
text-shadow:none!important;
}
body.home-body .lead{
color:#f5fdff!important;
font-size:1rem!important;
}
body.home-body .text-secondary{
color:#809fbb!important;
font-size:.82rem!important;
}
body.home-body .hero-card>.d-flex{
position:relative!important;
padding-bottom:6px!important;
}
body.home-body .hero-card>.d-flex:after{
content:"";
position:absolute;
left:0;
bottom:-8px;
width:58%;
height:1px;
background:linear-gradient(90deg,#28e9ff,rgba(40,233,255,.12),transparent);
}
body.home-body .text-end{
position:relative!important;
min-width:180px!important;
padding:12px 16px!important;
border:1px solid rgba(55,231,255,.56)!important;
border-radius:10px!important;
background:linear-gradient(135deg,rgba(16,65,75,.46),rgba(8,19,35,.82))!important;
box-shadow:
0 0 18px rgba(45,231,255,.12),
inset 0 0 20px rgba(45,231,255,.04)!important;
}
body.home-body .text-end:before{
content:"◉";
position:absolute;
left:14px;
top:50%;
transform:translateY(-50%);
font-size:2rem;
color:#39ecff;
opacity:.15;
text-shadow:0 0 15px #39ecff;
}
body.home-body .text-end>*{
position:relative;
z-index:1;
}
body.home-body .eyebrow{
font-size:.60rem!important;
letter-spacing:.16em!important;
font-weight:900!important;
color:#61ebff!important;
}
body.home-body .text-end b{
font-size:.80rem!important;
}
body.home-body .text-end .badge{
font-size:.58rem!important;
border-radius:4px!important;
padding:4px 8px!important;
}
body.home-body .text-end a{
color:#35e5ff!important;
text-decoration:none!important;
font-size:.67rem!important;
}
body.home-body .home-stat-grid{
display:grid!important;
grid-template-columns:repeat(4,1fr)!important;
gap:9px!important;
}
body.home-body .home-stat-grid>div{
position:relative!important;
overflow:hidden!important;
min-height:68px!important;
padding:12px 14px!important;
border:1px solid rgba(57,219,247,.56)!important;
border-radius:9px!important;
background:
linear-gradient(135deg,rgba(17,78,85,.60),rgba(8,27,40,.92))!important;
box-shadow:
0 0 13px rgba(45,225,255,.10),
inset 0 1px 0 rgba(255,255,255,.04)!important;
}
body.home-body .home-stat-grid>div:before{
content:"";
position:absolute;
left:0;
bottom:0;
width:58%;
height:2px;
background:linear-gradient(90deg,#28eaff,transparent);
box-shadow:0 0 10px #28eaff;
}
body.home-body .home-stat-grid>div:after{
content:"|||||||||||";
position:absolute;
right:12px;
bottom:13px;
font-family:monospace;
font-size:.65rem;
letter-spacing:-2px;
color:rgba(43,230,255,.27);
transform:scaleY(1.8);
}
body.home-body .home-stat-grid span{
display:block!important;
font-size:.57rem!important;
letter-spacing:.13em!important;
color:#9bc4d3!important;
font-weight:900!important;
}
body.home-body .home-stat-grid b{
display:block!important;
font-size:1.55rem!important;
line-height:1.1!important;
margin-top:3px!important;
color:#2feaff!important;
text-shadow:0 0 12px rgba(47,234,255,.72)!important;
}
body.home-body .home-stat-grid>div:nth-child(5){
grid-column:1!important;
}
body.home-body .home-action-grid{
display:grid!important;
grid-template-columns:repeat(4,1fr)!important;
gap:9px!important;
}
body.home-body .home-action{
position:relative!important;
overflow:hidden!important;
display:flex!important;
align-items:center!important;
gap:11px!important;
min-height:76px!important;
padding:12px 14px!important;
border:1px solid rgba(70,211,245,.50)!important;
border-radius:9px!important;
background:
linear-gradient(135deg,rgba(19,60,70,.90),rgba(8,19,32,.96))!important;
color:#ffffff!important;
text-decoration:none!important;
box-shadow:
0 7px 19px rgba(0,0,0,.30),
inset 0 1px 0 rgba(255,255,255,.04)!important;
transition:.22s ease!important;
}
body.home-body .home-action:before{
content:"";
position:absolute;
inset:0;
background:linear-gradient(110deg,transparent 25%,rgba(255,255,255,.06) 48%,transparent 70%);
transform:translateX(-120%);
transition:.45s ease;
}
body.home-body .home-action:after{
content:"";
position:absolute;
left:12%;
right:12%;
bottom:0;
height:3px;
background:linear-gradient(90deg,transparent,#2beaff,transparent);
box-shadow:0 0 13px #2beaff;
}
body.home-body .home-action:hover{
transform:translateY(-4px)!important;
border-color:#44efff!important;
box-shadow:
0 0 25px rgba(47,233,255,.23),
0 14px 30px rgba(0,0,0,.38)!important;
}
body.home-body .home-action:hover:before{
transform:translateX(120%);
}
body.home-body .home-action.primary{
border-color:rgba(164,97,255,.78)!important;
background:
linear-gradient(135deg,rgba(28,67,77,.92),rgba(56,26,87,.94))!important;
}
body.home-body .home-action.primary:after{
background:linear-gradient(90deg,transparent,#aa6dff,#2beaff,transparent)!important;
box-shadow:0 0 14px #9c65ff!important;
}
body.home-body .home-action:nth-child(3),
body.home-body .home-action:nth-child(4),
body.home-body .home-action:nth-child(8){
border-color:rgba(146,91,255,.58)!important;
background:
linear-gradient(135deg,rgba(23,48,64,.92),rgba(49,24,77,.94))!important;
}
body.home-body .home-action:nth-child(3):after,
body.home-body .home-action:nth-child(4):after,
body.home-body .home-action:nth-child(8):after{
background:linear-gradient(90deg,transparent,#9862ff,transparent)!important;
box-shadow:0 0 13px #8958ff!important;
}
body.home-body .home-action:nth-child(5){
border-color:rgba(255,204,73,.42)!important;
}
body.home-body .home-action:nth-child(5):after{
background:linear-gradient(90deg,transparent,#ffc84c,transparent)!important;
box-shadow:0 0 12px #ffc84c!important;
}
body.home-body .home-action>span{
position:relative!important;
z-index:1!important;
display:grid!important;
place-items:center!important;
min-width:38px!important;
height:38px!important;
font-size:1.55rem!important;
border-radius:9px!important;
background:
linear-gradient(145deg,rgba(45,228,255,.17),rgba(131,73,255,.15))!important;
border:1px solid rgba(66,218,255,.20)!important;
box-shadow:
inset 0 0 12px rgba(54,226,255,.07),
0 0 10px rgba(99,72,255,.09)!important;
}
body.home-body .home-action>div{
position:relative!important;
z-index:1!important;
min-width:0!important;
}
body.home-body .home-action b{
display:block!important;
font-size:.84rem!important;
font-weight:800!important;
color:#ffffff!important;
}
body.home-body .home-action small{
display:block!important;
margin-top:3px!important;
font-size:.67rem!important;
line-height:1.35!important;
color:#94b3ca!important;
}
body.home-body .hero-card>.row{
position:relative!important;
padding-top:17px!important;
}
body.home-body .hero-card>.row:before{
content:"";
position:absolute;
top:0;
left:4%;
right:4%;
height:1px;
background:
linear-gradient(90deg,transparent,rgba(46,229,255,.54),rgba(135,75,255,.44),transparent);
}
body.home-body .mini-card{
position:relative!important;
overflow:hidden!important;
min-height:112px!important;
padding:15px!important;
border:1px solid rgba(66,185,220,.38)!important;
border-radius:9px!important;
background:
linear-gradient(145deg,rgba(17,47,59,.90),rgba(7,15,28,.96))!important;
box-shadow:
0 8px 21px rgba(0,0,0,.26),
inset 0 1px 0 rgba(255,255,255,.03)!important;
}
body.home-body .mini-card:before{
content:"";
position:absolute;
right:-2px;
top:-2px;
width:58px;
height:58px;
opacity:.30;
background:
linear-gradient(90deg,transparent 48%,rgba(46,230,255,.20) 49%,rgba(46,230,255,.20) 51%,transparent 52%),
linear-gradient(transparent 48%,rgba(46,230,255,.20) 49%,rgba(46,230,255,.20) 51%,transparent 52%);
background-size:10px 10px;
}
body.home-body .mini-card:after{
content:"";
position:absolute;
left:0;
bottom:0;
width:55%;
height:2px;
background:linear-gradient(90deg,#2feaff,transparent);
box-shadow:0 0 10px #2feaff;
}
body.home-body .mini-card b{
position:relative!important;
z-index:1!important;
font-size:.82rem!important;
color:#fff!important;
}
body.home-body .mini-card span{
position:relative!important;
z-index:1!important;
font-size:.70rem!important;
line-height:1.5!important;
color:#88aac5!important;
}
@media(max-width:1200px){
body.home-body .home-action-grid{
grid-template-columns:repeat(2,1fr)!important;
}
body.home-body .home-stat-grid{
grid-template-columns:repeat(2,1fr)!important;
}
body.home-body .home-stat-grid>div:nth-child(5){
grid-column:auto!important;
}
}
@media(max-width:768px){
body.home-body main.container{
padding:15px!important;
}
body.home-body .hero-card{
padding:20px!important;
}
body.home-body .home-action-grid{
grid-template-columns:1fr!important;
}
body.home-body .home-stat-grid{
grid-template-columns:1fr 1fr!important;
}
body.home-body .text-end{
width:100%!important;
text-align:left!important;
}
}
@media(max-width:480px){
body.home-body .home-stat-grid{
grid-template-columns:1fr!important;
}
}

/* ===== ARQWAR HOME - CARRUSEL 3D NEON ===== */
body.home-body .home-carousel{
position:relative;
width:100%;
margin:0 auto 24px;
perspective:1600px;
}
body.home-body .home-carousel::before{
content:"";
position:absolute;
left:7%;right:7%;top:-2px;height:2px;
z-index:20;
background:linear-gradient(90deg,transparent,#23eaff,#8f5dff,#ff3f9e,#23eaff,transparent);
box-shadow:0 0 18px rgba(35,234,255,.85),0 0 34px rgba(143,93,255,.35);
pointer-events:none;
}
body.home-body .home-carousel-viewport{
position:relative;
width:100%;
aspect-ratio:16 / 8.35;
min-height:300px;
overflow:hidden;
background:
radial-gradient(circle at 50% 55%,rgba(18,128,180,.16),transparent 34%),
linear-gradient(145deg,#020a12,#030814 55%,#07031a);
touch-action:pan-y;
user-select:none;
-webkit-user-select:none;
transform-style:preserve-3d;
}
body.home-body .home-carousel-viewport::before{
content:"";
position:absolute;
inset:0;
z-index:2;
pointer-events:none;
background:
radial-gradient(circle at 50% 50%,transparent 38%,rgba(0,0,0,.18) 72%,rgba(0,0,0,.55) 100%),
linear-gradient(180deg,rgba(0,0,0,.06),transparent 45%,rgba(0,0,0,.20));
}
body.home-body .home-carousel-track{
position:relative;
width:100%;height:100%;
transform-style:preserve-3d;
}
body.home-body .home-carousel-slide{
position:absolute;
left:50%;top:50%;
width:70%;
aspect-ratio:16 / 9;
margin:0;
opacity:0;
pointer-events:none;
z-index:1;
transform-origin:center center;
transform:translate3d(-50%,-50%,0) scale(.54) rotateY(0deg);
filter:brightness(.42) saturate(.78) blur(.25px);
transition:
transform .85s cubic-bezier(.22,.75,.2,1),
opacity .65s ease,
filter .65s ease,
z-index 0s linear .35s;
box-shadow:0 18px 45px rgba(0,0,0,.45);
will-change:transform,opacity,filter;
}
body.home-body .home-carousel-slide::before{
content:"";
position:absolute;
inset:0;
z-index:3;
pointer-events:none;
background:linear-gradient(105deg,transparent 25%,rgba(255,255,255,.11) 48%,transparent 70%);
transform:translateX(-130%);
opacity:0;
}
body.home-body .home-carousel-slide::after{
content:"";
position:absolute;
inset:-1px;
z-index:2;
pointer-events:none;
border:1px solid rgba(62,226,255,.42);
border-radius:16px;
box-shadow:inset 0 0 25px rgba(45,225,255,.06),0 0 0 rgba(45,225,255,0);
transition:.5s ease;
}
body.home-body .home-carousel-slide img{
width:100%;height:100%;
display:block;
object-fit:cover;
object-position:center;
border-radius:16px;
backface-visibility:hidden;
-webkit-backface-visibility:hidden;
transform:translateZ(0);
image-rendering:auto;
user-select:none;
-webkit-user-drag:none;
}
/* posiciones 3D: centro + laterales + fondo */
body.home-body .home-carousel-slide.is-center{
opacity:1;
z-index:10;
pointer-events:auto;
transform:translate3d(-50%,-50%,45px) scale(1) rotateY(0deg);
filter:none;
box-shadow:
0 22px 55px rgba(0,0,0,.55),
0 0 25px rgba(45,225,255,.18),
0 0 70px rgba(127,74,255,.13);
}
body.home-body .home-carousel-slide.is-center::after{
border-color:rgba(57,236,255,.78);
box-shadow:inset 0 0 32px rgba(45,225,255,.09),0 0 25px rgba(45,225,255,.18),0 0 60px rgba(127,74,255,.13);
}
body.home-body .home-carousel-slide.is-center::before{
opacity:1;
animation:arqwarSlideShine 5.6s ease-in-out infinite;
}
body.home-body .home-carousel-slide.is-prev{
opacity:.72;
z-index:8;
pointer-events:auto;
transform:translate3d(-78%,-50%,0) scale(.73) rotateY(13deg);
filter:brightness(.62) saturate(.92);
}
body.home-body .home-carousel-slide.is-next{
opacity:.72;
z-index:8;
pointer-events:auto;
transform:translate3d(-22%,-50%,0) scale(.73) rotateY(-13deg);
filter:brightness(.62) saturate(.92);
}
body.home-body .home-carousel-slide.is-prev2{
opacity:.28;
z-index:5;
transform:translate3d(-104%,-50%,-60px) scale(.56) rotateY(22deg);
filter:brightness(.40) saturate(.72);
}
body.home-body .home-carousel-slide.is-next2{
opacity:.28;
z-index:5;
transform:translate3d(4%,-50%,-60px) scale(.56) rotateY(-22deg);
filter:brightness(.40) saturate(.72);
}
body.home-body .home-carousel-slide.is-hidden{
opacity:0;
z-index:1;
transform:translate3d(-50%,-50%,-140px) scale(.42);
filter:brightness(.25) blur(1px);
}
body.home-body .home-carousel-slide.is-prev:hover,
body.home-body .home-carousel-slide.is-next:hover{
filter:brightness(.82) saturate(1.08);
}
body.home-body .home-carousel-control{
position:absolute;
top:50%;
z-index:16;
transform:translateY(-50%);
width:48px;height:48px;
display:grid;place-items:center;
border:1px solid rgba(85,231,255,.70);
border-radius:50%;
background:linear-gradient(145deg,rgba(2,14,24,.84),rgba(9,10,28,.72));
backdrop-filter:blur(10px);
-webkit-backdrop-filter:blur(10px);
color:#eafcff;
font-size:1.45rem;
line-height:1;
cursor:pointer;
box-shadow:0 0 18px rgba(43,232,255,.22),inset 0 0 12px rgba(43,232,255,.06);
transition:.22s ease;
}
body.home-body .home-carousel-control:hover{
color:#fff;
border-color:#43efff;
box-shadow:0 0 28px rgba(43,232,255,.48),0 0 55px rgba(129,70,255,.20);
transform:translateY(-50%) scale(1.08);
}
body.home-body .home-carousel-prev{left:2.2%}
body.home-body .home-carousel-next{right:2.2%}
body.home-body .home-carousel-dots{
position:absolute;
left:50%;bottom:13px;
z-index:17;
transform:translateX(-50%);
display:flex;gap:7px;
padding:7px 10px;
border:1px solid rgba(84,222,255,.30);
border-radius:999px;
background:rgba(1,7,14,.68);
backdrop-filter:blur(10px);
-webkit-backdrop-filter:blur(10px);
box-shadow:0 0 18px rgba(0,0,0,.28),inset 0 0 10px rgba(45,225,255,.05);
}
body.home-body .home-carousel-dot{
width:9px;height:9px;padding:0;
border:1px solid rgba(255,255,255,.52);
border-radius:50%;
background:rgba(255,255,255,.28);
cursor:pointer;
transition:.24s ease;
}
body.home-body .home-carousel-dot.active{
width:27px;
border-color:#43efff;
background:#43efff;
box-shadow:0 0 13px #43efff,0 0 24px rgba(43,232,255,.30);
border-radius:999px;
}
body.home-body .home-carousel-status{
position:absolute;
right:2.2%;bottom:13px;
z-index:17;
padding:5px 9px;
border:1px solid rgba(84,222,255,.30);
border-radius:999px;
background:rgba(1,7,14,.68);
color:#a7dce9;
font:800 .62rem/1 Inter,ui-sans-serif,system-ui,sans-serif;
letter-spacing:.13em;
backdrop-filter:blur(10px);
-webkit-backdrop-filter:blur(10px);
}
@keyframes arqwarSlideShine{
0%,55%{transform:translateX(-130%);opacity:0}
62%{opacity:1}
78%{transform:translateX(130%);opacity:0}
100%{transform:translateX(130%);opacity:0}
}
@media(max-width:1000px){
body.home-body .home-carousel-viewport{aspect-ratio:16 / 8.8;}
body.home-body .home-carousel-slide{width:76%;}
body.home-body .home-carousel-slide.is-prev{transform:translate3d(-74%,-50%,0) scale(.70) rotateY(10deg)}
body.home-body .home-carousel-slide.is-next{transform:translate3d(-26%,-50%,0) scale(.70) rotateY(-10deg)}
body.home-body .home-carousel-slide.is-prev2{transform:translate3d(-99%,-50%,-50px) scale(.54) rotateY(17deg)}
body.home-body .home-carousel-slide.is-next2{transform:translate3d(-1%,-50%,-50px) scale(.54) rotateY(-17deg)}
}
@media(max-width:768px){
body.home-body .home-carousel{margin-bottom:15px;border-radius:13px}
body.home-body .home-carousel-viewport{aspect-ratio:16 / 9.8;min-height:220px}
body.home-body .home-carousel-slide{width:88%;}
body.home-body .home-carousel-slide img,
body.home-body .home-carousel-slide::after{border-radius:12px}
body.home-body .home-carousel-slide.is-prev{opacity:.48;transform:translate3d(-86%,-50%,0) scale(.66) rotateY(8deg)}
body.home-body .home-carousel-slide.is-next{opacity:.48;transform:translate3d(-14%,-50%,0) scale(.66) rotateY(-8deg)}
body.home-body .home-carousel-slide.is-prev2,
body.home-body .home-carousel-slide.is-next2{opacity:0;pointer-events:none}
body.home-body .home-carousel-control{width:37px;height:37px;font-size:1.05rem}
body.home-body .home-carousel-prev{left:7px}
body.home-body .home-carousel-next{right:7px}
body.home-body .home-carousel-dots{bottom:7px;gap:5px;padding:5px 7px}
body.home-body .home-carousel-dot{width:7px;height:7px}
body.home-body .home-carousel-dot.active{width:19px}
body.home-body .home-carousel-status{right:7px;bottom:7px;font-size:.48rem;padding:4px 6px}
}
@media(prefers-reduced-motion:reduce){
body.home-body .home-carousel-slide{transition:none}
body.home-body .home-carousel-slide.is-center::before{animation:none}
}

/* ===== ARQWAR / LOBBY 11 MODULOS: diseño tipo seleccion de mapas ===== */
body.home-body main.container { max-width:1480px!important; }
body.home-body .home-carousel { max-width:1120px; }
body.home-body .arq-lobby { color:#f4faff; position:relative; isolation:isolate; }
body.home-body .arq-lobby__head { display:flex;align-items:end;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:5px 0 17px;border-bottom:1px solid rgba(148,191,218,.22);margin-bottom:18px; }
body.home-body .arq-lobby__eyebrow { font-size:.67rem;letter-spacing:.19em;font-weight:850;color:#4fe5ff;margin-bottom:6px; }
body.home-body .arq-lobby__head h2 { color:#f4fbff;font-size:clamp(1.35rem,2.25vw,1.95rem);font-weight:950;letter-spacing:.015em;margin:0;line-height:1.2; }
body.home-body .arq-lobby__head h2 span {color:#46dfff;text-shadow:0 0 18px rgba(45,231,255,.28);}
body.home-body .arq-lobby__head p { color:#a4bacb;font-size:.81rem;margin:7px 0 0; }
body.home-body .arq-lobby__counter {display:inline-flex;align-items:center;gap:8px;align-self:center;color:#c6e7f8;border:1px solid rgba(62,210,234,.3);border-radius:6px;padding:8px 12px;background:rgba(5,25,35,.66);font-size:.65rem;font-weight:850;letter-spacing:.10em;white-space:nowrap;}
body.home-body .arq-lobby__counter-dot {width:7px;height:7px;flex:none;border-radius:50%;background:#39edc2;box-shadow:0 0 9px rgba(57,237,194,.75);}
body.home-body .arq-lobby__body {display:grid;grid-template-columns:142px minmax(0,1fr);gap:16px;align-items:stretch;}
body.home-body .arq-lobby__rail {display:flex;flex-direction:column;align-items:stretch;gap:9px;min-width:0;padding:11px 0 8px;border-right:1px solid rgba(202,226,239,.16);}
body.home-body .arq-lobby__rail-label {font-size:.61rem;letter-spacing:.18em;font-weight:850;color:#718ea3;margin:0 0 14px 9px;}
body.home-body .arq-lobby__rail-link {display:flex;align-items:center;gap:7px;position:relative;min-height:46px;padding:11px 11px;text-decoration:none;color:#9caebd;font-size:.9rem;font-weight:800;border-radius:5px 0 0 5px;border-left:3px solid transparent;transition:background .2s,color .2s,border-color .2s;}
body.home-body .arq-lobby__rail-link span {color:#617e92;font-family:ui-monospace,monospace;font-size:.56rem;}
body.home-body .arq-lobby__rail-link:hover,body.home-body .arq-lobby__rail-link:focus-visible {color:white;background:rgba(43,195,229,.08);border-left-color:#45ddf5;outline:0;}
body.home-body .arq-lobby__rail-link.is-active {color:#e6fbff;border-left-color:#34e8ff;background:linear-gradient(90deg,rgba(45,221,255,.16),transparent);}
body.home-body .arq-lobby__rail-foot {margin-top:auto;display:flex;flex-wrap:wrap;align-items:center;gap:5px;padding:13px 7px;border-top:1px solid rgba(204,223,232,.16);font-size:.55rem;letter-spacing:.055em;color:#69899f;}
body.home-body .arq-lobby__rail-foot b {color:#8ebaca;}
body.home-body .arq-lobby__rail-dot {background:#25ebb8;width:5px;height:5px;border-radius:50%;box-shadow:0 0 7px #25ebb8;}
body.home-body .arq-lobby__grid {display:grid;grid-template-columns:repeat(4,minmax(0,1fr));grid-auto-flow:row dense;grid-auto-rows:192px;gap:12px;min-width:0;}
body.home-body .arq-tile {--tile-neon:#43d8f5;position:relative;isolation:isolate;display:block;overflow:hidden;min-width:0;min-height:0;outline-offset:3px;background:#071a2a;border-radius:4px;border:1px solid rgba(131,174,195,.50);box-shadow:0 10px 24px rgba(0,0,0,.26);color:#fff;text-decoration:none!important;transition:transform .23s ease,border-color .23s ease,box-shadow .23s ease;}
body.home-body .arq-tile--nueva {--tile-neon:#ffd267;}
body.home-body .arq-tile--salas,body.home-body .arq-tile--ganadores,body.home-body .arq-tile--catalogo {--tile-neon:#49d6f8;}
body.home-body .arq-tile--equipos,body.home-body .arq-tile--master,body.home-body .arq-tile--cuenta {--tile-neon:#ae83ff;}
body.home-body .arq-tile--general,body.home-body .arq-tile--individual {--tile-neon:#ffcc6a;}
body.home-body .arq-tile__image {position:absolute;inset:0;z-index:-2;background-color:#12233a;background-size:cover;background-position:center 24%;background-repeat:no-repeat;transition:transform .48s ease,filter .48s ease;filter:saturate(.97) brightness(.90);}
body.home-body .arq-tile:before {content:"";position:absolute;inset:0;z-index:-1;pointer-events:none;background:linear-gradient(180deg,rgba(2,8,16,.02) 0%,rgba(4,10,20,.10) 35%,rgba(2,6,16,.72) 65%,rgba(2,6,13,.98) 100%);}
body.home-body .arq-tile:after {content:"";position:absolute;left:0;right:0;bottom:0;height:3px;background:linear-gradient(90deg,var(--tile-neon),transparent 78%);opacity:.72;box-shadow:0 0 11px var(--tile-neon);pointer-events:none;}
body.home-body .arq-tile__number {position:absolute;top:10px;right:10px;z-index:1;color:#e9faff;padding:5px 7px;background:rgba(4,9,17,.58);border:1px solid rgba(240,251,255,.20);border-radius:2px;backdrop-filter:blur(3px);font-size:.64rem;font-weight:900;font-family:ui-monospace,monospace;letter-spacing:.05em;}
body.home-body .arq-tile__bottom {display:flex;align-items:end;gap:10px;position:absolute;inset:auto 0 0;padding:13px 12px 15px;min-height:86px;color:#fff;}
body.home-body .arq-tile__icon {display:grid;place-items:center;flex:0 0 37px;height:37px;font-size:1.33rem;line-height:1;border-radius:8px;background:rgba(5,22,31,.72);border:1px solid var(--tile-neon);box-shadow:0 0 15px rgba(0,0,0,.32);}
body.home-body .arq-tile__details {display:block;min-width:0;flex:1;}
body.home-body .arq-tile__details strong {display:block;color:#fff;font-size:clamp(.83rem,1.02vw,1.07rem);font-weight:950;line-height:1.15;text-shadow:0 1px 7px #000;}
body.home-body .arq-tile__details small {display:block;margin-top:5px;color:#d0dfeb;font-size:clamp(.62rem,.75vw,.76rem);line-height:1.25;text-shadow:0 1px 5px #000;}
body.home-body .arq-tile__arrow {font-size:2rem;line-height:1;align-self:center;color:#ebfaff;text-shadow:0 0 9px rgba(61,210,235,.4);}
body.home-body .arq-tile--featured {grid-row:span 2;box-shadow:0 0 0 1px rgba(255,208,88,.65),0 15px 32px rgba(0,0,0,.38);}
body.home-body .arq-tile--featured .arq-tile__image {inset:0 0 25% 0;background-size:cover;background-position:47% center;}
body.home-body .arq-tile--featured:before {background:linear-gradient(180deg,rgba(2,6,14,.06),rgba(2,6,14,.20) 47%,rgba(3,9,18,.91) 75%,rgba(3,8,17,.99));}
body.home-body .arq-tile--featured .arq-tile__bottom {min-height:125px;padding:18px 16px 20px;}
body.home-body .arq-tile--featured .arq-tile__icon {flex-basis:48px;height:48px;font-size:1.7rem;}
body.home-body .arq-tile--featured .arq-tile__details strong {font-size:clamp(1.2rem,1.6vw,1.58rem);}
body.home-body .arq-tile--featured .arq-tile__details small {font-size:.82rem;line-height:1.4;}
body.home-body .arq-tile:hover,body.home-body .arq-tile:focus-visible {transform:translateY(-3px);border-color:var(--tile-neon);box-shadow:0 0 0 1px var(--tile-neon),0 16px 35px rgba(0,0,0,.42),0 0 20px rgba(32,201,255,.10);color:white;}
body.home-body .arq-tile:hover .arq-tile__image,body.home-body .arq-tile:focus-visible .arq-tile__image {transform:scale(1.055);filter:brightness(1.04) saturate(1.13);}
body.home-body .arq-tile:focus-visible {outline:2px solid #fff;}
body.home-body .arq-lobby__admin {margin-top:15px;padding-top:13px;border-top:1px solid rgba(204,222,238,.16);}
body.home-body .arq-lobby__admin a {display:flex;gap:10px;align-items:center;color:#d2edff;text-decoration:none;padding:10px 15px;border:1px solid rgba(102,203,234,.32);border-radius:5px;background:rgba(11,43,61,.5);font-size:.81rem;}
body.home-body .arq-lobby__admin a:hover {color:white;border-color:#39e7ff;}
body.home-body .arq-lobby__admin a span:first-of-type {flex:1;color:#87abc2;font-size:.74rem;}
body.home-body .arq-lobby__admin a span:last-of-type {font-size:1.5rem;line-height:1;}
@media (max-width:1240px) {
  body.home-body .arq-lobby__body {grid-template-columns:122px minmax(0,1fr);gap:10px;}
  body.home-body .arq-lobby__grid {grid-template-columns:repeat(3,minmax(0,1fr));grid-auto-rows:185px;gap:10px;}
  body.home-body .arq-tile__details strong {font-size:.96rem;}
  body.home-body .arq-tile__details small {font-size:.71rem;}
}
@media (max-width:860px) {
  body.home-body .arq-lobby__body {grid-template-columns:minmax(0,1fr);}
  body.home-body .arq-lobby__rail {flex-direction:row;flex-wrap:wrap;align-items:center;border-right:0;border-bottom:1px solid rgba(202,226,239,.16);padding:0 0 11px;gap:6px;}
  body.home-body .arq-lobby__rail-label {margin:0 10px 0 0;}
  body.home-body .arq-lobby__rail-link {padding:8px 10px;min-height:36px;border-left:0;border-bottom:2px solid transparent;border-radius:4px;}
  body.home-body .arq-lobby__rail-link.is-active,body.home-body .arq-lobby__rail-link:hover {border-left-color:transparent;border-bottom-color:#34e8ff;}
  body.home-body .arq-lobby__rail-foot {display:none;}
}
@media (max-width:690px) {
  body.home-body .arq-lobby__grid {grid-template-columns:repeat(2,minmax(0,1fr));grid-auto-rows:auto;gap:10px;}
  body.home-body .arq-tile {min-height:186px;}
  body.home-body .arq-tile--featured {grid-column:span 2;grid-row:span 1;min-height:255px;}
  body.home-body .arq-tile--featured .arq-tile__image {inset:0;background-size:cover;background-position:center 35%;}
  body.home-body .arq-tile--featured .arq-tile__bottom {min-height:105px;}
  body.home-body .arq-lobby__counter {font-size:.57rem;}
}
@media (max-width:440px) {
  body.home-body .arq-lobby__head {margin-bottom:12px;}
  body.home-body .arq-lobby__grid {grid-template-columns:minmax(0,1fr);grid-auto-rows:auto;}
  body.home-body .arq-tile {min-height:200px;}
  body.home-body .arq-lobby__rail {display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:3px;}
  body.home-body .arq-lobby__rail-label {display:none;}
  body.home-body .arq-tile--featured {grid-column:span 1;min-height:260px;}
  body.home-body .arq-tile__details strong {font-size:1.04rem;}
  body.home-body .arq-tile__details small {font-size:.76rem;}
  body.home-body .arq-lobby__rail-link {justify-content:center;white-space:nowrap;font-size:.68rem;padding:8px 3px;}
  body.home-body .arq-lobby__rail-link span {display:none;}
  body.home-body .arq-lobby__admin a {flex-wrap:wrap;}
}
@media (prefers-reduced-motion:reduce) {
  body.home-body .arq-tile,body.home-body .arq-tile__image {transition:none;}
}
body.home-body .arq-tile--participantes .arq-tile__image {background-image:url("assets/img/launcher/arqwar-participantes-oficina.webp?v=20261029-5");}
body.home-body .arq-tile--nueva .arq-tile__image {background-image:url("assets/img/launcher/arqwar-nueva-partida-oficina.webp?v=20261029-5");}
body.home-body .arq-tile--salas .arq-tile__image {background-image:url("data:image/webp;base64,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");}
body.home-body .arq-tile--equipos .arq-tile__image {background-image:url("data:image/webp;base64,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");}
body.home-body .arq-tile--individual .arq-tile__image {background-image:url("assets/img/launcher/arqwar-ranking-individual-oficina.webp?v=20261029-5");}
body.home-body .arq-tile--general .arq-tile__image {background-image:url("data:image/webp;base64,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");}
body.home-body .arq-tile--ganadores .arq-tile__image {background-image:url("data:image/webp;base64,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");}
body.home-body .arq-tile--master .arq-tile__image {background-image:url("data:image/webp;base64,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");}
body.home-body .arq-tile--arena .arq-tile__image {background-image:url("data:image/webp;base64,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");}
body.home-body .arq-tile--catalogo .arq-tile__image {background-image:url("data:image/webp;base64,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");}
body.home-body .arq-tile--cuenta .arq-tile__image {background-image:url("data:image/webp;base64,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");}
/* Correcciones 29/10/2026: fotos de referencia, sin iconos individuales en los accesos. */
body.home-body .arq-tile__bottom {gap:9px;}
body.home-body .arq-tile__details {padding-left:1px;}
body.home-body .arq-tile--nueva .arq-tile__image {background-position:center 22%;}
body.home-body .arq-tile--participantes .arq-tile__image {background-position:center 34%;}
body.home-body .arq-tile--individual .arq-tile__image {background-position:center 30%;}
body.home-body .arq-tile--featured .arq-tile__bottom {padding-left:19px;}
/* Barra lateral completa del inicio: accesos visibles y con desplazamiento si la pantalla es baja. */
@media (min-width:961px) {
  body.home-body .arqwar-global-sidebar.is-expanded .arqwar-global-nav {gap:2px;}
  body.home-body .arqwar-global-sidebar.is-expanded .arqwar-global-link {min-height:39px;padding-block:8px;}
}

/* ARQWAR-HOME-FIX-20261029: el inicio usa el menu completo y fotos profesionales. */
body.home-body .arq-tile__bottom .arq-tile__icon{display:none!important}
body.home-body .arq-tile--nueva .arq-tile__number,
body.home-body .arq-tile--participantes .arq-tile__number,
body.home-body .arq-tile--individual .arq-tile__number{display:none!important}
/* ===== FIN LOBBY 11 MODULOS ===== */
</style>
<link rel="stylesheet" href="assets/css/arqwar-identity.css?v=20261029-5">
  <meta name="arqwar-home-build" content="ARQWAR-HOME-FIX-20261029">
</head> 
<body class="home-body">
<?= arqwar_sidebar_html('inicio', true, (int)($latest['id']??0)) ?>
<main class="container py-5">
<section class="home-carousel" aria-label="Presentación de ARQWAR" tabindex="0" aria-roledescription="carrusel">
  <div class="home-carousel-viewport">
    <div class="home-carousel-track">
      <figure class="home-carousel-slide active"><img src="assets/img/carousel/slide-01-1920.webp" srcset="assets/img/carousel/slide-01-1280.webp 1280w, assets/img/carousel/slide-01-1920.webp 1920w, assets/img/carousel/slide-01.webp 3840w" sizes="(max-width: 768px) 92vw, (max-width: 1200px) 96vw, 1120px" alt="ARQWAR: aprende arquitectura TI jugando" width="3840" height="2160" loading="eager" fetchpriority="high" decoding="async" draggable="false"></figure>
      <figure class="home-carousel-slide"><img src="assets/img/carousel/slide-02-1920.webp" srcset="assets/img/carousel/slide-02-1280.webp 1280w, assets/img/carousel/slide-02-1920.webp 1920w, assets/img/carousel/slide-02.webp 3840w" sizes="(max-width: 768px) 92vw, (max-width: 1200px) 96vw, 1120px" alt="ARQWAR: explora áreas tecnológicas" width="3840" height="2160" loading="eager" decoding="async" draggable="false"></figure>
      <figure class="home-carousel-slide"><img src="assets/img/carousel/slide-03-1920.webp" srcset="assets/img/carousel/slide-03-1280.webp 1280w, assets/img/carousel/slide-03-1920.webp 1920w, assets/img/carousel/slide-03.webp 3840w" sizes="(max-width: 768px) 92vw, (max-width: 1200px) 96vw, 1120px" alt="ARQWAR: casuísticas reales y retos estratégicos" width="3840" height="2160" loading="eager" decoding="async" draggable="false"></figure>
      <figure class="home-carousel-slide"><img src="assets/img/carousel/slide-04-1920.webp" srcset="assets/img/carousel/slide-04-1280.webp 1280w, assets/img/carousel/slide-04-1920.webp 1920w, assets/img/carousel/slide-04.webp 3840w" sizes="(max-width: 768px) 92vw, (max-width: 1200px) 96vw, 1120px" alt="ARQWAR: forma tu equipo" width="3840" height="2160" loading="eager" decoding="async" draggable="false"></figure>
      <figure class="home-carousel-slide"><img src="assets/img/carousel/slide-05-1920.webp" srcset="assets/img/carousel/slide-05-1280.webp 1280w, assets/img/carousel/slide-05-1920.webp 1920w, assets/img/carousel/slide-05.webp 3840w" sizes="(max-width: 768px) 92vw, (max-width: 1200px) 96vw, 1120px" alt="ARQWAR: sube en el ranking" width="3840" height="2160" loading="eager" decoding="async" draggable="false"></figure>
      <figure class="home-carousel-slide"><img src="assets/img/carousel/slide-06-1920.webp" srcset="assets/img/carousel/slide-06-1280.webp 1280w, assets/img/carousel/slide-06-1920.webp 1920w, assets/img/carousel/slide-06.webp 3840w" sizes="(max-width: 768px) 92vw, (max-width: 1200px) 96vw, 1120px" alt="ARQWAR: aprende, practica y conviértete en experto" width="3840" height="2160" loading="eager" decoding="async" draggable="false"></figure>
    </div>
    <button type="button" class="home-carousel-control home-carousel-prev" aria-label="Imagen anterior">&#10094;</button>
    <button type="button" class="home-carousel-control home-carousel-next" aria-label="Imagen siguiente">&#10095;</button>
    <div class="home-carousel-dots" role="tablist" aria-label="Seleccionar diapositiva">
      <button type="button" class="home-carousel-dot active" aria-label="Diapositiva 1" aria-selected="true"></button>
      <button type="button" class="home-carousel-dot" aria-label="Diapositiva 2" aria-selected="false"></button>
      <button type="button" class="home-carousel-dot" aria-label="Diapositiva 3" aria-selected="false"></button>
      <button type="button" class="home-carousel-dot" aria-label="Diapositiva 4" aria-selected="false"></button>
      <button type="button" class="home-carousel-dot" aria-label="Diapositiva 5" aria-selected="false"></button>
      <button type="button" class="home-carousel-dot" aria-label="Diapositiva 6" aria-selected="false"></button>
    </div>
    <div class="home-carousel-status" aria-live="polite">ARQWAR · 01 / 06</div>
  </div>
</section>
<div class="hero-card p-4 p-lg-5"><div class="d-flex justify-content-between align-items-start flex-wrap gap-3"><div><div class="brand display-5">ARQWAR <span>v1.6</span></div><p class="lead mt-3 mb-1">Architecture Strategy Game · juego híbrido físico + digital</p><p class="text-secondary mb-0">Casuísticas por especialidad, hasta 8 equipos, cartas contextuales, incidentes explicados, ranking y Arena TV.</p></div><?php if($user): ?><div class="text-end"><div class="eyebrow">SESIÓN ACTIVA</div><b><?=e($user['display_name'])?></b><div><span class="badge <?=($user['role']??'operator')==='admin'?'text-bg-danger':'text-bg-info'?>"><?=($user['role']??'operator')==='admin'?'Administrador':'General'?></span></div><div><a class="small" href="logout.php">Cerrar sesión</a></div></div><?php endif; ?></div> 
<?php if(!$dbOk): ?><div class="alert alert-warning mt-4">La base de datos todavía no está instalada o MySQL no está disponible. Ejecuta el instalador antes de continuar.</div><?php endif; ?> 
<?php if($user): ?> 
<div class="home-stat-grid mt-4"><div><span>PARTICIPANTES</span><b><?=$stats['participants']?></b></div><div><span>EQUIPOS</span><b><?=$stats['teams']?></b></div><div><span>CASUÍSTICAS</span><b><?=$stats['scenarios']?></b></div><div><span>CARTAS</span><b><?=$stats['cards']?></b></div><div><span>PARTIDAS FINALIZADAS</span><b><?=$stats['games']?></b></div></div> 
<!-- INICIO: redistribución de los 11 módulos (solo portada) -->
<section class="arq-lobby mt-4" aria-labelledby="arq-lobby-title">
  <div class="arq-lobby__head">
    <div>
      <div class="arq-lobby__eyebrow">◈ CENTRO DE OPERACIONES / MENÚ PRINCIPAL</div>
      <h2 id="arq-lobby-title">ELIGE TU MISIÓN <span>ARQWAR</span></h2>
      <p>Selecciona una modalidad y accede a los módulos del juego.</p>
    </div>
    <span class="arq-lobby__counter"><span class="arq-lobby__counter-dot"></span> 11 MÓDULOS DISPONIBLES</span>
  </div>
  <div class="arq-lobby__body">
    <div class="arq-lobby__grid" aria-label="Accesos a los módulos">
      <a id="arq-nueva" class="arq-tile arq-tile--nueva arq-tile--featured" href="game-master.php?new=1" aria-label="Nueva partida: configurar un juego">
        <span class="arq-tile__image" aria-hidden="true"></span>
        <span class="arq-tile__number" aria-hidden="true">01</span>
        <span class="arq-tile__bottom">
          <span class="arq-tile__details"><strong>Nueva partida</strong><small>Área · casuística · equipos · juego</small></span>
          <span class="arq-tile__arrow" aria-hidden="true">›</span>
        </span>
      </a>
      <a id="arq-participantes" class="arq-tile arq-tile--participantes" href="<?= is_admin() ? 'participants.php' : 'mi-perfil.php' ?>" aria-label="<?= is_admin() ? 'Administrar participantes' : 'Abrir mi perfil' ?>">
        <span class="arq-tile__image" aria-hidden="true"></span>
        <span class="arq-tile__number" aria-hidden="true">02</span>
        <span class="arq-tile__bottom">
          <span class="arq-tile__details"><strong><?= is_admin() ? 'Participantes' : 'Mi perfil' ?></strong><small><?= is_admin() ? 'Registro de jugadores' : 'Avatar, niveles y experiencia' ?></small></span>
          <span class="arq-tile__arrow" aria-hidden="true">›</span>
        </span>
      </a>
      <a id="arq-salas" class="arq-tile arq-tile--salas" href="rooms.php" aria-label="Explorar salas ARQWAR">
        <span class="arq-tile__image" aria-hidden="true"></span>
        <span class="arq-tile__number" aria-hidden="true">03</span>
        <span class="arq-tile__bottom">
          <span class="arq-tile__details"><strong>Salas ARQWAR</strong><small>Entra y juega en tiempo real</small></span>
          <span class="arq-tile__arrow" aria-hidden="true">›</span>
        </span>
      </a>
      <a id="arq-equipos" class="arq-tile arq-tile--equipos" href="teams.php" aria-label="Gestionar equipos">
        <span class="arq-tile__image" aria-hidden="true"></span>
        <span class="arq-tile__number" aria-hidden="true">04</span>
        <span class="arq-tile__bottom">
          <span class="arq-tile__details"><strong>Equipos</strong><small>Integrantes y preparación</small></span>
          <span class="arq-tile__arrow" aria-hidden="true">›</span>
        </span>
      </a>
      <a id="arq-individual" class="arq-tile arq-tile--individual" href="participant-ranking.php" aria-label="Consultar ranking individual">
        <span class="arq-tile__image" aria-hidden="true"></span>
        <span class="arq-tile__number" aria-hidden="true">05</span>
        <span class="arq-tile__bottom">
          <span class="arq-tile__details"><strong>Ranking individual</strong><small>XP y niveles competitivos</small></span>
          <span class="arq-tile__arrow" aria-hidden="true">›</span>
        </span>
      </a>
      <a id="arq-general" class="arq-tile arq-tile--general" href="ranking.php" aria-label="Consultar ranking general">
        <span class="arq-tile__image" aria-hidden="true"></span>
        <span class="arq-tile__number" aria-hidden="true">06</span>
        <span class="arq-tile__bottom">
          <span class="arq-tile__details"><strong>Ranking general</strong><small>Posiciones acumuladas de equipos</small></span>
          <span class="arq-tile__arrow" aria-hidden="true">›</span>
        </span>
      </a>
      <a id="arq-ganadores" class="arq-tile arq-tile--ganadores" href="winners.php" aria-label="Consultar ganadores por partida">
        <span class="arq-tile__image" aria-hidden="true"></span>
        <span class="arq-tile__number" aria-hidden="true">07</span>
        <span class="arq-tile__bottom">
          <span class="arq-tile__details"><strong>Ganadores por partida</strong><small>Historial de campeones</small></span>
          <span class="arq-tile__arrow" aria-hidden="true">›</span>
        </span>
      </a>
      <a id="arq-master" class="arq-tile arq-tile--master" href="game-master.php" aria-label="Acceder a Game Master">
        <span class="arq-tile__image" aria-hidden="true"></span>
        <span class="arq-tile__number" aria-hidden="true">08</span>
        <span class="arq-tile__bottom">
          <span class="arq-tile__details"><strong>Game Master</strong><small>Retomar la última partida</small></span>
          <span class="arq-tile__arrow" aria-hidden="true">›</span>
        </span>
      </a>
      <a id="arq-arena" class="arq-tile arq-tile--arena" href="arena.php<?= $latest?'?game_id='.(int)$latest['id']:'' ?>" aria-label="Abrir Arena TV en otra pestaña" target="_blank" rel="noopener noreferrer">
        <span class="arq-tile__image" aria-hidden="true"></span>
        <span class="arq-tile__number" aria-hidden="true">09</span>
        <span class="arq-tile__bottom">
          <span class="arq-tile__details"><strong>Arena TV</strong><small>Pantalla para el televisor</small></span>
          <span class="arq-tile__arrow" aria-hidden="true">›</span>
        </span>
      </a>
      <a id="arq-catalogo" class="arq-tile arq-tile--catalogo" href="cards.php" aria-label="Consultar catálogo">
        <span class="arq-tile__image" aria-hidden="true"></span>
        <span class="arq-tile__number" aria-hidden="true">10</span>
        <span class="arq-tile__bottom">
          <span class="arq-tile__details"><strong>Catálogo</strong><small>Servicios, herramientas y cartas</small></span>
          <span class="arq-tile__arrow" aria-hidden="true">›</span>
        </span>
      </a>
      <a id="arq-cuenta" class="arq-tile arq-tile--cuenta" href="account.php" aria-label="Configurar mi cuenta">
        <span class="arq-tile__image" aria-hidden="true"></span>
        <span class="arq-tile__number" aria-hidden="true">11</span>
        <span class="arq-tile__bottom">
          <span class="arq-tile__details"><strong>Mi cuenta</strong><small>Contraseña y sesión</small></span>
          <span class="arq-tile__arrow" aria-hidden="true">›</span>
        </span>
      </a>
    </div>
  </div>
  <?php if(is_admin()): ?>
  <div class="arq-lobby__admin"><a href="admin.php">🛠️ <strong>Administración CRUD</strong> <span>Participantes, equipos, batallas, casuísticas, cartas, incidentes y usuarios</span> <span aria-hidden="true">›</span></a></div>
  <?php endif; ?>
</section>
<!-- FIN: redistribución de los 11 módulos -->
<?php else: ?><div class="d-flex flex-wrap gap-3 mt-4"><a class="btn btn-warning btn-lg" href="login.php">Iniciar sesión</a><a class="btn btn-outline-info btn-lg" href="register.php">Crear cuenta</a><a class="btn btn-outline-light btn-lg" href="arena.php<?= $latest?'?game_id='.(int)$latest['id']:'' ?>">Arena TV</a><a class="btn btn-outline-info btn-lg" href="cards.php">Catálogo</a><?php if(!$dbOk):?><a class="btn btn-outline-secondary btn-lg" href="setup.php">Instalar BD</a><?php endif;?></div><?php endif; ?> 
<div class="row g-3 mt-5"><div class="col-md-3"><div class="mini-card"><b>1. Área</b><span>Elige Desarrollo, Data, DevOps, MLOps, FinOps, RPA, Cybersecurity, Cloud, IA o SRE.</span></div></div><div class="col-md-3"><div class="mini-card"><b>2. Casuística</b><span>Selecciona una problemática realista dentro del área.</span></div></div><div class="col-md-3"><div class="mini-card"><b>3. Equipos</b><span>Enfrenta de 2 a 8 equipos. Sus paneles no cambian de lugar; cambia su puesto.</span></div></div><div class="col-md-3"><div class="mini-card"><b>4. Decisiones</b><span>Las cartas pueden sumar, valer cero o restar según contexto, costo y problema.</span></div></div></div> 

<script>
(function(){
  const root=document.querySelector('.home-carousel');
  if(!root) return;
  const slides=[...root.querySelectorAll('.home-carousel-slide')];
  const dots=[...root.querySelectorAll('.home-carousel-dot')];
  const status=root.querySelector('.home-carousel-status');
  const prev=root.querySelector('.home-carousel-prev');
  const next=root.querySelector('.home-carousel-next');
  let index=0;
  let timer=null;
  let touchStartX=0;
  let touchStartY=0;
  let touchMoved=false;

  const preload=(i)=>{
    const img=slides[(i+slides.length)%slides.length]?.querySelector('img');
    if(!img) return;
    const sources=(img.getAttribute('srcset')||'').split(',').map(x=>x.trim().split(' ')[0]).filter(Boolean);
    const source=sources[sources.length-1] || img.src;
    if(source){
      const warm=new Image();
      warm.decoding='async';
      warm.src=source;
    }
  };

  const paint=()=>{
    slides.forEach((slide,i)=>{
      slide.classList.remove('is-center','is-prev','is-next','is-prev2','is-next2','is-hidden','active');
      const delta=(i-index+slides.length)%slides.length;
      if(delta===0) slide.classList.add('is-center','active');
      else if(delta===1) slide.classList.add('is-next');
      else if(delta===slides.length-1) slide.classList.add('is-prev');
      else if(delta===2) slide.classList.add('is-next2');
      else if(delta===slides.length-2) slide.classList.add('is-prev2');
      else slide.classList.add('is-hidden');
      slide.setAttribute('aria-hidden',i===index?'false':'true');
    });

    dots.forEach((dot,i)=>{
      const active=i===index;
      dot.classList.toggle('active',active);
      dot.setAttribute('aria-selected',active?'true':'false');
      dot.setAttribute('aria-current',active?'true':'false');
    });

    if(status) status.textContent='ARQWAR · '+String(index+1).padStart(2,'0')+' / '+String(slides.length).padStart(2,'0');
    preload(index+1);
    preload(index-1);
  };

  const setSlide=(nextIndex,manual=false)=>{
    index=(nextIndex+slides.length)%slides.length;
    paint();
    if(manual) restart();
  };

  const stop=()=>{
    if(timer){clearInterval(timer);timer=null;}
  };
  const start=()=>{
    stop();
    timer=setInterval(()=>setSlide(index+1),6000);
  };
  const restart=()=>start();

  prev.addEventListener('click',()=>setSlide(index-1,true));
  next.addEventListener('click',()=>setSlide(index+1,true));
  dots.forEach((dot,i)=>dot.addEventListener('click',()=>setSlide(i,true)));

  slides.forEach((slide,i)=>slide.addEventListener('click',()=>{
    if(i===index) return;
    const delta=(i-index+slides.length)%slides.length;
    if(delta===1) setSlide(index+1,true);
    else if(delta===slides.length-1) setSlide(index-1,true);
    else if(delta===2) setSlide(index+2,true);
    else if(delta===slides.length-2) setSlide(index-2,true);
  }));

  root.addEventListener('mouseenter',stop);
  root.addEventListener('mouseleave',start);
  root.addEventListener('focusin',stop);
  root.addEventListener('focusout',()=>{if(!root.contains(document.activeElement)) start();});

  root.addEventListener('keydown',(event)=>{
    if(event.key==='ArrowLeft'){event.preventDefault();setSlide(index-1,true);}
    if(event.key==='ArrowRight'){event.preventDefault();setSlide(index+1,true);}
    if(event.key==='Home'){event.preventDefault();setSlide(0,true);}
    if(event.key==='End'){event.preventDefault();setSlide(slides.length-1,true);}
    if(event.key===' '){event.preventDefault();timer?stop():start();}
  });

  root.addEventListener('touchstart',(event)=>{
    const touch=event.changedTouches[0];
    touchStartX=touch.clientX;
    touchStartY=touch.clientY;
    touchMoved=false;
    stop();
  },{passive:true});

  root.addEventListener('touchmove',(event)=>{
    const touch=event.changedTouches[0];
    if(Math.abs(touch.clientX-touchStartX)>10 || Math.abs(touch.clientY-touchStartY)>10) touchMoved=true;
  },{passive:true});

  root.addEventListener('touchend',(event)=>{
    const touch=event.changedTouches[0];
    const dx=touch.clientX-touchStartX;
    const dy=touch.clientY-touchStartY;
    if(touchMoved && Math.abs(dx)>50 && Math.abs(dx)>Math.abs(dy)){
      setSlide(index+(dx<0?1:-1),true);
    }else{
      start();
    }
  },{passive:true});

  document.addEventListener('visibilitychange',()=>document.hidden?stop():start());
  paint();
  preload(index+1);
  preload(index+2);
  start();
})();
</script>
</div></main>
<?= arqwar_mascot_html('home') ?>
<script src="assets/js/arqwar-mascot.js?v=20261029-1" defer></script>
</body></html>