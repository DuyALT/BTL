<?php
$errorBase=defined('BASE_URL')?BASE_URL:'/BTL/';
$errorCode=$errorCode??500;
$errorTitle=$errorTitle??'Đã có lỗi xảy ra';
$errorMessage=$errorMessage??'Hệ thống chưa thể hoàn tất yêu cầu. Vui lòng thử lại sau.';
$escapeError=static fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= $escapeError($errorCode.' — '.$errorTitle) ?> | EduScore</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="<?= $escapeError($errorBase) ?>assets/css/style.css" rel="stylesheet"><link href="<?= $escapeError($errorBase) ?>assets/css/eduscore.css" rel="stylesheet"></head><body>
<main class="error-page"><div class="error-card"><img src="<?= $escapeError($errorBase) ?>assets/images/logo.png" alt="Học viện An ninh nhân dân"><div class="page-eyebrow mt-3">EDUSCORE · QUẢN LÝ ĐIỂM HỌC TẬP</div><div class="error-code" aria-hidden="true"><?= $escapeError($errorCode) ?></div><h1><?= $escapeError($errorTitle) ?></h1><p><?= $escapeError($errorMessage) ?></p><a class="btn btn-primary" href="<?= $escapeError($errorBase) ?>index.php">Về trang chủ</a> <a class="btn btn-outline-secondary" href="<?= $escapeError($errorBase) ?>login.php">Đăng nhập</a></div></main>
</body></html>
