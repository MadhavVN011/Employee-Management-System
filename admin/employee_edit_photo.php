<?php
require 'includes/session.php';
require_once __DIR__.'/includes/photo.php';
$filename = null;
try {
    if (!isset($_POST['upload'])) {
        throw new InvalidArgumentException('Select employee to update photo first');
    }
    $id = $_POST['id'] ?? '';
    if (!is_string($id) || !ctype_digit($id) || !db_query($conn, 'SELECT id FROM employees WHERE id = ?', array($id))->num_rows) {
        throw new InvalidArgumentException('Employee not found');
    }
    $filename = store_photo($_FILES['photo'] ?? null);
    if ($filename === null) {
        throw new InvalidArgumentException('Choose a photo to upload.');
    }
    db_query($conn, 'UPDATE employees SET photo = ? WHERE id = ?', array($filename, $id));
    $_SESSION['success'] = 'Employee photo updated successfully';
} catch (InvalidArgumentException $error) {
    discard_photo($filename);
    $_SESSION['error'] = $error->getMessage();
} catch (mysqli_sql_exception $error) {
    discard_photo($filename);
    error_log($error->getMessage());
    $_SESSION['error'] = 'Unable to update the photo. Please try again.';
}
header('Location: employee.php');
exit;
