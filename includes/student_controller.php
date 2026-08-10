<?php

/**
 * Student-page helper functions.
 * Request handling and data preparation will migrate here incrementally.
 */
function instructorCanUseSection(array $sectionScope, int $sectionId): bool
{
    return !empty($sectionScope['section_ids'])
        && in_array($sectionId, $sectionScope['section_ids'], true);
}

function handleStudentAction(PDO $pdo, string $action, array $input, bool $isInstructorScoped, array $sectionScope): array
{
    if ($action === 'add') {
        require_permission('edit_students');
        if ($isInstructorScoped && !instructorCanUseSection($sectionScope, (int)($input['section_id'] ?? 0))) {
            return ['success' => false, 'message' => 'You can only add students to sections you created.'];
        }

        try {
            $pdo->beginTransaction();
            $studentNo = trim($input['student_no'] ?? '');
            $chk = $pdo->prepare("SELECT COUNT(*) FROM student WHERE st_lastname=? AND st_name=? AND st_middlename=? AND st_suffix=? AND course_id=?");
            $chk->execute([
                ucwords(strtolower($input['lastname'] ?? '')),
                ucwords(strtolower($input['firstname'] ?? '')),
                ucwords(strtolower($input['middlename'] ?? '')),
                ucwords(strtolower($input['suffix'] ?? '')),
                $input['course_id'] ?? null,
            ]);
            if ($chk->fetchColumn() > 0) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Duplicate student record found.'];
            }
            if ($studentNo !== '') {
                $studentNoChk = $pdo->prepare("SELECT COUNT(*) FROM student WHERE student_no = ?");
                $studentNoChk->execute([$studentNo]);
                if ($studentNoChk->fetchColumn() > 0) {
                    $pdo->rollBack();
                    return ['success' => false, 'message' => 'Student Number already exists.'];
                }
            } else {
                $studentNo = null;
            }

            $ins = $pdo->prepare("INSERT INTO student (student_no,st_lastname,st_name,st_middlename,st_suffix,st_gender,course_id) VALUES (?,?,?,?,?,?,?)");
            $ins->execute([
                $studentNo,
                ucwords(strtolower($input['lastname'] ?? '')),
                ucwords(strtolower($input['firstname'] ?? '')),
                ucwords(strtolower($input['middlename'] ?? '')),
                ucwords(strtolower($input['suffix'] ?? '')),
                $input['gender'] ?? '',
                $input['course_id'] ?? null,
            ]);
            $stId = $pdo->lastInsertId();
            $ayId = current_ay_id($pdo);
            $sec = $pdo->prepare("INSERT INTO student_section (st_id,sectionID,yearlvl,ay_id) VALUES (?,?,?,?)");
            $sec->execute([$stId, $input['section_id'] ?? null, $input['year_level'] ?? null, $ayId]);
            $pdo->commit();
            return ['success' => true, 'message' => 'Student added successfully.'];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    if ($action === 'update') {
        require_permission('edit_students');
        if ($isInstructorScoped && !instructorCanUseSection($sectionScope, (int)($input['section_id'] ?? 0))) {
            return ['success' => false, 'message' => 'You can only update students in sections you created.'];
        }
        try {
            $pdo->beginTransaction();
            $studentNo = trim($input['student_no'] ?? '');
            if ($studentNo !== '') {
                $studentNoChk = $pdo->prepare("SELECT COUNT(*) FROM student WHERE student_no = ? AND st_id <> ?");
                $studentNoChk->execute([$studentNo, $input['st_id'] ?? 0]);
                if ($studentNoChk->fetchColumn() > 0) {
                    $pdo->rollBack();
                    return ['success' => false, 'message' => 'Student Number already exists.'];
                }
            } else {
                $studentNo = null;
            }

            $upd = $pdo->prepare("UPDATE student SET student_no=?,st_lastname=?,st_name=?,st_middlename=?,st_suffix=?,st_gender=?,course_id=? WHERE st_id=?");
            $upd->execute([
                $studentNo,
                $input['lastname'] ?? '',
                $input['firstname'] ?? '',
                $input['middlename'] ?? '',
                $input['suffix'] ?? '',
                $input['gender'] ?? '',
                $input['course_id'] ?? null,
                $input['st_id'] ?? 0,
            ]);
            $ayId = current_ay_id($pdo);
            $updSec = $pdo->prepare("UPDATE student_section SET sectionID=?,yearlvl=? WHERE st_id=? AND ay_id=?");
            $updSec->execute([
                $input['section_id'] ?? null,
                $input['year_level'] ?? null,
                $input['st_id'] ?? 0,
                $ayId,
            ]);
            $pdo->commit();
            return ['success' => true, 'message' => 'Student updated.'];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    if ($action === 'delete') {
        require_permission('edit_students');
        try {
            if ($isInstructorScoped) {
                $sectionCheck = $pdo->prepare("SELECT ss.sectionID FROM student_section ss WHERE ss.st_id=? LIMIT 1");
                $sectionCheck->execute([$input['st_id'] ?? 0]);
                $currentSectionId = (int)$sectionCheck->fetchColumn();
                if (!instructorCanUseSection($sectionScope, $currentSectionId)) {
                    return ['success' => false, 'message' => 'You can only delete students from sections you created.'];
                }
            }

            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM student_section WHERE st_id=?")->execute([$input['st_id'] ?? 0]);
            $pdo->prepare("DELETE FROM student WHERE st_id=?")->execute([$input['st_id'] ?? 0]);
            $pdo->commit();
            return ['success' => true, 'message' => 'Student deleted.'];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    return ['success' => false, 'message' => 'Unknown student action.'];
}
/**
 * Build all data required by the students page.
 * The page remains responsible only for rendering the returned view data.
 */
function loadStudentPageData(PDO $pdo, bool $isInstructorScoped, array $ownedSectionIds, array $input = []): array
{
    $page = max(1, (int)($input['p'] ?? 1));
    $pageSize = 15;
    $search = trim((string)($input['q'] ?? ''));
    $filterSection = (int)($input['section_id'] ?? 0);
    $offset = ($page - 1) * $pageSize;

    $sectionScope = buildStudentSectionScope($isInstructorScoped, $ownedSectionIds);
    $whereParts = [];
    $params = [];

    if ($search !== '') {
        $whereParts[] = '(student.student_no LIKE :q OR student.st_lastname LIKE :q OR student.st_name LIKE :q)';
        $params[':q'] = "%{$search}%";
    }
    if ($filterSection > 0) {
        $whereParts[] = 'student_section.sectionID = :filter_section';
        $params[':filter_section'] = $filterSection;
    }
    if ($sectionScope['clause'] !== '') {
        $whereParts[] = $sectionScope['clause'];
        $params = array_merge($params, $sectionScope['params']);
    }

    $where = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';

    $countSql = "SELECT COUNT(DISTINCT student.st_id)
                 FROM student
                 INNER JOIN student_section ON student.st_id = student_section.st_id
                 INNER JOIN section ON section.sectionID = student_section.sectionID
                 {$where}";
    $countStmt = $pdo->prepare($countSql);
    bindStudentParams($countStmt, $params);
    $countStmt->execute();
    $totalRecords = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalRecords / $pageSize));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $pageSize;

    $assignedSql = "SELECT COUNT(DISTINCT s.st_id)
                    FROM student s
                    INNER JOIN student_section ss ON s.st_id = ss.st_id";
    $assignedStudents = (int)$pdo->query($assignedSql)->fetchColumn();

    $unassignedSql = "SELECT COUNT(*)
                      FROM student s
                      LEFT JOIN student_section ss ON s.st_id = ss.st_id
                      WHERE ss.st_id IS NULL";
    $unassignedStudents = (int)$pdo->query($unassignedSql)->fetchColumn();

    $sql = "SELECT student.st_id, student.student_no, student.st_lastname, student.st_name, student.st_middlename,
                   student.st_suffix, student.st_gender, course.course_acronym,
                   student_section.yearlvl, section.section
            FROM student
            INNER JOIN course ON student.course_id = course.course_id
            INNER JOIN student_section ON student.st_id = student_section.st_id
            INNER JOIN section ON section.sectionID = student_section.sectionID
            {$where}
            ORDER BY section.section ASC, student.st_lastname ASC, student.st_name ASC
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    bindStudentParams($stmt, $params);
    $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $students = $stmt->fetchAll();

    $courses = $pdo->query('SELECT course_id, course_acronym FROM course ORDER BY course_acronym')->fetchAll();
    $sections = loadStudentSections($pdo, $isInstructorScoped, $ownedSectionIds);
    $years = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
    $genders = ['Male', 'Female'];

    $maleWhere = $where ? $where . " AND student.st_gender='Male'" : "WHERE student.st_gender='Male'";
    $femaleWhere = $where ? $where . " AND student.st_gender='Female'" : "WHERE student.st_gender='Female'";
    $maleStmt = $pdo->prepare("SELECT COUNT(DISTINCT student.st_id) FROM student INNER JOIN student_section ON student.st_id=student_section.st_id {$maleWhere}");
    $femaleStmt = $pdo->prepare("SELECT COUNT(DISTINCT student.st_id) FROM student INNER JOIN student_section ON student.st_id=student_section.st_id {$femaleWhere}");
    bindStudentParams($maleStmt, $params);
    bindStudentParams($femaleStmt, $params);
    $maleStmt->execute();
    $femaleStmt->execute();

    return [
        'page' => $page,
        'pageSize' => $pageSize,
        'search' => $search,
        'filterSection' => $filterSection,
        'offset' => $offset,
        'totalRecords' => $totalRecords,
        'totalPages' => $totalPages,
        'assignedStudents' => $assignedStudents,
        'unassignedStudents' => $unassignedStudents,
        'male' => (int)$maleStmt->fetchColumn(),
        'female' => (int)$femaleStmt->fetchColumn(),
        'students' => $students,
        'courses' => $courses,
        'sections' => $sections,
        'years' => $years,
        'genders' => $genders,
    ];
}

function buildStudentSectionScope(bool $isInstructorScoped, array $ownedSectionIds): array
{
    $scope = ['clause' => '', 'params' => [], 'section_ids' => $ownedSectionIds];
    if (!$isInstructorScoped) {
        return $scope;
    }

    if (empty($ownedSectionIds)) {
        $scope['clause'] = '1=0';
        return $scope;
    }

    $placeholders = [];
    foreach ($ownedSectionIds as $index => $sectionId) {
        $key = ':sec' . $index;
        $placeholders[] = $key;
        $scope['params'][$key] = $sectionId;
    }
    $scope['clause'] = 'student_section.sectionID IN (' . implode(',', $placeholders) . ')';
    return $scope;
}

function bindStudentParams(PDOStatement $statement, array $params): void
{
    foreach ($params as $key => $value) {
        $statement->bindValue($key, $value);
    }
}

function loadStudentSections(PDO $pdo, bool $isInstructorScoped, array $ownedSectionIds): array
{
    $sql = 'SELECT sectionID, section FROM section';
    $params = [];
    if ($isInstructorScoped) {
        if (empty($ownedSectionIds)) {
            $sql .= ' WHERE 1=0';
        } else {
            $sql .= ' WHERE sectionID IN (' . implode(',', array_fill(0, count($ownedSectionIds), '?')) . ')';
            $params = $ownedSectionIds;
        }
    }
    $sql .= ' ORDER BY section';
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    return $statement->fetchAll();
}

function prepareStudentPageContext(PDO $pdo): array
{
    $currentUser = current_user();
    $isInstructorScoped = in_array($currentUser['role'] ?? '', ['instructor', 'instructor_admin'], true);
    $ownedSectionIds = current_user_owned_section_ids($pdo);
    return [
        'currentUser' => $currentUser,
        'isInstructorScoped' => $isInstructorScoped,
        'ownedSectionIds' => $ownedSectionIds,
        'sectionScope' => buildStudentSectionScope($isInstructorScoped, $ownedSectionIds),
    ];
}

function handleStudentPageRequest(): array
{
    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/auth.php';
    require_permission('view_students');

    $pdo = getConnection();
    $context = prepareStudentPageContext($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        header('Content-Type: application/json');
        echo json_encode(handleStudentAction(
            $pdo,
            $action,
            $_POST,
            $context['isInstructorScoped'],
            $context['sectionScope']
        ));
        exit;
    }

    $data = loadStudentPageData(
        $pdo,
        $context['isInstructorScoped'],
        $context['ownedSectionIds'],
        $_GET
    );

    return array_merge($context, $data);
}
