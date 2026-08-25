// TalaKlase skeleton foundation. No artificial delays; callers control lifecycle.
(function () {
  function show(target, options) {
    const el = typeof target === 'string' ? document.querySelector(target) : target;
    if (!el) return null;
    const opts = Object.assign({ rows: 5, columns: 4 }, options || {});
    el.setAttribute('aria-busy', 'true');
    el.classList.add('tk-skeleton-host');
    const fragment = document.createDocumentFragment();
    for (let r = 0; r < opts.rows; r++) {
      const row = document.createElement('div'); row.className = 'tk-skeleton-row';
      for (let c = 0; c < opts.columns; c++) {
        const block = document.createElement('span'); block.className = 'tk-skeleton-block';
        block.style.setProperty('--tk-skeleton-width', c === 0 ? '72%' : (55 + ((r + c) % 4) * 10) + '%');
        row.appendChild(block);
      }
      fragment.appendChild(row);
    }
    el.replaceChildren(fragment);
    return el;
  }

  function hide(target) {
    const el = typeof target === 'string' ? document.querySelector(target) : target;
    if (!el) return;
    el.removeAttribute('aria-busy'); el.classList.remove('tk-skeleton-host');
  }

  window.TalaSkeleton = { show, hide };
})();
