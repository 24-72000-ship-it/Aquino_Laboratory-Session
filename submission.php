<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_role('resident');
require_once dirname(__DIR__) . '/config/db.php';

$submissionId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$submissionId || $submissionId < 1) {
    http_response_code(404);
    exit('Submission not found.');
}
$query = $pdo->prepare(
    'SELECT * FROM submissions WHERE id = :id AND user_id = :user_id LIMIT 1'
);
$query->execute(['id' => $submissionId, 'user_id' => current_user_id()]);
$submission = $query->fetch();
if (!$submission) {
    http_response_code(404);
    exit('Submission not found.');
}
$historyQuery = $pdo->prepare(
    'SELECT status, remarks, changed_at FROM status_history WHERE submission_id = :id ORDER BY changed_at ASC, id ASC'
);
$historyQuery->execute(['id' => $submissionId]);
$history = $historyQuery->fetchAll();
$canEdit = $submission['status'] === 'Received';

render_app_start('Submission details', 'resident', 'submissions');
?>
<div class="detail-back"><a class="back-link-inline" href="dashboard.php#submissions"><i class="bi bi-arrow-left"></i> Back to my submissions</a></div>
<section class="detail-heading">
    <div><p class="eyebrow">SUBMISSION DETAILS</p><h1 class="page-title"><?= escape($submission['reference_no']) ?></h1><p class="welcome-subtitle">Submitted <?= escape(date('F j, Y \a\t g:i A', strtotime($submission['created_at']))) ?></p></div>
    <span class="status-pill status-<?= escape(status_class($submission['status'])) ?>"><i class="bi <?= escape(status_icon($submission['status'])) ?>"></i><?= escape($submission['status']) ?></span>
</section>
<div class="detail-layout">
    <section class="detail-card">
        <div class="detail-type-row"><span class="type-tag <?= $submission['type'] === 'Complaint' ? 'type-tag-complaint' : 'type-tag-suggestion' ?>"><i class="bi <?= $submission['type'] === 'Complaint' ? 'bi-megaphone' : 'bi-lightbulb' ?>"></i><?= escape($submission['type']) ?></span><span class="detail-category"><i class="bi bi-grid"></i><?= escape($submission['category']) ?></span></div>
        <h2 class="detail-subject"><?= escape($submission['subject']) ?></h2>
        <p class="detail-description"><?= nl2br(escape($submission['description'])) ?></p>
        <?php if ($submission['image_path'] !== null): ?>
            <div class="attachment-block"><p class="eyebrow">ATTACHED PHOTO</p><a href="../<?= escape($submission['image_path']) ?>" target="_blank" rel="noopener noreferrer"><img class="submission-photo" src="../<?= escape($submission['image_path']) ?>" alt="Photo attached to <?= escape($submission['reference_no']) ?>"></a></div>
        <?php endif; ?>
        <?php if ($submission['admin_reply'] !== null && $submission['admin_reply'] !== ''): ?>
            <section class="admin-reply" aria-label="Reply from the barangay"><div class="reply-heading"><span class="reply-avatar"><i class="bi bi-buildings"></i></span><span><strong>Barangay response</strong><small>Official reply</small></span></div><p><?= nl2br(escape($submission['admin_reply'])) ?></p></section>
        <?php endif; ?>
        <div class="reporter-note"><i class="bi bi-incognito"></i><?= (int) $submission['is_anonymous'] ? 'Submitted anonymously' : 'Submitted by ' . escape($_SESSION['full_name'] ?? 'you') ?></div>
        <?php if ($canEdit): ?>
            <div class="detail-actions"><a class="btn btn-light" href="edit.php?id=<?= (int) $submission['id'] ?>"><i class="bi bi-pencil"></i> Edit submission</a><form method="post" action="delete.php" data-confirm="true"><input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>"><input type="hidden" name="submission_id" value="<?= (int) $submission['id'] ?>"><button class="btn btn-outline-danger" type="submit"><i class="bi bi-trash3"></i> Delete</button></form></div>
        <?php else: ?>
            <p class="locked-note"><i class="bi bi-lock"></i> Submissions can only be edited or deleted while they are marked Received.</p>
        <?php endif; ?>
    </section>
    <aside class="timeline-card" aria-labelledby="timeline-title">
        <p class="eyebrow">KEEPING YOU IN THE LOOP</p><h2 id="timeline-title">Status timeline</h2>
        <ol class="status-timeline">
            <?php foreach ($history as $event): ?>
                <li class="timeline-event <?= 'event-' . escape(status_class($event['status'])) ?>">
                    <span class="timeline-marker"><i class="bi <?= escape(status_icon($event['status'])) ?>"></i></span>
                    <div><strong><?= escape($event['status']) ?></strong><time datetime="<?= escape(date(DATE_ATOM, strtotime($event['changed_at']))) ?>"><?= escape(date('M j, Y · g:i A', strtotime($event['changed_at']))) ?></time>
                    <?php if ($event['remarks'] !== ''): ?><p><?= nl2br(escape($event['remarks'])) ?></p><?php endif; ?></div>
                </li>
            <?php endforeach; ?>
        </ol>
        <div class="timeline-footnote"><i class="bi bi-info-circle"></i> We’ll update this timeline as your submission is reviewed.</div>
    </aside>
</div>
<?php render_app_end(); ?>
