<?php
require_once __DIR__ . '/../../../auth.php';
require_login();
require_adminbl();

require __DIR__ . '/../../../vendor/autoload.php'; 
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

// Buat header otomatis dari key data
$keys = array_keys($data[0]);
$columnIndex = 'A';

foreach ($keys as $header) {
    $sheet->setCellValue($columnIndex . "1", strtoupper($header));
    $columnIndex++;
}

// Isi data baris per baris
$row = 2;
foreach ($data as $entry) {
    $col = 'A';
    foreach ($keys as $key) {
        // format date jika field adalah tanggal
        if ((strpos(strtolower($key), 'DOB') !== false || strpos(strtolower($key), 'EFFSTARTDATE') !== false) && !empty($entry[$key])) {
            $date = date_create($entry[$key]);
            if ($date) {
                $entry[$key] = date_format($date, 'd-m-Y');
            }
        }
        $sheet->setCellValue($col . $row, $entry[$key] ?? '');
        $col++;
    }
    $row++;
}

// Output File Download
$filename = "data_peserta_" . date("Ymd") . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;

?>
