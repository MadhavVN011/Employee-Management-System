<?php
require 'includes/session.php';
require_once __DIR__.'/includes/photo.php';
$pages = array('home.php', 'employee.php', 'attendance.php', 'overtime.php', 'cashadvance.php',
    'schedule.php', 'position.php', 'deduction.php', 'schedule_employee.php', 'payroll.php');
$return = $_GET['return'] ?? 'home.php';
if (!is_string($return) || !in_array($return, $pages, true)) {
    $return = 'home.php';
}
$filename = null;
try {
    if (!isset($_POST['save'])) {
        throw new InvalidArgumentException('Fill up required details first');
    }
    foreach (array('curr_password', 'username', 'firstname', 'lastname') as $field) {
        if (!isset($_POST[$field]) || !is_string($_POST[$field]) || trim($_POST[$field]) === '') {
            throw new InvalidArgumentException('Fill up required details first');
        }
    }
    if (!password_verify($_POST['curr_password'], $user['password'])) {
        throw new InvalidArgumentException('Incorrect password');
    }
    $password = $_POST['password'] ?? '';
    if (!is_string($password) || strpos($password, "\0") !== false || ($password !== '' && trim($password) === '')) {
        throw new InvalidArgumentException('Enter a valid new password or leave it blank.');
    }
    $hash = $password === '' ? $user['password'] : password_hash($password, PASSWORD_DEFAULT);
    $filename = store_photo($_FILES['photo'] ?? null);
    db_query($conn, 'UPDATE admin SET username = ?, password = ?, firstname = ?, lastname = ?, photo = ? WHERE id = ?',
        array(trim($_POST['username']), $hash, $_POST['firstname'], $_POST['lastname'], $filename ?? $user['photo'], $user['id']));
    $_SESSION['success'] = 'Admin profile updated successfully';
} catch (InvalidArgumentException $error) {
    discard_photo($filename);
    $_SESSION['error'] = $error->getMessage();
} catch (mysqli_sql_exception $error) {
    discard_photo($filename);
    error_log($error->getMessage());
    $_SESSION['error'] = 'Unable to update the profile. Please check the details.';
}
header('Location: '.$return);
exit;
