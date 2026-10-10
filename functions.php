<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

const SUBMISSION_TYPES = ['Complaint', 'Suggestion'];
const SUBMISSION_STATUSES = ['Received', 'In Progress', 'Resolved', 'Rejected'];
const SUBMISSION_CATEGORIES = [
    'Road & Infrastructure',
    'Garbage & Sanitation',
    'Noise',
    'Peace & Order',
    'Water & Electricity',
    'Health',
    'Others',
];

function escape(string|null $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function request_string(array $source, string $key): string
{
    return isset($source[$key]) && is_string($source[$key]) ? trim($source[$key]) : '';
}

function text_length(string $value): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($value, 'UTF-8');
    }

    $length = preg_match_all('/./us', $value, $matches);
    return $length === false ? strlen($value) : $length;
}

function request_password(array $source, string $key): string
{
    return isset($source[$key]) && is_string($source[$key]) ? $source[$key] : '';
}

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit;
    }
}

function require_role(string $role): void
{
    require_login();
    if (($_SESSION['role'] ?? '') !== $role) {
        if (($_SESSION['role'] ?? '') === 'admin') {
            redirect_to('../admin/dashboard.php');
        }
        if (($_SESSION['role'] ?? '') === 'resident') {
            redirect_to('../resident/dashboard.php');
        }
        http_response_code(403);
        exit('This account does not have an authorized role.');
    }
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(): void
{
    $token = request_string($_POST, 'csrf_token');
    if ($token === '' || !hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        exit('Invalid request token. Refresh the page and try again.');
    }
}

function set_flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function get_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function redirect_to(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function current_user_id(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

function valid_date(string $value): bool
{
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}

function store_submission_image(?array $file): ?string
{
    if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('The image could not be uploaded. Please try again.');
    }
    if (!isset($file['tmp_name'], $file['size']) || !is_uploaded_file($file['tmp_name'])) {
        throw new InvalidArgumentException('The uploaded image is not valid.');
    }
    if ((int) $file['size'] > 2 * 1024 * 1024) {
        throw new InvalidArgumentException('Images must be 2 MB or smaller.');
    }

    $extensionsByMime = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
    ];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!is_string($mime) || !isset($extensionsByMime[$mime])) {
        throw new InvalidArgumentException('Upload a JPG, PNG, or WEBP image.');
    }
    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($extension, $extensionsByMime[$mime], true)) {
        throw new InvalidArgumentException('The image file extension does not match its content.');
    }

    $directory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'complaints';
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('The image upload folder could not be prepared.');
    }

    $storedExtension = $mime === 'image/jpeg' ? 'jpg' : $extensionsByMime[$mime][0];
    $filename = bin2hex(random_bytes(20)) . '.' . $storedExtension;
    if (!move_uploaded_file($file['tmp_name'], $directory . DIRECTORY_SEPARATOR . $filename)) {
        throw new RuntimeException('The image could not be saved. Check folder permissions.');
    }
    return 'uploads/complaints/' . $filename;
}

function remove_submission_image(?string $path): void
{
    if ($path === null || !preg_match('#^uploads/complaints/[a-f0-9]{40}\.(?:jpg|png|webp)$#', $path)) {
        return;
    }
    $fullPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    if (is_file($fullPath) && !unlink($fullPath)) {
        error_log('Unable to remove submission image: ' . $fullPath);
    }
}

function render_head(string $title, string $assetPrefix = './', bool $includeCharts = false): void
{
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<meta name="color-scheme" content="light dark"><title>' . escape($title) . ' | BaryoCare</title>';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">';
    echo '<link href="' . escape($assetPrefix) . 'assets/style.css?v=20261010-baryocare-3" rel="stylesheet">';
    if ($includeCharts) {
        echo '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>';
    }
    echo '<script src="' . escape($assetPrefix) . 'assets/script.js?v=20261010-baryocare" defer></script></head>';
}

function render_app_start(string $title, string $role, string $active): void
{
    $prefix = '../';
    $name = (string) ($_SESSION['full_name'] ?? 'Resident');
    $parts = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $initials = strtoupper(substr($parts[0] ?? 'R', 0, 1) . (count($parts) > 1 ? substr($parts[count($parts) - 1], 0, 1) : ''));
    $residentLinks = [
        ['dashboard', 'bi-grid-1x2', 'Dashboard', 'dashboard.php'],
        ['submissions', 'bi-chat-square-text', 'My Submissions', 'dashboard.php?view=submissions#submissions'],
        ['new', 'bi-plus-circle', 'New Submission', 'new_submission.php'],
    ];
    $adminLinks = [
        ['dashboard', 'bi-grid-1x2', 'Overview', 'dashboard.php'],
        ['submissions', 'bi-inbox', 'All submissions', 'dashboard.php#submissions'],
        ['export', 'bi-download', 'Export CSV', 'export_csv.php'],
    ];
    $links = $role === 'admin' ? $adminLinks : $residentLinks;
    $date = date('l, F j, Y');

    render_head($title, $prefix, $role === 'admin');
    echo '<body class="app-page ' . ($role === 'admin' ? 'admin-app' : 'resident-app') . '" data-role="' . escape($role) . '">';
    echo '<button class="sidebar-scrim" type="button" data-sidebar-close aria-label="Close navigation"></button>';
    echo '<aside class="app-sidebar" id="app-sidebar" aria-label="Main navigation">';
    echo '<a class="brand-mark" href="' . escape($role === 'admin' ? 'dashboard.php' : 'dashboard.php') . '"><span class="brand-symbol"><i class="bi bi-buildings"></i></span><span>Baryo<span class="brand-light">Care</span></span></a>';
    echo '<div class="barangay-chip"><span class="live-dot"></span>' . ($role === 'admin' ? 'BARANGAY ADMIN' : 'COMMUNITY PORTAL') . '</div>';
    echo '<nav class="sidebar-nav" aria-label="Workspace"><span class="sidebar-section-label">' . ($role === 'admin' ? 'MANAGE' : 'MENU') . '</span>';
    foreach ($links as [$key, $icon, $label, $href]) {
        $isActive = $active === $key;
        echo '<a class="sidebar-link' . ($isActive ? ' active' : '') . '" href="' . escape($href) . '"' . ($isActive ? ' aria-current="page"' : '') . '>';
        echo '<i class="bi ' . escape($icon) . '" aria-hidden="true"></i><span>' . escape($label) . '</span></a>';
    }
    echo '</nav><div class="sidebar-bottom">';
    echo '<div class="sidebar-help"><span class="help-icon"><i class="bi bi-heart"></i></span><strong>' . ($role === 'admin' ? 'Here for the community.' : 'Your voice matters.') . '</strong>';
    echo '<span>' . ($role === 'admin' ? 'Every response builds trust.' : 'Together, we can make things better.') . '</span></div>';
    echo '<button class="theme-toggle sidebar-theme" type="button" data-theme-toggle aria-label="Switch to dark mode"><i class="bi bi-moon-stars"></i><span>Dark mode</span></button>';
    echo '<form action="' . $prefix . 'logout.php" method="post" data-loading-form><input type="hidden" name="csrf_token" value="' . escape(csrf_token()) . '">';
    echo '<button class="sidebar-link sidebar-logout" type="submit"><i class="bi bi-box-arrow-left"></i><span>Sign out</span></button></form></div></aside>';
    echo '<main class="app-main"><header class="topbar"><button class="menu-toggle" type="button" data-sidebar-toggle aria-controls="app-sidebar" aria-expanded="false" aria-label="Open navigation"><i class="bi bi-list"></i></button>';
    echo '<div class="topbar-breadcrumb"><span>Barangay portal</span><i class="bi bi-chevron-right"></i><strong>' . escape($title) . '</strong></div>';
    echo '<div class="topbar-actions"><span class="topbar-date"><i class="bi bi-calendar3"></i>' . escape($date) . '</span>';
    echo '<button class="theme-toggle topbar-theme" type="button" data-theme-toggle aria-label="Switch to dark mode" title="Toggle dark mode"><i class="bi bi-moon-stars"></i></button><span class="topbar-divider"></span>';
    echo '<div class="user-chip"><span class="user-avatar" aria-hidden="true">' . escape($initials) . '</span><span class="user-chip-name">' . escape($name) . '</span></div></div></header>';
    echo '<div class="page-content">';
}

function render_app_end(): void
{
    $flash = get_flash();
    if ($flash) {
        $isError = ($flash['type'] ?? '') === 'danger';
        echo '<div class="toast app-toast" role="' . ($isError ? 'alert' : 'status') . '" aria-live="' . ($isError ? 'assertive' : 'polite') . '" aria-atomic="true" data-toast>';
        echo '<span class="toast-mark' . ($isError ? ' toast-error' : '') . '"><i class="bi ' . ($isError ? 'bi-exclamation-circle' : 'bi-check-lg') . '"></i></span>';
        echo '<span class="toast-body">' . escape((string) $flash['message']) . '</span><button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button></div>';
    }
    echo '<footer class="app-footer">BaryoCare <span>·</span> Your voice. Your barangay.</footer></div></main>';
    echo '<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content confirm-modal">';
    echo '<div class="modal-body"><span class="confirm-icon"><i class="bi bi-trash3"></i></span><h2 id="confirmDeleteTitle">Delete submission?</h2><p>This action cannot be undone.</p>';
    echo '<div class="confirm-actions"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-danger" data-confirm-delete>Delete</button></div></div></div></div>';
    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></body></html>';
}

function status_class(string $status): string
{
    return match ($status) {
        'Received' => 'received',
        'In Progress' => 'in-progress',
        'Resolved' => 'resolved',
        'Rejected' => 'rejected',
        default => 'received',
    };
}

function status_icon(string $status): string
{
    return match ($status) {
        'Received' => 'bi-inbox',
        'In Progress' => 'bi-arrow-repeat',
        'Resolved' => 'bi-check2-circle',
        'Rejected' => 'bi-x-circle',
        default => 'bi-inbox',
    };
}

function submission_reference(int $id, string $createdAt): string
{
    return 'BRG-' . date('Y', strtotime($createdAt)) . '-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
}

function safe_csv_cell(string $value): string
{
    return preg_match('/^[\s\x00-\x1F]*[=+\-@]/u', $value) ? "'" . $value : $value;
}
