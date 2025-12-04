<?php
require_once __DIR__ . '/../../../auth.php';
require_login();
require_adminbl();

require __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// API Config
$token = "3VGUkzXpm0mdkE1jDsPALWkbOmLfFbOJxF0O8rHc";
$baseUrl = "https://api-gina.taspenlife.com/acs/report/individuals";
$url = $baseUrl . "?tipe=getSemuaPesertaBulog";

// CURL Request
$curl = curl_init($url);
curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "Authorization: Bearer $token"
    ]
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

$apiData = json_decode($response, true);

if ($httpCode !== 200 || empty($apiData['acs/report/individu'])) {
    die("Tidak ada data ditemukan.");
}

$data = $apiData['acs/report/individu'];

// --- Generate Excel --- //
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Tentukan Baris Mulai Data (setelah Judul)
$startRowData = 5;

// Dapatkan keys data, filter dan urutkan sesuai tampilan di gambar (Fungsi ini mungkin perlu disesuaikan dengan key API Anda yang sebenarnya)
// Asumsi keys API Anda: NO, NOTAS, NIP, NAMA, TGL_LAHIR, TMT_ASURANSI, SALDO_AKHIR (atau nama key sejenis)
$keysMap = [
    'NO' => 'NO', // Kolom NO (atau kolom penomor urut jika bukan dari API)
    'notas' => 'NOTAS',
    'EMPLOYEENO' => 'NIP',
    'FULLNAME' => 'NAMA',
    // Ganti nama key API di bawah ini sesuai key Tgl Lahir, TMT Asuransi, Saldo Akhir yang benar
    'DOB' => 'TGL LAHIR',
    'EFFSTARTDATE' => 'TMT ASURANSI',
    'ENDBALANCE' => 'SALDO AKHIR'
];

// Ambil keys yang akan digunakan
$keys = array_keys($keysMap);
$headerDisplay = array_values($keysMap);
$lastCol = chr(ord('A') + count($keys) - 1); // Hitung kolom terakhir (misalnya G)

## 1. Penulisan dan Styling Judul (Baris 1-3) 📝

// Baris 1: Saldo Dana Taspen Save
$sheet->setCellValue('A1', 'Saldo Dana Taspen Save');
// Baris 2: Karyawan Perum BULOG
$sheet->setCellValue('A2', 'Karyawan Perum BULOG');
// Baris 3: Periode 31 Oktober 2025 (Anda mungkin ingin mengganti tanggal ini dengan tanggal saat ini)
// Mengambil timestamp untuk hari terakhir bulan sebelumnya,
// berdasarkan tanggal hari ini (3 Desember 2025).
$tanggal_periode = strtotime('last day of previous month');

// Output akan menjadi: 30 November 2025
$string_periode = 'Periode ' . date('d F Y', $tanggal_periode);

// Masukkan ke dalam sel
$sheet->setCellValue('A3', $string_periode);
// $sheet->setCellValue('A3', 'Periode ' . date('d F Y'));

// Merge dan Center Header
$sheet->mergeCells('A1:' . $lastCol . '1');
$sheet->mergeCells('A2:' . $lastCol . '2');
$sheet->mergeCells('A3:' . $lastCol . '3');
$sheet->getStyle('A1:' . $lastCol . '3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Styling Header (Font Bold dan Besar)
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A3')->getFont()->setSize(12);

// Sisipkan Logo (Asumsi Anda punya logo.png di direktori yang benar)
// Catatan: Menambahkan gambar memerlukan library tambahan/kode yang lebih kompleks. 
// Untuk kemudahan, kita lewati penambahan gambar dan fokus pada struktur.
// Jika ingin menambahkan logo, tambahkan kode penambahan gambar di sini.

// Baris 4: Kosong (separator)


## 2. Pembuatan Header Tabel (Baris 5) <thead>

$columnIndex = 'A';
foreach ($headerDisplay as $header) {
    $sheet->setCellValue($columnIndex . $startRowData, strtoupper($header));
    $columnIndex++;
}

// Styling Header Tabel (Baris 5)
$headerStyleArray = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEBEBEB']], // Abu-abu muda
];
$sheet->getStyle('A' . $startRowData . ':' . $lastCol . $startRowData)->applyFromArray($headerStyleArray);
$sheet->getRowDimension($startRowData)->setRowHeight(30); // Atur tinggi baris header

// Set Lebar Kolom Otomatis
for ($i = 'A'; $i <= $lastCol; $i++) {
    $sheet->getColumnDimension($i)->setAutoSize(true);
}


## 3. Pengisian Data dan Formatting (Baris 6 dst) <tbody>

$row = $startRowData + 1;
$no = 1; // Inisialisasi nomor urut jika diperlukan
foreach ($data as $entry) {
    $col = 'A';
    $sheet->setCellValue($col . $row, $no);
    $col++;
    foreach ($keys as $key) {
        if ($key === 'NO') {
            continue;
        }
        $value = $entry[$key] ?? '';

        if (strtolower($key) === 'notas' || strtolower($key) === 'employeeno') {
            $sheet->getStyle($col . $row)
                ->getNumberFormat()
                ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
        }

        // --- Date Formatting (TGL LAHIR, TMT ASURANSI) ---
        if ((strpos(strtolower($key), 'dob') !== false || strpos(strtolower($key), 'effstartdate') !== false) && !empty($value)) {
            $date = date_create($value);
            if ($date) {
                // Set nilai dan format sebagai teks 'd-m-Y' (sesuai gambar)
                $sheet->setCellValue($col . $row, date_format($date, 'd/m/Y')); // Menggunakan d/m/Y seperti di gambar
            } else {
                $sheet->setCellValue($col . $row, $value);
            }
        }

        // --- Number Formatting (SALDO AKHIR) ---
        elseif (strpos(strtolower($key), 'endbalance') !== false && is_numeric($value)) {
            // Hilangkan pemformatan string (Rp/koma) dan set sebagai angka
            $numericValue = floatval(preg_replace('/[^\d\.]/', '', $value));
            $sheet->setCellValue($col . $row, $numericValue);

            // Terapkan format angka (misalnya, #,##0.00 untuk dua desimal atau #,##0 untuk bilangan bulat)
            $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0');

            // Set Alignment ke Kanan (sesuai format saldo)
            $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        // --- Default/Teks Formatting ---
        else {
            $sheet->setCellValue($col . $row, $value);
        }

        $col++;
    }
    $no++;
    $row++;
}

// Styling Borders untuk semua data (dari Baris 5 sampai baris terakhir)
$styleBorderArray = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]],
];
$sheet->getStyle('A' . $startRowData . ':' . $lastCol . ($row - 1))->applyFromArray($styleBorderArray);


// Output File Download
$filename = "Saldo_Dana_Taspen_Save_BULOG_" . date("Ymd") . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
