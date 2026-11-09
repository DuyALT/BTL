<?php
// TRA CỨU BẢNG ĐIỂM CÁ NHÂN (sinh viên xem của mình; admin xem bất kỳ ai qua ?sv=id)
require_once __DIR__ . '/../includes/auth.php';
$u = require_login('sinhvien', 'admin');

$svId = $u['role'] === 'sinhvien' ? current_sv_id() : (int)in('sv');
$st = $pdo->prepare('SELECT * FROM sinh_vien WHERE id = ?');
$st->execute([$svId]);
$sv = $st->fetch();
if (!$sv) {
    if ($u['role'] === 'admin') { flash('warning', 'Chọn sinh viên để xem bảng điểm.'); redirect('admin/sinhvien.php'); }
    forbidden('Tài khoản chưa được liên kết với hồ sơ sinh viên.');
}

$fhk = in('hk');
$sql = 'SELECT hk.id hk_id, hk.ma_hk, hk.ten_hk, hk.nam_hoc, m.ma_mon, m.ten_mon, m.so_tin_chi, m.ts_cc, m.ts_kt, m.ts_th, m.ts_thi,
        l.ma_lhp, d.tx, d.cc, d.kt, d.th, d.thi, d.tong_ket, d.ket_qua
    FROM dang_ky dk JOIN lop_hoc_phan l ON l.id = dk.lhp_id JOIN mon_hoc m ON m.id = l.mon_hoc_id
    JOIN hoc_ky hk ON hk.id = l.hoc_ky_id LEFT JOIN diem d ON d.dang_ky_id = dk.id
    WHERE dk.sinh_vien_id = ? AND l.diem_chinh_thuc=1';
$p = [$svId];
if ($fhk !== '') { $sql .= ' AND hk.id = ?'; $p[] = (int)$fhk; }
$st = $pdo->prepare($sql . ' ORDER BY hk.ma_hk, m.ma_mon');
$st->execute($p);
$theoHK = [];
foreach ($st as $r) $theoHK[$r['hk_id']][] = $r;

$s = $pdo->prepare('SELECT DISTINCT hk.id, CONCAT(hk.ten_hk," (",hk.nam_hoc,")") ten FROM hoc_ky hk
    JOIN lop_hoc_phan l ON l.hoc_ky_id = hk.id JOIN dang_ky dk ON dk.lhp_id = l.id WHERE dk.sinh_vien_id = ? AND l.diem_chinh_thuc=1 ORDER BY hk.ma_hk');
$s->execute([$svId]);
$dsHK = $s->fetchAll();

/** Điểm TB có trọng số tín chỉ (chỉ tính môn đã có điểm tổng kết) */
function trung_binh(array $mons): array
{
    $tc = 0; $t10 = 0; $t4 = 0; $tichLuy = 0;
    foreach ($mons as $m) {
        if ($m['tong_ket'] === null) continue;
        [, $he4] = quy_doi((float)$m['tong_ket']);
        $tc += $m['so_tin_chi'];
        $t10 += $m['tong_ket'] * $m['so_tin_chi'];
        $t4 += $he4 * $m['so_tin_chi'];
        if ($m['ket_qua'] === 'Đạt') $tichLuy += $m['so_tin_chi'];
    }
    return $tc ? [round($t10 / $tc, 2), round($t4 / $tc, 2), $tichLuy, $tc] : [null, null, $tichLuy, 0];
}
$tatCa = array_merge(...array_values($theoHK ?: [[]]));
[$tb10, $tb4, $tichLuy] = trung_binh($tatCa);

$title = 'Bảng điểm ' . $sv['ma_sv'];
require __DIR__ . '/../includes/header.php';
?>
<p class="no-print"><a href="<?= BASE_URL ?>tra_cuu.php" class="btn btn-outline-primary btn-sm">Tìm kiếm / lọc / xuất CSV</a></p><div class="print-sheet">
<div class="d-flex justify-content-between align-items-start mb-2">
    <div>
        <h4 class="mb-1">BẢNG ĐIỂM CÁ NHÂN</h4>
        <div>Sinh viên: <b><?= h($sv['ho_ten']) ?></b> · Mã SV: <b><?= h($sv['ma_sv']) ?></b> · Lớp: <?= h($sv['lop']) ?>
            <?= $sv['ngay_sinh'] ? ' · Ngày sinh: ' . date('d/m/Y', strtotime($sv['ngay_sinh'])) : '' ?></div>
    </div>
    <div class="no-print d-flex gap-2">
        <form class="d-flex gap-2"><?php if ($u['role'] === 'admin'): ?><input type="hidden" name="sv" value="<?= $svId ?>"><?php endif; ?>
            <select name="hk" class="form-select form-select-sm" onchange="this.form.submit()"><?= options($dsHK, 'id', 'ten', $fhk, 'Tất cả học kỳ') ?></select></form>
        <button onclick="window.print()" class="btn btn-sm btn-outline-dark text-nowrap"><i class="bi bi-printer me-1"></i>In</button>
    </div>
</div>

<div class="row g-2 mb-3">
    <div class="col-6 col-md-3"><div class="card stat-card h-100"><div><div class="stat-label">TB tích lũy (hệ 10)</div><div class="num"><?= $tb10 ?? '–' ?></div></div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card h-100"><div><div class="stat-label">TB tích lũy (hệ 4)</div><div class="num"><?= $tb4 ?? '–' ?></div></div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card h-100"><div><div class="stat-label">Tín chỉ tích lũy</div><div class="num"><?= $tichLuy ?></div></div></div></div>
</div>
<div class="mb-3 no-print"><label class="form-label" for="studentYearFilter">Lọc năm học trong bảng điểm đang xem</label><select id="studentYearFilter" class="form-select" style="max-width:240px"><option value="">Tất cả năm học</option><?php foreach(array_unique(array_column($tatCa,'nam_hoc')) as $year): ?><option><?= h($year) ?></option><?php endforeach; ?></select></div>

<?php foreach ($theoHK as $mons): [$h10, $h4, $tl, $tc] = trung_binh($mons); ?>
<div class="card p-3 mb-3" data-school-year="<?= h($mons[0]['nam_hoc']) ?>">
    <h6><?= h($mons[0]['ten_hk'] . ' – Năm học ' . $mons[0]['nam_hoc']) ?></h6>
    <div class="table-responsive">
    <table class="table table-sm table-bordered mb-1">
        <thead class="table-light text-center"><tr><th>Mã học phần</th><th>Mã môn</th><th class="text-start">Tên môn</th><th>TC</th><th>Thường xuyên</th><th>Giữa HP</th><th>Cuối HP</th><th>Tổng kết</th><th>Hệ 4</th><th>Kết quả</th></tr></thead>
        <tbody>
        <?php foreach ($mons as $m): [$chu, $he4] = quy_doi($m['tong_ket'] === null ? null : (float)$m['tong_ket']); ?>
            <tr class="text-center"><td><?= h($m['ma_lhp']) ?></td><td><?= h($m['ma_mon']) ?></td><td class="text-start cell-entity"><?= h($m['ten_mon']) ?></td><td><?= $m['so_tin_chi'] ?></td>
                <?php foreach (array_keys(COT_DIEM) as $c): ?><td><?= h(hien_thi_diem($m, $c)) ?></td><?php endforeach; ?>
                <td class="fw-bold"><?= fd($m['tong_ket']) ?></td><td><?= $he4 === null ? '' : number_format($he4, 1) ?></td>
                <td class="<?= $m['ket_qua'] === null ? 'text-muted' : ($m['ket_qua'] === 'Đạt' ? 'kq-dat' : 'kq-truot') ?>"><?= h($m['ket_qua'] ?? 'Chưa có') ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <small class="text-muted">TB học kỳ: <b><?= $h10 ?? '–' ?></b> (hệ 10) · <b><?= $h4 ?? '–' ?></b> (hệ 4) · Tín chỉ đạt: <?= $tl ?>/<?= array_sum(array_column($mons, 'so_tin_chi')) ?></small>
    </div>
</div>
<?php endforeach; ?>
<?php if (!$theoHK): ?><div class="alert alert-info">Chưa có điểm chính thức được công bố.</div><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
