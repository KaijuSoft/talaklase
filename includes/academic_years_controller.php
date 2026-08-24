<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/academic_year.php';

require_permission('manage_academic_years');
$pdo = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    if (!verify_csrf()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
        exit;
    }

    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add') {
            $ayName = trim((string)($_POST['ay_name'] ?? ''));
            $startDate = trim((string)($_POST['start_date'] ?? ''));
            $endDate = trim((string)($_POST['end_date'] ?? ''));
            if ($ayName === '') throw new InvalidArgumentException('Academic year is required.');
            if ($startDate !== '' && $endDate !== '' && $startDate > $endDate) throw new InvalidArgumentException('End date must not be earlier than start date.');
            $check = $pdo->prepare('SELECT COUNT(*) FROM academic_year WHERE ay_name = ?');
            $check->execute([$ayName]);
            if ((int)$check->fetchColumn() > 0) throw new InvalidArgumentException('That academic year already exists.');
            $stmt = $pdo->prepare('INSERT INTO academic_year (ay_name,start_date,end_date,is_active,status) VALUES (?,?,?,0,\'Closed\')');
            $stmt->execute([$ayName, $startDate !== '' ? $startDate : null, $endDate !== '' ? $endDate : null]);
            echo json_encode(['success' => true, 'message' => 'Academic year added successfully.']);
            exit;
        }
        if ($action === 'activate') {
            $ayId = (int)($_POST['ay_id'] ?? 0);
            if ($ayId <= 0) throw new InvalidArgumentException('Invalid academic year.');
            $pdo->beginTransaction();
            $pdo->exec("UPDATE academic_year SET is_active=0,status='Closed'");
            $stmt = $pdo->prepare("UPDATE academic_year SET is_active=1,status='Active' WHERE ay_id=?");
            $stmt->execute([$ayId]);
            if ($stmt->rowCount() !== 1) throw new InvalidArgumentException('Academic year was not found.');
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Academic year activated.']);
            exit;
        }
        throw new InvalidArgumentException('Unknown academic year action.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Academic year action failed: ' . $e->getMessage());
        http_response_code($e instanceof InvalidArgumentException ? 422 : 500);
        echo json_encode(['success' => false, 'message' => $e instanceof InvalidArgumentException ? $e->getMessage() : 'Unable to complete the request.']);
        exit;
    }
}

$years = $pdo->query('SELECT * FROM academic_year ORDER BY ay_id DESC')->fetchAll(PDO::FETCH_ASSOC);