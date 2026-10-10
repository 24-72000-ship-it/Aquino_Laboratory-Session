<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_role('admin');
require_once dirname(__DIR__) . '/config/db.php';

$status = request_string($_GET, 'status');
$category = request_string($_GET, 'category');
$type = request_string($_GET, 'type');
$fromDate = request_string($_GET, 'from');
$toDate = request_string($_GET, 'to');
$search = request_string($_GET, 'search');
if (!in_array($status, ['', ...SUBMISSION_STATUSES], true)) $status = '';
if (!in_array($category, ['', ...SUBMISSION_CATEGORIES], true)) $category = '';
if (!in_array($type, ['', ...SUBMISSION_TYPES], true)) $type = '';
if ($fromDate !== '' && !valid_date($fromDate)) $fromDate = '';
if ($toDate !== '' && !valid_date($toDate)) $toDate = '';

$conditions = [];
$params = [];
if ($status !== '') { $conditions[] = 's.status = :status'; $params['status'] = $status; }
if ($category !== '') { $conditions[] = 's.category = :category'; $params['category'] = $category; }
if ($type !== '') { $conditions[] = 's.type = :type'; $params['type'] = $type; }
if ($fromDate !== '') { $conditions[] = 's.created_at >= :from_date'; $params['from_date'] = $fromDate . ' 00:00:00'; }
if ($toDate !== '') { $conditions[] = 's.created_at < DATE_ADD(:to_date, INTERVAL 1 DAY)'; $params['to_date'] = $toDate; }
if ($search !== '') { $conditions[] = '(s.subject LIKE :search OR s.reference_no LIKE :search_ref)'; $params['search'] = '%' . $search . '%'; $params['search_ref'] = '%' . $search . '%'; }
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
$query = $pdo->prepare(
    "SELECT s.reference_no, s.type, s.category, s.subject, s.description, s.is_anonymous,
            s.status, s.admin_reply, s.created_at, u.full_name, u.email, u.address
     FROM submissions s JOIN users u ON u.id = s.user_id
     {$where} ORDER BY s.created_at DESC"
);
$query->execute($params);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="barangay-submissions-' . date('Y-m-d') . '.csv"');
header('X-Content-Type-Options: nosniff');
$output = fopen('php://output', 'wb');
fwrite($output, "\xEF\xBB\xBF");
fputcsv($output, ['Reference', 'Type', 'Category', 'Subject', 'Description', 'Anonymous', 'Resident', 'Email', 'Address', 'Status', 'Barangay reply', 'Submitted at']);
while ($row = $query->fetch()) {
    fputcsv($output, [
        safe_csv_cell($row['reference_no']),
        safe_csv_cell($row['type']),
        safe_csv_cell($row['category']),
        safe_csv_cell($row['subject']),
        safe_csv_cell($row['description']),
        (int) $row['is_anonymous'] === 1 ? 'Yes' : 'No',
        (int) $row['is_anonymous'] === 1 ? 'Anonymous' : safe_csv_cell($row['full_name']),
        (int) $row['is_anonymous'] === 1 ? '' : safe_csv_cell($row['email']),
        (int) $row['is_anonymous'] === 1 ? '' : safe_csv_cell($row['address']),
        safe_csv_cell($row['status']),
        safe_csv_cell((string) ($row['admin_reply'] ?? '')),
        safe_csv_cell($row['created_at']),
    ]);
}
fclose($output);
