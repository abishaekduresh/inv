<?php
  require_once 'common.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Invoice Management - Dashboard</title>

  <link href="./assets/lib/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
  <link href="./assets/lib/sweetalert2/sweetalert2.min.css" rel="stylesheet" />
  <link href="./assets/lib/tabulator/dist/css/tabulator.min.css" rel="stylesheet"/>
  <!-- Select2 CDN -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" >
  <!-- Font Awesome CDN -->
  <link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
    rel="stylesheet"
  />
  <link href="./assets/css/common.css" rel="stylesheet"/>
  <link href="./assets/css/header.css" rel="stylesheet"/>
</head>

<body>
  <!-- Navbar -->
  <nav class="navbar fixed-top shadow-sm">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <button class="navbar-toggler d-lg-none" type="button"
        data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
        <i class="fa-solid fa-bars"></i>
      </button>
      <a class="navbar-brand text-white" href="#">Invoice Management</a>
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
        <a href="dashboard" class="nav-link"><i class="fa-solid fa-gauge"></i> Dashboard</a>
        <a href="invoices" class="nav-link"><i class="fa-solid fa-file-invoice"></i> Invoices</a>
        <a href="users" class="nav-link"><i class="fa-solid fa-user-group"></i> Users</a>
        <a href="payments" class="nav-link"><i class="fa-solid fa-credit-card"></i> Payments</a>

        <a href="#" class="nav-link dropdown-toggle"><i class="fa-solid fa-chart-line"></i> Reports</a>
        <div class="submenu">
          <a href="daily-report">Daily Report</a>
          <a href="monthly-report">Monthly Report</a>
          <a href="yearly-report">Yearly Report</a>
        </div>
      </nav>
    </div>

    <!-- Content -->
    <div class="content">
