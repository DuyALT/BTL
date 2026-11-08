<?php if (current_user()): ?>
        </main>
        <footer class="app-footer no-print">© <?= date('Y') ?> <?= h(APP_NAME) ?> · Bài tập lớn PHP &amp; MySQL</footer>
    </div><!-- /.app-main -->
</div><!-- /.app -->
<?php else: ?>
</main>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/validate.js"></script>
<?= $extraScripts ?? '' ?>
</body>
</html>
