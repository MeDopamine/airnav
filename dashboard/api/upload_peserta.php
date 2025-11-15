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
set_exception_handler(function ($e) {
  http_response_code(500);
  header('Content-Type: application/json');
  error_log('Fatal error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
  echo json_encode(['error' => 'Fatal: ' . $e->getMessage()]);
  exit;
});
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
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
  // $idbatch = "batch_" . date('Ymd_His') . "_" . uniqid();

  // Jika baris pertama adalah judul gabungan, ambil baris kedua sebagai header
  $headerRowIdx = 0;
  foreach ($rows as $i => $r) {
    // Cari baris pertama yang mengandung "NIK" dan "Periode" dst
    if (in_array('NIK', $r) && in_array('Periode Invoice', $r)) {
      $headerRowIdx = $i;
      break;
    }
  }
  $header = array_map(function ($v) {
    return is_string($v) ? trim($v) : $v;
  }, $rows[$headerRowIdx]);
  // Ambil data mulai setelah header
  $dataRows = array_slice($rows, $headerRowIdx + 1);

  // Mapping kolom Excel ke kolom database (tambahkan jenis dan rincian premi)
  $map = [
    'Nama' => 'nama',
    'TMT Member' => 'tmt_asuransi',
    'NIP' => 'nip',
    'NIK' => 'nik',
    'Periode Invoice' => 'periode',
    'Jenis Invoice' => 'jenis_premi',
    'GAPOK' => 'gapok',
    // 'Tgl Lahir' => 'tgl_lahir',
    'Premi Karyawan' => 'jml_premi_krywn',
    'Premi Prushaan' => 'jml_premi_pt',
    'Total Premi' => 'total_premi',
    'PIC' => 'pic',
    'Status' => 'status',
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
  $jenis_premi_map = [
    'JHT REGULAR' => 1,
    'JHT TOPUP' => 2,
    'PKP REGULAR' => 3,
  ];
  $batch_map = [];
  foreach ($dataRows as $row) {
    // Lewati baris kosong
    if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) continue;
    $processed++;
    $excelData = array_combine($header, $row);
    $dbData = [];
    foreach ($map as $excelCol => $dbCol) {
      $val = isset($excelData[$excelCol]) ? $excelData[$excelCol] : null;
      if ($dbCol === 'tmt_asuransi' && !empty($val)) {
        // Cek jika ini adalah angka serial Excel (mis. 43831)
        if (is_numeric($val) && $val > 25569) { // 25569 = 1970-01-01
          // Konversi Excel timestamp ke Unix timestamp, lalu format
          $unixTimestamp = ($val - 25569) * 86400;
          $val = date('Y-m-d', $unixTimestamp);
        } else {
          // Coba parsing format string (DD/MM/YYYY atau DD-MM-YYYY dll)
          $ts = strtotime($val);
          if ($ts !== false) {
            $val = date('Y-m-d', $ts);
          } else {
            $val = null; // Gagal parsing, set null
          }
        }
      }
      if ($dbCol === 'jenis_premi' && $val !== null) {
        // Normalisasi nilai dari Excel (hapus spasi, ubah ke huruf besar)
        $normalized_val = strtoupper(trim((string)$val));

        // Cek apakah teks itu ada di map Anda
        if (isset($jenis_premi_map[$normalized_val])) {
          // Jika ada, ganti nilainya dengan angka
          $val = $jenis_premi_map[$normalized_val];
        }
        // Jika tidak ada di map (misalnya, nilainya sudah "1", "2", atau "3"),
        // $val akan tetap seperti aslinya, yang sudah benar.
      }
      if ($dbCol === 'status' && !empty($val)) {
        // Normalisasi status: jika "Aktif" set 1, jika "Non Aktif" set 0
        $normalized_status = strtolower(trim((string)$val));
        if ($normalized_status === 'aktif') {
          $val = 1;
        } else {
          $val = 0; // Jika status tidak dikenali, set null
        }
      }
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
      if (in_array($dbCol, ['jml_premi_krywn', 'jml_premi_pt', 'total_premi', 'gapok'])) {
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
    // --- TAMBAHKAN BLOK INI ---
    // Buat idbatch unik BERDASARKAN jenis_premi

    $current_jenis_premi = $dbData['jenis_premi'] ?? 'unknown';

    if (isset($batch_map[$current_jenis_premi])) {
      // Jika kita sudah membuat idbatch untuk jenis ini, gunakan lagi
      $idbatch = $batch_map[$current_jenis_premi];
    } else {
      // Jika ini pertama kalinya kita melihat jenis ini, buat idbatch baru
      $idbatch = "batch_" . date('Ymd_His') . "_" . $current_jenis_premi . "_" . uniqid();
      $batch_map[$current_jenis_premi] = $idbatch; // Simpan untuk baris berikutnya
    }
    // --- AKHIR BLOK ---
    // Validasi minimal NIK dan Periode
    if (empty($dbData['nip']) || empty($dbData['periode'])) continue;
    // Cek duplikasi berdasarkan NIK dan Periode
    // $cekStmt = $conn->prepare("SELECT COUNT(*) FROM data_peserta WHERE nik = ? AND periode = ?");
    // if ($cekStmt === false) continue;
    // $cekStmt->bind_param('ss', $dbData['nik'], $dbData['periode']);
    // $cekStmt->execute();
    // $cekStmt->bind_result($count);
    // $cekStmt->fetch();
    // $cekStmt->close();
    // if ($count > 0) continue; // skip jika sudah ada

    // Ensure status_data is explicitly set so imported rows are pending approval (0)
    // Insert with additional premi fields: jenis_premi, jml_premi_krywn, jml_premi_pt
    $stmt = $conn->prepare("INSERT INTO data_peserta (nik, nama, nip, periode, jenis_premi, gapok, tmt_asuransi, jml_premi_krywn, jml_premi_pt, total_premi, pic, `status`, status_data, idbatch, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_DATE)");
    // $stmt = $conn->prepare("INSERT INTO data_peserta (nik, periode, jenis_premi, jml_premi_krywn, jml_premi_pt, total_premi, pic, status_data, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_DATE)");
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
    $b_nama = isset($dbData['nama']) ? $dbData['nama'] : null;
    $b_nip = isset($dbData['nip']) ? $dbData['nip'] : null;
    $b_periode = isset($dbData['periode']) ? $dbData['periode'] : null;
    $b_jenis_premi = isset($dbData['jenis_premi']) ? $dbData['jenis_premi'] : null;
    $b_gapok = isset($dbData['gapok']) ? $dbData['gapok'] : null;
    $b_tmt_asuransi = isset($dbData['tmt_asuransi']) ? $dbData['tmt_asuransi'] : null;
    // $b_tgl_lahir = isset($dbData['tgl_lahir']) ? $dbData['tgl_lahir'] : null;
    $b_jml_premi_krywn = isset($dbData['jml_premi_krywn']) ? $dbData['jml_premi_krywn'] : null;
    $b_jml_premi_pt = isset($dbData['jml_premi_pt']) ? $dbData['jml_premi_pt'] : null;
    $b_total_premi = isset($dbData['total_premi']) ? $dbData['total_premi'] : null;
    $b_pic = isset($dbData['pic']) ? $dbData['pic'] : null;
    $b_status = isset($dbData['status']) ? $dbData['status'] : null;
    // Bind as strings to avoid locale/float conversion problems; DB will cast where necessary
    $stmt->bind_param(
      'ssssssssssssss',
      $b_nik,
      $b_nama,
      $b_nip,
      $b_periode,
      $b_jenis_premi,
      $b_gapok,
      $b_tmt_asuransi,
      $b_jml_premi_krywn,
      $b_jml_premi_pt,
      $b_total_premi,
      $b_pic,
      $b_status,
      $status_data_import,
      $idbatch
    );
    if ($stmt->execute()) $inserted++;
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
