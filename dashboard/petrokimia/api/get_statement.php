<?php
require_once __DIR__ . '/../../../auth.php';
require_login();
require_adminpk();

header('Content-Type: application/json');

$polis = "TS16AJTL00000137" ?? '';

// --- MASUKKAN TOKEN DI SINI ---
$token = "3VGUkzXpm0mdkE1jDsPALWkbOmLfFbOJxF0O8rHc";   // contoh: eyJhbGciOiJIUzI1...

$baseUrl = "https://api-gina.taspenlife.com/acs/report/individuals";
$url = $baseUrl . "?tipe=cekDashboardPetrokimia" ;

// --- CURL REQUEST ---
$curl = curl_init($url);

curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        // "Authorization: Bearer $token"    // <── TOKEN MASUK DI SINI
    ],
    CURLOPT_TIMEOUT => 15,
]);

$response = curl_exec($curl);

$data = json_decode($response, true);

if (isset($data['acs/report/individu']) && is_array($data['acs/report/individu'])) {

    // Field yang ingin diformat menjadi mata uang
    $fieldsToFormat = [
        'STARTBALANCE',
        'TOTALPREMIUM',
        'TOPUPAMOUNT',
        'INVESTMENT',
        'WITHDRAW',
        'ENDBALANCE'
    ];

    foreach ($data['acs/report/individu'] as $index => $row) {

        foreach ($fieldsToFormat as $field) {
            if (isset($row[$field])) {

                // Hilangkan koma/format lain jika numeric string
                $value = floatval($row[$field]);

                // Format Rupiah
                $formatted = "Rp " . number_format($value, 0, ',', '.');

                $data['acs/report/individu'][$index][$field] = $formatted;
            }
        }
    }
}

echo json_encode(
    $data,
);
