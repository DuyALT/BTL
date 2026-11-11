<?php
// LỊCH SỬ CHỈNH SỬA ĐIỂM (admin xem tất cả, giảng viên xem lớp mình)
require_once __DIR__ . '/../includes/auth.php';
$u = require_login('admin', 'giangvien');

$lhp = in('lhp'); $q = in('q'); $tu = in('tu'); $den = in('den'); $cot = in('cot');
$sql = 'SELECT ls.*, us.ho_ten nguoi, us.username, s.ma_sv, s.ho_ten sv, l.ma_lhp, m.ten_mon
    FROM lich_su_diem ls JOIN diem d ON d.id = ls.diem_id JOIN dang_ky dk ON dk.id = d.dang_ky_id
    JOIN sinh_vien s ON s.id = dk.sinh_vien_id JOIN lop_hoc_phan l ON l.id = dk.lhp_id JOIN mon_hoc m ON m.id = l.mon_hoc_id
    LEFT JOIN users us ON us.id = ls.nguoi_sua WHERE 1=1';
$p = [];
if ($u['role'] === 'giangvien') { $sql .= ' AND l.giang_vien_id = ?'; $p[] = current_gv_id(); }
if ($lhp !== '') { $sql .= ' AND l.id = ?'; $p[] = (int)$lhp; }
if ($q !== '') { $sql .= ' AND (s.ma_sv LIKE ? OR s.ho_ten LIKE ? OR us.ho_ten LIKE ?)'; array_push($p, "%$q%", "%$q%", "%$q%"); }
if (isset(COT_DIEM[$cot])) { $sql .= ' AND ls.cot = ?'; $p[] = $cot; }
if ($tu !== '') { $sql .= ' AND ls.thoi_gian >= ?'; $p[] = $tu . ' 00:00:00'; }
if ($den !== '') { $sql .= ' AND ls.thoi_gian <= ?'; $p[] = $den . ' 23:59:59'; }
$st = $pdo->prepare($sql . ' ORDER BY ls.thoi_gian DESC, ls.id DESC');
$st->execute($p);
$rows = $st->fetchAll();

$sqlL = "SELECT l.id, CONCAT(l.ma_lhp,' – ',m.ten_mon) ten FROM lop_hoc_phan l JOIN mon_hoc m ON m.id = l.mon_hoc_id";
if ($u['role'] === 'giangvien') { $s = $pdo->prepare($sqlL . ' WHERE l.giang_vien_id = ? ORDER BY l.ma_lhp'); $s->execute([current_gv_id()]); }
else $s = $pdo->query($sqlL . ' ORDER BY l.ma_lhp');
$dsLhp = $s->fetchAll();

require_once __DIR__ . '/../includes/pagination.php';
[$rows,$pagination]=phan_trang($rows);
$title = 'Lịch sử sửa điểm';
require __DIR__ . '/../includes/header.php';
?>
<h4>Lịch sử chỉnh sửa điểm</h4>
<form class="row g-2 mb-3">
    <div class="col-md-3"><select name="lhp" class="form-select"><?= options($dsLhp, 'id', 'ten', $lhp, '-- Mọi lớp học phần --') ?></select></div>
    <div class="col-md-2"><select name="cot" class="form-select"><option value="">-- Mọi cột điểm --</option><?php foreach (COT_DIEM as $k => $v): ?><option value="<?= $k ?>" <?= $cot === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><input name="q" class="form-control" placeholder="Mã/tên SV, người sửa" value="<?= h($q) ?>"></div>
    <div class="col-md-2"><input type="date" name="tu" class="form-control" value="<?= h($tu) ?>" title="Từ ngày"></div>
    <div class="col-md-2"><input type="date" name="den" class="form-control" value="<?= h($den) ?>" title="Đến ngày"></div>
    <div class="col-md-1"><button class="btn btn-outline-primary w-100">Lọc</button></div>
</form>
<div class="card p-0">
<table class="table table-sm table-hover mb-0">
    <thead class="table-light"><tr><th>Thời gian</th><th>Người sửa</th><th>Lớp HP</th><th>Mã SV</th><th>Họ tên</th><th>Cột</th><th>Cũ</th><th>Mới</th><th>Lý do</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr><td class="text-nowrap"><?= date('d/m/Y H:i', strtotime($r['thoi_gian'])) ?></td><td><?= h(($r['nguoi'] ?? '(đã xóa)') . ' / ' . ($r['username'] ?? '')) ?></td>
            <td><?= h($r['ma_lhp']) ?></td><td><?= h($r['ma_sv']) ?></td><td><?= h($r['sv']) ?></td><td><?= h(COT_DIEM[$r['cot']] ?? $r['cot']) ?></td>
            <td class="text-danger"><?= h($r['gia_tri_cu_text'] ?? fd($r['gia_tri_cu'])) ?: '∅' ?></td><td class="text-success fw-bold"><?= h($r['gia_tri_moi_text'] ?? fd($r['gia_tri_moi'])) ?: '∅' ?></td><td><?= h($r['ly_do']) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="9" class="text-center text-muted">Không có bản ghi.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>
<p class="small text-muted mt-2"><?= count($rows) ?> bản ghi trên trang này</p>
<?php hien_phan_trang($pagination); require __DIR__ . '/../includes/footer.php'; ?>
