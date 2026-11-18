<?php
function payroll_range($range) {
    if (!is_string($range)) {
        throw new InvalidArgumentException('Enter a valid date range.');
    }
    $parts = explode(' - ', $range);
    if (count($parts) !== 2) {
        throw new InvalidArgumentException('Enter a valid date range.');
    }
    $dates = array();
    foreach ($parts as $part) {
        $date = DateTime::createFromFormat('!m/d/Y', $part);
        if (!$date || $date->format('m/d/Y') !== $part) {
            throw new InvalidArgumentException('Enter a valid date range.');
        }
        $dates[] = $date->format('Y-m-d');
    }
    if ($dates[0] > $dates[1]) {
        throw new InvalidArgumentException('The start date must be before the end date.');
    }
    return $dates;
}

function payroll_rows($conn, $from, $to) {
    $deduction = (float) db_query($conn, 'SELECT COALESCE(SUM(amount), 0) AS amount FROM deductions')->fetch_assoc()['amount'];
    // Aggregate each source before joining, so multiple advances and overtime
    // records cannot multiply attendance hours. Include overtime-only employees.
    $sql = 'SELECT employees.id AS empid, employees.employee_id, employees.firstname, employees.lastname,
                   COALESCE(position.rate, 0) AS rate, COALESCE(work.total_hr, 0) AS total_hr,
                   COALESCE(extra.overtime_pay, 0) AS overtime_pay, COALESCE(advance.cashadvance, 0) AS cashadvance
            FROM employees
            LEFT JOIN position ON position.id = employees.position_id
            LEFT JOIN (SELECT employee_id, SUM(num_hr) AS total_hr FROM attendance WHERE date BETWEEN ? AND ? GROUP BY employee_id) work ON work.employee_id = employees.id
            LEFT JOIN (SELECT employee_id, SUM(hours * rate) AS overtime_pay FROM overtime WHERE date_overtime BETWEEN ? AND ? GROUP BY employee_id) extra ON extra.employee_id = employees.id
            LEFT JOIN (SELECT employee_id, SUM(amount) AS cashadvance FROM cashadvance WHERE date_advance BETWEEN ? AND ? GROUP BY employee_id) advance ON advance.employee_id = employees.id
            WHERE work.employee_id IS NOT NULL OR extra.employee_id IS NOT NULL
            ORDER BY employees.lastname, employees.firstname';
    $query = db_query($conn, $sql, array($from, $to, $from, $to, $from, $to));
    $rows = array();
    while ($row = $query->fetch_assoc()) {
        $row['gross'] = $row['rate'] * $row['total_hr'] + $row['overtime_pay'];
        $row['deduction'] = $deduction;
        $row['total_deduction'] = $deduction + $row['cashadvance'];
        $row['net'] = $row['gross'] - $row['total_deduction'];
        $rows[] = $row;
    }
    return $rows;
}
