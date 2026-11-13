<?php
require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/backup.php';$me=require_login('admin');
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    try{
        if(in('action')==='export'){
            $data=tao_sao_luu($pdo);header('Content-Type: application/json; charset=utf-8');header('Content-Disposition: attachment; filename="quanlydiem_'.date('Ymd_His').'.json"');header('Cache-Control: no-store');echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR);exit;
        }
        if(in('action')==='restore'){
            $st=$pdo->prepare('SELECT password_hash FROM users WHERE id=?');$st->execute([$me['id']]);
            if(!password_verify((string)in('password'),$st->fetchColumn()) || in('confirm')!=='KHOI PHUC')throw new RuntimeException('Cần đúng mật khẩu Admin và nhập KHOI PHUC.');
            $upload=$_FILES['backup']??[];
            if(($upload['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || ($upload['size']??0)>20*1024*1024 || !is_uploaded_file($upload['tmp_name']))throw new RuntimeException('Chọn tệp JSON hợp lệ, tối đa 20 MB (cũng phụ thuộc giới hạn upload của PHP).');
            $data=doc_sao_luu($pdo,file_get_contents($upload['tmp_name']));
            // Lưu bản trước khôi phục, được PHP và Apache bảo vệ.
            if(file_put_contents(__DIR__.'/../database/before_restore_'.date('Ymd_His').'.php',"<?php exit; ?>\n".json_encode(tao_sao_luu($pdo),JSON_UNESCAPED_UNICODE))===false)throw new RuntimeException('Không ghi được bản sao trước khôi phục. Chưa thay đổi dữ liệu.');
            khoi_phuc($pdo,$data);session_unset();session_destroy();redirect('login.php');
        }
    }catch(Throwable $e){flash('danger',$e instanceof PDOException?'Khôi phục thất bại vì dữ liệu vi phạm ràng buộc. Dữ liệu hiện tại được giữ nguyên.':$e->getMessage());redirect('admin/saoluu.php');}
}
$title='Sao lưu và khôi phục';require __DIR__.'/../includes/header.php';
?>
<h4>Sao lưu và khôi phục dữ liệu</h4>
<div class="card p-3 mb-3"><h5>Xuất bản sao lưu</h5><p>Tải toàn bộ dữ liệu MySQL dưới dạng JSON, bao gồm tài khoản, điểm và lịch sử. Giữ tệp ở nơi riêng tư.</p><form method="post"><?= csrf_field() ?><button class="btn btn-success" name="action" value="export">Tải bản sao lưu</button></form></div>
<div class="card p-3"><h5>Khôi phục từ bản sao lưu</h5><p>Thay thế dữ liệu hiện tại bằng tệp sao lưu do website xuất. Website lưu một bản trước khi khôi phục. Sau khi hoàn tất, đăng nhập bằng tài khoản trong bản sao lưu.</p>
<form method="post" enctype="multipart/form-data" class="row g-3"><?= csrf_field() ?><input type="hidden" name="action" value="restore">
<div class="col-md-6"><label class="form-label">Tệp sao lưu JSON</label><input type="file" accept=".json,application/json" name="backup" required class="form-control"></div>
<div class="col-md-6"><label class="form-label">Mật khẩu Admin hiện tại</label><input type="password" name="password" required autocomplete="current-password" class="form-control"></div>
<div class="col-md-6"><label class="form-label">Nhập KHOI PHUC để xác nhận thay thế dữ liệu</label><input name="confirm" required class="form-control"></div>
<div class="col-12"><button class="btn btn-danger" data-confirm="Thay thế toàn bộ dữ liệu hiện tại bằng bản sao lưu đã chọn?">Khôi phục dữ liệu</button></div></form></div>
<?php require __DIR__.'/../includes/footer.php'; ?>
