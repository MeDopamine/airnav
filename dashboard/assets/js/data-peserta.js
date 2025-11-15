// Tabel Periode + Jenis Premi (combined aggregates)
// Tabel Periode + Jenis Premi (combined aggregates)
function formatJenisPremi(jenisValue) {
  var jenisMap = {
    1: "JHT Regular",
    2: "PKP Regular",
    3: "JHT Topup",
  };
  return jenisMap[String(jenisValue)] || String(jenisValue);
}
var periodeTable = null;
function loadPeriodeTable() {
  $.get("api/get_periode_jenis.php", function (resp) {
    if (resp && resp.ok && Array.isArray(resp.data)) {
      if (periodeTable) {
        periodeTable.clear().draw();
      } else {
        periodeTable = $("#periode-table").DataTable({
          pageLength: 10,
          searching: false,
          ordering: false,
          lengthChange: false,
          info: false,
          autoWidth: false,
          // 1. DIPERBARUI: Lebar kolom disesuaikan untuk 10 kolom
          columnDefs: [
            { targets: 0, width: "40px", className: "text-center" }, // No
            { targets: 1, width: "110px", className: "text-center" }, // Periode
            { targets: 2, width: "100px", className: "text-center" }, // Jenis Premi
            { targets: 3, width: "100px", className: "text-center" }, // Jumlah Peserta
            { targets: 4, width: "130px", className: "text-center" }, // Total Premi Karyawan
            { targets: 5, width: "130px", className: "text-center" }, // Total Premi PT
            { targets: 6, width: "130px", className: "text-center" }, // Total Premi
            { targets: 7, width: "110px", className: "text-center" }, // Tanggal Upload
            { targets: 8, width: "110px", className: "text-center" }, // Status (BARU)
            { targets: 9, width: "110px", className: "text-center" }, // Aksi
          ],
          // 2. DIPERBARUI: Definisi kolom untuk 10 kolom
          columns: [
            {
              data: null,
              className: "text-center",
              render: function (data, type, row, meta) {
                return meta.row + 1;
              },
            },
            {
              data: "periode",
              className: "text-center",
              render: function (data, type, row) {
                try {
                  return formatPeriodeReadable(data);
                } catch (e) {
                  return String(data);
                }
              },
            },
            {
              data: "jenis",
              className: "text-center",
              render: function (data, type, row) {
                switch (data) {
                  case "1":
                    return "JHT Regular";
                  case "2":
                    return "JHT Topup";
                  case "3":
                    return "PKP Regular";
                  default:
                    return String(data || "");
                }
              },
            },
            {
              data: "jumlah_peserta",
              className: "text-center",
              render: function (data, type, row) {
                return String(data || "0");
              },
            },
            {
              data: "sum_krywn",
              className: "text-center",
              render: function (data, type, row) {
                try {
                  return formatPremi(data);
                } catch (e) {
                  return String(data);
                }
              },
            },
            {
              data: "sum_pt",
              className: "text-center",
              render: function (data, type, row) {
                try {
                  return formatPremi(data);
                } catch (e) {
                  return String(data);
                }
              },
            },
            {
              data: "sum_total",
              className: "text-center",
              render: function (data, type, row) {
                try {
                  return formatPremi(data);
                } catch (e) {
                  return String(data);
                }
              },
            },
            {
              data: "created_at",
              className: "text-center",
              render: function (data, type, row) {
                if (!data) return "-";
                try {
                  var d = new Date(data);
                  return d.toLocaleDateString("id-ID", {
                    year: "numeric",
                    month: "2-digit",
                    day: "2-digit",
                  });
                } catch (e) {
                  return String(data);
                }
              },
            },
            // 3. KOLOM STATUS BARU (menampilkan badge)
            {
              data: "status_data",
              className: "text-center",
              render: function (data, type, row) {
                var status_data = "";
                var btnClass = "";
                switch (data) {
                  case 0:
                    status_data = "Not Approved";
                    btnClass =
                      "px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-yellow-100 text-yellow-800";
                    break;
                  case 1:
                    status_data = "Approved";
                    btnClass =
                      "px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-green-100 text-green-800";
                    break;
                  case 2:
                    status_data = "Rejected";
                    btnClass =
                      "px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-red-100 text-red-800";
                    break;
                  case 3:
                    status_data = "Done";
                    btnClass =
                      "px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-blue-100 text-blue-800";
                    break;
                  case 4:
                    status_data = "Revision";
                    btnClass =
                      "px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-purple-100 text-purple-800";
                    break;
                }
                return (
                  '<span class="' +
                  btnClass +
                  '" style="min-width:110px;">' +
                  status_data +
                  "</span>"
                );
              },
            },
            // 4. TOMBOL AKSI DIPERBARUI (menyertakan data-status)
            {
              data: null,
              className: "text-center",
              render: function (data, type, row) {
                $btnAddInvoice = `<div style='display:flex;justify-content:center;align-items:center;height:100%;'><button class="btn-add-invoice bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-4 py-1.5 rounded-full flex items-center justify-center transition" style="min-width:100px;gap:6px;" data-idbatch="${row.idbatch}" data-periode="${row.periode}" data-jenis="${row.jenis}"><span style='margin-left:4px;'>Add Invoice</span></button></div>`;
                $btnLihat = `<div style='display:flex;justify-content:center;align-items:center;height:100%;'><button class="btn-lihat-peserta bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-1.5 rounded-full flex items-center justify-center transition" style="min-width:100px;gap:6px;" data-idbatch="${row.idbatch}" data-periode="${row.periode}" data-jenis="${row.jenis}" data-status="${row.status_data}"><i class='fa-solid fa-eye' style='font-size:15px;vertical-align:middle;'></i><span style='margin-left:4px;'>Lihat</span></button></div>`;
                if (
                  typeof userRole !== "undefined" &&
                  userRole === "admintl" &&
                  row.status_data == 1
                ) {
                  return `<div style="display:flex;justify-content:center;align-items:center;gap:8px;">${$btnLihat} ${$btnAddInvoice}</div>`;
                } else {
                  return `<div style="display:flex;justify-content:center;align-items:center;gap:8px;">${$btnLihat}</div>`;
                }
              },
            },
          ],
        });
      }
      // 5. DIPERBARUI: 'rows' sekarang menyertakan status_data
      var rows = resp.data.map(function (r) {
        return {
          periode: r.periode,
          jenis: r.jenis,
          status_data: r.status_data, // Tambahkan status_data
          jumlah_peserta: r.jumlah_peserta,
          sum_krywn: r.sum_krywn,
          sum_pt: r.sum_pt,
          sum_total: r.sum_total,
          created_at: r.created_at,
          idbatch: r.idbatch,
        };
      });
      periodeTable.clear().rows.add(rows).draw();
    }
  });
}

// Helper functions to remove duplicated code
// NOTE: UI helpers (formatPremi, makeApproveBadge, rebuildPeriodeSelectFromSet,
// resetApproveAllButton, showErrorNotification, formatPeriodeDisplay, formatPeriodeReadable)
// are provided by `assets/js/helpers/peserta-helpers.js` and exposed on window.

function buildMainTableFromData(dataArray) {
  // Avoid auto-initializing DataTable here. If the DataTable has not been
  // initialized yet (it is initialized in $(document).ready below),
  // populate the <tbody> directly and return. This prevents a race
  // where calling DataTable() early creates a second instance with
  // different options, causing inconsistent rendering.
  var table;
  var usingApi = false;
  if ($.fn.DataTable && $.fn.DataTable.isDataTable("#data-peserta-table")) {
    table = $("#data-peserta-table").DataTable();
    table.clear();
    usingApi = true;
  } else {
    // fallback: build tbody HTML so the later DataTable init will pick it up
    var $tbody = $("#data-peserta-table tbody");
    $tbody.empty();
  }
  // ensure periode set
  if (!window._periodeSet) window._periodeSet = {};
  dataArray.forEach(function (row) {
    // Expected fields from API: id, periode, jenis_premi, jml_premi_krywn, jml_premi_pt, total_premi, status_data
    var displayPeriode = formatPeriodeDisplay(row.periode);
    var jenisPremi =
      typeof row.jenis_premi !== "undefined" ? String(row.jenis_premi) : "";
    var jmlKry =
      typeof row.jml_premi_krywn !== "undefined"
        ? formatPremi(row.jml_premi_krywn)
        : "";
    var jmlPt =
      typeof row.jml_premi_pt !== "undefined"
        ? formatPremi(row.jml_premi_pt)
        : "";
    var totalPremiFormatted = formatPremi(row.total_premi);
    var status = typeof row.status !== "undefined" ? row.status : "";
    var approved = row.status_data == 1 ? 1 : 0;
    var approveBtn = makeApproveBadge(approved, row.id);
    var actionBtns =
      '<button class="btn-edit-data text-blue-600 hover:text-blue-800" style="margin-right:4px;" title="Edit" data-id="' +
      row.id +
      '"><i class="fa-solid fa-pen-to-square"></i></button>' +
      '<button class="btn-delete-data transition" style="margin-left:4px;" title="Hapus" data-id="' +
      row.id +
      '"><i class="fa-solid fa-trash" style="color:#dc2626;"></i></button>';
    var rowHtml =
      "<tr>" +
      '<td class="text-center"></td>' +
      '<td class="text-center">' +
      displayPeriode +
      "</td>" +
      '<td class="text-center">' +
      jenisPremi +
      "</td>" +
      '<td class="text-center">' +
      jmlKry +
      "</td>" +
      '<td class="text-center">' +
      jmlPt +
      "</td>" +
      '<td class="text-center">' +
      totalPremiFormatted +
      "</td>" +
      '<td class="text-center">' +
      status +
      "</td>" +
      '<td class="text-center">' +
      approveBtn +
      "</td>" +
      '<td class="text-center">' +
      actionBtns +
      "</td>" +
      "</tr>";
    if (usingApi) {
      table.row.add([
        "",
        displayPeriode,
        jenisPremi,
        jmlKry,
        jmlPt,
        totalPremiFormatted,
        status,
        approveBtn,
        actionBtns,
      ]);
    } else {
      $tbody.append(rowHtml);
    }
    if (row.periode) window._periodeSet[row.periode] = true;
  });
  // default order by periode (column 1 desc)
  table.order([1, "desc"]);
  table.rows().invalidate().draw(false);
  rebuildPeriodeSelectFromSet();
}

loadPeriodeTable();

// Handler klik tombol Lihat Data pada tabel periode
// HAPUS BLOK LAMA ANDA DAN GANTI DENGAN INI
$(document).on("click", ".btn-lihat-peserta", function () {
  var periode = $(this).data("periode");
  var jenis = $(this).data("jenis");
  var status = $(this).data("status");
  var idbatch = $(this).data("idbatch");
  if (!periode) return;

  showTableLoading();

  var params = { periode: periode };
  if (jenis && jenis !== "" && jenis !== null && jenis !== "undefined") {
    params.jenis = jenis;
  }

  $.get("api/get_peserta_by_periode.php", params, function (resp) {
    hideTableLoading();

    // (DEBUGGING, bisa Anda hapus nanti)
    console.log("RESPONS API:", resp);
    console.log("STATUS YANG DIKLIK:", status);
    console.log("KEY YANG DIAKSES:", String(status));
    console.log("DATA DITEMUKAN:", resp.data[String(status)]);

    // 1. Pengecekan IF yang benar (cek jika OBJECT, BUKAN array)
    if (
      resp &&
      resp.ok &&
      typeof resp.data === "object" &&
      resp.data !== null &&
      !Array.isArray(resp.data)
    ) {
      // 2. Ambil array yang benar menggunakan KEY STRING
      var dataForStatus = resp.data[String(status)] || [];
      // var filteredData = dataForStatus.filter(function (row) {
      //   // Gunakan String() untuk perbandingan yang aman
      //   return String(row.status) === "1";
      // });

      if (dataForStatus.length === 0) {
        Swal.fire({
          icon: "info",
          title: "Tidak ada data",
          text: "Tidak ada data peserta untuk status ini.",
        });
        return;
      }

      // 3. Definisikan fungsi sort
      var nikSort = function (a, b) {
        if (a.nik < b.nik) return -1;
        if (a.nik > b.nik) return 1;
        return 0;
      };

      // 4. Urutkan ARRAY yang benar (dataForStatus), BUKAN object (resp.data)
      dataForStatus.sort(nikSort);

      var html = "";
      html += '<div style="overflow-x:auto;">';
      html +=
        '<table id="modal-peserta-table" class="display stripe hover w-full" style="width:100%;font-size:13px;">';
      html +=
        "<thead><tr><th>No</th><th>Nama</th><th>TMT Member</th><th>NIP</th><th>NIK</th><th>Periode Invoice</th><th>Jenis Invoice</th><th>Gapok</th><th>Premi Karyawan</th><th>Premi Perusahaan</th><th>Total Premi</th><th>PIC</th><th>Status</th><th>Approval</th><th>Created At</th></tr></thead><tbody>";

      function formatDate(dateString) {
        if (!dateString) return "";
        try {
          var d = new Date(dateString);
          var day = ("0" + d.getDate()).slice(-2);
          var month = ("0" + (d.getMonth() + 1)).slice(-2);
          var year = d.getFullYear();
          return day + "-" + month + "-" + year;
        } catch (e) {
          return dateString; // kembalikan string asli jika format salah
        }
      }
      // 5. Gunakan dataForStatus.forEach
      dataForStatus.forEach(function (row, idx) {
        // ... (Sisa kode parser Anda)
        function parseAmount(val) {
          if (val === null || typeof val === "undefined") return 0;
          if (typeof val === "number") return val;
          var s = String(val).trim();
          s = s.replace(/[^0-9\-,\.]/g, "");
          if (s === "") return 0;
          if (s.indexOf(",") > -1 && s.indexOf(".") > -1) {
            s = s.replace(/\./g, "").replace(",", ".");
          } else {
            s = s.replace(/,/g, "");
          }
          var n = parseFloat(s);
          return isNaN(n) ? 0 : n;
        }

        var nip = row.nip || "";
        var nama = row.nama || "";
        var tmt_asuransi = formatDate(row.tmt_asuransi); // Gunakan helper
        var numGapok = parseAmount(row.gapok);
        var gapok =
          "Rp " +
          numGapok.toLocaleString("id-ID", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          });
        var jenisPremi = "";

        // var jenisPremi =
        //   typeof row.jenis_premi !== "undefined" ? String(row.jenis_premi) : "";
        switch (row.jenis_premi) {
          case "1":
            jenisPremi = "JHT Regular";
            break;
          case "2":
            jenisPremi = "JHT Topup";
            break;
          case "3":
            jenisPremi = "PKP Regular";
            break;
          default:
            jenisPremi = String(row.jenis_premi || "");
        }
        var rawKry =
          typeof row.jml_premi_krywn !== "undefined"
            ? row.jml_premi_krywn
            : typeof row.total_premi !== "undefined"
            ? row.total_premi
            : 0;
        var numKry = parseAmount(rawKry);
        var jmlKry =
          "Rp " +
          numKry.toLocaleString("id-ID", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          });
        var rawPt =
          typeof row.jml_premi_pt !== "undefined"
            ? row.jml_premi_pt
            : typeof row.total_premi !== "undefined"
            ? row.total_premi - numKry
            : 0;
        var numPt = parseAmount(rawPt);
        var jmlPt =
          "Rp " +
          numPt.toLocaleString("id-ID", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          });
        var totalPremi =
          typeof row.total_premi !== "undefined"
            ? parseAmount(row.total_premi)
            : numKry + numPt;
        var totalPremiFormatted =
          "Rp " +
          totalPremi.toLocaleString("id-ID", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          });
        var status = "";
        switch (row.status) {
          case "1":
            status = "Aktif";
            break;
          default:
            status = "CLTP";
        }
        var approveBtn = "";
        let statusData = "";
        switch (row.status_data) {
          case "0":
            statusData = "Not Approved";
            approveBtn = `<span class="px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-yellow-100 text-yellow-800"> ${statusData} </span>`;
            break;
          case "1":
            statusData = "Approved";
            approveBtn = `<span class="px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-green-100 text-green-800"> ${statusData} </span>`;
            break;
          case "2":
            statusData = "Rejected";
            approveBtn = `<span class="px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-red-100 text-red-800"> ${statusData} </span>`;
            break;
          case "3":
            statusData = "Done";
            approveBtn = `<span class="px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-blue-100 text-blue-800"> ${statusData} </span>`;
            break;
          case "4":
            statusData = "Revision";
            approveBtn = `<span class="px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-purple-100 text-purple-800"> ${statusData} </span>`;
            break;
        }
        const nik = row.nik ?? "-";
        // var approved = row.status_data == 1 ? 1 : 0;
        // var badgeClass = approved
        //   ? "px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-green-100 text-green-800"
        //   : "px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-red-100 text-red-800";
        // var badgeLabel = approved ? "Approved" : "Not Approved";
        // var approveBtn =
        //   '<span class="' + btnClass + '">' + status_data + "</span>";
        // var approveBtn =
        //   '<span class="' + btnClass + '">' + status_data + "</span>";
        html += "<tr>";
        html += '<td class="text-center">' + (idx + 1) + "</td>";
        html += '<td class="text-center">' + row.nama + "</td>";
        html +=
          '<td class="text-center">' + formatDate(row.tmt_asuransi) + "</td>";
        html += '<td class="text-center">' + row.nip + "</td>";
        html += '<td class="text-center">' + nik + "</td>";
        html +=
          '<td class="text-center">' +
          formatPeriodeDisplay(row.periode) +
          "</td>";
        html += '<td class="text-center">' + jenisPremi + "</td>";
        html += '<td class="text-center">' + gapok + "</td>";
        html += '<td class="text-center">' + jmlKry + "</td>";
        html += '<td class="text-center">' + jmlPt + "</td>";
        html += '<td class="text-center">' + totalPremiFormatted + "</td>";
        html += '<td class="text-center">' + row.pic + "</td>";
        html += '<td class="text-center">' + status + "</td>";
        html += '<td class="text-center">' + approveBtn + "</td>";
        html += '<td class="text-center">' + row.created_at + "</td>";
        html += "</tr>";
      });

      html += "</tbody></table></div>";

      var displayPeriode = periode;
      var p = String(periode);
      if (/^\d{6}$/.test(p)) {
        var year = p.slice(0, 4);
        var month = p.slice(4, 6);
        var dateObj = new Date(year + "-" + month + "-01");
        var namaBulan = dateObj.toLocaleString("id-ID", {
          month: "long",
        });
        displayPeriode =
          namaBulan.charAt(0).toUpperCase() + namaBulan.slice(1) + " " + year;
      }

      var allApproved =
        dataForStatus.length > 0 &&
        dataForStatus.every(function (r) {
          return Number(r.status_data) === 1;
        });

      var titleText = "Data Peserta Periode " + displayPeriode;
      if (
        typeof jenis !== "undefined" &&
        jenis !== null &&
        String(jenis) !== "" &&
        String(jenis) !== "undefined"
      ) {
        titleText += " - " + jenis;
      }

      titleText +=
        status == 1
          ? " (Approved)"
          : status == 2
          ? " (Rejected)"
          : status == 3
          ? " (Done)"
          : status == 4
          ? " (Revision)"
          : " (Not Approved)";
      const columns = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14]; // Sesuaikan dengan kolom yang ingin ditampilkan
      Swal.fire({
        title: titleText,
        html: html,
        width: "85vw",
        customClass: { popup: "swal2-modal-peserta" },
        showCloseButton: true,
        showCancelButton: false,
        showConfirmButton: false,
        didOpen: () => {
          setTimeout(function () {
            var dt = $("#modal-peserta-table").DataTable({
              pageLength: 10,
              ordering: true,
              dom: "Bfrtip",
              buttons:
                typeof userRole !== "undefined" &&
                userRole === "admintl" &&
                status == 0
                  ? [
                      {
                        text: '<i class="fa-solid fa-check-double mr-2"></i>Approve All',
                        className:
                          "approve-all-btn bg-green-500 hover:bg-green-600 text-white font-semibold rounded-full px-4 py-2 transition flex items-center",
                        action: function (e, dt, node, config) {
                          var btn = $(node);
                          // Tampilkan konfirmasi sebelum melakukan approve all
                          Swal.fire({
                            title: "Konfirmasi",
                            text:
                              "Yakin ingin menyetujui semua peserta untuk periode " +
                              titleText +
                              " ?",
                            icon: "question",
                            showCancelButton: true,
                            confirmButtonText: "Ya, Approve Semua",
                            cancelButtonText: "Batal",
                          }).then(function (result) {
                            if (result.isConfirmed) {
                              btn
                                .prop("disabled", true)
                                .html(
                                  '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Memproses...'
                                );
                              // include jenis if present so approval is scoped by periode + jenis_premi
                              var postData = {
                                approve_all: 1,
                                periode: periode,
                              };
                              if (
                                typeof jenis !== "undefined" &&
                                jenis !== null &&
                                String(jenis) !== ""
                              )
                                postData.jenis = jenis;
                              if (
                                typeof idbatch !== "undefined" &&
                                idbatch !== null &&
                                String(idbatch) !== ""
                              )
                                postData.idbatch = idbatch;
                              postData.status_data = status;

                              $.ajax({
                                url: "api/update_status_peserta.php",
                                type: "POST",
                                data: postData,
                                dataType: "json",
                                success: function (resp) {
                                  if (resp && resp.ok) {
                                    Swal.close();
                                    // Refresh tabel utama peserta (AJAX) using centralized helper
                                    if (
                                      $("#data-peserta-table").length &&
                                      $.fn.DataTable.isDataTable(
                                        "#data-peserta-table"
                                      )
                                    ) {
                                      showTableLoading && showTableLoading();
                                      $.get(
                                        "api/get_peserta.php",
                                        function (resp2) {
                                          if (
                                            resp2 &&
                                            resp2.ok &&
                                            Array.isArray(resp2.data)
                                          ) {
                                            buildMainTableFromData(resp2.data);
                                          }
                                        }
                                      ).always(function () {
                                        hideTableLoading && hideTableLoading();
                                      });
                                    }
                                    // Refresh periode table to update approval status
                                    if (
                                      typeof loadPeriodeTable === "function"
                                    ) {
                                      loadPeriodeTable();
                                    }
                                    if (typeof Toast !== "undefined") {
                                      Toast.fire({
                                        icon: "success",
                                        title:
                                          "Semua peserta periode ini sudah di-approve",
                                      });
                                    } else {
                                      Swal.fire({
                                        icon: "success",
                                        title:
                                          "Semua peserta periode ini sudah di-approve",
                                        timer: 2000,
                                        showConfirmButton: false,
                                      });
                                    }
                                    // disable Approve All button now that all are approved (if DataTable/button still present)
                                    try {
                                      dt.button(0).enable(false);
                                      btn
                                        .addClass(
                                          "opacity-50 cursor-not-allowed"
                                        )
                                        .prop("disabled", true);
                                    } catch (e) {
                                      // ignore if dt/button not available
                                    }
                                  } else {
                                    // reset button and show error
                                    resetApproveAllButton(btn);
                                    showErrorNotification(
                                      "Gagal approve semua peserta"
                                    );
                                  }
                                },
                                error: function () {
                                  // reset button and show error on network failure
                                  resetApproveAllButton(btn);
                                  showErrorNotification(
                                    "Gagal approve semua peserta"
                                  );
                                },
                              });
                            }
                          });
                        },
                      },
                      {
                        extend: "excel",
                        text: '<i class="fa-solid fa-file-excel mr-2" style="font-size:18px;"></i><span class="font-semibold">Excel</span>',
                        className:
                          "mr-2 px-6 py-2 border border-blue-300 text-blue-700 font-semibold rounded-full bg-white hover:bg-blue-600 hover:text-white transition duration-150 focus:outline-none flex items-center",
                        exportOptions: {
                          columns: columns,
                          modifier: { search: "none" },
                          format: {
                            body: function (data, row, column, node) {
                              if (column === 13) {
                                if (data.includes("Not Approved"))
                                  return "Not Approved";
                                if (data.includes("Approved"))
                                  return "Approved";
                                if (data.includes("Rejected"))
                                  return "Rejected";
                                if (data.includes("Done")) return "Done";
                                if (data.includes("Revision"))
                                  return "Revision";
                              }
                              return data;
                            },
                          },
                        },
                      },
                      // {
                      //   extend: "pdf",
                      //   text: '<i class="fa-solid fa-file-pdf mr-2" style="font-size:18px;"></i><span class="font-semibold">PDF</span>',
                      //   className:
                      //     "mr-2 px-6 py-2 border border-blue-300 text-blue-700 font-semibold rounded-full bg-white hover:bg-blue-600 hover:text-white transition duration-150 focus:outline-none flex items-center",
                      //   exportOptions: {
                      //     columns: columns,
                      //     modifier: { search: "none" },
                      //   },
                      //   orientation: "portrait",
                      //   pageSize: "A4",
                      //   customize: function (doc) {
                      //     doc.defaultStyle.fontSize = 10;
                      //     doc.pageMargins = [30, 20, 30, 20];
                      //     doc.content[1].table.widths = [
                      //       20, // No
                      //       80, // NIK
                      //       50, // Periode
                      //       75, // Total Premi
                      //       60, // PIC
                      //       65, // Approval
                      //     ];
                      //     var body = doc.content[1].table.body;
                      //     for (var i = 0; i < body.length; i++) {
                      //       for (var j = 0; j < body[i].length; j++) {
                      //         body[i][j].alignment = "center";
                      //         body[i][j].margin = [0, 4, 0, 4];
                      //       }
                      //     }
                      //   },
                      // },
                      {
                        extend: "print",
                        text: '<i class="fa-solid fa-print mr-2" style="font-size:18px;"></i><span class="font-semibold">Print</span>',
                        className:
                          "mr-2 px-6 py-2 border border-blue-300 text-blue-700 font-semibold rounded-full bg-white hover:bg-blue-600 hover:text-white transition duration-150 focus:outline-none flex items-center",
                        exportOptions: {
                          columns: columns,
                          modifier: { search: "none" },
                        },
                      },
                    ]
                  : [
                      {
                        extend: "excel",
                        text: '<i class="fa-solid fa-file-excel mr-2" style="font-size:18px;"></i><span class="font-semibold">Excel</span>',
                        className:
                          "mr-2 px-6 py-2 border border-blue-300 text-blue-700 font-semibold rounded-full bg-white hover:bg-blue-600 hover:text-white transition duration-150 focus:outline-none flex items-center",
                        exportOptions: {
                          columns: columns,
                          modifier: { search: "none" },
                          format: {
                            body: function (data, row, column, node) {
                              if (column === 13) {
                                if (data.includes("Not Approved"))
                                  return "Not Approved";
                                if (data.includes("Approved"))
                                  return "Approved";
                                if (data.includes("Rejected"))
                                  return "Rejected";
                                if (data.includes("Done")) return "Done";
                                if (data.includes("Revision"))
                                  return "Revision";
                              }
                              return data;
                            },
                          },
                        },
                      },
                      // {
                      //   extend: "pdf",
                      //   text: '<i class="fa-solid fa-file-pdf mr-2" style="font-size:18px;"></i><span class="font-semibold">PDF</span>',
                      //   className:
                      //     "mr-2 px-6 py-2 border border-blue-300 text-blue-700 font-semibold rounded-full bg-white hover:bg-blue-600 hover:text-white transition duration-150 focus:outline-none flex items-center",
                      //   exportOptions: {
                      //     columns: columns,
                      //     modifier: { search: "none" },
                      //   },
                      //   orientation: "landscape",
                      //   pageSize: "A4",
                      //   customize: function (doc) {
                      //     doc.defaultStyle.fontSize = 10;
                      //     doc.pageMargins = [30, 20, 30, 20];
                      //     doc.content[1].table.widths = [
                      //       20, // No
                      //       80, // NIK
                      //       50, // Periode
                      //       75, // Total Premi
                      //       60, // PIC
                      //       65, // Approval
                      //     ];
                      //     var body = doc.content[1].table.body;
                      //     for (var i = 0; i < body.length; i++) {
                      //       for (var j = 0; j < body[i].length; j++) {
                      //         body[i][j].alignment = "center";
                      //         body[i][j].margin = [0, 4, 0, 4];
                      //       }
                      //     }
                      //   },
                      // },
                      {
                        extend: "print",
                        text: '<i class="fa-solid fa-print mr-2" style="font-size:18px;"></i><span class="font-semibold">Print</span>',
                        className:
                          "mr-2 px-6 py-2 border border-blue-300 text-blue-700 font-semibold rounded-full bg-white hover:bg-blue-600 hover:text-white transition duration-150 focus:outline-none flex items-center",
                        exportOptions: {
                          columns: columns,
                          modifier: { search: "none" },
                        },
                      },
                    ],
              order: [[1, "asc"]],
              columnDefs: [
                { targets: 0, className: "text-center", orderable: false },
                { targets: "_all", className: "text-center" },
              ],
            });
            dt.column(12).search("Aktif").draw();
            // After DataTable initialized, set Approve All initial enabled/disabled state (only for AdminTL)
            if (typeof userRole !== "undefined" && userRole === "admintl") {
              try {
                // Button index 0 is the Approve All button we added above
                // if (allApproved) {
                //   dt.button(0).enable(false);
                //   // visually indicate disabled
                //   $("#modal-peserta-table")
                //     .closest(".swal2-html-container")
                //     .find(".approve-all-btn")
                //     .addClass("opacity-50 cursor-not-allowed")
                //     .prop("disabled", true);
                // } else {
                //   dt.button(0).enable(true);
                //   $("#modal-peserta-table")
                //     .closest(".swal2-html-container")
                //     .find(".approve-all-btn")
                //     .removeClass("opacity-50 cursor-not-allowed")
                //     .prop("disabled", false);
                // }
              } catch (e) {
                // ignore if button API not available
                console.warn("Could not set Approve All button state", e);
              }
            }
            // Style tombol approve all agar lebih menonjol
            setTimeout(function () {
              $(".approve-all-btn")
                .css({
                  "background-color": "#22c55e",
                  color: "#fff",
                  border: "none",
                  "border-radius": "9999px",
                  "margin-right": "8px",
                  "font-weight": "600",
                  "font-size": "15px",
                  "box-shadow": "0 1px 4px rgba(0,0,0,0.04)",
                })
                .hover(
                  function () {
                    $(this).css("background-color", "#16a34a");
                  },
                  function () {
                    $(this).css("background-color", "#22c55e");
                  }
                );
            }, 200);
          }, 100);
        },
      });
    } else {
      Swal.fire({
        icon: "error",
        title: "Tidak ada data",
        text: resp && resp.msg ? resp.msg : "",
      });
    }
  }).fail(function () {
    hideTableLoading();
    Swal.fire({ icon: "error", title: "Gagal memuat data" });
  });
});

// Fungsi untuk menampilkan overlay loading
function showTableLoading() {
  $("#table-loading-overlay").css("display", "flex");
}
function hideTableLoading() {
  $("#table-loading-overlay").hide();
}
$(document).ready(function () {
  // Inisialisasi Select2 pada select periode
  $("#periode").select2({
    theme: "default",
    minimumResultsForSearch: 10,
    width: "resolve",
    dropdownAutoWidth: true,
  });
  var table = $("#data-peserta-table").DataTable({
    pageLength: 10,
    // default order: Periode (index 1) desc (karena kolom No di depan)
    order: [[1, "desc"]],
    autoWidth: false,
    // column widths set to keep stable layout like the screenshot
    columnDefs: [
      { orderable: false, targets: [0, 6, 7] }, // No, Status, Aksi column
      {
        className: "text-center align-middle",
        targets: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
      },
      {
        targets: 0,
        width: "20px",
        render: function (data, type, row, meta) {
          return meta.row + 1;
        },
      },
      { targets: 1, width: "100px" },
      { targets: 2, width: "100px" },
      { targets: 3, width: "100px" },
      { targets: 4, width: "130px" },
      { targets: 5, width: "130px" },
      { targets: 6, width: "160px" },
      {
        targets: 9,
        width: "150px",
        visible: true,
        orderable: false,
        searchable: false,
      }, // Aksi
    ],
    dom: "Bfrtip",
    buttons: [
      {
        extend: "excel",
        text: '<i class="fa-solid fa-file-excel mr-2" style="font-size:18px;"></i><span class="font-semibold">Excel</span>',
        className:
          "mr-2 px-6 py-2 border border-blue-300 text-blue-700 font-semibold rounded-full bg-white hover:bg-blue-600 hover:text-white transition duration-150 focus:outline-none flex items-center",
        exportOptions: {
          columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
          format: {
            body: function (data, row, column, node) {
              if (column === 9) {
                console.log(data);
                if (data.includes("Not Approved")) return "Not Approved";
                if (data.includes("Approved")) return "Approved";
                if (data.includes("Rejected")) return "Rejected";
                if (data.includes("Done")) return "Done";
                if (data.includes("Revision")) return "Revision";
              }
              // if (column === 5) {
              //   var num = String(data)
              //     .replace(/[^\d,\.]/g, "")
              //     .replace(/\.(?=\d{3,})/g, "")
              //     .replace(",", ".");
              //   return num;
              // }
              // if (column === 6) {
              //   var match =
              //     String(data).match(/>(Approved|Not Approved)</) ||
              //     String(data).match(/>(Verified|Not Verified)</);
              //   return match ? match[1] : data;
              // }
              return data;
            },
          },
        },
      },
      // {
      //   extend: "pdf",
      //   text: '<i class="fa-solid fa-file-pdf mr-2" style="font-size:18px;"></i><span class="font-semibold">PDF</span>',
      //   className:
      //     "mr-2 px-6 py-2 border border-blue-300 text-blue-700 font-semibold rounded-full bg-white hover:bg-blue-600 hover:text-white transition duration-150 focus:outline-none flex items-center",
      //   exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] },
      //   orientation: "landscape",
      //   pageSize: "A4",
      //   customize: function (doc) {
      //     doc.defaultStyle.fontSize = 10;
      //     doc.pageMargins = [30, 20, 30, 20];
      //     // Adjust widths to match new columns: No, Periode, Jenis, Jml Karyawan, Jml PT, Total, Status, Aksi
      //     doc.content[1].table.widths = [20, 70, 70, 80, 80, 80, 65, 65];
      //     var body = doc.content[1].table.body;
      //     for (var i = 0; i < body.length; i++) {
      //       for (var j = 0; j < body[i].length; j++) {
      //         body[i][j].alignment = "center";
      //         body[i][j].margin = [0, 4, 0, 4];
      //       }
      //     }
      //   },
      // },
      {
        extend: "print",
        text: '<i class="fa-solid fa-print mr-2" style="font-size:18px;"></i><span class="font-semibold">Print</span>',
        className:
          "mr-2 px-6 py-2 border border-blue-300 text-blue-700 font-semibold rounded-full bg-white hover:bg-blue-600 hover:text-white transition duration-150 focus:outline-none flex items-center",
        exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9] },
      },
    ],
  });

  // Inisialisasi DataTable untuk Registrasi Peserta jika ada
  if ($("#registrasi-peserta-table").length) {
    $("#registrasi-peserta-table").DataTable({
      pageLength: 10,
      order: [[1, "asc"]],
      columnDefs: [
        { orderable: false, targets: 0 },
        { className: "text-center align-middle", targets: "_all" },
      ],
      dom: "Bfrtip",
      buttons: [
        {
          extend: "excel",
          text: '<i class="fa-solid fa-file-excel mr-2" style="font-size:18px;"></i><span class="font-semibold">Excel</span>',
          className:
            "mr-2 px-6 py-2 border border-blue-300 text-blue-700 font-semibold rounded-full bg-white hover:bg-blue-600 hover:text-white transition duration-150 focus:outline-none flex items-center",
          exportOptions: {
            columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            format: {
              body: function (data, row, column, node) {
                if (column === 7) {
                  var match = String(data).match(/>(Verified|Not Verified)</);
                  return match ? match[1] : data;
                }
                return data;
              },
            },
          },
        },
        {
          extend: "pdf",
          text: '<i class="fa-solid fa-file-pdf mr-2" style="font-size:18px;"></i><span class="font-semibold">PDF</span>',
          className:
            "mr-2 px-6 py-2 border border-blue-300 text-blue-700 font-semibold rounded-full bg-white hover:bg-blue-600 hover:text-white transition duration-150 focus:outline-none flex items-center",
          exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9] },
          orientation: "portrait",
          pageSize: "A4",
          customize: function (doc) {
            doc.defaultStyle.fontSize = 10;
            doc.pageMargins = [30, 20, 30, 20];
            // Adjust column widths roughly
            doc.content[1].table.widths = [
              20, 80, 100, 35, 60, 80, 60, 60, 70, 100,
            ];
            var body = doc.content[1].table.body;
            for (var i = 0; i < body.length; i++) {
              for (var j = 0; j < body[i].length; j++) {
                body[i][j].alignment = "center";
                body[i][j].margin = [0, 4, 0, 4];
              }
            }
          },
        },
        {
          extend: "print",
          text: '<i class="fa-solid fa-print mr-2" style="font-size:18px;"></i><span class="font-semibold">Print</span>',
          className:
            "mr-2 px-6 py-2 border border-blue-300 text-blue-700 font-semibold rounded-full bg-white hover:bg-blue-600 hover:text-white transition duration-150 focus:outline-none flex items-center",
          exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9] },
        },
      ],
    });
  }

  // Approve All Registrasi button handler
  $(document).on("click", "#approve-all-registrasi-btn", function (e) {
    e.preventDefault();
    var btn = $(this);
    Swal.fire({
      title: "Setujui semua registrasi? ",
      text: "Aksi ini akan menandai semua akun yang belum diverifikasi sebagai terverifikasi.",
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Ya, Setujui Semua",
      cancelButtonText: "Batal",
    }).then(function (res) {
      if (!res.isConfirmed) return;
      btn.prop("disabled", true).text("Memproses...");
      $.post("api/approve_registrasi_all.php")
        .done(function (resp) {
          if (resp && resp.ok) {
            Toast.fire({
              icon: "success",
              title: "Semua registrasi yang belum diverifikasi telah disetujui",
            });
            // delegate click to handle dynamic table content with confirmation
            $(document).on("click", ".approve-btn", function (e) {
              e.preventDefault();
              var btn = $(this);
              var id = btn.data("id");
              var status = parseInt(btn.data("status")) ? 1 : 0;
              var newStatus = status ? 0 : 1;

              var confirmText =
                newStatus === 1
                  ? "Setujui peserta ini?"
                  : "Batalkan persetujuan peserta ini?";

              Swal.fire({
                title: confirmText,
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Ya",
                cancelButtonText: "Batal",
              }).then(function (result) {
                if (result.isConfirmed) {
                  btn.prop("disabled", true).text("Diproses...");

                  $.ajax({
                    url: "api/update_status_peserta.php",
                    type: "POST",
                    dataType: "json",
                    data: { id: id, status: newStatus },
                    success: function (resp) {
                      if (resp && resp.ok) {
                        // Otomatis refresh tabel AJAX agar data dan status langsung update
                        showTableLoading();
                        $.get("api/get_peserta.php", function (resp2) {
                          if (resp2 && resp2.ok && Array.isArray(resp2.data)) {
                            buildMainTableFromData(resp2.data);
                          }
                        }).always(function () {
                          hideTableLoading();
                        });
                        Toast.fire({
                          icon: "success",
                          title:
                            newStatus === 1
                              ? "Peserta disetujui"
                              : "Persetujuan dibatalkan",
                        });
                      } else {
                        Toast.fire({
                          icon: "error",
                          title: "Gagal menyimpan status",
                        });
                      }
                    },
                    error: function (xhr) {
                      Toast.fire({
                        icon: "error",
                        title: "Terjadi kesalahan saat menyimpan",
                      });
                    },
                    complete: function () {
                      btn.prop("disabled", false);
                    },
                  });
                }
              });
            });
            try {
              var userVerify =
                resp.audit && resp.audit.user_verify
                  ? resp.audit.user_verify
                  : "&mdash;";
              var tgl = resp.audit && resp.audit.tgl ? resp.audit.tgl : null;
              $("#registrasi-peserta-table tbody tr").each(function () {
                var row = $(this);
                var verifCell = row.find("td").eq(7);
                // If already verified, skip
                if (
                  verifCell.find("span").text().trim().toLowerCase() ===
                  "verified"
                )
                  return;
                verifCell.html(
                  '<span class="px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-green-100 text-green-800">Verified</span>'
                );
                var approvedByCell = row.find("td").eq(8);
                var approvedAtCell = row.find("td").eq(9);
                approvedByCell.html(userVerify);
                if (tgl) {
                  var dt = new Date(tgl);
                  if (!isNaN(dt.getTime())) {
                    var dd = ("0" + dt.getDate()).slice(-2);
                    var mm = ("0" + (dt.getMonth() + 1)).slice(-2);
                    var yyyy = dt.getFullYear();
                    approvedAtCell.html(dd + "-" + mm + "-" + yyyy);
                  } else {
                    var s = String(tgl).trim();
                    var datePart = s.split(" ")[0];
                    var m = datePart.match(/^(\d{4})-(\d{2})-(\d{2})$/);
                    if (m) approvedAtCell.html(m[3] + "-" + m[2] + "-" + m[1]);
                    else approvedAtCell.html(tgl);
                  }
                } else {
                  approvedAtCell.html("&mdash;");
                }
              });
              // refresh main peserta table
              if (
                $("#data-peserta-table").length &&
                $.fn.DataTable.isDataTable("#data-peserta-table")
              ) {
                showTableLoading();
                $.get("api/get_peserta.php", function (resp2) {
                  if (resp2 && resp2.ok && Array.isArray(resp2.data)) {
                    buildMainTableFromData(resp2.data);
                  }
                }).always(function () {
                  hideTableLoading();
                });
              }
            } catch (e) {
              console.warn(
                "Could not update registrasi table after approve all",
                e
              );
            }
          } else {
            Toast.fire({
              icon: "error",
              title: resp && resp.error ? resp.error : "Gagal approve all",
            });
            btn.prop("disabled", false).text("Approve All");
          }
        })
        .fail(function () {
          Toast.fire({ icon: "error", title: "Terjadi kesalahan jaringan" });
          btn.prop("disabled", false).text("Approve All");
        });
    });
  });

  // Approve registrasi (delegated handler)
  $(document).on("click", ".btn-approve-registrasi", function (e) {
    e.preventDefault();
    var btn = $(this);
    var id = btn.data("id");
    if (!id) return;
    Swal.fire({
      title: "Setujui akun ini?",
      text: "Aksi ini akan menandai akun sebagai terverifikasi dan membuat entri data_peserta (status_data=1).",
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Ya, Setujui",
      cancelButtonText: "Batal",
    }).then(function (result) {
      if (!result.isConfirmed) return;
      btn.prop("disabled", true).text("Memproses...");
      $.post("api/approve_registrasi.php", { id: id })
        .done(function (resp) {
          if (resp && resp.ok) {
            Toast.fire({ icon: "success", title: "Akun diverifikasi" });
            // Update registrasi row in-place: change Verifikasi badge and remove Approve button
            try {
              var row = btn.closest("tr");
              // Verifikasi column is now index 7 (0-based)
              var verifCell = row.find("td").eq(7);
              verifCell.html(
                '<span class="px-3 py-1 inline-flex text-xs text-center leading-4 font-semibold rounded-full bg-green-100 text-green-800">Verified</span>'
              );
              // Approved By column is index 8
              var approvedByCell = row.find("td").eq(8);
              // Approved At column is index 9
              var approvedAtCell = row.find("td").eq(9);
              // fill values from response audit if available
              if (resp.audit) {
                var userVerify = resp.audit.user_verify || "&mdash;";
                var tgl = resp.audit.tgl || null;
                approvedByCell.html(userVerify);
                if (tgl) {
                  // show date only (DD-MM-YYYY)
                  var dt = new Date(tgl);
                  if (!isNaN(dt.getTime())) {
                    var dd = ("0" + dt.getDate()).slice(-2);
                    var mm = ("0" + (dt.getMonth() + 1)).slice(-2);
                    var yyyy = dt.getFullYear();
                    approvedAtCell.html(dd + "-" + mm + "-" + yyyy);
                  } else {
                    // try to extract YYYY-MM-DD from string and reformat
                    var s = String(tgl).trim();
                    var datePart = s.split(" ")[0];
                    var m = datePart.match(/^(\d{4})-(\d{2})-(\d{2})$/);
                    if (m) {
                      approvedAtCell.html(m[3] + "-" + m[2] + "-" + m[1]);
                    } else {
                      approvedAtCell.html(tgl);
                    }
                  }
                } else {
                  approvedAtCell.html("&mdash;");
                }
              }
              // no per-row aksi column (Approve All handles approvals)
            } catch (e) {
              console.warn("Could not update registrasi row inline", e);
            }

            // Refresh main data_peserta table via AJAX
            try {
              if (
                $("#data-peserta-table").length &&
                $.fn.DataTable.isDataTable("#data-peserta-table")
              ) {
                showTableLoading();
                $.get("api/get_peserta.php", function (resp2) {
                  if (resp2 && resp2.ok && Array.isArray(resp2.data)) {
                    buildMainTableFromData(resp2.data);
                  }
                }).always(function () {
                  hideTableLoading();
                });
              }
            } catch (e) {
              console.warn("Could not refresh main peserta table", e);
            }

            // Refresh periode table (list of periods)
            try {
              if (typeof loadPeriodeTable === "function") loadPeriodeTable();
            } catch (e) {}
          } else {
            Toast.fire({
              icon: "error",
              title: resp && resp.error ? resp.error : "Gagal verifikasi",
            });
            btn.prop("disabled", false).text("Approve");
          }
        })
        .fail(function () {
          Toast.fire({ icon: "error", title: "Terjadi kesalahan jaringan" });
          btn.prop("disabled", false).text("Approve");
        });
    });
  });

  // Filter DataTable berdasarkan periode
  $("#periode").on("change", function () {
    var val = $(this).val();
    // Kolom Periode ada di index 1 (setelah No)
    if (val) {
      table
        .column(1)
        .search("^" + val + "$", true, false)
        .draw();
    } else {
      table.column(1).search("", true, false).draw();
    }
  });

  // SweetAlert2 Toast mixin
  const Toast = Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
  });

  // delegate click to handle dynamic table content with confirmation
  $(document).on("click", ".approve-btn", function (e) {
    e.preventDefault();
    var btn = $(this);
    var id = btn.data("id");
    var status = parseInt(btn.data("status")) ? 1 : 0;
    var newStatus = status ? 0 : 1;

    var confirmText =
      newStatus === 1
        ? "Setujui peserta ini?"
        : "Batalkan persetujuan peserta ini?";

    Swal.fire({
      title: confirmText,
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Ya",
      cancelButtonText: "Batal",
    }).then(function (result) {
      if (result.isConfirmed) {
        btn.prop("disabled", true).text("Diproses...");

        $.post("api/update_status_peserta.php", { id: id, status: newStatus })
          .done(function (resp) {
            if (resp && resp.ok) {
              // Otomatis refresh tabel AJAX agar data dan status langsung update
              showTableLoading();
              $.get("api/get_peserta.php", function (resp2) {
                if (resp2 && resp2.ok && Array.isArray(resp2.data)) {
                  buildMainTableFromData(resp2.data);
                }
              }).always(function () {
                hideTableLoading();
              });
              Toast.fire({
                icon: "success",
                title:
                  newStatus === 1
                    ? "Peserta disetujui"
                    : "Persetujuan dibatalkan",
              });
            } else {
              Toast.fire({ icon: "error", title: "Gagal menyimpan status" });
            }
          })
          .fail(function (xhr) {
            Toast.fire({
              icon: "error",
              title: "Terjadi kesalahan saat menyimpan",
            });
          })
          .always(function () {
            btn.prop("disabled", false);
          });
      }
    });
  });

  // Handler edit data peserta

  // Handler hapus data peserta
  $(document).on("click", ".btn-delete-data", function (e) {
    e.preventDefault();
    var btn = $(this);
    var id = btn.data("id");
    Swal.fire({
      title: "Hapus Data Peserta?",
      text: "Data yang dihapus tidak dapat dikembalikan!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Ya, Hapus",
      cancelButtonText: "Batal",
      confirmButtonColor: "#d33",
    }).then(function (result) {
      if (result.isConfirmed) {
        $.post("api/delete_peserta.php", { id: id })
          .done(function (resp) {
            if (resp && resp.ok) {
              Swal.fire({
                icon: "success",
                title: "Data berhasil dihapus",
                timer: 1500,
                showConfirmButton: false,
              });
              // Refresh tabel data peserta setelah delete berhasil
              setTimeout(function () {
                refreshDataPesertaTable();
              }, 1000);
            } else {
              Swal.fire({
                icon: "error",
                title: "Gagal menghapus data",
                text: resp && resp.msg ? resp.msg : "",
              });
            }
          })
          .fail(function (xhr) {
            console.error("Delete peserta failed:", xhr);
            Swal.fire({ icon: "error", title: "Terjadi kesalahan koneksi" });
          });
      }
    });
  });

  // Fungsi reusable untuk refresh tabel data peserta
  function refreshDataPesertaTable() {
    if (!$.fn.DataTable || !$.fn.DataTable.isDataTable("#data-peserta-table")) {
      console.warn("DataTable #data-peserta-table not initialized yet");
      return;
    }
    showTableLoading();
    $.get("api/get_peserta.php", function (resp) {
      hideTableLoading();
      if (resp && resp.ok && Array.isArray(resp.data)) {
        buildMainTableFromData(resp.data);
        // Refresh periode tabel juga
        try {
          if (typeof loadPeriodeTable === "function") loadPeriodeTable();
        } catch (e) {
          console.warn("loadPeriodeTable error:", e);
        }
      } else {
        console.error("get_peserta.php returned invalid response:", resp);
        Toast.fire({ icon: "error", title: "Gagal memuat data peserta" });
      }
    }).fail(function (xhr) {
      hideTableLoading();
      console.error("get_peserta.php request failed:", xhr);
      Toast.fire({
        icon: "error",
        title: "Terjadi kesalahan saat memuat data",
      });
    });
  }

  // Expose fungsi global untuk digunakan di tempat lain jika diperlukan
  window.refreshDataPesertaTable = refreshDataPesertaTable;
});

const Toast = Swal.mixin({
  toast: true,
  position: "top-end",
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
});

$(document).on("click", ".btn-add-invoice", function (e) {
  e.preventDefault();
  var idbatch = $(this).data("idbatch");
  var periode = $(this).data("periode");
  console.log(idbatch);
  console.log(periode);

  Swal.fire({
    title: "Tambah Data Invoice",
    allowOutsideClick: true,
    allowEscapeKey: true,
    heightAuto: false,
    showCloseButton: true,
    html: `
                <div style="text-align:left; padding:10px 5px; border-top:1px solid #eee; border-bottom:1px solid #eee; max-height:700px; overflow-y:auto;">
                    <!-- FORM FIELDS - SIMPLE COLUMN LAYOUT -->
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <!-- ROW 1: PERIODE (DROPDOWN) -->
                        <div style="display:flex; flex-direction:column; gap:4px;">
                            <label for="swal-periode-select" style="font-size:14px;font-weight:500;">Periode</label>
                            <select id="swal-periode-select" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" style="margin:0;">
                                <option value="">-- Pilih Periode --</option>
                            </select>
                        </div>
                        
                        <!-- ROW 2: BULAN & TAHUN (READONLY, FILLED FROM PERIODE SELECT) -->
                        <div style="display:flex; flex-direction:column; gap:4px;">
                            <label style="font-size:14px;font-weight:500;">Bulan / Tahun</label>
                            <div style="display:flex; gap:5px; align-items:center;">
                                <input id="swal-bulan" type="text" readonly class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block flex-1 p-2.5" style="margin:0; cursor:not-allowed; max-width:48%;">
                                <span style="font-size:14px; color:#666;">/</span>
                                <input id="swal-tahun" type="text" readonly class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block flex-1 p-2.5" style="margin:0; cursor:not-allowed; max-width:48%;">
                            </div>
                        </div>
                        
                        <!-- ROW 3: JENIS_INVOICE (DROPDOWN) & TGL_INVOICE (2 COLUMNS) -->
                        <div style="display:flex; gap:12px;">
                            <div style="flex:1; display:flex; flex-direction:column; gap:4px;">
                                <label for="swal-jenis_invoice" style="font-size:14px;font-weight:500;">Jenis Premi</label>
                                <select id="swal-jenis_invoice" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" style="margin:0;">
                                    <option value="">-- Pilih Jenis Premi --</option>
                                </select>
                            </div>
                            <div style="flex:1; display:flex; flex-direction:column; gap:4px;">
                                <label for="swal-tgl_invoice" style="font-size:14px;font-weight:500;">Tanggal Invoice</label>
                                <input id="swal-tgl_invoice" type="text" placeholder="Pilih Tanggal" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" style="margin:0;">
                            </div>
                        </div>
                        
                        <!-- ROW 4: NOINVOICE & JML_PESERTA (2 COLUMNS) -->
                        <div style="display:flex; gap:12px;">
                            <div style="flex:1; display:flex; flex-direction:column; gap:4px;">
                                <label for="swal-noinvoice" style="font-size:14px;font-weight:500;">No. Invoice</label>
                                <input id="swal-noinvoice" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="Nomor Invoice" style="margin:0;">
                            </div>
                            <div style="flex:1; display:flex; flex-direction:column; gap:4px;">
                                <label for="swal-jml_peserta" style="font-size:14px;font-weight:500;">Jumlah Peserta</label>
                                <input id="swal-jml_peserta" type="number" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="Jumlah Peserta" style="margin:0;">
                            </div>
                        </div>
                        
                        <!-- ROW 5: PIC & JML_PREMI (2 COLUMNS) -->
                        <div style="display:flex; gap:12px;">
                            <div style="flex:1; display:flex; flex-direction:column; gap:4px;">
                                <label for="swal-pic" style="font-size:14px;font-weight:500;">PIC</label>
                                <input id="swal-pic" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="Nama PIC" style="margin:0;">
                            </div>
                            <div style="flex:1; display:flex; flex-direction:column; gap:4px;">
                                <label for="swal-jml_premi" style="font-size:14px;font-weight:500;">Total Premi</label>
                                <div style="display:flex; align-items:center; border:1px solid #ccc; border-radius:8px; overflow:hidden; width:100%; height:42px;">
                                    <span style="padding:0 10px; font-size:14px; color:#555; white-space:nowrap;">Rp</span>
                                    <input id="swal-jml_premi" type="text" class="bg-gray-50 border-none text-gray-900 text-sm outline-none block flex-1" placeholder="Masukkan premi" inputmode="numeric" style="margin:0; padding:16px 10px; font-size:14px; border-radius:8px; border-left:1px solid #ccc; border-right:1px solid #fff;">
                                </div>
                            </div>
                        </div>
                        
                        <!-- ROW 6: FILE UPLOADS (FULL WIDTH) -->
                        <div style="display:flex; flex-direction:column; gap:12px; padding-top:12px; border-top:1px solid #f2f2f2;">
                            <div style="display:flex; flex-direction:column; gap:4px;">
                                <label for="swal-link_file" style="font-size:14px;font-weight:500;">File Invoice (PDF)</label>
                                <input id="swal-link_file" type="file" accept=".pdf" class="block w-full text-sm text-gray-500 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none p-2" style="margin:0;">
                                <p style="font-size:12px; color:#999; margin:0;">Format: PDF</p>
                            </div>
                            
                            <div style="display:flex; flex-direction:column; gap:4px;">
                                <label for="swal-link_peserta" style="font-size:14px;font-weight:500;">Data Peserta (Excel)</label>
                                <input id="swal-link_peserta" type="file" accept=".xlsx,.xls" class="block w-full text-sm text-gray-500 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none p-2" style="margin:0;">
                                <p style="font-size:12px; color:#999; margin:0;">Format: Excel (.xlsx, .xls)</p>
                            </div>
                        </div>
                    </div>
                </div>
            `,
    focusConfirm: false,
    showCancelButton: true,
    confirmButtonText: "Simpan",
    cancelButtonText: "Batal",
    didOpen: () => {
      // Initialize Flatpickr datepickers after modal opens
      setTimeout(() => {
        const periodeSelect = document.getElementById("swal-periode-select");
        const jenisSelect = document.getElementById("swal-jenis_invoice");

        // Initialize Select2 on periode and jenis dropdowns
        if (periodeSelect) {
          $(periodeSelect).select2({
            dropdownParent: $(".swal2-container"),
            placeholder: "Pilih Periode",
            allowClear: true,
            width: "100%",
          });
        }
        if (jenisSelect) {
          $(jenisSelect).select2({
            dropdownParent: $(".swal2-container"),
            placeholder: "Pilih Jenis Premi",
            allowClear: true,
            width: "100%",
          });
        }

        // Load periode dropdown from API
        $.get("api/get_periode_jenis.php?mode=periods", function (resp) {
          if (resp.ok && resp.data) {
            if (periodeSelect) {
              resp.data.forEach(function (item) {
                const opt = document.createElement("option");
                opt.value = item.periode;
                opt.textContent = item.periode;
                periodeSelect.appendChild(opt);
              });
              // Refresh Select2 to show new options
              $(periodeSelect).select2({
                dropdownParent: $(".swal2-container"),
                placeholder: "Pilih Periode",
                allowClear: true,
                width: "100%",
              });
            }
          }
        });

        // Event listener: when Periode is selected, populate Jenis dropdown and fill Bulan/Tahun
        if (periodeSelect) {
          $(periodeSelect).on("change", function () {
            const selectedPeriode = this.value;
            const bulanEl = document.getElementById("swal-bulan");
            const tahunEl = document.getElementById("swal-tahun");

            // Parse periode YYYYMM -> MM and YYYY
            if (selectedPeriode && selectedPeriode.length === 6) {
              const yyyy = selectedPeriode.substring(0, 4);
              const mm = selectedPeriode.substring(4, 6);
              if (bulanEl) bulanEl.value = mm;
              if (tahunEl) tahunEl.value = yyyy;
            } else {
              if (bulanEl) bulanEl.value = "";
              if (tahunEl) tahunEl.value = "";
            }

            // Clear and load jenis dropdown for this periode
            if (jenisSelect) {
              // Clear existing options (keep placeholder)
              $(jenisSelect)
                .empty()
                .append('<option value="">Pilih Jenis Premi</option>');

              if (selectedPeriode) {
                $.get(
                  "api/get_periode_jenis.php?mode=periode_jenis&periode=" +
                    encodeURIComponent(selectedPeriode) +
                    "&idbatch=" +
                    encodeURIComponent(idbatch),
                  function (resp) {
                    if (resp.ok && resp.data) {
                      resp.data.forEach(function (item) {
                        const opt = document.createElement("option");
                        opt.value = item.jenis_value;
                        opt.textContent = item.jenis_name;
                        jenisSelect.appendChild(opt);
                      });
                      // Trigger Select2 refresh
                      $(jenisSelect).trigger("change");
                    }
                  }
                );
              }
            }
          });
        }

        // Event listener: when Jenis is selected, auto-fill Jumlah Peserta and Total Premi
        if (jenisSelect) {
          $(jenisSelect).on("change", function () {
            const selectedJenis = this.value;
            const selectedPeriode = periodeSelect ? periodeSelect.value : "";
            const jmlPesertaEl = document.getElementById("swal-jml_peserta");
            const jmlPremilEl = document.getElementById("swal-jml_premi");

            if (selectedPeriode && selectedJenis) {
              // Fetch aggregated data
              $.get(
                "api/get_periode_jenis.php?mode=jenis_detail&periode=" +
                  encodeURIComponent(selectedPeriode) +
                  "&jenis=" +
                  encodeURIComponent(selectedJenis) +
                  "&idbatch=" +
                  encodeURIComponent(idbatch),
                function (resp) {
                  if (resp.ok) {
                    if (jmlPesertaEl) jmlPesertaEl.value = resp.jumlah_peserta;
                    if (jmlPremilEl) {
                      // Format total_premi as currency (with dots as thousand separator)
                      const total = resp.total_premi;
                      const formatted = Math.floor(total)
                        .toString()
                        .replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                      jmlPremilEl.value = formatted;
                    }
                  }
                }
              );
            }
          });
        }

        // Flatpickr for Tanggal Invoice (Full date) - will be formatted as YYYY-MM-DD
        flatpickr("#swal-tgl_invoice", {
          mode: "single",
          dateFormat: "Y-m-d",
          appendTo: document.body,
          position: "auto",
        });

        // If user manually types/pastes periode, parse it on blur
        const periodeElManual = document.getElementById("swal-periode");
        if (periodeElManual) {
          periodeElManual.addEventListener("blur", function () {
            const v = String(this.value || "").trim();
            let y = "",
              m = "";
            // Accept either YYYYMM or YYYY-MM or YYYY/MM
            const r1 = v.match(/^(\d{4})(\d{2})$/);
            const r2 = v.match(/^(\d{4})[-\/](\d{2})$/);
            if (r1) {
              y = r1[1];
              m = r1[2];
            } else if (r2) {
              y = r2[1];
              m = r2[2];
            }
            if (y && m) {
              // normalize to YYYYMM in the periode input
              this.value = y + m;
              const bulanEl = document.getElementById("swal-bulan");
              const tahunEl = document.getElementById("swal-tahun");
              if (bulanEl) bulanEl.value = m;
              if (tahunEl) tahunEl.value = y;
            }
          });
        }

        // Currency formatter for Total Premi input (ID: swal-jml_premi)
        const premiEl = document.getElementById("swal-jml_premi");
        if (premiEl) {
          const formatToCurrency = (val) => {
            // keep only digits
            const digits = String(val || "").replace(/\D/g, "");
            if (!digits) return "";
            return digits.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
          };

          const setFormatted = (el) => {
            const cur = el.value;
            el.value = formatToCurrency(cur);
          };

          // format on input (simple, caret position may reset)
          premiEl.addEventListener("input", function (e) {
            const pos = this.selectionStart;
            setFormatted(this);
            // try to keep caret at end
            this.selectionStart = this.selectionEnd = this.value.length;
          });

          // format on blur to ensure proper display
          premiEl.addEventListener("blur", function () {
            setFormatted(this);
          });

          // allow paste: sanitize then format
          premiEl.addEventListener("paste", function (e) {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData(
              "text"
            );
            const digits = String(text).replace(/\D/g, "");
            this.value = formatToCurrency(digits);
          });
        }
      }, 100);
    },
    preConfirm: () => {
      const periode = document.getElementById("swal-periode-select").value;
      const bulan = document.getElementById("swal-bulan").value;
      const tahun = document.getElementById("swal-tahun").value;
      const jenis_invoice = document.getElementById("swal-jenis_invoice").value;
      const noinvoice = document.getElementById("swal-noinvoice").value;
      const tglInvoice = document.getElementById("swal-tgl_invoice").value;
      const jml_peserta = document.getElementById("swal-jml_peserta").value;
      const jml_premi = document.getElementById("swal-jml_premi").value;
      const pic = document.getElementById("swal-pic").value;
      const linkFile = document.getElementById("swal-link_file").files[0];
      const linkPeserta = document.getElementById("swal-link_peserta").files[0];

      // Parse currency untuk jml_premi
      let jml_premi_formatted = jml_premi
        .replace(/[^\d,\.]/g, "")
        .replace(/\.(?=\d{3,})/g, "")
        .replace(",", ".");

      return {
        periode: periode,
        bulan: bulan,
        tahun: tahun,
        jenis_invoice: jenis_invoice,
        noinvoice: noinvoice,
        tgl_invoice: tglInvoice,
        jml_peserta: jml_peserta,
        jml_premi: jml_premi_formatted,
        pic: pic,
        linkFile: linkFile,
        linkPeserta: linkPeserta,
        idbatch: idbatch,
      };
    },
  }).then((result) => {
    if (result.isConfirmed && result.value) {
      const data = result.value;

      // Validasi input
      if (!data.periode) {
        Toast.fire({ icon: "error", title: "Periode wajib dipilih" });
        return;
      }
      if (!data.jenis_invoice) {
        Toast.fire({ icon: "error", title: "Jenis Invoice wajib diisi" });
        return;
      }
      if (!data.noinvoice) {
        Toast.fire({ icon: "error", title: "No. Invoice wajib diisi" });
        return;
      }
      if (!data.tgl_invoice) {
        Toast.fire({ icon: "error", title: "Tanggal Invoice wajib diisi" });
        return;
      }
      if (!data.jml_peserta) {
        Toast.fire({ icon: "error", title: "Jumlah Peserta wajib diisi" });
        return;
      }
      if (!data.jml_premi) {
        Toast.fire({ icon: "error", title: "Total Premi wajib diisi" });
        return;
      }
      if (!data.pic) {
        Toast.fire({ icon: "error", title: "PIC wajib diisi" });
        return;
      }

      // Create FormData for file upload support
      const formData = new FormData();
      formData.append("periode", data.periode);
      formData.append("bulan", data.bulan);
      formData.append("tahun", data.tahun);
      formData.append("jenis_invoice", data.jenis_invoice);
      formData.append("noinvoice", data.noinvoice);
      formData.append("tgl_invoice", data.tgl_invoice);
      formData.append("jml_peserta", data.jml_peserta);
      formData.append("jml_premi", data.jml_premi);
      formData.append("pic", data.pic);
      formData.append("idbatch", data.idbatch);
      console.log(data.idbatch);
      console.log(data);
      console.log(formData.get("idbatch"));

      // Append files if selected
      if (data.linkFile) {
        formData.append("link_file", data.linkFile);
      }
      if (data.linkPeserta) {
        formData.append("link_peserta", data.linkPeserta);
      }

      // Send to API via AJAX
      $.ajax({
        url: "api/add_invoice.php",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function (resp) {
          if (resp && resp.ok) {
            Toast.fire({
              icon: "success",
              title: "Invoice berhasil ditambahkan",
            });
            // Reload tabel via AJAX
            // table.ajax.reload();
          } else {
            console.log(formData);

            Toast.fire({
              icon: "error",
              title:
                "Gagal menambahkan invoice: " +
                (resp && resp.msg ? resp.msg : ""),
            });
          }
        },
        error: function (xhr) {
          Toast.fire({
            icon: "error",
            title: "Terjadi kesalahan saat menyimpan invoice",
          });
        },
      });
    }
  });
});
