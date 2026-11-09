<?php
const BACKUP_TABLES=['users','giang_vien','lop_hanh_chinh','sinh_vien','nam_hoc','hoc_ky','mon_hoc','lop_hoc_phan','dang_ky','diem','lich_su_diem'];
function tao_sao_luu(PDO $pdo): array {
    $pdo->beginTransaction();
    try {
        $tables=[];foreach(BACKUP_TABLES as $t)$tables[$t]=$pdo->query("SELECT * FROM `$t`")->fetchAll();
        $pdo->commit();return ['format'=>'quanlydiem-v2','created_at'=>date(DATE_ATOM),'tables'=>$tables];
    } catch(Throwable $e){$pdo->rollBack();throw $e;}
}
function doc_sao_luu(PDO $pdo,string $json): array {
    $data=json_decode($json,true,512,JSON_THROW_ON_ERROR);
    if(($data['format']??'')!=='quanlydiem-v2' || !isset($data['tables']) || !is_array($data['tables'])) throw new RuntimeException('Không đúng định dạng sao lưu của website.');
    $names=array_keys($data['tables']);sort($names);$expected=BACKUP_TABLES;sort($expected);
    if($names!==$expected)throw new RuntimeException('Bản sao lưu thiếu hoặc thừa bảng dữ liệu.');
    foreach(BACKUP_TABLES as $t){
        $fields=$pdo->query("SHOW COLUMNS FROM `$t`")->fetchAll(PDO::FETCH_COLUMN);
        if(!is_array($data['tables'][$t]) || !array_is_list($data['tables'][$t]))throw new RuntimeException('Dữ liệu bảng không hợp lệ.');
        foreach($data['tables'][$t] as $r){
            if(!is_array($r) || count($r)!==count($fields) || array_diff($fields,array_keys($r))) throw new RuntimeException('Cấu trúc dữ liệu không khớp: '.$t);
            foreach($r as $v)if(!is_scalar($v) && $v!==null)throw new RuntimeException('Giá trị sao lưu không hợp lệ.');
            if($t==='diem'){
                $tx=$r['tx']===null?[]:json_decode($r['tx'],true);
                if(!is_array($tx)||!array_is_list($tx))throw new RuntimeException('Danh sách điểm thường xuyên trong sao lưu không hợp lệ.');
                foreach($tx as $v)if(parse_diem($v)===false||$v===null)throw new RuntimeException('Điểm thường xuyên trong sao lưu không hợp lệ.');
                foreach(['kt','thi'] as $c)if(parse_diem($r[$c])===false)throw new RuntimeException('Điểm thành phần trong sao lưu không hợp lệ.');
            }
        }
    }
    if(!array_filter($data['tables']['users'],static fn($r)=>$r['role']==='admin' && $r['trang_thai']==='active'))throw new RuntimeException('Bản sao lưu phải có ít nhất một Admin hoạt động.');
    return $data;
}
function khoi_phuc(PDO $pdo,array $data): void {
    $pdo->beginTransaction();
    try{
        // Giữ khóa ngoại hoạt động; mọi lỗi sẽ rollback cả quá trình.
        foreach(array_reverse(BACKUP_TABLES) as $t)$pdo->exec("DELETE FROM `$t`");
        foreach(BACKUP_TABLES as $t){
            $fields=$pdo->query("SHOW COLUMNS FROM `$t`")->fetchAll(PDO::FETCH_COLUMN);
            $sql="INSERT INTO `$t` (`".implode('`,`',$fields).'`) VALUES ('.implode(',',array_fill(0,count($fields),'?')).')';
            $st=$pdo->prepare($sql);
            foreach($data['tables'][$t] as $r)$st->execute(array_map(static fn($k)=>$r[$k],$fields));
        }
        // Loại bỏ tổng kết/kết quả bị chỉnh tay trong tệp nhập.
        foreach($pdo->query('SELECT id FROM mon_hoc')->fetchAll() as $m)tinh_lai_theo_mon($pdo,(int)$m['id']);
        if($pdo->query('SELECT COUNT(*) FROM diem d JOIN dang_ky dk ON dk.id=d.dang_ky_id JOIN lop_hoc_phan l ON l.id=dk.lhp_id WHERE l.diem_chinh_thuc=1 AND d.tong_ket IS NULL')->fetchColumn())throw new RuntimeException('Bản sao lưu có điểm chính thức chưa đủ thành phần.');
        $pdo->commit();
    } catch(Throwable $e){$pdo->rollBack();throw $e;}
}
