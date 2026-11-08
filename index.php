<?php
require_once __DIR__ . '/includes/auth.php';
$u = require_login();

if ($u['role'] === 'sinhvien') redirect('sinhvien/bang_diem.php');

$title = 'Tổng quan';
$isAdmin = $u['role'] === 'admin';
$gv = current_gv_id();

// Điều kiện lọc: giảng viên chỉ thấy lớp của mình
$whereLhp = $isAdmin ? '1=1' : 'l.giang_vien_id = ' . (int)$gv;

$soSV  = $pdo->query('SELECT COUNT(*) FROM sinh_vien')->fetchColumn();
$soMon = $pdo->query('SELECT COUNT(*) FROM mon_hoc')->fetchColumn();
$soLHP = $pdo->query("SELECT COUNT(*) FROM lop_hoc_phan l WHERE $whereLhp")->fetchColumn();
$tk = $pdo->query("SELECT COUNT(d.tong_ket) co_diem, SUM(d.ket_qua='Đạt') dat, ROUND(AVG(d.tong_ket),2) tb
    FROM diem d JOIN dang_ky dk ON dk.id=d.dang_ky_id JOIN lop_hoc_phan l ON l.id=dk.lhp_id WHERE $whereLhp")->fetch();
$tyLeDat = $tk['co_diem'] ? round($tk['dat'] * 100 / $tk['co_diem'], 1) : 0;

// Tiến độ nhập điểm từng lớp học phần
$lhps = $pdo->query("SELECT l.id, l.ma_lhp, m.ten_mon, hk.ten_hk, hk.nam_hoc, gv.ho_ten gv,
        COUNT(dk.id) si_so, COUNT(d.tong_ket) da_co, SUM(d.ket_qua='Không đạt') truot
    FROM lop_hoc_phan l
    JOIN mon_hoc m ON m.id=l.mon_hoc_id JOIN hoc_ky hk ON hk.id=l.hoc_ky_id
    LEFT JOIN giang_vien gv ON gv.id=l.giang_vien_id
    LEFT JOIN dang_ky dk ON dk.lhp_id=l.id LEFT JOIN diem d ON d.dang_ky_id=dk.id
    WHERE $whereLhp GROUP BY l.id ORDER BY hk.ma_hk DESC, l.ma_lhp")->fetchAll();

// 6 lần sửa điểm gần nhất
$ls = $pdo->query("SELECT ls.*, u.ho_ten nguoi, sv.ma_sv, sv.ho_ten sv, l.ma_lhp
    FROM lich_su_diem ls JOIN diem d ON d.id=ls.diem_id JOIN dang_ky dk ON dk.id=d.dang_ky_id
    JOIN sinh_vien sv ON sv.id=dk.sinh_vien_id JOIN lop_hoc_phan l ON l.id=dk.lhp_id
    LEFT JOIN users u ON u.id=ls.nguoi_sua
    WHERE $whereLhp ORDER BY ls.thoi_gian DESC, ls.id DESC LIMIT 6")->fetchAll();

$soUser = $isAdmin ? $pdo->query("SELECT COUNT(*) FROM users WHERE trang_thai='locked'")->fetchColumn() : 0;

require __DIR__ . '/includes/header.php';
?>
<h4 class="mb-1">Xin chào, <?= h($u['ho_ten']) ?> 👋</h4>
<p class="text-muted mb-4">Tổng quan tình hình học tập<?= $isAdmin ? ' toàn trường' : ' các lớp bạn phụ trách' ?>.</p>
<div class="row g-3 mb-3">
    <?php
    $cards = [
        ['Sinh viên', $soSV, 'primary', 'bi-mortarboard'],
        ['Môn học', $soMon, 'info', 'bi-journal-bookmark'],
        [$isAdmin ? 'Lớp học phần' : 'Lớp tôi phụ trách', $soLHP, 'secondary', 'bi-collection'],
        ['Điểm TB tổng kết', $tk['tb'] ?? '–', 'warning', 'bi-graph-up'],
        ['Tỷ lệ đạt', $tyLeDat . '%', 'success', 'bi-check2-circle'],
    ];
    if ($isAdmin) $cards[] = ['Tài khoản bị khóa', $soUser, 'danger', 'bi-lock'];
    foreach ($cards as [$t, $v, $c, $icon]): ?>
        <div class="col-6 col-md-4 col-xxl-2">
            <div class="card stat-card h-100">
                <span class="stat-icon tone-<?= $c ?>"><i class="bi <?= $icon ?>"></i></span>
                <div><div class="stat-label"><?= h($t) ?></div><div class="num"><?= h($v) ?></div></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-xxl-8">
        <div class="card p-3 h-100">
            <h6><i class="bi bi-list-check me-1 text-primary"></i>Tiến độ nhập điểm theo lớp học phần</h6>
            <div class="table-responsive">
            <table class="table table-sm table-hover table-nowrap mb-0">
                <thead><tr><th>Mã LHP</th><th>Môn</th><th>Học kỳ</th><?php if ($isAdmin): ?><th>Giảng viên</th><?php endif; ?><th>Sĩ số</th><th style="min-width:150px">Đã có điểm</th><th>Không đạt</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($lhps as $l): $pt = $l['si_so'] ? round($l['da_co'] * 100 / $l['si_so']) : 0; ?>
                    <tr>
                        <td><?= h($l['ma_lhp']) ?></td><td><?= h($l['ten_mon']) ?></td>
                        <td><?= h($l['ten_hk'] . ' ' . $l['nam_hoc']) ?></td>
                        <?php if ($isAdmin): ?><td><?= h($l['gv'] ?? '—') ?></td><?php endif; ?>
                        <td><?= $l['si_so'] ?></td>
                        <td><div class="progress" style="height:18px"><div class="progress-bar <?= $pt == 100 ? 'bg-success' : 'bg-warning' ?>" style="width:<?= $pt ?>%"><?= $l['da_co'] ?>/<?= $l['si_so'] ?></div></div></td>
                        <td><?= (int)$l['truot'] ?></td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-primary" title="Nhập điểm" href="<?= BASE_URL ?>giangvien/nhap_diem.php?lhp=<?= $l['id'] ?>"><i class="bi bi-pencil-square"></i></a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$lhps): ?><tr><td colspan="8" class="text-muted">Chưa có lớp học phần.</td></tr><?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
    <div class="col-xxl-4">
        <div class="card p-3 h-100">
            <h6><i class="bi bi-clock-history me-1 text-primary"></i>Chỉnh sửa điểm gần đây</h6>
            <ul class="list-group list-group-flush small">
                <?php foreach ($ls as $r): ?>
                    <li class="list-group-item px-0">
                        <b><?= h($r['nguoi'] ?? '(đã xóa)') ?></b> sửa <b><?= h(COT_DIEM[$r['cot']] ?? $r['cot']) ?></b>
                        của <?= h($r['sv']) ?> (<?= h($r['ma_lhp']) ?>):
                        <?= fd($r['gia_tri_cu']) ?: '∅' ?> → <b><?= fd($r['gia_tri_moi']) ?: '∅' ?></b>
                        <div class="text-muted"><?= h($r['thoi_gian']) ?><?= $r['ly_do'] ? ' · ' . h($r['ly_do']) : '' ?></div>
                    </li>
                <?php endforeach; ?>
                <?php if (!$ls): ?><li class="list-group-item px-0 text-muted">Chưa có.</li><?php endif; ?>
            </ul>
            <a href="<?= BASE_URL ?>giangvien/lich_su.php" class="small mt-2">Xem tất cả →</a>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
