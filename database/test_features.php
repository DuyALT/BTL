<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../config/db.php';require __DIR__.'/../includes/functions.php';require __DIR__.'/../includes/grade-service.php';require __DIR__.'/../includes/backup.php';
$server=new PDO('mysql:host='.DB_HOST.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$testName='quanlydiem_test_'.bin2hex(random_bytes(5));$created=false;$passed=0;
function check($ok,$msg){global $passed;if(!$ok)throw new RuntimeException('FAIL: '.$msg);$passed++;echo "PASS: $msg\n";}
function rejected(callable $f,string $text){try{$f();}catch(RuntimeException $e){check(str_contains($e->getMessage(),$text),$text);return;}throw new RuntimeException('Expected rejection: '.$text);}
try{
    $server->exec("CREATE DATABASE `$testName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");$created=true;
    $db=new PDO('mysql:host='.DB_HOST.';dbname='.$testName.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
    $sql=str_replace('quanlydiem',$testName,file_get_contents(__DIR__.'/schema.sql')."\n".file_get_contents(__DIR__.'/sample_data.sql'));$db->exec($sql);
    check((int)$db->query('SELECT COUNT(*) FROM sinh_vien')->fetchColumn()===40,'Cài mới từ SQL và 40 sinh viên mẫu');
    $lhp=$db->query('SELECT * FROM lop_hoc_phan ORDER BY id LIMIT 1')->fetch();$id=(int)$lhp['id'];
    $teacher=$db->query('SELECT u.* FROM users u JOIN giang_vien g ON g.user_id=u.id WHERE g.id='.(int)$lhp['giang_vien_id'])->fetch();
    $admin=$db->query("SELECT * FROM users WHERE role='admin' LIMIT 1")->fetch();
    $student=$db->query("SELECT * FROM users WHERE role='sinhvien' LIMIT 1")->fetch();
    $rows=$db->query('SELECT d.* FROM diem d JOIN dang_ky dk ON dk.id=d.dang_ky_id WHERE dk.lhp_id='.$id)->fetchAll();$input=[];
    foreach($rows as $r)$input[$r['id']]=['cc'=>'7; 8; 9','kt'=>'8','thi'=>'9'];$first=(int)$rows[0]['id'];
    $version=fn()=>(int)$db->query('SELECT phien_ban FROM lop_hoc_phan WHERE id='.$id)->fetchColumn();
    luu_diem($db,$teacher,$id,$input,'',false,$version());
    check((float)$db->query('SELECT tong_ket FROM diem WHERE id='.$first)->fetchColumn()===8.38,'Lưu nháp và tổng kết hai chữ số');
    $invalid=$input;$invalid[$first]['cc']='7; abc';rejected(fn()=>luu_diem($db,$teacher,$id,$invalid,'',false,$version()),'Mỗi điểm thường xuyên');
    $invalid=$input;$invalid[$first]['thi']='';rejected(fn()=>luu_diem($db,$teacher,$id,$invalid,'',true,$version()),'Chưa đủ điểm');
    $invalid=$input;$invalid[$first]['thi']=[8,9];rejected(fn()=>luu_diem($db,$teacher,$id,$invalid,'',false,$version()),'Giữa HP và cuối HP');
    rejected(fn()=>luu_diem($db,$student,$id,$input,'',false,$version()),'Bạn không được sửa');
    $other=$db->query("SELECT u.* FROM users u JOIN giang_vien g ON g.user_id=u.id WHERE g.id<>".(int)$lhp['giang_vien_id'].' LIMIT 1')->fetch();rejected(fn()=>luu_diem($db,$other,$id,$input,'',false,$version()),'Bạn không được sửa');
    rejected(fn()=>luu_diem($db,$teacher,$id,$input,'',false,0),'phiên khác');
    $db->exec("UPDATE hoc_ky SET trang_thai='Đóng' WHERE id=".(int)$lhp['hoc_ky_id']);rejected(fn()=>luu_diem($db,$teacher,$id,$input,'',false,$version()),'Học kỳ đã đóng');$db->exec("UPDATE hoc_ky SET trang_thai='Mở'");
    luu_diem($db,$teacher,$id,$input,'',true,$version());check((int)$db->query('SELECT diem_chinh_thuc FROM lop_hoc_phan WHERE id='.$id)->fetchColumn()===1,'Lưu chính thức khóa cả lớp');
    rejected(fn()=>luu_diem($db,$teacher,$id,$input,'',false,$version()),'Điểm đã lưu chính thức');
    $input[$first]['thi']='4.99';rejected(fn()=>luu_diem($db,$admin,$id,$input,'',false,$version()),'Admin phải nhập lý do');
    luu_diem($db,$admin,$id,$input,'Phúc khảo',false,$version());check($db->query('SELECT ket_qua FROM diem WHERE id='.$first)->fetchColumn()==='Không đạt','Cuối HP dưới 5 dù tổng kết trên 5 vẫn không đạt');
    check((int)$db->query("SELECT COUNT(*) FROM lich_su_diem WHERE diem_id=$first AND ly_do='Phúc khảo'")->fetchColumn()===1,'Lưu lịch sử chính thức kèm người sửa/lý do');
    $input[$first]['cc']='6; 9; 9';
    luu_diem($db,$admin,$id,$input,'Điều chỉnh từng bài TX',false,$version());
    check($db->query("SELECT gia_tri_moi_text FROM lich_su_diem WHERE diem_id=$first AND cot='cc' ORDER BY id DESC LIMIT 1")->fetchColumn()==='6.00; 9.00; 9.00','Lưu thay đổi TX ngay cả khi trung bình không đổi');
    $before=tao_sao_luu($db);$validated=doc_sao_luu($db,json_encode($before));khoi_phuc($db,$validated);
    check(tao_sao_luu($db)['tables']===$before['tables'],'Xuất/khôi phục đầy đủ dữ liệu không mất hàng');
    $invalid=$before;$invalid['tables']['diem'][0]['dang_ky_id']=99999999;
    try{khoi_phuc($db,$invalid);throw new RuntimeException('Expected FK error');}catch(PDOException $e){check(tao_sao_luu($db)['tables']===$before['tables'],'Khôi phục lỗi khóa ngoại rollback toàn bộ');}
    $invalid=$before;unset($invalid['tables']['users']);rejected(fn()=>doc_sao_luu($db,json_encode($invalid)),'thiếu hoặc thừa');
    $invalid=$before;$invalid['tables']['diem'][0]['tx']='[99]';rejected(fn()=>doc_sao_luu($db,json_encode($invalid)),'Điểm thường xuyên trong sao lưu');
    echo "SUCCESS: $passed integration checks.\n";
}finally{if($created)$server->exec("DROP DATABASE `$testName`");}
