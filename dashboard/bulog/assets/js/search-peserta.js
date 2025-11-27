// Search Peserta untuk Bulog Admin
$(document).ready(function () {
  var pesertaTable = null;
  var currentSearchKeyword = "";

  // Function untuk format jenis premi
  function formatJenisPremi(jenisValue) {
    var jenisMap = {
      1: "JHT Regular",
      2: "JHT Topup",
      3: "PKP Regular",
    };
    return jenisMap[String(jenisValue)] || String(jenisValue);
  }

  // Function untuk format currency
  function formatCurrency(value) {
    if (!value || isNaN(value)) return "Rp 0";
    var num = parseFloat(value);
    return "Rp " + num.toLocaleString("id-ID", { maximumFractionDigits: 0 });
  }

  // Function untuk format tanggal
  function formatTanggal(date) {
    if (!date) return "-";
    var d = new Date(date);
    if (isNaN(d)) return date;
    return d.toLocaleDateString("id-ID", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
    });
  }

  // Function untuk format status
  function formatStatus(statusData) {
    var status = parseInt(statusData) || 0;
    switch (status) {
      case 1:
        return '<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Approved</span>';
      case 0:
        return '<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>';
      default:
        return '<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Unknown</span>';
    }
  }

  // Initialize DataTable
  function initDataTable(data) {
    if (pesertaTable) {
      pesertaTable.destroy();
    }

    pesertaTable = $("#peserta-table").DataTable({
      data: data,
      pageLength: 20,
      searching: false,
      ordering: false,
      lengthChange: false,
      info: false,
      autoWidth: false,
      columnDefs: [
        { targets: 0, width: "40px" }, // No
        { targets: 1, width: "100px" }, // NIK
        { targets: 2, width: "150px" }, // Nama
        { targets: 3, width: "100px" }, // Periode
        { targets: 4, width: "110px" }, // Jenis Premi
        { targets: 5, width: "130px" }, // Total Premi
        { targets: 6, width: "110px" }, // Tanggal Upload
      ],
      columns: [
        {
          // 1. Nomor Urut
          data: null,
          className: "text-center",
          render: function (data, type, row, meta) {
            return meta.row + 1;
          },
        },
        {
          // 2. Nama Peserta (menggunakan "fullname")
          data: "fullname",
          className: "text-center", // Ubah ke left agar nama panjang mudah dibaca
          render: function (data) {
            return data || "-";
          },
        },
        {
          // 3. Nomor Taspen (menggunakan "notas")
          data: "notas",
          className: "text-center",
          render: function (data) {
            return data || "-";
          },
        },
        {
          // 4. Nomor Kartu/NIK (menggunakan "CARDNUMBER")
          data: "CARDNUMBER",
          className: "text-center",
          render: function (data) {
            return data || "-";
          },
        },
        {
          // 5. ID Anggota (menggunakan "IDMEMBER")
          data: "IDMEMBER",
          className: "text-center",
          render: function (data) {
            // Logika pemformatan lama dipertahankan, meskipun IDMEMBER baru terlihat berbeda (tidak 6 digit)
            if (data && data.length === 6) {
              return data.substring(0, 4) + "-" + data.substring(4, 6);
            }
            return data || "-";
          },
        },
        {
          // 6. Status Peserta (Menggunakan "status" dari API)
          // Kolom ini ditambahkan kembali karena data "status: AKTIF" tersedia di respons API.
          data: "status",
          className: "text-center",
          render: function (data) {
            // Anda mungkin memiliki fungsi formatStatus untuk status ini
            // Jika tidak, tampilkan data mentah
            return data ? data : "-";
            // return formatStatus(data); // Gunakan ini jika formatStatus() mendukung nilai 'AKTIF'
          },
        },
        // Kolom 'total_premi', 'created_at', dan 'status_data' dihapus
        // karena tidak ada di respons API baru yang Anda berikan.
        {
          // 7. Kolom Aksi (Tombol Detail)
          data: null,
          className: "text-center",
          orderable: false,
          render: function (data, type, row) {
            // Perhatikan: Karena API baru tidak memiliki 'id',
            // tombol detail harus menggunakan 'notas' atau 'IDMEMBER' untuk mengidentifikasi baris.
            // Saya asumsikan 'notas' adalah identitas utama.
            return (
              '<button class="btn-view-detail bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-1.5 rounded-full transition" data-notas="' +
              row.notas + // Menggunakan notas sebagai identitas
              '" title="Lihat detail"><i class="fa-solid fa-eye" style="font-size:13px;margin-right:6px;"></i><span>Detail</span></button>'
            );
          },
        },
      ],
    });
  }

  // Search function
  function performSearch() {
    var searchName = $("#search-nama").val().trim();

    if (!searchName) {
        swal.fire({
          toast: true,
            position: 'top',
            icon: 'warning',
            title: 'Silakan masukkan nama atau NIK peserta',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
      return;
    }

    // Show loading
    $("#table-loading-overlay").show();
    // $("#global-loading").show();

    // Perform search via API
    $.ajax({
      url: "api/search_peserta.php",
      type: "GET",
      data: { name: searchName },
      dataType: "json",
      success: function (response) {
        $("#table-loading-overlay").hide();
        console.log(response);

        if (!response.ok || response.data.length === 0) {
          // No results
          $("#initial-state").hide();
          $("#table-container").hide();
          $("#search-info-container").hide();

          $("#empty-state").show();
          $("#empty-message").text(
            response.msg ||
              "Tidak ada data peserta dengan pencarian: " + searchName
          );

          return;
        }

        // Show results
        currentSearchKeyword = searchName;

        $("#initial-state").hide();
        // $("#global-loading").hide();
        $("#empty-state").hide();

        $("#search-info-container").show();
        $("#search-keyword").text(searchName);
        $("#search-count").text(response.count);

        $("#table-container").show();

        // Initialize or refresh table
        initDataTable(response.data);
        // initDataTable(response.data["acs/report/individu"]);
        console.log(response.data);
      },
      error: function (xhr, status, error) {
        $("#table-loading-overlay").hide();

        let errorMessage = "Gagal melakukan pencarian.";

        // Cek jika respons diuraikan sebagai JSON
        if (xhr.responseJSON && xhr.responseJSON.msg) {
          errorMessage = xhr.responseJSON.msg;
        }
        // Cek jika respons adalah teks error PHP (HTML/non-JSON)
        else if (xhr.responseText) {
          // Tampilkan pesan yang lebih umum atau status error HTTP
          errorMessage =
            "Terjadi kesalahan server. Status HTTP: " +
            xhr.status +
            ". Detail error ada di konsol.";
          console.error(
            "Kesalahan Respon Mentah (Bukan JSON):",
            xhr.responseText
          );
        } else {
          // Kesalahan koneksi, timeout, dll.
          errorMessage =
            "Kesalahan jaringan atau server tidak merespons. Status: " + status;
        }

        alert("Error: " + errorMessage);
        console.error("AJAX Failure:", error, xhr);
      },
    });
  }

  // Bind search button
  $("#btn-search").on("click", function () {
    performSearch();
    
  });

  // Search on Enter key
  $("#search-nama").on("keypress", function (e) {
    if (e.which === 13) {
      // Enter key
      performSearch();
    }
  });

  // Clear button
  $("#btn-clear").on("click", function () {
    $("#search-nama").val("");
    $("#initial-state").show();
    $("#table-container").hide();
    $("#search-info-container").hide();
    $("#empty-state").hide();

    if (pesertaTable) {
      pesertaTable.destroy();
      pesertaTable = null;
    }

    $("#search-nama").focus();
  });

  // Export button
  $("#btn-export").click(function () {
    window.location.href = "api/download_excel.php";
  });


  // Delegated click handler for detail button
  $(document).on("click", ".btn-view-detail", function () {
    var pesertaId = $(this).data("notas");
    var polis = pesertaId; // misal data-id berisi nomor polis

    $.ajax({
      url: "api/get_account_statement.php",
      method: "GET",
      data: { polis: polis },
      dataType: "json",
      success: function (response) {
        let data = response["acs/account/statement"];
        console.log(data);

        // --- Data pribadi ---
        let nama = data.name ?? "-";
        let nomorPolis = data.noPolis ?? "-";
        let notas = data.id ?? "-";
        let premi =
          "Rp " + data.premi.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".") ??
          "Rp 0";

        // --- Histories ---
        let histories = data.histories || [];
        let rows = "";

        histories.forEach((history) => {
          rows += `
                        <tr>
                            <td>${history.currentMonth}</td>
                            <td>${history.akumulasiPremi}</td>
                            <td>${premi}</td>
                            <td>${history.saldoAwal}</td>
                            <td>${history.pengembangan}</td>
                            <td>${history.saldoAkhir}</td>
                        </tr>
                    `;
        });

        // HTML Modal
        var detailHTML = `
                    <div style="max-width: 100%; text-align: left; font-size: 13px;">
                        <div style="margin-bottom: 15px; max-width: 35%; overflow-x: auto;">
                            <h3 style="margin: 0 0 10px 0; font-size: 14px; font-weight: bold; border-bottom: 2px solid #e5e7eb; padding-bottom: 6px;">Data Pribadi</h3>
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="padding: 6px 0; width: 35%; font-weight: 600; color: #374151;">Nama</td>
                                    <td style="padding: 6px 0; color: #6b7280;">${nama}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 6px 0; width: 35%; font-weight: 600; color: #374151;">Nomor Polis</td>
                                    <td style="padding: 6px 0; color: #6b7280;">${nomorPolis}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 6px 0; width: 35%; font-weight: 600; color: #374151;">Notas</td>
                                    <td style="padding: 6px 0; color: #6b7280;">${notas}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <div style="margin-top: 15px; overflow-x: auto;">
                            <h3 style="margin: 0 0 10px 0; font-size: 14px; font-weight: bold; border-bottom: 2px solid #e5e7eb; padding-bottom: 6px;">Detail Premi</h3>
                            <table id="detail-premi-table" class="display" style="width: 100%; font-size: 12px;">
                                <thead>
                                    <tr>
                                        <th style="text-align: center;">Bulan</th>
                                        <th style="text-align: center;">Akumulasi Premi</th>
                                        <th style="text-align: center;">Premi</th>
                                        <th style="text-align: center;">Saldo Awal</th>
                                        <th style="text-align: center;">Pengembangan</th>
                                        <th style="text-align: center;">Saldo Akhir</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${rows}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;

        // --- SweetAlert Modal ---
        Swal.fire({
          html: detailHTML,
          width: "90%",
          padding: "20px 10px",
          showCancelButton: true,
          cancelButtonText: "Tutup",
          confirmButtonText: "Download Excel",
          confirmButtonColor: "#22c55e",
          cancelButtonColor: "#3b82f6",
          didOpen: function () {
            const modalContent = document.querySelector(
              ".swal2-html-container"
            );
            if (modalContent) {
              modalContent.style.maxHeight = "100%";
              modalContent.style.overflowY = "auto";
            }

            if ($.fn.DataTable.isDataTable("#detail-premi-table")) {
              $("#detail-premi-table").DataTable().destroy();
            }

            $("#detail-premi-table").DataTable({
              pageLength: 9,
              searching: false,
              ordering: false,
              lengthChange: false,
              info: false,
              paging: false,
              autoWidth: false,
              columnDefs: [
                { targets: 0, width: "90px" },
                { targets: "_all", width: "90px", className: "text-center" },
              ],
            });
          },
        }).then((result) => {
          if (result.isConfirmed) {
            downloadExcel(histories, nama, nomorPolis, notas, premi);
          }
        });
      },
    });
  });

  function downloadExcel(histories, nama, polis, notas, premi) {
    // ---- HEADER DATA PERSONAL ----
    let headerData = [
      ["Data Pribadi"],
      ["Nama", nama],
      ["Nomor Polis", polis],
      ["Notas", notas],
      [],
      ["Detail Premi"],
    ];

    // ---- HEADER TABEL ----
    const tableHeader = [
      "Bulan",
      "Akumulasi Premi",
      "Premi",
      "Saldo Awal",
      "Pengembangan",
      "Saldo Akhir",
    ];

    // ---- DATA BODY ----
    let tableRows = histories.map((h) => [
      h.currentMonth,
      h.akumulasiPremi,
      premi,
      h.saldoAwal,
      h.pengembangan,
      h.saldoAkhir,
    ]);

    // Gabungkan ke worksheet
    let wsData = [...headerData, tableHeader, ...tableRows];

    let worksheet = XLSX.utils.aoa_to_sheet(wsData);

    // ---- STYLING ----
    const range = XLSX.utils.decode_range(worksheet["!ref"]);

    for (let R = 0; R <= range.e.r; R++) {
      for (let C = 0; C <= range.e.c; C++) {
        let cellAddress = XLSX.utils.encode_cell({ r: R, c: C });
        let cell = worksheet[cellAddress];
        if (!cell) continue;

        // Border untuk semua data tabel (mulai row header tabel)
        if (R >= 5) {
          cell.s = {
            border: {
              top: { style: "thin" },
              bottom: { style: "thin" },
              left: { style: "thin" },
              right: { style: "thin" },
            },
          };
        }

        // Header Table Styling (Row 5 untuk tabel)
        if (R === 5) {
          cell.s = {
            font: { bold: true, color: { rgb: "#FFFFFF" } },
            alignment: { horizontal: "center" },
            fill: { fgColor: { rgb: "#4472C4" } },
            border: {
              top: { style: "thin" },
              bottom: { style: "thin" },
              left: { style: "thin" },
              right: { style: "thin" },
            },
          };
        }
      }
    }

    // Atur column width biar rapih
    worksheet["!cols"] = [
      { wpx: 110 },
      { wpx: 130 },
      { wpx: 100 },
      { wpx: 120 },
      { wpx: 120 },
      { wpx: 120 },
    ];

    // Export ke file
    let workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Detail Premi");

    XLSX.writeFile(workbook, `Statement_${polis}_${nama}.xlsx`);
  }
});
