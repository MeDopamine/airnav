$(document).ready(function () {
    // Initialize DataTable with AJAX source and Buttons
    var periodeSelect = $('#filter-periode');

    var ajaxUrl = function () {
        var p = periodeSelect.val();
        return 'api/get_invoices.php' + (p ? ('?periode=' + encodeURIComponent(p)) : '');
    };

    var table = $('#data-peserta-table').DataTable({
        dom: "Bfrtip",
        pageLength: 25,
        responsive: true,
        ajax: {
            url: ajaxUrl(),
            dataSrc: 'data'
        },
        columns: [
            { data: 'no', className: 'text-center align-middle' },
            { data: 'periode', className: 'text-center align-middle' },
            { data: 'jenis_premi', className: 'text-center align-middle' },
            { data: 'no_invoice', className: 'text-center align-middle' },
            { data: 'tgl_invoice', className: 'text-center align-middle' },
            {
                data: 'jumlah_premi_karyawan',
                className: 'text-right align-middle',
                render: function (data, type, row) {
                    if (!data) return '';
                    if (type === 'sort' || type === 'order') return data.sort;
                    return data.display;
                }
            },
            { data: 'jumlah_peserta', className: 'text-center align-middle' },
            {
                data: 'total_premi',
                className: 'text-right align-middle font-semibold',
                render: function (data, type, row) {
                    if (!data) return '';
                    if (type === 'sort' || type === 'order') return data.sort;
                    return data.display;
                }
            },
            { data: 'pic', className: 'text-center align-middle' },
            { data: 'status', className: 'text-center align-middle', orderable: false },
            { data: 'actions', className: 'text-center align-middle', orderable: false }
        ],
        buttons: [
            { extend: 'pageLength' },
            { extend: 'excelHtml5', title: 'invoice_list' },
            { extend: 'pdfHtml5', title: 'invoice_list', orientation: 'landscape' },
            { extend: 'print' }
        ],
        drawCallback: function () {
            // Convert actions HTML (DataTables will have injected the HTML)
        }
    });

    // Reload table when periode filter changes
    periodeSelect.on('change', function () {
        table.ajax.url(ajaxUrl()).load();
    });

    // Confirm before downloading peserta (Excel)
    $(document).on('click', '.download-peserta', function (e) {
        e.preventDefault();
        var url = $(this).attr('href');
        var periode = $(this).data('periode') || '';
        var label = periode ? ' untuk periode ' + periode : '';

        Swal.fire({
            title: 'Download peserta' + label + '?',
            text: 'File akan di-generate sebagai Excel (.xlsx). Lanjutkan?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, download',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (result.isConfirmed) {
                // Probe availability first via ajax (so we can show nice JSON error)
                var probeUrl = url + (url.indexOf('?') === -1 ? '?ajax=1' : '&ajax=1');
                $.get(probeUrl).done(function (resp) {
                    if (resp && resp.ok) {
                        window.open(url, '_blank');
                    } else {
                        Toast.fire({ icon: 'error', title: resp && resp.msg ? resp.msg : 'Gagal menghasilkan file' });
                    }
                }).fail(function () {
                    Toast.fire({ icon: 'error', title: 'Gagal menghubungi server' });
                });
            }
        });
    });

    // Let download-invoice buttons behave (they point to api/download_invoice.php)
    $(document).on('click', '.download-invoice', function (e) {
        // open in new tab to allow browser to handle download
        e.preventDefault();
        var url = $(this).attr('href');
        var probeUrl = url + (url.indexOf('?') === -1 ? '?ajax=1' : '&ajax=1');
        $.get(probeUrl).done(function (resp) {
            if (resp && resp.ok) {
                window.open(url, '_blank');
            } else {
                Toast.fire({ icon: 'error', title: resp && resp.msg ? resp.msg : 'File invoice tidak tersedia' });
            }
        }).fail(function () {
            Toast.fire({ icon: 'error', title: 'Gagal menghubungi server' });
        });
    });

    // SweetAlert2 Toast mixin
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });

    // Handler untuk tombol "Data Invoice" - buka modal form input
    $(document).on('click', '#add-peserta-btn', function (e) {
        e.preventDefault();
        Swal.fire({
            title: 'Tambah Data Invoice',
                allowOutsideClick: true,
                allowEscapeKey: true,
                heightAuto: false,
                showCloseButton: true,
            html: `
                <div style="text-align:left; padding:10px 5px; border-top:1px solid #eee; border-bottom:1px solid #eee; max-height:700px; overflow-y:auto;">
                    <!-- FORM FIELDS - SIMPLE COLUMN LAYOUT -->
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <!-- ROW 1: PERIODE (DROPDOWN) -->
                        <div style="display:flex; flex-direction:column; gap:4px;">
                            <label for="swal-periode-select" style="font-size:14px;font-weight:500;">Periode</label>
                            <select id="swal-periode-select" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" style="margin:0;">
                                <option value="">-- Pilih Periode --</option>
                            </select>
                        </div>
                        
                        <!-- ROW 2: BULAN & TAHUN (READONLY, FILLED FROM PERIODE SELECT) -->
                        <div style="display:flex; flex-direction:column; gap:4px;">
                            <label style="font-size:14px;font-weight:500;">Bulan / Tahun</label>
                            <div style="display:flex; gap:5px; align-items:center;">
                                <input id="swal-bulan" type="text" readonly class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block flex-1 p-2.5" style="margin:0; cursor:not-allowed; max-width:48%;">
                                <span style="font-size:14px; color:#666;">/</span>
                                <input id="swal-tahun" type="text" readonly class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block flex-1 p-2.5" style="margin:0; cursor:not-allowed; max-width:48%;">
                            </div>
                        </div>
                        
                        <!-- ROW 3: JENIS_INVOICE (DROPDOWN) & TGL_INVOICE (2 COLUMNS) -->
                        <div style="display:flex; gap:12px;">
                            <div style="flex:1; display:flex; flex-direction:column; gap:4px;">
                                <label for="swal-jenis_invoice" style="font-size:14px;font-weight:500;">Jenis Premi</label>
                                <select id="swal-jenis_invoice" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" style="margin:0;">
                                    <option value="">-- Pilih Jenis Premi --</option>
                                </select>
                            </div>
                            <div style="flex:1; display:flex; flex-direction:column; gap:4px;">
                                <label for="swal-tgl_invoice" style="font-size:14px;font-weight:500;">Tanggal Invoice</label>
                                <input id="swal-tgl_invoice" type="text" placeholder="Pilih Tanggal" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" style="margin:0;">
                            </div>
                        </div>
                        
                        <!-- ROW 4: NOINVOICE & JML_PESERTA (2 COLUMNS) -->
                        <div style="display:flex; gap:12px;">
                            <div style="flex:1; display:flex; flex-direction:column; gap:4px;">
                                <label for="swal-noinvoice" style="font-size:14px;font-weight:500;">No. Invoice</label>
                                <input id="swal-noinvoice" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="Nomor Invoice" style="margin:0;">
                            </div>
                            <div style="flex:1; display:flex; flex-direction:column; gap:4px;">
                                <label for="swal-jml_peserta" style="font-size:14px;font-weight:500;">Jumlah Peserta</label>
                                <input id="swal-jml_peserta" type="number" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="Jumlah Peserta" style="margin:0;">
                            </div>
                        </div>
                        
                        <!-- ROW 5: PIC & JML_PREMI (2 COLUMNS) -->
                        <div style="display:flex; gap:12px;">
                            <div style="flex:1; display:flex; flex-direction:column; gap:4px;">
                                <label for="swal-pic" style="font-size:14px;font-weight:500;">PIC</label>
                                <input id="swal-pic" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="Nama PIC" style="margin:0;">
                            </div>
                            <div style="flex:1; display:flex; flex-direction:column; gap:4px;">
                                <label for="swal-jml_premi" style="font-size:14px;font-weight:500;">Total Premi</label>
                                <div style="display:flex; align-items:center; border:1px solid #ccc; border-radius:8px; overflow:hidden; width:100%; height:42px;">
                                    <span style="padding:0 10px; font-size:14px; color:#555; white-space:nowrap;">Rp</span>
                                    <input id="swal-jml_premi" type="text" class="bg-gray-50 border-none text-gray-900 text-sm outline-none block flex-1" placeholder="Masukkan premi" inputmode="numeric" style="margin:0; padding:16px 10px; font-size:14px; border-radius:8px; border-left:1px solid #ccc; border-right:1px solid #fff;">
                                </div>
                            </div>
                        </div>
                        
                        <!-- ROW 6: FILE UPLOADS (FULL WIDTH) -->
                        <div style="display:flex; flex-direction:column; gap:12px; padding-top:12px; border-top:1px solid #f2f2f2;">
                            <div style="display:flex; flex-direction:column; gap:4px;">
                                <label for="swal-link_file" style="font-size:14px;font-weight:500;">File Invoice (PDF)</label>
                                <input id="swal-link_file" type="file" accept=".pdf" class="block w-full text-sm text-gray-500 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none p-2" style="margin:0;">
                                <p style="font-size:12px; color:#999; margin:0;">Format: PDF</p>
                            </div>
                            
                            <div style="display:flex; flex-direction:column; gap:4px;">
                                <label for="swal-link_peserta" style="font-size:14px;font-weight:500;">Data Peserta (Excel)</label>
                                <input id="swal-link_peserta" type="file" accept=".xlsx,.xls" class="block w-full text-sm text-gray-500 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none p-2" style="margin:0;">
                                <p style="font-size:12px; color:#999; margin:0;">Format: Excel (.xlsx, .xls)</p>
                            </div>
                        </div>
                    </div>
                </div>
            `,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'Simpan',
            cancelButtonText: 'Batal',
            didOpen: () => {
                // Initialize Flatpickr datepickers after modal opens
                setTimeout(() => {
                    const periodeSelect = document.getElementById('swal-periode-select');
                    const jenisSelect = document.getElementById('swal-jenis_invoice');

                    // Initialize Select2 on periode and jenis dropdowns
                    if (periodeSelect) {
                        $(periodeSelect).select2({
                            dropdownParent: $('.swal2-container'),
                            placeholder: 'Pilih Periode',
                            allowClear: true,
                            width: '100%'
                        });
                    }
                    if (jenisSelect) {
                        $(jenisSelect).select2({
                            dropdownParent: $('.swal2-container'),
                            placeholder: 'Pilih Jenis Premi',
                            allowClear: true,
                            width: '100%'
                        });
                    }

                    // Load periode dropdown from API
                    $.get('api/get_periode_jenis.php?mode=periods', function(resp) {
                        if (resp.ok && resp.data) {
                            if (periodeSelect) {
                                resp.data.forEach(function(item) {
                                    const opt = document.createElement('option');
                                    opt.value = item.periode;
                                    opt.textContent = item.periode;
                                    periodeSelect.appendChild(opt);
                                });
                                // Refresh Select2 to show new options
                                $(periodeSelect).select2({
                                    dropdownParent: $('.swal2-container'),
                                    placeholder: 'Pilih Periode',
                                    allowClear: true,
                                    width: '100%'
                                });
                            }
                        }
                    });

                    // Event listener: when Periode is selected, populate Jenis dropdown and fill Bulan/Tahun
                    if (periodeSelect) {
                        $(periodeSelect).on('change', function() {
                            const selectedPeriode = this.value;
                            const bulanEl = document.getElementById('swal-bulan');
                            const tahunEl = document.getElementById('swal-tahun');

                            // Parse periode YYYYMM -> MM and YYYY
                            if (selectedPeriode && selectedPeriode.length === 6) {
                                const yyyy = selectedPeriode.substring(0, 4);
                                const mm = selectedPeriode.substring(4, 6);
                                if (bulanEl) bulanEl.value = mm;
                                if (tahunEl) tahunEl.value = yyyy;
                            } else {
                                if (bulanEl) bulanEl.value = '';
                                if (tahunEl) tahunEl.value = '';
                            }

                            // Clear and load jenis dropdown for this periode
                            if (jenisSelect) {
                                // Clear existing options (keep placeholder)
                                $(jenisSelect).empty().append('<option value="">Pilih Jenis Premi</option>');
                                
                                if (selectedPeriode) {
                                    $.get('api/get_periode_jenis.php?mode=periode_jenis&periode=' + encodeURIComponent(selectedPeriode), function(resp) {
                                        if (resp.ok && resp.data) {
                                            resp.data.forEach(function(item) {
                                                const opt = document.createElement('option');
                                                opt.value = item.jenis_premi;
                                                opt.textContent = item.jenis_premi;
                                                jenisSelect.appendChild(opt);
                                            });
                                            // Trigger Select2 refresh
                                            $(jenisSelect).trigger('change');
                                        }
                                    });
                                }
                            }
                        });
                    }

                    // Event listener: when Jenis is selected, auto-fill Jumlah Peserta and Total Premi
                    if (jenisSelect) {
                        $(jenisSelect).on('change', function() {
                            const selectedJenis = this.value;
                            const selectedPeriode = periodeSelect ? periodeSelect.value : '';
                            const jmlPesertaEl = document.getElementById('swal-jml_peserta');
                            const jmlPremilEl = document.getElementById('swal-jml_premi');

                            if (selectedPeriode && selectedJenis) {
                                // Fetch aggregated data
                                $.get('api/get_periode_jenis.php?mode=jenis_detail&periode=' + encodeURIComponent(selectedPeriode) + '&jenis=' + encodeURIComponent(selectedJenis), function(resp) {
                                    if (resp.ok) {
                                        if (jmlPesertaEl) jmlPesertaEl.value = resp.jumlah_peserta;
                                        if (jmlPremilEl) {
                                            // Format total_premi as currency (with dots as thousand separator)
                                            const total = resp.total_premi;
                                            const formatted = Math.floor(total).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                                            jmlPremilEl.value = formatted;
                                        }
                                    }
                                });
                            }
                        });
                    }

                    // Flatpickr for Tanggal Invoice (Full date) - will be formatted as YYYY-MM-DD
                    flatpickr('#swal-tgl_invoice', {
                        mode: 'single',
                        dateFormat: 'Y-m-d',
                        appendTo: document.body,
                        position: 'auto'
                    });

                    // If user manually types/pastes periode, parse it on blur
                    const periodeElManual = document.getElementById('swal-periode');
                    if (periodeElManual) {
                        periodeElManual.addEventListener('blur', function () {
                            const v = String(this.value || '').trim();
                            let y = '', m = '';
                            // Accept either YYYYMM or YYYY-MM or YYYY/MM
                            const r1 = v.match(/^(\d{4})(\d{2})$/);
                            const r2 = v.match(/^(\d{4})[-\/](\d{2})$/);
                            if (r1) {
                                y = r1[1]; m = r1[2];
                            } else if (r2) {
                                y = r2[1]; m = r2[2];
                            }
                            if (y && m) {
                                // normalize to YYYYMM in the periode input
                                this.value = y + m;
                                const bulanEl = document.getElementById('swal-bulan');
                                const tahunEl = document.getElementById('swal-tahun');
                                if (bulanEl) bulanEl.value = m;
                                if (tahunEl) tahunEl.value = y;
                            }
                        });
                    }

                    // Currency formatter for Total Premi input (ID: swal-jml_premi)
                    const premiEl = document.getElementById('swal-jml_premi');
                    if (premiEl) {
                        const formatToCurrency = (val) => {
                            // keep only digits
                            const digits = String(val || '').replace(/\D/g, '');
                            if (!digits) return '';
                            return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                        };

                        const setFormatted = (el) => {
                            const cur = el.value;
                            el.value = formatToCurrency(cur);
                        };

                        // format on input (simple, caret position may reset)
                        premiEl.addEventListener('input', function (e) {
                            const pos = this.selectionStart;
                            setFormatted(this);
                            // try to keep caret at end
                            this.selectionStart = this.selectionEnd = this.value.length;
                        });

                        // format on blur to ensure proper display
                        premiEl.addEventListener('blur', function () {
                            setFormatted(this);
                        });

                        // allow paste: sanitize then format
                        premiEl.addEventListener('paste', function (e) {
                            e.preventDefault();
                            const text = (e.clipboardData || window.clipboardData).getData('text');
                            const digits = String(text).replace(/\D/g, '');
                            this.value = formatToCurrency(digits);
                        });
                    }
                }, 100);
            },
            preConfirm: () => {
                const periode = document.getElementById('swal-periode-select').value;
                const bulan = document.getElementById('swal-bulan').value;
                const tahun = document.getElementById('swal-tahun').value;
                const jenis_invoice = document.getElementById('swal-jenis_invoice').value;
                const noinvoice = document.getElementById('swal-noinvoice').value;
                const tglInvoice = document.getElementById('swal-tgl_invoice').value;
                const jml_peserta = document.getElementById('swal-jml_peserta').value;
                const jml_premi = document.getElementById('swal-jml_premi').value;
                const pic = document.getElementById('swal-pic').value;
                const linkFile = document.getElementById('swal-link_file').files[0];
                const linkPeserta = document.getElementById('swal-link_peserta').files[0];
                
                // Parse currency untuk jml_premi
                let jml_premi_formatted = jml_premi.replace(/[^\d,\.]/g, '').replace(/\.(?=\d{3,})/g, '').replace(',', '.');
                
                return {
                    periode: periode,
                    bulan: bulan,
                    tahun: tahun,
                    jenis_invoice: jenis_invoice,
                    noinvoice: noinvoice,
                    tgl_invoice: tglInvoice,
                    jml_peserta: jml_peserta,
                    jml_premi: jml_premi_formatted,
                    pic: pic,
                    linkFile: linkFile,
                    linkPeserta: linkPeserta
                };
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                const data = result.value;

                // Validasi input
                if (!data.periode) {
                    Toast.fire({ icon: 'error', title: 'Periode wajib dipilih' });
                    return;
                }
                if (!data.jenis_invoice) {
                    Toast.fire({ icon: 'error', title: 'Jenis Invoice wajib diisi' });
                    return;
                }
                if (!data.noinvoice) {
                    Toast.fire({ icon: 'error', title: 'No. Invoice wajib diisi' });
                    return;
                }
                if (!data.tgl_invoice) {
                    Toast.fire({ icon: 'error', title: 'Tanggal Invoice wajib diisi' });
                    return;
                }
                if (!data.jml_peserta) {
                    Toast.fire({ icon: 'error', title: 'Jumlah Peserta wajib diisi' });
                    return;
                }
                if (!data.jml_premi) {
                    Toast.fire({ icon: 'error', title: 'Total Premi wajib diisi' });
                    return;
                }
                if (!data.pic) {
                    Toast.fire({ icon: 'error', title: 'PIC wajib diisi' });
                    return;
                }

                // Create FormData for file upload support
                const formData = new FormData();
                formData.append('periode', data.periode);
                formData.append('bulan', data.bulan);
                formData.append('tahun', data.tahun);
                formData.append('jenis_invoice', data.jenis_invoice);
                formData.append('noinvoice', data.noinvoice);
                formData.append('tgl_invoice', data.tgl_invoice);
                formData.append('jml_peserta', data.jml_peserta);
                formData.append('jml_premi', data.jml_premi);
                formData.append('pic', data.pic);
                
                // Append files if selected
                if (data.linkFile) {
                    formData.append('link_file', data.linkFile);
                }
                if (data.linkPeserta) {
                    formData.append('link_peserta', data.linkPeserta);
                }

                // Send to API via AJAX
                $.ajax({
                    url: 'api/add_invoice.php',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function (resp) {
                        if (resp && resp.ok) {
                            Toast.fire({ icon: 'success', title: 'Invoice berhasil ditambahkan' });
                            // Reload tabel via AJAX
                            table.ajax.reload();
                        } else {
                            Toast.fire({ icon: 'error', title: 'Gagal menambahkan invoice: ' + (resp && resp.msg ? resp.msg : '') });
                        }
                    },
                    error: function (xhr) {
                        Toast.fire({ icon: 'error', title: 'Terjadi kesalahan saat menyimpan invoice' });
                    }
                });
            }
        });
    });

    // Handle Edit button click
    $(document).on('click', '.btn-edit', function(e) {
        e.preventDefault();
        var invoiceId = $(this).data('id');
        var invoiceNo = $(this).data('invoice');

        // Fetch invoice detail
        $.get('api/get_invoice_detail.php?id=' + invoiceId, function(resp) {
            if (!resp || !resp.ok) {
                Toast.fire({ icon: 'error', title: 'Gagal memuat data invoice' });
                return;
            }

            var data = resp.data;
            var tglInvoice = data.tgl_invoice ? data.tgl_invoice.split(' ')[0] : '';

            Swal.fire({
                title: 'Edit Data Invoice',
                allowOutsideClick: true,
                allowEscapeKey: true,
                heightAuto: false,
                showCloseButton: true,
                html: `
                    <div style="text-align:left; padding:10px 5px; border-top:1px solid #eee; border-bottom:1px solid #eee; max-height:700px; overflow-y:auto;">
                        <!-- FORM FIELDS - SIMPLE COLUMN LAYOUT -->
                        <div style="display:flex; flex-direction:column; gap:12px;">
                            <!-- ROW 1: PERIODE (DROPDOWN) -->
                            <div style="display:flex; flex-direction:column; gap:4px;">
                                <label for="edit-periode-select" style="font-size:14px;font-weight:500;">Periode</label>
                                <select id="edit-periode-select" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" style="margin:0;">
                                    <option value="">-- Pilih Periode --</option>
                                </select>
                            </div>
                            
                            <!-- ROW 2: BULAN & TAHUN (READONLY, FILLED FROM PERIODE SELECT) -->
                            <div style="display:flex; flex-direction:column; gap:4px;">
                                <label style="font-size:14px;font-weight:500;">Bulan / Tahun</label>
                                <div style="display:flex; gap:5px; align-items:center;">
                                    <input id="edit-bulan" type="text" readonly class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block flex-1 p-2.5" style="margin:0; cursor:not-allowed; max-width:48%;">
                                    <span style="font-size:14px; color:#666;">/</span>
                                    <input id="edit-tahun" type="text" readonly class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block flex-1 p-2.5" style="margin:0; cursor:not-allowed; max-width:48%;">
                                </div>
                            </div>
                            
                            <!-- ROW 3: JENIS_INVOICE (DROPDOWN) & TGL_INVOICE (2 COLUMNS) -->
                            <div style="display:flex; gap:12px;">
                                <div style="flex:1; display:flex; flex-direction:column; gap:4px;">
                                    <label for="edit-jenis_invoice" style="font-size:14px;font-weight:500;">Jenis Premi</label>
                                    <select id="edit-jenis_invoice" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" style="margin:0;">
                                        <option value="">-- Pilih Jenis Premi --</option>
                                    </select>
                                </div>
                                <div style="flex:1; display:flex; flex-direction:column; gap:4px;">
                                    <label for="edit-tgl_invoice" style="font-size:14px;font-weight:500;">Tanggal Invoice</label>
                                    <input id="edit-tgl_invoice" type="text" placeholder="Pilih Tanggal" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" style="margin:0;">
                                </div>
                            </div>
                            
                            <!-- ROW 4: NOINVOICE & JML_PESERTA (2 COLUMNS) -->
                            <div style="display:flex; gap:12px;">
                                <div style="flex:1; display:flex; flex-direction:column; gap:4px;">
                                    <label for="edit-noinvoice" style="font-size:14px;font-weight:500;">No. Invoice</label>
                                    <input id="edit-noinvoice" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="Nomor Invoice" style="margin:0;">
                                </div>
                                <div style="flex:1; display:flex; flex-direction:column; gap:4px;">
                                    <label for="edit-jml_peserta" style="font-size:14px;font-weight:500;">Jumlah Peserta</label>
                                    <input id="edit-jml_peserta" type="number" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="Jumlah Peserta" style="margin:0;">
                                </div>
                            </div>
                            
                            <!-- ROW 5: PIC & JML_PREMI (2 COLUMNS) -->
                            <div style="display:flex; gap:12px;">
                                <div style="flex:1; display:flex; flex-direction:column; gap:4px;">
                                    <label for="edit-pic" style="font-size:14px;font-weight:500;">PIC</label>
                                    <input id="edit-pic" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="Nama PIC" style="margin:0;">
                                </div>
                                <div style="flex:1; display:flex; flex-direction:column; gap:4px;">
                                    <label for="edit-jml_premi" style="font-size:14px;font-weight:500;">Total Premi</label>
                                    <div style="display:flex; align-items:center; border:1px solid #ccc; border-radius:8px; overflow:hidden; width:100%; height:42px;">
                                        <span style="padding:0 10px; font-size:14px; color:#555; white-space:nowrap;">Rp</span>
                                        <input id="edit-jml_premi" type="text" class="bg-gray-50 border-none text-gray-900 text-sm outline-none block flex-1" placeholder="Masukkan premi" inputmode="numeric" style="margin:0; padding:16px 10px; font-size:14px; border-radius:8px; border-left:1px solid #ccc; border-right:1px solid #fff;">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- ROW 6: FILE UPLOADS (FULL WIDTH) -->
                            <div style="display:flex; flex-direction:column; gap:12px; padding-top:12px; border-top:1px solid #f2f2f2;">
                                <div style="display:flex; flex-direction:column; gap:4px;">
                                    <label for="edit-link_file" style="font-size:14px;font-weight:500;">File Invoice (PDF)</label>
                                    <input id="edit-link_file" type="file" accept=".pdf" class="block w-full text-sm text-gray-500 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none p-2" style="margin:0;">
                                    <p style="font-size:12px; color:#999; margin:0;">Format: PDF</p>
                                </div>
                                
                                <div style="display:flex; flex-direction:column; gap:4px;">
                                    <label for="edit-link_peserta" style="font-size:14px;font-weight:500;">Data Peserta (Excel)</label>
                                    <input id="edit-link_peserta" type="file" accept=".xlsx,.xls" class="block w-full text-sm text-gray-500 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none p-2" style="margin:0;">
                                    <p style="font-size:12px; color:#999; margin:0;">Format: Excel (.xlsx, .xls)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Simpan',
                cancelButtonText: 'Batal',
                didOpen: async () => {
                    // Set nilai dari data yang diambil
                    document.getElementById('edit-noinvoice').value = data.invoice_no;
                    document.getElementById('edit-tgl_invoice').value = tglInvoice;
                    document.getElementById('edit-jml_peserta').value = data.jumlah;
                    document.getElementById('edit-pic').value = data.pic;
                    document.getElementById('edit-jml_premi').value = data.total_premi.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    
                    // Parse bulan dan tahun dari periode (YYYYMM)
                    if (data.periode && data.periode.length === 6) {
                        document.getElementById('edit-tahun').value = data.periode.slice(0, 4);
                        var month = data.periode.slice(4, 6);
                        document.getElementById('edit-bulan').value = month;
                    }
                    
                    // Initialize Select2 untuk periode
                    const periodeSelect = document.getElementById('edit-periode-select');
                    try {
                        const perResponse = await fetch('api/get_periode_jenis.php?mode=periods', {
                            credentials: 'include'
                        });
                        const perData = await perResponse.json();
                        
                        if (perData.ok && perData.data) {
                            const options = '<option></option>' + perData.data.map(p => `<option value="${p.periode}">${p.periode}</option>`).join('');
                            periodeSelect.innerHTML = options;
                            periodeSelect.value = data.periode;
                            $(periodeSelect).select2({
                                placeholder: 'Pilih Periode',
                                allowClear: true,
                                width: '100%'
                            });
                        }
                    } catch (e) {
                        console.error('Error loading periode:', e);
                        $(periodeSelect).select2({
                            placeholder: 'Pilih Periode',
                            allowClear: true,
                            width: '100%'
                        });
                        periodeSelect.value = data.periode;
                    }
                    
                    // Initialize Select2 untuk jenis invoice
                    const jenisSelect = document.getElementById('edit-jenis_invoice');
                    try {
                        const jenisResponse = await fetch('api/get_periode_jenis.php?mode=jenis', {
                            credentials: 'include'
                        });
                        const jenisData = await jenisResponse.json();
                        
                        if (jenisData.ok && jenisData.data) {
                            const options = '<option></option>' + jenisData.data.map(j => `<option value="${j.jenis}">${j.jenis}</option>`).join('');
                            jenisSelect.innerHTML = options;
                            jenisSelect.value = data.jenis_premi;
                            $(jenisSelect).select2({
                                placeholder: 'Pilih Jenis Premi',
                                allowClear: true,
                                width: '100%'
                            });
                        }
                    } catch (e) {
                        console.error('Error loading jenis:', e);
                        $(jenisSelect).select2({
                            placeholder: 'Pilih Jenis Premi',
                            allowClear: true,
                            width: '100%'
                        });
                        jenisSelect.value = data.jenis_premi;
                    }
                    
                    // Initialize Flatpickr datepicker for Tanggal Invoice
                    flatpickr('#edit-tgl_invoice', {
                        mode: 'single',
                        dateFormat: 'Y-m-d',
                        appendTo: document.body,
                        position: 'auto'
                    });

                    // Currency formatter for Total Premi
                    const premiEl = document.getElementById('edit-jml_premi');
                    if (premiEl) {
                        const formatToCurrency = (val) => {
                            const digits = String(val || '').replace(/\D/g, '');
                            if (!digits) return '';
                            return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                        };

                        premiEl.addEventListener('input', function () {
                            this.value = formatToCurrency(this.value);
                            this.selectionStart = this.selectionEnd = this.value.length;
                        });

                        premiEl.addEventListener('blur', function () {
                            this.value = formatToCurrency(this.value);
                        });

                        premiEl.addEventListener('paste', function (e) {
                            e.preventDefault();
                            const text = (e.clipboardData || window.clipboardData).getData('text');
                            const digits = String(text).replace(/\D/g, '');
                            this.value = formatToCurrency(digits);
                        });
                    }
                },
                preConfirm: () => {
                    const periode = document.getElementById('edit-periode-select').value;
                    const jenis_invoice = document.getElementById('edit-jenis_invoice').value;
                    const tgl_invoice = document.getElementById('edit-tgl_invoice').value;
                    const noinvoice = document.getElementById('edit-noinvoice').value;
                    const jml_peserta = document.getElementById('edit-jml_peserta').value;
                    const jml_premi = document.getElementById('edit-jml_premi').value;
                    const pic = document.getElementById('edit-pic').value;
                    const linkFile = document.getElementById('edit-link_file').files[0];
                    const linkPeserta = document.getElementById('edit-link_peserta').files[0];
                    
                    // Parse currency untuk jml_premi (remove dots)
                    let jml_premi_formatted = jml_premi.replace(/\./g, '');
                    
                    return {
                        periode: periode,
                        jenis_invoice: jenis_invoice,
                        tgl_invoice: tgl_invoice,
                        noinvoice: noinvoice,
                        jml_peserta: jml_peserta,
                        jml_premi: jml_premi_formatted,
                        pic: pic,
                        linkFile: linkFile,
                        linkPeserta: linkPeserta
                    };
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    const formData = new FormData();
                    
                    // Append form fields
                    formData.append('id', invoiceId);
                    formData.append('invoice_no', result.value.noinvoice);
                    formData.append('tgl_invoice', result.value.tgl_invoice);
                    formData.append('jml_peserta', result.value.jml_peserta);
                    formData.append('jml_premi', result.value.jml_premi);
                    formData.append('pic', result.value.pic);
                    
                    // Append files if selected
                    if (result.value.linkFile) {
                        formData.append('link_file', result.value.linkFile);
                    }
                    if (result.value.linkPeserta) {
                        formData.append('link_peserta', result.value.linkPeserta);
                    }

                    $.ajax({
                        url: 'api/update_invoice.php',
                        type: 'POST',
                        data: formData,
                        contentType: false,
                        processData: false,
                        success: function (resp) {
                            if (resp && resp.ok) {
                                Toast.fire({ icon: 'success', title: 'Invoice berhasil diperbarui' });
                                table.ajax.reload();
                            } else {
                                Toast.fire({ icon: 'error', title: 'Gagal update invoice: ' + (resp && resp.msg ? resp.msg : '') });
                            }
                        },
                        error: function () {
                            Toast.fire({ icon: 'error', title: 'Terjadi kesalahan saat menyimpan' });
                        }
                    });
                }
            });
        }).fail(function() {
            Toast.fire({ icon: 'error', title: 'Gagal memuat data invoice' });
        });
    });
});
