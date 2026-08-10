document.addEventListener('DOMContentLoaded', () => {
  const tabs = document.querySelectorAll('.analytics-tab');
  const panels = document.querySelectorAll('.analytics-tab-panel');
  if (!tabs.length) return;

  const activate = (name, updateHash = true) => {
    tabs.forEach(tab => tab.classList.toggle('active', tab.dataset.tab === name));
    panels.forEach(panel => {
      const active = panel.dataset.panel === name;
      panel.hidden = !active;
      panel.classList.toggle('active', active);
    });
    if (updateHash) history.replaceState(null, '', `#${name}`);
  };

  tabs.forEach(tab => tab.addEventListener('click', () => activate(tab.dataset.tab)));
  const initial = window.location.hash.slice(1);
  activate(['overview', 'attendance', 'academics', 'students'].includes(initial) ? initial : 'overview', false);
});
