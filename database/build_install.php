<?php
// Tạo bộ SQL cài mới từ cấu trúc hiện tại và dữ liệu mẫu gốc.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../config/db.php';require __DIR__.'/../includes/backup.php';
$original=file_get_contents(__DIR__.'/sample_data.sql');
$seed=substr($original,strpos($original,'INSERT INTO users'));
$seed=str_replace("UPDATE sinh_vien SET khoa_hoc='K22';",'', $seed);
$schema="-- CÀI MỚI: xóa dữ liệu cũ. Với website đang dùng, chạy upgrade_features.php.\nSET NAMES utf8mb4;\nCREATE DATABASE IF NOT EXISTS quanlydiem CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\nUSE quanlydiem;\nSET FOREIGN_KEY_CHECKS=0;\n";
foreach(array_reverse(BACKUP_TABLES) as $t)$schema.="DROP TABLE IF EXISTS `$t`;\n";
$schema.="SET FOREIGN_KEY_CHECKS=1;\n";
foreach(BACKUP_TABLES as $t){$r=$pdo->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_NUM);$schema.=$r[1].";\n\n";}
$extra="INSERT INTO lop_hanh_chinh (ma_lop,ten_lop,khoa_hoc) VALUES ('CNTT1-K22','Công nghệ thông tin 1 - K22','K22'),('CNTT2-K22','Công nghệ thông tin 2 - K22','K22');\n";
preg_match_all('/20\d{2}-20\d{2}/',$seed,$years);foreach(array_unique($years[0]) as $year)$extra.="INSERT IGNORE INTO nam_hoc VALUES ('$year');\n";
$seed=$extra.$seed."\nUPDATE sinh_vien SET khoa_hoc='K22';\n";
file_put_contents(__DIR__.'/schema.sql',$schema);
file_put_contents(__DIR__.'/sample_data.sql',"-- Chạy sau schema.sql, trên cơ sở dữ liệu trống.\nSET NAMES utf8mb4;\nUSE quanlydiem;\n".$seed);
file_put_contents(__DIR__.'/quanlydiem.sql',$schema."\n-- DỮ LIỆU MẪU\n".$seed);
echo "Built schema.sql, sample_data.sql and quanlydiem.sql\n";
