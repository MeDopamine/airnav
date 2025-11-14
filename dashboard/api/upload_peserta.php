<?php
// File: upload_peserta.php
// Endpoint untuk upload dan import data peserta dari file Excel


require_once __DIR__ . '/../../auth.php';
require_login();
// only admin may upload peserta files
if (!is_admin()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}
// Matikan tampilan error PHP ke output agar tidak mengganggu JSON
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Tangani error fatal agar tetap output JSON
set_exception_handler(function($e) {
    http_response_code(500);
    header('Content-Type: application/json');
    error_log('Fatal error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    echo json_encode(['error' => 'Fatal: ' . $e->getMessage()]);
    exit;
});
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    header('Content-Type: application/json');
    error_log("PHP error [$errno] $errstr in $errfile:$errline");
    echo json_encode(['error' => "PHP error: $errstr"]);
    exit;
});

require_once '../../vendor/autoload.php';
require_once '../../db/db.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metode tidak diizinkan']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'File tidak ditemukan atau gagal upload']);
    exit;
}

$fileTmpPath = $_FILES['file']['tmp_name'];
$fileName = $_FILES['file']['name'];
$fileSize = $_FILES['file']['size'];
$fileType = $_FILES['file']['type'];
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

$allowedExt = ['xls', 'xlsx'];
if (!in_array($fileExt, $allowedExt)) {
    http_response_code(400);
    echo json_encode(['error' => 'Format file tidak didukung']);
    exit;
}

try {
    $spreadsheet = IOFactory::load($fileTmpPath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();
    
    // Jika baris pertama adalah judul gabungan, ambil baris kedua sebagai header
    $headerRowIdx = 0;
    foreach ($rows as $i => $r) {
        // Cari baris pertama yang mengandung "NIK" dan "Periode" dst
        if (in_array('NIK', $r) && in_array('Periode', $r)) {
            $headerRowIdx = $i;
            break;
        }
    }
    $header = array_map(function($v) { return is_string($v) ? trim($v) : $v; }, $rows[$headerRowIdx]);
    // Ambil data mulai setelah header
    $dataRows = array_slice($rows, $headerRowIdx + 1);

    // Mapping kolom Excel ke kolom database (tambahkan jenis dan rincian premi)
    $map = [
        'NIK' => 'nik',
        'Periode' => 'periode',
        'Jenis Premi' => 'jenis_premi',
        // 'Tgl Lahir' => 'tgl_lahir',
        'Jumlah Premi Karyawan' => 'jml_premi_krywn',
        'Jumlah Premi PT' => 'jml_premi_pt',
        'Total Premi' => 'total_premi',
        'PIC' => 'pic',
        // 'Approval' => 'status_data',
    ];

    // Validasi header (tanpa Created At)
    foreach (array_keys($map) as $col) {
        if (!in_array($col, $header)) {
            http_response_code(400);
            echo json_encode(['error' => "Kolom $col tidak ditemukan di file Excel"]);
            exit;
        }
    }

    $conn = $conn ?? null;
    if (!$conn) {
        http_response_code(500);
        echo json_encode(['error' => 'Koneksi database gagal']);
        exit;
    }
    $inserted = 0;
    $processed = 0;
    $log_status = 'Berhasil';
    foreach ($dataRows as $row) {
        // Lewati baris kosong
    if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) continue;
        $processed++;
        $excelData = array_combine($header, $row);
        $dbData = [];
        foreach ($map as $excelCol => $dbCol) {
            $val = isset($excelData[$excelCol]) ? $excelData[$excelCol] : null;
            // Konversi tanggal lahir jika kolom ini
            // if ($dbCol === 'tgl_lahir' && !empty($val)) {
            //     // Jika format Excel: 10 07 1978 atau 10/07/1978 atau 10-07-1978
            //     $val = str_replace(['/', '-', '.'], ' ', $val);
            //     $parts = preg_split('/\s+/', trim($val));
            //     if (count($parts) === 3) {
            //         // Asumsi DD MM YYYY
            //         $val = sprintf('%04d-%02d-%02d', $parts[2], $parts[1], $parts[0]);
            //     }
            // }
            // Normalisasi angka (hapus pemisah ribuan, simbol Rp, dll) dan format 2 desimal
            if (in_array($dbCol, ['jml_premi_krywn','jml_premi_pt','total_premi'])) {
                if ($val === null || $val === '') {
                    $val = null;
                } else {
                    $s = (string)$val;
                    // Hapus simbol mata uang dan spasi normalisasi
                    $s = trim(str_ireplace(['rp', 'Rp', ' ', '\xC2\xA0'], ['', '', '', ''], $s));
                    // Simpan hanya digit, titik, koma dan minus
                    $s = preg_replace('/[^0-9\.,\-]/', '', $s);
                    if ($s === '' || $s === '-') {
                        $val = null;
                    } else {
                        $lastDot = strrpos($s, '.');
                        $lastComma = strrpos($s, ',');
                        if ($lastComma !== false && $lastDot !== false) {
                            // Jika kedua ada, tentukan mana yang kemungkinan separator desimal
                            if ($lastComma > $lastDot) {
                                // Komma sebagai desimal, titik sebagai ribuan
                                $s = str_replace('.', '', $s);
                                $s = str_replace(',', '.', $s);
                            } else {
                                // Titik sebagai desimal, koma sebagai ribuan
                                $s = str_replace(',', '', $s);
                            }
                        } elseif ($lastComma !== false) {
                            // Hanya koma hadir: anggap koma sebagai desimal
                            $s = str_replace(',', '.', $s);
                        } else {
                            // Hanya titik atau tidak ada: jika ada lebih dari 1 titik, kemungkinan titik ribuan
                            if (substr_count($s, '.') > 1) {
                                $s = str_replace('.', '', $s);
                            }
                            // jika hanya satu titik tetap dianggap desimal
                        }

                        // Cast ke float dan format 2 desimal untuk simpan
                        // Gunakan (float) untuk meng-handle nilai besar, kemudian format
                        $num = (float) $s;
                        $val = number_format($num, 2, '.', '');
                    }
                }
            }
            $dbData[$dbCol] = $val;
        }
        // Validasi minimal NIK dan Periode
        if (empty($dbData['nik']) || empty($dbData['periode'])) continue;
        // Cek duplikasi berdasarkan NIK dan Periode
        $cekStmt = $conn->prepare("SELECT COUNT(*) FROM data_peserta WHERE nik = ? AND periode = ?");
        if ($cekStmt === false) continue;
        $cekStmt->bind_param('ss', $dbData['nik'], $dbData['periode']);
        $cekStmt->execute();
        $cekStmt->bind_result($count);
        $cekStmt->fetch();
        $cekStmt->close();
    if ($count > 0) continue; // skip jika sudah ada

        // Ensure status_data is explicitly set so imported rows are pending approval (0)
        // Insert with additional premi fields: jenis_premi, jml_premi_krywn, jml_premi_pt
        $stmt = $conn->prepare("INSERT INTO data_peserta (nik, periode, jenis_premi, jml_premi_krywn, jml_premi_pt, total_premi, pic, status_data, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_DATE)");
        if ($stmt === false) {
            http_response_code(500);
            $err = $conn->error;
            error_log('Prepare failed: ' . $err);
            echo json_encode(['error' => 'Query error: ' . $err]);
            exit;
        }
        $status_data_import = 0;
        // mysqli::bind_param requires variables passed by reference. Create local vars.
        $b_nik = isset($dbData['nik']) ? $dbData['nik'] : null;
        $b_periode = isset($dbData['periode']) ? $dbData['periode'] : null;
        $b_jenis_premi = isset($dbData['jenis_premi']) ? $dbData['jenis_premi'] : null;
        // $b_tgl_lahir = isset($dbData['tgl_lahir']) ? $dbData['tgl_lahir'] : null;
        $b_jml_premi_krywn = isset($dbData['jml_premi_krywn']) ? $dbData['jml_premi_krywn'] : null;
        $b_jml_premi_pt = isset($dbData['jml_premi_pt']) ? $dbData['jml_premi_pt'] : null;
        $b_total_premi = isset($dbData['total_premi']) ? $dbData['total_premi'] : null;
        $b_pic = isset($dbData['pic']) ? $dbData['pic'] : null;
        // Bind as strings to avoid locale/float conversion problems; DB will cast where necessary
        $stmt->bind_param('ssssssss',
            $b_nik,
            $b_periode,
            $b_jenis_premi,
            // $b_tgl_lahir,
            $b_jml_premi_krywn,
            $b_jml_premi_pt,
            $b_total_premi,
            $b_pic,
            $status_data_import
        );
        if ($stmt->execute()) $inserted++;
    }

    // === Kirim ke endpoint API eksternal ===
    try {
        // Siapkan array payload dari data yang berhasil di-insert
        $apiPayload = [
            "airnav/premi/upload" => []
        ];

        // Ambil kembali data yang baru diinsert (bisa dari memori atau query)
        $result = $conn->query("SELECT nik, periode, jenis_premi, jml_premi_krywn, jml_premi_pt, total_premi, pic 
                                FROM data_peserta 
                                WHERE DATE(created_at) = CURRENT_DATE");

        while ($row = $result->fetch_assoc()) {
            $apiPayload["airnav/premi/upload"][] = [
                "periode" => $row['periode'],
                "nik" => $row['nik'],
                "jenis_premi" => $row['jenis_premi'],
                "tgl_lahir" => "",
                "tgl_diangkat" => "",
                "tmt_asuransi" => "",
                "isg" => "1",
                "isik" => "0",
                "jml_rapel" => 0,
                "jml_premi_karyawan" => (float)$row['jml_premi_krywn'],
                "jml_premi_perusahaan" => (float)$row['jml_premi_pt'],
                "total_premi" => (float)$row['total_premi'],
                "link_file" => $fileName,
                "pic" => $row['pic'],
                "idbatch" => "BATCH_1",
                "tgl" => date('Y-m-d'),
                "status_data" => "1"
                // "periode"=> "202410",
                // "nik"=> "123456",
                // "jenis_premi"=> "1",
                // "tgl_lahir"=> "1990-01-15",
                // "tgl_diangkat"=> "2015-03-01",
                // "tmt_asuransi"=> "2015-04-01",
                // "isg"=> "1",
                // "isik"=> "0",
                // "jml_rapel"=> 0,
                // "jml_premi_karyawan"=> 100000,
                // "jml_premi_perusahaan"=> 200000,
                // "total_premi"=> 300000,
                // "link_file"=> "test",
                // "pic"=> "PIC_USER_A",
                // "idbatch"=> "BATCH_001",
                // "tgl"=> "2024-10-28",
                // "status_data"=> "1"
            ];
        }

        // Encode ke JSON
        $jsonPayload = json_encode($apiPayload);

        // Kirim via cURL
        $ch = curl_init("https://dev-api-gina.taspenlife.com/airnav/premi/uploads"); // ganti dengan endpoint kamu
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
        ]);

        $apiResponse = curl_exec($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            error_log("Error kirim ke API: $curlErr");
        } else {
            error_log("Json Payload: $jsonPayload");
            error_log("Respon API: $apiResponse");
        }
    } catch (Exception $ex) {
        error_log("Gagal kirim ke API: " . $ex->getMessage());
    }


    // Catat ke riwayat upload
    $log_status = ($inserted > 0) ? 'Berhasil' : 'Gagal';


    $log_stmt = $conn->prepare("INSERT INTO riwayat_upload (nama_file, tipe_data, tanggal_upload, status) VALUES (?, 'Data Peserta', NOW(), ?)");
    if ($log_stmt) {
        $log_stmt->bind_param('ss', $fileName, $log_status);
        $log_stmt->execute();
        $log_stmt->close();
    }
    if ($processed === 0) {
        echo json_encode(['success' => false, 'inserted' => 0, 'error' => 'Tidak ada data yang diproses. Cek format file.']);
    } else {
        echo json_encode(['success' => true, 'inserted' => $inserted]);
    }
    exit;
} catch (Exception $e) {
    http_response_code(500);
    error_log('Upload peserta error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    echo json_encode(['error' => 'Gagal memproses file: ' . $e->getMessage()]);
    exit;
}
