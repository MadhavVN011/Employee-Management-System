<?php
require 'includes/session.php';
require_once __DIR__.'/includes/overtime_functions.php';
try {
    if (!isset($_POST['add'])) {
        throw new InvalidArgumentException('Fill up add form first');
    }
    list($date, $hours, $rate) = overtime_values($_POST);
    $employee_id = $_POST['employee'] ?? '';
    if (!is_string($employee_id)) {
        throw new InvalidArgumentException('Employee not found');
    }
    $employee = db_query($conn, 'SELECT id FROM employees WHERE employee_id = ?', array($employee_id))->fetch_assoc();
    if (!$employee) {
        throw new InvalidArgumentException('Employee not found');
    }
    db_query($conn, 'INSERT INTO overtime (employee_id, date_overtime, hours, rate) VALUES (?, ?, ?, ?)', array($employee['id'], $date, $hours, $rate));
    $_SESSION['success'] = 'Overtime added successfully';
} catch (InvalidArgumentException $error) {
    $_SESSION['error'] = $error->getMessage();
} catch (mysqli_sql_exception $error) {
    error_log($error->getMessage());
    $_SESSION['error'] = 'Unable to add overtime. Please check the details.';
}
header('Location: overtime.php');
exit;
