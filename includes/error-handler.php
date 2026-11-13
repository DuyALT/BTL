<?php
// Friendly presentation for unexpected errors; no database/session changes.
ini_set('display_errors','0');
ini_set('log_errors','1');
set_exception_handler(static function(Throwable $e): void {
    error_log((string)$e);
    if(!headers_sent())http_response_code(500);
    $errorCode=500;$errorTitle='Hệ thống tạm thời gián đoạn';
    $errorMessage='Yêu cầu chưa thể hoàn tất. Vui lòng tải lại trang hoặc thử lại sau.';
    require __DIR__.'/error-view.php';
});
register_shutdown_function(static function(): void {
    $last=error_get_last();
    if(!$last || !in_array($last['type'],[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR],true))return;
    if(!headers_sent())http_response_code(500);
    $errorCode=500;$errorTitle='Hệ thống tạm thời gián đoạn';$errorMessage='Trang chưa thể hiển thị. Vui lòng thử lại sau.';
    require __DIR__.'/error-view.php';
});
