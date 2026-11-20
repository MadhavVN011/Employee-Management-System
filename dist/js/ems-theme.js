/* Apply the saved theme before styles render, including when storage is unavailable. */
(function () {
  'use strict';
  var key = 'ems-theme';
  var system = window.matchMedia('(prefers-color-scheme: dark)');
  var preference = null;
  try { preference = window.localStorage.getItem(key); } catch (error) { /* Private browsing may block storage. */ }
  if (preference !== 'light' && preference !== 'dark') preference = null;

  function apply() {
    var dark = preference ? preference === 'dark' : system.matches;
    document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
    document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
      button.setAttribute('aria-pressed', String(dark));
      button.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
      button.setAttribute('title', dark ? 'Switch to light mode' : 'Switch to dark mode');
      button.querySelector('i').className = dark ? 'fa fa-sun-o' : 'fa fa-moon-o';
      button.querySelector('[data-theme-label]').textContent = dark ? 'Light mode' : 'Dark mode';
    });
    document.dispatchEvent(new CustomEvent('ems:themechange', { detail: { theme: dark ? 'dark' : 'light' } }));
  }

  apply();
  document.addEventListener('DOMContentLoaded', apply);
  document.addEventListener('click', function (event) {
    if (!event.target.closest('[data-theme-toggle]')) return;
    preference = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    try { window.localStorage.setItem(key, preference); } catch (error) { /* Keep the toggle usable without storage. */ }
    apply();
  });
  function followSystem() { if (!preference) apply(); }
  if (system.addEventListener) system.addEventListener('change', followSystem);
  else system.addListener(followSystem);
  window.addEventListener('storage', function (event) {
    if (event.key !== key && event.key !== null) return;
    preference = event.newValue === 'dark' || event.newValue === 'light' ? event.newValue : null;
    apply();
  });
})();
