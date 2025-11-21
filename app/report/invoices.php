<?php
include_once '../header.php';
?>

<link href="../assets/css/report/invoices.css" rel="stylesheet" />

    <div class="container-fluid mb-3">

        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-primary text-white shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <div>
                                <h1 class="h3 mb-1 fw-semibold">
                                    <i class="bi bi-receipt"></i> Invoices Report
                                </h1>
                                <!-- <p class="text-white-50 mb-0">Report Optical Invoices</p> -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body pb-2">
                <form id="invoiceReportFilterForm" class="row g-3" autocomplete="off">

                    <div class="col-md-2">
                        <label class="form-label fw-semibold">From Date</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-calendar-event"></i></span>
                            <input type="date" class="form-control" id="invoiceReportFromDateInput" >
                        </div>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-semibold">To Date</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-calendar-event"></i></span>
                            <input type="date" class="form-control" id="invoiceReportToDateInput" >
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Invoice Type</label>
                        <select class="form-select" id="invoiceTypeFilter">
                            <option value="">All Types</option>
                            <optgroup label="Optical Products">
                                <option value="spectacles">Spectacles</option>
                                <option value="frames">Frames Only</option>
                                <option value="lenses">Lenses Only</option>
                                <option value="contact lenses">Contact Lenses</option>
                                <option value="sunglasses">Sunglasses</option>
                                <option value="accessories">Accessories</option>
                            </optgroup>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="col-md-1">
                        <label class="form-label fw-semibold">Status</label>
                        <select class="form-select" id="invoiceStatusFilter">
                            <option value="">All</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="col-md-1 d-grid align-self-end">
                        <button type="button" id="searchInvoiceReportBtn" class="btn btn-primary">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>

                    <div class="col-md-1 d-grid align-self-end">
                        <button type="button" id="resetFilterBtn" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Invoices Table -->
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 fw-semibold">
                    <i class="bi bi-list-ul me-2"></i>Invoices Report
                    </h5>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary" id="totalCount">0 invoices</span>
                    <!-- <button id="exportCsvBtn" class="btn btn-sm btn-success">
                    <i class="bi bi-filetype-csv me-1"></i> CSV
                    </button> -->
                    <!-- <button id="exportExcelBtn" class="btn btn-sm btn-primary">
                    <i class="bi bi-file-earmark-excel me-1"></i> Excel
                    </button> -->
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <div id="invoicesTable" class="table table-hover mb-0 align-middle"></div>
                </div>
            </div>

        </div>

        <nav aria-label="Invoice pagination" class="mt-3">
            <ul class="pagination justify-content-center" id="pagination"></ul>
        </nav>
    </div>

</div>

  <script src="../assets/js/report/invoices.js"></script>

<?php
  include_once '../footer.php';
?>
