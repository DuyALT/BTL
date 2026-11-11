<?php
// =====================================================================
//  header.php — Khung giao diện chung: <head>, menu trái (sidebar), thanh trên (topbar)
//  Trang nào cũng: đặt $title rồi require header.php ... require footer.php
// =====================================================================
$u = current_user();
$page = substr($_SERVER['SCRIPT_NAME'], strlen(BASE_URL));   // vd: admin/users.php

// Menu: [đường dẫn, chữ, icon Bootstrap Icons, các vai trò được thấy]
$menu = [
    '' => [
        ['index.php', 'Tổng quan', 'bi-grid-1x2', ['admin', 'giangvien']],
        ['index.php#hocphan', 'Học phần của tôi', 'bi-journal-text', ['giangvien']],
        ['sinhvien/tong_quan.php', 'Thông tin cá nhân', 'bi-person-circle', ['sinhvien']],
        ['sinhvien/bang_diem.php', 'Bảng điểm của tôi', 'bi-file-earmark-text', ['sinhvien']],
    ],
    'Quản trị' => [
        ['admin/users.php', 'Người dùng', 'bi-people', ['admin']],
        ['admin/sinhvien.php', 'Sinh viên', 'bi-mortarboard', ['admin']],
        ['admin/lop.php', 'Lớp hành chính', 'bi-building', ['admin']],
        ['admin/giangvien.php', 'Giảng viên', 'bi-person-badge', ['admin']],
        ['admin/monhoc.php', 'Môn học', 'bi-journal-bookmark', ['admin']],
        ['admin/hocky.php', 'Học kỳ', 'bi-calendar3', ['admin']],
        ['admin/namhoc.php', 'Năm học', 'bi-calendar-range', ['admin']],
        ['admin/lophocphan.php', 'Lớp học phần', 'bi-collection', ['admin']],
        ['admin/saoluu.php', 'Sao lưu & khôi phục', 'bi-database-check', ['admin']],
    ],
    'Điểm' => [
        ['tra_cuu.php', 'Tra cứu bảng điểm', 'bi-search', ['admin', 'giangvien', 'sinhvien']],
        ['giangvien/nhap_diem.php', 'Nhập điểm', 'bi-pencil-square', ['admin', 'giangvien']],
        ['giangvien/lich_su.php', 'Lịch sử sửa điểm', 'bi-clock-history', ['admin', 'giangvien']],
        ['giangvien/in_bang_diem.php', 'In bảng điểm', 'bi-printer', ['admin', 'giangvien']],
    ],
    'Báo cáo' => [
        ['thongke.php', 'Thống kê', 'bi-bar-chart-line', ['admin', 'giangvien']],
    ],
];
// Trang con được tính là thuộc mục menu nào (để tô sáng)
$menuCha = ['admin/lhp_sinhvien.php' => 'admin/lophocphan.php'];
$active = $menuCha[$page] ?? $page;

// Chữ cái đầu của tên để làm avatar, vd "Nguyễn Văn Hùng" -> "H"
$avatar = $u ? mb_strtoupper(mb_substr(trim(strrchr(' ' . $u['ho_ten'], ' ')), 0, 1)) : '';
$pageDescriptions = [
    'index.php'=>'Theo dõi học phần, tiến độ nhập điểm và kết quả học tập trong phạm vi phụ trách.',
    'admin/users.php'=>'Quản lý tài khoản, quyền truy cập và trạng thái hoạt động.',
    'admin/sinhvien.php'=>'Tra cứu hồ sơ sinh viên, lớp hành chính và thông tin học tập.',
    'admin/giangvien.php'=>'Quản lý giảng viên và các học phần được phân công.',
    'admin/lop.php'=>'Quản lý lớp hành chính, khóa học và danh sách sinh viên.',
    'admin/lophocphan.php'=>'Tổ chức học phần theo môn học, học kỳ và giảng viên phụ trách.',
    'admin/lhp_sinhvien.php'=>'Quản lý danh sách sinh viên đăng ký học phần.',
    'admin/monhoc.php'=>'Quản lý danh mục môn học và số tín chỉ.',
    'admin/hocky.php'=>'Tổ chức học kỳ và quản lý thời gian nhập điểm.',
    'admin/namhoc.php'=>'Quản lý danh sách năm học của hệ thống.',
    'admin/saoluu.php'=>'Lưu giữ và khôi phục dữ liệu dành cho quản trị viên.',
    'giangvien/nhap_diem.php'=>'Nhập điểm thành phần, xem trước tổng kết và lưu điểm chính thức.',
    'giangvien/lich_su.php'=>'Tra cứu thay đổi điểm, người thực hiện và lý do chỉnh sửa.',
    'giangvien/in_bang_diem.php'=>'Chuẩn bị bảng điểm học phần hoặc lớp để in và xuất dữ liệu.',
    'sinhvien/tong_quan.php'=>'Thông tin cá nhân và kết quả các học phần của bạn.',
    'sinhvien/bang_diem.php'=>'Theo dõi điểm chính thức được công bố theo từng học kỳ.',
    'tra_cuu.php'=>'Tìm kiếm, lọc và xuất bảng điểm theo quyền truy cập của bạn.',
    'thongke.php'=>'Phân tích kết quả học tập từ dữ liệu điểm chính thức.',
    'doi_mat_khau.php'=>'Cập nhật mật khẩu để bảo vệ tài khoản của bạn.',
];
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h(($title ?? '') . ' | ' . APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/eduscore.css?v=2" rel="stylesheet">
    <?php if($page==='login.php'): ?><link href="<?= BASE_URL ?>assets/css/login-campus.css?v=1" rel="stylesheet"><?php endif; ?>
    <script>try{if(localStorage.getItem('eduscore-sidebar')==='collapsed')document.documentElement.dataset.sidebar='collapsed';}catch(e){}</script>
</head>
<body class="<?= $u ? 'has-sidebar' : 'guest' ?>">
<a class="skip-link" href="#main-content">Đi đến nội dung chính</a>
<?php if ($u): ?>
<div class="app">
    <!-- ===== SIDEBAR (màn hình nhỏ: thành menu trượt) ===== -->
    <aside class="sidebar offcanvas-lg offcanvas-start no-print" tabindex="-1" id="sidebar">
        <div class="sidebar-brand">
            <?php require __DIR__ . '/brand-logo.php'; ?>
            <div class="brand-copy">
                <div class="brand-name">EduScore.</div>
                <div class="brand-sub">Quản lý điểm sinh viên</div>
            </div>
            <button type="button" class="btn-close btn-close-white ms-auto d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebar"></button>
        </div>
        <nav class="sidebar-nav">
            <?php foreach ($menu as $nhom => $items):
                $items = array_filter($items, fn($i) => in_array($u['role'], $i[3], true));
                if (!$items) continue; ?>
                <?php if ($nhom !== ''): ?><div class="nav-group"><?= h($nhom) ?></div><?php endif; ?>
                <?php foreach ($items as [$href, $text, $icon]): ?>
                    <a class="nav-item-link <?= $active === $href ? 'active' : '' ?>" href="<?= BASE_URL . $href ?>" title="<?= h($text) ?>" <?= $active===$href?'aria-current="page"':'' ?>>
                        <i class="bi <?= $icon ?>"></i><span><?= h($text) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <p class="workspace-label">KHÔNG GIAN HỌC TẬP · <?= h(TEN_VAI_TRO[$u['role']]) ?></p>
            <a class="nav-item-link <?= $page === 'doi_mat_khau.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>doi_mat_khau.php"><i class="bi bi-key"></i><span>Đổi mật khẩu</span></a>
            <a class="nav-item-link text-danger-emphasis" href="<?= BASE_URL ?>logout.php" data-confirm="Bạn có chắc chắn muốn đăng xuất không?"><i class="bi bi-box-arrow-right"></i><span>Đăng xuất</span></a>
        </div>
    </aside>

    <!-- ===== VÙNG NỘI DUNG ===== -->
    <div class="app-main">
        <header class="topbar no-print">
            <button type="button" class="btn-icon d-none d-lg-inline-grid" id="sidebarToggle" aria-label="Thu gọn menu" aria-expanded="true"><i class="bi bi-layout-sidebar"></i></button>
            <button class="btn btn-icon d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-label="Mở menu"><i class="bi bi-list"></i></button>
            <div class="topbar-title">
                <a class="d-none d-sm-inline" href="<?= BASE_URL ?>index.php">Trang chủ</a>
                <i class="bi bi-chevron-right text-muted small d-none d-sm-inline"></i>
                <span class="fw-semibold"><?= h($title ?? '') ?></span>
            </div>
            <span class="topbar-role ms-auto"><?= h(TEN_VAI_TRO[$u['role']]) ?></span>
            <div class="dropdown ms-auto ms-xl-0">
                <button type="button" class="user-chip" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Mở menu tài khoản">
                    <span class="avatar"><?= h($avatar) ?></span>
                    <span class="d-none d-md-block text-start lh-sm">
                        <span class="d-block fw-semibold"><?= h($u['ho_ten']) ?></span>
                        <span class="d-block small text-muted"><?= h(TEN_VAI_TRO[$u['role']]) ?></span>
                    </span>
                    <i class="bi bi-chevron-down small text-muted"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><span class="dropdown-item-text small text-muted">Đăng nhập: <b><?= h($u['username']) ?></b></span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#profileModal"><i class="bi bi-person-circle me-2"></i>Hồ sơ cá nhân</button></li>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>doi_mat_khau.php"><i class="bi bi-key me-2"></i>Đổi mật khẩu</a></li>
                    <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout.php" data-confirm="Bạn có chắc chắn muốn đăng xuất không?"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</a></li>
                </ul>
            </div>
        </header>
        <main class="content" id="main-content" tabindex="-1">
        <div class="page-intro no-print"><div><div class="page-eyebrow">EDUSCORE / <?= h(TEN_VAI_TRO[$u['role']]) ?></div><h1><?= h($title??'Tổng quan') ?></h1><p><?= h($pageDescriptions[$page]??'Quản lý thông tin học tập và kết quả sinh viên.') ?></p></div><div class="page-intro-symbol" aria-hidden="true"><i class="bi bi-mortarboard"></i></div></div>
<?php else: ?>
<main class="guest-main" id="main-content">
<?php endif; ?>
<?php show_flash(); ?>
