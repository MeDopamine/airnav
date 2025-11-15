<?php
ob_start();
require_once __DIR__ . '/../../auth.php';
ob_end_clean();

require_login();
// Hanya admin yang bisa melihat invoice untuk approval
require_admin();

include __DIR__ . '/../../db/db.php';

header('Content-Type: application/json; charset=utf-8');

// Get filter parameter
$periode = isset($_GET['periode']) ? trim($_GET['periode']) : '';

// Build query untuk invoice semua status (semua invoice yang pernah disubmit)
$where = "1=1";
if (!empty($periode)) {
    $periode = mysqli_real_escape_string($conn, $periode);
    $where .= " AND periode = '$periode'";
}

$sql = "SELECT id, periode, jenis_premi, invoice_no, jml_premi_krywn, total_premi, jumlah, pic, created_at, flag, urlinvoice FROM invoice_airnav WHERE $where ORDER BY created_at DESC";
$res = mysqli_query($conn, $sql);

if (!$res) {
    http_response_code(500);
    die(json_encode(['ok' => false, 'msg' => 'Database error: ' . mysqli_error($conn)]));
}

$data = [];
$no = 1;

if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        // Get jenis premi - gunakan nilai langsung dari invoice_airnav
        $periode_v = $row['periode'] ?: '';
        $jenis_premi_val = $row['jenis_premi'] ?: '';

        // Format tanggal
        $tgl_invoice = $row['created_at'] ? substr($row['created_at'], 0, 10) : '';

        // Format nilai
        $jml_premi_krywn = isset($row['jml_premi_krywn']) ? (int)$row['jml_premi_krywn'] : 0;
        $total_premi = isset($row['total_premi']) ? (int)$row['total_premi'] : 0;
        $jumlah = isset($row['jumlah']) ? (int)$row['jumlah'] : 0;

        // Map flag to status: 0=Pending, 1=Approved, 2=Rejected
        $flag_val = isset($row['flag']) ? (int)$row['flag'] : 0;
        if ($flag_val === 1) {
            $status_html = '<span class="px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-green-100 text-green-800">Approved</span>';
        } elseif ($flag_val === 2) {
            $status_html = '<span class="px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-red-100 text-red-800">Rejected</span>';
        } else {
            $status_html = '<span class="px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>';
        }

        // Build action buttons
        $actions = '<div style="display:flex; gap:6px; justify-content:center; flex-wrap:wrap;">';

        $download_peserta_link = 'api/download_peserta.php?id=' . (int)$row['id'];
        if ($periode_v) $download_peserta_link .= '&periode=' . urlencode($periode_v);
        if ($jenis_premi_val) $download_peserta_link .= '&jenis=' . urlencode($jenis_premi_val);

        $download_peserta_btn = '<a href="' . htmlspecialchars($download_peserta_link) . '" class="download-peserta text-white bg-green-600 hover:bg-green-700 px-4 py-2 rounded-md inline-flex items-center justify-center w-40" data-periode="' . htmlspecialchars($periode_v) . '" data-id="' . (int)$row['id'] . '"><i class="fa-solid fa-file-excel mr-2"></i><span>Peserta</span></a>';


        // Download button - jika ada file
        if (!empty($row['urlinvoice'])) {
            $download_link = '/dashboard/api/download_invoice.php?id=' . (int)$row['id'];
            // $actions .= '<a href="' . htmlspecialchars($download_link) . '" class="btn-download px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-sm"><i class="fa-solid fa-file-pdf mr-2"></i><span>Invoice</span></a>';
            $actions .= '<a href="' . htmlspecialchars($download_link) . '" class="download-invoice text-white bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-md inline-flex items-center justify-center w-40" data-id="' . (int)$row['id'] . '"><i class="fa-solid fa-file-pdf mr-2"></i><span>Invoice</span></a>';
        }
        $actions .= $download_peserta_btn;

        // Show Approve/Reject buttons only for pending invoices
        if ($flag_val === 0) {
            $actions .= '<button class="btn-approve px-3 py-1 bg-green-600 hover:bg-green-700 text-white rounded text-sm" data-id="' . htmlspecialchars($row['id']) . '"><i class="fa-solid fa-check mr-1"></i>Approve</button>';
            $actions .= '<button class="btn-reject px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-sm" data-id="' . htmlspecialchars($row['id']) . '"><i class="fa-solid fa-times mr-1"></i>Reject</button>';
        }
        $actions .= '</div>';

        $data[] = [
            'no' => $no++,
            'periode' => htmlspecialchars($row['periode'] ?? ''),
            'jenis_premi' => htmlspecialchars($jenis_premi_val),
            'no_invoice' => htmlspecialchars($row['invoice_no'] ?? ''),
            'tgl_invoice' => htmlspecialchars($tgl_invoice),
            'jml_premi_krywn' => [
                'display' => number_format($jml_premi_krywn, 0, ',', '.'),
                'sort' => $jml_premi_krywn
            ],
            'jumlah_peserta' => $jumlah,
            'total_premi' => [
                'display' => number_format($total_premi, 0, ',', '.'),
                'sort' => $total_premi
            ],
            'pic' => htmlspecialchars($row['pic'] ?? ''),
            'status' => $status_html,
            'actions' => $actions
        ];
    }
    mysqli_free_result($res);
}

mysqli_close($conn);
echo json_encode(['ok' => true, 'data' => $data]);
