<?php
require_once __DIR__.'/../includes/auth.php';require_login('admin');
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();$nam=in('nam_hoc');
    try{
        if(in('action')==='delete'){
            $st=$pdo->prepare('SELECT COUNT(*) FROM hoc_ky WHERE nam_hoc=?');$st->execute([$nam]);if($st->fetchColumn())throw new RuntimeException('Năm học đang có học kỳ nên không thể xóa.');
            $pdo->prepare('DELETE FROM nam_hoc WHERE nam_hoc=?')->execute([$nam]);
        } else {
            if(!preg_match('/^(\d{4})-(\d{4})$/',$nam,$m) || (int)$m[2]-(int)$m[1]!==1)throw new RuntimeException('Năm học phải có dạng 2026-2027.');
            $pdo->prepare('INSERT INTO nam_hoc VALUES (?)')->execute([$nam]);
        }flash('success','Đã cập nhật năm học.');
    }catch(PDOException $e){flash('danger','Năm học đã tồn tại.');}catch(RuntimeException $e){flash('danger',$e->getMessage());}redirect('admin/namhoc.php');
}
$rows=$pdo->query('SELECT n.nam_hoc,COUNT(h.id) so_hk FROM nam_hoc n LEFT JOIN hoc_ky h ON h.nam_hoc=n.nam_hoc GROUP BY n.nam_hoc ORDER BY n.nam_hoc DESC')->fetchAll();$title='Năm học';require __DIR__.'/../includes/header.php';
?>
<h4>Năm học</h4><form method="post" class="d-flex gap-2 mb-3"><?= csrf_field() ?><input required name="nam_hoc" class="form-control" placeholder="2026-2027" pattern="[0-9]{4}-[0-9]{4}"><button class="btn btn-success text-nowrap">Thêm năm học</button></form>
<div class="card table-responsive"><table class="table mb-0"><thead><tr><th>Năm học</th><th>Số học kỳ</th><th>Thao tác</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td class="cell-entity"><?= h($r['nam_hoc']) ?></td><td><?= $r['so_hk'] ?></td><td><a href="hocky.php?q=<?= urlencode($r['nam_hoc']) ?>" class="btn btn-sm btn-outline-primary">Xem học kỳ</a> <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="nam_hoc" value="<?= h($r['nam_hoc']) ?>"><button class="btn btn-sm btn-outline-danger" name="action" value="delete" data-confirm="Xóa năm học này?">Xóa</button></form></td></tr><?php endforeach; ?></tbody></table></div>
<?php require __DIR__.'/../includes/footer.php'; ?>
