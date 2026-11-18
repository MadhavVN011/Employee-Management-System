<?php
require 'includes/session.php';
require_once '../attendance_functions.php';
try {
    if (!isset($_POST['add'])) {
        throw new InvalidArgumentException('Fill up add form first');
    }
    $date = attendance_date($_POST['date'] ?? '');
    $time_in = attendance_time($_POST['time_in'] ?? '');
    $time_out = attendance_time($_POST['time_out'] ?? '');
    if ($time_out < $time_in) {
        throw new InvalidArgumentException('Time out must be after time in.');
    }
    $conn->begin_transaction();
    $employee = db_query($conn, 'SELECT * FROM employees WHERE employee_id = ? FOR UPDATE', array($_POST['employee'] ?? ''))->fetch_assoc();
    if (!$employee) {
        throw new InvalidArgumentException('Employee not found');
    }
    if (db_query($conn, 'SELECT id FROM attendance WHERE employee_id = ? AND date = ?', array($employee['id'], $date))->num_rows) {
        throw new InvalidArgumentException('Employee attendance for the day exists');
    }
    $shift = db_query($conn, 'SELECT * FROM schedules WHERE id = ?', array($employee['schedule_id']))->fetch_assoc();
    if (!$shift) {
        throw new InvalidArgumentException('No valid schedule is assigned to this employee.');
    }
    $hours = attendance_hours($time_in, $time_out, $shift['time_in'], $shift['time_out']);
    db_query($conn, 'INSERT INTO attendance (employee_id, date, time_in, time_out, status, num_hr) VALUES (?, ?, ?, ?, ?, ?)',
        array($employee['id'], $date, $time_in, $time_out, $time_in <= $shift['time_in'] ? 1 : 0, $hours));
    $conn->commit();
    $_SESSION['success'] = 'Attendance added successfully';
} catch (InvalidArgumentException $error) {
    $conn->rollback();
    $_SESSION['error'] = $error->getMessage();
} catch (mysqli_sql_exception $error) {
    $conn->rollback();
    error_log($error->getMessage());
    $_SESSION['error'] = 'Unable to add attendance. Please check the details.';
}
header('Location: attendance.php');
exit;
