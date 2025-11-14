// Tabel Periode + Jenis Premi (combined aggregates)
        var periodeTable = null;
        function loadPeriodeTable() {
            $.get('api/get_periode_jenis.php', function(resp) {
                if (resp && resp.ok && Array.isArray(resp.data)) {
                    if (periodeTable) {
                        periodeTable.clear().draw();
                    } else {
                        periodeTable = $('#periode-table').DataTable({
                            pageLength: 10,
                            searching: false,
                            ordering: false,
                            lengthChange: false,
                            info: false,
                            autoWidth: false,
                            // set column widths to produce stable layout similar to provided screenshot
                            columnDefs: [
                                { targets: 0, width: '50px', className: 'text-center' }, // No
                                { targets: 1, width: '120px', className: 'text-center' }, // Periode
                                { targets: 2, width: '100px', className: 'text-center' }, // Jenis Premi
                                { targets: 3, width: '100px', className: 'text-center' }, // Jumlah Peserta
                                { targets: 4, width: '140px', className: 'text-center' }, // Total Premi Karyawan
                                { targets: 5, width: '140px', className: 'text-center' }, // Total Premi PT
                                { targets: 6, width: '140px', className: 'text-center' }, // Total Premi
                                { targets: 7, width: '140px', className: 'text-center' }, // Tanggal Upload
                                { targets: 8, width: '120px', className: 'text-center' }  // Aksi
                            ],
                            columns: [
                                { data: null, className: 'text-center', render: function(data, type, row, meta) { return meta.row + 1; } },
                                { data: 'periode', className: 'text-center', render: function(data, type, row) {
                                    try { return formatPeriodeReadable(data); } catch (e) { return String(data); }
                                } },
                                { data: 'jenis', className: 'text-center', render: function(data, type, row) {
                                    return String(data || '');
                                } },
                                { data: 'jumlah_peserta', className: 'text-center', render: function(data, type, row) {
                                    return String(data || '0');
                                } },
                                { data: 'sum_krywn', className: 'text-center', render: function(data, type, row) {
                                    try { return formatPremi(data); } catch (e) { return String(data); }
                                } },
                                { data: 'sum_pt', className: 'text-center', render: function(data, type, row) {
                                    try { return formatPremi(data); } catch (e) { return String(data); }
                                } },
                                { data: 'sum_total', className: 'text-center', render: function(data, type, row) {
                                    try { return formatPremi(data); } catch (e) { return String(data); }
                                } },
                                { data: 'created_at', className: 'text-center', render: function(data, type, row) {
                                    if (!data) return '-';
                                    try {
                                        var d = new Date(data);
                                        return d.toLocaleDateString('id-ID', { year: 'numeric', month: '2-digit', day: '2-digit' });
                                    } catch (e) { return String(data); }
                                } },
                                { data: null, className: 'text-center', render: function(data, type, row) {
                                    return `<div style='display:flex;justify-content:center;align-items:center;height:100%;'><button class="btn-lihat-peserta bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-1.5 rounded-full flex items-center justify-center transition" style="min-width:100px;gap:6px;" data-periode="${row.periode}" data-jenis="${row.jenis}"><i class='fa-solid fa-eye' style='font-size:15px;vertical-align:middle;'></i><span style='margin-left:4px;'>Lihat</span></button></div>`;
                                }}
                            ]
                        });
                    }
                    var rows = resp.data.map(function(r) {
                        return {
                            periode: r.periode,
                            jenis: r.jenis,
                            jumlah_peserta: r.jumlah_peserta,
                            sum_krywn: r.sum_krywn,
                            sum_pt: r.sum_pt,
                            sum_total: r.sum_total,
                            created_at: r.created_at
                        };
                    });
                    periodeTable.clear().rows.add(rows).draw();
                }
            });
        }
        
                // Helper functions to remove duplicated code
                // NOTE: UI helpers (formatPremi, makeApproveBadge, rebuildPeriodeSelectFromSet,
                // resetApproveAllButton, showErrorNotification, formatPeriodeDisplay, formatPeriodeReadable)
                // are provided by `assets/js/helpers/peserta-helpers.js` and exposed on window.

                function buildMainTableFromData(dataArray) {
                    // Avoid auto-initializing DataTable here. If the DataTable has not been
                    // initialized yet (it is initialized in $(document).ready below),
                    // populate the <tbody> directly and return. This prevents a race
                    // where calling DataTable() early creates a second instance with
                    // different options, causing inconsistent rendering.
                    var table;
                    var usingApi = false;
                    if ($.fn.DataTable && $.fn.DataTable.isDataTable('#data-peserta-table')) {
                        table = $('#data-peserta-table').DataTable();
                        table.clear();
                        usingApi = true;
                    } else {
                    // fallback: build tbody HTML so the later DataTable init will pick it up
                        var $tbody = $('#data-peserta-table tbody');
                        $tbody.empty();
                    }
                    // ensure periode set
                    if (!window._periodeSet) window._periodeSet = {};
                    dataArray.forEach(function(row) {
                        // Expected fields from API: id, periode, jenis_premi, jml_premi_krywn, jml_premi_pt, total_premi, status_data
                        var displayPeriode = formatPeriodeDisplay(row.periode);
                        var jenisPremi = typeof row.jenis_premi !== 'undefined' ? String(row.jenis_premi) : '';
                        var jmlKry = typeof row.jml_premi_krywn !== 'undefined' ? formatPremi(row.jml_premi_krywn) : '';
                        var jmlPt = typeof row.jml_premi_pt !== 'undefined' ? formatPremi(row.jml_premi_pt) : '';
                        var totalPremiFormatted = formatPremi(row.total_premi);
                        var approved = (row.status_data == 1) ? 1 : 0;
                        var approveBtn = makeApproveBadge(approved, row.id);
                        var actionBtns = '<button class="btn-edit-data text-blue-600 hover:text-blue-800" style="margin-right:4px;" title="Edit" data-id="' + row.id + '"><i class="fa-solid fa-pen-to-square"></i></button>' +
                                         '<button class="btn-delete-data transition" style="margin-left:4px;" title="Hapus" data-id="' + row.id + '"><i class="fa-solid fa-trash" style="color:#dc2626;"></i></button>';
                        var rowHtml = '<tr>' +
                            '<td class="text-center"></td>' +
                            '<td class="text-center">' + displayPeriode + '</td>' +
                            '<td class="text-center">' + jenisPremi + '</td>' +
                            '<td class="text-center">' + jmlKry + '</td>' +
                            '<td class="text-center">' + jmlPt + '</td>' +
                            '<td class="text-center">' + totalPremiFormatted + '</td>' +
                            '<td class="text-center">' + approveBtn + '</td>' +
                            '<td class="text-center">' + actionBtns + '</td>' +
                            '</tr>';
                        if (usingApi) {
                            table.row.add([
                                '',
                                displayPeriode,
                                jenisPremi,
                                jmlKry,
                                jmlPt,
                                totalPremiFormatted,
                                approveBtn,
                                actionBtns
                            ]);
                        } else {
                            $tbody.append(rowHtml);
                        }
                        if (row.periode) window._periodeSet[row.periode] = true;
                    });
                    // default order by periode (column 1 desc)
                    table.order([1, 'desc']);
                    table.rows().invalidate().draw(false);
                    rebuildPeriodeSelectFromSet();
                }

                    loadPeriodeTable();

        // Handler klik tombol Lihat Data pada tabel periode
        $(document).on('click', '.btn-lihat-peserta', function() {
              var periode = $(this).data('periode');
              var jenis = $(this).data('jenis');
              if (!periode) return;
              showTableLoading();
              // Build params: always send periode, conditionally send jenis if not empty
              var params = { periode: periode };
              if (jenis && jenis !== '' && jenis !== null && jenis !== 'undefined') {
                  params.jenis = jenis;
              }
              $.get('api/get_peserta_by_periode.php', params, function(resp) {
                hideTableLoading();
                if (resp && resp.ok && Array.isArray(resp.data)) {
                    // Urutkan berdasarkan NIK ascending
                    resp.data.sort(function(a, b) {
                        if (a.nik < b.nik) return -1;
                        if (a.nik > b.nik) return 1;
                        return 0;
                    });
                    var html = '';
                    // Tombol approve all akan dimasukkan lewat DataTables custom button
                    html += '<div style="overflow-x:auto;">';
                    html += '<table id="modal-peserta-table" class="display stripe hover w-full" style="width:100%;font-size:13px;">';
                    html += '<thead><tr>';
                    html += '<th>No</th>';
                    html += '<th>NIK</th>';
                    html += '<th>Periode</th>';
                    html += '<th>Jenis Premi</th>';
                    html += '<th>Jumlah Premi Karyawan</th>';
                    html += '<th>Jumlah Premi PT</th>';
                    html += '<th>Total Premi</th>';
                    html += '<th>PIC</th>';
                    html += '<th>Approval</th>';
                    html += '<th>Created At</th>';
                    html += '</tr></thead><tbody>';
                    resp.data.forEach(function(row, idx) {
                        // Robust parsing for premi amount to avoid NaN when source is missing or formatted string
                        function parseAmount(val) {
                            if (val === null || typeof val === 'undefined') return 0;
                            if (typeof val === 'number') return val;
                            var s = String(val).trim();
                            // remove any non-numeric except comma and dot and minus
                            s = s.replace(/[^0-9\-,\.]/g, '');
                            if (s === '') return 0;
                            // If both dot and comma present, assume dot is thousand separator and comma decimal
                            if (s.indexOf(',') > -1 && s.indexOf('.') > -1) {
                                s = s.replace(/\./g, '').replace(',', '.');
                            } else {
                                // remove commas (as thousand separators)
                                s = s.replace(/,/g, '');
                            }
                            var n = parseFloat(s);
                            return isNaN(n) ? 0 : n;
                        }
                        var jenisPremi = typeof row.jenis_premi !== 'undefined' ? String(row.jenis_premi) : '';
                        var rawKry = typeof row.jml_premi_krywn !== 'undefined' ? row.jml_premi_krywn : (typeof row.total_premi !== 'undefined' ? row.total_premi : 0);
                        var numKry = parseAmount(rawKry);
                        var jmlKry = 'Rp ' + numKry.toLocaleString('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2});
                        var rawPt = typeof row.jml_premi_pt !== 'undefined' ? row.jml_premi_pt : (typeof row.total_premi !== 'undefined' ? row.total_premi - numKry : 0);
                        var numPt = parseAmount(rawPt);
                        var jmlPt = 'Rp ' + numPt.toLocaleString('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2});
                        var totalPremi = typeof row.total_premi !== 'undefined' ? parseAmount(row.total_premi) : (numKry + numPt);
                        var totalPremiFormatted = 'Rp ' + totalPremi.toLocaleString('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2});
                        var approved = (row.status_data == 1) ? 1 : 0;
                        var badgeClass = approved
                            ? 'px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-green-100 text-green-800'
                            : 'px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-red-100 text-red-800';
                        var badgeLabel = approved ? 'Approved' : 'Not Approved';
                        var approveBtn = '<span class="' + badgeClass + '">' + badgeLabel + '</span>';
                        html += '<tr>';
                        html += '<td class="text-center">' + (idx+1) + '</td>';
                        html += '<td class="text-center">' + row.nik + '</td>';
                        html += '<td class="text-center">' + formatPeriodeDisplay(row.periode) + '</td>';
                        html += '<td class="text-center">' + jenisPremi + '</td>';
                        html += '<td class="text-center">' + jmlKry + '</td>';
                        html += '<td class="text-center">' + jmlPt + '</td>';
                        html += '<td class="text-center">' + totalPremiFormatted + '</td>';
                        html += '<td class="text-center">' + row.pic + '</td>';
                        html += '<td class="text-center">' + approveBtn + '</td>';
                        html += '<td class="text-center">' + row.created_at + '</td>';
                        html += '</tr>';
                    });
                    html += '</tbody></table></div>';
                    // Format periode ke bentuk 'Month Year' (contoh: Oktober 2025)
                    var displayPeriode = periode;
                    var p = String(periode);
                    if (/^\d{6}$/.test(p)) {
                        var year = p.slice(0,4);
                        var month = p.slice(4,6);
                        var dateObj = new Date(year + '-' + month + '-01');
                        var namaBulan = dateObj.toLocaleString('id-ID', { month: 'long' });
                        displayPeriode = namaBulan.charAt(0).toUpperCase() + namaBulan.slice(1) + ' ' + year;
                    }
                    // Determine whether all rows are already approved so we can disable the Approve All button
                    var allApproved = Array.isArray(resp.data) && resp.data.length > 0 && resp.data.every(function(r){ return Number(r.status_data) === 1; });
                    // Build title that includes periode and optional jenis premi
                    var titleText = 'Data Peserta Periode ' + displayPeriode;
                    if (typeof jenis !== 'undefined' && jenis !== null && String(jenis) !== '' && String(jenis) !== 'undefined') {
                        titleText += ' - ' + jenis;
                    }
                    Swal.fire({
                        title: titleText,
                        html: html,
                        width:  '65vw',
                        customClass: { popup: 'swal2-modal-peserta' },
                        showCloseButton: true,
                        showCancelButton: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            setTimeout(function() {
                                var dt = $('#modal-peserta-table').DataTable({
                                    pageLength: 10,
                                    ordering: true,
                                    dom: 'Bfrtip',
                                    buttons: [
                                        {
                                            text: '<i class="fa-solid fa-check-double mr-2"></i>Approve All',
                                            className: 'approve-all-btn bg-green-500 hover:bg-green-600 text-white font-semibold rounded-full px-4 py-2 transition flex items-center',
                                            action: function(e, dt, node, config) {
                                                var btn = $(node);
                                                // Tampilkan konfirmasi sebelum melakukan approve all
                                                Swal.fire({
                                                    title: 'Konfirmasi',
                                                    text: 'Yakin ingin menyetujui semua peserta untuk periode ' + titleText + ' ?',
                                                    icon: 'question',
                                                    showCancelButton: true,
                                                    confirmButtonText: 'Ya, Approve Semua',
                                                    cancelButtonText: 'Batal'
                                                }).then(function(result) {
                                                    if (result.isConfirmed) {
                                                        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin mr-2"></i>Memproses...');
                                                        // include jenis if present so approval is scoped by periode + jenis_premi
                                                        var postData = { approve_all: 1, periode: periode };
                                                        if (typeof jenis !== 'undefined' && jenis !== null && String(jenis) !== '') postData.jenis = jenis;
                                                        $.post('api/update_status_peserta.php', postData, function(resp) {
                                                            if (resp && resp.ok) {
                                                                Swal.close();
                                                                // Refresh tabel utama peserta (AJAX) using centralized helper
                                                                if ($('#data-peserta-table').length && $.fn.DataTable.isDataTable('#data-peserta-table')) {
                                                                    showTableLoading && showTableLoading();
                                                                    $.get('api/get_peserta.php', function(resp2) {
                                                                        if (resp2 && resp2.ok && Array.isArray(resp2.data)) {
                                                                            buildMainTableFromData(resp2.data);
                                                                        }
                                                                    }).always(function() {
                                                                        hideTableLoading && hideTableLoading();
                                                                    });
                                                                }
                                                                if (typeof Toast !== 'undefined') {
                                                                    Toast.fire({ icon: 'success', title: 'Semua peserta periode ini sudah di-approve' });
                                                                } else {
                                                                    Swal.fire({ icon: 'success', title: 'Semua peserta periode ini sudah di-approve', timer: 2000, showConfirmButton: false });
                                                                }
                                                                // disable Approve All button now that all are approved (if DataTable/button still present)
                                                                try {
                                                                    dt.button(0).enable(false);
                                                                    btn.addClass('opacity-50 cursor-not-allowed').prop('disabled', true);
                                                                } catch (e) {
                                                                    // ignore if dt/button not available
                                                                }
                                                            } else {
                                                                // reset button and show error
                                                                resetApproveAllButton(btn);
                                                                showErrorNotification('Gagal approve semua peserta');
                                                            }
                                                        }).fail(function() {
                                                            // reset button and show error on network failure
                                                            resetApproveAllButton(btn);
                                                            showErrorNotification('Gagal approve semua peserta');
                                                        });
                                                    }
                                                });
                                            }
                                        },
                                        {
                                            extend: 'excel',
                                            text: '<i class="fa-solid fa-file-excel mr-2" style="font-size:18px;"></i><span class="font-semibold">Excel</span>',
                                            className: 'mr-2 px-6 py-2 border border-blue-300 text-blue-700 font-semibold rounded-full bg-white hover:bg-blue-600 hover:text-white transition duration-150 focus:outline-none flex items-center',
                                            exportOptions: {
                                                columns: [0,1,2,3,4,5],
                                                format: {
                                                    body: function (data, row, column, node) {
                                                        if (column === 3) {
                                                            var num = String(data).replace(/[^\d,\.]/g, '').replace(/\.(?=\d{3,})/g, '').replace(',', '.');
                                                            return num;
                                                        }
                                                        if (column === 5) {
                                                            var match = String(data).match(/>(Approved|Not Approved)</);
                                                            return match ? match[1] : data;
                                                        }
                                                        return data;
                                                    }
                                                }
                                            }
                                        },
                                        {
                                            extend: 'pdf',
                                            text: '<i class="fa-solid fa-file-pdf mr-2" style="font-size:18px;"></i><span class="font-semibold">PDF</span>',
                                            className: 'mr-2 px-6 py-2 border border-blue-300 text-blue-700 font-semibold rounded-full bg-white hover:bg-blue-600 hover:text-white transition duration-150 focus:outline-none flex items-center',
                                            exportOptions: { columns: [0,1,2,3,4,5] },
                                            orientation: 'portrait',
                                            pageSize: 'A4',
                                            customize: function(doc) {
                                                doc.defaultStyle.fontSize = 10;
                                                doc.pageMargins = [30, 20, 30, 20];
                                                doc.content[1].table.widths = [
                                                    20,    // No
                                                    80,    // NIK
                                                    50,    // Periode
                                                    75,    // Total Premi
                                                    60,    // PIC
                                                    65,    // Approval
                                                ];
                                                var body = doc.content[1].table.body;
                                                for (var i = 0; i < body.length; i++) {
                                                    for (var j = 0; j < body[i].length; j++) {
                                                        body[i][j].alignment = 'center';
                                                        body[i][j].margin = [0, 4, 0, 4];
                                                    }
                                                }
                                            }
                                        },
                                        {
                                            extend: 'print',
                                            text: '<i class="fa-solid fa-print mr-2" style="font-size:18px;"></i><span class="font-semibold">Print</span>',
                                            className: 'mr-2 px-6 py-2 border border-blue-300 text-blue-700 font-semibold rounded-full bg-white hover:bg-blue-600 hover:text-white transition duration-150 focus:outline-none flex items-center',
                                            exportOptions: { columns: [0,1,2,3,4,5] }
                                        }
                                    ],
                                    order: [[1, 'asc']],
                                    columnDefs: [
                                        { targets: 0, className: 'text-center', orderable: false },
                                        { targets: '_all', className: 'text-center' }
                                    ]
                                });
                                        // After DataTable initialized, set Approve All initial enabled/disabled state
                                        try {
                                            // Button index 0 is the Approve All button we added above
                                            if (allApproved) {
                                                dt.button(0).enable(false);
                                                // visually indicate disabled
                                                $('#modal-peserta-table').closest('.swal2-html-container').find('.approve-all-btn').addClass('opacity-50 cursor-not-allowed').prop('disabled', true);
                                            } else {
                                                dt.button(0).enable(true);
                                                $('#modal-peserta-table').closest('.swal2-html-container').find('.approve-all-btn').removeClass('opacity-50 cursor-not-allowed').prop('disabled', false);
                                            }
                                        } catch (e) {
                                            // ignore if button API not available
                                            console.warn('Could not set Approve All button state', e);
                                        }
                                        // Style tombol approve all agar lebih menonjol
                                        setTimeout(function() {
                                            $('.approve-all-btn').css({
                                                'background-color': '#22c55e',
                                                'color': '#fff',
                                                'border': 'none',
                                                'border-radius': '9999px',
                                                'margin-right': '8px',
                                                'font-weight': '600',
                                                'font-size': '15px',
                                                'box-shadow': '0 1px 4px rgba(0,0,0,0.04)'
                                            }).hover(function(){
                                                $(this).css('background-color','#16a34a');
                                            }, function(){
                                                $(this).css('background-color','#22c55e');
                                            });
                                        }, 200);
                                    }, 100);
                                }
                            });
                } else {
                    Swal.fire({ icon: 'error', title: 'Tidak ada data', text: resp && resp.msg ? resp.msg : '' });
                }
            }).fail(function() {
                hideTableLoading();
                Swal.fire({ icon: 'error', title: 'Gagal memuat data' });
            });
        });

        // Fungsi untuk menampilkan overlay loading
        function showTableLoading() {
            $('#table-loading-overlay').css('display', 'flex');
        }
        function hideTableLoading() {
            $('#table-loading-overlay').hide();
        }
        $(document).ready(function() {
            // Inisialisasi Select2 pada select periode
            $('#periode').select2({
                theme: 'default',
                minimumResultsForSearch: 10,
                width: 'resolve',
                dropdownAutoWidth: true
            });
            var table = $('#data-peserta-table').DataTable({
                pageLength: 10,
                // default order: Periode (index 1) desc (karena kolom No di depan)
                order: [[1, 'desc']],
                autoWidth: false,
                // column widths set to keep stable layout like the screenshot
                columnDefs: [
                    { orderable: false, targets: [0, 6, 7] }, // No, Status, Aksi column
                    { className: 'text-center align-middle', targets: [0,1,2,3,4,5,6,7] },
                    { targets: 0, width: '50px', render: function (data, type, row, meta) { return meta.row + 1; } },
                    { targets: 1, width: '180px' }, // Periode
                    { targets: 2, width: '140px' }, // Jenis Premi
                    { targets: 3, width: '160px' }, // Jumlah Premi Karyawan
                    { targets: 4, width: '160px' }, // Jumlah Premi PT
                    { targets: 5, width: '160px' }, // Total Premi
                    { targets: 6, width: '120px' }, // Status
                    { targets: 7, width: '150px', visible: true, orderable: false, searchable: false }, // Aksi
                ],
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excel',
                        text: '<i class="fa-solid fa-file-excel mr-2" style="font-size:18px;"></i><span class="font-semibold">Excel</span>',
                        className: 'mr-2 px-6 py-2 border border-blue-300 text-blue-700 font-semibold rounded-full bg-white hover:bg-blue-600 hover:text-white transition duration-150 focus:outline-none flex items-center',
                        exportOptions: {
                            columns: [0,1,2,3,4,5,6],
                            format: {
                                body: function (data, row, column, node) {
                                    if (column === 5) {
                                        var num = String(data).replace(/[^\d,\.]/g, '').replace(/\.(?=\d{3,})/g, '').replace(',', '.');
                                        return num;
                                    }
                                    if (column === 6) {
                                        var match = String(data).match(/>(Approved|Not Approved)</) || String(data).match(/>(Verified|Not Verified)</);
                                        return match ? match[1] : data;
                                    }
                                    return data;
                                }
                            }
                        }
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fa-solid fa-file-pdf mr-2" style="font-size:18px;"></i><span class="font-semibold">PDF</span>',
                        className: 'mr-2 px-6 py-2 border border-blue-300 text-blue-700 font-semibold rounded-full bg-white hover:bg-blue-600 hover:text-white transition duration-150 focus:outline-none flex items-center',
                        exportOptions: { columns: [0,1,2,3,4,5,6] },
                        orientation: 'portrait',
                        pageSize: 'A4',
                        customize: function(doc) {
                            doc.defaultStyle.fontSize = 10;
                            doc.pageMargins = [30, 20, 30, 20];
                            // Adjust widths to match new columns: No, Periode, Jenis, Jml Karyawan, Jml PT, Total, Status, Aksi
                            doc.content[1].table.widths = [20, 70, 70, 80, 80, 80, 65, 65];
                            var body = doc.content[1].table.body;
                            for (var i = 0; i < body.length; i++) {
                                for (var j = 0; j < body[i].length; j++) {
                                    body[i][j].alignment = 'center';
                                    body[i][j].margin = [0, 4, 0, 4];
                                }
                            }
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="fa-solid fa-print mr-2" style="font-size:18px;"></i><span class="font-semibold">Print</span>',
                        className: 'mr-2 px-6 py-2 border border-blue-300 text-blue-700 font-semibold rounded-full bg-white hover:bg-blue-600 hover:text-white transition duration-150 focus:outline-none flex items-center',
                        exportOptions: { columns: [0,1,2,3,4,5,6] }
                    }
                ]
            });

            // Inisialisasi DataTable untuk Registrasi Peserta jika ada
            if ($('#registrasi-peserta-table').length) {
                $('#registrasi-peserta-table').DataTable({
                    pageLength: 10,
                    order: [[1, 'asc']],
                    columnDefs: [
                        { orderable: false, targets: 0 },
                        { className: 'text-center align-middle', targets: '_all' }
                    ],
                    dom: 'Bfrtip',
                    buttons: [
                        {
                            extend: 'excel',
                            text: '<i class="fa-solid fa-file-excel mr-2" style="font-size:18px;"></i><span class="font-semibold">Excel</span>',
                            className: 'mr-2 px-6 py-2 border border-blue-300 text-blue-700 font-semibold rounded-full bg-white hover:bg-blue-600 hover:text-white transition duration-150 focus:outline-none flex items-center',
                            exportOptions: {
                                columns: [0,1,2,3,4,5,6,7,8,9],
                                    format: {
                                    body: function (data, row, column, node) {
                                        if (column === 7) {
                                            var match = String(data).match(/>(Verified|Not Verified)</);
                                            return match ? match[1] : data;
                                        }
                                    return data;
                                    }
                                }
                            }   
                        },
                        {
                            extend: 'pdf',
                            text: '<i class="fa-solid fa-file-pdf mr-2" style="font-size:18px;"></i><span class="font-semibold">PDF</span>',
                            className: 'mr-2 px-6 py-2 border border-blue-300 text-blue-700 font-semibold rounded-full bg-white hover:bg-blue-600 hover:text-white transition duration-150 focus:outline-none flex items-center',
                            exportOptions: { columns: [0,1,2,3,4,5,6,7,8,9] },
                            orientation: 'portrait',
                            pageSize: 'A4',
                            customize: function(doc) {
                                doc.defaultStyle.fontSize = 10;
                                doc.pageMargins = [30, 20, 30, 20];
                                // Adjust column widths roughly
                                doc.content[1].table.widths = [20, 80, 100, 35, 60, 80, 60, 60, 70, 100];
                                var body = doc.content[1].table.body;
                                for (var i = 0; i < body.length; i++) {
                                    for (var j = 0; j < body[i].length; j++) {
                                        body[i][j].alignment = 'center';
                                        body[i][j].margin = [0, 4, 0, 4];
                                    }
                                }
                            }
                        },
                        {
                            extend: 'print',
                            text: '<i class="fa-solid fa-print mr-2" style="font-size:18px;"></i><span class="font-semibold">Print</span>',
                            className: 'mr-2 px-6 py-2 border border-blue-300 text-blue-700 font-semibold rounded-full bg-white hover:bg-blue-600 hover:text-white transition duration-150 focus:outline-none flex items-center',
                            exportOptions: { columns: [0,1,2,3,4,5,6,7,8,9] }
                        }
                    ]
                });
            }

            // Approve All Registrasi button handler
            $(document).on('click', '#approve-all-registrasi-btn', function(e) {
                e.preventDefault();
                var btn = $(this);
                Swal.fire({
                    title: 'Setujui semua registrasi? ',
                    text: 'Aksi ini akan menandai semua akun yang belum diverifikasi sebagai terverifikasi.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Setujui Semua',
                    cancelButtonText: 'Batal'
                }).then(function(res) {
                    if (!res.isConfirmed) return;
                    btn.prop('disabled', true).text('Memproses...');
                    $.post('api/approve_registrasi_all.php')
                        .done(function(resp) {
                            if (resp && resp.ok) {
                                Toast.fire({ icon: 'success', title: 'Semua registrasi yang belum diverifikasi telah disetujui' });
                                try {
                                    var userVerify = resp.audit && resp.audit.user_verify ? resp.audit.user_verify : '&mdash;';
                                    var tgl = resp.audit && resp.audit.tgl ? resp.audit.tgl : null;
                                    $('#registrasi-peserta-table tbody tr').each(function() {
                                        var row = $(this);
                                        var verifCell = row.find('td').eq(7);
                                        // If already verified, skip
                                        if (verifCell.find('span').text().trim().toLowerCase() === 'verified') return;
                                        verifCell.html('<span class="px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-green-100 text-green-800">Verified</span>');
                                        var approvedByCell = row.find('td').eq(8);
                                        var approvedAtCell = row.find('td').eq(9);
                                        approvedByCell.html(userVerify);
                                        if (tgl) {
                                            var dt = new Date(tgl);
                                            if (!isNaN(dt.getTime())) {
                                                var dd = ('0' + dt.getDate()).slice(-2);
                                                var mm = ('0' + (dt.getMonth() + 1)).slice(-2);
                                                var yyyy = dt.getFullYear();
                                                approvedAtCell.html(dd + '-' + mm + '-' + yyyy);
                                            } else {
                                                var s = String(tgl).trim();
                                                var datePart = s.split(' ')[0];
                                                var m = datePart.match(/^(\d{4})-(\d{2})-(\d{2})$/);
                                                if (m) approvedAtCell.html(m[3] + '-' + m[2] + '-' + m[1]); else approvedAtCell.html(tgl);
                                            }
                                        } else {
                                            approvedAtCell.html('&mdash;');
                                        }
                                    });
                                    // refresh main peserta table
                                    if ($('#data-peserta-table').length && $.fn.DataTable.isDataTable('#data-peserta-table')) {
                                        showTableLoading();
                                        $.get('api/get_peserta.php', function(resp2) {
                                            if (resp2 && resp2.ok && Array.isArray(resp2.data)) {
                                                buildMainTableFromData(resp2.data);
                                            }
                                        }).always(function() { hideTableLoading(); });
                                    }
                                } catch (e) { console.warn('Could not update registrasi table after approve all', e); }
                            } else {
                                Toast.fire({ icon: 'error', title: resp && resp.error ? resp.error : 'Gagal approve all' });
                                btn.prop('disabled', false).text('Approve All');
                            }
                        })
                        .fail(function() { Toast.fire({ icon: 'error', title: 'Terjadi kesalahan jaringan' }); btn.prop('disabled', false).text('Approve All'); });
                });
            });

            // Approve registrasi (delegated handler)
            $(document).on('click', '.btn-approve-registrasi', function(e) {
                e.preventDefault();
                var btn = $(this);
                var id = btn.data('id');
                if (!id) return;
                Swal.fire({
                    title: 'Setujui akun ini?',
                    text: 'Aksi ini akan menandai akun sebagai terverifikasi dan membuat entri data_peserta (status_data=1).',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Setujui',
                    cancelButtonText: 'Batal'
                }).then(function(result) {
                    if (!result.isConfirmed) return;
                    btn.prop('disabled', true).text('Memproses...');
                    $.post('api/approve_registrasi.php', { id: id })
                        .done(function(resp) {
                                if (resp && resp.ok) {
                                Toast.fire({ icon: 'success', title: 'Akun diverifikasi' });
                                // Update registrasi row in-place: change Verifikasi badge and remove Approve button
                                try {
                                    var row = btn.closest('tr');
                                    // Verifikasi column is now index 7 (0-based)
                                    var verifCell = row.find('td').eq(7);
                                    verifCell.html('<span class="px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-green-100 text-green-800">Verified</span>');
                                    // Approved By column is index 8
                                    var approvedByCell = row.find('td').eq(8);
                                    // Approved At column is index 9
                                    var approvedAtCell = row.find('td').eq(9);
                                    // fill values from response audit if available
                                    if (resp.audit) {
                                        var userVerify = resp.audit.user_verify || '&mdash;';
                                        var tgl = resp.audit.tgl || null;
                                        approvedByCell.html(userVerify);
                                        if (tgl) {
                                            // show date only (DD-MM-YYYY)
                                            var dt = new Date(tgl);
                                            if (!isNaN(dt.getTime())) {
                                                var dd = ('0' + dt.getDate()).slice(-2);
                                                var mm = ('0' + (dt.getMonth() + 1)).slice(-2);
                                                var yyyy = dt.getFullYear();
                                                approvedAtCell.html(dd + '-' + mm + '-' + yyyy);
                                            } else {
                                                // try to extract YYYY-MM-DD from string and reformat
                                                var s = String(tgl).trim();
                                                var datePart = s.split(' ')[0];
                                                var m = datePart.match(/^(\d{4})-(\d{2})-(\d{2})$/);
                                                if (m) {
                                                    approvedAtCell.html(m[3] + '-' + m[2] + '-' + m[1]);
                                                } else {
                                                    approvedAtCell.html(tgl);
                                                }
                                            }
                                        } else {
                                            approvedAtCell.html('&mdash;');
                                        }
                                    }
                                    // no per-row aksi column (Approve All handles approvals)
                                } catch (e) {
                                    console.warn('Could not update registrasi row inline', e);
                                }

                                // Refresh main data_peserta table via AJAX
                                try {
                                    if ($('#data-peserta-table').length && $.fn.DataTable.isDataTable('#data-peserta-table')) {
                                        showTableLoading();
                                        $.get('api/get_peserta.php', function(resp2) {
                                            if (resp2 && resp2.ok && Array.isArray(resp2.data)) {
                                                buildMainTableFromData(resp2.data);
                                            }
                                        }).always(function() { hideTableLoading(); });
                                    }
                                } catch (e) { console.warn('Could not refresh main peserta table', e); }

                                // Refresh periode table (list of periods)
                                try { if (typeof loadPeriodeTable === 'function') loadPeriodeTable(); } catch(e) {}

                            } else {
                                Toast.fire({ icon: 'error', title: resp && resp.error ? resp.error : 'Gagal verifikasi' });
                                btn.prop('disabled', false).text('Approve');
                            }
                        })
                        .fail(function() {
                            Toast.fire({ icon: 'error', title: 'Terjadi kesalahan jaringan' });
                            btn.prop('disabled', false).text('Approve');
                        });
                });
            });

            // Filter DataTable berdasarkan periode
            $('#periode').on('change', function() {
                var val = $(this).val();
                // Kolom Periode ada di index 1 (setelah No)
                if (val) {
                    table.column(1).search('^' + val + '$', true, false).draw();
                } else {
                    table.column(1).search('', true, false).draw();
                }
            });

            // SweetAlert2 Toast mixin
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });

            // delegate click to handle dynamic table content with confirmation
            $(document).on('click', '.approve-btn', function(e) {
                e.preventDefault();
                var btn = $(this);
                var id = btn.data('id');
                var status = parseInt(btn.data('status')) ? 1 : 0;
                var newStatus = status ? 0 : 1;

                var confirmText = newStatus === 1 ? 'Setujui peserta ini?' : 'Batalkan persetujuan peserta ini?';

                Swal.fire({
                    title: confirmText,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya',
                    cancelButtonText: 'Batal'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        btn.prop('disabled', true).text('Diproses...');

                        $.post('api/update_status_peserta.php', { id: id, status: newStatus })
                            .done(function(resp) {
                                if (resp && resp.ok) {
                                    // Otomatis refresh tabel AJAX agar data dan status langsung update
                                    showTableLoading();
                                    $.get('api/get_peserta.php', function(resp2) {
                                        if (resp2 && resp2.ok && Array.isArray(resp2.data)) {
                                            buildMainTableFromData(resp2.data);
                                        }
                                    }).always(function() {
                                        hideTableLoading();
                                    });
                                    Toast.fire({ icon: 'success', title: (newStatus === 1 ? 'Peserta disetujui' : 'Persetujuan dibatalkan') });
                                } else {
                                    Toast.fire({ icon: 'error', title: 'Gagal menyimpan status' });
                                }
                            })
                            .fail(function(xhr) {
                                Toast.fire({ icon: 'error', title: 'Terjadi kesalahan saat menyimpan' });
                            })
                            .always(function() {
                                btn.prop('disabled', false);
                            });
                    }
                });
            });

            // Handler edit data peserta
            
            // Handler hapus data peserta
            $(document).on('click', '.btn-delete-data', function(e) {
                e.preventDefault();
                var btn = $(this);
                var id = btn.data('id');
                Swal.fire({
                    title: 'Hapus Data Peserta?',
                    text: 'Data yang dihapus tidak dapat dikembalikan!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#d33',
                }).then(function(result) {
                    if (result.isConfirmed) {
                        $.post('api/delete_peserta.php', { id: id })
                            .done(function(resp) {
                                if (resp && resp.ok) {
                                    Swal.fire({ icon: 'success', title: 'Data berhasil dihapus', timer: 1500, showConfirmButton: false });
                                    // Refresh tabel data peserta setelah delete berhasil
                                    setTimeout(function() {
                                        refreshDataPesertaTable();
                                    }, 1000);
                                } else {
                                    Swal.fire({ icon: 'error', title: 'Gagal menghapus data', text: resp && resp.msg ? resp.msg : '' });
                                }
                            })
                            .fail(function(xhr) {
                                console.error('Delete peserta failed:', xhr);
                                Swal.fire({ icon: 'error', title: 'Terjadi kesalahan koneksi' });
                            });
                    }
                });
            });

            // Fungsi reusable untuk refresh tabel data peserta
            function refreshDataPesertaTable() {
                if (!$.fn.DataTable || !$.fn.DataTable.isDataTable('#data-peserta-table')) {
                    console.warn('DataTable #data-peserta-table not initialized yet');
                    return;
                }
                showTableLoading();
                $.get('api/get_peserta.php', function(resp) {
                    hideTableLoading();
                    if (resp && resp.ok && Array.isArray(resp.data)) {
                        buildMainTableFromData(resp.data);
                        // Refresh periode tabel juga
                        try { if (typeof loadPeriodeTable === 'function') loadPeriodeTable(); } catch(e) { console.warn('loadPeriodeTable error:', e); }
                    } else {
                        console.error('get_peserta.php returned invalid response:', resp);
                        Toast.fire({ icon: 'error', title: 'Gagal memuat data peserta' });
                    }
                })
                .fail(function(xhr) {
                    hideTableLoading();
                    console.error('get_peserta.php request failed:', xhr);
                    Toast.fire({ icon: 'error', title: 'Terjadi kesalahan saat memuat data' });
                });
            }

            // Expose fungsi global untuk digunakan di tempat lain jika diperlukan
            window.refreshDataPesertaTable = refreshDataPesertaTable;
        });