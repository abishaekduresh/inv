$(document).ready(function () {
  const DASHBOARD_KEY = "dashboardStats";

  // Render invoices (Bootstrap cards)
  function renderRecentInvoices(invoices) {
    const container = $("#recentInvoicesContainer");
    $(".recentInvoicesCount").text(invoices.length);

    container.empty();

    if (!invoices || !invoices.length) {
      container.html(
        `<div class="col-12 text-center text-muted py-3">No recent invoices found.</div>`
      );
      return;
    }

    invoices.forEach((inv) => {
      const card = `
        <div class="col-md-6 col-lg-4 mb-3">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column justify-content-between">
              <div>
                <div class="d-flex justify-content-between mb-2">
                  <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold">
                    #${inv.invoice_number}
                  </span>
                  <small class="text-muted">
                    <i class="fa fa-calendar me-1"></i>${inv.invoice_date}
                  </small>
                </div>
                <h6 class="fw-semibold text-dark mb-1">
                  <i class="fa fa-user me-2 text-secondary"></i>${inv.name}
                </h6>
                <h5 class="fw-bold text-success mb-0">₹${parseFloat(
                  inv.amount
                ).toLocaleString()}</h5>
              </div>
            </div>
          </div>
        </div>`;
      container.append(card);
    });
  }

  // Render dashboard stats
  function renderDashboard(data) {
    if (!data) return;

    $("#totalInvoices").text(data.totalInvoices || 0);
    $("#todayInvoices").text(data.todayInvoices || 0);
    $("#yesterdayInvoices").text(data.yesterdayInvoices || 0);
    $("#totalBusiness").text(data.totalBusiness || 0);
    $("#totalSales").text(data.totalSales || "0.00");
    $("#todaySales").text(data.todaySales || "0.00");
    $("#yesterdaySales").text(data.yesterdaySales || "0.00");
    $("#totalLogs").text(data.totalLogs || 0);

    renderRecentInvoices(data.recentInvoices || []);
  }

  // ===== Render Charts =====
  function renderCharts(data) {
    if (!data || !data.last7Days) return;

    const labels = data.last7Days.labels || [];
    const sales = data.last7Days.sales || [];
    const invoices = data.last7Days.invoices || [];

    if (window.salesChartInstance) window.salesChartInstance.destroy();
    if (window.invoiceChartInstance) window.invoiceChartInstance.destroy();

    const salesCtx = document.getElementById("salesChart");
    const invoiceCtx = document.getElementById("invoiceChart");

    // === Sales Chart (Bar) ===
    if (salesCtx) {
      window.salesChartInstance = new Chart(salesCtx, {
        type: "bar",
        data: {
          labels,
          datasets: [
            {
              label: "Sales (₹)",
              data: sales,
              backgroundColor: "rgba(13, 110, 253, 0.7)",
              borderRadius: 6,
            },
          ],
        },
        options: {
          responsive: true,
          scales: {
            y: { beginAtZero: true },
          },
          plugins: { legend: { display: false } },
        },
      });
    }

    // === Invoice Chart (Line) ===
    if (invoiceCtx) {
      window.invoiceChartInstance = new Chart(invoiceCtx, {
        type: "line",
        data: {
          labels,
          datasets: [
            {
              label: "Invoices",
              data: invoices,
              fill: true,
              borderColor: "#198754",
              backgroundColor: "rgba(25, 135, 84, 0.1)",
              tension: 0.4,
              pointBackgroundColor: "#198754",
              pointRadius: 4,
            },
          ],
        },
        options: {
          responsive: true,
          scales: {
            y: { beginAtZero: true },
          },
          plugins: { legend: { display: false } },
        },
      });
    }
  }

  // Fetch dashboard data from API
  function fetchDashboardData(showToast = true) {
    apiRequest(
      "GET",
      "/api/business/stats",
      null,
      false,
      function (res) {
        if (
          res.status &&
          Array.isArray(res.data.records) &&
          res.data.records.length
        ) {
          const data = res.data.records[0]; // ✅ pick first record
          sessionStorage.setItem(DASHBOARD_KEY, JSON.stringify(data));

          if (showToast) {
            Swal.fire({
              toast: true,
              icon: "success",
              text: "Dashboard updated",
              position: "top-end",
              showConfirmButton: false,
              timer: 1500,
              timerProgressBar: true,
            });
          }

          renderDashboard(data);
          renderCharts(data);
        } else {
          Swal.fire({
            toast: true,
            position: "top-end",
            icon: "error",
            title: res.message || "Failed to fetch dashboard data",
            showConfirmButton: false,
            timer: 1500,
            timerProgressBar: true,
          });
        }
      },
      function () {
        Swal.fire({
          toast: true,
          position: "top-end",
          icon: "error",
          title: "Error fetching dashboard data",
          showConfirmButton: false,
          timer: 1500,
          timerProgressBar: true,
        });
      }
    );
  }

  // Load from sessionStorage if available
  const cached = sessionStorage.getItem(DASHBOARD_KEY);
  if (cached) {
    const cachedData = JSON.parse(cached);
    renderDashboard(cachedData);
    renderCharts(cachedData);
  } else {
    fetchDashboardData(false);
  }

  // Refresh button
  $("#refreshDashboardBtn").on("click", function () {
    const $btn = $(this);
    $btn
      .prop("disabled", true)
      .html('<i class="fa fa-spinner fa-spin"></i> Refreshing...');
    fetchDashboardData();
    setTimeout(() => {
      $btn
        .prop("disabled", false)
        .html('<i class="fa fa-refresh"></i> Refresh');
    }, 2000);
  });
});
