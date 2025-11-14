<?php
include 'db.php';

$sql = "CREATE TABLE IF NOT EXISTS data_peserta (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    periode VARCHAR(6),
    jenis_premi INT,
    tgl_lahir DATE,
    tgl_diangkat DATE,
    tmt_asuransi DATE,
    isg BIGINT,
    isik BIGINT,
    jml_rapel BIGINT,
    jml_premi_krywn BIGINT,
    jml_premi_pt BIGINT,
    total_premi BIGINT,
    link_file VARCHAR(50),
    pic VARCHAR(20),
    idbatch VARCHAR(15),
    status INT,
    userid VARCHAR(15),
    created_at DATE,
    nik VARCHAR(15),
    notas VARCHAR(15),
    status_data BIGINT
);";

if (mysqli_query($conn, $sql)) {
    echo "Tabel data_peserta berhasil dibuat.";
} else {
    echo "Error: " . mysqli_error($conn);
}

mysqli_close($conn);
?>
