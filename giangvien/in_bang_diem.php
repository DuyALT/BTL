<?php
// IN BẢNG ĐIỂM: theo lớp học phần (?lhp=) hoặc theo lớp sinh hoạt + học kỳ (?lop=&hk=)
require_once __DIR__ . '/../includes/auth.php';
$u = require_login('admin', 'giangvien');

$lhpId = (int)in('lhp');
$lop = in('lop');
$hkId = (int)in('hk');

// Danh sách cho form chọn
$sqlL = "SELECT l.id, CONCAT(l.ma_lhp,' – ',m.ten_mon) ten FROM lop_hoc_phan l JOIN mon_hoc m ON m.id = l.mon_hoc_id";
if ($u['role'] === 'giangvien') { $s = $pdo->prepare($sqlL . ' WHERE l.giang_vien_id = ? ORDER BY l.ma_lhp'); $s->execute([current_gv_id()]); }
else $s = $pdo->query($sqlL . ' ORDER BY l.ma_lhp');
$dsLhp = $s->fetchAll();
$dsLop = $pdo->query('SELECT DISTINCT lop FROM sinh_vien ORDER BY lop')->fetchAll();
$dsHK = $pdo->query("SELECT id, CONCAT(ten_hk,' (',nam_hoc,')') ten FROM hoc_ky ORDER BY ma_hk DESC")->fetchAll();

$title = 'In bảng điểm';
require __DIR__ . '/../includes/header.php';
?>
<div class="no-print card p-3 mb-3">
    <div class="row g-3">
        <form class="col-md-6 d-flex gap-2">
            <select name="lhp" class="form-select"><?= options($dsLhp, 'id', 'ten', $lhpId, '-- Theo lớp học phần --') ?></select>
            <button class="btn btn-primary text-nowrap">Xem</button>
        </form>
        <?php if ($u['role'] === 'admin'): ?>
        <form class="col-md-6 d-flex gap-2">
            <select name="lop" class="form-select"><?= options($dsLop, 'lop', 'lop', $lop, '-- Lớp sinh hoạt --') ?></select>
            <select name="hk" class="form-select"><?= options($dsHK, 'id', 'ten', $hkId, '-- Học kỳ --') ?></select>
            <button class="btn btn-primary text-nowrap">Xem</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php
$tieuDe = '<div class="print-header mb-3"><div><b>TRƯỜNG ĐẠI HỌC ...</b><br>PHÒNG ĐÀO TẠO</div>
    <div><b>CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</b><br>Độc lập – Tự do – Hạnh phúc</div></div>';

// ===== Chế độ 1: theo lớp học phần =====
if ($lhpId):
    if (!co_quyen_lhp($lhpId)) { echo '<div class="alert alert-danger">Bạn không có quyền in lớp này.</div>'; require __DIR__ . '/../includes/footer.php'; exit; }
    $st = $pdo->prepare('SELECT l.*, m.*, hk.ten_hk, hk.nam_hoc, g.ho_ten gv FROM lop_hoc_phan l JOIN mon_hoc m ON m.id = l.mon_hoc_id
        JOIN hoc_ky hk ON hk.id = l.hoc_ky_id LEFT JOIN giang_vien g ON g.id = l.giang_vien_id WHERE l.id = ?');
    $st->execute([$lhpId]);
    $l = $st->fetch();
    $st = $pdo->prepare('SELECT s.ma_sv, s.ho_ten, s.ngay_sinh, s.lop, d.* FROM dang_ky dk JOIN sinh_vien s ON s.id = dk.sinh_vien_id
        LEFT JOIN diem d ON d.dang_ky_id = dk.id WHERE dk.lhp_id = ? ORDER BY s.ma_sv');
    $st->execute([$lhpId]);
    $rows = $st->fetchAll();
    $dat = count(array_filter($rows, fn($r) => $r['ket_qua'] === 'Đạt'));
    $truot = count(array_filter($rows, fn($r) => $r['ket_qua'] === 'Không đạt'));
?>
<div class="print-sheet card">
    <?= $tieuDe ?>
    <h5 class="text-center mb-1">BẢNG ĐIỂM LỚP HỌC PHẦN</h5>
    <p class="text-center"><?= $l['diem_chinh_thuc']?'Điểm chính thức':'BẢN NHÁP — chưa công bố' ?></p>
    <p class="no-print"><a class="btn btn-success btn-sm" href="<?= BASE_URL ?>tra_cuu.php?lhp=<?= $lhpId ?>&amp;export=csv">Xuất CSV học phần</a></p>
    <p class="text-center mb-3">Môn: <b><?= h($l['ten_mon']) ?></b> (<?= h($l['ma_mon']) ?>, <?= $l['so_tin_chi'] ?> TC) · Lớp: <b><?= h($l['ma_lhp']) ?></b> · <?= h($l['ten_hk'] . ' – ' . $l['nam_hoc']) ?><br>
        Giảng viên: <?= h($l['gv']) ?></p>
    <table class="table table-bordered table-sm">
        <thead class="text-center"><tr><th>STT</th><th>Mã SV</th><th>Họ và tên</th><th>Ngày sinh</th><th>Lớp</th>
            <?php foreach (COT_DIEM as $c => $t) echo '<th>' . $t . '<br>(' . HE_SO_DIEM[$c] . ')</th>'; ?>
            <th>Tổng kết</th><th>Kết quả</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $i => $r): ?>
            <tr class="text-center"><td><?= $i + 1 ?></td><td><?= h($r['ma_sv']) ?></td><td class="text-start cell-person"><?= h($r['ho_ten']) ?></td>
                <td><?= $r['ngay_sinh'] ? date('d/m/Y', strtotime($r['ngay_sinh'])) : '' ?></td><td><?= h($r['lop']) ?></td>
                <?php foreach (COT_DIEM as $c => $t) echo '<td>' . h(hien_thi_diem($r, $c)) . '</td>'; ?>
                <td><b><?= fd($r['tong_ket']) ?></b></td><td><?= h($r['ket_qua']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p>Sĩ số: <?= count($rows) ?> · Đạt: <?= $dat ?> · Không đạt: <?= $truot ?> · Chưa có điểm: <?= count($rows) - $dat - $truot ?></p>
    <p class="text-end fst-italic">Ngày <?= date('d') ?> tháng <?= date('m') ?> năm <?= date('Y') ?></p>
    <div class="sign-row"><div><b>Trưởng bộ môn</b><br><i>(Ký, ghi rõ họ tên)</i></div><div><b>Giảng viên</b><br><i>(Ký, ghi rõ họ tên)</i><br><br><br><br><?= h($l['gv']) ?></div></div>
    <div class="no-print mt-3"><button onclick="window.print()" class="btn btn-dark"><i class="bi bi-printer me-1"></i>In / Lưu PDF</button></div>
</div>

<?php
// ===== Chế độ 2: theo lớp sinh hoạt + học kỳ (admin) =====
elseif ($lop !== '' && $hkId && $u['role'] === 'admin'):
    $st = $pdo->prepare('SELECT DISTINCT m.id, m.ma_mon, m.ten_mon, m.so_tin_chi FROM dang_ky dk JOIN sinh_vien s ON s.id = dk.sinh_vien_id
        JOIN lop_hoc_phan l ON l.id = dk.lhp_id JOIN mon_hoc m ON m.id = l.mon_hoc_id WHERE s.lop = ? AND l.hoc_ky_id = ? ORDER BY m.ma_mon');
    $st->execute([$lop, $hkId]);
    $mons = $st->fetchAll();
    $st = $pdo->prepare('SELECT id, ma_sv, ho_ten FROM sinh_vien WHERE lop = ? ORDER BY ma_sv');
    $st->execute([$lop]);
    $svs = $st->fetchAll();
    $st = $pdo->prepare('SELECT dk.sinh_vien_id, l.mon_hoc_id, d.tong_ket FROM dang_ky dk JOIN sinh_vien s ON s.id = dk.sinh_vien_id
        JOIN lop_hoc_phan l ON l.id = dk.lhp_id JOIN diem d ON d.dang_ky_id = dk.id WHERE s.lop = ? AND l.hoc_ky_id = ? AND l.diem_chinh_thuc=1');
    $st->execute([$lop, $hkId]);
    $bang = [];
    foreach ($st as $r) $bang[$r['sinh_vien_id']][$r['mon_hoc_id']] = $r['tong_ket'];
    $tenHK = array_column($dsHK, 'ten', 'id')[$hkId] ?? '';
?>
<div class="print-sheet card">
    <?= $tieuDe ?>
    <h5 class="text-center mb-1">BẢNG ĐIỂM TỔNG HỢP LỚP <?= h($lop) ?></h5>
    <p class="text-center"><?= h($tenHK) ?></p>
    <table class="table table-bordered table-sm">
        <thead class="text-center"><tr><th>STT</th><th>Mã SV</th><th>Họ và tên</th>
            <?php foreach ($mons as $m): ?><th><?= h($m['ten_mon']) ?><br>(<?= $m['so_tin_chi'] ?> TC)</th><?php endforeach; ?><th>TB học kỳ</th></tr></thead>
        <tbody>
        <?php foreach ($svs as $i => $s):
            $tc = 0; $sum = 0; ?>
            <tr class="text-center"><td><?= $i + 1 ?></td><td><?= h($s['ma_sv']) ?></td><td class="text-start cell-person"><?= h($s['ho_ten']) ?></td>
            <?php foreach ($mons as $m):
                $t = $bang[$s['id']][$m['id']] ?? null;
                if ($t !== null) { $tc += $m['so_tin_chi']; $sum += $t * $m['so_tin_chi']; } ?>
                <td class="<?= $t !== null && $t < 4 ? 'kq-truot' : '' ?>"><?= fd($t) ?></td>
            <?php endforeach; ?>
                <td><b><?= $tc ? number_format($sum / $tc, 2) : '' ?></b></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p class="text-end fst-italic">Ngày <?= date('d') ?> tháng <?= date('m') ?> năm <?= date('Y') ?></p>
    <div class="sign-row"><div><b>Phòng Đào tạo</b><br><i>(Ký, ghi rõ họ tên)</i></div><div><b>Người lập biểu</b><br><i>(Ký, ghi rõ họ tên)</i></div></div>
    <div class="no-print mt-3"><button onclick="window.print()" class="btn btn-dark"><i class="bi bi-printer me-1"></i>In / Lưu PDF</button></div>
</div>
<?php else: ?>
    <div class="alert alert-info">Chọn lớp học phần<?= $u['role'] === 'admin' ? ' hoặc lớp sinh hoạt + học kỳ' : '' ?> để xem và in bảng điểm.</div>
<?php endif;
require __DIR__ . '/../includes/footer.php';
