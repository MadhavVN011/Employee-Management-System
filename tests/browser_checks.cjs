const assert = require('node:assert/strict');
const fs = require('node:fs');
const { chromium } = require('playwright');
const settings = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));

(async () => {
  const browser = await chromium.launch({ executablePath: settings.chrome, headless: true, args: ['--no-sandbox'] });
  try {
    const context = await browser.newContext({ colorScheme: 'light' });
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
    const screenshot = async (name) => {
      if (!process.env.EMS_SCREENSHOTS) return;
      fs.mkdirSync(process.env.EMS_SCREENSHOTS, { recursive: true });
      await page.waitForTimeout(350); // Let Bootstrap's modal transition settle for screenshots.
      await page.screenshot({ path: `${process.env.EMS_SCREENSHOTS}/${name}.png`, fullPage: true });
    };
    const theme = (target, value) => target.waitForFunction(value => document.documentElement.dataset.theme === value, value);
    await page.goto(`${base}/`);
    await theme(page, 'light');
    await screenshot('portal-light');
    await page.locator('[data-theme-toggle]').focus();
    await page.keyboard.press('Enter');
    await theme(page, 'dark');
    assert.equal(await page.locator('[data-theme-toggle]').getAttribute('aria-pressed'), 'true');
    assert.equal(await page.locator('[data-theme-toggle]').getAttribute('aria-label'), 'Switch to light mode');
    assert.equal(await page.locator('body').evaluate(el => getComputedStyle(el).backgroundColor), 'rgb(21, 29, 24)');
    await page.reload();
    await theme(page, 'dark');
    await screenshot('portal-dark');
    await page.goto(`${base}/admin/`);
    await theme(page, 'dark');
    await screenshot('login-dark');

    const device = await browser.newContext({ colorScheme: 'dark' });
    const devicePage = await device.newPage();
    await devicePage.goto(`${base}/`);
    await theme(devicePage, 'dark');
    await devicePage.emulateMedia({ colorScheme: 'light' });
    await theme(devicePage, 'light');
    await devicePage.locator('[data-theme-toggle]').click();
    await devicePage.emulateMedia({ colorScheme: 'dark' });
    await devicePage.emulateMedia({ colorScheme: 'light' });
    await theme(devicePage, 'dark');
    const otherTab = await device.newPage();
    await otherTab.goto(`${base}/admin/`);
    await theme(otherTab, 'dark');
    await otherTab.locator('[data-theme-toggle]').click();
    await theme(devicePage, 'light');
    await device.close();

    const blocked = await browser.newContext({ colorScheme: 'dark', viewport: { width: 390, height: 844 } });
    await blocked.addInitScript(() => {
      Storage.prototype.getItem = function () { throw new Error('Storage blocked'); };
      Storage.prototype.setItem = function () { throw new Error('Storage blocked'); };
    });
    const blockedPage = await blocked.newPage();
    await blockedPage.goto(`${base}/admin/`);
    await theme(blockedPage, 'dark');
    await blockedPage.locator('[data-theme-toggle]').click();
    await theme(blockedPage, 'light');
    await blocked.close();
    console.log('PASS: keyboard theme toggle, saved preference across navigation/reload, system preference, cross-tab sync, and blocked storage');

    await page.fill('[name=username]', 'admin');
    await page.fill('[name=password]', 'password');
    await Promise.all([page.waitForURL('**/home.php'), page.click('[name=login]')]);
    await page.goto(`${base}/admin/home.php?year=2018`);
    const chartText = () => page.evaluate(() => Object.values(Chart.instances).find(chart => chart.chart.canvas.id === 'barChart').scale.textColor);
    assert.equal(await chartText(), '#b4c2ad');
    await screenshot('dashboard-dark');
    await page.locator('[data-theme-toggle]').click();
    assert.equal(await chartText(), '#63725b');
    await screenshot('dashboard-light');
    await page.locator('[data-theme-toggle]').click();
    assert.equal(await chartText(), '#b4c2ad');
    console.log('PASS: chart labels redraw when switching themes');
    const marker = '<img src=x onerror="window.__auditXss=true">';
    await context.request.post(`${base}/admin/employee_edit.php`, { form: {
      edit: '', id: '1', firstname: marker, lastname: 'Smith', address: 'Test',
      birthdate: '1990-01-01', contact: '123', gender: 'Female', position: '1', schedule: '2'
    }});
    for (const name of ['home', 'employee', 'attendance', 'overtime', 'cashadvance', 'schedule', 'position', 'deduction', 'schedule_employee', 'payroll']) {
      await page.goto(`${base}/admin/${name}.php`);
      await page.waitForFunction(() => window.jQuery && jQuery.isReady && jQuery.active === 0);
      await page.waitForTimeout(100);
      await theme(page, 'dark');
      assert.equal(await page.locator('.box').first().evaluate(el => getComputedStyle(el).backgroundColor), 'rgb(32, 44, 37)', `${name}: card theme`);
      assert.equal(await page.locator('.box-header').first().evaluate(el => getComputedStyle(el).color), 'rgb(227, 235, 221)', `${name}: card heading contrast`);
      assert.equal(await page.evaluate(() => window.__auditXss === true), false, `${name}: stored markup executed`);
      assert.equal(await page.locator('#profile input[name=password]').inputValue(), '', `${name}: profile password is prefilled`);
      if (await page.locator('#example1 .edit').count()) {
        if (name === 'employee') await screenshot('employees-dark');
        await page.locator('#example1 .edit').first().click();
        await page.locator('#edit.in').waitFor();
        await page.waitForFunction(() => jQuery.active === 0);
        assert.equal(await page.locator('#edit .modal-content').evaluate(el => getComputedStyle(el).backgroundColor), 'rgb(32, 44, 37)', `${name}: modal theme`);
        if (name === 'employee') await screenshot('employee-modal-dark');
        assert.equal(await page.evaluate(() => window.__auditXss === true), false, `${name}: modal markup executed`);
      }
    }
    assert.deepEqual(errors, [], 'Admin pages have JavaScript errors');
    assert.deepEqual(missing, [], 'Local resources failed to load');
    console.log('PASS: all 10 admin pages and edit modals; no JavaScript errors, missing local assets, stored scripts, or exposed password hashes');

    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto(`${base}/admin/employee.php`);
    const toggle = await page.locator('[data-theme-toggle]').boundingBox();
    const account = await page.locator('[aria-label="Open account menu"]').boundingBox();
    assert.ok(toggle.x >= 0 && toggle.x + toggle.width <= account.x, 'Mobile theme toggle overlaps account menu');
    assert.ok(account.x + account.width <= 390, 'Mobile account menu is off-screen');
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'Mobile page overflows horizontally');
    await screenshot('employees-mobile-dark');
    await page.locator('[data-theme-toggle]').click();
    await theme(page, 'light');
    await page.locator('[data-theme-toggle]').click();
    await theme(page, 'dark');
    await page.goto(`${base}/`);
    await screenshot('portal-mobile-dark');
    await page.setViewportSize({ width: 1280, height: 900 });
    console.log('PASS: mobile theme toggle, account menu layout, and dark portal');

    await page.goto(`${base}/admin/payroll.php`);
    await page.locator('#reservation').click();
    const rangePicker = page.locator('.daterangepicker:visible');
    assert.equal(await rangePicker.locator('.calendar-table').first().evaluate(el => getComputedStyle(el).backgroundColor), 'rgb(32, 44, 37)', 'Date range picker theme');
    await screenshot('payroll-range-dark');
    await rangePicker.locator('.cancelBtn').click();
    await page.goto(`${base}/admin/attendance.php`);
    await page.click('a[href="#addnew"]');
    await page.locator('#addnew.in').waitFor();
    await page.locator('#addnew [name=time_in]').click();
    const timePicker = page.locator('.bootstrap-timepicker-widget:visible');
    await timePicker.waitFor();
    assert.equal(await timePicker.locator('a').first().evaluate(el => getComputedStyle(el).color), 'rgb(169, 204, 155)', 'Time picker arrow contrast');
    await screenshot('timepicker-dark');
    console.log('PASS: dark date range and time picker controls');

    await page.goto(`${base}/admin/overtime.php`);
    await page.click('a[href="#addnew"]');
    await page.locator('#addnew.in').waitFor();
    await page.locator('#addnew [name=employee]').fill('ABC123456789');
    await page.locator('#addnew [name=date]').click();
    assert.equal(await page.locator('.datepicker-dropdown:visible').evaluate(el => getComputedStyle(el).backgroundColor), 'rgb(32, 44, 37)', 'Date picker theme');
    await screenshot('datepicker-dark');
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
