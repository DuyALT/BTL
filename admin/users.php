<?php
// QUẢN LÝ NGƯỜI DÙNG: thêm, sửa, xóa, khóa/mở khóa, đặt lại mật khẩu, phân quyền
require_once __DIR__ . '/../includes/auth.php';
$me = require_login('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'save') {
        $username = trim($_POST['username'] ?? '');
        $ho_ten = trim($_POST['ho_ten'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? '';
        $trang_thai = $_POST['trang_thai'] ?? 'active';
        $pw = $_POST['mat_khau_moi'] ?? '';

        $loi = [];
        if (!preg_match('/^[A-Za-z0-9_.]{3,50}$/', $username)) $loi[] = 'Tên đăng nhập 3–50 ký tự, chỉ gồm chữ, số, _ và .';
        if ($ho_ten === '') $loi[] = 'Họ tên không được để trống';
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $loi[] = 'Email không hợp lệ';
        if (!isset(TEN_VAI_TRO[$role])) $loi[] = 'Vai trò không hợp lệ';
        if (!in_array($trang_thai, ['active', 'locked'], true)) $loi[] = 'Trạng thái không hợp lệ';
        // Mật khẩu mặc định được phép: người dùng sẽ bị bắt đổi ở lần đăng nhập đầu
        if ((!$id || $pw !== '') && $pw !== MAT_KHAU_MAC_DINH && ($e = loi_mat_khau($pw, $username))) {
            $loi[] = rtrim($e, '.') . ' (hoặc dùng mật khẩu mặc định ' . MAT_KHAU_MAC_DINH . ' để người dùng tự đổi)';
        }
        if ($id === (int)$me['id'] && ($role !== 'admin' || $trang_thai !== 'active')) $loi[] = 'Không thể tự hạ quyền hoặc tự khóa chính mình';

        $st = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? AND id <> ?');
        $st->execute([$username, $id]);
        if ($st->fetchColumn()) $loi[] = 'Tên đăng nhập đã tồn tại';

        if ($loi) {
            flash('danger', implode('. ', $loi));
            redirect('admin/users.php?' . ($id ? "edit=$id" : 'add=1'));
        }
        if ($id) {
            $pdo->prepare('UPDATE users SET username=?, ho_ten=?, email=?, role=?, trang_thai=? WHERE id=?')
                ->execute([$username, $ho_ten, $email ?: null, $role, $trang_thai, $id]);
            if ($pw !== '') {
                $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($pw, PASSWORD_DEFAULT), $id]);
            }
            flash('success', 'Đã cập nhật tài khoản ' . $username);
        } else {
            $pdo->prepare('INSERT INTO users (username, password_hash, ho_ten, email, role, trang_thai) VALUES (?,?,?,?,?,?)')
                ->execute([$username, password_hash($pw, PASSWORD_DEFAULT), $ho_ten, $email ?: null, $role, $trang_thai]);
            flash('success', 'Đã thêm tài khoản ' . $username);
        }
    } elseif ($action === 'toggle' && $id !== (int)$me['id']) {
        $pdo->prepare("UPDATE users SET trang_thai = IF(trang_thai='active','locked','active') WHERE id = ?")->execute([$id]);
        flash('success', 'Đã thay đổi trạng thái tài khoản.');
    } elseif ($action === 'reset') {
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash(MAT_KHAU_MAC_DINH, PASSWORD_DEFAULT), $id]);
        flash('success', 'Đã đặt lại mật khẩu về ' . MAT_KHAU_MAC_DINH);
    } elseif ($action === 'delete') {
        if ($id === (int)$me['id']) {
            flash('danger', 'Không thể xóa tài khoản đang đăng nhập.');
        } else {
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            flash('success', 'Đã xóa tài khoản. (Hồ sơ sinh viên/giảng viên liên kết vẫn được giữ lại.)');
        }
    }
    redirect('admin/users.php');
}

// ----- Danh sách + tìm kiếm + lọc -----
$q = in('q');
$role = in('role');
$tt = in('tt');
$sql = 'SELECT * FROM users WHERE 1=1';
$p = [];
if ($q !== '') { $sql .= ' AND (username LIKE ? OR ho_ten LIKE ? OR email LIKE ?)'; array_push($p, "%$q%", "%$q%", "%$q%"); }
if ($role !== '') { $sql .= ' AND role = ?'; $p[] = $role; }
if ($tt !== '') { $sql .= ' AND trang_thai = ?'; $p[] = $tt; }
$sql .= ' ORDER BY FIELD(role,"admin","giangvien","sinhvien"), username';
$st = $pdo->prepare($sql);
$st->execute($p);
$rows = $st->fetchAll();

$edit = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch();
}
$showForm = $edit || isset($_GET['add']);

require_once __DIR__ . '/../includes/pagination.php';
[$rows,$pagination]=phan_trang($rows);
$title = 'Quản lý người dùng';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Quản lý người dùng</h4>
    <a href="users.php?add=1" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Thêm tài khoản</a>
</div>

<?php if ($showForm): $e = $edit ?: ['id' => 0, 'username' => '', 'ho_ten' => '', 'email' => '', 'role' => 'sinhvien', 'trang_thai' => 'active']; ?>
<div class="card p-3 mb-3">
    <h6><?= $edit ? 'Sửa tài khoản: ' . h($e['username']) : 'Thêm tài khoản mới' ?></h6>
    <form method="post" class="row g-2 js-validate" novalidate>
        <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= $e['id'] ?>">
        <div class="col-md-2"><label class="form-label">Tên đăng nhập *</label><input name="username" class="form-control" required data-label="Tên đăng nhập" value="<?= h($e['username']) ?>"></div>
        <div class="col-md-3"><label class="form-label">Họ tên *</label><input name="ho_ten" class="form-control" required data-label="Họ tên" value="<?= h($e['ho_ten']) ?>"></div>
        <div class="col-md-2"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= h($e['email']) ?>"></div>
        <div class="col-md-2"><label class="form-label">Vai trò</label>
            <select name="role" class="form-select"><?php foreach (TEN_VAI_TRO as $k => $v): ?><option value="<?= $k ?>" <?= $e['role'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
        <div class="col-md-1"><label class="form-label">Trạng thái</label>
            <select name="trang_thai" class="form-select"><option value="active">Hoạt động</option><option value="locked" <?= $e['trang_thai'] === 'locked' ? 'selected' : '' ?>>Khóa</option></select></div>
        <div class="col-md-2"><label class="form-label">Mật khẩu <?= $edit ? '(bỏ trống = giữ nguyên)' : '*' ?></label>
            <input type="password" name="mat_khau_moi" class="form-control" <?= $edit ? '' : 'required' ?> autocomplete="new-password" data-label="Mật khẩu" title="Tối thiểu 8 ký tự, có chữ và số; hoặc <?= MAT_KHAU_MAC_DINH ?> để người dùng tự đổi khi đăng nhập"></div>
        <div class="col-12"><button class="btn btn-success">Lưu</button> <a href="users.php" class="btn btn-secondary">Hủy</a></div>
    </form>
</div>
<?php endif; ?>

<form class="row g-2 mb-3">
    <div class="col-md-4"><input name="q" class="form-control" placeholder="Tìm theo tên đăng nhập, họ tên, email..." value="<?= h($q) ?>"></div>
    <div class="col-md-2"><select name="role" class="form-select"><option value="">-- Mọi vai trò --</option><?php foreach (TEN_VAI_TRO as $k => $v): ?><option value="<?= $k ?>" <?= $role === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><select name="tt" class="form-select"><option value="">-- Mọi trạng thái --</option><option value="active" <?= $tt === 'active' ? 'selected' : '' ?>>Hoạt động</option><option value="locked" <?= $tt === 'locked' ? 'selected' : '' ?>>Bị khóa</option></select></div>
    <div class="col-md-2"><button class="btn btn-outline-primary">Lọc</button> <a href="users.php" class="btn btn-link">Xóa lọc</a></div>
</form>

<div class="card p-0">
<table class="table table-hover mb-0">
    <thead class="table-light"><tr><th>#</th><th>Tên đăng nhập</th><th>Họ tên</th><th>Email</th><th>Vai trò</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $i => $r): ?>
        <tr>
            <td><?= $i + 1 ?></td><td><?= h($r['username']) ?></td><td><?= h($r['ho_ten']) ?></td><td><?= h($r['email']) ?></td>
            <td><?= h(TEN_VAI_TRO[$r['role']]) ?></td>
            <td><?= $r['trang_thai'] === 'active' ? '<span class="badge bg-success">Hoạt động</span>' : '<span class="badge bg-danger">Bị khóa</span>' ?></td>
            <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-outline-primary" href="users.php?edit=<?= $r['id'] ?>">Sửa</a>
                <?php if ($r['id'] != $me['id']): ?>
                <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <button name="action" value="toggle" class="btn btn-sm btn-outline-warning"><?= $r['trang_thai'] === 'active' ? 'Khóa' : 'Mở khóa' ?></button>
                    <button name="action" value="reset" class="btn btn-sm btn-outline-secondary" data-confirm="Đặt lại mật khẩu về <?= MAT_KHAU_MAC_DINH ?>?">Reset MK</button>
                    <button name="action" value="delete" class="btn btn-sm btn-outline-danger" data-confirm="Xóa tài khoản <?= h($r['username']) ?>?">Xóa</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="7" class="text-muted text-center">Không tìm thấy.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>
<p class="text-muted small mt-2">Tổng: <?= count($rows) ?> tài khoản</p>
<?php hien_phan_trang($pagination); require __DIR__ . '/../includes/footer.php'; ?>
