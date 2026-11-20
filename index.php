<?php session_start(); ?>
<?php include 'header.php'; ?>
<body class="hold-transition login-page ems-auth">
<div class="auth-layout">
  <section class="auth-story auth-story-portal" aria-label="Your workday">
    <a href="index.php" class="brand-lockup" aria-label="EMS attendance portal"><span class="brand-mark"><i class="fa fa-th-large" aria-hidden="true"></i></span><span class="brand-name">EMS<em>.</em><small class="brand-caption">People operations</small></span></a>
    <div class="auth-story-copy"><p class="auth-eyebrow">ONE TEAM. EVERY DAY.</p><h2>Every great day<br>starts with you.</h2><p>A simple check-in. A fresh start.<br>Here's to another good day, together.</p></div>
    <div class="auth-art" aria-hidden="true"><span class="art-ring"></span><span class="art-leaf"></span><span class="art-dot"></span><span class="art-spark">✳</span></div>
    <div class="auth-clock"><div class="clock-label">YOUR WORKDAY, IN REAL TIME</div><p id="date"></p><p id="time"></p></div>
    <div class="auth-story-footer"><span>Small moments. Good work.</span><i class="fa fa-sun-o" aria-hidden="true"></i></div>
  </section>
  <main class="auth-form-panel">
    <div class="auth-theme-control"><?php include __DIR__.'/theme_toggle.php'; ?></div>
    <a href="admin/" class="auth-nav">Admin workspace <i class="fa fa-long-arrow-right" aria-hidden="true"></i></a>
    <a href="index.php" class="portal-mobile-brand brand-lockup" aria-label="EMS attendance portal"><span class="brand-mark"><i class="fa fa-th-large" aria-hidden="true"></i></span><span class="brand-name">EMS<em>.</em></span></a>
    <div class="auth-card">
      <div class="page-eyebrow">MAKE YOURSELF COUNT</div>
      <h1>Ready for your day?</h1>
      <p class="auth-intro">Your workday starts here. Enter your employee ID to record your attendance.</p>
      <form id="attendance" class="auth-form">
        <div class="form-group"><label for="employee">Enter Employee ID</label><input type="text" class="form-control" id="employee" name="employee" placeholder="e.g. ABC123456789" autocomplete="off" spellcheck="false" required></div>
        <div class="form-group"><label for="attendance-status">Attendance action</label><select class="form-control" id="attendance-status" name="status"><option value="in">Time In</option><option value="out">Time Out</option></select></div>
        <button type="submit" class="btn btn-primary" name="signin"><span>Confirm attendance</span><i class="fa fa-long-arrow-right" aria-hidden="true"></i></button>
      </form>
      <div class="alert alert-success" style="display:none" role="status" aria-live="polite"><button type="button" class="close" data-hide-alert aria-label="Dismiss message">&times;</button><span class="result"><i class="icon fa fa-check" aria-hidden="true"></i><span class="message"></span></span></div>
      <div class="alert alert-danger" style="display:none" role="alert"><button type="button" class="close" data-hide-alert aria-label="Dismiss message">&times;</button><span class="result"><i class="icon fa fa-exclamation-circle" aria-hidden="true"></i><span class="message"></span></span></div>
      <div class="auth-divider"></div>
      <div class="auth-switch"><span>Managing the team?</span><a href="admin/">Open admin workspace <i class="fa fa-long-arrow-right" aria-hidden="true"></i></a></div>
      <p class="auth-help">Need your employee ID? Your administrator can help.</p>
    </div>
  </main>
</div>
<?php include 'scripts.php'; ?>
<script>
$(function () {
  function updateClock() {
    var now = moment();
    $('#date').text(now.format('dddd, MMMM D, YYYY').toUpperCase());
    $('#time').text(now.format('h:mm:ss A'));
  }
  updateClock();
  setInterval(updateClock, 1000);
  $('#attendance').on('submit', function (event) {
    event.preventDefault();
    var button = $(this).find('button[type=submit]');
    button.prop('disabled', true).find('span').text('Recording…');
    $.ajax({
      type: 'POST', url: 'attendance.php', data: $(this).serialize(), dataType: 'json',
      success: function (response) {
        $('.alert').hide();
        $(response.error ? '.alert-danger' : '.alert-success').show();
        $('.message').text(response.message);
        if (!response.error) $('#employee').val('');
      },
      error: function () {
        $('.alert').hide();
        $('.alert-danger').show().find('.message').text('Unable to record attendance. Please try again.');
      },
      complete: function () { button.prop('disabled', false).find('span').text('Confirm attendance'); }
    });
  });
});
</script>
</body>
</html>
