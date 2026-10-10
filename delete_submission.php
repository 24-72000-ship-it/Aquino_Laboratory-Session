<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_role('admin');
require_once dirname(__DIR__) . '/config/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect_to('dashboard.php');
verify_csrf_token();
$submissionId = filter_input(INPUT_POST, 'submission_id', FILTER_VALIDATE_INT);
if (!$submissionId || $submissionId < 1) {
    http_response_code(400);
    exit('Invalid submission.');
}
$query = $pdo->prepare('SELECT image_path FROM submissions WHERE id = :id');
$query->execute(['id' => $submissionId]);
$submission = $query->fetch();
if (!$submission) {
    http_response_code(404);
    exit('Submission not found.');
}
$delete = $pdo->prepare('DELETE FROM submissions WHERE id = :id');
$delete->execute(['id' => $submissionId]);
remove_submission_image($submission['image_path']);
set_flash('Submission removed.');
redirect_to('dashboard.php');
