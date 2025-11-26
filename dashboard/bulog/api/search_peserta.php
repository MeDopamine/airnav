<?php
// API untuk search data peserta berdasarkan NOTAS (menggunakan API eksternal)
require_once __DIR__ . '/../../../auth.php';
require_login();
require_adminbl();

// db.php tidak diperlukan karena kita menggunakan API
// include '../../../db/db.php'; 

header('Content-Type: application/json');

// Mengambil input, yang sekarang diasumsikan sebagai NOTAS
$notas = isset($_GET['name']) ? trim($_GET['name']) : '';

if (empty($notas)) {
    echo json_encode([
        'ok' => false,
        'data' => [],
        'msg' => 'Nama atau NOTAS tidak boleh kosong'
    ]);
    exit;
}

// --- KONFIGURASI API EKSTERNAL ---
// Ganti dengan token API Taspen Life Anda yang valid
$token = "3VGUkzXpm0mdkE1jDsPALWkbOmLfFbOJxF0O8rHc";
$baseUrl = "https://api.taspenlife.com/acs/report/individuals";
$url = $baseUrl . "?tipe=cekPesertaBulogByNotas&notas=" . urlencode($notas);

// --- PANGGILAN API DENGAN CURL ---
$curl = curl_init($url);

curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        // Masukkan Token Anda di sini
        "Authorization: Bearer $token"
    ],
    CURLOPT_TIMEOUT => 15,
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

// Cek jika terjadi error CURL
if ($response === false) {
    echo json_encode([
        'ok' => false,
        'data' => [],
        'msg' => 'Gagal terhubung ke API eksternal: ' . curl_error($curl)
    ]);
    exit;
}

// Dekode respons JSON
$apiData = json_decode($response, true);

// Cek jika HTTP Code menunjukkan kegagalan
if ($httpCode !== 200) {
    $errorMsg = isset($apiData['message']) ? $apiData['message'] : "API eksternal mengembalikan HTTP Status $httpCode.";
    echo json_encode([
        'ok' => false,
        'data' => $apiData ?? [],
        'msg' => 'Kesalahan API: ' . $errorMsg
    ]);
    exit;
}

// Asumsi: Jika API berhasil (HTTP 200), respons akan langsung dikembalikan.
// Struktur respons dari API ini mungkin berbeda dari struktur database lokal Anda.

$raw_data = $apiData['acs/report/individu'];
$unique_data = [];
$seen_notas = [];

// --- LOGIKA DEDUPLIKASI BERDASARKAN NOTAS ---

foreach ($raw_data as $entry) {
    // Cek apakah 'notas' ada dan belum pernah dilihat
    if (isset($entry['notas']) && !in_array($entry['notas'], $seen_notas)) {

        // Simpan entri ini sebagai data unik
        $unique_data[] = $entry;

        // Tandai 'notas' ini sudah diproses
        $seen_notas[] = $entry['notas'];
    }
}

// --- OUTPUT HASIL DEDUPLIKASI ---

// Jika API mengembalikan data (asumsi struktur API memiliki kunci 'data')
if (empty($unique_data)) {
    // Tidak ada data unik yang ditemukan setelah deduplikasi.
    echo json_encode([
        'ok' => true, // Status 'ok' tetap true jika API berhasil dihubungi
        'data' => [],
        'count' => 0,
        'msg' => 'Tidak ada data peserta unik ditemukan untuk NOTAS: ' . htmlspecialchars($notas)
    ]);
    exit;
}

// Mengembalikan data unik yang telah diproses.
echo json_encode([
    'ok' => true,
    'data' => $unique_data,
    'count' => count($unique_data)
]);
