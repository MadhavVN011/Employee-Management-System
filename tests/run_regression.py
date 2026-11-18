#!/usr/bin/env python3
"""Run regression checks in disposable PHP/MariaDB containers (requires Docker)."""
import http.cookiejar
import json
import math
import pathlib
import re
import shutil
import subprocess
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request
import uuid
from security_checks import run_security_checks

ROOT = pathlib.Path(__file__).resolve().parents[1]


def command(args, **kwargs):
    return subprocess.run(args, check=True, capture_output=True, text=True, **kwargs).stdout.strip()


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args):
        return None


def run_checks(context):
    db, php = context['db'], context['php']
    port = command(['docker', 'port', php, '8080/tcp']).split(':')[-1]
    base = 'http://127.0.0.1:' + port
    anon = urllib.request.build_opener(NoRedirect())
    auth = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()), NoRedirect())
    checks = 0

    def check(condition, message):
        nonlocal checks
        if not condition:
            raise AssertionError(message)
        checks += 1

    def sql(statement):
        return command(['docker', 'exec', '-i', db, 'mariadb', '-uroot', '-N', 'company_ems'], input=statement)

    def php_code(code):
        return command(['docker', 'exec', php, 'php', '-d', 'mysqli.default_socket=/run/mysqld/mysqld.sock', '-r', code])

    def request(path, data=None, opener=auth, files=None):
        headers = {}
        if files:
            boundary = 'ems-' + uuid.uuid4().hex
            parts = []
            for key, value in (data or {}).items():
                parts.append(('--' + boundary + '\r\nContent-Disposition: form-data; name="' + key + '"\r\n\r\n' + str(value) + '\r\n').encode())
            for key, (name, content, mime) in files.items():
                parts.append(('--' + boundary + '\r\nContent-Disposition: form-data; name="' + key + '"; filename="' + name + '"\r\nContent-Type: ' + mime + '\r\n\r\n').encode() + content + b'\r\n')
            data = b''.join(parts) + ('--' + boundary + '--\r\n').encode()
            headers['Content-Type'] = 'multipart/form-data; boundary=' + boundary
        else:
            data = urllib.parse.urlencode(data).encode() if data is not None else None
        req = urllib.request.Request(base + path, data=data, headers=headers)
        try:
            response = opener.open(req, timeout=30)
        except urllib.error.HTTPError as error:
            response = error
        body = response.read()
        check(not re.search(rb'(?:Fatal error|Warning|Deprecated|Parse error)(?:</b>)?:', body), path + ': PHP error: ' + body[:1500].decode(errors='replace'))
        return response, body

    # This database belongs only to the containers created by this test runner.
    check(db.startswith(('ems-regression-', 'ems-fix-')), 'Refusing to reset a non-test database')
    sql('DROP DATABASE company_ems; CREATE DATABASE company_ems;')
    sql((ROOT / 'database/company_ems.sql').read_text())
    sql("SET GLOBAL sql_mode = 'STRICT_TRANS_TABLES,ONLY_FULL_GROUP_BY,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';")
    command(['docker', 'exec', php, 'php', '/app/tests/attendance_test.php'])
    print('PASS: attendance calculation and validation cases', flush=True)

    # Every protected controller must stop at its authentication guard.
    for path in sorted((ROOT / 'admin').glob('*.php')):
        if not re.search(r"(?:include|require).*includes/session\.php", path.read_text()):
            continue
        response, body = request('/admin/' + path.name, {'id': '1', 'delete': '', 'add': '', 'edit': ''}, anon)
        check(response.code == 302 and response.headers.get('Location') == 'index.php' and body == b'', 'Unauthenticated execution: ' + path.name)
    check(sql('SELECT COUNT(*) FROM employees;') == '22', 'Anonymous request changed employees')
    check(sql('SELECT COUNT(*) FROM `position`;') == '4', 'Anonymous request changed positions')
    print('PASS: all protected admin controllers reject anonymous requests', flush=True)

    request('/admin/login.php', {'login': '', 'username': "admin' OR '1'='1", 'password': 'password'})
    response, _ = request('/admin/home.php')
    check(response.code == 302, 'SQL injection authenticated a user')
    request('/admin/login.php', {'login': '', 'username': 'admin', 'password': 'password'})
    for page in ['home', 'employee', 'attendance', 'overtime', 'cashadvance', 'schedule', 'position', 'deduction', 'schedule_employee', 'payroll']:
        response, body = request('/admin/' + page + '.php')
        check(response.code == 200 and body, page + ' failed to load')

    # Apostrophes and SQL-like input must be saved literally.
    person = {'edit': '', 'id': '1', 'firstname': 'Christine', 'lastname': "O'Neil", 'address': "O'Connell Street", 'birthdate': '1990-01-01', 'contact': '1234', 'gender': 'Female', 'position': '1', 'schedule': '2'}
    request('/admin/employee_edit.php', person)
    check(sql('SELECT lastname FROM employees WHERE id=1;') == "O'Neil", 'Apostrophe was not saved')
    payload = "literal', position_id='4"
    request('/admin/employee_edit.php', dict(person, lastname=payload))
    check(sql('SELECT lastname FROM employees WHERE id=1;') == payload, 'SQL payload was not saved literally')
    check(sql('SELECT position_id FROM employees WHERE id=1;') == '1', 'SQL injection changed another column')
    request('/admin/employee_edit.php', person)
    request('/admin/employee_add.php', dict(person, add='', firstname='New', lastname="D'Angelo"))
    check(sql("SELECT COUNT(*) FROM employees WHERE firstname='New';") == '1', 'Employee creation failed')
    for name, fields in [('position', {'title': "Manager's role", 'rate': '100'}), ('deduction', {'description': "Worker's deduction", 'amount': '10'}), ('schedule', {'time_in': '08:00 AM', 'time_out': '05:00 PM'}), ('cashadvance', {'employee': 'ABC123456789', 'amount': '100'}), ('overtime', {'employee': 'ABC123456789', 'date': '2026-02-01', 'hours': '2', 'mins': '0', 'rate': '100'})]:
        response, _ = request('/admin/' + name + '_add.php', dict(fields, add=''))
        check(response.code == 302, name + ' creation failed')
    for name, identifier in [('employee', 1), ('attendance', 13), ('position', 1), ('schedule', 1), ('deduction', 1), ('cashadvance', 2), ('overtime', 4), ('schedule_employee', 1)]:
        _, body = request('/admin/' + name + '_row.php', {'id': identifier})
        check(isinstance(json.loads(body), dict), name + ' lookup failed')
    _, body = request('/attendance.php', {'employee': "ABC123456789' OR '1'='1", 'status': 'in'}, anon)
    check(json.loads(body)['error'], 'Attendance accepted an injected employee ID')
    print('PASS: login, admin pages, CRUD, lookups, apostrophes and injection checks', flush=True)

    run_security_checks(request, sql, php_code, check, auth, anon, person)

    # Public attendance and QR flows under strict database settings.
    sql('DELETE FROM attendance;')
    for data, expect_error in [({'employee': 'ABC123456789', 'status': 'out'}, True), ({'employee': 'ABC123456789', 'status': 'in'}, False), ({'employee': 'ABC123456789', 'status': 'in'}, True), ({'employee': 'ABC123456789', 'status': 'out'}, False), ({'employee': 'ABC123456789', 'status': 'out'}, True), ({'employee': 'ABC123456789', 'status': 'invalid'}, True)]:
        _, body = request('/attendance.php', data, anon)
        check(json.loads(body)['error'] == expect_error, 'Wrong attendance response: ' + body.decode())
    sql('DELETE FROM attendance;')
    for message in ['Time in:', 'Time out:', 'timed out for today']:
        _, body = request('/attendance.php?employee=ABC123456789', opener=anon)
        check(message.encode() in body, 'QR flow failed: ' + body.decode())
    check(sql('SELECT COUNT(*) FROM attendance;') == '1', 'Duplicate daily attendance created')
    sql("UPDATE schedules SET time_in='00:00:00', time_out='01:00:00' WHERE id=2; UPDATE attendance SET time_in='00:00:00',time_out='00:00:00',num_hr=0;")
    request('/attendance.php', {'employee': 'ABC123456789', 'status': 'out'}, anon)
    time_out, hours = sql('SELECT time_out,num_hr FROM attendance WHERE employee_id=1;').split('\t')
    h, m, s = map(int, time_out.split(':'))
    expected = min(3600, h * 3600 + m * 60 + s) // 60 / 60
    check(math.isclose(float(hours), expected), 'Public check-out exceeded scheduled paid hours')
    sql("UPDATE schedules SET time_in='08:00:00',time_out='17:00:00' WHERE id=2;")
    data = {'add': '', 'employee': 'ABC123456789', 'date': '2026-01-01', 'time_in': '07:00 AM', 'time_out': '07:00 PM'}
    request('/admin/attendance_add.php', data)
    check(sql("SELECT num_hr FROM attendance WHERE date='2026-01-01';") == '8', 'Admin attendance did not cap shift hours')
    request('/admin/attendance_add.php', data)
    check(sql("SELECT COUNT(*) FROM attendance WHERE date='2026-01-01';") == '1', 'Admin added duplicate attendance')
    identifier = sql("SELECT id FROM attendance WHERE date='2026-01-01';")
    request('/admin/attendance_edit.php', {'edit': '', 'id': identifier, 'edit_date': '2026-01-01', 'edit_time_in': '09:30 AM', 'edit_time_out': '04:00 PM'})
    check(sql('SELECT num_hr,status FROM attendance WHERE id=' + identifier + ';') == '5.5\t0', 'Edited hours/status incorrect')
    request('/admin/attendance_edit.php', {'edit': '', 'id': identifier, 'edit_date': '2026-01-01', 'edit_time_in': '05:00 PM', 'edit_time_out': '08:00 AM'})
    check(sql('SELECT num_hr FROM attendance WHERE id=' + identifier + ';') == '5.5', 'Invalid edit changed attendance')
    print('PASS: public, QR, duplicate, admin and shift-boundary attendance flows', flush=True)

    # Deterministic pay data proves overtime inclusion and avoids join fan-out.
    sql("DELETE FROM attendance; DELETE FROM overtime; DELETE FROM cashadvance; DELETE FROM deductions; INSERT INTO deductions (description,amount) VALUES ('Test',10); INSERT INTO attendance (employee_id,date,time_in,status,time_out,num_hr) VALUES (1,'2026-02-01','08:00:00',1,'17:00:00',8),(1,'2026-02-02','08:00:00',1,'17:00:00',8); INSERT INTO overtime (employee_id,hours,rate,date_overtime) VALUES (1,2,150,'2026-02-01'),(1,1,200,'2026-02-02'),(3,1,50,'2026-02-01'),(1,100,100,'2025-01-01'); INSERT INTO cashadvance (employee_id,date_advance,amount) VALUES (1,'2026-02-01',100),(1,'2026-02-02',50),(1,'2025-01-01',9999);")
    code = "require '/app/conn.php'; require '/app/admin/includes/payroll_functions.php'; echo json_encode(payroll_rows($conn,'2026-02-01','2026-02-28'));"
    rows = {int(row['empid']): row for row in json.loads(php_code(code))}
    check(len(rows) == 2, 'Overtime-only employee missing')
    row = rows[1]
    check(float(row['total_hr']) == 16 and float(row['overtime_pay']) == 500 and float(row['cashadvance']) == 150, 'Incorrect aggregation/date range')
    check(float(row['gross']) == 2100 and float(row['net']) == 1940, 'Incorrect payroll calculation')
    check(float(rows[3]['net']) == 40, 'Incorrect overtime-only pay')
    _, body = request('/admin/payroll.php?range=02/01/2026%20-%2002/28/2026')
    check(b'2,100.00' in body and b'1,940.00' in body, 'Payroll page disagrees with calculation')
    for endpoint in ['payroll_generate', 'payslip_generate', 'schedule_print']:
        response, body = request('/admin/' + endpoint + '.php', {'date_range': '02/01/2026 - 02/28/2026'})
        check(response.headers.get_content_type() == 'application/pdf' and body.startswith(b'%PDF-') and b'%%EOF' in body[-100:], endpoint + ' did not produce a complete PDF')
        if shutil.which('pdftotext') and endpoint != 'schedule_print':
            text = subprocess.run(['pdftotext', '-', '-'], input=body, capture_output=True, check=True).stdout
            check(b'1,940.00' in text, endpoint + ' has incorrect net pay')
            if endpoint == 'payslip_generate':
                check(b'500.00' in text, 'Payslip omits overtime pay')
    response, _ = request('/admin/payroll_generate.php', {'date_range': 'invalid'})
    check(response.code == 400, 'Invalid report range accepted')
    print('PASS: overtime, overtime-only payroll, advances, date ranges and all PDF exports', flush=True)

    sql('DELETE FROM attendance;')
    response, body = request('/admin/home.php')
    check(response.code == 200 and b'0.00' in body, 'Empty dashboard failed')
    command(['docker', 'exec', php, 'php', '-r', "session_id('emsinvalidsession');session_start();$_SESSION['admin']=999999;session_write_close();"])
    stale = urllib.request.build_opener(NoRedirect())
    stale.addheaders = [('Cookie', 'PHPSESSID=emsinvalidsession')]
    response, body = request('/admin/employee_row.php', {'id': '1'}, stale)
    check(response.code == 302 and body == b'', 'Invalid admin session accepted')
    print(f'PASS: {checks} HTTP/database assertions; no PHP warnings or errors', flush=True)


def main():
    directory = pathlib.Path(tempfile.mkdtemp(prefix='ems-regression-'))
    context = {'db': directory.name + '-db', 'php': directory.name + '-php'}
    socket = directory / 'socket'
    socket.mkdir()
    socket.chmod(0o777)
    shutil.copytree(ROOT / 'images', directory / 'images')
    try:
        for image in ['php:8.2-cli', 'mariadb:10.11']:
            if subprocess.run(['docker', 'image', 'inspect', image], capture_output=True).returncode:
                command(['docker', 'pull', image])
        command(['docker', 'run', '-d', '--name', context['db'], '--tmpfs', '/var/lib/mysql', '-v', str(socket) + ':/run/mysqld', '-e', 'MARIADB_ALLOW_EMPTY_ROOT_PASSWORD=1', '-e', 'MARIADB_DATABASE=company_ems', 'mariadb:10.11'])
        command(['docker', 'run', '-d', '--name', context['php'], '-p', '127.0.0.1::8080', '-v', str(ROOT) + ':/app:ro', '-v', str(directory / 'images') + ':/app/images', '-v', str(socket) + ':/run/mysqld', '-w', '/app', 'php:8.2-cli', 'sh', '-c', 'docker-php-ext-install mysqli >/tmp/extension-build.log 2>&1 && php -d mysqli.default_socket=/run/mysqld/mysqld.sock -d display_errors=1 -d error_reporting=32767 -d upload_max_filesize=5M -d post_max_size=6M -S 0.0.0.0:8080 -t /app'])
        port = command(['docker', 'port', context['php'], '8080/tcp']).split(':')[-1]
        for attempt in range(180):
            try:
                urllib.request.urlopen('http://127.0.0.1:' + port, timeout=1).close()
                command(['docker', 'exec', context['db'], 'mariadb', '-uroot', 'company_ems', '-e', 'SELECT 1'])
                break
            except (OSError, subprocess.CalledProcessError):
                time.sleep(0.5)
        else:
            raise RuntimeError('Test containers did not become ready')
        lint = "find /app -name '*.php' -print0 | xargs -0 -n1 php -l"
        output = command(['docker', 'exec', context['php'], 'sh', '-c', lint])
        if 'Errors parsing' in output:
            raise RuntimeError(output)
        print('PASS: PHP lint for all source and bundled library files', flush=True)
        run_checks(context)
    finally:
        subprocess.run(['docker', 'rm', '-f', '-v', context['php'], context['db']], capture_output=True)
        shutil.rmtree(directory)


if __name__ == '__main__':
    main()
