<?php
require_once __DIR__ . '/../../auth.php';
require_login();
if (!is_admin_or_admintl()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}

include __DIR__ . '/../../db/db.php';

$periode = isset($_GET['periode']) ? trim($_GET['periode']) : '';

$where = '';
if ($periode !== '') {
    $periode_safe = mysqli_real_escape_string($conn, $periode);
    $where = "WHERE periode = '" . $periode_safe . "'";
}

$sql = "SELECT * FROM invoice_airnav $where ORDER BY periode DESC, id DESC";
$res = mysqli_query($conn, $sql);

$data = [];
$no = 1;
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        // Normalize fields with fallbacks
        $periode_v = isset($row['periode']) ? $row['periode'] : '';
        $jenis_premi = isset($row['jenis_premi']) ? $row['jenis_premi'] : (isset($row['jenis']) ? $row['jenis'] : '');
        $no_invoice = isset($row['no_invoice']) ? $row['no_invoice'] : (isset($row['invoice_no']) ? $row['invoice_no'] : '');
        $tgl_raw = isset($row['tgl_invoice']) ? $row['tgl_invoice'] : (isset($row['created_at']) ? $row['created_at'] : (isset($row['printed_at']) ? $row['printed_at'] : ''));
        // Output invoice date in YYYY-MM-DD format for consistent sorting and display
        $tgl = $tgl_raw ? date('Y-m-d', strtotime($tgl_raw)) : '';

        $j_karyawan = isset($row['jumlah_premi_karyawan']) ? (float)$row['jumlah_premi_karyawan'] : (isset($row['jml_premi_krywn']) ? (float)$row['jml_premi_krywn'] : 0);
        $j_pt = isset($row['jumlah_premi_pt']) ? (float)$row['jumlah_premi_pt'] : (isset($row['jml_premi_pt']) ? (float)$row['jml_premi_pt'] : 0);
        $total = isset($row['total_premi']) ? (float)$row['total_premi'] : 0;

        // format display
        $display_j_karyawan = 'Rp ' . number_format($j_karyawan, 2, ',', '.');
        $display_j_pt = 'Rp ' . number_format($j_pt, 2, ',', '.');
        $display_total = 'Rp ' . number_format($total, 2, ',', '.');

        // Map flag values to status: 0=Pending, 1=Approved, 2=Rejected
        $flag_val = isset($row['flag']) ? (int)$row['flag'] : 0;
        if ($flag_val === 1) {
            $status_html = '<span class="px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-green-100 text-green-800">Approved</span>';
        } elseif ($flag_val === 2) {
            $status_html = '<span class="px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-red-100 text-red-800">Rejected</span>';
        } elseif ($flag_val === 3) {
            $status_html = '<span class="px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-purple-100 text-purple-800">Revision</span>';
        } else {
            $status_html = '<span class="px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>';
        }

        // invoice URL
        $urlinvoice = '';
        if (isset($row['urlinvoice']) && $row['urlinvoice']) $urlinvoice = $row['urlinvoice'];
        if (!$urlinvoice && isset($row['url_invoice'])) $urlinvoice = $row['url_invoice'];

        // actions: download invoice via server-side endpoint, and download peserta for periode
        $download_invoice_link = 'api/download_invoice.php?id=' . (int)$row['id'];
        $download_invoice_btn = '';
        if ($urlinvoice) {
            // always go through server-side to validate auth - render as a stacked block button
            $download_invoice_btn = '<a href="' . htmlspecialchars($download_invoice_link) . '" class="download-invoice text-white bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-md inline-flex items-center justify-center w-40" data-id="' . (int)$row['id'] . '"><i class="fa-solid fa-file-pdf mr-2"></i><span>Invoice</span></a>';
        } else {
            // disabled button (block style)
            $download_invoice_btn = '<button class="text-gray-500 bg-gray-100 px-4 py-2 rounded-md inline-flex items-center justify-center w-40 cursor-not-allowed" disabled><i class="fa-solid fa-file-pdf mr-2"></i><span>No<br>File</span></button>';
        }

        // include invoice id in peserta download link so server can fallback to uploaded file when needed
        $download_peserta_link = 'api/download_peserta.php?id=' . (int)$row['id'];
        if ($periode_v) $download_peserta_link .= '&periode=' . urlencode($periode_v);
        if ($jenis_premi) $download_peserta_link .= '&jenis=' . urlencode($jenis_premi);

        $download_peserta_btn = '<a href="' . htmlspecialchars($download_peserta_link) . '" class="download-peserta text-white bg-green-600 hover:bg-green-700 px-4 py-2 rounded-md inline-flex items-center justify-center w-40" data-periode="' . htmlspecialchars($periode_v) . '" data-id="' . (int)$row['id'] . '"><i class="fa-solid fa-file-excel mr-2"></i><span>Peserta</span></a>';

        // use stored jumlah (participant count) from invoice table if available
        $jumlah_peserta = isset($row['jumlah']) ? (int)$row['jumlah'] : 0;

        // Edit button: only show for Pending (flag=0)
        $edit_btn = '';
        $revision_btn = '';
        if ($flag_val === 0) {
            $edit_btn = '<button class="btn-edit text-white bg-yellow-600 hover:bg-yellow-700 px-4 py-2 rounded-md inline-flex items-center justify-center gap-2" data-id="' . (int)$row['id'] . '" data-invoice="' . htmlspecialchars($no_invoice) . '"><i class="fa-solid fa-pencil"></i><span>Edit</span></button>';
        }
        if ($flag_val === 1) {
            $revision_btn = '<button class="btn-revision px-4 py-2 w-40 bg-purple-600 hover:bg-purple-700 text-white rounded text-sm" data-idbatch="' . htmlspecialchars($row['idbatch']) . '" data-id="' . htmlspecialchars($row['id']) . '"><i class="fa-solid fa-pencil mr-1"></i>Revision</button>';
        }

        // Wrap actions in a column so they render as stacked, centered buttons
        // $actions = '<div class="flex flex-col items-center gap-2">' . $download_invoice_btn . $download_peserta_btn . '</div>';
        $actions = '<div class="flex flex-col items-center gap-2">' . $download_invoice_btn . $download_peserta_btn . $edit_btn . $revision_btn . '</div>';
        $data[] = [
            'no' => $no,
            'periode' => $periode_v,
            'jenis_premi' => $jenis_premi,
            'no_invoice' => $no_invoice,
            'tgl_invoice' => $tgl,
            'jumlah_premi_karyawan' => ['display' => $display_j_karyawan, 'sort' => $j_karyawan],
            'jumlah_premi_pt' => ['display' => $display_j_pt, 'sort' => $j_pt],
            'total_premi' => ['display' => $display_total, 'sort' => $total],
            'pic' => isset($row['pic']) ? $row['pic'] : '',
            'jumlah_peserta' => $jumlah_peserta,
            'status' => $status_html,
            'actions' => $actions
        ];

        $no++;
    }
    mysqli_free_result($res);
}

mysqli_close($conn);

header('Content-Type: application/json');
echo json_encode(['data' => $data]);
exit;
