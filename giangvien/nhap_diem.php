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
    $st = $pdo->prepare('SELECT l.*, m.*, l.id lhp_id, hk.ten_hk, hk.nam_hoc, g.ho_ten gv FROM lop_hoc_phan l
        JOIN mon_hoc m ON m.id = l.mon_hoc_id JOIN hoc_ky hk ON hk.id = l.hoc_ky_id LEFT JOIN giang_vien g ON g.id = l.giang_vien_id
        WHERE l.id = ?');
    $st->execute([$lhpId]);
    $lhp = $st->fetch();
}

// ================= LƯU ĐIỂM =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $lhp) {
    csrf_check();
    $input = $_POST['diem'] ?? [];
    $lyDo = mb_substr(trim($_POST['ly_do'] ?? ''), 0, 255);

    // Lấy điểm hiện tại của đúng lớp này (chặn sửa bản ghi của lớp khác bằng cách đổi id trên form)
    $st = $pdo->prepare('SELECT d.*, s.ma_sv FROM diem d JOIN dang_ky dk ON dk.id = d.dang_ky_id
        JOIN sinh_vien s ON s.id = dk.sinh_vien_id WHERE dk.lhp_id = ?');
    $st->execute([$lhpId]);
    $cu = [];
    foreach ($st as $r) $cu[$r['id']] = $r;

    // 1) Kiểm tra toàn bộ dữ liệu trước khi ghi
    $loi = [];
    $moi = [];
    foreach ($input as $diemId => $cot) {
        $diemId = (int)$diemId;
        if (!isset($cu[$diemId])) { $loi[] = 'Dữ liệu không thuộc lớp học phần này'; continue; }
        foreach (COT_DIEM as $c => $ten) {
            if ((int)$lhp['ts_' . $c] === 0) { $moi[$diemId][$c] = null; continue; }  // cột không dùng
            $v = parse_diem($cot[$c] ?? '');
            if ($v === false) {
                $loi[] = "SV {$cu[$diemId]['ma_sv']}: điểm $ten không hợp lệ (phải là số từ 0 đến 10)";
            } else {
                $moi[$diemId][$c] = $v;
            }
        }
    }

    // Sửa điểm đã có thì bắt buộc nhập lý do
    $coSuaDiemCu = false;
    foreach ($moi as $diemId => $cot) {
        foreach ($cot as $c => $v) {
            $old = $cu[$diemId][$c] === null ? null : (float)$cu[$diemId][$c];
            if ($old !== null && $old !== $v) $coSuaDiemCu = true;
        }
    }
    if ($coSuaDiemCu && $lyDo === '') $loi[] = 'Bạn đang sửa điểm đã nhập trước đó, vui lòng nhập lý do chỉnh sửa';

    if ($loi) {
        flash('danger', implode(' | ', array_unique(array_slice($loi, 0, 8))));
        $_SESSION['old_diem'] = $input;
        $_SESSION['old_lydo'] = $lyDo;
        redirect("giangvien/nhap_diem.php?lhp=$lhpId");
    }

    // 2) Ghi trong 1 transaction: cập nhật điểm + ghi lịch sử
    $pdo->beginTransaction();
    try {
        $up = $pdo->prepare('UPDATE diem SET cc=?, kt=?, th=?, thi=?, tong_ket=?, ket_qua=?, updated_by=?, updated_at=NOW() WHERE id=?');
        $log = $pdo->prepare('INSERT INTO lich_su_diem (diem_id, nguoi_sua, cot, gia_tri_cu, gia_tri_moi, ly_do) VALUES (?,?,?,?,?,?)');
        $soSV = 0; $soO = 0;
        foreach ($moi as $diemId => $d) {
            $thayDoi = false;
            foreach (COT_DIEM as $c => $_) {
                $old = $cu[$diemId][$c] === null ? null : (float)$cu[$diemId][$c];
                if ($old !== $d[$c]) {
                    $log->execute([$diemId, $u['id'], $c, $old, $d[$c], $lyDo ?: ($old === null ? 'Nhập điểm' : null)]);
                    $thayDoi = true;
                    $soO++;
                }
            }
            if ($thayDoi) {
                $tong = tinh_tong_ket($d, $lhp);
                $up->execute([$d['cc'], $d['kt'], $d['th'], $d['thi'], $tong, xet_ket_qua($tong, $lhp), $u['id'], $diemId]);
                $soSV++;
            }
        }
        $pdo->commit();
        flash($soO ? 'success' : 'info', $soO ? "Đã lưu $soO ô điểm của $soSV sinh viên. Lịch sử chỉnh sửa đã được ghi lại." : 'Không có thay đổi nào.');
    } catch (Throwable $e) {
        $pdo->rollBack();
        flash('danger', 'Lỗi khi lưu điểm, dữ liệu chưa thay đổi.');
    }
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
<h4>Nhập điểm thành phần</h4>
<form class="row g-2 mb-3">
    <div class="col-md-6"><select name="lhp" class="form-select" onchange="this.form.submit()"><?= options($dsLhp, 'id', 'ten', $lhpId, '-- Chọn lớp học phần --') ?></select></div>
</form>

<?php if ($lhp): ?>
<div class="card p-3 mb-3">
    <div class="row">
        <div class="col-md-8">
            <b><?= h($lhp['ma_lhp']) ?> – <?= h($lhp['ten_mon']) ?></b> (<?= $lhp['so_tin_chi'] ?> TC) · <?= h($lhp['ten_hk'] . ' ' . $lhp['nam_hoc']) ?> · GV: <?= h($lhp['gv']) ?><br>
            <span class="text-muted">Công thức:</span> <b>Tổng kết = <?= h(cong_thuc_text($lhp)) ?></b>
            · <span class="text-muted">Đạt khi tổng kết ≥</span> <b><?= fd($lhp['diem_dat']) ?></b>
        </div>
        <div class="col-md-4 text-md-end">
            <a class="btn btn-outline-secondary btn-sm" href="lich_su.php?lhp=<?= $lhpId ?>">Lịch sử sửa điểm</a>
            <a class="btn btn-outline-dark btn-sm" href="in_bang_diem.php?lhp=<?= $lhpId ?>" target="_blank"><i class="bi bi-printer me-1"></i>In bảng điểm</a>
        </div>
    </div>
</div>

<form method="post" class="js-validate" novalidate id="formDiem">
    <?= csrf_field() ?>
    <div class="d-flex gap-2 mb-2">
        <input id="locSV" class="form-control form-control-sm" style="max-width:260px" placeholder="Lọc nhanh theo mã / tên SV...">
        <span class="small text-muted align-self-center">Điểm từ 0 đến 10, cho phép 1 chữ số thập phân. Để trống = chưa nhập.</span>
    </div>
    <div class="card p-0 mb-3">
    <div class="table-responsive">
    <table class="table table-sm table-hover table-scores mb-0">
        <thead class="table-light"><tr>
            <th>#</th><th>Mã SV</th><th>Họ tên</th><th>Lớp</th>
            <?php foreach (COT_DIEM as $c => $t): ?>
                <th class="text-center"><?= $t ?><br><small class="text-muted"><?= $lhp['ts_' . $c] ?>%</small></th>
            <?php endforeach; ?>
            <th class="text-center">Tổng kết</th><th>Chữ</th><th>Kết quả</th>
        </tr></thead>
        <tbody>
        <?php foreach ($rows as $i => $r): [$chu] = quy_doi($r['tong_ket'] === null ? null : (float)$r['tong_ket']); ?>
            <tr data-search="<?= h(mb_strtolower($r['ma_sv'] . ' ' . $r['ho_ten'])) ?>">
                <td><?= $i + 1 ?></td><td><?= h($r['ma_sv']) ?></td><td class="text-nowrap"><?= h($r['ho_ten']) ?></td><td><?= h($r['lop']) ?></td>
                <?php foreach (COT_DIEM as $c => $t):
                    $val = $old[$r['id']][$c] ?? fd($r[$c]);
                    $off = (int)$lhp['ts_' . $c] === 0; ?>
                    <td class="text-center"><input name="diem[<?= $r['id'] ?>][<?= $c ?>]" class="form-control form-control-sm score d-inline-block"
                        data-w="<?= $lhp['ts_' . $c] ?>" value="<?= h($val) ?>" <?= $off ? 'disabled' : '' ?> inputmode="decimal"></td>
                <?php endforeach; ?>
                <td class="text-center fw-bold tong"><?= fd($r['tong_ket']) ?></td>
                <td class="chu"><?= $chu ?></td>
                <td class="kq <?= $r['ket_qua'] === 'Đạt' ? 'kq-dat' : 'kq-truot' ?>"><?= h($r['ket_qua']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="11" class="text-muted text-center">Lớp chưa có sinh viên.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
    </div>
    <?php if ($rows): ?>
    <div class="row g-2 align-items-end">
        <div class="col-md-6"><label class="form-label">Lý do chỉnh sửa <span class="text-muted small">(bắt buộc khi sửa điểm đã có)</span></label>
            <input name="ly_do" class="form-control" maxlength="255" value="<?= h($oldLyDo) ?>" placeholder="vd: Phúc khảo, nhập sai..."></div>
        <div class="col-md-6"><button class="btn btn-success"><i class="bi bi-save me-1"></i>Lưu điểm</button></div>
    </div>
    <?php endif; ?>
</form>

<?php
$extraScripts = '<script>
const DIEM_DAT = ' . json_encode((float)$lhp['diem_dat']) . ';
// Tính thử điểm tổng kết ngay khi gõ (server sẽ tính lại khi lưu)
function quyDoi(t){const b=[[8.5,"A"],[8,"B+"],[7,"B"],[6.5,"C+"],[5.5,"C"],[5,"D+"],[4,"D"]];for(const[m,c]of b)if(t>=m)return c;return "F";}
function tinhDong(tr){
  let tong=0, du=true, hopLe=true;
  tr.querySelectorAll("input.score").forEach(i=>{
    const w=+i.dataset.w; if(!w) return;
    const v=i.value.trim().replace(",",".");
    if(v===""){du=false;return;}
    if(!hopLeDiem(v)){hopLe=false;return;}
    tong+=parseFloat(v)*w;
  });
  const tdT=tr.querySelector(".tong"), tdK=tr.querySelector(".kq"), tdC=tr.querySelector(".chu");
  if(!du||!hopLe){tdT.textContent="";tdK.textContent="";tdC.textContent="";return;}
  const t=Math.round(tong/100*10)/10;
  tdT.textContent=t.toFixed(1); tdC.textContent=quyDoi(t);
  tdK.textContent=t>=DIEM_DAT?"Đạt":"Không đạt";
  tdK.className="kq "+(t>=DIEM_DAT?"kq-dat":"kq-truot");
}
document.querySelectorAll("#formDiem tbody tr").forEach(tr=>tr.querySelectorAll("input.score").forEach(i=>i.addEventListener("input",()=>tinhDong(tr))));
document.getElementById("locSV").addEventListener("input",e=>{
  const k=e.target.value.toLowerCase().trim();
  document.querySelectorAll("#formDiem tbody tr[data-search]").forEach(tr=>tr.style.display=tr.dataset.search.includes(k)?"":"none");
});
// Enter để xuống ô cùng cột ở dòng dưới
document.querySelectorAll("#formDiem input.score").forEach(i=>i.addEventListener("keydown",e=>{
  if(e.key!=="Enter")return; e.preventDefault();
  const td=i.closest("td"), idx=[...td.parentNode.children].indexOf(td);
  let tr=td.parentNode.nextElementSibling; while(tr&&tr.style.display==="none")tr=tr.nextElementSibling;
  if(tr){const n=tr.children[idx].querySelector("input");n&&n.focus();n&&n.select();}
}));
</script>';
endif;
require __DIR__ . '/../includes/footer.php';
