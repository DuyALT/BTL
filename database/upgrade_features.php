<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
$tables = ['users','giang_vien','sinh_vien','hoc_ky','mon_hoc','lop_hoc_phan','dang_ky','diem','lich_su_diem'];
$snapshot = [];
foreach ($tables as $table) $snapshot[$table] = $pdo->query("SELECT * FROM `$table`")->fetchAll();
file_put_contents(__DIR__ . '/features_backup_' . date('Ymd_His') . '.php', "<?php exit; ?>\n" . json_encode($snapshot, JSON_UNESCAPED_UNICODE));
function add_column(PDO $pdo, string $table, string $name, string $definition): void {
    if (!$pdo->query("SHOW COLUMNS FROM `$table` LIKE '$name'")->fetch()) $pdo->exec("ALTER TABLE `$table` ADD `$name` $definition");
}
$pdo->exec("CREATE TABLE IF NOT EXISTS lop_hanh_chinh (ma_lop VARCHAR(20) PRIMARY KEY, ten_lop VARCHAR(100) NOT NULL, khoa_hoc VARCHAR(30) NOT NULL DEFAULT '') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$pdo->exec("INSERT IGNORE INTO lop_hanh_chinh (ma_lop,ten_lop) SELECT DISTINCT lop,lop FROM sinh_vien");
$pdo->exec("CREATE TABLE IF NOT EXISTS nam_hoc (nam_hoc VARCHAR(9) PRIMARY KEY) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$pdo->exec('INSERT IGNORE INTO nam_hoc SELECT DISTINCT nam_hoc FROM hoc_ky');
$yearFk=$pdo->query("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='hoc_ky' AND CONSTRAINT_NAME='fk_hk_nam'")->fetchColumn();
if(!$yearFk)$pdo->exec('ALTER TABLE hoc_ky ADD CONSTRAINT fk_hk_nam FOREIGN KEY (nam_hoc) REFERENCES nam_hoc(nam_hoc) ON UPDATE CASCADE');
add_column($pdo, 'sinh_vien', 'email', 'VARCHAR(100) NULL');
add_column($pdo, 'sinh_vien', 'khoa_hoc', "VARCHAR(30) NOT NULL DEFAULT ''");
add_column($pdo, 'sinh_vien', 'trang_thai_hoc', "ENUM('Đang học','Bảo lưu','Đã tốt nghiệp','Thôi học') NOT NULL DEFAULT 'Đang học'");
add_column($pdo, 'hoc_ky', 'trang_thai', "ENUM('Mở','Đóng') NOT NULL DEFAULT 'Mở'");
add_column($pdo, 'lop_hoc_phan', 'diem_chinh_thuc', 'TINYINT(1) NOT NULL DEFAULT 0');
add_column($pdo, 'lop_hoc_phan', 'chot_luc', 'DATETIME NULL');
add_column($pdo, 'lop_hoc_phan', 'chot_boi', 'INT NULL');
add_column($pdo, 'lop_hoc_phan', 'phien_ban', 'INT NOT NULL DEFAULT 0');
add_column($pdo, 'lich_su_diem', 'gia_tri_cu_text', 'TEXT NULL');
add_column($pdo, 'lich_su_diem', 'gia_tri_moi_text', 'TEXT NULL');
add_column($pdo, 'diem', 'tx', 'TEXT NULL');
$pdo->exec('ALTER TABLE diem MODIFY tong_ket DECIMAL(4,2) NULL, MODIFY cc DECIMAL(4,2) NULL, MODIFY kt DECIMAL(4,2) NULL, MODIFY thi DECIMAL(4,2) NULL');
$pdo->exec('ALTER TABLE lich_su_diem MODIFY gia_tri_cu DECIMAL(4,2) NULL, MODIFY gia_tri_moi DECIMAL(4,2) NULL');
$pdo->exec('UPDATE diem SET tx=JSON_ARRAY(cc) WHERE tx IS NULL AND cc IS NOT NULL');
$hasFk = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='sinh_vien' AND CONSTRAINT_NAME='fk_sv_lop'")->fetchColumn();
if (!$hasFk) $pdo->exec('ALTER TABLE sinh_vien ADD CONSTRAINT fk_sv_lop FOREIGN KEY (lop) REFERENCES lop_hanh_chinh(ma_lop) ON UPDATE CASCADE');
$pdo->beginTransaction();
$pdo->exec('UPDATE mon_hoc SET diem_dat=5');
foreach ($pdo->query('SELECT id FROM mon_hoc')->fetchAll() as $m) tinh_lai_theo_mon($pdo, (int)$m['id']);
$pdo->commit();
echo "Feature upgrade complete. Existing grades remain drafts until explicitly finalized.\n";
