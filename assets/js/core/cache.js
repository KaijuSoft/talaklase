// TalaKlase cache foundation: allowlisted, TTL-based client cache only.
(function () {
  const PREFIX = 'talaklase-cache:';
  const DEFAULTS = { academicYears: 30 * 60 * 1000, sections: 10 * 60 * 1000, subjects: 10 * 60 * 1000 };
  const ALLOWED = new Set(['academicYears', 'sections', 'subjects', 'teachingAssignments', 'students', 'analytics']);
  const TTL = Object.assign({}, DEFAULTS, { teachingAssignments: 5 * 60 * 1000, students: 2 * 60 * 1000, analytics: 30 * 1000 });

  function key(name, context) { return PREFIX + name + ':' + (context || 'default'); }
  function valid(name) { return ALLOWED.has(name); }

  window.TalaCache = {
    get(name, context) {
      if (!valid(name)) return null;
      try {
        const raw = sessionStorage.getItem(key(name, context));
        if (!raw) return null;
        const item = JSON.parse(raw);
        if (!item || Date.now() - item.createdAt > TTL[name]) {
          sessionStorage.removeItem(key(name, context)); return null;
        }
        return item.value;
      } catch (_) { return null; }
    },
    set(name, value, context) {
      if (!valid(name)) return value;
      try { sessionStorage.setItem(key(name, context), JSON.stringify({ createdAt: Date.now(), value })); } catch (_) {}
      return value;
    },
    invalidate(name, context) {
      if (!valid(name)) return;
      if (context) sessionStorage.removeItem(key(name, context));
      else Object.keys(sessionStorage).filter(k => k.startsWith(PREFIX + name + ':')).forEach(k => sessionStorage.removeItem(k));
    },
    clear() { Object.keys(sessionStorage).filter(k => k.startsWith(PREFIX)).forEach(k => sessionStorage.removeItem(k)); }
  };
})();
