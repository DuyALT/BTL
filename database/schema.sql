-- CÀI MỚI: xóa dữ liệu cũ. Với website đang dùng, chạy upgrade_features.php.
SET NAMES utf8mb4;
CREATE DATABASE IF NOT EXISTS quanlydiem CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quanlydiem;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `dang_nhap_sai`;
DROP TABLE IF EXISTS `lich_su_diem`;
DROP TABLE IF EXISTS `diem`;
DROP TABLE IF EXISTS `dang_ky`;
DROP TABLE IF EXISTS `lop_hoc_phan`;
DROP TABLE IF EXISTS `mon_hoc`;
DROP TABLE IF EXISTS `hoc_ky`;
DROP TABLE IF EXISTS `nam_hoc`;
DROP TABLE IF EXISTS `sinh_vien`;
DROP TABLE IF EXISTS `lop_hanh_chinh`;
DROP TABLE IF EXISTS `giang_vien`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS=1;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','giangvien','sinhvien') NOT NULL,
  `trang_thai` enum('active','locked') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `giang_vien` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ma_gv` varchar(20) NOT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `khoa` varchar(100) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ma_gv` (`ma_gv`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `fk_gv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `lop_hanh_chinh` (
  `ma_lop` varchar(20) NOT NULL,
  `ten_lop` varchar(100) NOT NULL,
  `khoa_hoc` varchar(30) NOT NULL DEFAULT '',
  PRIMARY KEY (`ma_lop`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sinh_vien` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ma_sv` varchar(20) NOT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `ngay_sinh` date DEFAULT NULL,
  `gioi_tinh` enum('Nam','Nữ') DEFAULT NULL,
  `lop` varchar(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `khoa_hoc` varchar(30) NOT NULL DEFAULT '',
  `trang_thai_hoc` enum('Đang học','Bảo lưu','Đã tốt nghiệp','Thôi học') NOT NULL DEFAULT 'Đang học',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ma_sv` (`ma_sv`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_sv_lop` (`lop`),
  CONSTRAINT `fk_sv_lop` FOREIGN KEY (`lop`) REFERENCES `lop_hanh_chinh` (`ma_lop`) ON UPDATE CASCADE,
  CONSTRAINT `fk_sv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `nam_hoc` (
  `nam_hoc` varchar(9) NOT NULL,
  PRIMARY KEY (`nam_hoc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `hoc_ky` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ma_hk` varchar(10) NOT NULL,
  `ten_hk` varchar(50) NOT NULL,
  `nam_hoc` varchar(9) NOT NULL,
  `trang_thai` enum('Mở','Đóng') NOT NULL DEFAULT 'Mở',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ma_hk` (`ma_hk`),
  KEY `fk_hk_nam` (`nam_hoc`),
  CONSTRAINT `fk_hk_nam` FOREIGN KEY (`nam_hoc`) REFERENCES `nam_hoc` (`nam_hoc`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `mon_hoc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ma_mon` varchar(20) NOT NULL,
  `ten_mon` varchar(150) NOT NULL,
  `so_tin_chi` tinyint(3) unsigned NOT NULL CHECK (`so_tin_chi` between 1 and 10),
  `ts_cc` tinyint(3) unsigned NOT NULL DEFAULT 10,
  `ts_kt` tinyint(3) unsigned NOT NULL DEFAULT 20,
  `ts_th` tinyint(3) unsigned NOT NULL DEFAULT 20,
  `ts_thi` tinyint(3) unsigned NOT NULL DEFAULT 50,
  `diem_dat` decimal(3,1) NOT NULL DEFAULT 4.0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ma_mon` (`ma_mon`),
  CONSTRAINT `chk_tong_trong_so` CHECK (`ts_cc` + `ts_kt` + `ts_th` + `ts_thi` = 100),
  CONSTRAINT `chk_diem_dat` CHECK (`diem_dat` between 0 and 10)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `lop_hoc_phan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ma_lhp` varchar(30) NOT NULL,
  `mon_hoc_id` int(11) NOT NULL,
  `hoc_ky_id` int(11) NOT NULL,
  `giang_vien_id` int(11) DEFAULT NULL,
  `si_so_toi_da` int(11) NOT NULL DEFAULT 60,
  `diem_chinh_thuc` tinyint(1) NOT NULL DEFAULT 0,
  `chot_luc` datetime DEFAULT NULL,
  `chot_boi` int(11) DEFAULT NULL,
  `phien_ban` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ma_lhp` (`ma_lhp`),
  KEY `fk_lhp_mon` (`mon_hoc_id`),
  KEY `fk_lhp_hk` (`hoc_ky_id`),
  KEY `fk_lhp_gv` (`giang_vien_id`),
  CONSTRAINT `fk_lhp_gv` FOREIGN KEY (`giang_vien_id`) REFERENCES `giang_vien` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_lhp_hk` FOREIGN KEY (`hoc_ky_id`) REFERENCES `hoc_ky` (`id`),
  CONSTRAINT `fk_lhp_mon` FOREIGN KEY (`mon_hoc_id`) REFERENCES `mon_hoc` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `dang_ky` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lhp_id` int(11) NOT NULL,
  `sinh_vien_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dk` (`lhp_id`,`sinh_vien_id`),
  KEY `fk_dk_sv` (`sinh_vien_id`),
  CONSTRAINT `fk_dk_lhp` FOREIGN KEY (`lhp_id`) REFERENCES `lop_hoc_phan` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dk_sv` FOREIGN KEY (`sinh_vien_id`) REFERENCES `sinh_vien` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=161 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `diem` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dang_ky_id` int(11) NOT NULL,
  `cc` decimal(4,2) DEFAULT NULL,
  `tx` text DEFAULT NULL,
  `kt` decimal(4,2) DEFAULT NULL,
  `th` decimal(3,1) DEFAULT NULL CHECK (`th` between 0 and 10),
  `thi` decimal(4,2) DEFAULT NULL,
  `tong_ket` decimal(4,2) DEFAULT NULL,
  `ket_qua` enum('Đạt','Không đạt') DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dang_ky_id` (`dang_ky_id`),
  KEY `fk_diem_user` (`updated_by`),
  CONSTRAINT `fk_diem_dk` FOREIGN KEY (`dang_ky_id`) REFERENCES `dang_ky` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_diem_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=161 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `lich_su_diem` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `diem_id` int(11) NOT NULL,
  `nguoi_sua` int(11) DEFAULT NULL,
  `cot` varchar(10) NOT NULL,
  `gia_tri_cu` decimal(4,2) DEFAULT NULL,
  `gia_tri_moi` decimal(4,2) DEFAULT NULL,
  `ly_do` varchar(255) DEFAULT NULL,
  `thoi_gian` datetime NOT NULL DEFAULT current_timestamp(),
  `gia_tri_cu_text` text DEFAULT NULL,
  `gia_tri_moi_text` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_ls_diem` (`diem_id`),
  KEY `fk_ls_user` (`nguoi_sua`),
  KEY `idx_ls_time` (`thoi_gian`),
  CONSTRAINT `fk_ls_diem` FOREIGN KEY (`diem_id`) REFERENCES `diem` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ls_user` FOREIGN KEY (`nguoi_sua`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nhật ký đăng nhập sai để chống dò mật khẩu (không đưa vào sao lưu; tự xóa sau 1 ngày)
CREATE TABLE `dang_nhap_sai` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `thoi_gian` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_dns_user_ip` (`username`,`ip`,`thoi_gian`),
  KEY `idx_dns_ip` (`ip`,`thoi_gian`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
