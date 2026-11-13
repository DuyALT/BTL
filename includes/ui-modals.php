<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
<div class="modal-header"><h2 class="modal-title" id="confirmTitle">Xác nhận thao tác</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
<div class="modal-body"><div class="confirm-symbol"><i class="bi bi-exclamation-triangle"></i></div><p id="confirmMessage" class="mb-0"></p></div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy bỏ</button><button type="button" class="btn btn-primary" id="confirmProceed">Xác nhận</button></div>
</div></div></div>
<?php if(current_user()): $profileUser=current_user(); ?>
<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
<div class="modal-header"><h2 class="modal-title" id="profileTitle">Hồ sơ cá nhân</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
<div class="modal-body"><div class="profile-banner"><div class="profile-avatar" aria-hidden="true"><?= h(mb_substr($profileUser['ho_ten'],0,1)) ?></div><div><h2><?= h($profileUser['ho_ten']) ?></h2><p><?= h(TEN_VAI_TRO[$profileUser['role']]) ?></p></div></div><dl class="row mb-0"><dt class="col-5">Tên đăng nhập</dt><dd class="col-7"><?= h($profileUser['username']) ?></dd><dt class="col-5">Quyền truy cập</dt><dd class="col-7"><?= h(TEN_VAI_TRO[$profileUser['role']]) ?></dd><dt class="col-5">Trạng thái</dt><dd class="col-7"><span class="result-badge pass">Đang hoạt động</span></dd></dl></div>
<div class="modal-footer"><?php if($profileUser['role']==='sinhvien'): ?><a class="btn btn-outline-primary" href="<?= BASE_URL ?>sinhvien/tong_quan.php">Thông tin sinh viên</a><?php endif; ?><a class="btn btn-primary" href="<?= BASE_URL ?>doi_mat_khau.php">Đổi mật khẩu</a></div>
</div></div></div>
<?php endif; ?>
