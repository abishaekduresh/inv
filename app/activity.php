<?php
include_once 'header.php';
?>

  <style>
    /* Small niceties */
    body { background: #f8f9fa; }
    .card { border-radius: .75rem; }
    .table-fixed thead th { position: sticky; top: 0; background: #fff; z-index: 2; }
    .small-muted { font-size: .85rem; color: #6c757d; }
    .no-data { height: 180px; display: flex; align-items: center; justify-content: center; color: #6c757d; }
    .spinner-center { display:flex; align-items:center; justify-content:center; height:120px; }
  </style>
</head>
<body>
  <div class="container py-4">
    <div class="d-flex align-items-center mb-3">
      <h3 class="me-auto mb-0"><i class="fa fa-list-check text-primary me-2"></i> Activity Logs</h3>
      <div>
        <button id="refreshBtn" class="btn btn-outline-primary btn-sm me-2">
          <i class="fa fa-sync"></i> Refresh
        </button>
        <!-- <button id="downloadJsonBtn" class="btn btn-outline-secondary btn-sm">
          <i class="fa fa-download"></i> Export JSON
        </button> -->
      </div>
    </div>

    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <input id="searchInput" type="search" class="form-control" placeholder="Search by action, endpoint, IP, user id" readonly/>
          </div>
          <div class="col-md-2">
            <select id="limitSelect" class="form-select">
              <option value="10">Limit: 10</option>
              <option value="25" selected>Limit: 25</option>
              <option value="50">Limit: 50</option>
            </select>
          </div>
          <div class="col-md-2">
            <select id="orderSelect" class="form-select">
              <option value="DESC" selected>Newest</option>
              <option value="ASC">Oldest</option>
            </select>
          </div>
          <div class="col-md-4 text-end">
            <span class="small-muted">Showing <span id="showingInfo">0</span> of <strong id="totalRecords">0</strong> logs</span>
          </div>
        </div>

        <div id="tableWrapper" class="table-responsive">
          <table class="table table-hover table-fixed mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 60px;">#</th>
                <th>User ID</th>
                <th>Business ID</th>
                <th>Action</th>
                <th>IP Address</th>
                <th style="width: 180px;">When</th>
              </tr>
            </thead>
            <tbody id="logsTbody">
              <tr><td colspan="6" class="no-data">Loading logs...</td></tr>
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">
          <div>
            <nav>
              <ul id="pagination" class="pagination mb-0"></ul>
            </nav>
          </div>
          <div class="small-muted">Page <span id="currentPage">0</span> · <span id="totalPages">0</span></div>
        </div>
      </div>
    </div>
  </div>

  <script src="<?= $base_url ?>assets/js/activity.js"></script>

<?php
  include_once 'footer.php';
?>
