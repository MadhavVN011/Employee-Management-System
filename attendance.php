<?php
require_once __DIR__.'/conn.php';
require_once __DIR__.'/timezone.php';
require_once __DIR__.'/attendance_functions.php';

$employee = $_POST['employee'] ?? $_GET['employee'] ?? '';
$status = $_POST['status'] ?? $_GET['status'] ?? '';
$json = $status !== '' || $_SERVER['REQUEST_METHOD'] === 'POST';
$output = array('error' => false);
try {
    if (!is_string($employee) || trim($employee) === '') {
        throw new InvalidArgumentException('Enter an Employee ID.');
    }
    if (!in_array($status, array('', 'in', 'out'), true)) {
        throw new InvalidArgumentException('Select Time In or Time Out.');
    }
    $conn->begin_transaction();
    // Serialize attendance changes for this employee, including QR requests.
    $person = db_query($conn, 'SELECT * FROM employees WHERE employee_id = ? FOR UPDATE', array($employee))->fetch_assoc();
    if (!$person) {
        throw new InvalidArgumentException('Employee ID not found');
    }
    $shift = db_query($conn, 'SELECT * FROM schedules WHERE id = ?', array($person['schedule_id']))->fetch_assoc();
    if (!$shift) {
        throw new InvalidArgumentException('No valid schedule is assigned to this employee.');
    }
    $date = date('Y-m-d');
    $time = date('H:i:s');
    $attendance = db_query($conn, 'SELECT * FROM attendance WHERE employee_id = ? AND date = ?', array($person['id'], $date))->fetch_assoc();
    if ($status === '') {
        $status = $attendance ? 'out' : 'in';
    }
    if ($status === 'in') {
        if ($attendance) {
            throw new InvalidArgumentException('You have timed in for today');
        }
        db_query($conn, 'INSERT INTO attendance (employee_id, date, time_in, status, time_out, num_hr) VALUES (?, ?, ?, ?, ?, ?)',
            array($person['id'], $date, $time, $time <= $shift['time_in'] ? 1 : 0, '00:00:00', 0));
        $output['message'] = 'Time in: '.$person['firstname'].' '.$person['lastname'];
    } else {
        if (!$attendance) {
            throw new InvalidArgumentException('Cannot Timeout. No time in.');
        }
        if ($attendance['time_out'] !== '00:00:00') {
            throw new InvalidArgumentException('You have timed out for today');
        }
        $hours = attendance_hours($attendance['time_in'], $time, $shift['time_in'], $shift['time_out']);
        db_query($conn, 'UPDATE attendance SET time_out = ?, num_hr = ? WHERE id = ?', array($time, $hours, $attendance['id']));
        $output['message'] = 'Time out: '.$person['firstname'].' '.$person['lastname'];
    }
    $conn->commit();
} catch (InvalidArgumentException $error) {
    $conn->rollback();
    $output = array('error' => true, 'message' => $error->getMessage());
} catch (mysqli_sql_exception $error) {
    $conn->rollback();
    error_log($error->getMessage());
    $output = array('error' => true, 'message' => 'Unable to save attendance. Please try again.');
}
if ($json) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($output);
} else {
    header('Content-Type: text/html; charset=utf-8');
    echo '<h1 style="margin-top:20%; text-align:center">'.htmlspecialchars($output['message'], ENT_QUOTES, 'UTF-8').'</h1>';
}
