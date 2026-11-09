<?php
require_once __DIR__ . '/includes/auth.php';
$u = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $cu = $_POST['mat_khau_cu'] ?? '';
    $moi = $_POST['mat_khau_moi'] ?? '';
    $lai = $_POST['nhap_lai'] ?? '';
    $st = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $st->execute([$u['id']]);
    if (!password_verify($cu, $st->fetchColumn())) {
        flash('danger', 'Mật khẩu hiện tại không đúng.');
    } elseif (strlen($moi) < 6) {
        flash('danger', 'Mật khẩu mới tối thiểu 6 ký tự.');
    } elseif ($moi !== $lai) {
        flash('danger', 'Mật khẩu nhập lại không khớp.');
    } else {
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($moi, PASSWORD_DEFAULT), $u['id']]);
        flash('success', 'Đổi mật khẩu thành công.');
    }
    redirect('doi_mat_khau.php');
}
$title = 'Đổi mật khẩu';
require __DIR__ . '/includes/header.php';
?>
<div class="card p-4" style="max-width:480px">
    <h5 class="mb-3">Đổi mật khẩu</h5>
    <form method="post" class="js-validate" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3"><label class="form-label">Mật khẩu hiện tại</label>
            <input type="password" name="mat_khau_cu" class="form-control" required data-label="Mật khẩu hiện tại"></div>
        <div class="mb-3"><label class="form-label">Mật khẩu mới</label>
            <input type="password" name="mat_khau_moi" class="form-control" required data-label="Mật khẩu mới"></div>
        <div class="mb-3"><label class="form-label">Nhập lại mật khẩu mới</label>
            <input type="password" name="nhap_lai" class="form-control" required data-label="Nhập lại mật khẩu"></div>
        <button class="btn btn-primary">Lưu</button>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
