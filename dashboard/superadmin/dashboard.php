<?php
// Halaman dashboard terpadu untuk role superadmin
include_once __DIR__ . '/../../auth.php';
require_login();
require_superadmin();

include_once __DIR__ . '/partials/_init.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Superadmin Dashboard</title>
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="../assets/css/tailwind.output.css">
    <!-- Font Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" href="https://placehold.co/32x32/0033A0/FFFFFF?text=S" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">

        <div class="flex-1 flex flex-col overflow-hidden">
            <?php render_partial('header'); ?>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6 md:p-8">
                <div class="max-w-4xl mx-auto space-y-6">
                    <div class="bg-white rounded-xl shadow p-6">
                        <h1 class="text-2xl font-semibold">Halaman Superadmin</h1>
                        <p class="text-sm text-gray-600 mt-1">Akses cepat ke dashboard Airnav, Bulog, dan Petrokimia.</p>
                    </div>

                    <div class="flex flex-col md:flex-row md:justify-end gap-6">
                        <div class="bg-white rounded-xl shadow p-6 flex flex-col justify-between w-full md:w-1/3">
                            <div>
                                <div class="text-3xl text-blue-600 mb-2"><i class="fa-solid fa-plane"></i></div>
                                <h3 class="text-lg font-semibold">Airnav</h3>
                                <p class="text-sm text-gray-500 mt-2">Manajemen data peserta utama, upload, dan pembuatan invoice.</p>
                            </div>
                            <div class="mt-4">
                                <a href="/dashboard/index.php" class="inline-block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-3 rounded">Buka Airnav</a>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow p-6 flex flex-col justify-between w-full md:w-1/3">
                            <div>
                                <div class="text-3xl text-green-600 mb-2"><i class="fa-solid fa-warehouse"></i></div>
                                <h3 class="text-lg font-semibold">Bulog</h3>
                                <p class="text-sm text-gray-500 mt-2">Akses modul Bulog: pencarian eksternal, sinkronisasi, dan laporan.</p>
                            </div>
                            <div class="mt-4">
                                <a href="/dashboard/bulog/data_peserta.php" class="inline-block w-full text-center bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-3 rounded">Buka Bulog</a>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow p-6 flex flex-col justify-between w-full md:w-1/3">
                            <div>
                                <div class="text-3xl text-red-600 mb-2"><i class="fa-solid fa-industry"></i></div>
                                <h3 class="text-lg font-semibold">Petrokimia</h3>
                                <p class="text-sm text-gray-500 mt-2">Kelola peserta dan invoice khusus Petrokimia.</p>
                            </div>
                            <div class="mt-4">
                                <a href="/dashboard/petrokimia/dashboard.php" class="inline-block w-full text-center bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-3 rounded">Buka Petrokimia</a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php render_partial('footer'); ?>
</body>

</html>
