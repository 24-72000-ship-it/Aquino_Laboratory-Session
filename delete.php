<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_role('resident');
require_once dirname(__DIR__) . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('dashboard.php');
}
verify_csrf_token();
$submissionId = filter_input(INPUT_POST, 'submission_id', FILTER_VALIDATE_INT);
if (!$submissionId || $submissionId < 1) {
    http_response_code(400);
    exit('Invalid submission.');
}

$query = $pdo->prepare(
    "SELECT image_path FROM submissions WHERE id = :id AND user_id = :user_id AND status = 'Received'"
);
$query->execute(['id' => $submissionId, 'user_id' => current_user_id()]);
$submission = $query->fetch();
if (!$submission) {
    http_response_code(404);
    exit('Submission not found or no longer editable.');
}
$delete = $pdo->prepare(
    "DELETE FROM submissions WHERE id = :id AND user_id = :user_id AND status = 'Received'"
);
$delete->execute(['id' => $submissionId, 'user_id' => current_user_id()]);
if ($delete->rowCount() !== 1) {
    http_response_code(409);
    exit('The submission status changed. Refresh and try again.');
}
if ($delete->rowCount() !== 1) {
    http_response_code(409);
    exit('The submission status changed. Refresh and try again.');
}
remove_submission_image($submission['image_path']);
set_flash('Your submission was deleted.');
redirect_to('dashboard.php');
