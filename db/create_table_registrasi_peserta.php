<?php
include 'db.php';

$sql = "CREATE TABLE IF NOT EXISTS registrasi_peserta (
    id INT PRIMARY KEY AUTO_INCREMENT,
    no_ktp VARCHAR(16),
    nik VARCHAR(20),
    nama VARCHAR(150),
    kelamin VARCHAR(1),
    agama VARCHAR(20),
    tmp_lahir VARCHAR(150),
    tgl_lahir DATE,
    alamat VARCHAR(250),
    lokasi VARCHAR(250),
    jabatan VARCHAR(100),
    status VARCHAR(10),
    tmt_kerja DATE,
    isg DECIMAL(20,2),
    isik DECIMAL(20,2),
    gaji DECIMAL(20,2),
    tmt_asuransi DATE,
    jml_premi DECIMAL(20,2),
    no_rekening VARCHAR(20),
    nama_bank VARCHAR(100),
    nama_nasabah VARCHAR(150),
    npwp VARCHAR(20),
    link_file_mcu VARCHAR(150),
    no_hp VARCHAR(16),
    email VARCHAR(150),
    tinggi_badan VARCHAR(3),
    berat_badan VARCHAR(3),
    rokok_20_perhari VARCHAR(1),
    alkohol_2_perhari VARCHAR(1),
    narkoba VARCHAR(1),
    pernah_dirawat CHAR(1),
    sedang_berobat CHAR(1),
    usia_hamil CHAR(1),
    komplikasi_hamil CHAR(1),
    pernyataan CHAR(1),
    user_verify VARCHAR(50),
    is_verify VARCHAR(1),
    tgl DATE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($conn, $sql)) {
    echo "Tabel 'registrasi_peserta' berhasil dibuat." . PHP_EOL;
} else {
    echo "Error membuat tabel: " . mysqli_error($conn) . PHP_EOL;
}

mysqli_close($conn);
?>
