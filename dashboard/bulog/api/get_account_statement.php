<?php
require_once __DIR__ . '/../../../auth.php';
require_login();
require_adminbl();

header('Content-Type: application/json');

$polis = $_GET['polis'] ?? '';

// --- MASUKKAN TOKEN DI SINI ---
$token = "CheiUSkZUiH8UE7Z50O3kDKf2v7gNcd0FYch2GQO";   // contoh: eyJhbGciOiJIUzI1...

$url = "https://dev-api.taspenlife.com/acs/account/statements/TS16AJTL00000137";

// --- CURL REQUEST ---
$curl = curl_init($url);

curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "Authorization: Bearer $token"    // <── TOKEN MASUK DI SINI
    ],
    CURLOPT_TIMEOUT => 15,
]);

// $url = "https://dev-api.taspenlife.com/acs/account/statements/$polis";

// $curl = curl_init($url);
// curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($curl);
curl_close($curl);

$data = json_decode($response, true);

if (isset($data['acs/account/statement']['histories']) && is_array($data['acs/account/statement']['histories'])) {

    // Field yang ingin diformat menjadi mata uang
    $fieldsToFormat = [
        'akumulasiPremi',
        'premiTopup',
        'pengembangan',
        'saldoAwal',
        'saldoAkhir',
        'accBalance'
    ];

    foreach ($data['acs/account/statement']['histories'] as $index => $row) {

        foreach ($fieldsToFormat as $field) {
            if (isset($row[$field])) {

                // Hilangkan koma/format lain jika numeric string
                $value = floatval($row[$field]);

                // Format Rupiah
                $formatted = "Rp " . number_format($value, 0, ',', '.');

                $data['acs/account/statement']['histories'][$index][$field] = $formatted;
            }
        }
    }
}

echo json_encode($data);