<?php
// =====================================================================
//  auth.php — Phiên đăng nhập, phân quyền, chống CSRF, hàm tiện ích
//  Mọi trang đều require file này ở dòng đầu tiên.
// =====================================================================
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

    // 4. Sai vai trò -> báo không có quyền
    if ($roles && !in_array($user['role'], $roles, true)) {
        forbidden();
    }
    return $user;
}

/** Trang báo lỗi 403 - không có quyền */
function forbidden(string $msg = 'Bạn không có quyền truy cập chức năng này.'): void
{
    http_response_code(403);
    echo '<!doctype html><meta charset="utf-8"><title>Không có quyền</title>'
       . '<div style="font-family:sans-serif;max-width:500px;margin:80px auto;text-align:center">'
       . '<h2>403 – Không có quyền</h2><p>' . h($msg) . '</p>'
       . '<a href="' . BASE_URL . 'index.php">← Về trang chủ</a></div>';
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
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Phiên làm việc không hợp lệ. Hãy tải lại trang và thử lại.');
    }
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
