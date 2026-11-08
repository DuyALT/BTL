<?php
// DANH MỤC MÔN HỌC + CẤU HÌNH TRỌNG SỐ TÍNH ĐIỂM
require_once __DIR__ . '/../includes/auth.php';
require_login('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'save') {
        $f = [
            'ma_mon' => strtoupper(trim($_POST['ma_mon'] ?? '')),
            'ten_mon' => trim($_POST['ten_mon'] ?? ''),
            'so_tin_chi' => filter_var($_POST['so_tin_chi'] ?? '', FILTER_VALIDATE_INT),
            'ts_cc' => filter_var($_POST['ts_cc'] ?? '', FILTER_VALIDATE_INT),
            'ts_kt' => filter_var($_POST['ts_kt'] ?? '', FILTER_VALIDATE_INT),
            'ts_th' => filter_var($_POST['ts_th'] ?? '', FILTER_VALIDATE_INT),
            'ts_thi' => filter_var($_POST['ts_thi'] ?? '', FILTER_VALIDATE_INT),
            'diem_dat' => parse_diem($_POST['diem_dat'] ?? ''),
        ];
        $loi = [];
        if (!preg_match('/^[A-Z0-9]{2,20}$/', $f['ma_mon'])) $loi[] = 'Mã môn 2–20 ký tự chữ/số';
        if ($f['ten_mon'] === '') $loi[] = 'Tên môn không được để trống';
        if ($f['so_tin_chi'] === false || $f['so_tin_chi'] < 1 || $f['so_tin_chi'] > 10) $loi[] = 'Số tín chỉ từ 1 đến 10';
        foreach (['ts_cc', 'ts_kt', 'ts_th', 'ts_thi'] as $k) {
            if ($f[$k] === false || $f[$k] < 0 || $f[$k] > 100) $loi[] = 'Trọng số phải là số nguyên 0–100';
        }
        if (!$loi && $f['ts_cc'] + $f['ts_kt'] + $f['ts_th'] + $f['ts_thi'] !== 100) $loi[] = 'Tổng trọng số phải bằng 100%';
        if (!$loi && $f['ts_thi'] < 1) $loi[] = 'Môn học phải có điểm thi kết thúc học phần';
        if ($f['diem_dat'] === false || $f['diem_dat'] === null) $loi[] = 'Điểm đạt phải từ 0 đến 10';
        $st = $pdo->prepare('SELECT COUNT(*) FROM mon_hoc WHERE ma_mon = ? AND id <> ?');
        $st->execute([$f['ma_mon'], $id]);
        if ($st->fetchColumn()) $loi[] = 'Mã môn đã tồn tại';

        if ($loi) {
            flash('danger', implode('. ', array_unique($loi)));
            redirect('admin/monhoc.php?' . ($id ? "edit=$id" : 'add=1'));
        }
        if ($id) {
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE mon_hoc SET ma_mon=?, ten_mon=?, so_tin_chi=?, ts_cc=?, ts_kt=?, ts_th=?, ts_thi=?, diem_dat=? WHERE id=?')
                ->execute([...array_values($f), $id]);
            $n = tinh_lai_theo_mon($pdo, $id);   // đổi công thức => tính lại toàn bộ điểm tổng kết
            $pdo->commit();
            flash('success', "Đã cập nhật môn {$f['ma_mon']}, tính lại điểm tổng kết cho $n sinh viên.");
        } else {
            $pdo->prepare('INSERT INTO mon_hoc (ma_mon, ten_mon, so_tin_chi, ts_cc, ts_kt, ts_th, ts_thi, diem_dat) VALUES (?,?,?,?,?,?,?,?)')
                ->execute(array_values($f));
            flash('success', "Đã thêm môn {$f['ma_mon']}.");
        }
    } elseif ($action === 'delete') {
        try {
            $pdo->prepare('DELETE FROM mon_hoc WHERE id = ?')->execute([$id]);
            flash('success', 'Đã xóa môn học.');
        } catch (PDOException $e) {
            flash('danger', 'Không thể xóa: môn học đã được mở lớp học phần.');
        }
    }
    redirect('admin/monhoc.php');
}

$q = in('q');
$tc = in('tc');
$sql = 'SELECT m.*, (SELECT COUNT(*) FROM lop_hoc_phan l WHERE l.mon_hoc_id = m.id) so_lhp FROM mon_hoc m WHERE 1=1';
$p = [];
if ($q !== '') { $sql .= ' AND (ma_mon LIKE ? OR ten_mon LIKE ?)'; array_push($p, "%$q%", "%$q%"); }
if ($tc !== '') { $sql .= ' AND so_tin_chi = ?'; $p[] = (int)$tc; }
$st = $pdo->prepare($sql . ' ORDER BY ma_mon');
$st->execute($p);
$rows = $st->fetchAll();

$edit = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare('SELECT * FROM mon_hoc WHERE id = ?');
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch();
}
$title = 'Môn học';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Môn học & cấu hình trọng số</h4>
    <a href="monhoc.php?add=1" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Thêm môn học</a>
</div>

<?php if ($edit || isset($_GET['add'])):
    $e = $edit ?: ['id' => 0, 'ma_mon' => '', 'ten_mon' => '', 'so_tin_chi' => 3, 'ts_cc' => 10, 'ts_kt' => 20, 'ts_th' => 20, 'ts_thi' => 50, 'diem_dat' => '4.0']; ?>
<div class="card p-3 mb-3">
    <h6><?= $edit ? 'Sửa môn học' : 'Thêm môn học' ?></h6>
    <form method="post" class="row g-2 js-validate" novalidate>
        <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= $e['id'] ?>">
        <div class="col-md-2"><label class="form-label">Mã môn *</label><input name="ma_mon" class="form-control" required data-label="Mã môn" value="<?= h($e['ma_mon']) ?>"></div>
        <div class="col-md-4"><label class="form-label">Tên môn *</label><input name="ten_mon" class="form-control" required data-label="Tên môn" value="<?= h($e['ten_mon']) ?>"></div>
        <div class="col-md-2"><label class="form-label">Số tín chỉ *</label><input type="number" min="1" max="10" name="so_tin_chi" class="form-control" required data-label="Số tín chỉ" value="<?= h($e['so_tin_chi']) ?>"></div>
        <div class="col-md-2"><label class="form-label">Điểm đạt (≥)</label><input name="diem_dat" class="form-control score" required data-label="Điểm đạt" value="<?= h($e['diem_dat']) ?>"></div>
        <div class="col-12 mt-2"><b>Trọng số (%)</b> — tổng hiện tại: <span id="tongTrongSo"></span> <span class="text-muted small">(đặt 0 nếu môn không có thành phần đó)</span></div>
        <?php foreach (COT_DIEM as $c => $t): ?>
            <div class="col-md-2"><label class="form-label"><?= $t ?></label><input type="number" min="0" max="100" name="ts_<?= $c ?>" class="form-control weight" value="<?= h($e['ts_' . $c]) ?>"></div>
        <?php endforeach; ?>
        <?php if ($edit): ?><div class="col-12 small text-warning">⚠ Đổi trọng số sẽ tính lại điểm tổng kết của tất cả sinh viên đã học môn này.</div><?php endif; ?>
        <div class="col-12"><button class="btn btn-success">Lưu</button> <a href="monhoc.php" class="btn btn-secondary">Hủy</a></div>
    </form>
</div>
<?php endif; ?>

<form class="row g-2 mb-3">
    <div class="col-md-4"><input name="q" class="form-control" placeholder="Tìm theo mã hoặc tên môn..." value="<?= h($q) ?>"></div>
    <div class="col-md-2"><select name="tc" class="form-select"><option value="">-- Số tín chỉ --</option><?php for ($i = 1; $i <= 6; $i++): ?><option <?= $tc == $i ? 'selected' : '' ?>><?= $i ?></option><?php endfor; ?></select></div>
    <div class="col-md-2"><button class="btn btn-outline-primary">Lọc</button> <a href="monhoc.php" class="btn btn-link">Xóa lọc</a></div>
</form>

<div class="card p-0">
<table class="table table-hover mb-0">
    <thead class="table-light"><tr><th>Mã môn</th><th>Tên môn</th><th>TC</th><th>CC</th><th>KT</th><th>TH</th><th>Thi</th><th>Công thức</th><th>Điểm đạt</th><th>Số LHP</th><th class="text-end">Thao tác</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= h($r['ma_mon']) ?></td><td><?= h($r['ten_mon']) ?></td><td><?= $r['so_tin_chi'] ?></td>
            <td><?= $r['ts_cc'] ?>%</td><td><?= $r['ts_kt'] ?>%</td><td><?= $r['ts_th'] ?>%</td><td><?= $r['ts_thi'] ?>%</td>
            <td class="small"><?= h(cong_thuc_text($r)) ?></td><td>≥ <?= fd($r['diem_dat']) ?></td><td><?= $r['so_lhp'] ?></td>
            <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-outline-primary" href="monhoc.php?edit=<?= $r['id'] ?>">Sửa</a>
                <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <button name="action" value="delete" class="btn btn-sm btn-outline-danger" data-confirm="Xóa môn <?= h($r['ma_mon']) ?>?">Xóa</button></form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="11" class="text-center text-muted">Không có dữ liệu.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
