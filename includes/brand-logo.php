<?php if (is_file(__DIR__ . '/../assets/images/logo.png')): ?>
    <img class="brand-logo brand-logo-image" src="<?= BASE_URL ?>assets/images/logo.png" alt="Logo Học viện An ninh nhân dân" width="56" height="56">
<?php else: ?>
    <span class="brand-logo"><i class="bi bi-mortarboard-fill" aria-hidden="true"></i></span>
<?php endif; ?>
