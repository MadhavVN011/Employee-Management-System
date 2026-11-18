<?php
require_once __DIR__.'/../attendance_functions.php';
$cases = array(
    array('08:00:00', '17:00:00', '08:00:00', '17:00:00', 8),
    array('07:00:00', '19:00:00', '08:00:00', '17:00:00', 8),
    array('09:30:00', '16:00:00', '08:00:00', '17:00:00', 5.5),
    array('07:00:00', '07:30:00', '08:00:00', '17:00:00', 0),
    array('18:00:00', '19:00:00', '08:00:00', '17:00:00', 0),
    array('08:00:00', '12:00:00', '08:00:00', '17:00:00', 4),
    array('17:00:00', '08:00:00', '08:00:00', '17:00:00', 0),
    array('08:00:00', '08:00:30', '08:00:00', '17:00:00', 0),
);
foreach ($cases as $case) {
    $expected = array_pop($case);
    $actual = attendance_hours(...$case);
    if (abs($actual - $expected) > 0.00001) {
        throw new RuntimeException('Incorrect paid hours: '.json_encode($case));
    }
}
foreach (array('', '2026-02-30', 'invalid') as $date) {
    try {
        attendance_date($date);
        throw new RuntimeException('Invalid date accepted');
    } catch (InvalidArgumentException $error) {
        // Expected.
    }
}
if (attendance_date('2026-01-01') !== '2026-01-01' || attendance_time('05:30 PM') !== '17:30:00') {
    throw new RuntimeException('Valid attendance input rejected');
}
echo "Attendance calculations and validation passed.\n";
