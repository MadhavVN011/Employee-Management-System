<?php
// Attendance is recorded per calendar day. Pay only the overlap with the shift,
// retaining the existing one-hour break when paid time exceeds four hours.
function attendance_hours($time_in, $time_out, $shift_in, $shift_out) {
    $start = max(strtotime($time_in), strtotime($shift_in));
    $end = min(strtotime($time_out), strtotime($shift_out));
    $hours = max(0, floor(($end - $start) / 60)) / 60;
    return $hours > 4 ? $hours - 1 : $hours;
}
function attendance_time($value) {
    $timestamp = is_string($value) && trim($value) !== '' ? strtotime($value) : false;
    if ($timestamp === false) {
        throw new InvalidArgumentException('Enter a valid time.');
    }
    return date('H:i:s', $timestamp);
}
function attendance_date($value) {
    $date = is_string($value) ? DateTime::createFromFormat('!Y-m-d', $value) : false;
    if (!$date || $date->format('Y-m-d') !== $value) {
        throw new InvalidArgumentException('Enter a valid attendance date.');
    }
    return $value;
}
