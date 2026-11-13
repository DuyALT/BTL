<?php
// DANH MỤC LỚP HỌC PHẦN (môn học mở trong học kỳ, phân công giảng viên)
require_once __DIR__ . '/../includes/auth.php';
require_login('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($action === 'save') {
        $ma = strtoupper(trim($_POST['ma_lhp'] ?? ''));
        $mon = (int)($_POST['mon_hoc_id'] ?? 0);
        $hk = (int)($_POST['hoc_ky_id'] ?? 0);
        $gv = (int)($_POST['giang_vien_id'] ?? 0);
        $ss = filter_var($_POST['si_so_toi_da'] ?? '', FILTER_VALIDATE_INT);
        $loi = [];
        if (!preg_match('/^[A-Z0-9.\-]{3,30}$/', $ma)) $loi[] = 'Mã lớp học phần 3–30 ký tự (chữ, số, dấu chấm, gạch ngang)';
        foreach ([[$mon, 'mon_hoc', 'Môn học'], [$hk, 'hoc_ky', 'Học kỳ'], [$gv, 'giang_vien', 'Giảng viên']] as [$v, $tb, $t]) {
            $s = $pdo->prepare("SELECT COUNT(*) FROM $tb WHERE id = ?");   // $tb là hằng trong code, không phải dữ liệu người dùng
            $s->execute([$v]);
            if (!$s->fetchColumn()) $loi[] = "$t không hợp lệ";
        }
        if ($ss === false || $ss < 1 || $ss > 300) $loi[] = 'Sĩ số tối đa từ 1 đến 300';
        $s = $pdo->prepare('SELECT COUNT(*) FROM lop_hoc_phan WHERE ma_lhp = ? AND id <> ?');
        $s->execute([$ma, $id]);
        if ($s->fetchColumn()) $loi[] = 'Mã lớp học phần đã tồn tại';
        if ($id && $ss !== false) {
            $s = $pdo->prepare('SELECT COUNT(*) FROM dang_ky WHERE lhp_id = ?');
            $s->execute([$id]);
            if ($s->fetchColumn() > $ss) $loi[] = 'Sĩ số tối đa nhỏ hơn số sinh viên hiện có';
            $s=$pdo->prepare('SELECT l.mon_hoc_id,l.hoc_ky_id,COUNT(dk.id) so_sv FROM lop_hoc_phan l LEFT JOIN dang_ky dk ON dk.lhp_id=l.id WHERE l.id=? GROUP BY l.id');$s->execute([$id]);$existing=$s->fetch();
            if($existing && $existing['so_sv'] && ((int)$existing['mon_hoc_id']!==$mon || (int)$existing['hoc_ky_id']!==$hk))$loi[]='Lớp đã có sinh viên, không được chuyển sang môn hoặc học kỳ khác';
        }
        if ($loi) {
            flash('danger', implode('. ', $loi));
            redirect('admin/lophocphan.php?' . ($id ? "edit=$id" : 'add=1'));
        }
        if ($id) {
            $pdo->prepare('UPDATE lop_hoc_phan SET ma_lhp=?, mon_hoc_id=?, hoc_ky_id=?, giang_vien_id=?, si_so_toi_da=?, phien_ban=phien_ban+1 WHERE id=?')->execute([$ma, $mon, $hk, $gv, $ss, $id]);
            tinh_lai_theo_mon($pdo, $mon);
        } else {
            $pdo->prepare('INSERT INTO lop_hoc_phan (ma_lhp, mon_hoc_id, hoc_ky_id, giang_vien_id, si_so_toi_da) VALUES (?,?,?,?,?)')->execute([$ma, $mon, $hk, $gv, $ss]);
            $id = $pdo->lastInsertId();
        }
        flash('success', "Đã lưu lớp học phần $ma.");
        redirect("admin/lhp_sinhvien.php?id=$id");
    } elseif ($action === 'delete') {
        $s = $pdo->prepare('SELECT COUNT(*) FROM dang_ky WHERE lhp_id = ?');
        $s->execute([$id]);
        if ($s->fetchColumn()) {
            flash('danger', 'Không thể xóa: lớp học phần đã có sinh viên. Hãy xóa sinh viên khỏi lớp trước.');
        } else {
            $pdo->prepare('DELETE FROM lop_hoc_phan WHERE id = ?')->execute([$id]);
            flash('success', 'Đã xóa lớp học phần.');
        }
    }
    redirect('admin/lophocphan.php');
}

$dsMon = $pdo->query("SELECT id, CONCAT(ma_mon,' - ',ten_mon) ten FROM mon_hoc ORDER BY ma_mon")->fetchAll();
$dsHK = $pdo->query("SELECT id, CONCAT(ten_hk,' (',nam_hoc,')') ten FROM hoc_ky ORDER BY ma_hk DESC")->fetchAll();
$dsGV = $pdo->query("SELECT id, CONCAT(ma_gv,' - ',ho_ten) ten FROM giang_vien ORDER BY ma_gv")->fetchAll();

$q = in('q'); $fhk = in('hk'); $fmon = in('mon'); $fgv = in('gv');
$sql = 'SELECT l.*, m.ma_mon, m.ten_mon, m.so_tin_chi, hk.ten_hk, hk.nam_hoc, g.ho_ten gv,
        (SELECT COUNT(*) FROM dang_ky dk WHERE dk.lhp_id = l.id) si_so
    FROM lop_hoc_phan l JOIN mon_hoc m ON m.id = l.mon_hoc_id JOIN hoc_ky hk ON hk.id = l.hoc_ky_id
    LEFT JOIN giang_vien g ON g.id = l.giang_vien_id WHERE 1=1';
$p = [];
if ($q !== '') { $sql .= ' AND (l.ma_lhp LIKE ? OR m.ten_mon LIKE ?)'; array_push($p, "%$q%", "%$q%"); }
if ($fhk !== '') { $sql .= ' AND l.hoc_ky_id = ?'; $p[] = (int)$fhk; }
if ($fmon !== '') { $sql .= ' AND l.mon_hoc_id = ?'; $p[] = (int)$fmon; }
if ($fgv !== '') { $sql .= ' AND l.giang_vien_id = ?'; $p[] = (int)$fgv; }
$st = $pdo->prepare($sql . ' ORDER BY hk.ma_hk DESC, l.ma_lhp');
$st->execute($p);
$rows = $st->fetchAll();

$edit = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare('SELECT * FROM lop_hoc_phan WHERE id = ?');
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch();
}
require_once __DIR__ . '/../includes/pagination.php';
[$rows,$pagination]=phan_trang($rows);
$title = 'Lớp học phần';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Lớp học phần</h4>
    <a href="lophocphan.php?add=1" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Mở lớp học phần</a>
</div>
<?php if ($edit || isset($_GET['add'])): $e = $edit ?: ['id' => 0, 'ma_lhp' => '', 'mon_hoc_id' => '', 'hoc_ky_id' => '', 'giang_vien_id' => '', 'si_so_toi_da' => 60]; ?>
<div class="card p-3 mb-3">
    <form method="post" class="row g-2 js-validate" novalidate>
        <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= $e['id'] ?>">
        <div class="col-md-2"><label class="form-label">Mã LHP *</label><input name="ma_lhp" class="form-control" placeholder="IT3010.20251.01" required data-label="Mã LHP" value="<?= h($e['ma_lhp']) ?>"></div>
        <div class="col-md-3"><label class="form-label">Môn học *</label><select name="mon_hoc_id" class="form-select" required data-label="Môn học"><?= options($dsMon, 'id', 'ten', $e['mon_hoc_id'], '-- Chọn --') ?></select></div>
        <div class="col-md-2"><label class="form-label">Học kỳ *</label><select name="hoc_ky_id" class="form-select" required data-label="Học kỳ"><?= options($dsHK, 'id', 'ten', $e['hoc_ky_id'], '-- Chọn --') ?></select></div>
        <div class="col-md-3"><label class="form-label">Giảng viên *</label><select name="giang_vien_id" class="form-select" required data-label="Giảng viên"><?= options($dsGV, 'id', 'ten', $e['giang_vien_id'], '-- Chọn --') ?></select></div>
        <div class="col-md-1"><label class="form-label">Sĩ số tối đa</label><input type="number" name="si_so_toi_da" class="form-control" min="1" max="300" value="<?= h($e['si_so_toi_da']) ?>"></div>
        <div class="col-md-1 d-flex align-items-end gap-2"><button class="btn btn-success">Lưu</button></div>
    </form>
</div>
<?php endif; ?>
<form class="row g-2 mb-3">
    <div class="col-md-3"><input name="q" class="form-control" placeholder="Tìm mã LHP / tên môn..." value="<?= h($q) ?>"></div>
    <div class="col-md-2"><select name="hk" class="form-select"><?= options($dsHK, 'id', 'ten', $fhk, '-- Học kỳ --') ?></select></div>
    <div class="col-md-3"><select name="mon" class="form-select"><?= options($dsMon, 'id', 'ten', $fmon, '-- Môn học --') ?></select></div>
    <div class="col-md-2"><select name="gv" class="form-select"><?= options($dsGV, 'id', 'ten', $fgv, '-- Giảng viên --') ?></select></div>
    <div class="col-md-2"><button class="btn btn-outline-primary">Lọc</button> <a href="lophocphan.php" class="btn btn-link">Xóa lọc</a></div>
</form>
<div class="card p-0">
<table class="table table-hover table-sm mb-0">
    <thead class="table-light"><tr><th>Mã LHP</th><th>Môn học</th><th>TC</th><th>Học kỳ</th><th>Giảng viên</th><th>Sĩ số</th><th class="text-end">Thao tác</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr><td><?= h($r['ma_lhp']) ?></td><td><?= h($r['ten_mon']) ?></td><td><?= $r['so_tin_chi'] ?></td><td><?= h($r['ten_hk'] . ' ' . $r['nam_hoc']) ?></td>
            <td><?= h($r['gv'] ?? '—') ?></td><td><?= $r['si_so'] ?>/<?= $r['si_so_toi_da'] ?></td>
            <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-outline-success" href="lhp_sinhvien.php?id=<?= $r['id'] ?>">Sinh viên</a>
                <a class="btn btn-sm btn-outline-info" href="<?= BASE_URL ?>giangvien/nhap_diem.php?lhp=<?= $r['id'] ?>">Điểm</a>
                <a class="btn btn-sm btn-outline-primary" href="lophocphan.php?edit=<?= $r['id'] ?>">Sửa</a>
                <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <button name="action" value="delete" class="btn btn-sm btn-outline-danger" data-confirm="Xóa lớp <?= h($r['ma_lhp']) ?>?">Xóa</button></form></td></tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted">Không có dữ liệu.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>
<?php hien_phan_trang($pagination); require __DIR__ . '/../includes/footer.php'; ?>
