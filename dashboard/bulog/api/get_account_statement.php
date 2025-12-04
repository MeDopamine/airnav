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
// *Kini juga membawa BALANCEDATE secara utuh*
// ---------------------------------------------
function map_data_fields(array $data_row): array
{
    $balanceDate = $data_row['BALANCEDATE'] ?? null;
    $joinKey = $balanceDate ? substr($balanceDate, 0, 7) : null; // YYYY-MM untuk matching

    return [
        // Data yang ingin diambil dari URL 2:
        'premiDariUrl2' => $data_row['TOTALPREMIUM'] ?? null,
        'tanggalDariUrl2' => $balanceDate, // Nilai BALANCEDATE penuh

        // KUNCI PENGGABUNGAN (YYYY-MM)
        'joinKey' => $joinKey,
    ];
}

// FUNGSI UNTUK MENGUBAH CURRENT MONTH (e.g., 'Nov 2025') MENJADI YYYY-MM
function format_current_month_to_join_key(string $currentMonth): ?string
{
    $time = strtotime("1 " . $currentMonth);
    return $time ? date('Y-m', $time) : null;
}

// ---------------------------------------------
// 1. PANGGILAN CURL KE URL UTAMA
// ---------------------------------------------
$curl_utama = curl_init($url_utama);
curl_setopt_array($curl_utama, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "Authorization: Bearer $token"
    ],
    CURLOPT_TIMEOUT => 15,
]);
$response_utama = curl_exec($curl_utama);
$data = json_decode($response_utama, true);
curl_close($curl_utama);


// ---------------------------------------------
// 2. PANGGILAN CURL KE URL TAMBAHAN
// ---------------------------------------------
$curl_tambahan = curl_init($url_tambahan);
curl_setopt_array($curl_tambahan, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "Authorization: Bearer $token"
    ],
    CURLOPT_TIMEOUT => 15,
]);
$response_tambahan = curl_exec($curl_tambahan);
$data_tambahan = json_decode($response_tambahan, true);
curl_close($curl_tambahan);

// ---------------------------------------------
// 3. PENGAMBILAN & TRANSFORMASI DATA URL 2
// ---------------------------------------------
$data_dari_url_2_mentah = $data_tambahan['acs/report/individu'] ?? [];
$data_dari_url_2_map = [];

if (is_array($data_dari_url_2_mentah)) {
    foreach ($data_dari_url_2_mentah as $row) {
        $mapped_row = map_data_fields($row);
        if (isset($mapped_row['joinKey'])) {
            $data_dari_url_2_map[$mapped_row['joinKey']] = $mapped_row;
        }
    }
}

// ---------------------------------------------
// 4. PENGGABUNGAN DATA (COMBINE) DAN PENGISIAN FIELD BULAN/TANGGAL
// ---------------------------------------------

if (!isset($data['acs/account/statement']['histories']) || !is_array($data['acs/account/statement']['histories'])) {
    $data['acs/account/statement']['histories'] = [];
}

// Iterasi melalui histories URL 1 menggunakan REFERENSI (&row)
foreach ($data['acs/account/statement']['histories'] as $index => &$row) {
    $currentMonth = $row['currentMonth'] ?? null;

    // Konversi currentMonth URL 1 menjadi kunci penggabungan (YYYY-MM)
    $join_key = $currentMonth ? format_current_month_to_join_key($currentMonth) : null;

    // Jika kunci ditemukan di kedua sisi (untuk menimpa premi & mengambil tanggal)
    if ($join_key && isset($data_dari_url_2_map[$join_key])) {
        $row_url2 = $data_dari_url_2_map[$join_key];

        // **REVISI PENTING 1: MENGISI FIELD BULAN DENGAN BALANCEDATE (atau hanya tanggalnya)**
        if (isset($row_url2['tanggalDariUrl2'])) {
            // Ambil hanya bagian tanggal (YYYY-MM-DD) dari BALANCEDATE
            $tanggal_saja = substr($row_url2['tanggalDariUrl2'], 0, 10);
            $row['Bulan'] = $tanggal_saja;
        } else {
            // Jika tanggal dari URL 2 tidak ada, setidaknya gunakan currentMonth
            $row['Bulan'] = $currentMonth;
        }

        // **REVISI PENTING 2: TIMPA FIELD 'premiTopup'**
        if (isset($row_url2['premiDariUrl2'])) {
            $row['premiTopup'] = $row_url2['premiDariUrl2'];
        }
    } else {
        // Jika tidak ada data yang cocok dari URL 2, setidaknya Bulan diisi dari currentMonth
        $row['Bulan'] = $currentMonth;
    }
}


// ---------------------------------------------
// 5. PROSES FORMATTING RUPIAH
// ---------------------------------------------

if (isset($data['acs/account/statement']['histories']) && is_array($data['acs/account/statement']['histories'])) {

    $fieldsToFormat = [
        'akumulasiPremi',
        'premiTopup',
        'pengembangan',
        'saldoAwal',
        'saldoAkhir',
        'accBalance'
    ];

    foreach ($data['acs/account/statement']['histories'] as &$row) {

        foreach ($fieldsToFormat as $field) {
            if (isset($row[$field])) {

                $value = floatval($row[$field]);
                $formatted = number_format($value, 0, ',', '.');
                $row[$field] = $formatted;
            }
        }
    }
}

echo json_encode(
    $data,
);
