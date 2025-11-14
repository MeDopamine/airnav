<?php
include 'db.php';

$sql = "CREATE TABLE IF NOT EXISTS riwayat_upload (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    nama_file VARCHAR(100),
    tipe_data VARCHAR(30),
    tanggal_upload DATETIME,
    status VARCHAR(20)
);";

if (mysqli_query($conn, $sql)) {
    echo "Tabel riwayat_upload berhasil dibuat.";
} else {
    echo "Error: " . mysqli_error($conn);
}

mysqli_close($conn);
?>
