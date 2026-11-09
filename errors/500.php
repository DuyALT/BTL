<?php
http_response_code(500);
$errorCode=500;$errorTitle='Hệ thống tạm thời gián đoạn';
$errorMessage='Yêu cầu chưa thể hoàn tất. Vui lòng thử lại sau.';
require __DIR__.'/../includes/error-view.php';
