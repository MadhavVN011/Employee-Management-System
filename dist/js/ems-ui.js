(function () {
  'use strict';
  function ready() {
    document.querySelectorAll('[data-current-date]').forEach(function (element) {
      element.textContent = new Date().toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
    });
    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
      button.addEventListener('click', function () {
        var input = document.getElementById(button.getAttribute('data-password-toggle'));
        if (!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        button.setAttribute('aria-pressed', String(show));
        button.querySelector('i').className = show ? 'fa fa-eye-slash' : 'fa fa-eye';
      });
    });
    document.querySelectorAll('[data-hide-alert]').forEach(function (button) {
      button.addEventListener('click', function () { button.closest('.alert').style.display = 'none'; });
    });
    if (window.jQuery) {
      var $ = window.jQuery;
      $('.dataTables_wrapper table').each(function () {
        if (!$(this).parent().hasClass('table-scroll')) $(this).wrap('<div class="table-scroll"></div>');
      });
      $('.dataTables_filter input').attr('placeholder', 'Search records…');
      var current = window.location.pathname.split('/').pop();
      $('.sidebar-menu a').each(function () {
        if (this.getAttribute('href') === current) {
          $(this).parent().addClass('active');
          this.setAttribute('aria-current', 'page');
        }
      });
    }
  }
  if (window.jQuery) window.jQuery(ready);
  else if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready);
  else ready();
})();
