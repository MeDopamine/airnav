<?php
require_once __DIR__ . '/../../auth.php';
require_login();
if (!is_admin_or_admintl()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Akses ditolak']);
    exit;
}

include __DIR__ . '/../../db/db.php';

$periode = isset($_GET['periode']) ? trim($_GET['periode']) : '';
$jenis = isset($_GET['jenis']) ? trim($_GET['jenis']) : '';

// Normalize periode to YYYYMM if possible
if ($periode) {
    $digits = preg_replace('/[^0-9]/', '', $periode);
    if (preg_match('/^\d{4}-\d{2}$/', $periode)) {
        $periode = str_replace('-', '', $periode);
    } elseif (preg_match('/^\d{6}$/', $digits)) {
        $periode = $digits;
    } else {
        // leave as-is
    }
    $periode = mysqli_real_escape_string($conn, $periode);
}

$where = [];
if ($periode) $where[] = "periode='" . $periode . "'";
if ($jenis !== '') $where[] = "jenis_premi='" . mysqli_real_escape_string($conn, $jenis) . "'";

if (empty($where)) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Periode tidak diberikan']);
    exit;
}

$sql = "SELECT * FROM data_peserta WHERE " . implode(' AND ', $where) . " ORDER BY nik ASC";
$res = mysqli_query($conn, $sql);
if (!$res) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Database error: ' . mysqli_error($conn)]);
    exit;
}

$rows = [];
while ($r = mysqli_fetch_assoc($res)) {
    $rows[] = $r;
}
mysqli_free_result($res);

if (empty($rows)) {
    // no data in data_peserta — try fallback: if invoice id provided, see if an uploaded peserta file exists
    $invoiceId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($invoiceId) {
        // Try to find an uploaded peserta file by convention: uploads/invoices/{periode}_{jenis}_peserta_*.{xls,xlsx,csv}
        $q = "SELECT periode, jenis_premi FROM invoice_airnav WHERE id=" . $invoiceId . " LIMIT 1";
        $r = mysqli_query($conn, $q);
        if ($r && $rowInv = mysqli_fetch_assoc($r)) {
            mysqli_free_result($r);
            $invPeriode = isset($rowInv['periode']) ? $rowInv['periode'] : $periode;
            $invJenis = isset($rowInv['jenis_premi']) ? $rowInv['jenis_premi'] : '';

            $uploadsDir = realpath(__DIR__ . '/../../uploads/invoices');
            if ($uploadsDir && is_dir($uploadsDir)) {
                $pattern = $uploadsDir . DIRECTORY_SEPARATOR . $invPeriode . '_' . $invJenis . '_peserta_*.*';
                $matches = glob($pattern);
                // if none found by jenis, try any peserta file for periode
                if (empty($matches)) {
                    $pattern2 = $uploadsDir . DIRECTORY_SEPARATOR . $invPeriode . '_*' . '_peserta_*.*';
                    $matches = glob($pattern2);
                }

                if (!empty($matches)) {
                    // pick the most recent by filemtime
                    usort($matches, function ($a, $b) {
                        return filemtime($b) - filemtime($a);
                    });
                    $filePath = $matches[0];

                    // If AJAX probe, return metadata
                    if (isset($_GET['ajax'])) {
                        header('Content-Type: application/json');
                        echo json_encode(['ok' => true, 'filename' => basename($filePath), 'count' => 0]);
                        exit;
                    }

                    // Stream file
                    header_remove();
                    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                    $ctype = 'application/octet-stream';
                    if (in_array($ext, ['xls', 'xlsx'])) $ctype = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                    if ($ext === 'csv') $ctype = 'text/csv; charset=utf-8';
                    header('Content-Type: ' . $ctype);
                    header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
                    header('Content-Length: ' . filesize($filePath));
                    readfile($filePath);
                    exit;
                }
            }
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Tidak ada data peserta untuk periode ini']);
    exit;
}

// Use XLSX (PhpSpreadsheet) instead of CSV
$fileName = 'peserta_' . $periode . ($jenis !== '' ? ('_jenis_' . $jenis) : '') . '.xlsx';

// If caller requested ajax probe, return JSON metadata instead of streaming
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'filename' => $fileName, 'count' => count($rows)]);
    exit;
}

// Verify PhpSpreadsheet is available
$autoload = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($autoload)) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Library PhpSpreadsheet tidak ditemukan. Pastikan composer install telah dijalankan.']);
    exit;
}

require_once $autoload;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// build spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$headers = ['NO', 'Nama', 'TMT Member', 'NIP', 'NIK', 'Periode Invoice', 'Jenis Invoice', 'Gapok', 'Premi Karyawan', 'Premi Perushaan', 'Total Premi', 'PIC', 'Status', 'Approval', 'Created At'];
// write headers
$col = 'A';
foreach ($headers as $h) {
    $sheet->setCellValue($col . '1', $h);
    $sheet->getColumnDimension($col)->setAutoSize(true);
    $col++;
}

$rowNum = 2;
foreach ($rows as $r) {
    // $approved = (isset($r['status_data']) && intval($r['status_data']) === 1) ? 'Yes' : 'No';
    switch ($r['jenis_premi']) {
        case '1':
            $r['jenis_premi'] = 'JHT REGULAR';
            break;
        case '2':
            $r['jenis_premi'] = 'JHT TOPUP';
            break;
        case '3':
            $r['jenis_premi'] = 'PKP REGULAR';
            break;
        default:
            // leave as-is
            break;
    }
    switch ($r['status_data']) {
        case '0':
            $r['status_data'] = 'Not Approved';
            break;
        case '1':
            $r['status_data'] = 'Approved';
            break;
        case '2':
            $r['status_data'] = 'Rejected';
            break;
        default:
            $r['status_data'] = 'Pending';
            break;
    }
    switch ($r['status']) {
        case '1':
            $r['status'] = 'Aktif';
            break;
        default:
            $r['status'] = 'Non-Aktif';
            break;
    }
    $sheet->setCellValue('A' . $rowNum, $rowNum - 1);
    $sheet->setCellValue('B' . $rowNum, $r['nama']);
    $sheet->setCellValue('C' . $rowNum, $r['tmt_asuransi']);
    $sheet->setCellValue('D' . $rowNum, $r['nip']);
    $sheet->setCellValue('E' . $rowNum, $r['nik']);
    $sheet->setCellValue('F' . $rowNum, $r['periode']);
    $sheet->setCellValue('G' . $rowNum, $r['jenis_premi']);
    // numeric columns
    $sheet->setCellValue('H' . $rowNum, is_numeric($r['gapok']) ? (float)$r['gapok'] : $r['gapok']);
    $sheet->setCellValue('I' . $rowNum, is_numeric($r['jml_premi_krywn']) ? (float)$r['jml_premi_krywn'] : $r['jml_premi_krywn']);
    $sheet->setCellValue('J' . $rowNum, is_numeric($r['jml_premi_pt']) ? (float)$r['jml_premi_pt'] : $r['jml_premi_pt']);
    $sheet->setCellValue('K' . $rowNum, is_numeric($r['total_premi']) ? (float)$r['total_premi'] : $r['total_premi']);
    $sheet->setCellValue('L' . $rowNum, $r['pic']);
    $sheet->setCellValue('M' . $rowNum, $r['status']);
    $sheet->setCellValue('N' . $rowNum, $r['status_data']);
    $sheet->setCellValue('O' . $rowNum, $r['created_at']);
    $rowNum++;
}

// Send headers and stream XLSX
header_remove();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Pragma: no-cache');
header('Expires: 0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
