<?php
require 'includes/session.php';
require_once '../attendance_functions.php';
try {
    if (!isset($_POST['edit'])) {
        throw new InvalidArgumentException('Fill up edit form first');
    }
    $id = $_POST['id'] ?? '';
    $date = attendance_date($_POST['edit_date'] ?? '');
    $time_in = attendance_time($_POST['edit_time_in'] ?? '');
    $time_out = attendance_time($_POST['edit_time_out'] ?? '');
    if ($time_out < $time_in) {
        throw new InvalidArgumentException('Time out must be after time in.');
    }
    $shift = db_query($conn, 'SELECT schedules.*, attendance.employee_id FROM attendance JOIN employees ON employees.id = attendance.employee_id JOIN schedules ON schedules.id = employees.schedule_id WHERE attendance.id = ?', array($id))->fetch_assoc();
    if (!$shift) {
        throw new InvalidArgumentException('Attendance or assigned schedule not found.');
    }
    if (db_query($conn, 'SELECT id FROM attendance WHERE employee_id = ? AND date = ? AND id <> ?', array($shift['employee_id'], $date, $id))->num_rows) {
        throw new InvalidArgumentException('Employee attendance for the day exists');
    }
    $hours = attendance_hours($time_in, $time_out, $shift['time_in'], $shift['time_out']);
    db_query($conn, 'UPDATE attendance SET date = ?, time_in = ?, time_out = ?, status = ?, num_hr = ? WHERE id = ?',
        array($date, $time_in, $time_out, $time_in <= $shift['time_in'] ? 1 : 0, $hours, $id));
    $_SESSION['success'] = 'Attendance updated successfully';
} catch (InvalidArgumentException $error) {
    $_SESSION['error'] = $error->getMessage();
} catch (mysqli_sql_exception $error) {
    error_log($error->getMessage());
    $_SESSION['error'] = 'Unable to update attendance. Please check the details.';
}
header('Location: attendance.php');
exit;
