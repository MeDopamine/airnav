<?php
// Require authentication and admin role before sending any output
include_once __DIR__ . '/../auth.php';
require_login();
if (!is_admin()) {
    header('Location: user/dashboard.php');
    exit;
}

include '../db/db.php';
// load partials helper for render_partial()
include_once __DIR__ . '/partials/_init.php';
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
                            <h2 class="text-xl font-semibold text-gray-800">Data Akun Peserta</h2>
                            <p class="text-sm text-gray-500 mt-1">Daftar akun peserta yang terdaftar. Gunakan kolom pencarian untuk filter data.</p>
                        </div>
                        <div class="p-6 overflow-x-auto" style="position:relative;">
                        <!-- <div class="mt-8 bg-white rounded-md p-4 border"> -->
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">Tabel Peserta</h3>
                                    <p class="text-sm text-gray-500">Daftar peserta yang mendaftar melalui formulir registrasi.</p>
                                </div>
                                <div>
                                    <button id="approve-all-registrasi-btn" class="px-4 py-2 bg-green-600 text-white rounded-full">Approve All</button>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table id="registrasi-peserta-table" class="w-full display stripe hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th class="text-center align-middle">No</th>
                                            <th class="text-center align-middle">NIK</th>
                                            <th class="text-center align-middle">Nama</th>
                                            <th class="text-center align-middle">Kelamin</th>
                                            <th class="text-center align-middle">Tanggal Lahir</th>
                                            <th class="text-center align-middle">No HP</th>
                                            <th class="text-center align-middle">Email</th>
                                            <th class="text-center align-middle">Verifikasi</th>
                                            <th class="text-center align-middle">Approved By</th>
                                            <th class="text-center align-middle">Approved At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Ambil data dari tabel registrasi_peserta
                                        // Left join ke tabel users untuk menampilkan nama admin apabila registrasi.user_verify menyimpan admin id
                                        $rSql = "SELECT r.id, r.no_ktp, r.nik, r.nama, r.kelamin, r.tgl_lahir, r.no_hp, r.email, r.is_verify, r.user_verify, r.tgl, u.name AS verifier_name FROM registrasi_peserta r LEFT JOIN users u ON u.id = r.user_verify ORDER BY r.tgl DESC, r.id DESC";
                                        $rRes = mysqli_query($conn, $rSql);
                                        if ($rRes) {
                                            while ($r = mysqli_fetch_assoc($rRes)) {
                                                echo '<tr>';
                                                echo '<td class="text-center"></td>'; // nomor oleh DataTables
                                                echo '<td class="text-center">' . htmlspecialchars($r['nik']) . '</td>';
                                                echo '<td class="text-center">' . htmlspecialchars($r['nama']) . '</td>';
                                                echo '<td class="text-center">' . htmlspecialchars($r['kelamin']) . '</td>';
                                                // format tgl_lahir
                                                $rt = $r['tgl_lahir'];
                                                $rt_fmt = '';
                                                if ($rt && $rt !== '0000-00-00') {
                                                    $dtr = DateTime::createFromFormat('Y-m-d', $rt);
                                                    if ($dtr) $rt_fmt = $dtr->format('d-m-Y'); else $rt_fmt = htmlspecialchars($rt);
                                                }
                                                echo '<td class="text-center">' . $rt_fmt . '</td>';
                                                echo '<td class="text-center">' . htmlspecialchars($r['no_hp']) . '</td>';
                                                echo '<td class="text-center">' . htmlspecialchars($r['email']) . '</td>';
                                                // verifikasi badge
                                                $isv = !empty($r['is_verify']) ? 1 : 0;
                                                if ($isv) {
                                                    $vclass = 'px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-green-100 text-green-800';
                                                    $vlabel = 'Verified';
                                                } else {
                                                    $vclass = 'px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-red-100 text-red-800';
                                                    $vlabel = 'Not Verified';
                                                }
                                                echo '<td class="text-center"><span class="' . $vclass . '">' . $vlabel . '</span></td>';
                                                // Approved by: if we have a joined user name (verifier_name) prefer it, otherwise show stored user_verify (may already be a name)
                                                $approved_by_val = '&mdash;';
                                                if ($isv && !empty($r['user_verify'])) {
                                                    if (!empty($r['verifier_name'])) {
                                                        $approved_by_val = htmlspecialchars($r['verifier_name']);
                                                    } else {
                                                        $approved_by_val = htmlspecialchars($r['user_verify']);
                                                    }
                                                }
                                                echo '<td class="text-center">' . $approved_by_val . '</td>';
                                                // Approved at (tgl)
                                                $approved_at = '&mdash;';
                                                // Show only the date (dd-mm-YYYY) for Approved At — no time component
                                                if ($isv && !empty($r['tgl']) && $r['tgl'] !== '0000-00-00') {
                                                    // Try datetime first
                                                    $dt2 = DateTime::createFromFormat('Y-m-d H:i:s', $r['tgl']);
                                                    if ($dt2) {
                                                        $approved_at = $dt2->format('d-m-Y');
                                                    } else {
                                                        // fallback to date-only formats
                                                        $dt3 = DateTime::createFromFormat('Y-m-d', $r['tgl']);
                                                        if ($dt3) $approved_at = $dt3->format('d-m-Y');
                                                        else $approved_at = htmlspecialchars($r['tgl']);
                                                    }
                                                }
                                                echo '<td class="text-center">' . $approved_at . '</td>';
                                                // Aksi column removed; approval can be done via "Approve All" button
                                                // show em dash placeholder for layout compatibility in earlier table versions (no Aksi column now)
                                                echo '</tr>' . PHP_EOL;
                                            }
                                            mysqli_free_result($rRes);
                                        } else {
                                            echo '<tr><td colspan="12">Tidak ada data atau terjadi kesalahan: ' . htmlspecialchars(mysqli_error($conn)) . '</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
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
    <!-- Page-specific helpers -->
    <script src="assets/js/data-peserta.js"></script>
</body>
</html>