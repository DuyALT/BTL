<?php
// =====================================================================
//  functions.php — Hàm nghiệp vụ về điểm + hàm tiện ích dùng chung
// =====================================================================

define('APP_NAME', 'Quản lý điểm sinh viên');
define('MAT_KHAU_MAC_DINH', '123456');   // mật khẩu khi tạo mới / reset tài khoản

const COT_DIEM = [
    'cc'  => 'Chuyên cần',
    'kt'  => 'Kiểm tra',
    'th'  => 'Thực hành',
    'thi' => 'Thi KTHP',
];

/** Chuyển chuỗi nhập vào thành số điểm. '' => null. Trả về false nếu không hợp lệ (không phải số / ngoài 0–10). */
function parse_diem($v)
{
    $v = trim(str_replace(',', '.', (string)$v));   // cho phép gõ 7,5
    if ($v === '') return null;
    if (!is_numeric($v)) return false;
    $f = (float)$v;
    if ($f < 0 || $f > 10) return false;
    return round($f, 1);
}

/**
 * Điểm tổng kết = Σ(điểm thành phần × trọng số) / 100, làm tròn 1 chữ số.
 * Cột có trọng số 0 thì bỏ qua. Thiếu điểm của cột có trọng số > 0 => chưa tính (null).
 * $mon là 1 dòng của bảng mon_hoc (có ts_cc, ts_kt, ts_th, ts_thi).
 */
function tinh_tong_ket(array $d, array $mon): ?float
{
    $tong = 0;
    foreach (COT_DIEM as $c => $_) {
        $w = (int)$mon['ts_' . $c];
        if ($w === 0) continue;
        if (!isset($d[$c]) || $d[$c] === null || $d[$c] === '') return null;
        $tong += (float)$d[$c] * $w;
    }
    return round($tong / 100, 1);
}

/** Đạt nếu điểm tổng kết >= điểm đạt của môn */
function xet_ket_qua(?float $tong, array $mon): ?string
{
    if ($tong === null) return null;
    return $tong >= (float)$mon['diem_dat'] ? 'Đạt' : 'Không đạt';
}

/** Quy đổi điểm chữ và thang 4. Trả về [chữ, hệ 4] */
function quy_doi(?float $tong): array
{
    if ($tong === null) return ['', null];
    $bang = [[8.5, 'A', 4.0], [8.0, 'B+', 3.5], [7.0, 'B', 3.0], [6.5, 'C+', 2.5],
             [5.5, 'C', 2.0], [5.0, 'D+', 1.5], [4.0, 'D', 1.0]];
    foreach ($bang as [$min, $chu, $he4]) {
        if ($tong >= $min) return [$chu, $he4];
    }
    return ['F', 0.0];
}

/** Công thức dạng chữ, vd: 10%×CC + 20%×KT + 70%×Thi */
function cong_thuc_text(array $mon): string
{
    $ten = ['cc' => 'CC', 'kt' => 'KT', 'th' => 'TH', 'thi' => 'Thi'];
    $parts = [];
    foreach ($ten as $c => $t) {
        if ((int)$mon['ts_' . $c] > 0) $parts[] = $mon['ts_' . $c] . '%×' . $t;
    }
    return implode(' + ', $parts);
}

/** Tính lại điểm tổng kết của toàn bộ sinh viên học môn này (gọi khi admin đổi trọng số) */
function tinh_lai_theo_mon(PDO $pdo, int $mon_id): int
{
    $st = $pdo->prepare('SELECT * FROM mon_hoc WHERE id = ?');
    $st->execute([$mon_id]);
    $mon = $st->fetch();
    if (!$mon) return 0;

    $st = $pdo->prepare('SELECT d.* FROM diem d
        JOIN dang_ky dk ON dk.id = d.dang_ky_id
        JOIN lop_hoc_phan l ON l.id = dk.lhp_id
        WHERE l.mon_hoc_id = ?');
    $st->execute([$mon_id]);
    $up = $pdo->prepare('UPDATE diem SET tong_ket = ?, ket_qua = ? WHERE id = ?');
    $n = 0;
    foreach ($st->fetchAll() as $d) {
        $tong = tinh_tong_ket($d, $mon);
        $up->execute([$tong, xet_ket_qua($tong, $mon), $d['id']]);
        $n++;
    }
    return $n;
}

/** Hiển thị điểm 1 chữ số thập phân, null => rỗng */
function fd($v): string
{
    return $v === null || $v === '' ? '' : number_format((float)$v, 1);
}

/** Lấy tham số từ POST hoặc GET, đã trim */
function in(string $key, $default = '')
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

/** Sinh các thẻ <option> cho <select> */
function options(array $rows, string $valKey, string $textKey, $selected = null, string $empty = ''): string
{
    $html = $empty !== '' ? '<option value="">' . h($empty) . '</option>' : '';
    foreach ($rows as $r) {
        $sel = (string)$r[$valKey] === (string)$selected ? ' selected' : '';
        $html .= '<option value="' . h($r[$valKey]) . '"' . $sel . '>' . h($r[$textKey]) . '</option>';
    }
    return $html;
}
