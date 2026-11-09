<?php
// DANH MỤC HỌC KỲ - NĂM HỌC
require_once __DIR__ . '/../includes/auth.php';
require_login('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($action === 'save') {
        $ma = trim($_POST['ma_hk'] ?? '');
        $ten = trim($_POST['ten_hk'] ?? '');
        $nam = trim($_POST['nam_hoc'] ?? '');
        $status=$_POST['trang_thai']??'Mở';
        $loi = [];
        if (!in_array($status,['Mở','Đóng'],true)) $loi[]='Trạng thái học kỳ không hợp lệ';
        if (!preg_match('/^\d{5}$/', $ma)) $loi[] = 'Mã học kỳ gồm 5 chữ số, vd 20251';
        if ($ten === '') $loi[] = 'Tên học kỳ không được để trống';
        if (!preg_match('/^(\d{4})-(\d{4})$/', $nam, $m) || $m[2] - $m[1] !== 1) $loi[] = 'Năm học dạng 2025-2026';
        $st = $pdo->prepare('SELECT COUNT(*) FROM hoc_ky WHERE ma_hk = ? AND id <> ?');
        $st->execute([$ma, $id]);
        if ($st->fetchColumn()) $loi[] = 'Mã học kỳ đã tồn tại';
        if ($loi) {
            flash('danger', implode('. ', $loi));
            redirect('admin/hocky.php?' . ($id ? "edit=$id" : 'add=1'));
        }
        $pdo->prepare('INSERT IGNORE INTO nam_hoc VALUES (?)')->execute([$nam]);
        if ($id) $pdo->prepare('UPDATE hoc_ky SET ma_hk=?, ten_hk=?, nam_hoc=? WHERE id=?')->execute([$ma, $ten, $nam, $id]);
        else $pdo->prepare('INSERT INTO hoc_ky (ma_hk, ten_hk, nam_hoc) VALUES (?,?,?)')->execute([$ma, $ten, $nam]);
        $savedId=$id ?: (int)$pdo->lastInsertId();
        $pdo->prepare('UPDATE hoc_ky SET trang_thai=? WHERE id=?')->execute([$status,$savedId]);
        flash('success', 'Đã lưu học kỳ.');
    } elseif ($action === 'delete') {
        try {
            $pdo->prepare('DELETE FROM hoc_ky WHERE id = ?')->execute([$id]);
            flash('success', 'Đã xóa học kỳ.');
        } catch (PDOException $e) {
            flash('danger', 'Không thể xóa: học kỳ đã có lớp học phần.');
        }
    }
    redirect('admin/hocky.php');
}

$q = in('q');
$st = $pdo->prepare('SELECT hk.*, (SELECT COUNT(*) FROM lop_hoc_phan l WHERE l.hoc_ky_id = hk.id) so_lhp
    FROM hoc_ky hk WHERE ma_hk LIKE ? OR nam_hoc LIKE ? OR ten_hk LIKE ? ORDER BY ma_hk DESC');
$st->execute(["%$q%", "%$q%", "%$q%"]);
$rows = $st->fetchAll();
$edit = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare('SELECT * FROM hoc_ky WHERE id = ?');
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch();
}
require_once __DIR__ . '/../includes/pagination.php';
[$rows,$pagination]=phan_trang($rows);
$title = 'Học kỳ';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Học kỳ – Năm học</h4>
    <a href="hocky.php?add=1" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Thêm học kỳ</a>
</div>
<?php if ($edit || isset($_GET['add'])): $e = $edit ?: ['id' => 0, 'ma_hk' => '', 'ten_hk' => '', 'nam_hoc' => '']; ?>
<div class="card p-3 mb-3">
    <form method="post" class="row g-2 js-validate" novalidate>
        <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= $e['id'] ?>">
        <div class="col-md-2"><label class="form-label">Mã HK *</label><input name="ma_hk" class="form-control" placeholder="20251" required data-label="Mã học kỳ" value="<?= h($e['ma_hk']) ?>"></div>
        <div class="col-md-3"><label class="form-label">Tên *</label><input name="ten_hk" class="form-control" placeholder="Học kỳ 1" required data-label="Tên học kỳ" value="<?= h($e['ten_hk']) ?>"></div>
        <div class="col-md-3"><label class="form-label">Năm học *</label><input name="nam_hoc" class="form-control" placeholder="2025-2026" required data-label="Năm học" value="<?= h($e['nam_hoc']) ?>"></div>
        <div class="col-md-2"><label class="form-label">Trạng thái</label><select name="trang_thai" class="form-select"><?php foreach(['Mở','Đóng'] as $v): ?><option <?= ($e['trang_thai']??'Mở')===$v?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4 d-flex align-items-end gap-2"><button class="btn btn-success">Lưu</button><a href="hocky.php" class="btn btn-secondary">Hủy</a></div>
    </form>
</div>
<?php endif; ?>
<form class="row g-2 mb-3"><div class="col-md-4"><input name="q" class="form-control" placeholder="Tìm học kỳ / năm học..." value="<?= h($q) ?>"></div><div class="col-auto"><button class="btn btn-outline-primary">Tìm</button></div></form>
<div class="card p-0">
<table class="table table-hover mb-0">
    <thead class="table-light"><tr><th>Mã HK</th><th>Tên</th><th>Năm học</th><th>Số lớp học phần</th><th class="text-end">Thao tác</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr><td><?= h($r['ma_hk']) ?></td><td class="cell-entity"><?= h($r['ten_hk']) ?></td><td class="cell-entity"><?= h($r['nam_hoc']) ?></td><td><a href="lophocphan.php?hk=<?= $r['id'] ?>"><?= $r['so_lhp'] ?> học phần</a> · <?= h($r['trang_thai']) ?></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="hocky.php?edit=<?= $r['id'] ?>">Sửa</a>
                <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <button name="action" value="delete" class="btn btn-sm btn-outline-danger" data-confirm="Xóa học kỳ này?">Xóa</button></form></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php hien_phan_trang($pagination); require __DIR__ . '/../includes/footer.php'; ?>
