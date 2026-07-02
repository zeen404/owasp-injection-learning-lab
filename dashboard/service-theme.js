/* ═══════════════════════════════════════════════════════════════
   OWASP A05 Injection Lab — Universal Service Theme Manager
═══════════════════════════════════════════════════════════════ */

(function () {
  // 1. Check URL parameters for theme state first, then fallback to localStorage
  const urlParams = new URLSearchParams(window.location.search);
  let theme = urlParams.get('theme');
  
  if (!theme) {
    theme = localStorage.getItem('theme') || 'dark';
  } else {
    localStorage.setItem('theme', theme);
  }
  
  document.documentElement.setAttribute('data-theme', theme);

  // 2. Insert Toggle Button dynamically on DOMContentLoaded
  window.addEventListener('DOMContentLoaded', () => {
    const header = document.querySelector('.header');
    if (header) {
      const toggleBtn = document.createElement('button');
      toggleBtn.className = 'theme-btn';
      toggleBtn.setAttribute('aria-label', 'Toggle Theme');
      toggleBtn.innerHTML = `
        <span class="theme-icon-dark">🌙</span>
        <span class="theme-icon-light">☀️</span>
        <span class="theme-icon-lumio">🌅</span>
      `;
      
      toggleBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
        let newTheme = 'dark';
        if (currentTheme === 'dark') {
          newTheme = 'light';
        } else if (currentTheme === 'light') {
          newTheme = 'lumio';
        } else {
          newTheme = 'dark';
        }
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
      });
      
      header.appendChild(toggleBtn);
    }
  });
})();
