(() => {
    'use strict';

    const config = window.CrudConfig || {};

    const pkField = config.pkField;
    const fields = config.fields || [];

    const CrudPage = {

        init() {
            // Reserved for future initialization
        }

    };

    document.addEventListener('DOMContentLoaded', () => {
        CrudPage.init();
    });

})();