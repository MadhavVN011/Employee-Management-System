<?php
require 'includes/session.php';
require_once __DIR__.'/includes/assignment_functions.php';
try {
    if (!isset($_POST['edit'])) {
        throw new InvalidArgumentException('Select schedule to edit first');
    }
    $id = $_POST['id'] ?? '';
    if (!is_string($id) || !ctype_digit($id)) {
        throw new InvalidArgumentException('Employee not found');
    }
    $conn->begin_transaction();
    lock_assignment($conn, 'schedules', $_POST['schedule'] ?? '');
    if (!db_query($conn, 'SELECT id FROM employees WHERE id = ? FOR UPDATE', array($id))->num_rows) {
        throw new InvalidArgumentException('Employee not found');
    }
    db_query($conn, 'UPDATE employees SET schedule_id = ? WHERE id = ?', array($_POST['schedule'], $id));
    $conn->commit();
    $_SESSION['success'] = 'Schedule updated successfully';
} catch (InvalidArgumentException $error) {
    $conn->rollback();
    $_SESSION['error'] = $error->getMessage();
} catch (mysqli_sql_exception $error) {
    $conn->rollback();
    error_log($error->getMessage());
    $_SESSION['error'] = 'Unable to update the schedule. Please check the details.';
}
header('Location: schedule_employee.php');
exit;
