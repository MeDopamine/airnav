<?php
include 'db.php';

$sql = "CREATE TABLE IF NOT EXISTS topup (
    id INT PRIMARY KEY AUTO_INCREMENT,
    created_at DATETIME(6),
    notas VARCHAR(25),
    jmltopup DECIMAL(18,2),
    tmt_topup DATE,
    jenis_topup DECIMAL(18,2),
    user_verify VARCHAR(25),
    status INT,
    status_data INT,
    tgltopup DATE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($conn, $sql)) {
    echo "Tabel 'topup' berhasil dibuat." . PHP_EOL;
} else {
    echo "Error membuat tabel: " . mysqli_error($conn) . PHP_EOL;
}

mysqli_close($conn);
?>
