<?php
// NHẬP / SỬA ĐIỂM THÀNH PHẦN — tự tính tổng kết, xét đạt, lưu lịch sử chỉnh sửa
require_once __DIR__ . '/../includes/auth.php';
$u = require_login('admin', 'giangvien');

// Danh sách lớp được phép: admin = tất cả, giảng viên = lớp mình phụ trách
$sql = "SELECT l.id, CONCAT(l.ma_lhp,' – ',m.ten_mon,' (',hk.ten_hk,' ',hk.nam_hoc,')') ten
        FROM lop_hoc_phan l JOIN mon_hoc m ON m.id = l.mon_hoc_id JOIN hoc_ky hk ON hk.id = l.hoc_ky_id";
if ($u['role'] === 'giangvien') {
    $st = $pdo->prepare($sql . ' WHERE l.giang_vien_id = ? ORDER BY hk.ma_hk DESC, l.ma_lhp');
    $st->execute([current_gv_id()]);
} else {
    $st = $pdo->query($sql . ' ORDER BY hk.ma_hk DESC, l.ma_lhp');
}
$dsLhp = $st->fetchAll();

$lhpId = (int)in('lhp');
$lhp = null;
if ($lhpId) {
    if (!co_quyen_lhp($lhpId)) forbidden('Bạn chỉ được nhập/sửa điểm lớp học phần do mình phụ trách.');
    $st = $pdo->prepare('SELECT l.*, m.*, l.id lhp_id, hk.trang_thai hk_trang_thai, hk.ten_hk, hk.nam_hoc, g.ho_ten gv FROM lop_hoc_phan l
        JOIN mon_hoc m ON m.id = l.mon_hoc_id JOIN hoc_ky hk ON hk.id = l.hoc_ky_id LEFT JOIN giang_vien g ON g.id = l.giang_vien_id
        WHERE l.id = ?');
    $st->execute([$lhpId]);
    $lhp = $st->fetch();
}

// ================= LƯU ĐIỂM =================
require_once __DIR__ . '/../includes/grade-service.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $lhp) {
    csrf_check();
    $input = $_POST['diem'] ?? [];
    $reason = is_string($_POST['ly_do'] ?? '') ? mb_substr(trim($_POST['ly_do'] ?? ''),0,255) : '';
    try {
        if (!is_array($input)) throw new RuntimeException('Dữ liệu điểm không hợp lệ.');
        $result = luu_diem($pdo,$u,$lhpId,$input,$reason,($_POST['action'] ?? '')==='finalize',(int)($_POST['phien_ban'] ?? -1));
        flash('success', $result['finalized'] ? 'Đã lưu điểm chính thức. Giảng viên chỉ được xem.' : 'Đã lưu bản nháp. Sinh viên chưa xem được điểm này.');
    } catch (PDOException $e) { flash('danger','Không thể lưu điểm. Dữ liệu chưa thay đổi.');
    } catch (RuntimeException $e) {
        flash('danger',$e->getMessage());
        $_SESSION['old_diem']=$input;
        $_SESSION['old_lydo']=$reason;
    } catch (Throwable $e) { flash('danger','Không thể lưu điểm. Dữ liệu chưa thay đổi.'); }
    redirect("giangvien/nhap_diem.php?lhp=$lhpId");
}
// ================= HIỂN THỊ =================
$rows = [];
if ($lhp) {
    $st = $pdo->prepare('SELECT d.*, s.ma_sv, s.ho_ten, s.lop FROM dang_ky dk
        JOIN sinh_vien s ON s.id = dk.sinh_vien_id JOIN diem d ON d.dang_ky_id = dk.id
        WHERE dk.lhp_id = ? ORDER BY s.ma_sv');
    $st->execute([$lhpId]);
    $rows = $st->fetchAll();
}
$old = $_SESSION['old_diem'] ?? null;
$oldLyDo = $_SESSION['old_lydo'] ?? '';
unset($_SESSION['old_diem'], $_SESSION['old_lydo']);

$title = 'Nhập điểm';
require __DIR__ . '/../includes/header.php';
?>
<h4 class="visually-hidden">Nhập điểm thành phần</h4><?php if ($lhp): ?><p class="badge <?= $lhp['diem_chinh_thuc'] ? 'bg-success' : 'bg-warning text-dark' ?>"><?= $lhp['diem_chinh_thuc'] ? 'Điểm chính thức — đã khóa' : 'Bản nháp — chưa công bố' ?></p><?php endif; ?>
<form class="row g-2 mb-3">
    <div class="col-md-6"><select name="lhp" class="form-select" onchange="this.form.submit()"><?= options($dsLhp, 'id', 'ten', $lhpId, '-- Chọn lớp học phần --') ?></select></div>
</form>

<?php if ($lhp): ?>
<div class="card p-3 mb-3">
    <div class="row">
        <div class="col-md-8">
            <b><?= h($lhp['ma_lhp']) ?> – <?= h($lhp['ten_mon']) ?></b> (<?= $lhp['so_tin_chi'] ?> TC) · <?= h($lhp['ten_hk'] . ' ' . $lhp['nam_hoc']) ?> · GV: <?= h($lhp['gv']) ?><br>
            <details class="score-formula" open><summary>Công thức và điều kiện đạt</summary><div class="pt-2"><span class="text-muted">Tổng kết =</span> <b><?= h(cong_thuc_text($lhp)) ?></b><br><span class="text-muted">Đạt khi tổng kết ≥ 5 và điểm cuối HP ≥ 5.</span></div></details>
        </div>
        <div class="col-md-4 text-md-end">
            <a class="btn btn-outline-secondary btn-sm" href="lich_su.php?lhp=<?= $lhpId ?>">Lịch sử sửa điểm</a>
            <a class="btn btn-outline-dark btn-sm" href="in_bang_diem.php?lhp=<?= $lhpId ?>" target="_blank"><i class="bi bi-printer me-1"></i>In bảng điểm</a>
        </div>
    </div>
</div>

<form method="post" class="js-validate" novalidate id="formDiem">
    <?= csrf_field() ?><input type="hidden" name="phien_ban" value="<?= $lhp['phien_ban'] ?>">
    <div class="score-toolbar">
        <div class="d-flex gap-2 flex-wrap"><input id="locSV" class="form-control form-control-sm" placeholder="Tìm mã hoặc họ tên sinh viên" aria-label="Lọc sinh viên"><button type="button" class="btn btn-outline-primary btn-sm" id="addTxColumn"><i class="bi bi-plus-lg"></i>Thêm bài TX</button></div>
        <div class="d-flex gap-3 align-items-center flex-wrap"><span id="scoreRowCount" class="small text-muted"><?= count($rows) ?> sinh viên</span><span id="scoreStatus" class="score-status" role="status" aria-live="polite"><i class="bi bi-check2-circle"></i>Dữ liệu đã tải</span></div>
    </div>
    <div class="score-legend mb-2"><span><i class="bi bi-keyboard"></i>Tab: ô tiếp theo · Enter / ↑ ↓: di chuyển dòng</span><span><i class="legend-square"></i>Ô vừa thay đổi</span></div>
    <p class="small text-muted d-md-none"><i class="bi bi-arrow-left-right me-1" aria-hidden="true"></i>Vuốt bảng sang ngang để nhập điểm TX, GK và CK.</p>
    <div class="card p-0 mb-3">
    <div class="score-grid-wrap" tabindex="0" role="region" aria-label="Bảng nhập điểm, cuộn ngang để xem tất cả bài">
    <table class="table table-sm table-hover table-scores mb-0">
        <thead class="table-light"><tr>
            <th>#</th><th>Mã SV</th><th>Họ tên</th><th>Lớp</th>
            <?php foreach (COT_DIEM as $c => $t): ?>
                <th class="text-center"><?= $t ?><br><small class="text-muted">Hệ số <?= HE_SO_DIEM[$c] ?></small></th>
            <?php endforeach; ?>
            <th class="text-center">Tổng kết</th><th>Kết quả</th>
        </tr></thead>
        <tbody>
        <?php foreach ($rows as $i => $r): ?>
            <tr data-search="<?= h(mb_strtolower($r['ma_sv'] . ' ' . $r['ho_ten'])) ?>">
                <td><?= $i + 1 ?></td><td><?= h($r['ma_sv']) ?></td><td class="text-nowrap"><?= h($r['ho_ten']) ?></td><td><?= h($r['lop']) ?></td>
                <?php foreach (COT_DIEM as $c => $t):
                    $val = $old[$r['id']][$c] ?? hien_thi_diem($r, $c);
                    $val = is_scalar($val) ? $val : '';
                    $off = $u['role'] !== 'admin' && ($lhp['diem_chinh_thuc'] || $lhp['hk_trang_thai'] === 'Đóng'); ?>
                    <td class="text-center"><input name="diem[<?= $r['id'] ?>][<?= $c ?>]" class="form-control form-control-sm <?= $c === 'cc' ? 'tx-scores' : 'score' ?> d-inline-block"
                        data-w="<?= HE_SO_DIEM[$c] ?>" data-kind="<?= $c ?>" aria-label="<?= h($t . ' của ' . $r['ma_sv']) ?>" value="<?= h($val) ?>" <?= $off ? 'disabled' : '' ?> inputmode="<?= $c==='cc'?'text':'decimal' ?>"></td>
                <?php endforeach; ?>
                <td class="text-center fw-bold tong"><?= fd($r['tong_ket']) ?></td>
                <td class="kq <?= $r['ket_qua'] === 'Đạt' ? 'kq-dat' : 'kq-truot' ?>"><?= h($r['ket_qua']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="9" class="text-muted text-center">Lớp chưa có sinh viên.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
    </div>
    <p class="score-help">Mỗi bài TX hệ số 1 · GK hệ số 2 · CK hệ số 3. Không để trống giữa các bài TX đã nhập. Cột TX cuối để trống không được tính là một bài. Tổng kết hiển thị để xem trước; kết quả lưu do máy chủ xác thực.</p>
    <?php if ($rows): ?>
    <div class="row g-2 align-items-end save-panel">
        <div class="col-md-6"><label class="form-label">Lý do chỉnh sửa <span class="text-muted small">(Admin bắt buộc nhập khi sửa điểm chính thức)</span></label>
            <input name="ly_do" class="form-control" maxlength="255" value="<?= h($oldLyDo) ?>" placeholder="vd: Phúc khảo, nhập sai..."></div>
        <div class="col-md-6 d-flex gap-2">
<?php if ($u['role']==='admin' || (!$lhp['diem_chinh_thuc'] && $lhp['hk_trang_thai']==='Mở')): ?>
<button name="action" value="draft" class="btn btn-success"><?= $lhp['diem_chinh_thuc'] ? 'Lưu chỉnh sửa của Admin' : 'Lưu bản nháp' ?></button>
<?php if (!$lhp['diem_chinh_thuc']): ?><button name="action" value="finalize" class="btn btn-primary" data-confirm="Lưu chính thức sẽ khóa điểm. Giảng viên không thể sửa tiếp. Bạn xác nhận?">Lưu điểm chính thức</button><?php endif; ?>
<?php else: ?><span class="badge bg-secondary align-self-center">Chỉ xem — điểm đã khóa hoặc học kỳ đã đóng</span><?php endif; ?>
</div>
    </div>
    <?php endif; ?>
</form>

<?php
$extraScripts = '<script src="' . BASE_URL . 'assets/js/score-grid.js?v=1"></script>';
endif;
require __DIR__ . '/../includes/footer.php';
