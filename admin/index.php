<?php
session_start();
if (isset($_SESSION['admin'])) {
    header('Location: home.php');
    exit;
}
include 'includes/header.php';
?>
<body class="hold-transition login-page ems-auth">
<div class="auth-layout">
  <section class="auth-story" aria-label="EMS workspace">
    <a href="../index.php" class="brand-lockup" aria-label="EMS attendance portal"><span class="brand-mark"><i class="fa fa-th-large" aria-hidden="true"></i></span><span class="brand-name">EMS<em>.</em><small class="brand-caption">People operations</small></span></a>
    <div class="auth-story-copy"><p class="auth-eyebrow">A little more human.</p><h2>Good people.<br>Great possibilities.</h2><p>A calmer place to manage your team, their time, and the work you do together.</p></div>
    <div class="auth-art" aria-hidden="true"><span class="art-ring"></span><span class="art-leaf"></span><span class="art-dot"></span><span class="art-spark">✳</span></div>
    <div class="auth-story-footer"><span>People. Time. A better workday.</span><i class="fa fa-leaf" aria-hidden="true"></i></div>
  </section>
  <main class="auth-form-panel">
    <div class="auth-theme-control"><?php include __DIR__.'/../theme_toggle.php'; ?></div>
    <a href="../index.php" class="auth-nav">Employee portal <i class="fa fa-long-arrow-right" aria-hidden="true"></i></a>
    <a href="../index.php" class="portal-mobile-brand brand-lockup" aria-label="EMS attendance portal"><span class="brand-mark"><i class="fa fa-th-large" aria-hidden="true"></i></span><span class="brand-name">EMS<em>.</em></span></a>
    <div class="auth-card">
      <div class="page-eyebrow">YOUR WORKSPACE AWAITS</div>
      <h1>Welcome back.</h1>
      <p class="auth-intro">A new day, a little more possibility.<br>Sign in to your admin workspace.</p>
      <form action="login.php" method="POST" class="auth-form">
        <div class="form-group"><label for="login-username">Username</label><input type="text" class="form-control" id="login-username" name="username" placeholder="Enter your username" autocomplete="username" required autofocus></div>
        <div class="form-group"><label for="login-password">Password</label><div class="password-field"><input type="password" class="form-control" id="login-password" name="password" placeholder="Enter your password" autocomplete="current-password" required><button type="button" class="password-toggle" data-password-toggle="login-password" aria-label="Show password" aria-pressed="false"><i class="fa fa-eye" aria-hidden="true"></i></button></div></div>
        <button type="submit" class="btn btn-primary" name="login">Sign in to workspace <i class="fa fa-long-arrow-right" aria-hidden="true"></i></button>
      </form>
      <?php if (isset($_SESSION['error'])): ?>
        <div class="callout callout-danger" role="alert"><p><?php echo h($_SESSION['error']); ?></p></div>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>
      <div class="auth-divider"></div>
      <div class="auth-switch"><span>Here to start your workday?</span><a href="../index.php">Record attendance <i class="fa fa-long-arrow-right" aria-hidden="true"></i></a></div>
      <p class="auth-help"><i class="fa fa-lock" aria-hidden="true"></i> Your workspace is for authorized administrators.</p>
    </div>
  </main>
</div>
<?php include 'includes/scripts.php'; ?>
</body>
</html>
