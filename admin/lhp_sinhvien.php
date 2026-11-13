<?php
// QUẢN LÝ DANH SÁCH SINH VIÊN THEO LỚP HỌC PHẦN
require_once __DIR__ . '/../includes/auth.php';
require_login('admin');
$id = (int)in('id');

$st = $pdo->prepare('SELECT l.*, m.ma_mon, m.ten_mon, hk.ten_hk, hk.nam_hoc, g.ho_ten gv FROM lop_hoc_phan l
    JOIN mon_hoc m ON m.id = l.mon_hoc_id JOIN hoc_ky hk ON hk.id = l.hoc_ky_id LEFT JOIN giang_vien g ON g.id = l.giang_vien_id WHERE l.id = ?');
$st->execute([$id]);
$lhp = $st->fetch();
if (!$lhp) { flash('danger', 'Không tìm thấy lớp học phần.'); redirect('admin/lophocphan.php'); }

function si_so(PDO $pdo, int $id): int
{
    $s = $pdo->prepare('SELECT COUNT(*) FROM dang_ky WHERE lhp_id = ?');
    $s->execute([$id]);
    return (int)$s->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pdo->beginTransaction();
    $lock=$pdo->prepare('SELECT * FROM lop_hoc_phan WHERE id=? FOR UPDATE');$lock->execute([$id]);$lhp=array_merge($lhp,$lock->fetch());
    if ($lhp['diem_chinh_thuc']) { $pdo->rollBack();flash('danger','Danh sách đã có điểm chính thức, không được thêm hoặc xóa sinh viên.');redirect("admin/lhp_sinhvien.php?id=$id"); }
    $action = $_POST['action'] ?? '';
    if ($action === 'add' || $action === 'add_lop') {
        if ($action === 'add_lop') {
            $s = $pdo->prepare('SELECT id FROM sinh_vien WHERE lop = ? AND id NOT IN (SELECT sinh_vien_id FROM dang_ky WHERE lhp_id = ?)');
            $s->execute([trim($_POST['lop'] ?? ''), $id]);
            $ids = $s->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $ids = array_map('intval', (array)($_POST['sv'] ?? []));
        }
        $con = $lhp['si_so_toi_da'] - si_so($pdo, $id);
        if (!$ids) {
            flash('warning', 'Chưa chọn sinh viên nào.');
        } elseif (count($ids) > $con) {
            flash('danger', "Vượt sĩ số tối đa: chỉ còn $con chỗ.");
        } else {
            $ins = $pdo->prepare('INSERT IGNORE INTO dang_ky (lhp_id, sinh_vien_id) SELECT ?, id FROM sinh_vien WHERE id = ?');
            $insD = $pdo->prepare('INSERT INTO diem (dang_ky_id) VALUES (?)');
            $n = 0;
            foreach ($ids as $sv) {
                $ins->execute([$id, $sv]);
                if ($ins->rowCount()) { $insD->execute([$pdo->lastInsertId()]); $n++; }
            }
            flash('success', "Đã thêm $n sinh viên vào lớp.");
        }
    } elseif ($action === 'remove') {
        $dk = (int)$_POST['dk'];
        $s = $pdo->prepare('SELECT COUNT(*) FROM dang_ky dk JOIN lop_hoc_phan l ON l.id=dk.lhp_id WHERE dk.id=? AND l.diem_chinh_thuc=1');
        $s->execute([$dk]);
        if ($s->fetchColumn()) {
            flash('danger', 'Không thể xóa sinh viên có điểm chính thức.');
        } else {
            $pdo->prepare('DELETE FROM dang_ky WHERE id = ? AND lhp_id = ?')->execute([$dk, $id]);
            flash('success', 'Đã xóa sinh viên khỏi lớp.');
        }
    }
    $pdo->prepare('UPDATE lop_hoc_phan SET phien_ban=phien_ban+1 WHERE id=?')->execute([$id]);
    $pdo->commit();
    redirect("admin/lhp_sinhvien.php?id=$id");
}

$st = $pdo->prepare('SELECT dk.id dk_id, s.*, d.tong_ket, d.ket_qua,
        (SELECT diem_chinh_thuc FROM lop_hoc_phan WHERE id=dk.lhp_id) co_diem
    FROM dang_ky dk JOIN sinh_vien s ON s.id = dk.sinh_vien_id LEFT JOIN diem d ON d.dang_ky_id = dk.id
    WHERE dk.lhp_id = ? ORDER BY s.ma_sv');
$st->execute([$id]);
$rows = $st->fetchAll();

// Sinh viên chưa có trong lớp (lọc theo lớp sinh hoạt / từ khóa)
$q = in('q'); $lop = in('lop');
$sql = 'SELECT * FROM sinh_vien WHERE id NOT IN (SELECT sinh_vien_id FROM dang_ky WHERE lhp_id = ?)';
$p = [$id];
if ($q !== '') { $sql .= ' AND (ma_sv LIKE ? OR ho_ten LIKE ?)'; array_push($p, "%$q%", "%$q%"); }
if ($lop !== '') { $sql .= ' AND lop = ?'; $p[] = $lop; }
$st = $pdo->prepare($sql . ' ORDER BY lop, ma_sv LIMIT 200');
$st->execute($p);
$chua = $st->fetchAll();
$dsLop = $pdo->query('SELECT DISTINCT lop FROM sinh_vien ORDER BY lop')->fetchAll();

$title = 'Sinh viên lớp ' . $lhp['ma_lhp'];
require __DIR__ . '/../includes/header.php';
?>
<a href="lophocphan.php" class="small">← Danh sách lớp học phần</a>
<h4 class="mt-1">Lớp <?= h($lhp['ma_lhp']) ?> – <?= h($lhp['ten_mon']) ?></h4>
<p class="text-muted"><?= h($lhp['ten_hk'] . ' ' . $lhp['nam_hoc']) ?> · GV: <?= h($lhp['gv'] ?? '—') ?> · Sĩ số: <?= count($rows) ?>/<?= $lhp['si_so_toi_da'] ?></p>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card p-3">
            <h6>Sinh viên trong lớp</h6>
            <table class="table table-sm table-hover">
                <thead><tr><th>#</th><th>Mã SV</th><th>Họ tên</th><th>Lớp</th><th>Tổng kết</th><th>Kết quả</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $i => $r): ?>
                    <tr><td><?= $i + 1 ?></td><td><?= h($r['ma_sv']) ?></td><td><?= h($r['ho_ten']) ?></td><td><?= h($r['lop']) ?></td>
                        <td><?= fd($r['tong_ket']) ?></td><td class="<?= $r['ket_qua'] === 'Đạt' ? 'kq-dat' : 'kq-truot' ?>"><?= h($r['ket_qua']) ?></td>
                        <td class="text-end"><?php if (!$r['co_diem']): ?>
                            <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="dk" value="<?= $r['dk_id'] ?>">
                                <button name="action" value="remove" class="btn btn-sm btn-outline-danger" data-confirm="Xóa <?= h($r['ho_ten']) ?> khỏi lớp?">Xóa</button></form>
                        <?php endif; ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?><tr><td colspan="7" class="text-muted">Lớp chưa có sinh viên.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card p-3 mb-3">
            <h6>Thêm nhanh cả lớp sinh hoạt</h6>
            <form method="post" class="d-flex gap-2"><?= csrf_field() ?>
                <select name="lop" class="form-select"><?= options($dsLop, 'lop', 'lop') ?></select>
                <button name="action" value="add_lop" class="btn btn-success text-nowrap">Thêm cả lớp</button>
            </form>
        </div>
        <div class="card p-3">
            <h6>Chọn từng sinh viên</h6>
            <form class="d-flex gap-2 mb-2"><input type="hidden" name="id" value="<?= $id ?>">
                <input name="q" class="form-control form-control-sm" placeholder="Mã / tên SV" value="<?= h($q) ?>">
                <select name="lop" class="form-select form-select-sm"><?= options($dsLop, 'lop', 'lop', $lop, 'Mọi lớp') ?></select>
                <button class="btn btn-sm btn-outline-primary">Lọc</button></form>
            <form method="post"><?= csrf_field() ?>
                <div style="max-height:380px;overflow:auto">
                <table class="table table-sm">
                    <?php foreach ($chua as $s): ?>
                        <tr><td><input type="checkbox" class="form-check-input" name="sv[]" value="<?= $s['id'] ?>"></td><td><?= h($s['ma_sv']) ?></td><td><?= h($s['ho_ten']) ?></td><td><?= h($s['lop']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$chua): ?><tr><td class="text-muted">Không còn sinh viên phù hợp.</td></tr><?php endif; ?>
                </table>
                </div>
                <button name="action" value="add" class="btn btn-primary btn-sm">Thêm sinh viên đã chọn</button>
            </form>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
