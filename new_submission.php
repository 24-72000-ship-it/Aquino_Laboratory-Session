<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_role('resident');

render_app_start('New submission', 'resident', 'new');
?>
<div class="detail-back"><a class="back-link-inline" href="dashboard.php"><i class="bi bi-arrow-left"></i> Back to dashboard</a></div>
<div class="edit-page-intro">
    <p class="eyebrow">MAKE YOUR VOICE HEARD</p>
    <h1 class="page-title">New submission</h1>
    <p class="welcome-subtitle">Share a concern or an idea with your barangay.</p>
</div>
<section class="edit-card new-submission-card">
    <form action="submit.php" method="post" enctype="multipart/form-data" class="new-submission-form" data-loading-form>
        <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
        <div class="form-grid-two">
            <div>
                <label class="form-label" for="type">Submission type <span class="required-mark">*</span></label>
                <select class="form-select" id="type" name="type" required>
                    <option value="">Choose one</option>
                    <?php foreach (SUBMISSION_TYPES as $type): ?><option value="<?= escape($type) ?>"><?= escape($type) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label" for="category">Category <span class="required-mark">*</span></label>
                <select class="form-select" id="category" name="category" required>
                    <option value="">Choose a category</option>
                    <?php foreach (SUBMISSION_CATEGORIES as $category): ?><option value="<?= escape($category) ?>"><?= escape($category) ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="mt-3">
            <label class="form-label" for="subject">Subject <span class="required-mark">*</span></label>
            <input class="form-control" id="subject" name="subject" maxlength="180" placeholder="Briefly describe the issue or idea" required>
        </div>
        <div class="mt-3">
            <label class="form-label" for="description">Details <span class="required-mark">*</span></label>
            <textarea class="form-control" id="description" name="description" rows="6" maxlength="5000" placeholder="Tell us what happened or how we can improve things..." required></textarea>
            <div class="form-hint">Please avoid sharing sensitive personal information.</div>
        </div>
        <div class="mt-3">
            <label class="form-label" for="image">Attach a photo <span class="optional-label">(optional · JPG, PNG, WEBP · max 2 MB)</span></label>
            <input class="form-control file-input" id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-file-upload>
            <div class="form-hint" data-file-feedback>One image can help us understand the situation.</div>
        </div>
        <label class="anonymous-option mt-3">
            <input class="form-check-input" name="is_anonymous" type="checkbox" value="1">
            <span><strong>Submit anonymously</strong><small>Your name will not appear on the submission.</small></span>
            <i class="bi bi-incognito"></i>
        </label>
        <div class="edit-actions">
            <a class="btn btn-light" href="dashboard.php">Cancel</a>
            <button class="btn btn-primary" type="submit"><span>Send submission</span><i class="bi bi-send"></i></button>
        </div>
    </form>
</section>
<?php render_app_end(); ?>
