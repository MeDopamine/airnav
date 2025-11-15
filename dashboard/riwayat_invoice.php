<?php
// Require authentication - hanya admin yang bisa akses
include_once __DIR__ . '/../auth.php';
require_login();
require_admin();

include '../db/db.php';
// load partials helper for render_partial()
include_once __DIR__ . '/partials/_init.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Invoice - Approval</title>
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="assets/css/tailwind.output.css">
    <!-- Font Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- DataTables CSS (CDN) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <!-- DataTables Buttons CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Icon -->
    <link rel="icon" href="https://placehold.co/32x32/0033A0/FFFFFF?text=D" type="image/png">
    <!-- Font Awesome for button icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <!-- Select2 CSS -->
    <link href="assets/css/select2.min.css" rel="stylesheet">
    <!-- SweetAlert2 custom styles -->
    <link rel="stylesheet" href="assets/css/swal-custom.css">
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
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-800">Riwayat Invoice - Approval</h2>
                        <p class="text-sm text-gray-500 mt-1">Daftar invoice yang perlu approval dari Admin AirNav. Gunakan kolom pencarian untuk filter data.</p>
                    </div>
                    <div class="p-6 overflow-x-auto" style="position:relative;">
                        <?php
                        // Fetch distinct periode values for filter dropdown
                        $periode_list = [];
                        $q = "SELECT DISTINCT periode FROM invoice_airnav ORDER BY periode DESC";
                        $r = mysqli_query($conn, $q);
                        if ($r) {
                            while ($rw = mysqli_fetch_assoc($r)) {
                                if (isset($rw['periode']) && $rw['periode'] !== '') $periode_list[] = $rw['periode'];
                            }
                            mysqli_free_result($r);
                        }
                        ?>

                        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4">
                            <div class="flex items-center space-x-3 mb-3 md:mb-0">
                                <label for="filter-periode" class="text-sm font-medium text-gray-700">Periode:</label>
                                <select id="filter-periode" class="border rounded-lg px-2 py-1 text-sm">
                                    <?php
                                    // populate periode options
                                    $pRes = mysqli_query($conn, "SELECT DISTINCT periode FROM invoice_airnav ORDER BY periode DESC");
                                    if ($pRes) {
                                        echo '<option value=""> Semua Periode </option>';
                                        while ($pRow = mysqli_fetch_assoc($pRes)) {
                                            $val = htmlspecialchars($pRow['periode']);
                                            echo "<option value=\"$val\">$val</option>";
                                        }
                                        mysqli_free_result($pRes);
                                    } else {
                                        echo '<option value="">Tidak ada periode</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <table id="invoice-approval-table" class="w-full display stripe hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th class="text-center align-middle">No</th>
                                    <th class="text-center align-middle">Periode</th>
                                    <th class="text-center align-middle">Jenis Premi</th>
                                    <th class="text-center align-middle">No. Invoice</th>
                                    <th class="text-center align-middle">Tanggal Invoice</th>
                                    <!-- <th class="text-center align-middle">Jumlah Premi Karyawan</th> -->
                                    <th class="text-center align-middle">Jumlah Peserta</th>
                                    <th class="text-center align-middle">Total Premi</th>
                                    <th class="text-center align-middle">PIC</th>
                                    <th class="text-center align-middle">Status</th>
                                    <th class="text-center align-middle">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <?php render_partial('footer'); ?>

    <!-- jQuery + DataTables JS (CDN) -->
    <script src="../assets/js/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/jquery.dataTables.min.js"></script>
    <!-- DataTables Buttons JS -->
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- SweetAlert2 for toasts -->
    <script src="../assets/js/sweetalert2@11.js"></script>
    <!-- Page script -->
    <script src="assets/js/riwayat-invoice.js"></script>
</body>

</html>