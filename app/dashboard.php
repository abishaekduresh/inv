<?php
include_once 'header.php';
?>

<link href="./assets/css/dashboard.css" rel="stylesheet" />

<div class="container my-1 pb-1">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-semibold text-primary mb-0">
      <i class="fa fa-chart-bar me-2"></i>Dashboard Overview
    </h4>
    <div class="d-flex align-items-center gap-2">
      <select id="periodSelect" class="form-select form-select-sm">
        <option value="today" selected>Today</option>
        <option value="yesterday">Yesterday</option>
        <option value="daily">Daily</option>
        <option value="monthly">Monthly</option>
        <option value="yearly">Yearly</option>
      </select>

      <input type="date" id="fromDate" class="form-control form-control-sm" />
      <input type="date" id="toDate" class="form-control form-control-sm" />

      <button id="applyFilterBtn" class="btn btn-sm btn-primary">
        <i class="fa fa-filter me-1"></i> <!-- Apply -->
      </button>

      <button id="refreshDashboardBtn" class="btn btn-outline-primary btn-sm">
        <i class="fa fa-refresh me-1"></i>  <!--  Refresh  -->
      </button>
    </div>
  </div>

  <div class="row g-3 mb-4" id="dashboardCards">
    <!-- Total Invoices -->
    <div class="col-md-6 col-xl-3">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1">
              <h6 class="text-muted mb-1">Total Invoices</h6>
              <h4 class="fw-bold mb-0" id="totalInvoices">0</h4>
            </div>
            <div class="ms-3 text-primary fs-3">
              <i class="fa fa-file-invoice"></i>
            </div>
          </div>
          <small class="text-success">Today: <span id="todayInvoices">0</span></small>
        </div>
      </div>
    </div>

    <!-- Total Sales -->
    <div class="col-md-6 col-xl-3">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1">
              <h6 class="text-muted mb-1">Total Sales</h6>
              <h4 class="fw-bold mb-0" id="totalSales">0.00</h4>
            </div>
            <div class="ms-3 text-success fs-3">
              <i class="fa fa-rupee-sign"></i>
            </div>
          </div>
          <small class="text-success">Today: <span id="todaySales">0.00</span></small>
        </div>
      </div>
    </div>

    <!-- Total Business -->
    <div class="col-md-6 col-xl-3">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1">
              <h6 class="text-muted mb-1">Total Business</h6>
              <h4 class="fw-bold mb-0" id="totalBusiness">0</h4>
            </div>
            <div class="ms-3 text-warning fs-3">
              <i class="fa fa-building"></i>
            </div>
          </div>
          <small class="text-muted">Logs: <span id="totalLogs">0</span></small>
        </div>
      </div>
    </div>

    <!-- Yesterday Overview -->
    <div class="col-md-6 col-xl-3">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1">
              <h6 class="text-muted mb-1">Yesterday Sales</h6>
              <h4 class="fw-bold mb-0 text-primary" id="yesterdaySales">0.00</h4>
            </div>
            <div class="ms-3 text-danger fs-3">
              <i class="fa fa-chart-line"></i>
            </div>
          </div>
          <small class="text-muted">Yesterday Invoices: <span id="yesterdayInvoices">0</span></small>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== Stats Cards ===== -->
  <div class="row g-3 mb-4" id="dashboardCards">
    <!-- (Your same stat cards code here — unchanged) -->
  </div>

  <!-- ====== Business Analytics Charts ====== -->
  <div class="card border-0 shadow-sm mt-4 mb-4">
    <div class="card-header bg-white border-0">
      <h5 class="mb-0 text-primary fw-semibold">
        <i class="bi bi-graph-up-arrow me-2"></i>Business Analytics
      </h5>
      <small class="text-muted" id="chartPeriodLabel">Visual overview of sales and invoice performance</small>
    </div>

    <div class="card-body bg-light">
      <div class="row g-4">
        <div class="col-lg-6">
          <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
              <h6 class="fw-semibold text-secondary mb-3">
                <i class="bi bi-currency-rupee me-2"></i>Sales Overview
              </h6>
              <canvas id="salesChart" height="150"></canvas>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
              <h6 class="fw-semibold text-secondary mb-3">
                <i class="bi bi-receipt me-2"></i>Invoices Overview
              </h6>
              <canvas id="invoiceChart" height="150"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== Recent Invoices ===== -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0">
      <h5 class="mb-0 text-primary fw-semibold">
        <i class="fa fa-receipt me-2"></i>Recent Invoices
      </h5>
      <small class="text-muted">Last <span class="recentInvoicesCount">0</span> invoices</small>
    </div>
    <div class="card-body bg-light">
      <div class="row g-3" id="recentInvoicesContainer">
        <div class="col-12 text-center text-muted py-3">Loading recent invoices...</div>
      </div>
    </div>
  </div>
</div>

  <script src="<?= $base_url ?>assets/js/dashboard.js"></script>

<?php
  include_once 'footer.php';
?>
