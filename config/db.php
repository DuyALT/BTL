<?php
// ===== THÔNG TIN KẾT NỐI (mặc định của XAMPP) =====
define('DB_HOST', 'localhost');
define('DB_NAME', 'quanlydiem');   // đúng tên database trong phpMyAdmin
define('DB_USER', 'root');
define('DB_PASS', '');             // XAMPP mặc định không có mật khẩu

// Đường dẫn gốc của web (tên thư mục trong htdocs), dùng cho link/CSS/JS/redirect
define('BASE_URL', '/BTL/');

date_default_timezone_set('Asia/Ho_Chi_Minh');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // SQL lỗi -> ném ngoại lệ
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // kết quả dạng $row['ten_cot']
            PDO::ATTR_EMULATE_PREPARES   => false,                  // prepared statement thật
        ]
    );
} catch (PDOException $e) {
    die('Không kết nối được CSDL. Kiểm tra MySQL trong XAMPP đã bật và đã import quanlydiem.sql chưa.');
}
