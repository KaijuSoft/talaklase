<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_permission('manage_score_settings');
$pdo = getConnection();

$allowedTerms = ['Prelim','Midterm','PreFinal','Final'];
$components = ['participation','written','performance'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    if (!verify_csrf()) {
        http_response_code(403);
        echo json_encode(['success'=>false,'message'=>'Invalid security token.']);
        exit;
    }
    $action = $_POST['action'] ?? '';
    try {
        $term = (string)($_POST['term'] ?? '');
        if (!in_array($term, $allowedTerms, true)) throw new InvalidArgumentException('Invalid academic term.');
        if ($action === 'load') {
            $result=[];
            foreach ($components as $comp) {
                $stmt=$pdo->prepare('SELECT one_max,two_max,three_max,four_max,five_max FROM score_settings WHERE term=? AND component=? LIMIT 1');
                $stmt->execute([$term,$comp]);
                $row=$stmt->fetch(PDO::FETCH_NUM);
                $result[$comp]=$row ? array_map('intval',$row) : [0,0,0,0,0];
            }
            $stmt=$pdo->prepare('SELECT score_max FROM exam_settings WHERE term=? LIMIT 1');
            $stmt->execute([$term]);
            $exam=$stmt->fetchColumn();
            $result['exam']=$exam !== false ? (int)$exam : 0;
            echo json_encode($result);
            exit;
        }
        if ($action === 'save') {
            $values=[];
            foreach ($components as $comp) {
                for ($i=1;$i<=5;$i++) {
                    $key="{$comp}_{$i}";
                    $value=filter_var($_POST[$key] ?? null,FILTER_VALIDATE_FLOAT);
                    if ($value === false || $value < 0 || $value > 1000) throw new InvalidArgumentException('Invalid maximum score supplied.');
                    $values[$comp][]=$value;
                }
            }
            $examMax=filter_var($_POST['exam_max'] ?? null,FILTER_VALIDATE_FLOAT);
            if ($examMax === false || $examMax < 0 || $examMax > 1000) throw new InvalidArgumentException('Invalid exam maximum score.');
            $pdo->beginTransaction();
            $sql='INSERT INTO score_settings (term,component,one_max,two_max,three_max,four_max,five_max) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE one_max=VALUES(one_max),two_max=VALUES(two_max),three_max=VALUES(three_max),four_max=VALUES(four_max),five_max=VALUES(five_max)';
            foreach ($components as $comp) $pdo->prepare($sql)->execute([$term,$comp,...$values[$comp]]);
            $pdo->prepare('INSERT INTO exam_settings (term,score_max) VALUES (?,?) ON DUPLICATE KEY UPDATE score_max=VALUES(score_max)')->execute([$term,$examMax]);
            $pdo->commit();
            echo json_encode(['success'=>true,'message'=>"$term max scores saved successfully."]);
            exit;
        }
        throw new InvalidArgumentException('Unknown score-settings action.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Score settings action failed: '.$e->getMessage());
        http_response_code($e instanceof InvalidArgumentException ? 422 : 500);
        echo json_encode(['success'=>false,'message'=>$e instanceof InvalidArgumentException ? $e->getMessage() : 'Unable to complete the request.']);
        exit;
    }
}

$terms=['Prelim'=>'Prelim','Midterm'=>'Midterm','PreFinal'=>'Pre-Finals','Final'=>'Finals'];