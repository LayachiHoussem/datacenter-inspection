    </main>
    <footer class="footer">
        <div><?= sanitize(enterprise_name()) ?> &bull; <?= sanitize(enterprise_site()) ?> &bull; Version <?= defined('APP_VERSION') ? APP_VERSION : '1.0.0' ?> &bull; &copy; <?= date('Y') ?> Devloped by Houssem Bendana 🖤.</div>
    </footer>
</div>
</div>
<?php
$root = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 3);
?>
<!-- JS Scripts -->
<script src="<?= asset('js/app.js') ?>?v=<?= file_exists($root . '/public/assets/js/app.js') ? filemtime($root . '/public/assets/js/app.js') : '1.0' ?>"></script>
<script src="<?= asset('js/inspection.js') ?>?v=<?= file_exists($root . '/public/assets/js/inspection.js') ? filemtime($root . '/public/assets/js/inspection.js') : '1.0' ?>"></script>
<script src="<?= asset('js/dashboard.js') ?>?v=<?= file_exists($root . '/public/assets/js/dashboard.js') ? filemtime($root . '/public/assets/js/dashboard.js') : '1.0' ?>"></script>
<script src="<?= asset('js/maintenance.js') ?>?v=<?= file_exists($root . '/public/assets/js/maintenance.js') ? filemtime($root . '/public/assets/js/maintenance.js') : '1.0' ?>"></script>
</body>
</html>
