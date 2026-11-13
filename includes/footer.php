<?php if (current_user()): ?>
        </main>
        <footer class="app-footer no-print"><span>© <?= date('Y') ?> <b>EduScore</b> · <?= h(APP_NAME) ?></span><span>Kết nối tri thức, theo dõi thành công.</span></footer>
    </div><!-- /.app-main -->
</div><!-- /.app -->
<?php else: ?>
</main>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/validate.js"></script>
<?php require __DIR__ . '/ui-modals.php'; ?>
<div class="toast-region" id="toastRegion" aria-live="polite" aria-atomic="false"></div>
<script src="<?= BASE_URL ?>assets/js/app.js?v=2"></script>
<?= $extraScripts ?? '' ?>
</body>
</html>
