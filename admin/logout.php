<?php
declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../includes/auth.php';

admin_logout();
header('Location: /admin/login.php');
exit;
