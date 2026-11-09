<?php
// THỐNG KÊ: điểm trung bình, tỷ lệ đạt / không đạt, phân bố điểm (bảng + biểu đồ)
require_once __DIR__ . '/includes/auth.php';
$u = require_login('admin', 'giangvien');

$fhk = in('hk'); $fmon = in('mon');
$where = 'd.tong_ket IS NOT NULL AND l.diem_chinh_thuc=1';
$p = [];
if ($u['role'] === 'giangvien') { $where .= ' AND l.giang_vien_id = ?'; $p[] = current_gv_id(); }
if ($fhk !== '') { $where .= ' AND l.hoc_ky_id = ?'; $p[] = (int)$fhk; }
if ($fmon !== '') { $where .= ' AND l.mon_hoc_id = ?'; $p[] = (int)$fmon; }
if (in('nam')!=='') {$where.=' AND hk.nam_hoc=?';$p[]=in('nam');}
if (in('lop')!=='') {$where.=' AND sv.lop=?';$p[]=in('lop');}
if (in('lhp')!=='') {$where.=' AND l.id=?';$p[]=(int)in('lhp');}
$from = 'FROM diem d JOIN dang_ky dk ON dk.id = d.dang_ky_id JOIN lop_hoc_phan l ON l.id = dk.lhp_id
         JOIN mon_hoc m ON m.id = l.mon_hoc_id JOIN hoc_ky hk ON hk.id = l.hoc_ky_id JOIN sinh_vien sv ON sv.id=dk.sinh_vien_id';

// 1) Theo lớp học phần
$st = $pdo->prepare("SELECT l.id, l.ma_lhp, m.ten_mon, CONCAT(hk.ten_hk,' ',hk.nam_hoc) hk,
        COUNT(*) so_sv, ROUND(AVG(d.tong_ket),2) tb, MAX(d.tong_ket) cao, MIN(d.tong_ket) thap,
        SUM(d.ket_qua='Đạt') dat, SUM(d.ket_qua='Không đạt') truot
    $from WHERE $where GROUP BY l.id ORDER BY hk.ma_hk, l.ma_lhp");
$st->execute($p);
$theoLop = $st->fetchAll();

// 2) Theo môn học
$st = $pdo->prepare("SELECT m.ma_mon, m.ten_mon, COUNT(*) so_sv, ROUND(AVG(d.tong_ket),2) tb,
        SUM(d.ket_qua='Đạt') dat, SUM(d.ket_qua='Không đạt') truot
    $from WHERE $where GROUP BY m.id ORDER BY m.ma_mon");
$st->execute($p);
$theoMon = $st->fetchAll();

// 3) Phân bố điểm chữ
$st = $pdo->prepare("SELECT d.tong_ket $from WHERE $where");
$st->execute($p);
$phanBo = ['0–<2' => 0, '2–<4' => 0, '4–<5' => 0, '5–<6' => 0, '6–<8' => 0, '8–10' => 0];
$tong = ['n' => 0, 'sum' => 0];
foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $t) {
    $phanBo[$t<2?'0–<2':($t<4?'2–<4':($t<5?'4–<5':($t<6?'5–<6':($t<8?'6–<8':'8–10'))))]++;
    $tong['n']++;
    $tong['sum'] += $t;
}
$dat = array_sum(array_column($theoLop, 'dat'));
$truot = array_sum(array_column($theoLop, 'truot'));
$tyLe = fn($a, $n) => $n ? round($a * 100 / $n, 1) : 0;

$dsHK = $pdo->query("SELECT id, CONCAT(ten_hk,' (',nam_hoc,')') ten FROM hoc_ky ORDER BY ma_hk DESC")->fetchAll();
$dsMon = $pdo->query("SELECT id, CONCAT(ma_mon,' - ',ten_mon) ten FROM mon_hoc ORDER BY ma_mon")->fetchAll();
$dsNam=$pdo->query('SELECT DISTINCT nam_hoc FROM hoc_ky ORDER BY nam_hoc DESC')->fetchAll();
$dsLop=$pdo->query('SELECT ma_lop FROM lop_hanh_chinh ORDER BY ma_lop')->fetchAll();
$sqlLhp='SELECT id,ma_lhp FROM lop_hoc_phan';$lhpArgs=[];
if($u['role']==='giangvien'){$sqlLhp.=' WHERE giang_vien_id=?';$lhpArgs[]=current_gv_id();}
$st=$pdo->prepare($sqlLhp.' ORDER BY ma_lhp');$st->execute($lhpArgs);$dsLhp=$st->fetchAll();
$st=$pdo->prepare("SELECT COUNT(DISTINCT sv.id) so_sv,COUNT(DISTINCT l.id) so_hp $from WHERE $where");$st->execute($p);$summary=$st->fetch();
$st=$pdo->prepare("SELECT sv.lop,COUNT(*) so_sv,ROUND(AVG(d.tong_ket),2) tb,SUM(d.ket_qua='Đạt') dat,SUM(d.ket_qua='Không đạt') truot $from WHERE $where GROUP BY sv.lop ORDER BY sv.lop");$st->execute($p);$theoHC=$st->fetchAll();
if(in('export')==='csv'){
    require_once __DIR__.'/includes/csv.php';$csv=[];
    foreach($theoLop as $r)$csv[]=[$r['ma_lhp'],$r['ten_mon'],$r['hk'],$r['so_sv'],$r['tb'],$r['cao'],$r['thap'],$r['dat'],$r['truot'],$tyLe($r['dat'],$r['so_sv'])];
    xuat_csv('bao_cao_ket_qua',['Lớp HP','Môn','Học kỳ','SV có điểm','TB','Cao nhất','Thấp nhất','Đạt','Không đạt','Tỷ lệ đạt %'],$csv);
}

$title = 'Thống kê';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Thống kê kết quả học tập <?= $u['role'] === 'giangvien' ? '<small class="text-muted fs-6">(các lớp tôi phụ trách)</small>' : '' ?></h4>
    <button onclick="window.print()" class="btn btn-outline-dark btn-sm"><i class="bi bi-printer me-1"></i>In báo cáo</button>
    <a class="btn btn-success btn-sm" href="?<?= h(http_build_query(array_merge($_GET,['export'=>'csv']))) ?>">Xuất CSV</a>
</div>
<form class="row g-2 mb-3 no-print">
    <div class="col-md-3"><select name="hk" class="form-select"><?= options($dsHK, 'id', 'ten', $fhk, '-- Tất cả học kỳ --') ?></select></div>
    <div class="col-md-3"><select name="mon" class="form-select"><?= options($dsMon, 'id', 'ten', $fmon, '-- Tất cả môn --') ?></select></div>
    <div class="col-md-2"><select name="nam" class="form-select"><?= options($dsNam,'nam_hoc','nam_hoc',in('nam'),'Mọi năm học') ?></select></div>
    <div class="col-md-2"><select name="lop" class="form-select"><?= options($dsLop,'ma_lop','ma_lop',in('lop'),'Mọi lớp hành chính') ?></select></div>
    <div class="col-md-2"><select name="lhp" class="form-select"><?= options($dsLhp,'id','ma_lhp',in('lhp'),'Mọi học phần') ?></select></div>
    <div class="col-auto"><button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Thống kê</button> <a href="thongke.php" class="btn btn-link">Xóa lọc</a></div>
</form>

<div class="row g-3 mb-3">
    <?php foreach ([['Lượt SV có điểm', $tong['n'], 'primary', 'bi-people'], ['Điểm TB chung', $tong['n'] ? round($tong['sum'] / $tong['n'], 2) : '–', 'warning', 'bi-graph-up'],
                    ['Tỷ lệ đạt', $tyLe($dat, $dat + $truot) . '%', 'success', 'bi-check2-circle'], ['Tỷ lệ không đạt', $tyLe($truot, $dat + $truot) . '%', 'danger', 'bi-x-circle']] as [$t, $v, $c, $icon]): ?>
        <div class="col-6 col-md-3"><div class="card stat-card h-100"><span class="stat-icon tone-<?= $c ?>"><i class="bi <?= $icon ?>"></i></span><div><div class="stat-label"><?= $t ?></div><div class="num"><?= $v ?></div></div></div></div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6"><div class="card p-3"><h6><i class="bi bi-bar-chart me-1 text-primary"></i>Điểm trung bình theo lớp học phần</h6><canvas id="cTB" height="220"></canvas></div></div>
    <div class="col-lg-3"><div class="card p-3"><h6><i class="bi bi-pie-chart me-1 text-primary"></i>Tỷ lệ đạt / không đạt</h6><canvas id="cKQ" height="220"></canvas></div></div>
    <div class="col-lg-3"><div class="card p-3"><h6><i class="bi bi-diagram-3 me-1 text-primary"></i>Phân bố điểm tổng kết</h6><canvas id="cPB" height="220"></canvas></div></div>
</div>
<p class="text-muted">Báo cáo chỉ tính điểm chính thức. Tổng <?= $summary['so_sv'] ?> sinh viên, <?= $summary['so_hp'] ?> học phần có điểm; <?= $dat ?> lượt đạt; <?= $truot ?> lượt không đạt.</p>
<div class="row g-3 mb-3"><div class="col-lg-6"><div class="card p-3"><h6>Số lượng đạt / không đạt theo học phần</h6><canvas id="cSL"></canvas></div></div><div class="col-lg-6"><div class="card p-3"><h6>Kết quả theo lớp hành chính</h6><canvas id="cHC"></canvas></div></div></div>

<div class="card p-3 mb-3">
    <h6>Theo lớp học phần</h6>
    <table class="table table-sm table-hover table-nowrap">
        <thead class="table-light"><tr><th>Lớp HP</th><th>Môn</th><th>Học kỳ</th><th>SV có điểm</th><th>Điểm TB</th><th>Cao nhất</th><th>Thấp nhất</th><th>Đạt</th><th>Không đạt</th><th>Tỷ lệ đạt</th><th class="no-print"></th></tr></thead>
        <tbody>
        <?php foreach ($theoLop as $r): ?>
            <tr><td><?= h($r['ma_lhp']) ?></td><td class="cell-entity"><?= h($r['ten_mon']) ?></td><td class="cell-entity"><?= h($r['hk']) ?></td><td><?= $r['so_sv'] ?></td><td><b><?= $r['tb'] ?></b></td>
                <td><?= fd($r['cao']) ?></td><td><?= fd($r['thap']) ?></td><td class="kq-dat"><?= $r['dat'] ?></td><td class="kq-truot"><?= $r['truot'] ?></td>
                <td><?= $tyLe($r['dat'], $r['so_sv']) ?>%</td><td class="no-print"><a href="<?= BASE_URL ?>giangvien/in_bang_diem.php?lhp=<?= $r['id'] ?>" target="_blank" class="small">In</a></td></tr>
        <?php endforeach; ?>
        <?php if (!$theoLop): ?><tr><td colspan="11" class="text-muted text-center">Chưa có dữ liệu.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<div class="card p-3">
    <h6>Theo môn học</h6>
    <table class="table table-sm table-hover table-nowrap">
        <thead class="table-light"><tr><th>Mã môn</th><th>Tên môn</th><th>SV có điểm</th><th>Điểm TB</th><th>Đạt</th><th>Không đạt</th><th>Tỷ lệ đạt</th><th>Tỷ lệ không đạt</th></tr></thead>
        <tbody>
        <?php foreach ($theoMon as $r): ?>
            <tr><td><?= h($r['ma_mon']) ?></td><td class="cell-entity"><?= h($r['ten_mon']) ?></td><td><?= $r['so_sv'] ?></td><td><b><?= $r['tb'] ?></b></td>
                <td><?= $r['dat'] ?></td><td><?= $r['truot'] ?></td><td><?= $tyLe($r['dat'], $r['so_sv']) ?>%</td><td><?= $tyLe($r['truot'], $r['so_sv']) ?>%</td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
// Truyền dữ liệu sang JavaScript dạng JSON, phần vẽ biểu đồ nằm trong assets/js/thongke.js
$duLieuBieuDo = [
    'lop'    => array_column($theoLop, 'ma_lhp'),
    'diemTB' => array_map('floatval', array_column($theoLop, 'tb')),
    'dat'    => (int)$dat,
    'truot'  => (int)$truot,
    'chu'    => array_keys($phanBo),
    'soLuot' => array_values($phanBo),
    'datLop'=>array_column($theoLop,'dat'), 'truotLop'=>array_column($theoLop,'truot'),
    'lopHC'=>array_column($theoHC,'lop'), 'datHC'=>array_column($theoHC,'dat'), 'truotHC'=>array_column($theoHC,'truot'),
];
$extraScripts = '<script>const THONG_KE = ' . json_encode($duLieuBieuDo, JSON_UNESCAPED_UNICODE) . ';</script>'
    . '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>'
    . '<script src="' . BASE_URL . 'assets/js/thongke.js"></script>';
require __DIR__ . '/includes/footer.php';
