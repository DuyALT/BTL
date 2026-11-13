<?php
// Công cụ dòng lệnh: đặt lại mật khẩu về mặc định và mở khóa đăng nhập sai.
// Dùng khi Admin quên mật khẩu hoặc bị tạm khóa, và để kiểm thử HTTP tự dọn dẹp.
//   C:\xampp\php\php.exe database/dat_lai_mat_khau.php admin gv001
//   C:\xampp\php\php.exe database/dat_lai_mat_khau.php --chi-mo-khoa admin
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$args = array_slice($argv, 1);
$chiMoKhoa = in_array('--chi-mo-khoa', $args, true);
$names = array_values(array_filter($args, static fn($a) => $a !== '--chi-mo-khoa'));
if (!$names) { fwrite(STDERR, "Cách dùng: php database/dat_lai_mat_khau.php [--chi-mo-khoa] <tên đăng nhập>...\n"); exit(1); }

$hasLog = (bool)$pdo->query("SHOW TABLES LIKE 'dang_nhap_sai'")->fetch();
foreach ($names as $name) {
    if ($hasLog) $pdo->prepare('DELETE FROM dang_nhap_sai WHERE username = ?')->execute([$name]);
    if ($chiMoKhoa) { echo "Đã mở khóa đăng nhập: $name\n"; continue; }
    $st = $pdo->prepare('UPDATE users SET password_hash = ? WHERE username = ?');
    $st->execute([password_hash(MAT_KHAU_MAC_DINH, PASSWORD_DEFAULT), $name]);
    echo $st->rowCount() ? "Đã đặt lại mật khẩu mặc định: $name\n" : "Không có tài khoản: $name (chỉ xóa lần đăng nhập sai)\n";
}
