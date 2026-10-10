<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_role('resident');
require_once dirname(__DIR__) . '/config/db.php';

$userId = current_user_id();
$showSubmissionsOnly = request_string($_GET, 'view') === 'submissions';
$filterStatus = request_string($_GET, 'status');
$filterType = request_string($_GET, 'type');
$search = request_string($_GET, 'search');
if (!in_array($filterStatus, ['', ...SUBMISSION_STATUSES], true)) {
    $filterStatus = '';
}
if (!in_array($filterType, ['', ...SUBMISSION_TYPES], true)) {
    $filterType = '';
}

$summaryQuery = $pdo->prepare(
    "SELECT COUNT(*) AS total,
            COALESCE(SUM(status = 'Received'), 0) AS received,
            COALESCE(SUM(status = 'In Progress'), 0) AS in_progress,
            COALESCE(SUM(status = 'Resolved'), 0) AS resolved
     FROM submissions WHERE user_id = :user_id"
);
$summaryQuery->execute(['user_id' => $userId]);
$summary = $summaryQuery->fetch();

$conditions = ['user_id = :user_id'];
$params = ['user_id' => $userId];
if ($filterStatus !== '') {
    $conditions[] = 'status = :status';
    $params['status'] = $filterStatus;
}
if ($filterType !== '') {
    $conditions[] = 'type = :type';
    $params['type'] = $filterType;
}
if ($search !== '') {
    $conditions[] = 'subject LIKE :search';
    $params['search'] = '%' . $search . '%';
}
$query = $pdo->prepare(
    'SELECT id, reference_no, type, category, subject, is_anonymous, status, created_at
     FROM submissions WHERE ' . implode(' AND ', $conditions) . ' ORDER BY created_at DESC'
);
$query->execute($params);
$submissions = $query->fetchAll();
$fullName = (string) ($_SESSION['full_name'] ?? 'Neighbor');
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

render_app_start('My submissions', 'resident', $showSubmissionsOnly ? 'submissions' : 'dashboard');
?>
<?php if (!$showSubmissionsOnly): ?>
<section class="welcome-row">
    <div>
        <p class="eyebrow">YOUR COMMUNITY PORTAL · <?= escape(date('l, F j, Y')) ?></p>
        <h1 class="page-title"><?= escape($greeting) ?>, <?= escape($fullName) ?></h1>
        <p class="welcome-subtitle">Your voice can make a real difference in our barangay.</p>
    </div>
    <a class="btn btn-primary desktop-new-button" href="new_submission.php">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> New submission
    </a>
</section>
<?php endif; ?>

<?php if (!$showSubmissionsOnly): ?>
<section class="stats-grid" aria-label="Your submission summary">
    <article class="stat-card stat-total"><div class="stat-top"><span>Total submissions</span><i class="bi bi-chat-square-text"></i></div><strong><?= (int) $summary['total'] ?></strong><p>Every voice counts</p><span class="stat-accent"></span></article>
    <article class="stat-card stat-received"><div class="stat-top"><span>Received</span><i class="bi bi-inbox"></i></div><strong><?= (int) $summary['received'] ?></strong><p>Submitted to the barangay</p><span class="stat-accent"></span></article>
    <article class="stat-card stat-progress"><div class="stat-top"><span>In progress</span><i class="bi bi-arrow-repeat"></i></div><strong><?= (int) $summary['in_progress'] ?></strong><p>Being reviewed by the team</p><span class="stat-accent"></span></article>
    <article class="stat-card stat-resolved"><div class="stat-top"><span>Resolved</span><i class="bi bi-check2-circle"></i></div><strong><?= (int) $summary['resolved'] ?></strong><p>Positive change, together</p><span class="stat-accent"></span></article>
</section>
<?php endif; ?>

<section class="submissions-panel" id="submissions" aria-labelledby="submissions-title">
    <div class="panel-heading">
        <div><p class="eyebrow">YOUR VOICE, TRACKED</p><h2 id="submissions-title">My submissions <span class="count-pill"><?= count($submissions) ?></span></h2></div>
        <form class="search-form" method="get" action="dashboard.php" role="search">
            <?php if ($showSubmissionsOnly): ?><input type="hidden" name="view" value="submissions"><?php endif; ?>
            <input type="hidden" name="status" value="<?= escape($filterStatus) ?>">
            <input type="hidden" name="type" value="<?= escape($filterType) ?>">
            <label class="visually-hidden" for="search">Search by subject</label><i class="bi bi-search" aria-hidden="true"></i>
            <input id="search" name="search" type="search" placeholder="Search by subject..." value="<?= escape($search) ?>">
            <?php if ($search !== ''): ?><a class="search-clear" href="<?= $showSubmissionsOnly ? 'dashboard.php?view=submissions' : 'dashboard.php' ?>" aria-label="Clear search"><i class="bi bi-x-lg"></i></a><?php endif; ?>
            <button class="visually-hidden" type="submit">Search</button>
        </form>
    </div>
    <div class="list-toolbar">
        <nav class="filter-tabs" aria-label="Filter submissions">
            <?php
            $filters = [
                ['status' => '', 'type' => '', 'label' => 'All'],
                ['status' => 'Received', 'type' => '', 'label' => 'Received'],
                ['status' => 'In Progress', 'type' => '', 'label' => 'In progress'],
                ['status' => 'Resolved', 'type' => '', 'label' => 'Resolved'],
                ['status' => '', 'type' => 'Complaint', 'label' => 'Complaints'],
                ['status' => '', 'type' => 'Suggestion', 'label' => 'Suggestions'],
            ];
            foreach ($filters as $filter):
                $url = 'dashboard.php?' . http_build_query(array_filter([
                    'view' => $showSubmissionsOnly ? 'submissions' : '',
                    'status' => $filter['status'],
                    'type' => $filter['type'],
                    'search' => $search,
                ], static fn ($value) => $value !== ''));
                $active = $filterStatus === $filter['status'] && $filterType === $filter['type'];
            ?>
                <a class="filter-tab <?= $active ? 'active' : '' ?>" href="<?= escape($url) ?>" <?= $active ? 'aria-current="page"' : '' ?>><?= escape($filter['label']) ?></a>
            <?php endforeach; ?>
        </nav>
        <span class="list-note"><i class="bi bi-shield-check"></i> Private to your account</span>
    </div>

    <?php if ($submissions === []): ?>
        <div class="empty-state">
            <span class="empty-art" aria-hidden="true"><i class="bi bi-chat-square-heart"></i></span>
            <h3><?= $search !== '' || $filterStatus !== '' || $filterType !== '' ? 'No matching submissions' : 'Your voice belongs here' ?></h3>
            <p><?= $search !== '' || $filterStatus !== '' || $filterType !== '' ? 'Try adjusting your search or filters.' : 'Share a concern or an idea. Every submission helps our community grow.' ?></p>
            <?php if ($search === '' && $filterStatus === '' && $filterType === ''): ?><a class="btn btn-primary" href="new_submission.php"><i class="bi bi-plus-lg"></i> Make your first submission</a><?php endif; ?>
        </div>
    <?php else: ?>
        <div class="submission-list">
            <?php foreach ($submissions as $submission): ?>
                <a class="submission-card" href="submission.php?id=<?= (int) $submission['id'] ?>">
                    <span class="submission-type-icon <?= $submission['type'] === 'Complaint' ? 'type-complaint' : 'type-suggestion' ?>"><i class="bi <?= $submission['type'] === 'Complaint' ? 'bi-megaphone' : 'bi-lightbulb' ?>"></i></span>
                    <span class="submission-main">
                        <span class="submission-topline"><span class="reference"><?= escape($submission['reference_no']) ?></span><span class="type-tag <?= $submission['type'] === 'Complaint' ? 'type-tag-complaint' : 'type-tag-suggestion' ?>"><?= escape($submission['type']) ?></span></span>
                        <strong class="submission-subject"><?= escape($submission['subject']) ?></strong>
                        <span class="submission-meta"><span><i class="bi bi-grid"></i><?= escape($submission['category']) ?></span><span><i class="bi bi-calendar3"></i><?= escape(date('M j, Y', strtotime($submission['created_at']))) ?></span><?php if ((int) $submission['is_anonymous']): ?><span><i class="bi bi-incognito"></i>Anonymous</span><?php endif; ?></span>
                    </span>
                    <span class="submission-end"><span class="status-pill status-<?= escape(status_class($submission['status'])) ?>"><i class="bi <?= escape(status_icon($submission['status'])) ?>"></i><?= escape($submission['status']) ?></span><i class="bi bi-chevron-right submission-chevron" aria-hidden="true"></i></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<div class="floating-actions">
    <a class="floating-add" href="new_submission.php" aria-label="Create a new submission"><i class="bi bi-plus-lg"></i><span>New submission</span></a>
</div>
<?php render_app_end(); ?>
