<?php
// Require authentication - adminbl bisa akses
include_once __DIR__ . '/../../auth.php';
require_login();
require_adminbl();

include __DIR__ . '/../../db/db.php';
// load partials helper for render_partial()
include_once __DIR__ . '/partials/_init.php';

?>
<!DOCTYPE html>
<html lang="id">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Data Peserta</title>
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
						<p class="text-sm text-gray-500 mt-1">Cari data peserta berdasarkan Nama atau Notas</p>
					</div>
					<div class="p-6">
						<!-- Search Bar -->
						<div class="mb-6">
							<div class="flex gap-3">
								<div class="flex-1">
									<label
										for="search-nama"
										class="block text-sm font-medium text-gray-700 mb-2">
										Cari Peserta (Nama/Notas)
									</label>
									<div class="flex gap-3">
										<input
											type="text"
											id="search-nama"
											placeholder="Masukkan Nama atau Notas peserta..."
											class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition" />
										<button id="btn-search" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition duration-200 flex items-center gap-2">
											<i class="fa-solid fa-search"></i>
											Cari
										</button>
										<button
											id="btn-clear"
											class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-6 rounded-lg transition duration-200 flex items-center gap-2">
											<i class="fa-solid fa-times"></i>
										</button>
										<button
											id="btn-export"
											class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-6 rounded-lg transition duration-200 flex items-center gap-2"
											aria-label="Export Data">
											<i id="export-icon" class="fa-solid fa-file-export"></i>
											<span id="export-text">Export Semua Peserta</span>
										</button>
										<!-- <button
                                            id="btn-export"
                                            class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-6 rounded-lg transition duration-200 flex items-center gap-2">
                                            <i class="fa-solid fa-file-export"></i>
                                            Export Semua Peserta
                                        </button> -->
									</div>
								</div>
							</div>
						</div>

						<!-- Info Container -->
						<div id="search-info-container" class="mb-4 hidden">
							<div class="bg-blue-50 border border-blue-200 text-blue-800 p-4 rounded-lg">
								<p class="text-sm"><strong>Hasil Pencarian:</strong> <span id="search-keyword"></span></p>
								<p class="text-sm mt-1"><strong>Total Data:</strong> <span id="search-count">0</span> peserta</p>
							</div>
						</div>

						<!-- Empty State -->
						<div id="empty-state" class="text-center py-12 hidden">
							<i class="fa-solid fa-users-slash fa-3x text-gray-400 mb-4"></i>
							<h3 class="text-lg font-medium text-gray-900 mb-1">Tidak ada hasil</h3>
							<p class="text-sm text-gray-500" id="empty-message">Coba cari dengan kata kunci lain</p>
						</div>

						<!-- Table Container -->
						<div id="table-container" class="overflow-x-auto hidden" style="position:relative;">
							<table id="peserta-table" class="w-full display stripe hover bg-white" style="width:100%">
								<thead>
									<tr>
										<th class="dt-center align-middle">No</th>
										<th class="dt-center align-middle">Nama</th>
										<th class="dt-center align-middle">No. Taspen</th>
										<th class="dt-center align-middle">No. Kartu / NIK</th>
										<th class="dt-center align-middle">Status</th>
										<th class="dt-center align-middle">Aksi</th>
									</tr>
								</thead>
								<tbody></tbody>
							</table>

							<!-- GLOBAL LOADING
                            <div id="global-loading" style="
                                display:none;
                                position: fixed;
                                top:0;left:0;right:0;bottom:0;
                                background: rgba(255,255,255,0.7);
                                z-index: 9999;
                                display:flex;
                                align-items:center;
                                justify-content:center;
                            ">
                                <div class="flex flex-col items-center justify-center h-full">
                                    <i class="fa-solid fa-arrows-rotate fa-spin text-4xl text-blue-600 mb-4"></i>
                                    <span class="text-blue-700 font-semibold">Sedang mencari data</span>
                                </div>
                            </div> -->

							<!-- Overlay loading untuk area tabel saja -->
							<div id="table-loading-overlay" style="display:none;position:absolute;top:0;left:0;right:0;bottom:0;width:100%;height:100%;background:rgba(255,255,255,0.7);z-index:20;align-items:center;justify-content:center;">
								<div class="flex flex-col items-center justify-center h-full">
									<i class="fa-solid fa-arrows-rotate fa-spin text-4xl text-blue-600 mx-auto mb-4"></i>
									<span class="text-blue-700 font-semibold">Memuat data</span>
								</div>
							</div>
						</div>

						<!-- Initial State -->
						<div id="initial-state" class="text-center py-12">
							<i class="fa-solid fa-users fa-3x text-gray-400 mb-4"></i>
							<p class="text-sm text-gray-500">Ketik Nama atau Notas peserta untuk mencari data</p>
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
	<!-- Search Peserta Script -->
	<script src="./assets/js/search-peserta.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</body>

</html>