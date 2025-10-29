<?php
include_once 'header.php';
?>

<link href="./assets/css/invoices.css" rel="stylesheet" />


    <div class="container-fluid mb-3">

        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-primary text-white shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <div>
                                <h1 class="h3 mb-1 fw-semibold">
                                    <i class="bi bi-people-fill me-2"></i> Manage Invoices
                                </h1>
                                <p class="text-white-50 mb-0">View, Create, and Manage Optical Invoices</p>
                            </div>
                            <button class="btn btn-light fw-semibold px-4 mt-3 mt-md-0" data-bs-toggle="modal" data-bs-target="#createInvoiceStaticBackdropModal">
                                <i class="bi bi-plus-circle me-2"></i> Add New Invoice
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body pb-2">
                <form id="invoiceFilterForm" class="row g-3">

                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Invoice ID</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-receipt"></i></span>
                            <input type="text" class="form-control" id="invoiceIdInput" placeholder="Search by ID">
                        </div>
                    </div>

                    <div class="col-md-1 d-grid align-self-end">
                        <button type="button" id="searchInvBtn" class="btn btn-primary">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Customer / Phone</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-person-vcard"></i></span>
                            <input type="text" class="form-control" id="searchInput" placeholder="Search...">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Invoice Number</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-hash"></i></span>
                            <input type="number" class="form-control" id="invoiceNumberInput" placeholder="Search by number">
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
                        <button type="button" id="resetFilterBtn" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Invoices Table -->
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold"><i class="bi bi-list-ul me-2"></i>Invoices List</h5>
                <span class="badge bg-secondary" id="totalCount">0 invoices</span>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Inv ID</th>
                                <th>Inv No.</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Place</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="invoicesTableBody"></tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="d-md-none p-2" id="invoicesCardsContainer"></div>

                <!-- Empty State -->
                <div class="text-center py-5 d-none" id="emptyState">
                    <i class="bi bi-file-earmark-x display-4 text-muted"></i>
                    <h4 class="text-muted mt-2">No Invoices Found</h4>
                    <p class="text-muted">Try adjusting your search criteria</p>
                </div>
            </div>
        </div>

        <nav aria-label="Invoice pagination" class="mt-3">
            <ul class="pagination justify-content-center" id="pagination"></ul>
        </nav>
    </div>

    <!-- Optical Picker -->
    <div id="optical-picker" 
        style="
            display:none; 
            position:absolute; 
            background:#fff; 
            border:1px solid #ddd; 
            padding:10px; 
            z-index:2000; 
            max-width:200px;   /* Optional: set max width */
            max-height:300px;  /* Control height */
            overflow-y:auto;   /* Enable vertical scroll */
            box-shadow:0 2px 10px rgba(0,0,0,0.2); 
            border-radius:0.25rem;
        ">
        <div id="optical-grid" class="d-flex flex-column gap-1"></div>
    </div>

    <!-- Create New Invoice Modal -->
    <div class="modal fade" 
        id="createInvoiceStaticBackdropModal" 
        data-bs-backdrop="static" 
        data-bs-keyboard="false" 
        tabindex="-1" 
        aria-labelledby="createInvoiceStaticBackdropModalLabel" 
        aria-hidden="true">

        <!-- Fullscreen modal -->
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content shadow-lg rounded-4">

            <!-- Modal Header -->
            <div class="modal-header bg-primary text-white shadow-sm">
                <h5 class="modal-title" id="createInvoiceStaticBackdropModalLabel">Create New Invoice</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 bg-light">
                <div class="container-fluid">
                <form id="createInvoiceForm">
                    
                    <!-- Centered Search Input (inside Modal Body) -->
                    <div class="d-flex justify-content-center mb-4">
                        <div class="col-md-4 col-sm-8 col-10 position-relative">

                            <div class="mb-3">
                                <label for="createInvoiceSearchInput" class="form-label fw-semibold">
                                    Search Customer
                                </label>
                                <select 
                                    id="createInvoiceSearchInput" 
                                    class="form-select" 
                                    style="width: 100%;" 
                                    aria-label="Search Customer"
                                >
                                    <!-- Options will be loaded dynamically via Select2 -->
                                </select>
                            </div>

                        </div>
                    </div>

                    <!-- Top Row: Invoice Details -->
                    <div class="row g-3 mb-3 align-items-end">
                    <!-- Invoice Type -->
                    <div class="col-md-3">
                        <label for="createInvoiceType" class="form-label">
                        Invoice Type <span class="text-danger">*</span>
                        </label>
                        <select class="form-select shadow-sm" id="createInvoiceType" name="createInvoiceType" required>
                            <option value="" disabled selected>Select invoice type</option>
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

                    <!-- Invoice Date -->
                    <div class="col-md-2">
                        <label for="createInvoiceDate" class="form-label">Invoice Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control shadow-sm" id="createInvoiceDate" name="createInvoiceDate" value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <!-- Invoice Number -->
                    <div class="col-md-2">
                        <label for="createInvoiceNumber" class="form-label">Invoice No. <span class="text-danger">*</span></label>
                        <input type="number" class="form-control shadow-sm" id="createInvoiceNumber" name="createInvoiceNumber" placeholder="Invoice #" required>
                    </div>

                    <!-- Customer Name -->
                    <div class="col-md-3">
                        <label for="createInvoiceName" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control shadow-sm" id="createInvoiceName" name="createInvoiceName" placeholder="Enter full name" required>
                    </div>

                    <!-- Phone -->
                    <div class="col-md-2">
                        <label for="createInvoicePhone" class="form-label">Phone <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control shadow-sm" id="createInvoicePhone" name="createInvoicePhone" placeholder="Enter phone" pattern="[0-9]{10}" required>
                    </div>
                    </div>

                    <!-- Customer Location & Age -->
                    <div class="row g-3 mb-3 align-items-end">
                    <div class="col-md-3">
                        <label for="createInvoicePlace" class="form-label">Place</label>
                        <input type="text" class="form-control shadow-sm" id="createInvoicePlace" name="createInvoicePlace" placeholder="Enter place">
                    </div>

                    <div class="col-md-2">
                        <label for="createInvoiceDob" class="form-label">DoB</label>
                        <input type="date" class="form-control shadow-sm" id="createInvoiceDob" name="createInvoiceDob" onchange="getAge(null, 'createInvoiceDob', 'createInvoiceAge')" />
                    </div>

                    <div class="col-md-1">
                        <label for="createInvoiceAge" class="form-label">Age</label>
                        <input type="number" class="form-control shadow-sm" id="createInvoiceAge" name="createInvoiceAge" readonly>
                    </div>

                    <div class="col-md-3">
                        <label for="createInvoiceFrame" class="form-label">Frame</label>
                        <input type="text" class="form-control shadow-sm" id="createInvoiceFrame" name="createInvoiceFrame" placeholder="Enter frame">
                    </div>

                    <div class="col-md-3">
                        <label for="createInvoiceLense" class="form-label">Lense</label>
                        <input type="text" class="form-control shadow-sm" id="createInvoiceLense" name="createInvoiceLense" placeholder="Enter lense">
                    </div>
                    </div>

                    <!-- Offer & Claim -->
                    <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="createInvoiceOffer" class="form-label">Offer</label>
                        <input type="text" class="form-control shadow-sm" id="createInvoiceOffer" name="createInvoiceOffer" placeholder="Enter offer">
                    </div>
                    <div class="col-md-6">
                        <label for="createInvoiceClaim" class="form-label">Claim</label>
                        <input type="text" class="form-control shadow-sm" id="createInvoiceClaim" name="createInvoiceClaim" placeholder="Enter claim">
                    </div>
                    </div>

                    <!-- Prescription Table -->
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label"><i class="fa-solid fa-eye"></i> Prescription (Right & Left Eye)</label>
                            <div class="table-responsive shadow-sm">
                            <table class="table table-bordered text-center">
                                <thead class="table-light">
                                <tr>
                                    <th></th>
                                    <th>SPH</th>
                                    <th>CYL</th>
                                    <th>AXIS</th>
                                    <th>VIA</th>
                                    <th>ADD</th>
                                    <th>P.D.</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <th>R.E.</th>
                                    <td><input type="text" class="form-control min-w-80 power-input" id="createInvoiceRSph" name="createInvoiceRSph" data-type="sph" readonly></td>
                                    <td><input type="text" class="form-control min-w-80 power-input" id="createInvoiceRCyl" name="createInvoiceRCyl" data-type="cyl" readonly></td>
                                    <td><input type="text" class="form-control min-w-80 power-input" id="createInvoiceRAxis" name="createInvoiceRAxis" data-type="axis" readonly></td>
                                    <td><input type="text" class="form-control min-w-80 power-input" id="createInvoiceRVia" name="createInvoiceRVia" data-type="via" readonly></td>
                                    <td><input type="text" class="form-control min-w-80 power-input" id="createInvoiceRAdd" name="createInvoiceRAdd" data-type="add" readonly></td>
                                    <td><input type="text" class="form-control min-w-80" id="createInvoiceRPd" name="createInvoiceRPd"></td>
                                </tr>
                                <tr>
                                    <th>L.E.</th>
                                    <td><input type="text" class="form-control min-w-80 power-input" id="createInvoiceLSph" name="createInvoiceLSph" data-type="sph" readonly></td>
                                    <td><input type="text" class="form-control min-w-80 power-input" id="createInvoiceLCyl" name="createInvoiceLCyl" data-type="cyl" readonly></td>
                                    <td><input type="text" class="form-control min-w-80 power-input" id="createInvoiceLAxis" name="createInvoiceLAxis" data-type="axis" readonly></td>
                                    <td><input type="text" class="form-control min-w-80 power-input" id="createInvoiceLVia" name="createInvoiceLVia" data-type="via" readonly></td>
                                    <td><input type="text" class="form-control min-w-80 power-input" id="createInvoiceLAdd" name="createInvoiceLAdd" data-type="add" readonly></td>
                                    <td><input type="text" class="form-control min-w-80" id="createInvoiceLPd" name="createInvoiceLPd"></td>
                                </tr>
                                </tbody>
                            </table>
                            </div>
                        </div>
                    </div>

                    <!-- Financials -->
                    <div class="row g-3 mb-2">
                        <div class="col-md-3">
                            <label for="createInvoiceAmount" class="form-label">Amount</label>
                            <input type="number" step="0.01" class="form-control shadow-sm" id="createInvoiceAmount" name="createInvoiceAmount" placeholder="Enter amt">
                        </div>
                        <div class="col-md-3">
                            <label for="createInvoicePaymentMode" class="form-label">Payment Mode</label>
                            <select class="form-select shadow-sm" id="createInvoicePaymentMode" name="createInvoicePaymentMode" required>
                                <option value="" selected>Select</option>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="upi">UPI</option>
                                <option value="netbanking">Net Banking</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="createInvoiceRemark" class="form-label">Remark</label>
                            <textarea class="form-control shadow-sm" id="createInvoiceRemark" name="createInvoiceRemark" rows="3" placeholder="Enter remark"></textarea>
                        </div>
                    </div>

                </form>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer justify-content-between shadow-sm">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                <i class="fa-solid fa-ban"></i> Cancel
                </button>
                <button type="button" class="btn btn-success shadow-sm px-5" id="createInvoiceFormBtn">
                <i class="fa-solid fa-square-plus"></i> Create Invoice
                </button>
            </div>

            </div> <!-- /.modal-content -->
        </div> <!-- /.modal-dialog -->
    </div> <!-- /.modal -->

    <!-- View Invoice Modal - Mobile Responsive Professional Invoice -->
    <div class="modal fade" 
        id="viewInvoiceModal" 
        data-bs-backdrop="static" 
        data-bs-keyboard="false" 
        tabindex="-1" 
        aria-labelledby="viewInvoiceModalLabel" 
        aria-hidden="true">

        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4">

                <!-- Modal Header -->
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="viewInvoiceModalLabel">Invoice View</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body p-3 p-md-4 bg-white">

                    <!-- Invoice Header -->
                    <div class="text-center mb-3 mb-md-4">
                        <h2 class="fw-bold mb-0">INVOICE</h2>
                        <small id="viewInvoiceType" class="text-muted fst-italic"></small>
                    </div>

                    <!-- Invoice & Customer Info -->
                    <div class="row mb-3 mb-md-4">
                        <div class="col-12 col-md-6 mb-2 mb-md-0">
                            <ul class="list-unstyled mb-0">
                                <li><strong>Invoice No:</strong> <span id="viewInvoiceNumber"></span></li>
                                <li><strong>Invoice Date:</strong> <span id="viewInvoiceDate"></span></li>
                                <li><strong>Payment Mode:</strong> <span id="viewInvoicePaymentMode"></span></li>
                            </ul>
                        </div>
                        <div class="col-12 col-md-6 text-md-end">
                            <ul class="list-unstyled mb-0">
                                <li><strong>Customer Name:</strong> <span id="viewInvoiceName"></span></li>
                                <li><strong>Phone:</strong> <span id="viewInvoicePhone"></span></li>
                                <li><strong>Place:</strong> <span id="viewInvoicePlace"></span></li>
                                <li><strong>DoB / Age:</strong> <span id="viewInvoiceDob"></span> / <span id="viewInvoiceAge"></span></li>
                            </ul>
                        </div>
                    </div>

                    <!-- Prescription Table -->
                    <div class="mb-3 mb-md-4">
                        <h6 class="fw-bold border-bottom pb-1">Prescription Details</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm text-center mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Eye</th>
                                        <th>SPH</th>
                                        <th>CYL</th>
                                        <th>AXIS</th>
                                        <th>VIA</th>
                                        <th>ADD</th>
                                        <th>P.D.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <th>R.E.</th>
                                        <td id="viewInvoiceRSph"></td>
                                        <td id="viewInvoiceRCyl"></td>
                                        <td id="viewInvoiceRAxis"></td>
                                        <td id="viewInvoiceRVia"></td>
                                        <td id="viewInvoiceRAdd"></td>
                                        <td id="viewInvoiceRPd"></td>
                                    </tr>
                                    <tr>
                                        <th>L.E.</th>
                                        <td id="viewInvoiceLSph"></td>
                                        <td id="viewInvoiceLCyl"></td>
                                        <td id="viewInvoiceLAxis"></td>
                                        <td id="viewInvoiceLVia"></td>
                                        <td id="viewInvoiceLAdd"></td>
                                        <td id="viewInvoiceLPd"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Frame & Lens / Offer & Claim -->
                    <div class="row mb-3 mb-md-4">
                        <div class="col-6 col-md-6 mb-2 mb-md-0">
                            <p class="mb-1"><strong>Frame:</strong> <span id="viewInvoiceFrame"></span></p>
                            <p class="mb-0"><strong>Offer:</strong> <span id="viewInvoiceOffer"></span></p>
                        </div>
                        <div class="col-6 col-md-6 text-end">
                            <p class="mb-1"><strong>Lens:</strong> <span id="viewInvoiceLense"></span></p>
                            <p class="mb-0"><strong>Claim:</strong> <span id="viewInvoiceClaim"></span></p>
                        </div>
                    </div>

                    <!-- Amount & Remark -->
                    <div class="row mb-3 mb-md-4">
                        <div class="col-6 col-md-6">
                            <p class="mb-0 fs-5"><strong>Amount:</strong> ₹<span id="viewInvoiceAmount"></span></p>
                        </div>
                        <div class="col-6 col-md-6 text-end">
                            <p class="mb-0"><strong>Remark:</strong> <span id="viewInvoiceRemark"></span></p>
                        </div>
                    </div>

                    <!-- Footer Note -->
                    <!-- <div class="text-center text-muted mt-2 mt-md-3 border-top pt-2">
                        <small>Thank you for your business!</small>
                    </div> -->

                </div>

                <!-- Modal Footer -->
                <div class="modal-footer justify-content-end">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark"></i> Close
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- Update Invoice Modal -->
    <div class="modal fade" 
        id="updateInvoiceModal" 
        data-bs-backdrop="static" 
        data-bs-keyboard="false" 
        tabindex="-1" 
        aria-labelledby="updateInvoiceModalLabel" 
        aria-hidden="true">

        <!-- Fullscreen modal -->
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content shadow-lg rounded-4">

                <!-- Modal Header -->
                <div class="modal-header bg-primary text-white shadow-sm">
                    <h5 class="modal-title" id="updateInvoiceModalLabel">Update Invoice</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body p-4 bg-light">
                    <div class="container-fluid">
                        <form id="updateInvoiceForm">

                            <!-- Top Row: Invoice Details -->
                            <div class="row g-3 mb-3 align-items-end">

                                <!-- Invoice Type -->
                                <div class="col-md-3">
                                    <label for="updateInvoiceType" class="form-label">Invoice Type <span class="text-danger">*</span></label>
                                    <select class="form-select shadow-sm" id="updateInvoiceType" name="updateInvoiceType" required>
                                        <option value="" disabled selected>Select invoice type</option>
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

                                <!-- Invoice Date -->
                                <div class="col-md-2">
                                    <label for="updateInvoiceDate" class="form-label">Invoice Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control shadow-sm" id="updateInvoiceDate" name="updateInvoiceDate" required>
                                </div>

                                <!-- Invoice Number -->
                                <div class="col-md-2">
                                    <label for="updateInvoiceNumber" class="form-label">Invoice No. <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control shadow-sm" id="updateInvoiceNumber" name="updateInvoiceNumber" placeholder="Invoice #" required>
                                </div>

                                <!-- Customer Name -->
                                <div class="col-md-3">
                                    <label for="updateInvoiceName" class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control shadow-sm" id="updateInvoiceName" name="updateInvoiceName" placeholder="Enter full name" required>
                                </div>

                                <!-- Phone -->
                                <div class="col-md-2">
                                    <label for="updateInvoicePhone" class="form-label">Phone <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control shadow-sm" id="updateInvoicePhone" name="updateInvoicePhone" placeholder="Enter phone" pattern="[0-9]{10}" required>
                                </div>

                            </div>

                            <!-- Customer Location & Age -->
                            <div class="row g-3 mb-3 align-items-end">
                                <div class="col-md-3">
                                    <label for="updateInvoicePlace" class="form-label">Place</label>
                                    <input type="text" class="form-control shadow-sm" id="updateInvoicePlace" name="updateInvoicePlace" placeholder="Enter place">
                                </div>

                                <div class="col-md-2">
                                    <label for="updateInvoiceDob" class="form-label">DoB</label>
                                    <input type="date" class="form-control shadow-sm" id="updateInvoiceDob" name="updateInvoiceDob" onchange="getAge(null, 'updateInvoiceDob', 'updateInvoiceAge')" />
                                </div>

                                <div class="col-md-1">
                                    <label for="updateInvoiceAge" class="form-label">Age</label>
                                    <input type="number" class="form-control shadow-sm" id="updateInvoiceAge" name="updateInvoiceAge" readonly>
                                </div>

                                <div class="col-md-3">
                                    <label for="updateInvoiceFrame" class="form-label">Frame</label>
                                    <input type="text" class="form-control shadow-sm" id="updateInvoiceFrame" name="updateInvoiceFrame" placeholder="Enter frame">
                                </div>

                                <div class="col-md-3">
                                    <label for="updateInvoiceLense" class="form-label">Lense</label>
                                    <input type="text" class="form-control shadow-sm" id="updateInvoiceLense" name="updateInvoiceLense" placeholder="Enter lense">
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-3">
                                    <label for="updateInvoiceStatus" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select class="form-select shadow-sm" id="updateInvoiceStatus" name="updateInvoiceStatus" required>
                                        <option value="" disabled selected>Select</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-3"></div>
                                <!-- Offer & Claim -->
                                <div class="col-md-3">
                                    <label for="updateInvoiceOffer" class="form-label">Offer</label>
                                    <input type="text" class="form-control shadow-sm" id="updateInvoiceOffer" name="updateInvoiceOffer" placeholder="Enter offer">
                                </div>
                                <div class="col-md-3">
                                    <label for="updateInvoiceClaim" class="form-label">Claim</label>
                                    <input type="text" class="form-control shadow-sm" id="updateInvoiceClaim" name="updateInvoiceClaim" placeholder="Enter claim">
                                </div>
                            </div>

                            <!-- Prescription Table -->
                            <div class="row g-3 mb-3">
                                <div class="col-12">
                                    <label class="form-label"><i class="fa-solid fa-eye"></i> Prescription (Right & Left Eye)</label>
                                    <div class="table-responsive shadow-sm">
                                        <table class="table table-bordered text-center">
                                            <thead class="table-light">
                                                <tr>
                                                    <th></th>
                                                    <th>SPH</th>
                                                    <th>CYL</th>
                                                    <th>AXIS</th>
                                                    <th>VIA</th>
                                                    <th>ADD</th>
                                                    <th>P.D.</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <th>R.E.</th>
                                                    <td><input type="text" class="form-control min-w-80 power-input" id="updateInvoiceRSph" name="updateInvoiceRSph" data-type="sph" readonly></td>
                                                    <td><input type="text" class="form-control min-w-80 power-input" id="updateInvoiceRCyl" name="updateInvoiceRCyl" data-type="cyl" readonly></td>
                                                    <td><input type="text" class="form-control min-w-80 power-input" id="updateInvoiceRAxis" name="updateInvoiceRAxis" data-type="axis" readonly></td>
                                                    <td><input type="text" class="form-control min-w-80 power-input" id="updateInvoiceRVia" name="updateInvoiceRVia" data-type="via" readonly></td>
                                                    <td><input type="text" class="form-control min-w-80 power-input" id="updateInvoiceRAdd" name="updateInvoiceRAdd" data-type="add" readonly></td>
                                                    <td><input type="text" class="form-control min-w-80" id="updateInvoiceRPd" name="updateInvoiceRPd"></td>
                                                </tr>
                                                <tr>
                                                    <th>L.E.</th>
                                                    <td><input type="text" class="form-control min-w-80 power-input" id="updateInvoiceLSph" name="updateInvoiceLSph" data-type="sph" readonly></td>
                                                    <td><input type="text" class="form-control min-w-80 power-input" id="updateInvoiceLCyl" name="updateInvoiceLCyl" data-type="cyl" readonly></td>
                                                    <td><input type="text" class="form-control min-w-80 power-input" id="updateInvoiceLAxis" name="updateInvoiceLAxis" data-type="axis" readonly></td>
                                                    <td><input type="text" class="form-control min-w-80 power-input" id="updateInvoiceLVia" name="updateInvoiceLVia" data-type="via" readonly></td>
                                                    <td><input type="text" class="form-control min-w-80 power-input" id="updateInvoiceLAdd" name="updateInvoiceLAdd" data-type="add" readonly></td>
                                                    <td><input type="text" class="form-control min-w-80" id="updateInvoiceLPd" name="updateInvoiceLPd"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Financials -->
                            <div class="row g-3 mb-2">
                                <div class="col-md-3">
                                    <label for="updateInvoiceAmount" class="form-label">Amount</label>
                                    <input type="number" step="0.01" class="form-control shadow-sm" id="updateInvoiceAmount" name="updateInvoiceAmount" placeholder="Enter amt">
                                </div>
                                <div class="col-md-3">
                                    <label for="updateInvoicePaymentMode" class="form-label">Payment Mode</label>
                                    <select class="form-select shadow-sm" id="updateInvoicePaymentMode" name="updateInvoicePaymentMode" required>
                                        <option value="" selected>Select</option>
                                        <option value="cash">Cash</option>
                                        <option value="card">Card</option>
                                        <option value="upi">UPI</option>
                                        <option value="netbanking">Net Banking</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="updateInvoiceRemark" class="form-label">Remark</label>
                                    <textarea class="form-control shadow-sm" id="updateInvoiceRemark" name="updateInvoiceRemark" rows="3" placeholder="Enter remark"></textarea>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer justify-content-between shadow-sm">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                        <i class="fa-solid fa-ban"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-success shadow-sm px-5" id="updateInvoiceFormBtn">
                        <i class="fa-solid fa-floppy-disk"></i> Update Invoice
                    </button>
                </div>

            </div> <!-- /.modal-content -->
        </div> <!-- /.modal-dialog -->
    </div> <!-- /.modal -->


    <!-- Delete Invoice Modal -->
    <div class="modal fade" id="deleteInvoiceModal" tabindex="-1" aria-labelledby="deleteInvoiceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg rounded-4">
            
            <!-- Modal Header -->
            <div class="modal-header bg-danger text-white shadow-sm">
                <h5 class="modal-title" id="deleteInvoiceModalLabel">Delete Invoice</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Modal Body -->
            <div class="modal-body">
                <p id="deleteInvoiceMessage">Are you sure you want to delete this Invoice?</p>
                <p class="text-danger mb-0"><strong>Note: This action cannot be undone.</strong></p>
            </div>
            
            <!-- Modal Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                <i class="fa-solid fa-ban"></i> Cancel
                </button>
                <button type="button" class="btn btn-danger" id="deleteInvoiceBtn">
                <i class="fa-solid fa-trash-can"></i> Delete
                </button>
            </div>
            
            </div>
        </div>
    </div>

</div>

  <script src="./assets/js/invoices.js"></script>

<?php
  include_once 'footer.php';
?>
