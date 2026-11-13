<?php
require_once __DIR__ . '/includes/auth.php';
if (current_user()) redirect('index.php');

$err = '';
$msg = ['timeout' => 'Phiên làm việc đã hết hạn, vui lòng đăng nhập lại.',
        'locked' => 'Tài khoản của bạn đã bị khóa.',
        'logout' => 'Bạn đã đăng xuất.'][$_GET['msg'] ?? ''] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $username = mb_substr($username, 0, 50);

    if ($username === '' || $password === '') {
        $err = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
    } elseif ($cho = dn_cho_giay($pdo, $username)) {
        // Chống dò mật khẩu: đang bị khóa thì không kiểm tra mật khẩu
        $err = 'Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau ' . ceil($cho / 60) . ' phút.';
    } else {
        $st = $pdo->prepare('SELECT * FROM users WHERE username = ?');
        $st->execute([$username]);
        $user = $st->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $con = dn_ghi_sai($pdo, $username);
            $err = 'Tên đăng nhập hoặc mật khẩu không đúng.'
                 . ($con === 0 ? ' Bạn đã bị tạm khóa đăng nhập 15 phút.' : ($con <= 2 ? " Còn $con lần thử." : ''));
        } elseif ($user['trang_thai'] !== 'active') {
            $err = 'Tài khoản đã bị khóa. Liên hệ quản trị viên.';
        } else {
            dn_xoa_sai($pdo, $username);
            session_regenerate_id(true);           // chống session fixation
            $_SESSION['user'] = ['id' => $user['id'], 'username' => $user['username'],
                                 'ho_ten' => $user['ho_ten'], 'role' => $user['role'], 'trang_thai' => $user['trang_thai']];
            $_SESSION['last_activity'] = time();
            // Tài khoản mới tạo / vừa reset vẫn dùng mật khẩu mặc định -> bắt đổi ngay
            $_SESSION['phai_doi_mk'] = password_verify(MAT_KHAU_MAC_DINH, $user['password_hash']);
            redirect($_SESSION['phai_doi_mk'] ? 'doi_mat_khau.php' : 'index.php');
        }
    }
}
$title = 'Đăng nhập';
require __DIR__ . '/includes/header.php';
?>
<div class="login-wrap login-campus">
    <!-- Cột trái: giới thiệu -->
    <section class="login-hero">
        <div class="d-flex align-items-center gap-2">
            <?php require __DIR__ . '/includes/brand-logo.php'; ?>
            <b class="fs-5">EduScore.</b>
        </div>
        <div>
            <h1>Học hôm nay,<br>vững bước ngày mai.</h1>
            <p class="lead mt-3">Mỗi ngày thêm một điều mới, mỗi nỗ lực thêm một bước gần hơn với ước mơ.</p>
            <div class="hero-feature"><i class="bi bi-book"></i><div><b>Tri thức mở lối tương lai</b><span>Học để hiểu, học để làm, học để trưởng thành.</span></div></div>
            <div class="hero-feature"><i class="bi bi-lightbulb"></i><div><b>Không ngừng khám phá</b><span>Mỗi câu hỏi là khởi đầu của một khám phá mới.</span></div></div>
            <div class="hero-feature"><i class="bi bi-graph-up-arrow"></i><div><b>Kiên trì tạo nên thành công</b><span>Tiến bộ từng ngày, chạm tới những ước mơ lớn.</span></div></div>
        </div>
        <small class="opacity-75">© <?= date('Y') ?> Bài tập lớn PHP &amp; MySQL</small>
    </section>

    <!-- Cột phải: form đăng nhập -->
    <section class="login-panel">
        <div class="login-box">
            <div class="login-brand-mobile"><?php require __DIR__ . '/includes/brand-logo.php'; ?><span>EduScore.</span></div>
            <div class="page-eyebrow mb-2">CỔNG QUẢN LÝ ĐIỂM HỌC TẬP</div>
            <h2 class="mb-1">Chào mừng trở lại!</h2>
            <p class="text-muted mb-4">Chào mừng bạn quay lại! Vui lòng đăng nhập để tiếp tục.</p>
            <?php if ($err): ?><div class="alert alert-danger"><i class="bi bi-exclamation-circle me-1"></i><?= h($err) ?></div><?php endif; ?>
            <?php if ($msg): ?><div class="alert alert-info"><i class="bi bi-info-circle me-1"></i><?= h($msg) ?></div><?php endif; ?>
            <form method="post" class="js-validate" novalidate>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Tên đăng nhập</label>
                    <div class="input-icon"><i class="bi bi-person"></i>
                        <input name="username" autocomplete="username" class="form-control" placeholder="Nhập tên đăng nhập của bạn" required data-label="Tên đăng nhập" value="<?= h($_POST['username'] ?? '') ?>" autofocus>
                    </div>
                </div>
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2"><label class="form-label mb-0" for="loginPassword">Mật khẩu</label><button type="button" class="forgot-password-link" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Quên mật khẩu?</button></div>
                    <div class="input-icon"><i class="bi bi-lock"></i>
                        <input id="loginPassword" name="password" type="password" autocomplete="current-password" class="form-control" placeholder="Nhập mật khẩu" required data-label="Mật khẩu">
                        <button type="button" class="btn-icon password-toggle" data-password-toggle="loginPassword" aria-label="Hiện mật khẩu" aria-pressed="false"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <button class="btn btn-primary w-100 py-2">Đăng nhập <i class="bi bi-arrow-right ms-1"></i></button>
            </form>
            <div class="demo-accounts mt-4">
                <i class="bi bi-lightbulb text-warning me-1"></i><b>Tài khoản mẫu</b> (mật khẩu <code>123456</code>)<br>
                Quản trị: <code>admin</code> · Giảng viên: <code>gv001</code> · Sinh viên: <code>2251000001</code>
            </div>
            <p class="login-note"><i class="bi bi-shield-check me-1"></i> Truy cập theo quyền tài khoản của bạn</p>
        </div>
    </section>
</div>
<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h2 class="modal-title" id="forgotPasswordTitle">Bạn quên mật khẩu?</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
    <div class="modal-body"><div class="confirm-symbol"><i class="bi bi-key" aria-hidden="true"></i></div><p>Vui lòng liên hệ quản trị viên hệ thống hoặc đơn vị quản lý tài khoản để được xác minh và đặt lại mật khẩu.</p><div class="p-3 rounded-3 bg-light"><b class="d-block mb-2">Thông tin cần cung cấp</b><ul class="mb-0 ps-3"><li>Họ và tên.</li><li>Tên đăng nhập hoặc mã sinh viên / mã giảng viên.</li><li>Lớp hoặc đơn vị công tác.</li></ul></div><p class="small text-muted mt-3 mb-0">Sau khi được cấp lại mật khẩu, hãy đăng nhập và chọn Đổi mật khẩu trong menu tài khoản.</p></div>
    <div class="modal-footer"><button type="button" class="btn btn-primary" data-bs-dismiss="modal">Quay lại đăng nhập</button></div>
</div></div></div>
<script>
// Bấm vào tên tài khoản mẫu để điền nhanh
document.querySelectorAll('.demo-accounts code').forEach(c => c.addEventListener('click', () => {
    if (c.textContent === '123456') return;
    document.querySelector('[name=username]').value = c.textContent;
    document.querySelector('[name=password]').focus();
}));
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
