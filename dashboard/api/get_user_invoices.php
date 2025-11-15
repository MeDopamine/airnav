<?php
require_once __DIR__ . '/../../auth.php';
require_login();

include __DIR__ . '/../../db/db.php';

// Optional filter by periode
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
        $periode_v = isset($row['periode']) ? $row['periode'] : '';
        $jenis_premi = isset($row['jenis_premi']) ? $row['jenis_premi'] : '';
        $no_invoice = isset($row['invoice_no']) ? $row['invoice_no'] : (isset($row['no_invoice']) ? $row['no_invoice'] : '');
        $tgl_raw = isset($row['created_at']) ? $row['created_at'] : (isset($row['printed_at']) ? $row['printed_at'] : '');
        $tgl = $tgl_raw ? date('Y-m-d', strtotime($tgl_raw)) : '';

        $j_karyawan = isset($row['jml_premi_krywn']) ? (float)$row['jml_premi_krywn'] : 0;
        $j_pt = isset($row['jml_premi_pt']) ? (float)$row['jml_premi_pt'] : 0;
        $total = isset($row['total_premi']) ? (float)$row['total_premi'] : 0;

        $display_total = 'Rp ' . number_format($total, 0, ',', '.');

        // Map flag values to status: 0/NULL=Pending, 1=Verified, 2=Revision
        $flag_val = isset($row['flag']) ? (int)$row['flag'] : 0;
        $status_label = '';
        $status_color = '';

        if ($flag_val === 1) {
            $status_label = 'Verified';
            $status_color = 'bg-green-100 text-green-800';
        } elseif ($flag_val === 2) {
            $status_label = 'Revision';
            $status_color = 'bg-orange-100 text-orange-800';
        } else {
            $status_label = 'Pending';
            $status_color = 'bg-yellow-100 text-yellow-800';
        }

        $status_html = '<span class="px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full ' . $status_color . '">' . $status_label . '</span>';

        $urlinvoice = '';
        if (isset($row['urlinvoice']) && $row['urlinvoice']) $urlinvoice = $row['urlinvoice'];
        if (!$urlinvoice && isset($row['url_invoice'])) $urlinvoice = $row['url_invoice'];

        $download_invoice_link = '/dashboard/api/download_invoice.php?id=' . (int)$row['id'];
        $download_invoice_btn = '';
        if ($urlinvoice) {
            $download_invoice_btn = '<a href="' . htmlspecialchars($download_invoice_link) . '" class="download-invoice text-white bg-blue-600 hover:bg-blue-700 px-3 py-2 rounded-md inline-flex items-center justify-center gap-2" data-id="' . (int)$row['id'] . '"><i class="fa-solid fa-file-pdf"></i><span>Invoice</span></a>';
        } else {
            $download_invoice_btn = '<button class="text-gray-500 bg-gray-100 px-3 py-2 rounded-md inline-flex items-center justify-center cursor-not-allowed" disabled><i class="fa-solid fa-file-pdf"></i><span>No File</span></button>';
        }

        $download_peserta_link = 'api/download_peserta.php?id=' . (int)$row['id'];
        if ($periode_v) $download_peserta_link .= '&periode=' . urlencode($periode_v);
        if ($jenis_premi) $download_peserta_link .= '&jenis=' . urlencode($jenis_premi);

        $download_peserta_btn = '<a href="' . htmlspecialchars($download_peserta_link) . '" class="download-peserta text-white bg-green-600 hover:bg-green-700 px-4 py-2 rounded-md inline-flex items-center justify-center w-40" data-periode="' . htmlspecialchars($periode_v) . '" data-id="' . (int)$row['id'] . '"><i class="fa-solid fa-file-excel mr-2"></i><span>Peserta</span></a>';


        // Add approval buttons only for pending invoices
        $approval_btns = '';
        if ($flag_val === 0 || $flag_val === null) {
            $approval_btns = '<div class="flex gap-2">' .
                '<button class="btn-verify text-white bg-green-600 hover:bg-green-700 px-3 py-2 rounded-md inline-flex items-center justify-center gap-2 text-sm" data-id="' . (int)$row['id'] . '" data-invoice="' . htmlspecialchars($no_invoice) . '"><i class="fa-solid fa-check"></i><span>Verify</span></button>' .
                '<button class="btn-revise text-white bg-red-600 hover:bg-red-700 px-3 py-2 rounded-md inline-flex items-center justify-center gap-2 text-sm" data-id="' . (int)$row['id'] . '" data-invoice="' . htmlspecialchars($no_invoice) . '"><i class="fa-solid fa-redo"></i><span>Revise</span></button>' .
                '</div>';
        }

        $actions = '<div class="flex flex-col items-center gap-2">' . $download_invoice_btn . $download_peserta_btn . $approval_btns . '</div>';

        // additional optional fields: jenis premi, jumlah premi karyawan, jumlah peserta, PIC
        $jenis_premi_val = $jenis_premi ?: (isset($row['jenis']) ? $row['jenis'] : '');
        $jml_premi_krywn_val = isset($row['jml_premi_krywn']) ? (float)$row['jml_premi_krywn'] : $j_karyawan;
        // jumlah peserta comes from invoice table's 'jumlah' column
        $jml_peserta_val = isset($row['jumlah']) ? (int)$row['jumlah'] : '';

        $pic_val = isset($row['pic']) ? $row['pic'] : (isset($row['created_by']) ? $row['created_by'] : '');

        $data[] = [
            'no' => $no,
            'periode' => $periode_v,
            'jenis_premi' => $jenis_premi_val,
            'no_invoice' => $no_invoice,
            'tgl_invoice' => $tgl,
            'jml_premi_krywn' => $jml_premi_krywn_val,
            'jumlah_peserta' => $jml_peserta_val,
            'total_premi' => ['display' => $display_total, 'sort' => $total],
            'pic' => $pic_val,
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
