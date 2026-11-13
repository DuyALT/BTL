<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../includes/functions.php';
$cases = [
    [['tx' => '[7,8,9]', 'kt' => 8, 'thi' => 9], 8.38],
    [['tx' => '[10]', 'kt' => 0, 'thi' => 0], 1.67],
    [['tx' => '[0,0]', 'kt' => 0, 'thi' => 0], 0.0],
    [['tx' => '[]', 'kt' => 8, 'thi' => 9], null],
    [['tx' => '[8]', 'kt' => null, 'thi' => 9], null],
    [['tx' => '[8]', 'kt' => 8, 'thi' => null], null],
];
foreach ($cases as [$data, $expected]) {
    if (tinh_tong_ket($data, []) !== $expected) throw new RuntimeException('Sai công thức');
}
if (parse_diem('10.1') !== false || parse_diem('-1') !== false || parse_diem('7,5') !== 7.5 || parse_diem([]) !== false) throw new RuntimeException('Sai kiểm tra điểm');
$results = [[4.9, 8, 'Không đạt'], [7.0, 4.9, 'Không đạt'], [4.9, 4.9, 'Không đạt'], [5.0, 5.0, 'Đạt'], [8.0, 6.0, 'Đạt'], [null, 8, null], [6.0, null, null]];
foreach ($results as [$total, $final, $expected]) {
    if (xet_ket_qua($total, ['thi' => $final]) !== $expected) throw new RuntimeException('Sai điều kiện đạt');
}
echo "Passed 6 calculation cases, 7 pass/fail cases and score validation.\n";
