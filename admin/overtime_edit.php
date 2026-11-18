<?php
require 'includes/session.php';
require_once __DIR__.'/includes/overtime_functions.php';
try {
    if (!isset($_POST['edit'])) {
        throw new InvalidArgumentException('Fill up edit form first');
    }
    list($date, $hours, $rate) = overtime_values($_POST);
    $id = $_POST['id'] ?? '';
    if (!is_string($id) || !ctype_digit($id) || !db_query($conn, 'SELECT id FROM overtime WHERE id = ?', array($id))->num_rows) {
        throw new InvalidArgumentException('Overtime record not found');
    }
    db_query($conn, 'UPDATE overtime SET hours = ?, rate = ?, date_overtime = ? WHERE id = ?', array($hours, $rate, $date, $id));
    $_SESSION['success'] = 'Overtime updated successfully';
} catch (InvalidArgumentException $error) {
    $_SESSION['error'] = $error->getMessage();
} catch (mysqli_sql_exception $error) {
    error_log($error->getMessage());
    $_SESSION['error'] = 'Unable to update overtime. Please check the details.';
}
header('Location: overtime.php');
exit;
