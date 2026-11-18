<?php
require_once __DIR__.'/../../attendance_functions.php';
function overtime_values($input) {
    $values = array();
    foreach (array('hours', 'mins', 'rate') as $field) {
        $value = $input[$field] ?? '';
        if (!is_string($value)) {
            throw new InvalidArgumentException('Enter valid overtime hours, minutes, and rate.');
        }
        $value = trim($value);
        if ($value === '' && $field !== 'rate') {
            $value = '0';
        }
        if (!is_numeric($value) || !is_finite((float) $value) || (float) $value < 0) {
            throw new InvalidArgumentException('Overtime hours, minutes, and rate must be non-negative numbers.');
        }
        $values[$field] = (float) $value;
    }
    if ($values['mins'] >= 60 || floor($values['mins']) !== $values['mins']) {
        throw new InvalidArgumentException('Overtime minutes must be a whole number from 0 to 59.');
    }
    $hours = $values['hours'] + $values['mins'] / 60;
    if (!is_finite($hours) || $hours <= 0) {
        throw new InvalidArgumentException('Enter an overtime duration greater than zero.');
    }
    return array(attendance_date($input['date'] ?? ''), $hours, $values['rate']);
}
