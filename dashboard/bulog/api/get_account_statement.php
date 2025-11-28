<?php
require_once __DIR__ . '/../../../auth.php';
require_login();
require_adminbl();

header('Content-Type: application/json');

$polis = $_GET['polis'] ?? '';

// --- MASUKKAN TOKEN DI SINI ---
$token = "3VGUkzXpm0mdkE1jDsPALWkbOmLfFbOJxF0O8rHc";

// --- URL 1: URL UTAMA (account/statements) ---
$url_utama = "https://api-gina.taspenlife.com/acs/account/statements/$polis";

// --- URL 2: URL TAMBAHAN (report/individuals) ---
$url_tambahan = "https://api-gina.taspenlife.com/acs/report/individuals?tipe=cekAkunstatmentBulogByNotasNew&notas=$polis";

// ---------------------------------------------
// FUNGSI BANTUAN: MAP FIELD DARI URL 2 KE URL 1
// ---------------------------------------------
function map_data_fields(array $data_row): array
{
    return [
        // Map data dari URL 2 (Kiri) ke field yang dibutuhkan formatting (Kanan)
        'akumulasiPremi' => $data_row['ACCUMULATEDPREMIUM'] ?? null,
        'premiTopup' => $data_row['TOTALPREMIUM'] ?? null,
        'pengembangan' => $data_row['INVESTMENT'] ?? null,
        'saldoAwal' => $data_row['STARTBALANCE'] ?? null,
        'saldoAkhir' => $data_row['ENDBALANCE'] ?? null,

        // Field lain yang mungkin dibutuhkan, bisa diisi dengan nilai yang sama atau dinamis.
        'accBalance' => $data_row['ENDBALANCE'] ?? null,
        'tglTrx' => $data_row['BALANCEDATE'] ?? null,
        'keterangan' => 'DATA DARI REPORT/INDIVIDUALS', // Tambahkan keterangan agar mudah dibedakan
    ];
}

// ---------------------------------------------
// 1. PANGGILAN CURL KE URL UTAMA (Mendapatkan Struktur Utama)
// ---------------------------------------------
$curl = curl_init($url_utama);
curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "Authorization: Bearer $token"
    ],
    CURLOPT_TIMEOUT => 15,
]);
$response_utama = curl_exec($curl);
$data = json_decode($response_utama, true);

// ---------------------------------------------
// 2. PANGGILAN CURL KE URL TAMBAHAN (Mendapatkan Data Tambahan)
// ---------------------------------------------
$curl = curl_init($url_tambahan);
curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "Authorization: Bearer $token"
    ],
    CURLOPT_TIMEOUT => 15,
]);
$response_tambahan = curl_exec($curl);
$data_tambahan = json_decode($response_tambahan, true);
curl_close($curl);

// ---------------------------------------------
// 3. PENGAMBILAN & TRANSFORMASI DATA URL 2
// ---------------------------------------------
$data_dari_url_2_mentah = $data_tambahan['acs/report/individu'] ?? [];
$data_dari_url_2_terformat = [];

// Transformasi data mentah dari URL 2 ke format yang diharapkan oleh 'histories'
if (is_array($data_dari_url_2_mentah)) {
    foreach ($data_dari_url_2_mentah as $row) {
        $data_dari_url_2_terformat[] = map_data_fields($row);
    }
}

// ---------------------------------------------
// 4. PENGGABUNGAN DATA (MERGE)
// ---------------------------------------------

// Inisialisasi struktur histories jika belum ada
if (!isset($data['acs/account/statement']) || !is_array($data['acs/account/statement'])) {
    $data['acs/account/statement'] = [];
}
if (!isset($data['acs/account/statement']['histories']) || !is_array($data['acs/account/statement']['histories'])) {
    $data['acs/account/statement']['histories'] = [];
}

// Gabungkan data yang sudah terformat dari URL 2 ke array histories URL 1
if (count($data_dari_url_2_terformat) > 0) {
    $data['acs/account/statement']['histories'] = array_merge(
        // $data['acs/account/statement']['histories'],
        $data_dari_url_2_terformat
    );
}

// ---------------------------------------------
// 5. PROSES FORMATTING RUPIAH (Berjalan pada data GABUNGAN)
// ---------------------------------------------

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

                // Simpan nilai yang diformat
                $data['acs/account/statement']['histories'][$index][$field] = $formatted;
            }
        }
    }
}

echo json_encode(
    $data,
);
