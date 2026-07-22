// TECH4LESS — interazioni frontend (vanilla JS, no jQuery)

(function () {
  const root = document.documentElement;
  const themeToggle = document.querySelector('[data-theme-toggle]');
  const STORAGE_KEY = 'tech4less-theme';

  function applyTheme(theme) {
    root.setAttribute('data-theme', theme);
    localStorage.setItem(STORAGE_KEY, theme);
    if (themeToggle) {
      themeToggle.textContent = theme === 'dark' ? '☀' : '☾';
      themeToggle.setAttribute('aria-label',
        theme === 'dark' ? 'Passa al tema chiaro' : 'Passa al tema scuro');
    }
  }

  const saved = localStorage.getItem(STORAGE_KEY)
    || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
  applyTheme(saved);

  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      const current = root.getAttribute('data-theme');
      applyTheme(current === 'dark' ? 'light' : 'dark');
    });
  }

  // Badge carrello: placeholder finché non c'è il backend reale (CartService)
  const cartCount = document.querySelector('[data-cart-count]');
  document.querySelectorAll('[data-add-to-cart]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const current = parseInt(cartCount?.textContent || '0', 10);
      if (cartCount) cartCount.textContent = String(current + 1);
      btn.textContent = 'Aggiunto ✓';
      btn.disabled = true;
      setTimeout(() => {
        btn.textContent = 'Aggiungi al carrello';
        btn.disabled = false;
      }, 1200);
    });
  });
})();
