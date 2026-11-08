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
        ['sinhvien/bang_diem.php', 'Bảng điểm của tôi', 'bi-file-earmark-text', ['sinhvien']],
    ],
    'Quản trị' => [
        ['admin/users.php', 'Người dùng', 'bi-people', ['admin']],
        ['admin/sinhvien.php', 'Sinh viên', 'bi-mortarboard', ['admin']],
        ['admin/giangvien.php', 'Giảng viên', 'bi-person-badge', ['admin']],
        ['admin/monhoc.php', 'Môn học & trọng số', 'bi-journal-bookmark', ['admin']],
        ['admin/hocky.php', 'Học kỳ', 'bi-calendar3', ['admin']],
        ['admin/lophocphan.php', 'Lớp học phần', 'bi-collection', ['admin']],
    ],
    'Điểm' => [
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
</head>
<body class="<?= $u ? 'has-sidebar' : 'guest' ?>">
<?php if ($u): ?>
<div class="app">
    <!-- ===== SIDEBAR (màn hình nhỏ: thành menu trượt) ===== -->
    <aside class="sidebar offcanvas-lg offcanvas-start no-print" tabindex="-1" id="sidebar">
        <div class="sidebar-brand">
            <span class="brand-logo"><i class="bi bi-mortarboard-fill"></i></span>
            <div>
                <div class="brand-name">QL Điểm</div>
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
                    <a class="nav-item-link <?= $active === $href ? 'active' : '' ?>" href="<?= BASE_URL . $href ?>">
                        <i class="bi <?= $icon ?>"></i><span><?= h($text) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <a class="nav-item-link <?= $page === 'doi_mat_khau.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>doi_mat_khau.php"><i class="bi bi-key"></i><span>Đổi mật khẩu</span></a>
            <a class="nav-item-link text-danger-emphasis" href="<?= BASE_URL ?>logout.php"><i class="bi bi-box-arrow-right"></i><span>Đăng xuất</span></a>
        </div>
    </aside>

    <!-- ===== VÙNG NỘI DUNG ===== -->
    <div class="app-main">
        <header class="topbar no-print">
            <button class="btn btn-icon d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-label="Mở menu"><i class="bi bi-list"></i></button>
            <div class="topbar-title">
                <span class="text-muted d-none d-sm-inline">Trang chủ</span>
                <i class="bi bi-chevron-right text-muted small d-none d-sm-inline"></i>
                <span class="fw-semibold"><?= h($title ?? '') ?></span>
            </div>
            <div class="dropdown ms-auto">
                <button class="user-chip" data-bs-toggle="dropdown">
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
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>doi_mat_khau.php"><i class="bi bi-key me-2"></i>Đổi mật khẩu</a></li>
                    <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</a></li>
                </ul>
            </div>
        </header>
        <main class="content">
<?php else: ?>
<main class="guest-main">
<?php endif; ?>
<?php show_flash(); ?>
