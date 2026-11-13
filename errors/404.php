<?php
http_response_code(404);
$errorCode=404;$errorTitle='Không tìm thấy trang';
$errorMessage='Đường dẫn có thể đã thay đổi hoặc trang bạn tìm không tồn tại.';
require __DIR__.'/../includes/error-view.php';
