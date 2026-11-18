<?php
require 'includes/session.php';
require_once __DIR__.'/includes/photo.php';
require_once __DIR__.'/includes/assignment_functions.php';
$filename = null;
try {
    if (!isset($_POST['add'])) {
        throw new InvalidArgumentException('Fill up add form first');
    }
    $fields = employee_fields($_POST);
    $conn->begin_transaction();
    lock_assignment($conn, 'position', $fields['position']);
    lock_assignment($conn, 'schedules', $fields['schedule']);
    $filename = store_photo($_FILES['photo'] ?? null);
    $employee_id = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3).substr(str_shuffle('0123456789'), 0, 9);
    db_query($conn, 'INSERT INTO employees (employee_id, firstname, lastname, address, birthdate, contact_info, gender, position_id, schedule_id, photo, created_on) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
        array($employee_id, $fields['firstname'], $fields['lastname'], $fields['address'], $fields['birthdate'], $fields['contact'], $fields['gender'], $fields['position'], $fields['schedule'], $filename ?? ''));
    $conn->commit();
    $_SESSION['success'] = 'Employee added successfully';
} catch (InvalidArgumentException $error) {
    $conn->rollback();
    discard_photo($filename);
    $_SESSION['error'] = $error->getMessage();
} catch (mysqli_sql_exception $error) {
    $conn->rollback();
    discard_photo($filename);
    error_log($error->getMessage());
    $_SESSION['error'] = 'Unable to add the employee. Please check the details.';
}
header('Location: employee.php');
exit;
