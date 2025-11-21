document.addEventListener("DOMContentLoaded", function () {
  // Global variables
  let invoiceCurrentPage = 1;
  let invoicesData = []; // To store fetched invoices for export

  // ==== INIT ====
  initInvoicesPage();

  document.getElementById("resetFilterBtn").addEventListener("click", () => {
    [
      "invoiceTypeFilter",
      "invoiceStatusFilter",
      "invoiceReportFromDateInput",
      "invoiceReportToDateInput",
    ].forEach((id) => (document.getElementById(id).value = ""));

    invoiceCurrentPage = 1;
    fetchInvoices();
    // Show top toast notification
    Swal.fire({
      toast: true,
      position: "top-end",
      icon: "success",
      title: "Filters have been reset",
      showConfirmButton: false,
      timer: 2000,
      timerProgressBar: true,
    });
  });

  document
    .getElementById("searchInvoiceReportBtn")
    .addEventListener("click", () => {
      invoiceCurrentPage = 1;
      fetchInvoices();
    });

  // ==== Initialize Search and Filter ====
  function initInvoicesPage() {
    fetchInvoices(); // Initial load
  }

  // ==== Fetch invoices from API ====
  function fetchInvoices() {
    const fmd = document
      .getElementById("invoiceReportFromDateInput")
      .value.trim();
    const tod = document
      .getElementById("invoiceReportToDateInput")
      .value.trim();
    const invtype = document.getElementById("invoiceTypeFilter").value;
    const sts = document.getElementById("invoiceStatusFilter").value;

    const params = {
      fmd,
      tod,
      invtype,
      sts,
      page: invoiceCurrentPage,
    };

    apiRequest(
      "GET",
      "/api/invoices",
      params,
      false,
      (res) => {
        const invoices = res.data?.invoices || [];
        const totalRecords = res.pagination?.totalRecords || 0;
        invoicesData = invoices; // Store for export
        renderInvoicesTable(invoices, totalRecords);
      },
      (xhr) => {
        let message = "Error fetching invoice.";
        // Safely parse JSON response
        if (xhr.responseText) {
          try {
            const res = JSON.parse(xhr.responseText);
            if (res.message) message = res.message;
          } catch (err) {
            console.warn("Response is not valid JSON:", xhr.responseText);
            message = xhr.responseText;
          }
        }
        // Show proper icon type
        const icon = xhr.status >= 500 ? "error" : "warning";
        showToast(icon, message);
      }
    );
  }

  // ==== Render Invoice Table ====
  let invoiceTable = null;
  function renderInvoicesTable(invoices = [], totalRecords = 0) {
    const count = document.getElementById("totalCount");
    const empty = document.getElementById("emptyState");

    if (!count) return;
    count.textContent = `${totalRecords} invoices`;

    if (!invoices || invoices.length === 0) {
      empty?.classList.remove("d-none");
      return;
    } else {
      empty?.classList.add("d-none");
    }

    // Destroy old table if exists
    if (invoiceTable) {
      invoiceTable.destroy();
      invoiceTable = null;
    }

    // Ensure container exists
    const tableContainer = document.getElementById("invoicesTable");
    if (!tableContainer) {
      console.error("Missing #invoicesTable div!");
      showToast("warning", "Missing #invoicesTable div!");
      return;
    }

    // Initialize Tabulator on <div>, not <table>
    invoiceTable = new Tabulator("#invoicesTable", {
      data: invoices,
      layout: "fitColumns",
      pagination: "local",
      responsiveLayout: "collapse",
      placeholder: "No invoices found",
      paginationSize: 10, // default per page
      paginationSizeSelector: [10, 25, 50, 100], // dropdown for limit selection
      columnDefaults: {
        hozAlign: "left",
        headerHozAlign: "left",
      },
      columns: [
        {
          title: "#",
          formatter: function (cell) {
            const table = cell.getTable();
            // Get the current table data (respecting current filters & sorting)
            const allData = table.getData();
            const rowData = cell.getRow().getData();

            // Use unique key to find position (faster and safer than deep-equal)
            const idx = allData.findIndex(
              (d) => d.invoiceId === rowData.invoiceId
            );

            // If not found (defensive), fallback to 0
            return idx >= 0 ? idx + 1 : "";
          },
          width: 60,
          hozAlign: "center",
        },
        { title: "Inv ID", field: "invoiceId", width: 150 },
        {
          title: "Inv #",
          field: "invoiceNumber",
          width: 90,
        },
        {
          title: "Date",
          field: "invoiceDate",
          width: 110,

          formatter: (cell) => formatTimestamp(cell.getValue(), "DD-MM-YYYY"),
        },
        { title: "Type", field: "invoiceType", width: 150 },
        {
          title: "Name",
          field: "name",
          width: 220,

          formatter: (cell) => StringUtils.toTitleCase(cell.getValue() || "-"),
        },
        { title: "Phone", field: "phone", width: 150 },
        {
          title: "Place",
          field: "place",
          width: 200,

          formatter: (cell) => StringUtils.toTitleCase(cell.getValue() || "-"),
        },
        {
          title: "Remark",
          field: "remark",
          width: 200,

          formatter: (cell) => StringUtils.toTitleCase(cell.getValue() || "-"),
        },
        {
          title: "Amount",
          field: "amount",
          width: 100,
        },
        {
          title: "Pay Mode",
          field: "paymentMode",
          width: 100,
          formatter: (cell) => {
            const value = cell.getValue();
            return value ? String(value).toUpperCase() : "-";
          },
        },
        {
          title: "Status",
          field: "invoiceStatus",
          width: 100,

          formatter: (cell) => {
            const status = cell.getValue() || "unknown";
            const color = status === "active" ? "bg-success" : "bg-secondary";
            return `<span class="badge ${color}">${StringUtils.capitalize(
              status
            )}</span>`;
          },
        },
      ],
    });
  }
});
