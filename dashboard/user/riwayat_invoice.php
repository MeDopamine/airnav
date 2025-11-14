<?php
include_once __DIR__ . '/../../auth.php';
require_login();

$user = current_user();

// load dashboard partials
include_once __DIR__ . '/../partials/_init.php';
require_once __DIR__ . '/../../db/db.php';

// determine approval state: check latest data_peserta row for this user (by userid varchar)
$approved = 0;
$stmtApp = mysqli_prepare($conn, 'SELECT status_data FROM data_peserta WHERE userid = ? ORDER BY id DESC LIMIT 1');
if ($stmtApp) {
    $userid_str = (string)$user['id'];
    mysqli_stmt_bind_param($stmtApp, 's', $userid_str);
    mysqli_stmt_execute($stmtApp);
    mysqli_stmt_bind_result($stmtApp, $status_data_val);
    if (mysqli_stmt_fetch($stmtApp)) {
        $approved = ((int)$status_data_val === 1) ? 1 : 0;
    }
    mysqli_stmt_close($stmtApp);
}
if (!$approved) {
    $sreg = mysqli_prepare($conn, 'SELECT is_verify FROM registrasi_peserta WHERE email = ? ORDER BY id DESC LIMIT 1');
    if ($sreg) {
        $email = $user['email'] ?? '';
        mysqli_stmt_bind_param($sreg, 's', $email);
        mysqli_stmt_execute($sreg);
        $r = mysqli_stmt_get_result($sreg);
        $rrow = mysqli_fetch_assoc($r);
        mysqli_stmt_close($sreg);
        if ($rrow && !empty($rrow['is_verify'])) {
            $approved = 1;
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Riwayat Invoice - User</title>
    <!-- Font Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/tailwind.output.css">
    <!-- DataTables CSS to ensure pagination/length UI are styled properly -->
    <link rel="stylesheet" href="../assets/css/jquery.dataTables.min.css">
    <link rel="icon" href="https://placehold.co/32x32/0033A0/FFFFFF?text=R" type="image/png">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        <?php render_partial('sidebar_user'); ?>

        <div class="flex-1 flex flex-col overflow-hidden">
            <?php render_partial('header'); ?>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6 md:p-8">
                <div class="max-w-5xl mx-auto space-y-6">
                    <div class="bg-white rounded-xl shadow-md p-6 md:p-8 relative">
                        <?php if (!$approved): ?>
                            <div class="absolute inset-0 flex items-center justify-center rounded-lg" style="background: rgba(255,255,255,0.8); backdrop-filter: blur(5px); -webkit-backdrop-filter: blur(5px); z-index: 20;">
                                <div class="text-center max-w-lg px-6">
                                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-yellow-100 mx-auto mb-4">
                                        <i class="fa-solid fa-triangle-exclamation fa-fade text-yellow-600 text-2xl" style="color:#D97706;"></i>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-800">Menunggu Verifikasi</h3>
                                    <p class="text-sm text-gray-600 mt-2">Akun Anda sedang menunggu verifikasi oleh admin. Invoice akan tersedia setelah akun Anda terverifikasi.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                        <h2 class="text-2xl font-semibold mb-2">Riwayat Invoice</h2>
                        <p class="text-gray-600 mb-4">Daftar riwayat Invoice berdasarkan periode, dipisah berdasarkan status</p>

                        <!-- Pending section -->
                        <div id="section-pending" class="bg-white border rounded-md mb-6">
                            <div class="px-6 py-4 border-b bg-gray-50">
                                <h3 class="text-lg font-semibold flex items-center">Invoice Pending <span id="pending-count" class="count-badge pending ml-2">0</span></h3>
                            </div>
                            <div id="pending-body" class="p-6">
                                <!-- If no pending, show placeholder. If has items, we'll inject a table here. -->
                                <div id="pending-placeholder" class="text-center text-gray-500">
                                    <div class="py-8">
                                            <i class="fa-solid fa-hourglass-half text-4xl text-gray-400"></i>
                                            <div class="mt-4 text-lg font-semibold">Tidak Ada Invoice Pending</div>
                                            <div class="mt-2 text-sm text-gray-500">Semua invoice sudah diverifikasi atau belum ada data</div>
                                        </div>
                                </div>
                                <div id="pending-table-wrap" style="display:none;">
                                    <div class="overflow-x-auto">
                                        <table id="tbl-pending" class="w-full text-sm">
                                                <thead class="text-xs text-gray-500 text-left">
                                                    <tr>
                                                        <th class="px-3 py-2">No</th>
                                                        <th class="px-3 py-2">Periode</th>
                                                        <th class="px-3 py-2">Jenis Premi</th>
                                                        <th class="px-3 py-2">No. Invoice</th>
                                                        <th class="px-3 py-2">Tanggal Invoice</th>
                                                        <th class="px-3 py-2 text-right">Jumlah Premi Karyawan</th>
                                                        <th class="px-3 py-2 text-right">Jumlah Peserta</th>
                                                        <th class="px-3 py-2 text-right">Total Premi</th>
                                                        <th class="px-3 py-2">PIC</th>
                                                        <th class="px-3 py-2">Status</th>
                                                        <th class="px-3 py-2">Aksi</th>
                                                    </tr>
                                                </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Approved section -->
                        <div id="section-approved" class="bg-white border rounded-md">
                            <div class="px-6 py-4 border-b bg-gray-50">
                                <h3 class="text-lg font-semibold">Invoice Diverifikasi <span id="approved-count" class="text-sm text-gray-500 ml-2">0</span></h3>
                            </div>
                            <div class="p-6">
                                <div class="overflow-x-auto">
                                    <table id="tbl-approved" class="w-full text-sm">
                                        <thead class="text-xs text-gray-500 text-left">
                                            <tr>
                                                <th class="px-3 py-2">No</th>
                                                <th class="px-3 py-2">Periode</th>
                                                <th class="px-3 py-2">Jenis Premi</th>
                                                <th class="px-3 py-2">No. Invoice</th>
                                                <th class="px-3 py-2">Tanggal Invoice</th>
                                                <th class="px-3 py-2 text-right">Jumlah Premi Karyawan</th>
                                                <th class="px-3 py-2 text-right">Jumlah Peserta</th>
                                                <th class="px-3 py-2 text-right">Total Premi</th>
                                                <th class="px-3 py-2">PIC</th>
                                                <th class="px-3 py-2">Status</th>
                                                <th class="px-3 py-2">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php render_partial('footer'); ?>

    <script src="../../assets/js/jquery-3.6.0.min.js"></script>
    <script src="../../assets/js/jquery.dataTables.min.js"></script>
    <style>
        /* Small visual polish for DataTables controls to match Tailwind spacing */
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            margin: 0 6px;
        }
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 0.5rem;
        }
        /* Make pagination buttons slightly larger and rounded to match dashboard style */
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 6px 10px;
            border-radius: 6px;
        }
        /* Count badge styles */
        .count-badge {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 2em;
            height: 2em;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 600;
            vertical-align: middle;
            transition: transform 0.18s ease, opacity 0.18s ease;
        }
        .count-badge.pending { background-color: #fef3c7; color: #92400e; border: 1px solid #f59e0b; }
        .count-badge.approved { background-color: #dcfce7; color: #14532d; border: 1px solid #22c55e; }

        /* Fade-in animation for placeholder and tables */
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeInUp 360ms ease both; }
        .row-highlight { transition: background-color 300ms ease, box-shadow 300ms ease; }
    </style>

    <script>
        (function(){
            function formatCurrency(num){
                return 'Rp ' + Number(num).toLocaleString('id-ID');
            }
            function formatDateIndo(dtStr){
                if (!dtStr) return '';
                // try to parse YYYY-MM-DD or MySQL datetime
                var d = new Date(dtStr);
                if (isNaN(d.getTime())){
                    // try manual parse from yyyy-mm-dd hh:mm:ss
                    var m = dtStr.match(/(\d{4})-(\d{2})-(\d{2})/);
                    if (!m) return dtStr;
                    d = new Date(m[1], parseInt(m[2],10)-1, m[3]);
                }
                var namaBulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
                return (('0'+d.getDate()).slice(-2)) + ' ' + namaBulan[d.getMonth()] + ' ' + d.getFullYear();
            }

            function formatNumber(n){
                if (n === null || n === undefined || n === '') return '';
                var num = Number(n);
                if (isNaN(num)) return n;
                return num.toLocaleString('id-ID');
            }

            function formatCurrencyRp(n){
                if (n === null || n === undefined || n === '') return '';
                var num = Number(n);
                if (isNaN(num)) return n;
                return 'Rp ' + num.toLocaleString('id-ID');
            }

            function renderInvoiceRows($tbody, rows){
                $tbody.empty();
                rows.forEach(function(r){
                    var rowId = (r.no_invoice && String(r.no_invoice).trim()) ? r.no_invoice : ('id-' + (r.no || ''));
                    var noCell = r.no || '';
                    var periodeCell = r.periode || '';
                    var jenisCell = r.jenis_premi || '';
                    var noInvCell = r.no_invoice || '';
                    var tglCell = r.tgl_invoice || '';
                    var jmlKrywnCell = (r.jml_premi_krywn !== undefined && r.jml_premi_krywn !== null) ? formatCurrencyRp(r.jml_premi_krywn) : '';
                    var jmlPesertaCell = r.jumlah_peserta || '';
                    var totalCell = (r.total_premi && r.total_premi.display) ? r.total_premi.display : '';
                    var picCell = r.pic || r.PIC || '';
                    var statusCell = r.status || '';
                    var actionsCell = r.actions || '';

                    var tr = '<tr class="border-t row-highlight" id="row-' + rowId + '">' +
                        '<td class="px-3 py-2">' + noCell + '</td>' +
                        '<td class="px-3 py-2">' + periodeCell + '</td>' +
                        '<td class="px-3 py-2">' + jenisCell + '</td>' +
                        '<td class="px-3 py-2">' + noInvCell + '</td>' +
                        '<td class="px-3 py-2">' + tglCell + '</td>' +
                        '<td class="px-3 py-2 text-right">' + jmlKrywnCell + '</td>' +
                        '<td class="px-3 py-2 text-right">' + jmlPesertaCell + '</td>' +
                        '<td class="px-3 py-2 text-right font-semibold">' + totalCell + '</td>' +
                        '<td class="px-3 py-2">' + picCell + '</td>' +
                        '<td class="px-3 py-2">' + statusCell + '</td>' +
                        '<td class="px-3 py-2">' + actionsCell + '</td>' +
                        '</tr>';
                    $tbody.append(tr);
                });
            }

            function loadRiwayat(){
                $.getJSON('../api/get_user_invoices.php').done(function(resp){
                    var data = resp && resp.data ? resp.data : [];
                    // Split by status: Pending invoices in pending section, Verified/Revision in approved section
                    var pending = data.filter(function(x){ return String(x.status).indexOf('Pending') !== -1; }).slice(0, 3);
                    var approved = data.filter(function(x){ return (String(x.status).indexOf('Verified') !== -1 || String(x.status).indexOf('Revision') !== -1); }).slice(0, 3);

                    // update counts
                    try { $('#pending-count').text(pending.length); $('#pending-count').removeClass('fade-in').addClass('count-badge pending fade-in'); setTimeout(function(){ $('#pending-count').removeClass('fade-in'); }, 600); } catch(e){}
                    try { $('#approved-count').text(approved.length); $('#approved-count').removeClass('fade-in').addClass('count-badge approved fade-in'); setTimeout(function(){ $('#approved-count').removeClass('fade-in'); }, 600); } catch(e){}

                    if (pending.length === 0) {
                        if ($.fn.DataTable.isDataTable('#tbl-pending')){ try { $('#tbl-pending').DataTable().clear().destroy(); } catch(e){} }
                        $('#pending-table-wrap').hide();
                        $('#pending-placeholder').addClass('fade-in').show();
                        setTimeout(function(){ $('#pending-placeholder').removeClass('fade-in'); }, 420);
                    } else {
                        $('#pending-placeholder').hide();
                        renderInvoiceRows($('#tbl-pending tbody'), pending);
                        $('#pending-table-wrap').show().addClass('fade-in');
                        setTimeout(function(){ $('#pending-table-wrap').removeClass('fade-in'); }, 420);
                        if ($.fn.DataTable.isDataTable('#tbl-pending')){ $('#tbl-pending').DataTable().rows().invalidate().draw(false); } else {
                            $('#tbl-pending').DataTable({ paging: false, searching: false, info: false, lengthChange: false, order: [[4, 'desc']], columnDefs: [ { targets: [5,6,7], className: 'text-right' } ], language: { emptyTable: 'Belum ada data' } });
                        }
                    }

                    renderInvoiceRows($('#tbl-approved tbody'), approved);
                    if ($.fn.DataTable.isDataTable('#tbl-approved')){ $('#tbl-approved').DataTable().rows().invalidate().draw(false); } else {
                        $('#tbl-approved').DataTable({ paging: false, searching: false, info: false, lengthChange: false, order: [[4, 'desc']], columnDefs: [ { targets: [5,6,7], className: 'text-right' } ], language: { emptyTable: 'Belum ada data' } });
                    }

                    // highlight new_id if present
                    try {
                        var params = new URLSearchParams(window.location.search);
                        var newId = params.get('new_id');
                        if (newId) {
                            setTimeout(function(){
                                var el = document.getElementById('row-' + newId) || document.querySelector('[data-id="' + newId + '"]');
                                if (el) {
                                    el.classList.add('bg-yellow-50','ring-2','ring-yellow-300');
                                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                    setTimeout(function(){ $(el).removeClass('bg-yellow-50 ring-2 ring-yellow-300'); }, 4000);
                                    try { params.delete('new_id'); var newQuery = params.toString(); var newUrl = window.location.pathname + (newQuery ? '?' + newQuery : ''); history.replaceState(null, document.title, newUrl); } catch (ee) {}
                                }
                            }, 350);
                        }
                    } catch(e) {}

                    // Bind approval button handlers
                    bindApprovalButtons();
                }).fail(function(){
                    $('#pending-placeholder').show();
                    $('#pending-table-wrap').hide();
                });
            }

            function bindApprovalButtons(){
                // Verify button handler
                $(document).off('click', '.btn-verify').on('click', '.btn-verify', function(e){
                    e.preventDefault();
                    var invoiceId = $(this).data('id');
                    var invoiceNo = $(this).data('invoice');
                    
                    Swal.fire({
                        title: 'Verifikasi Invoice',
                        html: 'Apakah Anda yakin ingin memverifikasi invoice <strong>' + invoiceNo + '</strong>?',
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Verifikasi',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#22c55e',
                        cancelButtonColor: '#6b7280'
                    }).then(function(result){
                        if (result.isConfirmed) {
                            submitApproval(invoiceId, 'verify');
                        }
                    });
                });

                // Revise button handler
                $(document).off('click', '.btn-revise').on('click', '.btn-revise', function(e){
                    e.preventDefault();
                    var invoiceId = $(this).data('id');
                    var invoiceNo = $(this).data('invoice');
                    
                    Swal.fire({
                        title: 'Minta Revisi Invoice',
                        html: 'Apakah Anda yakin ingin meminta revisi untuk invoice <strong>' + invoiceNo + '</strong>?<br><br><label style="text-align: left; display: block;">Catatan Revisi (opsional):</label>',
                        input: 'textarea',
                        inputPlaceholder: 'Tuliskan alasan atau detail revisi yang diperlukan...',
                        inputAttributes: { style: 'height: 100px; margin-top: 10px;' },
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Minta Revisi',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#6b7280'
                    }).then(function(result){
                        if (result.isConfirmed) {
                            submitApproval(invoiceId, 'revise', result.value);
                        }
                    });
                });
            }

            function submitApproval(invoiceId, action, notes){
                var btn = '[data-id="' + invoiceId + '"][class*="btn-' + action + '"]';
                $(btn).prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');

                $.ajax({
                    url: '/dashboard/api/approve_user_invoice.php',
                    method: 'POST',
                    data: { id: invoiceId, action: action, notes: notes || '' },
                    dataType: 'json',
                    success: function(resp){
                        if (resp.ok) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: 'Invoice ' + (action === 'verify' ? 'terverifikasi' : 'permintaan revisi dikirim') + ' dengan baik',
                                confirmButtonColor: '#3b82f6'
                            }).then(function(){
                                // Reload table to reflect new status
                                loadRiwayat();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: resp.msg || 'Terjadi kesalahan',
                                confirmButtonColor: '#3b82f6'
                            });
                            $(btn).prop('disabled', false).html('<i class="fa-solid fa-' + (action === 'verify' ? 'check' : 'redo') + '"></i><span>' + (action === 'verify' ? 'Verify' : 'Revise') + '</span>');
                        }
                    },
                    error: function(){
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Gagal menghubungi server',
                            confirmButtonColor: '#3b82f6'
                        });
                        $(btn).prop('disabled', false).html('<i class="fa-solid fa-' + (action === 'verify' ? 'check' : 'redo') + '"></i><span>' + (action === 'verify' ? 'Verify' : 'Revise') + '</span>');
                    }
                });
            }

            $(document).ready(function(){ loadRiwayat(); });
        })();
    </script>
</body>
</html>
