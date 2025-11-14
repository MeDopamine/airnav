// Shared helpers for peserta page
(function(window){
    'use strict';

    // Attach currency formatting listeners to input by id (attach once)
    function attachCurrencyFormattingById(elId) {
        try {
            var premiInput = document.getElementById(elId);
            if (!premiInput) return;
            // avoid attaching multiple times: mark with a flag
            if (premiInput._currencyAttached) return;
            premiInput._currencyAttached = true;

            premiInput.addEventListener('keypress', function(e) {
                if (e.key && !/[0-9]/.test(e.key)) {
                    e.preventDefault();
                }
            });
            premiInput.addEventListener('paste', function(e) {
                var pasted = (e.clipboardData || window.clipboardData).getData('text');
                if (/[^0-9]/.test(pasted)) {
                    e.preventDefault();
                }
            });
            premiInput.addEventListener('input', function(e) {
                let value = premiInput.value.replace(/[^0-9]/g, '');
                if (value) {
                    let formatted = new Intl.NumberFormat('id-ID').format(Number(value));
                    premiInput.value = formatted;
                } else {
                    premiInput.value = '';
                }
            });
        } catch (e) {
            console.warn('attachCurrencyFormattingById failed', e);
        }
    }

    // expose to global so existing pages can call it
    window.attachCurrencyFormattingById = attachCurrencyFormattingById;
})(window);

// Additional shared helpers for peserta page
(function(window){
    'use strict';

    function formatPremi(val) {
        return 'Rp ' + Number(val).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function makeApproveBadge(status, id) {
        var approved = status ? 1 : 0;
        var badgeClass = approved
            ? 'px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-green-100 text-green-800'
            : 'px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-red-100 text-red-800';
        var badgeLabel = approved ? 'Approved' : 'Not Approved';
        return '<span class="approve-btn cursor-pointer inline-flex items-center justify-center w-full h-full ' + badgeClass + '" style="min-width:110px;display:flex;align-items:center;justify-content:center;" data-id="' + (id || '') + '" data-status="' + approved + '">' + badgeLabel + '</span>';
    }

    function rebuildPeriodeSelectFromSet() {
        var arr = Object.keys(window._periodeSet || {});
        arr.sort(function(a,b){ return b.localeCompare(a, undefined, {numeric: true}); });
        $('#periode').empty();
        $('#periode').append($('<option>', { value: '', text: ' Semua Periode ' }));
        arr.forEach(function(v){ $('#periode').append($('<option>', { value: v, text: formatPeriodeReadable(v) })); });
        window._periodeSet = undefined;
    }

    function resetApproveAllButton(btn) {
        try {
            btn.prop('disabled', false).html('<i class="fa-solid fa-check-double mr-2"></i>Approve All');
        } catch (e) {
            console.warn('resetApproveAllButton failed', e);
        }
    }

    function showErrorNotification(msg) {
        if (typeof Toast !== 'undefined') {
            Toast.fire({ icon: 'error', title: msg });
        } else {
            Swal.fire({ icon: 'error', title: msg, timer: 2000, showConfirmButton: false });
        }
    }

    function formatPeriodeDisplay(val) {
        var p = String(val || '');
        if (/^\d{6}$/.test(p)) return p;
        if (/^\d{4}-\d{2}$/.test(p)) return p.replace('-', '');
        var digits = p.replace(/[^0-9]/g, '');
        if (/^\d{6}$/.test(digits)) return digits;
        if (digits.length >= 6) return digits.slice(0,6);
        return val;
    }

    function formatPeriodeReadable(val) {
        var yyyymm = formatPeriodeDisplay(val);
        if (/^\d{6}$/.test(String(yyyymm))) {
            var year = yyyymm.slice(0,4);
            var month = yyyymm.slice(4,6);
            try {
                var date = new Date(year + '-' + month + '-01');
                var namaBulan = date.toLocaleString('id-ID', { month: 'long' });
                return namaBulan.charAt(0).toUpperCase() + namaBulan.slice(1) + ' ' + year;
            } catch (e) {
                return yyyymm;
            }
        }
        return String(val);
    }

    // expose helpers
    window.formatPremi = formatPremi;
    window.makeApproveBadge = makeApproveBadge;
    window.rebuildPeriodeSelectFromSet = rebuildPeriodeSelectFromSet;
    window.resetApproveAllButton = resetApproveAllButton;
    window.showErrorNotification = showErrorNotification;
    window.formatPeriodeDisplay = formatPeriodeDisplay;
    window.formatPeriodeReadable = formatPeriodeReadable;

})(window);
