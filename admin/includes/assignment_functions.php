<?php
function lock_assignment($conn, $table, $id) {
    if (!in_array($table, array('schedules', 'position'), true) || !is_string($id) || !ctype_digit($id)) {
        throw new InvalidArgumentException('Choose a valid position and schedule.');
    }
    // Assignment and deletion use the same parent-row lock. A schedule cannot
    // disappear between validation and saving an employee's assignment.
    if (!db_query($conn, 'SELECT id FROM '.$table.' WHERE id = ? FOR UPDATE', array($id))->num_rows) {
        throw new InvalidArgumentException('The selected position or schedule no longer exists.');
    }
}

function employee_fields($input) {
    $fields = array();
    foreach (array('firstname', 'lastname', 'address', 'birthdate', 'contact', 'gender', 'position', 'schedule') as $field) {
        if (!isset($input[$field]) || !is_string($input[$field]) || trim($input[$field]) === '') {
            throw new InvalidArgumentException('Fill up required employee details.');
        }
        $fields[$field] = $input[$field];
    }
    return $fields;
}
