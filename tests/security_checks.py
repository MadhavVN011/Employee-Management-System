"""Regression cases for uploads, stored markup, profile updates and validation."""
import base64
import json
import re
import urllib.parse


def run_security_checks(request, sql, php_code, check, auth, anon, person):
    marker = '<img src=x onerror="window.__auditXss=true">'
    request('/admin/employee_edit.php', dict(person, firstname=marker))
    for page in ['employee', 'attendance', 'overtime', 'cashadvance', 'schedule_employee']:
        _, body = request('/admin/' + page + '.php')
        check(marker.encode() not in body and b'&lt;img' in body, page + ' renders employee markup')
    _, body = request('/admin/employee_row.php', {'id': '1'})
    check(json.loads(body)['firstname'] == marker, 'Modal lookup should return the original text')
    for page in ['employee', 'attendance', 'overtime', 'cashadvance', 'schedule', 'position', 'deduction', 'schedule_employee', 'payroll']:
        _, body = request('/admin/' + page + '.php')
        check(b'dist/js/pages/dashboard.js' not in body and b'.html(response' not in body, page + ' has an unsafe modal or demo script')
    request('/admin/employee_edit.php', person)

    # Blank profile passwords preserve the current hash; only an explicit new
    # password changes it, and no stored hash is rendered in the browser.
    original_hash = sql('SELECT password FROM admin WHERE id=1;')
    profile = {'save': '', 'curr_password': 'password', 'username': 'admin', 'password': '', 'firstname': 'Harry', 'lastname': 'Den'}
    request('/admin/profile_update.php?return=employee.php', profile)
    check(sql('SELECT password FROM admin WHERE id=1;') == original_hash, 'Blank profile password changed the password')
    request('/admin/profile_update.php', dict(profile, password='invalid\0password'))
    check(sql('SELECT password FROM admin WHERE id=1;') == original_hash, 'Invalid password changed the password')
    _, body = request('/admin/home.php')
    check(original_hash.encode() not in body and re.search(rb'id="password"[^>]*value=', body) is None, 'Password hash exposed in profile HTML')
    for target in ['https://example.com/', '//example.com', 'home.php?next=https://example.com', 'javascript:alert(1)', 'home.php\r\nX-Audit: injected']:
        response, _ = request('/admin/profile_update.php?return=' + urllib.parse.quote(target, safe=''))
        check(response.headers.get('Location') == 'home.php', 'External or malformed return URL accepted')
    response, _ = request('/admin/profile_update.php?return=employee.php')
    check(response.headers.get('Location') == 'employee.php', 'Valid local return URL rejected')
    request('/admin/profile_update.php', dict(profile, curr_password='wrong', firstname='Changed'))
    check(sql('SELECT firstname FROM admin WHERE id=1;') == 'Harry', 'Incorrect current password allowed changes')
    request('/admin/profile_update.php', dict(profile, password='new-test-password'))
    check(php_code("require '/app/conn.php'; echo password_verify('new-test-password', $conn->query('SELECT password FROM admin WHERE id=1')->fetch_assoc()['password']) ? 'yes' : 'no';") == 'yes', 'Explicit password change failed')
    request('/admin/profile_update.php', dict(profile, curr_password='new-test-password', password='password'))

    photo = sql('SELECT photo FROM employees WHERE id=1;')
    request('/admin/employee_edit_photo.php', {'upload': '', 'id': '1'})
    check(sql('SELECT photo FROM employees WHERE id=1;') == photo, 'Empty upload erased the existing photo')
    png = base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a2ioAAAAASUVORK5CYII=')
    for name, data, mime in [('audit.php', b'<?php echo "bad"; ?>', 'image/png'), ('audit.png', b'<?php echo "bad"; ?>', 'image/png'), ('audit.svg', b'<svg onload="alert(1)"/>', 'image/svg+xml'), ('audit.jpg', png, 'image/jpeg')]:
        request('/admin/employee_edit_photo.php', {'upload': '', 'id': '1'}, files={'photo': (name, data, mime)})
        check(sql('SELECT photo FROM employees WHERE id=1;') == photo, 'Invalid image accepted: ' + name)
        response, _ = request('/images/' + name, opener=anon)
        check(response.code == 404, 'Rejected upload was saved: ' + name)
    count = sql('SELECT COUNT(*) FROM employees;')
    bad_photo = {'photo': ('bad.php', b'<?php echo "bad"; ?>', 'image/png')}
    request('/admin/employee_add.php', dict(person, add=''), files=bad_photo)
    check(sql('SELECT COUNT(*) FROM employees;') == count, 'Employee creation accepted executable photo')
    old_admin_photo = sql('SELECT photo FROM admin WHERE id=1;')
    request('/admin/profile_update.php', profile, files=bad_photo)
    check(sql('SELECT photo FROM admin WHERE id=1;') == old_admin_photo, 'Profile accepted executable photo')
    request('/admin/employee_edit_photo.php', {'upload': '', 'id': '1'}, files={'photo': ('../../desktop.png', png, 'image/png')})
    first = sql('SELECT photo FROM employees WHERE id=1;')
    check(re.fullmatch(r'[a-f0-9]{32}\.png', first) is not None, 'Photo filename was not safely generated')
    response, body = request('/images/' + first, opener=anon)
    check(response.code == 200 and body == png, 'Valid uploaded image is not available')
    request('/admin/employee_edit_photo.php', {'upload': '', 'id': '1'}, files={'photo': ('../../desktop.png', png, 'image/png')})
    check(sql('SELECT photo FROM employees WHERE id=1;') != first, 'Two photo uploads reused a filename')
    maximum_image = png + b'\0' * (5 * 1024 * 1024 - len(png))
    request('/admin/employee_edit_photo.php', {'upload': '', 'id': '1'}, files={'photo': ('maximum.png', maximum_image, 'image/png')})
    maximum_name = sql('SELECT photo FROM employees WHERE id=1;')
    response, body = request('/images/' + maximum_name, opener=anon)
    check(body == maximum_image, 'Valid 5 MB image was rejected')
    request('/admin/employee_edit_photo.php', {'upload': '', 'id': '1'}, files={'photo': ('too-large.png', maximum_image + b'0', 'image/png')})
    check(sql('SELECT photo FROM employees WHERE id=1;') == maximum_name, 'Image larger than 5 MB was accepted')
    request('/admin/profile_update.php', profile, files={'photo': ('profile.png', png, 'image/png')})
    check(re.fullmatch(r'[a-f0-9]{32}\.png', sql('SELECT photo FROM admin WHERE id=1;')) is not None, 'Valid profile photo failed')
    request('/admin/employee_add.php', dict(person, add='', firstname='Photo test'), files={'photo': ('employee.png', png, 'image/png')})
    check(re.fullmatch(r'[a-f0-9]{32}\.png', sql("SELECT photo FROM employees WHERE firstname='Photo test';")) is not None, 'Valid new employee photo failed')
    print('PASS: stored markup, profile passwords, redirects and real multipart uploads', flush=True)

    for kind, identifier, table in [('schedule', '2', 'schedules'), ('position', '1', 'position')]:
        request('/admin/' + kind + '_delete.php', {'delete': '', 'id': identifier})
        check(sql('SELECT COUNT(*) FROM `' + table + '` WHERE id=' + identifier + ';') == '1', 'Assigned ' + kind + ' was deleted')
    request('/admin/schedule_add.php', {'add': '', 'time_in': '08:00 AM', 'time_out': '05:00 PM'})
    identifier = sql('SELECT MAX(id) FROM schedules;')
    request('/admin/schedule_delete.php', {'delete': '', 'id': identifier})
    check(sql('SELECT COUNT(*) FROM schedules WHERE id=' + identifier + ';') == '0', 'Unused schedule could not be deleted')
    request('/admin/schedule_employee_edit.php', {'edit': '', 'id': '1', 'schedule': identifier})
    check(sql('SELECT schedule_id FROM employees WHERE id=1;') == '2', 'Deleted schedule could be assigned')
    request('/admin/employee_edit.php', dict(person, schedule=identifier))
    check(sql('SELECT schedule_id FROM employees WHERE id=1;') == '2', 'Employee edit accepted a deleted schedule')
    count = sql('SELECT COUNT(*) FROM employees;')
    request('/admin/employee_add.php', dict(person, add='', schedule=identifier))
    check(sql('SELECT COUNT(*) FROM employees;') == count, 'Employee created with a missing schedule')

    overtime = {'add': '', 'employee': 'ABC123456789', 'date': '2026-02-01', 'hours': '2', 'mins': '', 'rate': '100'}
    request('/admin/overtime_add.php', overtime)
    identifier = sql('SELECT MAX(id) FROM overtime;')
    check(sql('SELECT hours FROM overtime WHERE id=' + identifier + ';') == '2', 'Blank minutes were not treated as zero')
    request('/admin/overtime_edit.php', dict(overtime, edit='', id=identifier, hours='', mins='30'))
    check(sql('SELECT hours FROM overtime WHERE id=' + identifier + ';') == '0.5', 'Blank hours were not treated as zero')
    count = sql('SELECT COUNT(*) FROM overtime;')
    for fields in [dict(hours='-2'), dict(mins='-1'), dict(mins='60'), dict(mins='1.5'), dict(rate='-100'), dict(rate='text'), dict(hours='', mins=''), dict(hours='1e999'), dict(date='2026-02-30')]:
        request('/admin/overtime_add.php', dict(overtime, **fields))
        check(sql('SELECT COUNT(*) FROM overtime;') == count, 'Invalid overtime created: ' + str(fields))
        request('/admin/overtime_edit.php', dict(overtime, **fields, edit='', id=identifier))
        check(sql('SELECT hours FROM overtime WHERE id=' + identifier + ';') == '0.5', 'Invalid overtime edit changed data: ' + str(fields))
    print('PASS: protected assignments, safe deletion and overtime edge cases', flush=True)
