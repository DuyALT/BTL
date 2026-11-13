<?php
// =====================================================================
//  auth.php — Phiên đăng nhập, phân quyền, chống CSRF, hàm tiện ích
//  Mọi trang đều require file này ở dòng đầu tiên.
// =====================================================================
require_once __DIR__ . '/error-handler.php';
require_once __DIR__ . '/../config/db.php';   // có biến $pdo và BASE_URL
require_once __DIR__ . '/functions.php';      // hàm tính điểm + tiện ích

// Bắt đầu session (chỉ gọi 1 lần)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']); // JS không đọc được cookie phiên
    session_start();
}

define('SESSION_TIMEOUT', 1800);   // 30 phút không thao tác thì tự đăng xuất
const TEN_VAI_TRO = ['admin' => 'Quản trị viên', 'giangvien' => 'Giảng viên', 'sinhvien' => 'Sinh viên'];

/** Chống XSS: luôn dùng h() khi in dữ liệu ra HTML */
function h($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** Chuyển hướng tới 1 trang, đường dẫn tính từ gốc dự án. VD: redirect('login.php') */
function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

/** Thông tin user đang đăng nhập (null nếu chưa đăng nhập) */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/** Kiểm tra user hiện tại có thuộc một trong các vai trò không. VD: is_role('admin','giangvien') */
function is_role(string ...$roles): bool
{
    $u = current_user();
    return $u !== null && in_array($u['role'], $roles, true);
}

/**
 * BẮT BUỘC ĐĂNG NHẬP — gọi ở đầu mỗi trang cần bảo vệ.
 *   require_login();                      // ai đăng nhập cũng vào được
 *   require_login('admin');               // chỉ admin
 *   require_login('admin', 'giangvien');  // admin hoặc giảng viên
 */
function require_login(string ...$roles): array
{
    global $pdo;

    // 1. Chưa đăng nhập -> về trang login
    if (!current_user()) {
        redirect('login.php');
    }

    // 2. Quá thời gian không thao tác -> hủy phiên
    if (time() - ($_SESSION['last_activity'] ?? 0) > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        redirect('login.php?msg=timeout');
    }
    $_SESSION['last_activity'] = time();

    // 3. Đọc lại tài khoản từ CSDL: nếu admin vừa khóa thì đăng xuất ngay
    $st = $pdo->prepare('SELECT id, username, ho_ten, role, trang_thai FROM users WHERE id = ?');
    $st->execute([current_user()['id']]);
    $user = $st->fetch();
    if (!$user || $user['trang_thai'] !== 'active') {
        session_unset();
        session_destroy();
        redirect('login.php?msg=locked');
    }
    $_SESSION['user'] = $user;   // cập nhật thông tin mới nhất

    // 4. Đang dùng mật khẩu mặc định -> phải đổi mật khẩu trước khi làm việc khác
    if (!empty($_SESSION['phai_doi_mk']) && basename($_SERVER['SCRIPT_NAME']) !== 'doi_mat_khau.php') {
        redirect('doi_mat_khau.php');
    }

    // 5. Sai vai trò -> báo không có quyền
    if ($roles && !in_array($user['role'], $roles, true)) {
        forbidden();
    }
    return $user;
}

/** Trang báo lỗi 403 - không có quyền */
function forbidden(string $msg = 'Bạn không có quyền truy cập chức năng này.'): void
{
    http_response_code(403);
    $errorCode=403;$errorTitle='Bạn không có quyền truy cập';$errorMessage=$msg;
    require __DIR__ . '/error-view.php';
    exit;
}

/** id trong bảng giang_vien của tài khoản đang đăng nhập */
function current_gv_id(): ?int
{
    global $pdo;
    $st = $pdo->prepare('SELECT id FROM giang_vien WHERE user_id = ?');
    $st->execute([current_user()['id'] ?? 0]);
    $id = $st->fetchColumn();
    return $id ? (int)$id : null;
}

/** id trong bảng sinh_vien của tài khoản đang đăng nhập */
function current_sv_id(): ?int
{
    global $pdo;
    $st = $pdo->prepare('SELECT id FROM sinh_vien WHERE user_id = ?');
    $st->execute([current_user()['id'] ?? 0]);
    $id = $st->fetchColumn();
    return $id ? (int)$id : null;
}

/** Quyền sửa điểm 1 lớp học phần: admin = mọi lớp, giảng viên = chỉ lớp mình dạy */
function co_quyen_lhp(int $lhp_id): bool
{
    global $pdo;
    if (is_role('admin')) return true;
    if (!is_role('giangvien')) return false;
    $st = $pdo->prepare('SELECT 1 FROM lop_hoc_phan WHERE id = ? AND giang_vien_id = ?');
    $st->execute([$lhp_id, current_gv_id()]);
    return (bool)$st->fetchColumn();
}

// ===================== CHỐNG CSRF =====================
// Trong mỗi form POST: in csrf_field() ra HTML; đầu phần xử lý POST: gọi csrf_check()
// (Lưu ý: KHÔNG viết ký tự đóng thẻ PHP trong chú thích //, PHP sẽ hiểu là kết thúc code)

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_check(): void
{
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
        http_response_code(400);
        exit('Phiên làm việc không hợp lệ. Hãy tải lại trang và thử lại.');
    }
}

// ===================== CHỐNG DÒ MẬT KHẨU =====================
// Ghi lần đăng nhập sai vào CSDL (không phụ thuộc cookie nên không né được bằng cách xóa cookie).
// Khóa theo cặp tài khoản + máy: kẻ dò ở máy khác không làm khóa được tài khoản của người thật.

const DN_SAI_TOI_DA    = 5;     // số lần sai cho 1 tài khoản từ 1 máy
const DN_SAI_TOI_DA_IP = 20;    // số lần sai từ 1 máy, cộng mọi tài khoản
const DN_KHOA_GIAY     = 900;   // khóa 15 phút tính từ lần sai cuối

function dn_tao_bang(PDO $pdo): void
{
    static $xong = false;
    if ($xong) return;
    // Tự tạo nếu CSDL cũ chưa có bảng này (cài mới thì đã có trong schema.sql)
    $pdo->exec("CREATE TABLE IF NOT EXISTS dang_nhap_sai (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL,
        ip VARCHAR(45) NOT NULL,
        thoi_gian DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_dns_user_ip (username, ip, thoi_gian),
        KEY idx_dns_ip (ip, thoi_gian)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $xong = true;
}

function dn_ip(): string
{
    return substr($_SERVER['REMOTE_ADDR'] ?? 'cli', 0, 45);
}

/** [số lần sai trong cửa sổ khóa, số giây đã qua kể từ lần sai cuối] */
function dn_dem(PDO $pdo, string $where, array $p): array
{
    $st = $pdo->prepare("SELECT COUNT(*), TIMESTAMPDIFF(SECOND, MAX(thoi_gian), NOW()) FROM dang_nhap_sai
        WHERE $where AND thoi_gian > NOW() - INTERVAL " . DN_KHOA_GIAY . ' SECOND');
    $st->execute($p);
    [$n, $qua] = $st->fetch(PDO::FETCH_NUM);
    return [(int)$n, (int)$qua];
}

/** Số giây còn phải chờ trước khi được thử đăng nhập (0 = được thử) */
function dn_cho_giay(PDO $pdo, string $username): int
{
    dn_tao_bang($pdo);
    $cho = 0;
    foreach ([['username = ? AND ip = ?', [$username, dn_ip()], DN_SAI_TOI_DA],
              ['ip = ?', [dn_ip()], DN_SAI_TOI_DA_IP]] as [$where, $p, $max]) {
        [$n, $qua] = dn_dem($pdo, $where, $p);
        if ($n >= $max) $cho = max($cho, DN_KHOA_GIAY - $qua);
    }
    return $cho;
}

/** Ghi 1 lần sai; trả về số lần còn được thử trước khi bị khóa */
function dn_ghi_sai(PDO $pdo, string $username): int
{
    dn_tao_bang($pdo);
    $pdo->exec('DELETE FROM dang_nhap_sai WHERE thoi_gian < NOW() - INTERVAL 1 DAY');
    $pdo->prepare('INSERT INTO dang_nhap_sai (username, ip) VALUES (?, ?)')->execute([$username, dn_ip()]);
    [$n] = dn_dem($pdo, 'username = ? AND ip = ?', [$username, dn_ip()]);
    return max(0, DN_SAI_TOI_DA - $n);
}

function dn_xoa_sai(PDO $pdo, string $username): void
{
    dn_tao_bang($pdo);
    $pdo->prepare('DELETE FROM dang_nhap_sai WHERE username = ? AND ip = ?')->execute([$username, dn_ip()]);
}

// ===================== QUY TẮC MẬT KHẨU =====================

/** Trả về thông báo lỗi nếu mật khẩu yếu, null nếu hợp lệ */
function loi_mat_khau(string $pw, string $username): ?string
{
    if (mb_strlen($pw) < 8) return 'Mật khẩu tối thiểu 8 ký tự.';
    if (strlen($pw) > 72) return 'Mật khẩu quá dài (tối đa 72 ký tự).';   // giới hạn của bcrypt
    if (!preg_match('/\pL/u', $pw) || !preg_match('/\d/', $pw)) return 'Mật khẩu phải có cả chữ và số.';
    if ($username !== '' && mb_stripos($pw, $username) !== false) return 'Mật khẩu không được chứa tên đăng nhập.';
    return null;
}

// ===================== THÔNG BÁO (FLASH) =====================
// Lưu thông báo trước khi redirect, trang sau hiển thị 1 lần rồi tự xóa.

function flash(string $type, string $msg): void   // $type: success | danger | warning | info
{
    $_SESSION['flash'][] = [$type, $msg];
}

function show_flash(): void
{
    foreach ($_SESSION['flash'] ?? [] as [$type, $msg]) {
        echo '<div class="alert alert-' . h($type) . ' alert-dismissible fade show">' . h($msg)
           . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    unset($_SESSION['flash']);
}
