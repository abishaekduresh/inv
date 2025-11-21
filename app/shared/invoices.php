<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Shared Invoice</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- SweetAlert2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

  <style>
    body {
      background-color: #f5f6fa;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .invoice-card {
      max-width: 900px;
      margin: 2rem auto;
      border-radius: 1rem;
      box-shadow: 0 5px 20px rgba(0,0,0,0.1);
      min-height: 500px;
      display: flex;
      flex-direction: column;
      transition: transform 0.2s;
      background-color: #fff;
    }

    .invoice-card:hover {
      transform: translateY(-5px);
    }

    .business-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem 2rem;
      border-bottom: 1px solid #ddd;
      background-color: #f1f1f1;
      border-radius: 1rem 1rem 0 0;
    }

    .business-logo img {
      max-height: 60px;
      object-fit: contain;
    }

    .business-details {
      text-align: right;
    }

    .invoice-header {
      background: linear-gradient(90deg, #4e54c8, #8f94fb);
      color: #fff;
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-radius: 0 0 0.5rem 0.5rem;
      margin-top: 1rem;
    }

    .badge-type {
      background-color: rgba(255,255,255,0.3);
      font-weight: 600;
      padding: 0.25rem 0.5rem;
      border-radius: 0.5rem;
    }

    .card-body {
      padding: 2rem;
      flex-grow: 1;
    }

    .section-title {
      font-weight: 600;
      margin-bottom: 1rem;
      color: #4e54c8;
      border-bottom: 1px solid #ddd;
      padding-bottom: 0.25rem;
    }

    .info-label {
      font-weight: 600;
    }

    .power-table td, .power-table th {
      border: 1px solid #ddd;
      padding: 0.4rem 0.6rem;
      text-align: center;
    }

    .card-footer {
      padding: 1rem 2rem;
      background-color: #f1f1f1;
      border-radius: 0 0 1rem 1rem;
    }

    @media (max-width: 767px) {
      .info-row > div {
        margin-bottom: 0.5rem;
      }
      .business-details {
        text-align: left;
        margin-top: 0.5rem;
      }
      .invoice-card {
        margin-left: 1rem;
        margin-right: 1rem;
      }
    }
  </style>
</head>
<body>

  <div id="invoiceContainer"></div>

  <!-- JS Libraries -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>

  <!-- Local Scripts -->
  <script src="../assets/js/common.js"></script>
  <script src="../assets/js/shared/invoices.js"></script>

</body>
</html>
