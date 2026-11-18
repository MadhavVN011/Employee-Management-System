<?php
require_once __DIR__.'/database.php';

// Defaults preserve the existing local PHP/MySQL setup. Compose supplies its
// database service and a dedicated application account through the environment.
$conn = new mysqli(
    getenv('DB_HOST') ?: 'localhost',
    getenv('DB_USER') ?: 'root',
    getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '',
    getenv('DB_NAME') ?: 'company_ems',
    (int) (getenv('DB_PORT') ?: 3306)
);
$conn->set_charset('utf8mb4');
