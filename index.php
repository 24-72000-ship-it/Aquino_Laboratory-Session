<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['user_id'])) {
    redirect_to(($_SESSION['role'] ?? '') === 'admin' ? 'admin/dashboard.php' : 'resident/dashboard.php');
}
redirect_to('login.php');
