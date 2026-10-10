<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_role('resident');
require_once dirname(__DIR__) . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('dashboard.php');
}
verify_csrf_token();

$type = request_string($_POST, 'type');
$category = request_string($_POST, 'category');
$subject = request_string($_POST, 'subject');
$description = request_string($_POST, 'description');
$anonymous = isset($_POST['is_anonymous']) && request_string($_POST, 'is_anonymous') === '1' ? 1 : 0;
if (!in_array($type, SUBMISSION_TYPES, true)
    || !in_array($category, SUBMISSION_CATEGORIES, true)
    || $subject === ''
    || text_length($subject) > 180
    || $description === ''
    || text_length($description) > 5000) {
    set_flash('Please complete the required fields and check their length.', 'danger');
    redirect_to('new_submission.php');
}

$upload = isset($_FILES['image']) && is_array($_FILES['image']) ? $_FILES['image'] : null;
try {
    $imagePath = store_submission_image($upload);
} catch (InvalidArgumentException $exception) {
    set_flash($exception->getMessage(), 'danger');
    redirect_to('new_submission.php');
} catch (RuntimeException $exception) {
    error_log($exception->getMessage());
    set_flash($exception->getMessage(), 'danger');
    redirect_to('new_submission.php');
}

try {
    $pdo->beginTransaction();
    $temporaryReference = 'TMP-' . bin2hex(random_bytes(12));
    $insert = $pdo->prepare(
        'INSERT INTO submissions (reference_no, user_id, type, category, subject, description, image_path, is_anonymous)
         VALUES (:reference_no, :user_id, :type, :category, :subject, :description, :image_path, :is_anonymous)'
    );
    $insert->execute([
        'reference_no' => $temporaryReference,
        'user_id' => current_user_id(),
        'type' => $type,
        'category' => $category,
        'subject' => $subject,
        'description' => $description,
        'image_path' => $imagePath,
        'is_anonymous' => $anonymous,
    ]);
    $submissionId = (int) $pdo->lastInsertId();
    $reference = 'BRG-' . date('Y') . '-' . str_pad((string) $submissionId, 4, '0', STR_PAD_LEFT);
    $update = $pdo->prepare('UPDATE submissions SET reference_no = :reference WHERE id = :id');
    $update->execute(['reference' => $reference, 'id' => $submissionId]);
    $history = $pdo->prepare(
        "INSERT INTO status_history (submission_id, status, remarks) VALUES (:submission_id, 'Received', :remarks)"
    );
    $history->execute(['submission_id' => $submissionId, 'remarks' => 'Submission received by the barangay.']);
    $pdo->commit();
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    remove_submission_image($imagePath);
    error_log('Submission create failed: ' . $exception->getMessage());
    set_flash('We could not save your submission right now. Please try again.', 'danger');
    redirect_to('new_submission.php');
}

set_flash('Your submission was sent successfully. Reference: ' . $reference);
redirect_to('submission.php?id=' . $submissionId);
