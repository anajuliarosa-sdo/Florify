    <script src="bootstrap/bootstrap.bundle.js"></script>
    <script src="javascript/site.js"></script>
<?php foreach (($scriptsExtras ?? []) as $src): ?>
    <script src="<?= htmlspecialchars($src) ?>"></script>
<?php endforeach; ?>
</body>
</html>
