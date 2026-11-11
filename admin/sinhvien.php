<?php
// DANH MỤC SINH VIÊN (thêm sinh viên sẽ tự tạo tài khoản đăng nhập: username = mã SV)
require_once __DIR__ . '/../includes/auth.php';
require_login('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'save') {
        $ma = trim($_POST['ma_sv'] ?? '');
        $ten = trim($_POST['ho_ten'] ?? '');
        $ns = trim($_POST['ngay_sinh'] ?? '');
        $gt = $_POST['gioi_tinh'] ?? '';
        $lop = strtoupper(trim($_POST['lop'] ?? ''));
        $email=trim($_POST['email']??'');$khoa=trim($_POST['khoa_hoc']??'');$tt=$_POST['trang_thai_hoc']??'Đang học';
        $loi = [];
        if ($email!=='' && (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>100)) $loi[]='Email không hợp lệ';
        if (mb_strlen($khoa)>30 || !in_array($tt,['Đang học','Bảo lưu','Đã tốt nghiệp','Thôi học'],true)) $loi[]='Khóa học hoặc trạng thái không hợp lệ';
        $st=$pdo->prepare('SELECT COUNT(*) FROM lop_hanh_chinh WHERE ma_lop=?');$st->execute([$lop]);if(!$st->fetchColumn()) $loi[]='Vui lòng tạo lớp hành chính trước khi thêm sinh viên';
        if (!preg_match('/^[0-9]{6,20}$/', $ma)) $loi[] = 'Mã SV gồm 6–20 chữ số';
        if (mb_strlen($ten) < 2) $loi[] = 'Họ tên không hợp lệ';
        if ($ns !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $ns);
            if (!$d || $d->format('Y-m-d') !== $ns || $d > new DateTime('-15 years')) $loi[] = 'Ngày sinh không hợp lệ';
        }
        if (!in_array($gt, ['Nam', 'Nữ', ''], true)) $loi[] = 'Giới tính không hợp lệ';
        if ($lop === '') $loi[] = 'Lớp không được để trống';
        $st = $pdo->prepare('SELECT COUNT(*) FROM sinh_vien WHERE ma_sv = ? AND id <> ?');
        $st->execute([$ma, $id]);
        if ($st->fetchColumn()) $loi[] = 'Mã sinh viên đã tồn tại';
        if (!$id) {
            $st = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
            $st->execute([$ma]);
            if ($st->fetchColumn()) $loi[] = 'Đã có tài khoản trùng mã sinh viên';
        }
        if ($id) {
            $st=$pdo->prepare('SELECT COUNT(*) FROM users WHERE username=? AND id<>COALESCE((SELECT user_id FROM sinh_vien WHERE id=?),0)');$st->execute([$ma,$id]);
            if($st->fetchColumn())$loi[]='Đã có tài khoản khác trùng mã sinh viên mới';
        }
        if ($loi) {
            flash('danger', implode('. ', $loi));
            redirect('admin/sinhvien.php?' . ($id ? "edit=$id" : 'add=1'));
        }
        $pdo->beginTransaction();
        if ($id) {
            $pdo->prepare('UPDATE sinh_vien SET ma_sv=?, ho_ten=?, ngay_sinh=?, gioi_tinh=?, lop=? WHERE id=?')
                ->execute([$ma, $ten, $ns ?: null, $gt ?: null, $lop, $id]);
            $pdo->prepare('UPDATE users u JOIN sinh_vien s ON s.user_id = u.id SET u.ho_ten = ?, u.username = ? WHERE s.id = ?')
                ->execute([$ten, $ma, $id]);
            flash('success', "Đã cập nhật sinh viên $ma.");
        } else {
            $pdo->prepare("INSERT INTO users (username, password_hash, ho_ten, role) VALUES (?,?,?,'sinhvien')")
                ->execute([$ma, password_hash(MAT_KHAU_MAC_DINH, PASSWORD_DEFAULT), $ten]);
            $uid = $pdo->lastInsertId();
            $pdo->prepare('INSERT INTO sinh_vien (ma_sv, ho_ten, ngay_sinh, gioi_tinh, lop, user_id) VALUES (?,?,?,?,?,?)')
                ->execute([$ma, $ten, $ns ?: null, $gt ?: null, $lop, $uid]);
            flash('success', "Đã thêm sinh viên $ma (tài khoản: $ma / " . MAT_KHAU_MAC_DINH . ').');
        }
        $studentId=$id ?: (int)$pdo->lastInsertId();
        $pdo->prepare('UPDATE sinh_vien SET email=?,khoa_hoc=?,trang_thai_hoc=? WHERE id=?')->execute([$email ?: null,$khoa,$tt,$studentId]);
        $pdo->prepare('UPDATE users u JOIN sinh_vien s ON s.user_id=u.id SET u.email=? WHERE s.id=?')->execute([$email ?: null,$studentId]);
        $pdo->commit();
    } elseif ($action === 'delete') {
        $st = $pdo->prepare('SELECT COUNT(*) FROM dang_ky WHERE sinh_vien_id = ?');
        $st->execute([$id]);
        if ($st->fetchColumn()) {
            flash('danger', 'Không thể xóa: sinh viên đã có trong lớp học phần (có dữ liệu điểm). Hãy khóa tài khoản thay vì xóa.');
        } else {
            $pdo->beginTransaction();
            $uid = $pdo->prepare('SELECT user_id FROM sinh_vien WHERE id = ?');
            $uid->execute([$id]);
            $uid = $uid->fetchColumn();
            $pdo->prepare('DELETE FROM sinh_vien WHERE id = ?')->execute([$id]);
            if ($uid) $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
            $pdo->commit();
            flash('success', 'Đã xóa sinh viên và tài khoản liên kết.');
        }
    }
    redirect('admin/sinhvien.php');
}

$q = in('q');
$lop = in('lop');
$sql = 'SELECT s.*, u.trang_thai, (SELECT COUNT(*) FROM dang_ky dk WHERE dk.sinh_vien_id = s.id) so_hp
        FROM sinh_vien s LEFT JOIN users u ON u.id = s.user_id WHERE 1=1';
$p = [];
if ($q !== '') { $sql .= ' AND (s.ma_sv LIKE ? OR s.ho_ten LIKE ?)'; array_push($p, "%$q%", "%$q%"); }
if ($lop !== '') { $sql .= ' AND s.lop = ?'; $p[] = $lop; }
if (in('khoa')!=='') {$sql.=' AND s.khoa_hoc=?';$p[]=in('khoa');}
$st = $pdo->prepare($sql . ' ORDER BY s.lop, s.ma_sv');
$st->execute($p);
$rows = $st->fetchAll();
$dsLop = $pdo->query('SELECT ma_lop lop FROM lop_hanh_chinh ORDER BY ma_lop')->fetchAll();

$edit = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare('SELECT * FROM sinh_vien WHERE id = ?');
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch();
}
require_once __DIR__ . '/../includes/pagination.php';
[$rows,$pagination]=phan_trang($rows);
$title = 'Sinh viên';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Danh sách sinh viên</h4>
    <a href="sinhvien.php?add=1" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Thêm sinh viên</a>
</div>
<?php if ($edit || isset($_GET['add'])): $e = $edit ?: ['id' => 0, 'ma_sv' => '', 'ho_ten' => '', 'ngay_sinh' => '', 'gioi_tinh' => '', 'lop' => '']; ?>
<div class="card p-3 mb-3">
    <h6><?= $edit ? 'Sửa sinh viên' : 'Thêm sinh viên (tự tạo tài khoản, mật khẩu ' . MAT_KHAU_MAC_DINH . ')' ?></h6>
    <form method="post" class="row g-2 js-validate" novalidate>
        <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= $e['id'] ?>">
        <div class="col-md-2"><label class="form-label">Mã SV *</label><input name="ma_sv" class="form-control" required data-label="Mã SV" value="<?= h($e['ma_sv']) ?>"></div>
        <div class="col-md-3"><label class="form-label">Họ tên *</label><input name="ho_ten" class="form-control" required data-label="Họ tên" value="<?= h($e['ho_ten']) ?>"></div>
        <div class="col-md-2"><label class="form-label">Ngày sinh</label><input type="date" name="ngay_sinh" class="form-control" value="<?= h($e['ngay_sinh']) ?>"></div>
        <div class="col-md-1"><label class="form-label">Giới tính</label><select name="gioi_tinh" class="form-select"><option value=""></option><?php foreach (['Nam', 'Nữ'] as $g): ?><option <?= $e['gioi_tinh'] === $g ? 'selected' : '' ?>><?= $g ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2"><label class="form-label">Lớp *</label><input name="lop" class="form-control" list="dsLop" required data-label="Lớp" value="<?= h($e['lop']) ?>">
            <datalist id="dsLop"><?php foreach ($dsLop as $l): ?><option value="<?= h($l['lop']) ?>"><?php endforeach; ?></datalist></div>
        <div class="col-md-3"><label class="form-label">Email</label><input type="email" name="email" maxlength="100" class="form-control" value="<?= h($e['email']??'') ?>"></div>
        <div class="col-md-2"><label class="form-label">Khóa học</label><input name="khoa_hoc" maxlength="30" class="form-control" value="<?= h($e['khoa_hoc']??'') ?>"></div>
        <div class="col-md-3"><label class="form-label">Trạng thái học tập</label><select name="trang_thai_hoc" class="form-select"><?php foreach(['Đang học','Bảo lưu','Đã tốt nghiệp','Thôi học'] as $tt): ?><option <?= ($e['trang_thai_hoc']??'Đang học')===$tt?'selected':'' ?>><?= $tt ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2 d-flex align-items-end gap-2"><button class="btn btn-success">Lưu</button><a href="sinhvien.php" class="btn btn-secondary">Hủy</a></div>
    </form>
</div>
<?php endif; ?>
<form class="row g-2 mb-3">
    <div class="col-md-4"><input name="q" class="form-control" placeholder="Tìm theo mã SV hoặc họ tên..." value="<?= h($q) ?>"></div>
    <div class="col-md-2"><select name="lop" class="form-select"><?= options($dsLop, 'lop', 'lop', $lop, '-- Tất cả lớp --') ?></select></div>
    <div class="col-md-2"><input name="khoa" class="form-control" placeholder="Lọc khóa học" value="<?= h(in('khoa')) ?>"></div>
    <div class="col-md-3"><button class="btn btn-outline-primary">Lọc</button> <a href="sinhvien.php" class="btn btn-link">Xóa lọc</a></div>
</form>
<div class="card p-0">
<table class="table table-hover table-sm mb-0">
    <thead class="table-light"><tr><th>#</th><th>Mã SV</th><th>Họ tên</th><th>Ngày sinh</th><th>Giới tính</th><th>Lớp</th><th>Khóa</th><th>Trạng thái học tập</th><th>Số HP</th><th>Tài khoản</th><th class="text-end">Thao tác</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $i => $r): ?>
        <tr><td><?= $i + 1 ?></td><td><?= h($r['ma_sv']) ?></td><td><?= h($r['ho_ten']) ?></td>
            <td><?= $r['ngay_sinh'] ? date('d/m/Y', strtotime($r['ngay_sinh'])) : '' ?></td><td><?= h($r['gioi_tinh']) ?></td><td><?= h($r['lop']) ?></td><td><?= h($r['khoa_hoc']) ?></td><td><?= h($r['trang_thai_hoc']) ?></td><td><?= $r['so_hp'] ?></td>
            <td><?= $r['trang_thai'] === 'active' ? '<span class="badge bg-success">Hoạt động</span>' : ($r['trang_thai'] ? '<span class="badge bg-danger">Khóa</span>' : '<span class="badge bg-secondary">Chưa có</span>') ?></td>
            <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-outline-info" href="<?= BASE_URL ?>sinhvien/bang_diem.php?sv=<?= $r['id'] ?>">Bảng điểm</a>
                <a class="btn btn-sm btn-outline-primary" href="sinhvien.php?edit=<?= $r['id'] ?>">Sửa</a>
                <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <button name="action" value="delete" class="btn btn-sm btn-outline-danger" data-confirm="Xóa sinh viên <?= h($r['ma_sv']) ?>?">Xóa</button></form></td></tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="11" class="text-center text-muted">Không tìm thấy.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>
<p class="text-muted small mt-2">Tổng: <?= $pagination['total'] ?> sinh viên</p>
<?php hien_phan_trang($pagination); require __DIR__ . '/../includes/footer.php'; ?>
