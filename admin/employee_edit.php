<?php
require 'includes/session.php';
require_once __DIR__.'/includes/assignment_functions.php';
try {
    if (!isset($_POST['edit'])) {
        throw new InvalidArgumentException('Select employee to edit first');
    }
    $fields = employee_fields($_POST);
    $id = $_POST['id'] ?? '';
    if (!is_string($id) || !ctype_digit($id)) {
        throw new InvalidArgumentException('Employee not found');
    }
    $conn->begin_transaction();
    lock_assignment($conn, 'position', $fields['position']);
    lock_assignment($conn, 'schedules', $fields['schedule']);
    if (!db_query($conn, 'SELECT id FROM employees WHERE id = ? FOR UPDATE', array($id))->num_rows) {
        throw new InvalidArgumentException('Employee not found');
    }
    db_query($conn, 'UPDATE employees SET firstname = ?, lastname = ?, address = ?, birthdate = ?, contact_info = ?, gender = ?, position_id = ?, schedule_id = ? WHERE id = ?',
        array($fields['firstname'], $fields['lastname'], $fields['address'], $fields['birthdate'], $fields['contact'], $fields['gender'], $fields['position'], $fields['schedule'], $id));
    $conn->commit();
    $_SESSION['success'] = 'Employee updated successfully';
} catch (InvalidArgumentException $error) {
    $conn->rollback();
    $_SESSION['error'] = $error->getMessage();
} catch (mysqli_sql_exception $error) {
    $conn->rollback();
    error_log($error->getMessage());
    $_SESSION['error'] = 'Unable to update the employee. Please check the details.';
}
header('Location: employee.php');
exit;
