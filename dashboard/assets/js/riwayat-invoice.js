$(document).ready(function () {
  // Initialize DataTable with AJAX source
  var periodeSelect = $("#filter-periode");

  var ajaxUrl = function () {
    var p = periodeSelect.val();
    return (
      "api/get_invoice_approval_list.php" +
      (p ? "?periode=" + encodeURIComponent(p) : "")
    );
  };

  var table = $("#invoice-approval-table").DataTable({
    dom: "frtip",
    pageLength: 25,
    responsive: true,
    ajax: {
      url: ajaxUrl(),
      dataSrc: function (data) {
        if (!data.ok) {
          Toast.fire({ icon: "error", title: data.msg || "Gagal memuat data" });
          return [];
        }
        return data.data || [];
      },
    },
    columns: [
      { data: "no", className: "text-center align-middle" },
      { data: "periode", className: "text-center align-middle" },
      {
        data: "jenis_premi",
        className: "text-center align-middle",
        render: function (data, type, row) {
          switch (data) {
            case "1":
              return "JHT Regular";
            case "2":
              return "JHT Topup";
            case "3":
              return "PKP Regular";
          }
        },
      },
      { data: "no_invoice", className: "text-center align-middle" },
      { data: "tgl_invoice", className: "text-center align-middle" },
      // {
      //   data: "jml_premi_krywn",
      //   className: "text-right align-middle",
      //   render: function (data, type, row) {
      //     if (!data) return "-";
      //     if (type === "sort" || type === "order") return data.sort || 0;
      //     return data.display || "-";
      //   },
      // },
      { data: "jumlah_peserta", className: "text-center align-middle" },
      {
        data: "total_premi",
        className: "text-right align-middle font-semibold",
        render: function (data, type, row) {
          if (!data) return "-";
          if (type === "sort" || type === "order") return data.sort || 0;
          return data.display || "-";
        },
      },
      { data: "pic", className: "text-center align-middle" },
      {
        data: "status",
        className: "text-center align-middle",
        orderable: false,
      },
      {
        data: "actions",
        className: "text-center align-middle",
        orderable: false,
      },
    ],
    drawCallback: function () {
      // Re-bind buttons after each draw
    },
  });

  // Reload table when periode filter changes
  periodeSelect.on("change", function () {
    table.ajax.url(ajaxUrl()).load();
  });

  // SweetAlert2 Toast configuration
  const Toast = Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 3500,
    timerProgressBar: true,
    didOpen: (toast) => {
      toast.addEventListener("mouseenter", Swal.stopTimer);
      toast.addEventListener("mouseleave", Swal.resumeTimer);
    },
  });

  // Handle Approve button
  $(document).on("click", ".btn-approve", function (e) {
    e.preventDefault();
    var invoiceId = $(this).data("id");

    Swal.fire({
      title: "Approve Invoice?",
      text: "Apakah Anda yakin ingin meng-approve invoice ini?",
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Ya, Approve",
      cancelButtonText: "Batal",
    }).then(function (result) {
      if (result.isConfirmed) {
        $.ajax({
          url: "api/approve_invoice.php",
          type: "POST",
          dataType: "json",
          data: {
            id: invoiceId,
            action: "approve",
          },
          success: function (resp) {
            console.log("Approve response:", resp);
            if (resp && resp.ok) {
              Toast.fire({
                icon: "success",
                title: "Invoice berhasil di-approve",
              });
              table.ajax.reload();
            } else {
              Toast.fire({
                icon: "error",
                title: resp.msg || "Gagal meng-approve invoice",
              });
            }
          },
          error: function (xhr, status, error) {
            console.error("Approve error:", error, xhr.responseText);
            Toast.fire({
              icon: "error",
              title: "Terjadi kesalahan saat meng-approve invoice",
            });
          },
        });
      }
    });
  });

  // Handle Reject button
  $(document).on("click", ".btn-reject", function (e) {
    e.preventDefault();
    var invoiceId = $(this).data("id");

    Swal.fire({
      title: "Reject Invoice?",
      text: "Apakah Anda yakin ingin menolak invoice ini?",
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Ya, Reject",
      cancelButtonText: "Batal",
    }).then(function (result) {
      if (result.isConfirmed) {
        $.ajax({
          url: "api/approve_invoice.php",
          type: "POST",
          dataType: "json",
          data: {
            id: invoiceId,
            action: "reject",
          },
          success: function (resp) {
            console.log("Reject response:", resp);
            if (resp && resp.ok) {
              Toast.fire({
                icon: "success",
                title: "Invoice berhasil di-reject",
              });
              table.ajax.reload();
            } else {
              Toast.fire({
                icon: "error",
                title: resp.msg || "Gagal reject invoice",
              });
            }
          },
          error: function (xhr, status, error) {
            console.error("Reject error:", error, xhr.responseText);
            Toast.fire({
              icon: "error",
              title: "Terjadi kesalahan saat reject invoice",
            });
          },
        });
      }
    });
  });

  // Handle Download button
  $(document).on("click", ".btn-download", function (e) {
    e.preventDefault();
    var url = $(this).attr("href");

    // Probe availability first
    var probeUrl = url + (url.indexOf("?") === -1 ? "?ajax=1" : "&ajax=1");
    $.get(probeUrl)
      .done(function (resp) {
        if (resp && resp.ok) {
          window.open(url, "_blank");
        } else {
          Toast.fire({
            icon: "error",
            title: resp && resp.msg ? resp.msg : "File tidak tersedia",
          });
        }
      })
      .fail(function () {
        Toast.fire({ icon: "error", title: "Gagal memeriksa file" });
      });
  });
});
