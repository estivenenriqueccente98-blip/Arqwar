<?php
/** ARQWAR · Perfil del jugador. Nunca muestra el listado global de participantes. */
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/arqwar-identity.php';
require_once __DIR__ . '/includes/avatars.php';
login_required_html();

$user = current_user();
$person = current_participant(true);
$error = '';
$notice = isset($_GET['guardado']) ? 'Tu perfil se guardó correctamente. Conservaste todos tus puntos e historial.' : '';
$avatars = arqwar_avatar_catalog();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!hash_equals(csrf_token(), (string)($_POST['csrf'] ?? ''))) {
            throw new RuntimeException('La sesión de seguridad expiró. Vuelve a intentarlo.');
        }
        if (($_POST['action'] ?? '') !== 'guardar_perfil') {
            throw new RuntimeException('Acción no permitida.');
        }
        if (!$person) {
            throw new RuntimeException('No se encontró el perfil asociado a tu cuenta.');
        }
        $alias = trim((string)($_POST['alias'] ?? ''));
        $displayName = trim((string)($_POST['display_name'] ?? ''));
        $specialty = trim((string)($_POST['specialty'] ?? ''));
        $avatarInput = (string)($_POST['avatar_emoji'] ?? '');
        if (!array_key_exists($avatarInput, $avatars)) {
            throw new RuntimeException('Selecciona un avatar del catálogo.');
        }
        if (mb_strlen($alias, 'UTF-8') < 2 || mb_strlen($alias, 'UTF-8') > 80) {
            throw new RuntimeException('Tu alias debe tener entre 2 y 80 caracteres.');
        }
        if (mb_strlen($displayName, 'UTF-8') < 2 || mb_strlen($displayName, 'UTF-8') > 120) {
            throw new RuntimeException('El nombre debe tener entre 2 y 120 caracteres.');
        }
        if (mb_strlen($specialty, 'UTF-8') > 120) {
            throw new RuntimeException('La especialidad no debe superar los 120 caracteres.');
        }
        if (row('SELECT id FROM participants WHERE alias=? AND id<>? LIMIT 1', [$alias, (int)$person['id']])) {
            throw new RuntimeException('Otro jugador ya utiliza ese alias.');
        }

        $db = db();
        $db->beginTransaction();
        try {
            // Sólo permite cambiar la propia identidad, sin tocar XP, partidas o permisos.
            exec_sql('UPDATE app_users SET display_name=? WHERE id=?', [$displayName, (int)$user['id']]);
            exec_sql(
                'UPDATE participants SET alias=?,full_name=?,specialty=?,avatar_emoji=? WHERE id=? AND user_id=?',
                [$alias, $displayName, $specialty ?: null, $avatarInput, (int)$person['id'], (int)$user['id']]
            );
            $db->commit();
        } catch (Throwable $ex) {
            if ($db->inTransaction()) $db->rollBack();
            throw $ex;
        }
        $_SESSION['user']['display_name'] = $displayName;
        header('Location: mi-perfil.php?guardado=1');
        exit;
    } catch (Throwable $ex) {
        $error = $ex instanceof RuntimeException ? $ex->getMessage() : 'No se pudieron guardar los cambios. Inténtalo nuevamente.';
        if (!($ex instanceof RuntimeException)) error_log('ARQWAR mi-perfil error: '.$ex->getMessage());
    }
}

$user = current_user();
$person = current_participant(false);
$alias = (string)($person['alias'] ?? $user['username'] ?? 'Jugador');
$name = (string)($user['display_name'] ?? $person['full_name'] ?? '');
$specialty = (string)($person['specialty'] ?? '');
$chosen = arqwar_avatar_key((string)($person['avatar_emoji'] ?? 'cyber_skull'));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error) {
    $alias = (string)($_POST['alias'] ?? $alias);
    $name = (string)($_POST['display_name'] ?? $name);
    $specialty = (string)($_POST['specialty'] ?? $specialty);
    $requested = (string)($_POST['avatar_emoji'] ?? '');
    if (isset($avatars[$requested])) $chosen = $requested;
}
$selectedMotionBase = 'assets/img/avatars-motion/' . $chosen;

$stats = ['total_points'=>0,'answers'=>0,'pronunciation_hits'=>0,'games_played'=>0];
if ($person) {
    $fromDb = row("SELECT
        COALESCE(SUM(points),0) AS total_points,
        COALESCE(SUM(CASE WHEN source_type='card' THEN 1 ELSE 0 END),0) AS answers,
        COALESCE(SUM(CASE WHEN source_type='pronunciation' AND points>0 THEN 1 ELSE 0 END),0) AS pronunciation_hits,
        COUNT(DISTINCT game_id) AS games_played
        FROM participant_points WHERE participant_id=?",[(int)$person['id']]);
    if ($fromDb) $stats = $fromDb;
}
$xp = max(0, (int)$stats['total_points']);
$level = participant_level($xp);
$rankList = participant_rank_catalog();
$selectedIndex = 0;
foreach ($rankList as $index => $rank) {
    if ($xp >= (int)$rank['min']) $selectedIndex = $index;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#031624">
  <title>ARQWAR · Mi perfil</title>
  <link rel="stylesheet" href="assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/app.css">
  <link rel="stylesheet" href="assets/css/arqwar-identity.css?v=20261029">
  <link rel="stylesheet" href="assets/css/mi-perfil.css?v=20261029poses">
</head>
<body class="gm-body arqwar-glow-bg profile-page">
  <nav class="navbar navbar-dark border-bottom border-secondary-subtle profile-navbar">
    <div class="container-fluid px-lg-4">
      <?= arqwar_brand_html('MI PERFIL') ?>
      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-sm btn-outline-info" href="index.php">Inicio</a>
        <a class="btn btn-sm btn-outline-info" href="participant-ranking.php">Ranking</a>
        <a class="btn btn-sm btn-outline-secondary" href="account.php">Seguridad de cuenta</a>
        <?php if (is_admin()): ?><a class="btn btn-sm btn-outline-warning" href="participants.php">Participantes</a><?php endif; ?>
      </div>
    </div>
  </nav>

  <main class="profile-layout container-fluid" id="perfil-principal">
    <header class="profile-header">
      <div>
        <div class="profile-eyebrow">◆ ARQWAR / IDENTIDAD DEL JUGADOR</div>
        <h1>MI <span>PERFIL</span></h1>
        <p>Personaliza tu identidad, elige tu personaje y sigue tu progreso en Arquitectura TI.</p>
      </div>
      <div class="profile-header-pill"><span class="profile-online"></span> PERFIL VINCULADO A TU CUENTA</div>
    </header>

    <?php if ($error): ?><div role="alert" class="profile-message profile-message--error"><?=e($error)?></div><?php endif; ?>
    <?php if ($notice): ?><div role="status" class="profile-message profile-message--success"><?=e($notice)?></div><?php endif; ?>
    <?php if (!$person): ?><div role="alert" class="profile-message profile-message--error">No se pudo vincular un participante a tu sesión. Contacta al administrador.</div><?php endif; ?>

    <div class="profile-main-grid">
      <section class="profile-form-panel" aria-labelledby="titulo-personalizar">
        <div class="profile-section-label">01 / PERSONALIZACIÓN</div>
        <h2 id="titulo-personalizar">Tu identidad ARQWAR</h2>
        <p class="profile-muted">Edita tus datos y elige tu avatar. Tus estadísticas no se perderán.</p>

        <form method="post" id="arqwarProfileForm">
          <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
          <input type="hidden" name="action" value="guardar_perfil">
          <input type="hidden" name="avatar_emoji" id="profileAvatarInput" value="<?=e($chosen)?>">
          <div class="profile-field">
            <label for="profileAlias">Alias competitivo</label>
            <input id="profileAlias" name="alias" value="<?=e($alias)?>" required minlength="2" maxlength="80" autocomplete="nickname">
          </div>
          <div class="profile-field">
            <label for="profileName">Nombre</label>
            <input id="profileName" name="display_name" value="<?=e($name)?>" required minlength="2" maxlength="120" autocomplete="name">
          </div>
          <div class="profile-field">
            <label for="profileSpecialty">Especialidad</label>
            <input id="profileSpecialty" name="specialty" value="<?=e($specialty)?>" maxlength="120" placeholder="Ej. Ciberseguridad, Cloud, Data…">
          </div>
          <div class="profile-pick-heading">
            <div><span class="profile-section-label">TU PERSONAJE</span><h3>Selecciona un avatar</h3></div>
            <span class="profile-catalog-count"><?=count($avatars)?> opciones</span>
          </div>
          <div class="profile-avatar-grid" id="profileAvatarPicker" role="group" aria-label="Avatares disponibles">
            <?php foreach ($avatars as $key => $meta): ?>
              <button type="button" class="profile-avatar-choice <?= $key === $chosen ? 'is-selected' : '' ?>"
                  data-avatar="<?=e($key)?>"
                  data-avatar-name="<?=e($meta['name'])?>"
                  data-avatar-portrait="assets/img/avatars-perfil/<?=e($key)?>.webp"
                  data-avatar-accent="<?=e(arqwar_avatar_accent($key))?>"
                  data-avatar-motion-base="<?=e('assets/img/avatars-motion/' . $key)?>"
                  aria-pressed="<?= $key === $chosen ? 'true' : 'false' ?>"
                  aria-label="Elegir <?=e($meta['name'])?>"
                  title="<?=e($meta['name'])?>">
                <span class="profile-choice-img"><img src="<?=e(arqwar_avatar_image_url($key))?>" alt="" loading="eager" decoding="async"></span>
                <span class="profile-choice-name"><?=e($meta['name'])?></span>
              </button>
            <?php endforeach; ?>
          </div>
          <button type="submit" class="profile-save-button" <?= $person ? '' : 'disabled' ?>>Guardar cambios <span aria-hidden="true">➜</span></button>
          <p class="profile-form-footnote">Tu experiencia, puntos, partidas y respuestas permanecen vinculados a tu cuenta.</p>
        </form>
      </section>

      <section class="profile-experience-panel" aria-label="Avatar y progreso del jugador">
        <div class="profile-experience-top">
          <div class="profile-section-label">02 / PERSONAJE ACTIVO</div>
          <span class="profile-status">● EN LÍNEA</span>
        </div>
        <div class="profile-display-grid">
          <div class="profile-character-scene" id="profileScene" data-motion="0" data-motion-base="<?=e($selectedMotionBase)?>" style="--character-accent:<?=e(arqwar_avatar_accent($chosen))?>">
            <div class="profile-orbit profile-orbit--one" aria-hidden="true"></div>
            <div class="profile-orbit profile-orbit--two" aria-hidden="true"></div>
            <div class="profile-scanline" aria-hidden="true"></div>
            <img id="profileCharacter" class="profile-character" src="<?=e($selectedMotionBase)?>/hero.webp" alt="Avatar animado: <?=e($avatars[$chosen]['name'])?>" role="button" tabindex="0" aria-label="Animar <?=e($avatars[$chosen]['name'])?>" width="320" height="440" loading="eager" decoding="async" draggable="false">
            <div class="profile-pose-label" id="profilePoseLabel" aria-live="off">PERSONAJE ACTIVO</div>
            <div class="profile-platform" aria-hidden="true"></div>
            <div class="profile-character-label" id="profileCharacterName"><?=e($avatars[$chosen]['name'])?></div>
            <button type="button" id="profileAnimate" class="profile-action-button" aria-label="Cambiar la pose del personaje seleccionado">✦ Siguiente movimiento</button>
          </div>

          <div class="profile-level-panel">
            <div class="profile-section-label">RANGO COMPETITIVO</div>
            <img src="<?=e($level['image'])?>" class="profile-current-rank" alt="Emblema del rango <?=e($level['name'])?>" width="160" height="160">
            <div class="profile-current-title">NIVEL ACTUAL</div>
            <h2><?=e($level['name'])?></h2>
            <p class="profile-username">@<?=e($alias)?></p>
            <div class="profile-xp-total"><strong><?=number_format($xp,0,',','.')?></strong><span>XP acumulada</span></div>
            <div class="profile-progress-head"><span>Progreso de nivel</span><strong><?=$level['progress_pct']?>%</strong></div>
            <div class="profile-progress" role="progressbar" aria-valuenow="<?=$level['progress_pct']?>" aria-valuemin="0" aria-valuemax="100" aria-label="Progreso hasta el siguiente rango"><span style="width:<?=$level['progress_pct']?>%"></span></div>
            <div class="profile-xp-caption">
              <?php if($level['next']!==null): ?>
                <span><?=number_format($xp,0,',','.')?> / <?=number_format($level['next'],0,',','.')?> XP</span>
                <strong><?=number_format($level['points_to_next'],0,',','.')?> XP para <?=e($level['next_name'])?></strong>
              <?php else: ?>
                <span>Rango máximo</span><strong>¡Gran Maestro alcanzado!</strong>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="profile-stats-grid" aria-label="Estadísticas personales">
          <div class="profile-stat"><span>PUNTOS</span><strong><?=number_format($xp,0,',','.')?></strong><small>XP total</small></div>
          <div class="profile-stat"><span>RESPUESTAS</span><strong><?=number_format((int)$stats['answers'],0,',','.')?></strong><small>Cartas respondidas</small></div>
          <div class="profile-stat"><span>PRONUNCIACIÓN</span><strong><?=number_format((int)$stats['pronunciation_hits'],0,',','.')?></strong><small>Aciertos registrados</small></div>
          <div class="profile-stat"><span>PARTIDAS</span><strong><?=number_format((int)$stats['games_played'],0,',','.')?></strong><small>Con participación</small></div>
        </div>
      </section>
    </div>

    <section class="profile-rank-roadmap" aria-labelledby="titulo-rangos">
      <div class="profile-roadmap-head">
        <div><div class="profile-section-label">03 / PROGRESIÓN COMPETITIVA</div><h2 id="titulo-rangos">Los seis rangos ARQWAR</h2></div>
        <div class="profile-muted">La experiencia acumulada determina tu rango</div>
      </div>
      <div class="profile-rank-grid">
        <?php foreach ($rankList as $index=>$rank): ?>
          <?php $status=$index < $selectedIndex ? 'is-unlocked' : ($index === $selectedIndex ? 'is-current' : 'is-locked'); ?>
          <div class="profile-rank-card <?=$status?>" <?= $index === $selectedIndex ? 'aria-current="step"' : '' ?>>
            <img src="assets/img/rangos/<?=e($rank['key'])?>.webp" alt="Emblema <?=e($rank['name'])?>" loading="eager" width="170" height="170">
            <span class="profile-rank-status"><?= $index === $selectedIndex ? 'TU RANGO' : ($index < $selectedIndex ? 'SUPERADO' : 'POR ALCANZAR') ?></span>
            <h3><?=e($rank['name'])?></h3>
            <small>Desde <?=number_format((int)$rank['min'],0,',','.')?> XP</small>
          </div>
        <?php endforeach; ?>
      </div>
      <p class="profile-rank-help">Los rangos se asignan con la XP total que ya registra ARQWAR. Los umbrales pueden ajustarse desde la función <code>participant_rank_catalog()</code>.</p>
    </section>
  </main>
  <script src="assets/js/mi-perfil.js?v=20261029poses" defer></script>
</body>
</html>
