<?php
require_once __DIR__.'/../includes/auth.php';$u=require_login('sinhvien');
$st=$pdo->prepare('SELECT s.*,l.ten_lop FROM sinh_vien s LEFT JOIN lop_hanh_chinh l ON l.ma_lop=s.lop WHERE s.user_id=?');$st->execute([$u['id']]);$sv=$st->fetch();
if(!$sv) forbidden('Tài khoản chưa liên kết hồ sơ sinh viên.');
$st=$pdo->prepare('SELECT l.ma_lhp,m.ten_mon,hk.ten_hk,hk.nam_hoc,l.diem_chinh_thuc,d.ket_qua FROM dang_ky dk JOIN lop_hoc_phan l ON l.id=dk.lhp_id JOIN mon_hoc m ON m.id=l.mon_hoc_id JOIN hoc_ky hk ON hk.id=l.hoc_ky_id LEFT JOIN diem d ON d.dang_ky_id=dk.id WHERE dk.sinh_vien_id=? ORDER BY hk.ma_hk DESC,l.ma_lhp');$st->execute([$sv['id']]);$rows=$st->fetchAll();
$dat=0;$truot=0;foreach($rows as $r)if($r['diem_chinh_thuc']){$dat+=$r['ket_qua']==='Đạt';$truot+=$r['ket_qua']==='Không đạt';}
$title='Tổng quan sinh viên';require __DIR__.'/../includes/header.php';
?>
<div class="profile-banner"><div class="profile-avatar" aria-hidden="true"><?= h(mb_substr($sv['ho_ten'],0,1)) ?></div><div><div class="page-eyebrow">HỒ SƠ SINH VIÊN</div><h2>Xin chào, <?= h($sv['ho_ten']) ?>!</h2><p><?= h($sv['ma_sv']) ?> · <?= h($sv['lop']) ?> · <?= h($sv['trang_thai_hoc']) ?></p></div></div>
<div class="card p-3 mb-3"><h5>Thông tin cá nhân</h5><div class="row g-2">
<?php foreach(['ma_sv'=>'Mã SV','ho_ten'=>'Họ tên','ngay_sinh'=>'Ngày sinh','gioi_tinh'=>'Giới tính','lop'=>'Lớp hành chính','khoa_hoc'=>'Khóa học','email'=>'Email','trang_thai_hoc'=>'Trạng thái học tập'] as $k=>$label): ?><div class="col-md-6"><span class="text-muted"><?= $label ?>:</span> <?= h($sv[$k]??'') ?></div><?php endforeach; ?></div></div>
<div class="row g-3 mb-3"><?php foreach(['Học phần đăng ký'=>count($rows),'Đã có kết quả'=>$dat+$truot,'Học phần đạt'=>$dat,'Học phần không đạt'=>$truot] as $t=>$v): ?><div class="col-6 col-lg-3"><div class="card p-3"><span class="stat-label text-muted small"><?= $t ?></span><strong class="fs-3 mt-2"><?= $v ?></strong></div></div><?php endforeach; ?></div>
<p><a href="bang_diem.php" class="btn btn-primary">Bảng điểm cá nhân</a> <a href="<?= BASE_URL ?>tra_cuu.php" class="btn btn-outline-primary">Tìm kiếm kết quả học tập</a></p>
<div class="card table-responsive"><table class="table mb-0"><thead><tr><th>Mã học phần</th><th>Môn học</th><th>Học kỳ</th><th>Năm học</th><th>Kết quả</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?= h($r['ma_lhp']) ?></td><td class="cell-entity"><?= h($r['ten_mon']) ?></td><td class="cell-entity"><?= h($r['ten_hk']) ?></td><td class="cell-entity"><?= h($r['nam_hoc']) ?></td><td><?= $r['diem_chinh_thuc']?h($r['ket_qua']):'Chưa công bố' ?></td></tr><?php endforeach; ?></tbody></table></div>
<div class="card p-3 mt-3"><h6>Kết quả theo học kỳ</h6><p class="text-muted small">Số học phần có điểm chính thức, theo kết quả đạt và không đạt.</p><div style="position:relative;max-height:320px"><canvas id="studentResultsChart" height="110"></canvas></div><p id="studentChartEmpty" class="text-muted small" hidden>Chưa có điểm chính thức được công bố.</p></div>
<?php
$studentPeriods=[];
foreach($rows as $r) if($r['diem_chinh_thuc'] && $r['ket_qua']) {
    $label=$r['ten_hk'].' '.$r['nam_hoc'];
    if(!isset($studentPeriods[$label]))$studentPeriods[$label]=['dat'=>0,'truot'=>0];
    $studentPeriods[$label][$r['ket_qua']==='Đạt'?'dat':'truot']++;
}
$extraScripts='<script type="application/json" id="studentChartData">'.json_encode($studentPeriods,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE).'</script><script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script><script src="'.BASE_URL.'assets/js/student-dashboard.js"></script>';
require __DIR__.'/../includes/footer.php'; ?>
