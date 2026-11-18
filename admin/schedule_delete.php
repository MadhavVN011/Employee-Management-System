<?php
require 'includes/session.php';
require_once __DIR__.'/includes/assignment_functions.php';
try {
    if (!isset($_POST['delete'])) {
        throw new InvalidArgumentException('Select an item to delete first');
    }
    $id = $_POST['id'] ?? '';
    $conn->begin_transaction();
    lock_assignment($conn, 'schedules', $id);
    if (db_query($conn, 'SELECT id FROM employees WHERE schedule_id = ? LIMIT 1', array($id))->num_rows) {
        throw new InvalidArgumentException('Reassign employees before deleting this schedule.');
    }
    db_query($conn, 'DELETE FROM schedules WHERE id = ?', array($id));
    $conn->commit();
    $_SESSION['success'] = 'Schedule deleted successfully';
} catch (InvalidArgumentException $error) {
    $conn->rollback();
    $_SESSION['error'] = $error->getMessage();
} catch (mysqli_sql_exception $error) {
    $conn->rollback();
    error_log($error->getMessage());
    $_SESSION['error'] = 'Unable to delete the schedule. Please try again.';
}
header('Location: schedule.php');
exit;
