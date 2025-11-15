<?php
// Require authentication - admin dan admintl bisa akses
include_once __DIR__ . '/../auth.php';
require_login();
require_admin_or_admintl();

include '../db/db.php';
// load partials helper for render_partial()
include_once __DIR__ . '/partials/_init.php';

// Helper function to format jenis premi display
function formatJenisPremiDisplay($jenisValue)
{
    $jenisMap = array(
        '1' => 'JHT Regular',
        '2' => 'PKP Regular',
        '3' => 'JHT Topup'
    );
    return isset($jenisMap[$jenisValue]) ? $jenisMap[$jenisValue] : $jenisValue;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Peserta</title>
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
    <link href="assets/css/select2.min.css" rel="stylesheet" />
    <!-- SweetAlert2 custom styles -->
    <link rel="stylesheet" href="assets/css/swal-custom.css">
    <!-- Flatpickr Datepicker CSS - Lightweight and SweetAlert2 compatible -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Flatpickr monthSelect plugin CSS (for month/year only picker) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
    <!-- SweetAlert2 custom styles -->
    <style>
        /* Ensure Flatpickr datepicker appears above SweetAlert2 modal */
        .flatpickr-calendar {
            z-index: 2000 !important;
        }

        .flatpickr-calendar.open {
            display: block !important;
        }
    </style>
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
                        <h2 class="text-xl font-semibold text-gray-800">Data Peserta</h2>
                        <p class="text-sm text-gray-500 mt-1">Daftar peserta hasil upload. Gunakan kolom pencarian untuk filter data.</p>
                    </div>
                    <div class="p-6 overflow-x-auto" style="position:relative;">
                        <!-- Tabel Periode -->
                        <div class="mb-4">
                            <table id="periode-table" class="w-full display stripe hover bg-white" style="width:100%">
                                <thead>
                                    <tr>
                                        <th class="text-center align-middle">No</th>
                                        <th class="text-center align-middle">Periode</th>
                                        <th class="text-center align-middle">Jenis Invoice</th>
                                        <th class="text-center align-middle">Jumlah Peserta</th>
                                        <th class="text-center align-middle">Total Premi Karyawan</th>
                                        <th class="text-center align-middle">Total Premi PT</th>
                                        <th class="text-center align-middle">Total Premi</th>
                                        <th class="text-center align-middle">Tanggal Upload</th>
                                        <th class="text-center align-middle">Status Approval</th>
                                        <th class="text-center align-middle">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <!-- Overlay loading untuk area tabel saja -->
                        <div id="table-loading-overlay" style="display:none;position:absolute;top:0;left:0;right:0;bottom:0;width:100%;height:100%;background:rgba(255,255,255,0.7);z-index:20;align-items:center;justify-content:center;">
                            <div class="flex flex-col items-center justify-center h-full">
                                <svg class="animate-spin h-10 w-10 text-blue-600 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                </svg>
                                <span class="text-blue-700 font-semibold">Memuat data...</span>
                            </div>
                        </div>
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4">
                            <div class="flex items-center space-x-3 mb-3 md:mb-0">
                                <label for="periode" class="text-sm mr-3 font-medium text-gray-700">Periode:</label>
                                <select id="periode" name="periode" class="form-select px-3 py-1 border-2 rounded-full bg-white text-sm" style="width: 160px;">
                                    <?php
                                    // populate periode options from distinct values in DB
                                    $pRes = mysqli_query($conn, "SELECT DISTINCT periode FROM data_peserta ORDER BY periode DESC");
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
                                </form>
                            </div>
                        </div>

                        <table id="data-peserta-table" class="w-full display stripe hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th class="text-center align-middle">No</th>
                                    <th class="text-center align-middle">Nama</th>
                                    <th class="text-center align-middle">NIP</th>
                                    <th class="text-center align-middle">NIK</th>
                                    <th class="text-center align-middle">Periode</th>
                                    <th class="text-center align-middle">Jenis Invoice</th>
                                    <th class="text-center align-middle">Jumlah Premi Karyawan</th>
                                    <th class="text-center align-middle">Jumlah Premi PT</th>
                                    <th class="text-center align-middle">Total Premi</th>
                                    <th class="text-center align-middle">Status</th>
                                    <th class="text-center align-middle">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // fetch rows from data_peserta (urutkan berdasarkan periode terbaru dulu, lalu id)
                                // Columns adjusted to match table header: Periode, Jenis Premi, Jumlah Premi Karyawan, Jumlah Premi PT, Total Premi, Status
                                $sql = "SELECT * FROM data_peserta ORDER BY periode DESC, id DESC";
                                $res = mysqli_query($conn, $sql);
                                $jenis_premi_map = [
                                    1 => 'JHT Regular',
                                    2 => 'JHT Topup',
                                    3 => 'PKP Regular',
                                ];
                                if ($res) {
                                    while ($row = mysqli_fetch_assoc($res)) {
                                        echo '<tr>';
                                        // Kolom nomor urut, diisi oleh DataTables (td kosong, nanti diisi JS)
                                        echo '<td class="text-center"></td>';
                                        // Periode (disimpan sebagai YYYYMM) - tampilkan apa adanya; frontend JS will also provide friendly format where used
                                        echo '<td class="text-center">' . htmlspecialchars($row['nama']) . '</td>';
                                        echo '<td class="text-center">' . htmlspecialchars($row['nip']) . '</td>';
                                        echo '<td class="text-center">' . htmlspecialchars($row['nik']) . '</td>';
                                        echo '<td class="text-center">' . htmlspecialchars($row['periode']) . '</td>';
                                        // Jenis Premi
                                        $jenis_premi_value = $row['jenis_premi'];
                                        $jenis_premi_display = $jenis_premi_map[$jenis_premi_value] ?? htmlspecialchars($jenis_premi_value);
                                        echo '<td class="text-center">' . $jenis_premi_display . '</td>';
                                        // echo '<td class="text-center">' . htmlspecialchars($row['jenis_premi']) . '</td>';
                                        // Jumlah Premi Karyawan
                                        $kry = is_numeric($row['jml_premi_krywn']) ? 'Rp ' . number_format((float)$row['jml_premi_krywn'], 2, ',', '.') : htmlspecialchars($row['jml_premi_krywn']);
                                        echo '<td class="text-center">' . $kry . '</td>';
                                        // Jumlah Premi PT
                                        $pt = is_numeric($row['jml_premi_pt']) ? 'Rp ' . number_format((float)$row['jml_premi_pt'], 2, ',', '.') : htmlspecialchars($row['jml_premi_pt']);
                                        echo '<td class="text-center">' . $pt . '</td>';
                                        // Total Premi
                                        $total = is_numeric($row['total_premi']) ? 'Rp ' . number_format((float)$row['total_premi'], 2, ',', '.') : htmlspecialchars($row['total_premi']);
                                        echo '<td class="text-center">' . $total . '</td>';
                                        // approval button (status_data) - hanya AdminTL yang bisa click
                                        $approved = !empty($row['status_data']) ? 1 : 0;
                                        $btnClass = $approved
                                            ? 'px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-green-100 text-green-800'
                                            : 'px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-red-100 text-red-800';
                                        $btnLabel = $approved ? 'Approved' : 'Not Approved';
                                        $id = (int)$row['id'];
                                        $isAdminTL = is_admintl();
                                        $btnAttrs = $isAdminTL ? 'class="approve-btn cursor-pointer inline-flex items-center justify-center w-full h-full ' . $btnClass . '" style="min-width:110px;display:flex;align-items:center;justify-content:center;" data-id="' . $id . '" data-status="' . $approved . '"' : 'class="inline-flex items-center justify-center w-full h-full ' . $btnClass . '" style="min-width:110px;display:flex;align-items:center;justify-content:center;cursor:not-allowed;"';
                                        echo '<td class="text-center align-middle"><span ' . $btnAttrs . '>' . $btnLabel . '</span></td>';
                                        // Kolom aksi: tombol edit & hapus (hanya untuk admin)
                                        if (is_admin()) {
                                            echo '<td class="text-center">
                                                <button class="btn-edit-data text-blue-600 hover:text-blue-800" style="margin-right:2px;" title="Edit" data-id="' . $id . '"><i class="fa-solid fa-pen-to-square"></i></button>
                                                <button class="btn-delete-data transition" style="margin-left:2px;" title="Hapus" data-id="' . $id . '"><i class="fa-solid fa-trash" style="color:#dc2626;"></i></button>
                                            </td>';
                                        } else {
                                            echo '<td class="text-center text-gray-400"><i class="fa-solid fa-lock" title="Tidak ada akses"></i></td>';
                                        }
                                        echo '</tr>' . PHP_EOL;
                                    }
                                    mysqli_free_result($res);
                                } else {
                                    echo '<tr><td colspan="8">Tidak ada data atau terjadi kesalahan: ' . htmlspecialchars(mysqli_error($conn)) . '</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php render_partial('footer'); ?>

    <!-- Pass user role to JavaScript -->
    <script>
        var userRole = '<?php echo isset($_SESSION['user_role']) ? htmlspecialchars($_SESSION['user_role']) : 'user'; ?>';
    </script>

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
    <!-- Page-specific helpers -->
    <script src="assets/js/helpers/peserta-helpers.js"></script>
    <script src="assets/js/data-peserta.js"></script>
    <!-- Flatpickr Datepicker JS - Lightweight and SweetAlert2 compatible -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <!-- Flatpickr monthSelect plugin (enables month/year picker) -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>
    <!-- <script src="assets/js/manajemen-invoice.js"></script> -->
</body>

</html>