(function(global){
    'use strict';

    function initElement(el, opts) {
        if (!el) return;
        try {
            if (typeof Datepicker !== 'undefined') {
                // Flowbite Datepicker present
                new Datepicker(el, Object.assign({autohide:true, format:'dd-mm-yyyy'}, opts || {}));
            } else {
                // Fallback to native date input
                try { el.type = 'text'; } catch(e) { el.type = 'text'; }
            }
        } catch (ex) {
            try { el.type = 'text'; } catch(_){ try { el.type = 'text'; } catch(__){} }
            // optional: console.warn('Datepicker init failed', ex);
        }
    }

    function initAll(selector) {
        selector = selector || '[datepicker]';
        var els = document.querySelectorAll(selector);
        Array.prototype.forEach.call(els, function(el){ initElement(el); });
    }

    // expose
    global.DatepickerInit = {
        initElement: initElement,
        initAll: initAll
    };

    // auto-run on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function(){
        try { initAll(); } catch(e){}
    });

})(window);
