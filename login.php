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

    // Chống dò mật khẩu: sai quá 5 lần thì chờ 60 giây
    $fail = $_SESSION['login_fail'] ?? ['n' => 0, 't' => 0];
    if ($fail['n'] >= 5 && time() - $fail['t'] < 60) {
        $err = 'Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau ' . (60 - (time() - $fail['t'])) . ' giây.';
    } elseif ($username === '' || $password === '') {
        $err = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
    } else {
        $st = $pdo->prepare('SELECT * FROM users WHERE username = ?');
        $st->execute([$username]);
        $user = $st->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['login_fail'] = ['n' => $fail['n'] + 1, 't' => time()];
            $err = 'Tên đăng nhập hoặc mật khẩu không đúng.';
        } elseif ($user['trang_thai'] !== 'active') {
            $err = 'Tài khoản đã bị khóa. Liên hệ quản trị viên.';
        } else {
            session_regenerate_id(true);           // chống session fixation
            unset($_SESSION['login_fail']);
            $_SESSION['user'] = ['id' => $user['id'], 'username' => $user['username'],
                                 'ho_ten' => $user['ho_ten'], 'role' => $user['role'], 'trang_thai' => $user['trang_thai']];
            $_SESSION['last_activity'] = time();
            redirect('index.php');
        }
    }
}
$title = 'Đăng nhập';
require __DIR__ . '/includes/header.php';
?>
<div class="login-wrap">
    <!-- Cột trái: giới thiệu -->
    <section class="login-hero">
        <div class="d-flex align-items-center gap-2">
            <span class="brand-logo"><i class="bi bi-mortarboard-fill"></i></span>
            <b class="fs-5">QL Điểm</b>
        </div>
        <div>
            <h1>Quản lý điểm học tập<br>sinh viên</h1>
            <p class="lead mt-3">Nhập điểm thành phần, tự động tính điểm tổng kết theo trọng số và theo dõi kết quả học tập theo từng học kỳ.</p>
            <div class="hero-feature"><i class="bi bi-calculator"></i><div><b>Tính điểm tự động</b><span>Công thức cấu hình riêng cho từng môn học</span></div></div>
            <div class="hero-feature"><i class="bi bi-shield-check"></i><div><b>Phân quyền rõ ràng</b><span>Chỉ giảng viên phụ trách mới được sửa điểm</span></div></div>
            <div class="hero-feature"><i class="bi bi-clock-history"></i><div><b>Lưu lịch sử chỉnh sửa</b><span>Ghi lại người sửa, giá trị cũ, mới và lý do</span></div></div>
        </div>
        <small class="opacity-75">© <?= date('Y') ?> Bài tập lớn PHP &amp; MySQL</small>
    </section>

    <!-- Cột phải: form đăng nhập -->
    <section class="login-panel">
        <div class="login-box">
            <h2 class="mb-1">Đăng nhập</h2>
            <p class="text-muted mb-4">Chào mừng bạn quay lại! Vui lòng đăng nhập để tiếp tục.</p>
            <?php if ($err): ?><div class="alert alert-danger"><i class="bi bi-exclamation-circle me-1"></i><?= h($err) ?></div><?php endif; ?>
            <?php if ($msg): ?><div class="alert alert-info"><i class="bi bi-info-circle me-1"></i><?= h($msg) ?></div><?php endif; ?>
            <form method="post" class="js-validate" novalidate>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Tên đăng nhập</label>
                    <div class="input-icon"><i class="bi bi-person"></i>
                        <input name="username" class="form-control" placeholder="vd: admin, gv001, 2251000001" required data-label="Tên đăng nhập" value="<?= h($_POST['username'] ?? '') ?>" autofocus>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Mật khẩu</label>
                    <div class="input-icon"><i class="bi bi-lock"></i>
                        <input name="password" type="password" class="form-control" placeholder="••••••" required data-label="Mật khẩu">
                    </div>
                </div>
                <button class="btn btn-primary w-100 py-2">Đăng nhập <i class="bi bi-arrow-right ms-1"></i></button>
            </form>
            <div class="demo-accounts mt-4">
                <i class="bi bi-lightbulb text-warning me-1"></i><b>Tài khoản mẫu</b> (mật khẩu <code>123456</code>)<br>
                Quản trị: <code>admin</code> · Giảng viên: <code>gv001</code> · Sinh viên: <code>2251000001</code>
            </div>
        </div>
    </section>
</div>
<script>
// Bấm vào tên tài khoản mẫu để điền nhanh
document.querySelectorAll('.demo-accounts code').forEach(c => c.addEventListener('click', () => {
    if (c.textContent === '123456') return;
    document.querySelector('[name=username]').value = c.textContent;
    document.querySelector('[name=password]').focus();
}));
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
