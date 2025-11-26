<?php
// Require authentication - adminpk bisa akses
include_once __DIR__ . '/../../auth.php';
require_login();
require_adminpk();

include __DIR__ . '/../../db/db.php';
// load partials helper for render_partial()
include_once __DIR__ . '/partials/_init.php';

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="../assets/css/tailwind.output.css">
    <!-- Font Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- DataTables CSS (CDN) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <!-- Icon -->
    <link rel="icon" href="https://placehold.co/32x32/0033A0/FFFFFF?text=D" type="image/png">
    <!-- Font Awesome for button icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        <?php render_partial('sidebar'); ?>
        <!-- Konten Utama -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <?php render_partial('header'); ?>
            <!-- Area Konten Utama -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6 md:p-8">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-800">Data Peserta</h2>
                        <p class="text-sm text-gray-500 mt-1">Detail data peserta petrokimia</p>
                    </div>
                    <div class="p-6">
                        <!-- Loading State -->
                        <div id="loading-state" class="text-center py-12">
                            <svg class="animate-spin h-10 w-10 text-blue-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            <p class="text-gray-600 font-medium">Memuat data peserta...</p>
                        </div>

                        <!-- Error State -->
                        <div id="error-state" class="hidden">
                            <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg">
                                <p class="font-semibold mb-2"><i class="fa-solid fa-exclamation-circle mr-2"></i>Terjadi Kesalahan</p>
                                <p class="text-sm" id="error-message">Gagal memuat data peserta</p>
                            </div>
                        </div>

                        <!-- Data Container -->
                        <div id="data-container" class="hidden">
                            <!-- Data Pribadi -->
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-blue-500">Data Pribadi</h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <p class="text-xs font-semibold text-gray-500 uppercase">Nama</p>
                                        <p class="text-lg font-semibold text-gray-900" id="data-nama">-</p>
                                    </div>
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <p class="text-xs font-semibold text-gray-500 uppercase">Nomor Polis</p>
                                        <p class="text-lg font-semibold text-gray-900" id="data-polis">-</p>
                                    </div>
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <p class="text-xs font-semibold text-gray-500 uppercase">Notas</p>
                                        <p class="text-lg font-semibold text-gray-900" id="data-notas">-</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Detail Premi Table -->
                            <div class="mt-8">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b-2 border-blue-500">Detail Premi</h3>
                                <div class="overflow-x-auto">
                                    <table id="detail-premi-table" class="w-full display stripe hover bg-white" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th class="text-center">Bulan</th>
                                                <th class="text-center">Akumulasi Premi</th>
                                                <th class="text-center">Premi</th>
                                                <th class="text-center">Saldo Awal</th>
                                                <th class="text-center">Pengembangan</th>
                                                <th class="text-center">Saldo Akhir</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Download Button -->
                            <div class="mt-6 flex justify-end">
                                <button id="btn-download" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-6 rounded-lg transition duration-200 flex items-center gap-2">
                                    <i class="fa-solid fa-download"></i>
                                    Download Excel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php render_partial('footer'); ?>

    <!-- jQuery + DataTables JS (CDN) -->
    <script src="../../assets/js/jquery-3.6.0.min.js"></script>
    <script src="../../assets/js/jquery.dataTables.min.js"></script>
    <!-- SweetAlert2 for toasts -->
    <script src="../../assets/js/sweetalert2@11.js"></script>
    <!-- XLSX for Excel export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <!-- Load Data Script -->
    <script src="./assets/js/load-data.js"></script>
</body>
</html>