<?php
require_once __DIR__.'/helpers.php';
// Bind values separately so names, identifiers and dates cannot alter SQL.
function db_query($conn, $sql, $params = array()) {
    $statement = $conn->prepare($sql);
    if ($params) {
        $types = str_repeat('s', count($params));
        $statement->bind_param($types, ...$params);
    }
    $statement->execute();
    $result = $statement->field_count ? $statement->get_result() : true;
    $statement->close();
    return $result;
}
