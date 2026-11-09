<?php
function xuat_csv(string $name,array $headers,array $rows): void {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$name.'.csv"');
    header('Cache-Control: no-store');
    $out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");
    foreach(array_merge([$headers],$rows) as $row) {
        // Không để Excel thực thi công thức từ dữ liệu người dùng.
        $row=array_map(static fn($v)=>is_string($v)&&preg_match('/^[\s]*[=+@-]/u',$v)?"'".$v:$v,$row);
        fputcsv($out,$row);
    }
    fclose($out);exit;
}
