// TalaKlase optimistic-action foundation. Use only for explicitly safe UI state changes.
(function () {
  async function run(options) {
    const opts = Object.assign({ onError: null, onSuccess: null }, options || {});
    if (typeof opts.apply !== 'function' || typeof opts.commit !== 'function') throw new Error('Optimistic action requires apply and commit.');
    let rollback;
    try {
      rollback = await opts.apply();
      const result = await opts.commit();
      if (typeof opts.onSuccess === 'function') await opts.onSuccess(result);
      return result;
    } catch (error) {
      if (typeof rollback === 'function') await rollback();
      if (typeof opts.onError === 'function') await opts.onError(error);
      throw error;
    }
  }

  window.TalaOptimistic = { run };
})();
