const assert = require('node:assert/strict');
const fs = require('node:fs');
const { chromium } = require('playwright');
const settings = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));

(async () => {
  const browser = await chromium.launch({ executablePath: settings.chrome, headless: true, args: ['--no-sandbox'] });
  try {
    const context = await browser.newContext();
    const page = await context.newPage();
    const base = `http://127.0.0.1:${settings.port}`;
    const errors = [];
    const missing = [];
    // External fonts are cosmetic; keep these checks independent of Google.
    await page.route('**/*', route => new URL(route.request().url()).origin === base ? route.continue() : route.abort());
    page.on('pageerror', error => errors.push(error.message));
    page.on('response', response => {
      if (response.url().startsWith(base) && response.status() >= 400) {
        missing.push(`${response.status()} ${response.url()}`);
      }
    });
    await page.goto(`${base}/admin/`);
    await page.fill('[name=username]', 'admin');
    await page.fill('[name=password]', 'password');
    await Promise.all([page.waitForURL('**/home.php'), page.click('[name=login]')]);
    const marker = '<img src=x onerror="window.__auditXss=true">';
    await context.request.post(`${base}/admin/employee_edit.php`, { form: {
      edit: '', id: '1', firstname: marker, lastname: 'Smith', address: 'Test',
      birthdate: '1990-01-01', contact: '123', gender: 'Female', position: '1', schedule: '2'
    }});
    for (const name of ['home', 'employee', 'attendance', 'overtime', 'cashadvance', 'schedule', 'position', 'deduction', 'schedule_employee', 'payroll']) {
      await page.goto(`${base}/admin/${name}.php`);
      await page.waitForFunction(() => window.jQuery && jQuery.isReady && jQuery.active === 0);
      await page.waitForTimeout(100);
      assert.equal(await page.evaluate(() => window.__auditXss === true), false, `${name}: stored markup executed`);
      assert.equal(await page.locator('#profile input[name=password]').inputValue(), '', `${name}: profile password is prefilled`);
      if (await page.locator('#example1 .edit').count()) {
        await page.locator('#example1 .edit').first().click();
        await page.locator('#edit.in').waitFor();
        await page.waitForFunction(() => jQuery.active === 0);
        assert.equal(await page.evaluate(() => window.__auditXss === true), false, `${name}: modal markup executed`);
      }
    }
    assert.deepEqual(errors, [], 'Admin pages have JavaScript errors');
    assert.deepEqual(missing, [], 'Local resources failed to load');
    console.log('PASS: all 10 admin pages and edit modals; no JavaScript errors, missing local assets, stored scripts, or exposed password hashes');

    await page.goto(`${base}/admin/overtime.php`);
    await page.click('a[href="#addnew"]');
    await page.locator('#addnew.in').waitFor();
    await page.locator('#addnew [name=employee]').fill('ABC123456789');
    await page.locator('#addnew [name=date]').click();
    await page.locator('.datepicker-dropdown:visible .datepicker-days td.day:not(.old):not(.new)').first().click();
    await page.locator('#addnew [name=hours]').fill('2');
    await page.locator('#addnew [name=rate]').fill('100');
    await Promise.all([page.waitForURL('**/overtime.php'), page.locator('#addnew [name=add]').click()]);
    try {
      await page.locator('.alert-success').waitFor({ timeout: 5000 });
    } catch (error) {
      console.error('Overtime submission state:', await page.evaluate(() => ({
        url: location.href,
        alerts: [...document.querySelectorAll('.alert')].map(x => x.textContent),
        fields: [...document.querySelectorAll('#addnew input')].map(x => ({name: x.name, value: x.value, validation: x.validationMessage})),
        buttonForm: document.querySelector('#addnew [name=add]')?.form?.action
      })));
      throw error;
    }
    assert.match(await page.locator('.alert-success').innerText(), /Overtime added successfully/);
    console.log('PASS: overtime form accepts hours with blank minutes');

    const png = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a2ioAAAAASUVORK5CYII=', 'base64');
    const lookup = () => context.request.post(`${base}/admin/employee_row.php`, { form: { id: '1' }}).then(r => r.json());
    const before = (await lookup()).photo;
    await context.request.post(`${base}/admin/employee_edit_photo.php`, { multipart: {
      id: '1', upload: '', photo: { name: 'audit.php', mimeType: 'image/png', buffer: Buffer.from('<?php echo "bad"; ?>') }
    }});
    assert.equal((await lookup()).photo, before, 'Executable upload accepted');
    await context.request.post(`${base}/admin/employee_edit_photo.php`, { multipart: {
      id: '1', upload: '', photo: { name: 'avatar.png', mimeType: 'image/png', buffer: png }
    }});
    const photo = (await lookup()).photo;
    assert.match(photo, /^[a-f0-9]{32}\.png$/);
    assert.deepEqual(await (await context.request.get(`${base}/images/${photo}`)).body(), png);
    assert.equal((await context.request.get(`${base}/images/legacy-audit.php`)).status(), 403, 'Apache executed an old uploaded PHP file');
    console.log('PASS: Apache multipart uploads and executable-file denial');

    await context.request.post(`${base}/admin/profile_update.php?return=employee.php`, { form: {
      save: '', curr_password: 'password', username: 'admin', password: '', firstname: marker, lastname: 'Den'
    }});
    await page.goto(`${base}/admin/home.php`);
    assert.equal(await page.evaluate(() => window.__auditXss === true), false, 'Profile name executed JavaScript');
    assert.equal(await page.locator('#profile [name=firstname]').inputValue(), marker, 'Profile text did not round-trip safely');
    await page.goto(`${base}/admin/logout.php`);
    await page.fill('[name=username]', 'admin');
    await page.fill('[name=password]', 'password');
    await Promise.all([page.waitForURL('**/home.php'), page.click('[name=login]')]);
    assert.deepEqual(errors, [], 'Browser interactions caused JavaScript errors');
    console.log('PASS: escaped profile fields and unchanged password after blank update');
  } finally {
    await browser.close();
  }
})().catch(error => { console.error(error); process.exit(1); });
