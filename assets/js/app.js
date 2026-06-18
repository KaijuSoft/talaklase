// TalaKlase — Main JS

(function () {
  const sidebar  = document.getElementById('sidebar');
  const content  = document.getElementById('page-content');
  const overlay  = document.getElementById('sidebar-overlay');
  const toggle   = document.getElementById('sidebarToggle');
<<<<<<< HEAD
  const themeToggle = document.getElementById('themeToggle');
  const MOBILE_BP = 900;

  function setTheme(theme) {
    const nextTheme = theme === 'dark' ? 'dark' : 'light';
    document.documentElement.dataset.theme = nextTheme;
    localStorage.setItem('talaklase-theme', nextTheme);

    if (themeToggle) {
      const isDark = nextTheme === 'dark';
      themeToggle.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
      themeToggle.innerHTML = isDark
        ? '<i class="bi bi-sun-fill"></i><span>Light</span>'
        : '<i class="bi bi-moon-stars-fill"></i><span>Dark</span>';
    }
  }

  setTheme(localStorage.getItem('talaklase-theme') || document.documentElement.dataset.theme || 'light');

  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      setTheme(document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark');
    });
  }

=======
  const MOBILE_BP = 900;

>>>>>>> dad965eae0886277347cae4c6fc181143c8fa104
  function isMobile() { return window.innerWidth <= MOBILE_BP; }

  function openSidebar() {
    sidebar.classList.add('open');
    if (overlay) overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('active');
    document.body.style.overflow = '';
  }

  function toggleDesktop() {
    const collapsed = sidebar.classList.toggle('collapsed');
    content.classList.toggle('expanded', collapsed);
  }

  if (toggle) {
    toggle.addEventListener('click', () => {
      if (isMobile()) {
        sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
      } else {
        toggleDesktop();
      }
    });
  }

  if (overlay) {
    overlay.addEventListener('click', closeSidebar);
  }

  // Close sidebar on nav link tap (mobile)
  document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
    link.addEventListener('click', () => { if (isMobile()) closeSidebar(); });
  });

  // Re-evaluate on resize
  window.addEventListener('resize', () => {
    if (!isMobile()) {
      closeSidebar();
      document.body.style.overflow = '';
    }
  });

  // Bottom nav "More" → opens sidebar
  const moreBtn = document.getElementById('moreMenuBtn');
  if (moreBtn) {
    moreBtn.addEventListener('click', e => {
      e.preventDefault();
      openSidebar();
    });
  }

  // ── Toast helper ──────────────────────────────────────────
  window.showToast = function (message, type = 'success') {
    const toast = document.getElementById('globalToast');
    const msg   = document.getElementById('toastMsg');
    if (!toast || !msg) return;
    toast.className = `toast align-items-center border-0 text-bg-${type}`;
    msg.textContent = message;
    bootstrap.Toast.getOrCreateInstance(toast, { delay: 3000 }).show();
  };

  // ── Generic AJAX helper ───────────────────────────────────
  window.apiCall = async function (url, method = 'GET', data = null) {
    const opts = { method, headers: { 'Content-Type': 'application/json' } };
    if (data) opts.body = JSON.stringify(data);
    const res = await fetch(url, opts);
    return res.json();
  };

  // ── Confirm delete ────────────────────────────────────────
  window.confirmDelete = function (message = 'Are you sure you want to delete this record?') {
    return confirm(message);
  };

  // ── Numeric input validation ──────────────────────────────
  window.numericOnly = function (el) {
    el.addEventListener('input', () => {
      el.value = el.value.replace(/[^0-9.]/g, '');
    });
  };

  document.querySelectorAll('.numeric-input').forEach(window.numericOnly);

})();
