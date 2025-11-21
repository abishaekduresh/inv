<?php
// === Configuration ===
$base_url = '/app/';        // browser base URL (http://localhost/app/)
$base_path = __DIR__ . '/'; // filesystem base path

// === Common Includes ===
require_once $base_path . 'common.php';
$APP_NAME = AppConfig::APP_NAME ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $APP_NAME ?? null ?> - Dashboard</title>
  <link rel="icon" href="<?= $base_url ?>assets/img/favicon.png" type="image/png">
  <!-- Apple/Android devices -->
  <link rel="apple-touch-icon" href="<?= $base_url ?>assets/img/favicon.png">
  <link rel="shortcut icon" href="<?= $base_url ?>assets/img/favicon.png" type="image/png">


  <!-- CSS Libraries -->
  <link href="<?= $base_url ?>assets/lib/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
  <link href="<?= $base_url ?>assets/lib/sweetalert2/sweetalert2.min.css" rel="stylesheet" />

  <!-- Select2 -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

  <!-- Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet" />

  <link href="https://cdn.jsdelivr.net/npm/tabulator-tables@6.2.0/dist/css/tabulator_bootstrap5.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/tabulator-tables@6.2.0/dist/js/tabulator.min.js"></script>

  <!-- App CSS -->
  <link href="<?= $base_url ?>assets/css/common.css" rel="stylesheet" />
  <link href="<?= $base_url ?>assets/css/header.css" rel="stylesheet" />
  <!-- Papa Parse -->
  <script src="<?= $base_url ?>assets/lib/papaparse/papaparse.min.js"></script>
  <!-- JS -->
  <script src="<?= $base_url ?>assets/lib/jquery/jquery.min.js"></script>
  <script src="<?= $base_url ?>assets/lib/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="<?= $base_url ?>assets/lib/sweetalert2/sweetalert2.all.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="<?= $base_url ?>assets/js/common.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body>
  <!-- Navbar -->
  <nav class="navbar fixed-top shadow-sm bg-primary">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      
      <!-- Sidebar toggle button -->
      <button class="navbar-toggler d-lg-none" type="button"
        data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
        <i class="fa-solid fa-bars text-white"></i>
      </button>

      <!-- Brand with logo -->
      <a class="navbar-brand text-white d-flex align-items-center" href="<?= $base_url ?>#">
        <img src="<?= $base_url ?>assets/img/favicon.png" alt="Logo" 
            width="28" height="28" class="me-2">
        <span><?= $APP_NAME ?? null ?></span>
      </a>

      <!-- Logout button -->
      <button id="logoutBtn" class="btn btn-outline-light btn-sm">
        <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
      </button>
    </div>
  </nav>

  <!-- Main Layout -->
  <div class="main-wrapper" style="margin-top: 56px;">
    <!-- Sidebar -->
    <div class="sidebar d-none d-lg-flex flex-column">
      <h6>Main Menu</h6>
      <nav class="nav flex-column">
        <a href="<?= $base_url ?>dashboard" class="nav-link"><i class="fa-solid fa-gauge"></i> Dashboard</a>
        <a href="<?= $base_url ?>invoices" class="nav-link"><i class="fa-solid fa-file-invoice"></i> Invoices</a>

        <a href="<?= $base_url ?>#" class="nav-link dropdown-toggle"><i class="fa-solid fa-chart-line"></i> Reports</a>
        <div class="submenu">
          <a href="<?= $base_url ?>report/invoices">Invoices</a>
          <!-- <a href="<?= $base_url ?>monthly-report">Monthly Report</a>
          <a href="<?= $base_url ?>yearly-report">Yearly Report</a> -->
        </div>
        <a href="<?= $base_url ?>users" class="nav-link"><i class="fa-solid fa-user-group"></i> Users</a>
        <a href="<?= $base_url ?>settings" class="nav-link"><i class="bi bi-gear"></i> Settings</a>
        <a href="<?= $base_url ?>activity" class="nav-link"><i class="fa fa-list-check"></i> Activity</a>
      </nav>
    </div>

    <!-- ====== Offcanvas Sidebar for Mobile ====== -->
    <div class="offcanvas offcanvas-start text-bg-dark" tabindex="-1" id="offcanvasSidebar">
        <div class="offcanvas-header border-bottom d-flex align-items-center justify-content-between px-3 py-2">
            <h5 class="offcanvas-title fw-semibold mb-0" style="font-size: 1rem; letter-spacing: 0.5px;">
            Main Menu
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        <div class="offcanvas-body d-flex flex-column justify-content-start p-3" style="gap: 8px;">
            <nav class="nav flex-column">
            <a href="<?= $base_url ?>dashboard" class="nav-link text-white py-2 px-2 d-flex align-items-center gap-2">
                <i class="fa-solid fa-gauge"></i><span>Dashboard</span>
            </a>
            <a href="<?= $base_url ?>invoices" class="nav-link text-white py-2 px-2 d-flex align-items-center gap-2">
                <i class="fa-solid fa-file-invoice"></i><span>Invoices</span>
            </a>
            <a href="<?= $base_url ?>users" class="nav-link text-white py-2 px-2 d-flex align-items-center gap-2">
                <i class="fa-solid fa-user-group"></i> <span>Users</span>
            </a>

            <!-- Reports dropdown -->
            <a href="<?= $base_url ?>#" class="nav-link dropdown-toggle text-white py-2 px-2 d-flex align-items-center gap-2">
                <i class="fa-solid fa-chart-line"></i><span>Reports</span>
            </a>

            <div class="submenu ps-4" style="margin-top: -4px;">
                <a href="<?= $base_url ?>report/invoices" class="text-white-50 py-1 d-block">Invoices</a>
                <!-- <a href="<?= $base_url ?>monthly-report" class="text-white-50 py-1 d-block">Monthly Report</a>
                <a href="<?= $base_url ?>yearly-report" class="text-white-50 py-1 d-block">Yearly Report</a> -->
            </div>
            <a href="<?= $base_url ?>settings" class="nav-link text-white py-2 px-2 d-flex align-items-center gap-2">
                <i class="bi bi-gear"></i> <span>Settings</span>
            </a>
            <a href="<?= $base_url ?>activity" class="nav-link text-white py-2 px-2 d-flex align-items-center gap-2">
                <i class="fa fa-list-check"></i> <span>Activity</span>
            </a>
            </nav>
        </div>
    </div>

    <!-- Content -->
    <div class="content">
