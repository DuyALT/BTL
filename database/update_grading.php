<?php
// Chạy bằng CLI: php database/update_grading.php. Không xóa điểm cũ.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/functions.php';
$backup = __DIR__ . '/grading_backup_' . date('Ymd_His') . '.php';
file_put_contents($backup, "<?php exit; ?>\n" . json_encode(['diem' => $pdo->query('SELECT * FROM diem')->fetchAll(), 'mon_hoc' => $pdo->query('SELECT * FROM mon_hoc')->fetchAll()], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
if (!$pdo->query("SHOW COLUMNS FROM diem LIKE 'tx'")->fetch()) {
    $pdo->exec('ALTER TABLE diem ADD tx TEXT NULL AFTER cc');
    $pdo->exec('UPDATE diem SET tx = JSON_ARRAY(cc) WHERE cc IS NOT NULL');
}
$pdo->beginTransaction();
$pdo->exec('UPDATE mon_hoc SET diem_dat=5.0');
foreach ($pdo->query('SELECT id FROM mon_hoc')->fetchAll() as $m) tinh_lai_theo_mon($pdo, (int)$m['id']);
$pdo->commit();
echo "Updated grading; backup: $backup\n";
