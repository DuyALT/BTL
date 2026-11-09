<?php
// =====================================================================
//  functions.php — Hàm nghiệp vụ về điểm + hàm tiện ích dùng chung
// =====================================================================

define('APP_NAME', 'Quản lý điểm sinh viên');
define('MAT_KHAU_MAC_DINH', '123456');   // mật khẩu khi tạo mới / reset tài khoản

const COT_DIEM = [
    'cc'  => 'Thường xuyên',
    'kt'  => 'Giữa HP',
    'thi' => 'Cuối HP',
];
const HE_SO_DIEM = ['cc' => 1, 'kt' => 2, 'thi' => 3];

function diem_thuong_xuyen(array $d): array
{
    if (isset($d['tx'])) return json_decode($d['tx'], true) ?: [];
    return isset($d['cc']) ? [(float)$d['cc']] : [];
}

function hien_thi_diem(array $d, string $c): string
{
    return $c === 'cc' ? implode('; ', array_map('fd', diem_thuong_xuyen($d))) : fd($d[$c]);
}

/** Chuyển chuỗi nhập vào thành số điểm. '' => null. Trả về false nếu không hợp lệ (không phải số / ngoài 0–10). */
function parse_diem($v)
{
    if (!is_scalar($v) && $v !== null) return false;
    $v = trim(str_replace(',', '.', (string)$v));   // cho phép gõ 7,5
    if ($v === '') return null;
    if (!preg_match('/^\d+(?:\.\d+)?$/D', $v)) return false;
    $f = (float)$v;
    if ($f < 0 || $f > 10) return false;
    return round($f, 2);
}

/**
 * Mỗi bài thường xuyên hệ số 1, giữa HP hệ số 2, cuối HP hệ số 3.
 * Thiếu một trong ba phần thì chưa tính. Làm tròn hai chữ số thập phân.
 */
function tinh_tong_ket(array $d, array $mon): ?float
{
    $tx = diem_thuong_xuyen($d);
    if (!$tx || in_array(null, $tx, true) || !isset($d['kt'], $d['thi'])) return null;
    return round((array_sum($tx) + 2 * $d['kt'] + 3 * $d['thi']) / (count($tx) + 5), 2);
}

/** Đạt khi cả tổng kết và cuối học phần đều từ 5 trở lên. */
function xet_ket_qua(?float $tong, array $diem): ?string
{
    if ($tong === null || !isset($diem['thi'])) return null;
    return $tong >= 5 && (float)$diem['thi'] >= 5 ? 'Đạt' : 'Không đạt';
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
    return '(Tổng điểm thường xuyên + 2 × Giữa HP + 3 × Cuối HP) / (Số bài thường xuyên + 5)';
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
        $up->execute([$tong, xet_ket_qua($tong, $d), $d['id']]);
        $n++;
    }
    return $n;
}

/** Hiển thị điểm 2 chữ số thập phân, null => rỗng */
function fd($v): string
{
    return $v === null || $v === '' ? '' : number_format((float)$v, 2);
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
