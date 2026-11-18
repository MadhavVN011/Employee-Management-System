<header class="main-header">
  <a href="home.php" class="logo" aria-label="EMS dashboard">
    <span class="logo-mini"><span class="brand-mark"><i class="fa fa-th-large" aria-hidden="true"></i></span></span>
    <span class="logo-lg"><span class="brand-lockup"><span class="brand-mark"><i class="fa fa-th-large" aria-hidden="true"></i></span><span class="brand-name">EMS<em>.</em><small class="brand-caption">People operations</small></span></span></span>
  </a>
  <nav class="navbar navbar-static-top" aria-label="Workspace navigation">
    <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button" aria-label="Toggle navigation"><span class="sr-only">Toggle navigation</span></a>
    <div class="workspace-context"><span>Workspace</span><i class="fa fa-angle-right" aria-hidden="true"></i><strong><?php echo h($ems_page_title ?? 'Overview'); ?></strong></div>
    <div class="navbar-custom-menu">
      <a href="../index.php" class="portal-shortcut pull-left"><i class="fa fa-clock-o" aria-hidden="true"></i> Attendance portal <i class="fa fa-arrow-up" style="transform:rotate(45deg);font-size:9px" aria-hidden="true"></i></a>
      <ul class="nav navbar-nav">
        <li class="dropdown user user-menu">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-label="Open account menu">
            <img src="<?php echo h(photo_url($user['photo'])); ?>" class="user-image" alt="">
            <span class="account-copy"><?php echo h($user['firstname']).' '.h($user['lastname']); ?><small>Administrator</small></span>
            <i class="fa fa-angle-down account-chevron" aria-hidden="true"></i>
          </a>
          <ul class="dropdown-menu">
            <li class="user-header">
              <img src="<?php echo h(photo_url($user['photo'])); ?>" class="img-circle" alt="">
              <p><?php echo h($user['firstname']).' '.h($user['lastname']); ?><small>Member since <?php echo date('M. Y', strtotime($user['created_on'])); ?></small></p>
            </li>
            <li class="user-footer">
              <div class="pull-left"><a href="#profile" data-toggle="modal" class="btn btn-default" id="admin_profile">Edit profile</a></div>
              <div class="pull-right"><a href="logout.php" class="btn btn-default">Sign out</a></div>
            </li>
          </ul>
        </li>
      </ul>
    </div>
  </nav>
</header>
<?php include 'includes/profile_modal.php'; ?>
