// Load Data Peserta untuk Admin Petrokimia
$(document).ready(function () {
  var detailTable = null;
  var currentData = null;
  var currentHistories = [];

  // Format currency
  function formatCurrency(value) {
    if (!value || isNaN(value)) return "Rp 0";
    var num = parseFloat(value);
    return "Rp " + num.toLocaleString("id-ID", { maximumFractionDigits: 0 });
  }

  // Load data on page load
  loadData();

  function loadData() {
    const apiUrl = "api/get_statement.php";

    $.ajax({
      url: apiUrl,
      type: "GET",
      dataType: "json",
      success: function (response) {
        console.log("API Response:", response);
        
        // Hide loading state
        $("#loading-state").addClass("hidden");

        if (response["acs/account/statement"]) {
          const data = response["acs/account/statement"];
          currentData = data;

          // Populate personal data
          $("#data-nama").text(data.name || "-");
          $("#data-polis").text(data.noPolis || "-");
          $("#data-notas").text(data.id || "-");

          // Prepare histories for table
          currentHistories = data.histories || [];

          // Show data container
          $("#data-container").removeClass("hidden");

          // Initialize DataTable
          initDataTable(currentHistories, data);
        } else {
          showError("Format respons API tidak sesuai");
        }
      },
      error: function (xhr, status, error) {
        console.error("API Error:", error, xhr);
        $("#loading-state").addClass("hidden");
        showError("Gagal memuat data dari API: " + (xhr.responseJSON?.message || status));
      },
    });
  }

  function showError(message) {
    $("#error-state").removeClass("hidden");
    $("#error-message").text(message);
  }

  function initDataTable(histories, personData) {
    if (detailTable) {
      detailTable.destroy();
    }

    // Transform data for DataTable
    const tableData = histories.map((h) => ({
      bulan: h.currentMonth || "-",
      akumulasiPremi: h.akumulasiPremi || "-",
      premi: formatCurrency(personData.premi),
      saldoAwal: h.saldoAwal || "-",
      pengembangan: h.pengembangan || "-",
      saldoAkhir: h.saldoAkhir || "-",
    }));

    detailTable = $("#detail-premi-table").DataTable({
      data: tableData,
      pageLength: 12,
      searching: false,
      ordering: false,
      lengthChange: false,
      info: false,
      autoWidth: false,
      columnDefs: [
        { targets: 0, width: "100px", className: "text-center" },
        { targets: "_all", width: "120px", className: "text-right" },
      ],
      columns: [
        { data: "bulan", className: "text-center" },
        { data: "akumulasiPremi", className: "text-right" },
        { data: "premi", className: "text-right" },
        { data: "saldoAwal", className: "text-right" },
        { data: "pengembangan", className: "text-right" },
        { data: "saldoAkhir", className: "text-right" },
      ],
    });
  }

  // Download Excel button
  $("#btn-download").on("click", function () {
    if (!currentData || !currentHistories) {
      alert("Data tidak tersedia");
      return;
    }

    downloadExcel(
      currentHistories,
      currentData.name,
      currentData.noPolis,
      currentData.id,
      formatCurrency(currentData.premi)
    );
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
