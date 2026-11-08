<?php
// DANH MỤC GIẢNG VIÊN (thêm giảng viên sẽ tự tạo tài khoản: username = mã GV viết thường)
require_once __DIR__ . '/../includes/auth.php';
require_login('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($action === 'save') {
        $ma = strtoupper(trim($_POST['ma_gv'] ?? ''));
        $ten = trim($_POST['ho_ten'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $khoa = trim($_POST['khoa'] ?? '');
        $loi = [];
        if (!preg_match('/^[A-Z0-9]{3,20}$/', $ma)) $loi[] = 'Mã GV 3–20 ký tự chữ/số';
        if (mb_strlen($ten) < 2) $loi[] = 'Họ tên không hợp lệ';
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $loi[] = 'Email không hợp lệ';
        $st = $pdo->prepare('SELECT COUNT(*) FROM giang_vien WHERE ma_gv = ? AND id <> ?');
        $st->execute([$ma, $id]);
        if ($st->fetchColumn()) $loi[] = 'Mã giảng viên đã tồn tại';
        if (!$id) {
            $st = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
            $st->execute([strtolower($ma)]);
            if ($st->fetchColumn()) $loi[] = 'Đã có tài khoản trùng mã giảng viên';
        }
        if ($loi) {
            flash('danger', implode('. ', $loi));
            redirect('admin/giangvien.php?' . ($id ? "edit=$id" : 'add=1'));
        }
        $pdo->beginTransaction();
        if ($id) {
            $pdo->prepare('UPDATE giang_vien SET ma_gv=?, ho_ten=?, email=?, khoa=? WHERE id=?')->execute([$ma, $ten, $email ?: null, $khoa ?: null, $id]);
            $pdo->prepare('UPDATE users u JOIN giang_vien g ON g.user_id = u.id SET u.ho_ten = ?, u.email = ? WHERE g.id = ?')->execute([$ten, $email ?: null, $id]);
            flash('success', "Đã cập nhật giảng viên $ma.");
        } else {
            $pdo->prepare("INSERT INTO users (username, password_hash, ho_ten, email, role) VALUES (?,?,?,?,'giangvien')")
                ->execute([strtolower($ma), password_hash(MAT_KHAU_MAC_DINH, PASSWORD_DEFAULT), $ten, $email ?: null]);
            $pdo->prepare('INSERT INTO giang_vien (ma_gv, ho_ten, email, khoa, user_id) VALUES (?,?,?,?,?)')
                ->execute([$ma, $ten, $email ?: null, $khoa ?: null, $pdo->lastInsertId()]);
            flash('success', "Đã thêm giảng viên $ma (tài khoản: " . strtolower($ma) . ' / ' . MAT_KHAU_MAC_DINH . ').');
        }
        $pdo->commit();
    } elseif ($action === 'delete') {
        $st = $pdo->prepare('SELECT COUNT(*) FROM lop_hoc_phan WHERE giang_vien_id = ?');
        $st->execute([$id]);
        if ($st->fetchColumn()) {
            flash('danger', 'Không thể xóa: giảng viên đang phụ trách lớp học phần. Hãy chuyển lớp cho giảng viên khác trước.');
        } else {
            $pdo->beginTransaction();
            $uid = $pdo->prepare('SELECT user_id FROM giang_vien WHERE id = ?');
            $uid->execute([$id]);
            $uid = $uid->fetchColumn();
            $pdo->prepare('DELETE FROM giang_vien WHERE id = ?')->execute([$id]);
            if ($uid) $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
            $pdo->commit();
            flash('success', 'Đã xóa giảng viên.');
        }
    }
    redirect('admin/giangvien.php');
}

$q = in('q');
$st = $pdo->prepare('SELECT g.*, u.username, u.trang_thai, (SELECT COUNT(*) FROM lop_hoc_phan l WHERE l.giang_vien_id = g.id) so_lhp
    FROM giang_vien g LEFT JOIN users u ON u.id = g.user_id
    WHERE g.ma_gv LIKE ? OR g.ho_ten LIKE ? OR g.khoa LIKE ? ORDER BY g.ma_gv');
$st->execute(["%$q%", "%$q%", "%$q%"]);
$rows = $st->fetchAll();
$edit = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare('SELECT * FROM giang_vien WHERE id = ?');
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch();
}
$title = 'Giảng viên';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Danh sách giảng viên</h4>
    <a href="giangvien.php?add=1" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Thêm giảng viên</a>
</div>
<?php if ($edit || isset($_GET['add'])): $e = $edit ?: ['id' => 0, 'ma_gv' => '', 'ho_ten' => '', 'email' => '', 'khoa' => '']; ?>
<div class="card p-3 mb-3">
    <form method="post" class="row g-2 js-validate" novalidate>
        <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= $e['id'] ?>">
        <div class="col-md-2"><label class="form-label">Mã GV *</label><input name="ma_gv" class="form-control" required data-label="Mã GV" value="<?= h($e['ma_gv']) ?>"></div>
        <div class="col-md-3"><label class="form-label">Họ tên *</label><input name="ho_ten" class="form-control" required data-label="Họ tên" value="<?= h($e['ho_ten']) ?>"></div>
        <div class="col-md-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= h($e['email']) ?>"></div>
        <div class="col-md-2"><label class="form-label">Khoa</label><input name="khoa" class="form-control" value="<?= h($e['khoa']) ?>"></div>
        <div class="col-md-2 d-flex align-items-end gap-2"><button class="btn btn-success">Lưu</button><a href="giangvien.php" class="btn btn-secondary">Hủy</a></div>
    </form>
</div>
<?php endif; ?>
<form class="row g-2 mb-3"><div class="col-md-4"><input name="q" class="form-control" placeholder="Tìm theo mã, tên, khoa..." value="<?= h($q) ?>"></div><div class="col-auto"><button class="btn btn-outline-primary">Tìm</button></div></form>
<div class="card p-0">
<table class="table table-hover mb-0">
    <thead class="table-light"><tr><th>Mã GV</th><th>Họ tên</th><th>Email</th><th>Khoa</th><th>Tài khoản</th><th>Số LHP</th><th class="text-end">Thao tác</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr><td><?= h($r['ma_gv']) ?></td><td><?= h($r['ho_ten']) ?></td><td><?= h($r['email']) ?></td><td><?= h($r['khoa']) ?></td>
            <td><?= h($r['username'] ?? '—') ?> <?= $r['trang_thai'] === 'locked' ? '<span class="badge bg-danger">Khóa</span>' : '' ?></td><td><?= $r['so_lhp'] ?></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="giangvien.php?edit=<?= $r['id'] ?>">Sửa</a>
                <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <button name="action" value="delete" class="btn btn-sm btn-outline-danger" data-confirm="Xóa giảng viên <?= h($r['ma_gv']) ?>?">Xóa</button></form></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
