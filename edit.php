<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_role('resident');
require_once dirname(__DIR__) . '/config/db.php';

$submissionId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $submissionId = filter_input(INPUT_POST, 'submission_id', FILTER_VALIDATE_INT);
}
if (!$submissionId || $submissionId < 1) {
    http_response_code(404);
    exit('Submission not found.');
}
$query = $pdo->prepare('SELECT * FROM submissions WHERE id = :id AND user_id = :user_id LIMIT 1');
$query->execute(['id' => $submissionId, 'user_id' => current_user_id()]);
$submission = $query->fetch();
if (!$submission) {
    http_response_code(404);
    exit('Submission not found.');
}
if ($submission['status'] !== 'Received') {
    set_flash('Only submissions marked Received can be edited.', 'danger');
    redirect_to('submission.php?id=' . $submissionId);
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = request_string($_POST, 'type');
    $category = request_string($_POST, 'category');
    $subject = request_string($_POST, 'subject');
    $description = request_string($_POST, 'description');
    $anonymous = isset($_POST['is_anonymous']) && request_string($_POST, 'is_anonymous') === '1' ? 1 : 0;
    if (!in_array($type, SUBMISSION_TYPES, true) || !in_array($category, SUBMISSION_CATEGORIES, true)
        || $subject === '' || mb_strlen($subject) > 180
        || $description === '' || mb_strlen($description) > 5000) {
        $error = 'Please complete the required fields and check their length.';
    } else {
        $update = $pdo->prepare(
            "UPDATE submissions SET type = :type, category = :category, subject = :subject,
             description = :description, is_anonymous = :anonymous
             WHERE id = :id AND user_id = :user_id AND status = 'Received'"
        );
        $update->execute([
            'type' => $type, 'category' => $category, 'subject' => $subject,
            'description' => $description, 'anonymous' => $anonymous,
            'id' => $submissionId, 'user_id' => current_user_id(),
        ]);
        set_flash('Your submission was updated.');
        redirect_to('submission.php?id=' . $submissionId);
    }
    $submission['type'] = $type;
    $submission['category'] = $category;
    $submission['subject'] = $subject;
    $submission['description'] = $description;
    $submission['is_anonymous'] = $anonymous;
}

render_app_start('Edit submission', 'resident', 'submissions');
?>
<div class="detail-back"><a class="back-link-inline" href="submission.php?id=<?= (int) $submissionId ?>"><i class="bi bi-arrow-left"></i> Back to submission</a></div>
<div class="edit-page-intro"><p class="eyebrow">UPDATE YOUR SUBMISSION</p><h1 class="page-title">Edit submission</h1><p class="welcome-subtitle"><?= escape($submission['reference_no']) ?> · You can edit this while it is still Received.</p></div>
<section class="edit-card submission-edit-card">
    <?php if ($error !== ''): ?><div class="inline-message inline-message-error" role="alert"><i class="bi bi-exclamation-circle"></i><?= escape($error) ?></div><?php endif; ?>
    <form method="post" action="edit.php" class="edit-form" data-loading-form>
        <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>"><input type="hidden" name="submission_id" value="<?= (int) $submission['id'] ?>">
        <div class="form-grid-two">
            <div><label class="form-label" for="type">Type</label><select class="form-select" id="type" name="type" required><?php foreach (SUBMISSION_TYPES as $type): ?><option value="<?= escape($type) ?>" <?= $submission['type'] === $type ? 'selected' : '' ?>><?= escape($type) ?></option><?php endforeach; ?></select></div>
            <div><label class="form-label" for="category">Category</label><select class="form-select" id="category" name="category" required><?php foreach (SUBMISSION_CATEGORIES as $category): ?><option value="<?= escape($category) ?>" <?= $submission['category'] === $category ? 'selected' : '' ?>><?= escape($category) ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="mt-3"><label class="form-label" for="subject">Subject</label><input class="form-control" id="subject" name="subject" maxlength="180" value="<?= escape($submission['subject']) ?>" required></div>
        <div class="mt-3"><label class="form-label" for="description">Details</label><textarea class="form-control" id="description" name="description" rows="6" maxlength="5000" required><?= escape($submission['description']) ?></textarea></div>
        <label class="anonymous-option mt-3"><input class="form-check-input" name="is_anonymous" type="checkbox" value="1" <?= (int) $submission['is_anonymous'] === 1 ? 'checked' : '' ?>><span><strong>Submit anonymously</strong><small>Your name will not appear on the submission.</small></span><i class="bi bi-incognito"></i></label>
        <div class="edit-actions"><a class="btn btn-light" href="submission.php?id=<?= (int) $submissionId ?>">Cancel</a><button class="btn btn-primary" type="submit"><span>Save changes</span><i class="bi bi-arrow-up-right"></i></button></div>
    </form>
</section>
<?php render_app_end(); ?>
