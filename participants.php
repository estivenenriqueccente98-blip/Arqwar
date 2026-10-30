<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__.'/includes/arqwar-identity.php';
require_once __DIR__ . "/includes/avatars.php";
admin_required_html();

$msg = '';
$error = '';
$currentParticipant = current_participant(true);
if (!$currentParticipant) {
    $error = 'Tu cuenta no tiene un participante ARQWAR vinculado. Inicia sesión nuevamente para sincronizar tu perfil.';
}

$avatarCatalog = arqwar_avatar_catalog();
$avatarOptions = array_keys($avatarCatalog);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!hash_equals(csrf_token(), $_POST['csrf'] ?? '')) {
            throw new RuntimeException('Solicitud inválida.');
        }

        $a = $_POST['action'] ?? '';

        if ($a === 'update_mine') {
            $participant = current_participant(true);
            if (!$participant) {
                throw new RuntimeException('No se encontró el participante vinculado a tu cuenta.');
            }

            $alias = trim((string)($_POST['alias'] ?? ''));
            $specialty = trim((string)($_POST['specialty'] ?? ''));
            $emoji = arqwar_avatar_key((string)($_POST['avatar_emoji'] ?? 'cyber_skull'));

            if (mb_strlen($alias) < 2 || mb_strlen($alias) > 80) {
                throw new RuntimeException('El alias debe tener entre 2 y 80 caracteres.');
            }

            $duplicate = row(
                'SELECT id FROM participants WHERE alias=? AND id<>? LIMIT 1',
                [$alias, (int)$participant['id']]
            );
            if ($duplicate) {
                throw new RuntimeException('Ese alias ya pertenece a otro participante. Elige otro.');
            }

            // Siempre conserva la misma fila vinculada a la cuenta.
            // El nombre se toma de la cuenta y no del formulario para evitar duplicados.
            $user = current_user();
            $displayName = trim((string)($user['display_name'] ?? $participant['full_name'] ?? ''));

            exec_sql(
                'UPDATE participants SET alias=?,full_name=?,specialty=?,avatar_emoji=? WHERE id=? AND user_id=?',
                [$alias, $displayName ?: null, $specialty ?: null, $emoji, (int)$participant['id'], (int)$user['id']]
            );

            header('Location: participants.php?updated_profile=1');
            exit;
        }

        if ($a === 'toggle') {
            if (!is_admin()) {
                throw new RuntimeException('Solo el administrador puede cambiar el estado de participantes.');
            }

            exec_sql(
                'UPDATE participants SET is_active=? WHERE id=?',
                [(int)($_POST['active'] ?? 0), (int)$_POST['id']]
            );

            header('Location: participants.php?updated=1');
            exit;
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

if (isset($_GET['updated_profile'])) $msg = 'Tu participante fue actualizado correctamente. No se creó ningún participante nuevo.';
if (isset($_GET['updated'])) $msg = 'Participante actualizado.';

$people = [];
try {
    $people = participant_ranking_rows();
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ARQWAR · Participantes</title>
<link href="assets/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/app.css?v=20261014-avatar">


<link rel="stylesheet" href="assets/css/arqwar-identity.css?v=20261028-2">
<link rel="stylesheet" href="assets/css/avatar-3d.css?v=3">
</head>
<body class="gm-body arqwar-glow-bg">

<nav class="navbar navbar-dark border-bottom border-secondary-subtle">
  <div class="container-fluid">
    <?= arqwar_brand_html('PARTICIPANTES') ?>
    <div class="d-flex gap-2 flex-wrap">
      <a class="btn btn-sm btn-outline-light" href="index.php">Inicio</a>
      <a class="btn btn-sm btn-outline-warning" href="teams.php">Equipos</a>
      <a class="btn btn-sm btn-outline-success" href="participant-ranking.php">Ranking individual</a>
      <?php if (is_admin()): ?>
        <a class="btn btn-sm btn-danger" href="admin.php?section=participants">Administrar CRUD</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<main class="container-fluid py-4 px-lg-4">
  <div class="row g-4">

    <div class="col-xl-4">
      <section class="panel p-4 arq-card depth-card">
        <div class="eyebrow">PASO PREVIO A LOS EQUIPOS</div>
        <h1 class="h3">Administración de participantes</h1>
        <p class="text-secondary">
          El participante conserva su alias, historial y puntos aunque juegue distintas partidas.
          Después lo asignas a uno o varios equipos.
        </p>

        <?php if ($error): ?>
          <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($msg): ?>
          <div class="alert alert-success"><?= e($msg) ?></div>
        <?php endif; ?>

        <?php
          $profileAlias = $currentParticipant['alias'] ?? '';
          $profileName = $currentParticipant['full_name'] ?? (current_user()['display_name'] ?? '');
          $profileSpecialty = $currentParticipant['specialty'] ?? '';
          $profileAvatar = arqwar_avatar_key($currentParticipant['avatar_emoji'] ?? 'cyber_skull');
          if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_mine') {
              $profileAlias = (string)($_POST['alias'] ?? $profileAlias);
              $profileSpecialty = (string)($_POST['specialty'] ?? $profileSpecialty);
              $profileAvatar = arqwar_avatar_key((string)($_POST['avatar_emoji'] ?? $profileAvatar));
          }
        ?>

        <div class="login-hint mb-3">
          <b>Perfil vinculado a tu cuenta</b>
          <span>Esta pantalla actualiza tu participante existente. No se crea otro registro.</span>
        </div>

        <form method="post" id="participantForm">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="update_mine">

          <label class="form-label">Alias competitivo</label>
          <input class="form-control form-control-lg mb-3" name="alias" value="<?= e($profileAlias) ?>" required maxlength="80" placeholder="Ej.: Luis">

          <label class="form-label">Nombre</label>
          <input class="form-control mb-3" value="<?= e($profileName) ?>" readonly>
          <div class="small text-secondary mb-3">El nombre se obtiene automáticamente de tu cuenta de usuario.</div>

          <label class="form-label">Especialidad</label>
          <input class="form-control mb-3" name="specialty" value="<?= e($profileSpecialty) ?>" placeholder="Ej.: Data Engineer">

          <label class="form-label">Avatar</label>
          <input type="hidden" name="avatar_emoji" id="avatarEmojiInput" value="<?= e($profileAvatar) ?>">

          <div class="avatar-picker mb-2" id="avatarPicker">
            <?php foreach ($avatarOptions as $i => $avatarKey): ?>
              <?php $meta = $avatarCatalog[$avatarKey]; ?>
              <button
                type="button"
                class="avatar-option <?= $avatarKey === $profileAvatar ? 'active' : '' ?>"
                data-avatar="<?= e($avatarKey) ?>"
                data-avatar-name="<?= e($meta['name']) ?>"
                style="--avatar-neon:<?= arqwar_avatar_accent($avatarKey) ?>"
                aria-label="Seleccionar <?= e($meta['name']) ?>"
                aria-pressed="<?= $avatarKey === $profileAvatar ? 'true' : 'false' ?>"
                title="<?= e($meta['name']) ?>"
              >
                <span class="avatar-stage"><?= arqwar_avatar_image_html($avatarKey) ?></span>
                <span class="avatar-name"><?= e($meta['name']) ?></span>
              </button>
            <?php endforeach; ?>
          </div>

          <button type="button" class="avatar-3d-preview-button" id="avatar3dPreviewButton"
                  aria-haspopup="dialog">⟳ Explorar avatar en 3D</button>

          <div class="small text-secondary mb-3">
            Cambia tu avatar, alias o especialidad. Tu historial, puntos, equipos y partidas siguen vinculados al mismo participante.
          </div>

          <button class="btn btn-warning btn-lg w-100" <?= $currentParticipant ? '' : 'disabled' ?>>Guardar mis datos</button>
        </form>
      </section>
    </div>

    <div class="col-xl-8">
      <div class="d-flex justify-content-between align-items-end mb-3 flex-wrap gap-3">
        <div>
          <div class="eyebrow">SOLO ADMINISTRADORES · REGISTRO GLOBAL</div>
          <h2 class="h3 mb-0">Participantes</h2>
        </div>
        <a class="btn btn-outline-success" href="participant-ranking.php">Ver ranking</a>
      </div>

      <div class="participant-grid">
        <?php foreach ($people as $p): ?>
          <?php
            $avatarKey = arqwar_avatar_key($p['avatar_emoji'] ?: 'cyber_skull');
          ?>
          <article class="participant-card arq-card depth-card">
            <div class="participant-avatar avatar-badge" title="<?= e(arqwar_avatar_meta($avatarKey)['name']) ?>"
                 style="--avatar-neon:<?= arqwar_avatar_accent($avatarKey) ?>">
              <span class="avatar-art"><?= arqwar_avatar_image_html($avatarKey, true) ?></span>
            </div>

            <div class="flex-grow-1">
              <div class="d-flex justify-content-between gap-2 flex-wrap">
                <div>
                  <h3><?= e($p['alias']) ?></h3>
                  <p><?= e($p['specialty'] ?: 'Sin especialidad') ?></p>
                </div>
                <div class="participant-level">
                  <img src="<?=e($p['level']['image'])?>" alt="" width="38" height="38" style="object-fit:cover;border-radius:9px">
                  <b><?= e($p['level']['name']) ?></b>
                </div>
              </div>

              <div class="participant-stats">
                <span>Puntos <b><?= $p['total_points'] ?></b></span>
                <span>Respuestas <b><?= $p['answers'] ?></b></span>
                <span>Pronunciación <b><?= $p['pronunciation_hits'] ?></b></span>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</main>

<dialog class="avatar-3d-dialog" id="avatar3dDialog" aria-labelledby="avatar3dDialogTitle">
  <div class="avatar-3d-dialog-header">
    <div>
      <div class="avatar-3d-dialog-kicker">ARQWAR // AVATAR 3D</div>
      <h2 id="avatar3dDialogTitle">Vista tridimensional</h2>
    </div>
    <button type="button" class="avatar-3d-dialog-close" id="avatar3dClose" aria-label="Cerrar visor">×</button>
  </div>
  <iframe id="avatar3dViewer" title="Visor interactivo tridimensional del avatar seleccionado" loading="lazy"></iframe>
  <p class="avatar-3d-dialog-note">Arrastra para girar y usa la rueda para acercar. El visor utiliza un modelo GLB low-poly; las tarjetas muestran el diseño 3D detallado.</p>
</dialog>
<script src="assets/js/avatar-3d.js?v=3" defer></script>

</body>
</html>
