<?php
require_once __DIR__ . '/includes/auth.php';
// Chỉ đăng xuất bằng POST có mã CSRF: trang khác không thể ép người dùng đăng xuất qua 1 đường link
if (!current_user()) redirect('login.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');
csrf_check();
session_unset();
session_destroy();
redirect('login.php?msg=logout');
