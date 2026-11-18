<?php
session_start();
if (empty($_SESSION['admin']) || !ctype_digit((string) $_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}
require_once __DIR__.'/conn.php';
$user = db_query($conn, 'SELECT * FROM admin WHERE id = ?', array($_SESSION['admin']))->fetch_assoc();
if (!$user) {
    unset($_SESSION['admin']);
    header('Location: index.php');
    exit;
}
