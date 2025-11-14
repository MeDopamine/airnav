<?php 
include '../db/db.php';
// load partials helper for render_partial()
include_once __DIR__ . '/partials/_init.php';
// require login
include_once __DIR__ . '/../auth.php';
require_login();
// only allow admin to access main dashboard; regular users go to their profile
if (!is_admin()) {
    header('Location: user/dashboard.php');
    exit;
}
?>                           
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Upload AirNav</title>
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="assets/css/tailwind.output.css">
    <!-- Font Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icon -->
    <link rel="icon" href="https://placehold.co/32x32/0033A0/FFFFFF?text=A" type="image/png">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body class="bg-gray-100">

    <div class="flex h-screen overflow-hidden">
        <?php render_partial('sidebar'); ?>

        <!-- Konten Utama -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <?php render_partial('header'); ?>

            <!-- Area Konten Utama -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6 md:p-8">
                <!-- Grid untuk Kartu Upload -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-8">
                    
                    <!-- Kartu Upload Data Peserta -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="p-6">
                            <h2 class="text-xl font-semibold text-gray-800">Upload Data Peserta</h2>
                            <p class="text-sm text-gray-500 mt-2">
                                Upload file Excel (.xlsx, .xls) yang berisi data peserta baru.
                            </p>
                        </div>
                        <div class="px-6 pb-6">
                            <!-- Komponen Upload File -->
                            <div class="upload-zone" data-zone-id="peserta">
                                <label for="peserta-upload" class="flex flex-col items-center justify-center w-full h-48 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                        <div class="w-10 h-10 mb-3 text-gray-400">
                                            <i class="fa-solid fa-upload fa-2xl"></i>
                                        </div>
                                        <p class="mb-2 text-sm text-gray-500 text-center">
                                            <span class="font-semibold">Seret & lepas file</span> atau klik untuk memilih
                                        </p>
                                        <p class="text-xs text-gray-500">XLSX, XLS</p>
                                    </div>
                                    <input id="peserta-upload" type="file" class="hidden" accept=".xlsx, .csv" />
                                </label>
                            </div>
                            <!-- Tampilan file terpilih -->
                            <div id="peserta-filename" class="mt-4 text-sm text-gray-600 font-medium hidden">
                                File terpilih: <span></span>
                            </div>
                            <button class="mt-4 w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-300">
                                <i class="fa-solid fa-file-import mr-2"></i>Upload Data Peserta
                            </button>
                        </div>
                    </div>

                    <!-- Kartu Upload Invoice -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="p-6">
                            <h2 class="text-xl font-semibold text-gray-800">Upload Invoice</h2>
                            <p class="text-sm text-gray-500 mt-2">
                                Tambahkan invoice baru untuk periode yang dibutuhkan.
                            </p>
                        </div>
                        <div class="px-6 pb-6">
                            <button id="btn-upload-invoice" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-300">
                                <i class="fa-solid fa-file-import mr-2"></i>Upload Invoice
                            </button>

                            <!-- Tabel Invoice Pending & Revisi -->
                            <div class="mt-6 overflow-x-auto">
                                <table id="invoice-pending-table" class="w-full text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periode</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis Premi</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Invoice</th>
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="invoice-pending-tbody" class="bg-white divide-y divide-gray-200">
                                        <!-- Data loaded via AJAX -->
                                    </tbody>
                                </table>
                                <div id="invoice-pending-empty" class="text-center py-8 text-gray-500">
                                    <i class="fa-solid fa-inbox text-3xl mb-2 opacity-50"></i>
                                    <p class="text-sm">Tidak ada invoice pending atau revisi</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabel Riwayat Upload -->
                <div class="mt-8 bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-800">Riwayat Upload Terkini</h2>
                        <button id="btn-lihat-semua-riwayat" class="ml-auto bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold px-4 py-2 rounded-lg text-sm transition duration-200">Lihat Semua Riwayat</button>
                    </div>
                    <!-- Modal Riwayat Upload dihandle oleh SweetAlert2 -->
                    <!-- Kontainer untuk scroll horizontal di mobile -->
                    <div class="overflow-x-auto" tabindex="0">
                        <table class="w-full min-w-max">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Nama File
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Tipe Data
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Tanggal Upload
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="riwayat-upload-tbody" class="bg-white divide-y divide-gray-200">
                            </tbody>
                            <script>
                                // Render riwayat upload dinamis
                                function formatTanggalIndo(dt) {
                                        if (!dt) return '';
                                        const bulan = [
                                                '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                                                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
                                        ];
                                        const d = new Date(dt.replace(' ', 'T'));
                                        if (isNaN(d)) return dt;
                                        return `${d.getDate().toString().padStart(2, '0')} ${bulan[d.getMonth()+1]} ${d.getFullYear()}, ${d.getHours().toString().padStart(2, '0')}:${d.getMinutes().toString().padStart(2, '0')}`;
                                }
                                function statusBadge(status) {
                                        let color = 'bg-gray-200 text-gray-700', label = status;
                                        if (status === 'Berhasil') color = 'bg-green-100 text-green-800';
                                        else if (status === 'Gagal') color = 'bg-red-100 text-red-800';
                                        else if (status === 'Diproses') color = 'bg-yellow-100 text-yellow-800';
                                        return `<span class=\"px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${color}\">${label}</span>`;
                                }
                                function refreshRiwayatUpload() {
                                    fetch('api/get_riwayat_upload.php')
                                        .then(r => {
                                            if (!r.ok) {
                                                if (r.status === 401 || r.status === 403) {
                                                    Swal.fire({ toast: true, position: 'top', icon: 'warning', title: 'Akses ditolak atau sesi berakhir. Silakan masuk kembali.', showConfirmButton: false, timer: 3500, timerProgressBar: true });
                                                } else {
                                                    Swal.fire({ toast: true, position: 'top', icon: 'error', title: 'Gagal memuat riwayat upload.', showConfirmButton: false, timer: 3500, timerProgressBar: true });
                                                }
                                                return [];
                                            }
                                            return r.json();
                                        })
                                        .then(data => {
                                            const tbody = document.getElementById('riwayat-upload-tbody');
                                            if (!data || data.length === 0) {
                                                tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Tidak ada riwayat upload data peserta</td></tr>';
                                                return;
                                            }
                                            // ensure we only show the latest 5 entries here (API already limits to 5 by default)
                                            const latest = data.slice(0, 5);
                                            tbody.innerHTML = latest.map(row => `
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${row.nama_file}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${row.tipe_data}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${formatTanggalIndo(row.tanggal_upload)}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm">${statusBadge(row.status)}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                        <button class="btn-delete-riwayat text-red-600 hover:text-red-900 font-semibold" data-id="${row.id}" title="Hapus riwayat">Hapus</button>
                                                    </td>
                                                </tr>
                                            `).join('');
                                        }).catch(err => {
                                            console.error('refreshRiwayatUpload error', err);
                                            Swal.fire({ toast: true, position: 'top', icon: 'error', title: 'Terjadi kesalahan saat memuat riwayat.', showConfirmButton: false, timer: 3500, timerProgressBar: true });
                                        });
                                }
                                // load recent uploads
                                refreshRiwayatUpload();

                                // Event listener untuk tombol hapus riwayat
                                document.addEventListener('click', function(e) {
                                    if (e.target.classList.contains('btn-delete-riwayat')) {
                                        const riwayatId = e.target.dataset.id;
                                        Swal.fire({
                                            title: 'Hapus Riwayat?',
                                            text: 'Riwayat upload ini akan dihapus secara permanen.',
                                            icon: 'warning',
                                            showCancelButton: true,
                                            confirmButtonColor: '#dc2626',
                                            cancelButtonColor: '#6b7280',
                                            confirmButtonText: 'Ya, Hapus',
                                            cancelButtonText: 'Batal'
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                fetch('api/delete_riwayat_upload.php', {
                                                    method: 'POST',
                                                    headers: {
                                                        'Content-Type': 'application/json'
                                                    },
                                                    body: JSON.stringify({ id: riwayatId })
                                                })
                                                .then(r => r.json())
                                                .then(resp => {
                                                    if (resp.ok) {
                                                        Swal.fire({ toast: true, position: 'top', icon: 'success', title: 'Riwayat berhasil dihapus', showConfirmButton: false, timer: 3000, timerProgressBar: true });
                                                        refreshRiwayatUpload();
                                                    } else {
                                                        Swal.fire({ toast: true, position: 'top', icon: 'error', title: 'Gagal menghapus riwayat: ' + (resp.msg || ''), showConfirmButton: false, timer: 3500, timerProgressBar: true });
                                                    }
                                                })
                                                .catch(err => {
                                                    console.error('Delete error:', err);
                                                    Swal.fire({ toast: true, position: 'top', icon: 'error', title: 'Terjadi kesalahan saat menghapus', showConfirmButton: false, timer: 3500, timerProgressBar: true });
                                                });
                                            }
                                        });
                                    }
                                });

                                // Modal riwayat upload dengan SweetAlert2
                                document.addEventListener('DOMContentLoaded', function() {
                                    var btnLihat = document.getElementById('btn-lihat-semua-riwayat');
                                    if (btnLihat) {
                                        btnLihat.onclick = function() {
                                        fetch('api/get_riwayat_upload.php?all=1')
                                            .then(r => {
                                                if (!r.ok) {
                                                    if (r.status === 401 || r.status === 403) {
                                                        Swal.fire({ icon: 'warning', title: 'Akses ditolak atau sesi berakhir. Silakan masuk kembali.' });
                                                    } else {
                                                        Swal.fire({ icon: 'error', title: 'Gagal memuat riwayat upload.' });
                                                    }
                                                    return [];
                                                }
                                                return r.json();
                                            })
                                            .then(data => {
                                                // Batasi maksimal 100 data
                                                data = data.slice(0, 100);
                                                let page = 1;
                                                const perPage = 10;
                                                function renderTable(page) {
                                                    const start = (page-1)*perPage;
                                                    const end = start+perPage;
                                                    let html = `<div style='max-height:60vh;overflow:auto'><table class='w-full min-w-max text-xs'><thead class='bg-gray-50'><tr><th class='px-3 py-2 text-left text-gray-500 uppercase'>Nama File</th><th class='px-3 py-2 text-left text-gray-500 uppercase'>Tipe Data</th><th class='px-3 py-2 text-left text-gray-500 uppercase'>Tanggal Upload</th><th class='px-3 py-2 text-left text-gray-500 uppercase'>Status</th></tr></thead><tbody>`;
                                                    html += data.slice(start, end).map(row => `
                                                        <tr>
                                                            <td class='px-3 py-2 whitespace-nowrap font-medium text-gray-900'>${row.nama_file}</td>
                                                            <td class='px-3 py-2 text-gray-500'>${row.tipe_data}</td>
                                                            <td class='px-3 py-2 text-gray-500'>${formatTanggalIndo(row.tanggal_upload)}</td>
                                                            <td class='px-3 py-2'>${statusBadge(row.status)}</td>
                                                        </tr>
                                                    `).join('');
                                                    html += '</tbody></table></div>';
                                                    // Pagination
                                                        if (data.length > perPage) {
                                                            const totalPage = Math.ceil(data.length/perPage);
                                                            html += `<div style='display:flex;justify-content:center;gap:6px;margin:16px 0 0 0;'>`;
                                                            for (let i=1; i<=totalPage; i++) {
                                                                html += `<button type='button' class='swal-riwayat-page' data-page='${i}' style='
                                                                    width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;
                                                                    border-radius:50%;font-weight:600;font-size:1rem;
                                                                    background:${i===page?'#2563eb':'#f3f4f6'};color:${i===page?'#fff':'#374151'};
                                                                    border:none;outline:none;cursor:pointer;transition:background .2s,color .2s;box-shadow:0 1px 2px 0 rgba(0,0,0,0.03);margin:0 2px;'
                                                                    onmouseover="this.style.background='${i===page?'#2563eb':'#e5e7eb'}'" onmouseout="this.style.background='${i===page?'#2563eb':'#f3f4f6'}'"
                                                                >${i}</button>`;
                                                            }
                                                            html += '</div>';
                                                        }
                                                        return html;
                                                }
                                                function showSwal(page) {
                                                    Swal.fire({
                                                        title: 'Semua Riwayat Upload',
                                                        html: renderTable(page),
                                                        width: 600,
                                                        showCloseButton: true,
                                                        showConfirmButton: false,
                                                        customClass: {popup:'p-0'},
                                                        scrollbarPadding: false,
                                                        didRender: () => {
                                                            document.querySelectorAll('.swal-riwayat-page').forEach(btn => {
                                                                btn.onclick = function() {
                                                                    showSwal(Number(this.dataset.page));
                                                                };
                                                            });
                                                        }
                                                    });
                                                }
                                                showSwal(1);
                                            });
                                        };
                                    }
                                });
                            </script>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Script untuk load invoice pending di dashboard -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function loadInvoicePending() {
                fetch('api/get_invoice_pending.php', {
                    method: 'GET',
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                    .then(r => {
                        if (!r.ok) {
                            console.error('API error:', r.status);
                            throw new Error('API error: ' + r.status);
                        }
                        return r.json();
                    })
                    .then(data => {
                        const tbody = document.getElementById('invoice-pending-tbody');
                        const emptyMsg = document.getElementById('invoice-pending-empty');
                        const table = document.getElementById('invoice-pending-table');
                        
                        if (!data.ok || !Array.isArray(data.data) || data.data.length === 0) {
                            tbody.innerHTML = '';
                            table.style.display = 'none';
                            emptyMsg.style.display = 'block';
                            return;
                        }

                        table.style.display = 'table';
                        emptyMsg.style.display = 'none';
                        tbody.innerHTML = '';

                        data.data.forEach(row => {
                            let statusBadge = '';
                            let statusValue = parseInt(row.status) || 0;  // Convert to int, default 0 jika null
                            if (statusValue === 2) {
                                statusBadge = '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800">Revision</span>';
                            } else {
                                statusBadge = '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>';
                            }

                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">${row.periode || '-'}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">${row.jenis_premi || '-'}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">${row.no_invoice || '-'}</td>
                                <td class="px-4 py-3 text-center">${statusBadge}</td>
                            `;
                            tbody.appendChild(tr);
                        });
                    })
                    .catch(err => {
                        console.error('Error loading invoice pending:', err);
                        const emptyMsg = document.getElementById('invoice-pending-empty');
                        emptyMsg.style.display = 'block';
                    });
            }

            // Load data saat halaman dimuat
            loadInvoicePending();

            // Reload setiap 30 detik untuk update real-time
            setInterval(loadInvoicePending, 30000);

            // Expose function global untuk direfresh dari tempat lain jika perlu
            window.loadInvoicePending = loadInvoicePending;
        });
    </script>

<?php render_partial('footer'); ?>
