<?php
require_once __DIR__ . '/includes/auth.php';
$u = require_login();
$batBuoc = !empty($_SESSION['phai_doi_mk']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $cu = $_POST['mat_khau_cu'] ?? '';
    $moi = $_POST['mat_khau_moi'] ?? '';
    $lai = $_POST['nhap_lai'] ?? '';
    $st = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $st->execute([$u['id']]);
    $hash = $st->fetchColumn();
    if (!password_verify($cu, $hash)) {
        flash('danger', 'Mật khẩu hiện tại không đúng.');
    } elseif ($loi = loi_mat_khau($moi, $u['username'])) {
        flash('danger', $loi);
    } elseif (password_verify($moi, $hash) || $moi === MAT_KHAU_MAC_DINH) {
        flash('danger', 'Mật khẩu mới phải khác mật khẩu hiện tại và mật khẩu mặc định.');
    } elseif ($moi !== $lai) {
        flash('danger', 'Mật khẩu nhập lại không khớp.');
    } else {
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($moi, PASSWORD_DEFAULT), $u['id']]);
        session_regenerate_id(true);
        unset($_SESSION['phai_doi_mk']);
        flash('success', 'Đổi mật khẩu thành công.');
        redirect($batBuoc ? 'index.php' : 'doi_mat_khau.php');
    }
    redirect('doi_mat_khau.php');
}
$title = 'Đổi mật khẩu';
require __DIR__ . '/includes/header.php';
?>
<div class="card p-4" style="max-width:480px">
    <h5 class="mb-3">Đổi mật khẩu</h5>
    <?php if ($batBuoc): ?>
    <div class="alert alert-warning small">Bạn đang dùng mật khẩu mặc định. Hãy đặt mật khẩu mới trước khi tiếp tục sử dụng hệ thống.</div>
    <?php endif; ?>
    <form method="post" class="js-validate" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3"><label class="form-label">Mật khẩu hiện tại</label>
            <input type="password" name="mat_khau_cu" class="form-control" required autocomplete="current-password" data-label="Mật khẩu hiện tại"></div>
        <div class="mb-3"><label class="form-label">Mật khẩu mới</label>
            <input type="password" name="mat_khau_moi" class="form-control" required minlength="8" autocomplete="new-password" data-label="Mật khẩu mới">
            <div class="form-text">Tối thiểu 8 ký tự, có cả chữ và số, không chứa tên đăng nhập.</div></div>
        <div class="mb-3"><label class="form-label">Nhập lại mật khẩu mới</label>
            <input type="password" name="nhap_lai" class="form-control" required autocomplete="new-password" data-label="Nhập lại mật khẩu"></div>
        <button class="btn btn-primary">Lưu</button>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
