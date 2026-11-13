<?php
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/pagination.php';
require_once __DIR__.'/includes/csv.php';
$u=require_login(); $where=['1=1'];$args=[];
if ($u['role']==='sinhvien') { $where[]='s.user_id=? AND l.diem_chinh_thuc=1';$args[]=$u['id']; }
if ($u['role']==='giangvien') { $where[]='l.giang_vien_id=?';$args[]=current_gv_id(); }
$q=in('q');if($q!=='') { $where[]='(s.ma_sv LIKE ? OR s.ho_ten LIKE ?)';array_push($args,"%$q%","%$q%"); }
foreach(['lop'=>'s.lop','lhp'=>'l.id','mon'=>'m.id','hk'=>'hk.id','nam'=>'hk.nam_hoc','kq'=>'d.ket_qua'] as $key=>$column) if(in($key)!==''){$where[]="$column=?";$args[]=in($key);}
$sorts=['ma'=>'s.ma_sv,l.ma_lhp','ten'=>'s.ho_ten,s.ma_sv','diem'=>'d.tong_ket DESC,s.ma_sv'];$sort=$sorts[in('sort')]??$sorts['ma'];
$st=$pdo->prepare('SELECT s.ma_sv,s.ho_ten,s.lop,l.ma_lhp,l.diem_chinh_thuc,m.ten_mon,m.so_tin_chi,hk.ten_hk,hk.nam_hoc,d.* FROM dang_ky dk JOIN sinh_vien s ON s.id=dk.sinh_vien_id JOIN lop_hoc_phan l ON l.id=dk.lhp_id JOIN mon_hoc m ON m.id=l.mon_hoc_id JOIN hoc_ky hk ON hk.id=l.hoc_ky_id LEFT JOIN diem d ON d.dang_ky_id=dk.id WHERE '.implode(' AND ',$where).' ORDER BY '.$sort);
$st->execute($args);$all=$st->fetchAll();
if(in('export')==='csv') {
    $csv=[];foreach($all as $r)$csv[]=[$r['ma_sv'],$r['ho_ten'],$r['lop'],$r['ma_lhp'],$r['ten_mon'],$r['so_tin_chi'],hien_thi_diem($r,'cc'),fd($r['kt']),fd($r['thi']),fd($r['tong_ket']),$r['ket_qua'],$r['ten_hk'],$r['nam_hoc'],$r['diem_chinh_thuc']?'Chính thức':'Bản nháp'];
    xuat_csv('bang_diem',['Mã SV','Họ tên','Lớp','Mã HP','Tên môn','TC','Thường xuyên','Giữa HP','Cuối HP','Tổng kết','Kết quả','Học kỳ','Năm học','Trạng thái'],$csv);
}
[$rows,$pagination]=phan_trang($all);
$dsLop=$pdo->query('SELECT ma_lop FROM lop_hanh_chinh ORDER BY ma_lop')->fetchAll();
$dsHK=$pdo->query("SELECT id,CONCAT(ten_hk,' ',nam_hoc) ten FROM hoc_ky ORDER BY ma_hk DESC")->fetchAll();
$dsNam=$pdo->query('SELECT DISTINCT nam_hoc FROM hoc_ky ORDER BY nam_hoc DESC')->fetchAll();
$dsMon=$pdo->query("SELECT id,CONCAT(ma_mon,' - ',ten_mon) ten FROM mon_hoc ORDER BY ma_mon")->fetchAll();
$sql='SELECT l.id,l.ma_lhp FROM lop_hoc_phan l';$p=[];
if($u['role']==='giangvien'){$sql.=' WHERE l.giang_vien_id=?';$p[]=current_gv_id();}
if($u['role']==='sinhvien'){$sql.=' JOIN dang_ky dk ON dk.lhp_id=l.id JOIN sinh_vien s ON s.id=dk.sinh_vien_id WHERE s.user_id=?';$p[]=$u['id'];}
$st=$pdo->prepare($sql.' ORDER BY l.ma_lhp');$st->execute($p);$dsLhp=$st->fetchAll();
$title='Tra cứu bảng điểm';require __DIR__.'/includes/header.php';
$export=$_GET;$export['export']='csv';unset($export['page']);
?>
<div class="d-flex justify-content-between gap-2 mb-3"><h4>Tra cứu bảng điểm</h4><div class="no-print"><a class="btn btn-success btn-sm" href="?<?= h(http_build_query($export)) ?>">Xuất CSV</a> <button class="btn btn-outline-dark btn-sm" onclick="window.print()">In bảng điểm</button></div></div>
<p><?= $u['role']==='sinhvien'?'Chỉ hiển thị điểm đã lưu chính thức của bạn.':'' ?></p>
<form class="row g-2 mb-3 no-print">
<div class="col-md-3"><input class="form-control" name="q" placeholder="Mã sinh viên / họ tên" value="<?= h($q) ?>"></div>
<?php if($u['role']!=='sinhvien'): ?><div class="col-md-2"><select name="lop" class="form-select"><?= options($dsLop,'ma_lop','ma_lop',in('lop'),'Mọi lớp') ?></select></div><?php endif; ?>
<div class="col-md-2"><select name="lhp" class="form-select"><?= options($dsLhp,'id','ma_lhp',in('lhp'),'Mọi học phần') ?></select></div>
<div class="col-md-3"><select name="mon" class="form-select"><?= options($dsMon,'id','ten',in('mon'),'Mọi môn học') ?></select></div>
<div class="col-md-3"><select name="hk" class="form-select"><?= options($dsHK,'id','ten',in('hk'),'Mọi học kỳ') ?></select></div>
<div class="col-md-2"><select name="nam" class="form-select"><?= options($dsNam,'nam_hoc','nam_hoc',in('nam'),'Mọi năm học') ?></select></div>
<div class="col-md-2"><select name="kq" class="form-select"><option value="">Mọi kết quả</option><?php foreach(['Đạt','Không đạt'] as $v): ?><option <?= in('kq')===$v?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><select name="sort" class="form-select"><?php foreach(['ma'=>'Theo mã SV','ten'=>'Theo họ tên','diem'=>'Điểm giảm dần'] as $k=>$v): ?><option value="<?= $k ?>" <?= in('sort')===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select></div>
<div class="col-auto"><button class="btn btn-primary">Tra cứu</button> <a href="tra_cuu.php" class="btn btn-link">Xóa lọc</a></div></form>
<div class="card table-responsive"><table class="table table-sm table-bordered mb-0"><thead><tr><th>Mã SV</th><th>Họ tên</th><th>Lớp</th><th>Học phần</th><th>Học kỳ / năm</th><th>TX</th><th>Giữa HP</th><th>Cuối HP</th><th>Tổng kết</th><th>Kết quả</th><th>Trạng thái</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?= h($r['ma_sv']) ?></td><td><?= h($r['ho_ten']) ?></td><td><?= h($r['lop']) ?></td><td><?= h($r['ma_lhp'].' — '.$r['ten_mon']) ?></td><td><?= h($r['ten_hk'].' '.$r['nam_hoc']) ?></td><td><?= h(hien_thi_diem($r,'cc')) ?></td><td><?= fd($r['kt']) ?></td><td><?= fd($r['thi']) ?></td><td><b><?= fd($r['tong_ket']) ?></b></td><td><?= h($r['ket_qua']??'Chưa đủ điểm') ?></td><td><?= $r['diem_chinh_thuc']?'Chính thức':'Bản nháp' ?></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="11" class="text-center text-muted">Chưa có kết quả phù hợp.</td></tr><?php endif; ?>
</tbody></table></div><?php hien_phan_trang($pagination); ?>
<?php require __DIR__.'/includes/footer.php'; ?>
