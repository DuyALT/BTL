<?php
require_once __DIR__ . '/../includes/auth.php';
require_login('admin');
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    $ma=strtoupper(trim(in('ma_lop'))); $old=trim(in('old')); $ten=trim(in('ten_lop')); $khoa=trim(in('khoa_hoc'));
    try {
        if (in('action')==='delete') {
            $pdo->prepare('DELETE FROM lop_hanh_chinh WHERE ma_lop=?')->execute([$ma]);
        } else {
            if (!preg_match('/^[A-Z0-9_-]{1,20}$/',$ma) || $ten==='' || mb_strlen($ten)>100 || mb_strlen($khoa)>30) throw new RuntimeException('Mã lớp tối đa 20 ký tự chữ/số, tên lớp bắt buộc, khóa học tối đa 30 ký tự.');
            if ($old!=='') $pdo->prepare('UPDATE lop_hanh_chinh SET ma_lop=?,ten_lop=?,khoa_hoc=? WHERE ma_lop=?')->execute([$ma,$ten,$khoa,$old]);
            else $pdo->prepare('INSERT INTO lop_hanh_chinh VALUES (?,?,?)')->execute([$ma,$ten,$khoa]);
        }
        flash('success','Đã cập nhật lớp hành chính.');
    } catch (PDOException $e) { flash('danger','Mã lớp trùng hoặc lớp đang có sinh viên nên không thể xóa.'); }
    catch (RuntimeException $e) { flash('danger',$e->getMessage()); }
    redirect('admin/lop.php');
}
$q=in('q'); $st=$pdo->prepare('SELECT l.*,COUNT(s.id) si_so FROM lop_hanh_chinh l LEFT JOIN sinh_vien s ON s.lop=l.ma_lop WHERE l.ma_lop LIKE ? OR l.ten_lop LIKE ? GROUP BY l.ma_lop ORDER BY l.ma_lop'); $st->execute(["%$q%","%$q%"]); $rows=$st->fetchAll();
$edit=null; if (in('edit')!=='') { $st=$pdo->prepare('SELECT * FROM lop_hanh_chinh WHERE ma_lop=?');$st->execute([in('edit')]);$edit=$st->fetch(); }
require_once __DIR__ . '/../includes/pagination.php';
[$rows,$pagination]=phan_trang($rows);
$title='Lớp hành chính';require __DIR__.'/../includes/header.php';
?>
<h4>Lớp hành chính</h4>
<form method="post" class="card p-3 mb-3"><div class="row g-2"><?= csrf_field() ?><input type="hidden" name="old" value="<?= h($edit['ma_lop']??'') ?>">
<div class="col-md-2"><label class="form-label">Mã lớp</label><input required maxlength="20" name="ma_lop" class="form-control" value="<?= h($edit['ma_lop']??'') ?>"></div>
<div class="col-md-4"><label class="form-label">Tên lớp</label><input required maxlength="100" name="ten_lop" class="form-control" value="<?= h($edit['ten_lop']??'') ?>"></div>
<div class="col-md-3"><label class="form-label">Khóa học</label><input maxlength="30" name="khoa_hoc" class="form-control" value="<?= h($edit['khoa_hoc']??'') ?>"></div>
<div class="col-auto align-self-end"><button class="btn btn-success">Lưu lớp</button> <a href="lop.php" class="btn btn-secondary">Hủy</a></div></div></form>
<form class="d-flex gap-2 mb-3"><input name="q" class="form-control" placeholder="Tìm mã hoặc tên lớp" value="<?= h($q) ?>"><button class="btn btn-primary">Tìm</button></form>
<div class="card table-responsive"><table class="table mb-0"><thead><tr><th>Mã lớp</th><th>Tên lớp</th><th>Khóa học</th><th>Sĩ số</th><th>Thao tác</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?= h($r['ma_lop']) ?></td><td class="cell-entity"><?= h($r['ten_lop']) ?></td><td><?= h($r['khoa_hoc']) ?></td><td><?= $r['si_so'] ?></td><td class="text-nowrap"><a href="sinhvien.php?lop=<?= urlencode($r['ma_lop']) ?>" class="btn btn-sm btn-outline-info">Sinh viên</a> <a href="lop.php?edit=<?= urlencode($r['ma_lop']) ?>" class="btn btn-sm btn-outline-primary">Sửa</a><form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="ma_lop" value="<?= h($r['ma_lop']) ?>"><button class="btn btn-sm btn-outline-danger" name="action" value="delete" data-confirm="Xóa lớp này?">Xóa</button></form></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php hien_phan_trang($pagination); require __DIR__.'/../includes/footer.php'; ?>
