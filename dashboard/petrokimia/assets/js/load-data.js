// Load Data Peserta untuk Admin Petrokimia
$(document).ready(function () {
  var detailTable = null;
  var currentData = null;

  // Format currency
  function formatCurrency(value) {
    if (!value || isNaN(value)) return "Rp 0";
    var num = parseFloat(value);
    return "Rp " + num.toLocaleString("id-ID", { maximumFractionDigits: 0 });
  }

  // Format negative numbers with parentheses
  function formatNegative(value) {
  if (!value || isNaN(value)) return "-";
  const num = parseFloat(value);
  return "(" + num.toLocaleString("id-ID", { maximumFractionDigits: 0 }) + ")";
  }

  // Format date
  function formatDate(dateString) {
    const options = { year: "numeric", month: "short", day: "numeric" };
    const date = new Date(dateString);
    return date.toLocaleDateString("id-ID", options);
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

        if (response["acs/report/individu"]) {
          const data = response["acs/report/individu"];
          currentData = data;
          nama = "Petrokimia";
          polis = "3141";

          // Populate personal data
          $("#data-nama").text(nama);
          $("#data-polis").text(polis);

          // Show data container
          $("#data-container").removeClass("hidden");

          // Initialize DataTable
          initDataTable(currentData);
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

  function initDataTable(currentData) {
    if (detailTable) {
      detailTable.destroy();
    }

    // Transform data for DataTable
    const tableData = currentData.map((h) => ({
      BALANCEDATE: formatDate(h.BALANCEDATE) || "-",
      STARTBALANCE: h.STARTBALANCE || "-",
      TOTALPREMIUM: h.TOTALPREMIUM || "-",
      TOPUPAMOUNT: h.TOPUPAMOUNT || "-",
      biaya: h.biaya ? formatNegative(h.biaya) : "-",
      WITHDRAW: h.WITHDRAW  || "-",
      INVESTMENT: h.INVESTMENT || "-",
      ENDBALANCE: h.ENDBALANCE || "-",
      NUMBEROFMEMBER: h.NUMBEROFMEMBER || "-",
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
        { data: "BALANCEDATE", className: "text-center" },
        { data: "STARTBALANCE", className: "text-center" },
        { data: "TOTALPREMIUM", className: "text-center" },
        { data: "TOPUPAMOUNT", className: "text-center" },
        { data: "biaya", className: "text-center" },
        { data: "WITHDRAW", className: "text-center" },
        { data: "INVESTMENT", className: "text-center" },
        { data: "ENDBALANCE", className: "text-center" },
        { data: "NUMBEROFMEMBER", className: "text-center" },
      ],
    });
  }

  // Download Excel button
  $("#btn-download").on("click", function () {
    if (!currentData) {
      alert("Data tidak tersedia");
      return;
    }

    downloadExcel(
      currentData,
      nama,
      polis,
      formatCurrency(currentData.TOTALPREMIUM)
    );
  });

  function downloadExcel(currentData, nama, polis) {
    // ---- HEADER DATA ----
    const headerData = [
      ["STATEMENT DETAIL PREMI"],
      [],
      ["Nama Instansi", nama],
      ["No. Polis", polis],
      [],
      ["DETAIL PREMI"],
      [],
    ];
    // ---- HEADER TABEL ----
    const tableHeader = [
      "BULAN",
      "SALDO AWAL",
      "PREMI",
      "TOP UP",
      "BIAYA",
      "WITHDRAW",
      "PENGEMBANGAN",
      "SALDO AKHIR",
      "JUMLAH PESERTA",
    ];

    // ---- DATA BODY ----
    let tableRows = currentData.map((h) => [
      formatDate(h.BALANCEDATE),
      h.STARTBALANCE,
      h.TOTALPREMIUM,
      h.TOPUPAMOUNT,
      h.biaya ? formatNegative(h.biaya) : "-",
      h.WITHDRAW,
      h.INVESTMENT,
      h.ENDBALANCE,
      h.NUMBEROFMEMBER,
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
      { wpx: 130 },
      { wpx: 130 },
      { wpx: 130 },
    ];

    // Export ke file
    let workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Detail Premi");

    XLSX.writeFile(workbook, `Statement_${nama}_${polis}.xlsx`);
  }
});
