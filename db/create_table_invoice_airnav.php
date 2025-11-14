<?php
include 'db.php';

$sql = "CREATE TABLE IF NOT EXISTS invoice_airnav (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    periode VARCHAR(6),
    spano VARCHAR(20),
    jenis_premi INT,
    produk_premi VARCHAR(9),
    invoice_no VARCHAR(50),
    jumlah INT,
    isg BIGINT,
    isik BIGINT,
    thp BIGINT,
    jml_premi_krywn BIGINT,
    jml_premi_pt BIGINT,
    total_premi BIGINT,
    created_at DATETIME,
    deleted_at DATE,
    printed_at DATE,
    urlinvoice VARCHAR(108),
    payed_at DATE,
    status INT,
    pic VARCHAR(20),
    flag INT
);";

if (mysqli_query($conn, $sql)) {
    echo "Tabel invoice_airnav berhasil dibuat.";
} else {
    echo "Error: " . mysqli_error($conn);
}

mysqli_close($conn);
?>
