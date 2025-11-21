<?php
    include_once 'header.php';
?>

  <!-- Content -->
  <div class="content">
    <!-- <div class="settings-card">
      <h4 class="section-title"><i class="bi bi-building-gear me-2"></i> Business Information</h4>

      <form id="businessForm" class="row g-3" method="POST" autocomplete="off">
        <div class="col-md-6">
          <label class="form-label">Business Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="businessName" id="businessName" placeholder="Enter business name">
        </div>
        <div class="col-md-3">
          <label class="form-label">Email</label>
          <input type="email" class="form-control" name="businessEmail" id="businessEmail" placeholder="Enter email address">
        </div>
        <div class="col-md-3">
          <label class="form-label">Phone <span class="text-danger">*</span></label>
          <input type="tel" class="form-control" name="businessPhone" id="businessPhone" placeholder="Enter phone number">
        </div>
        <div class="col-md-6">
          <label class="form-label">GST Number</label>
          <input type="text" class="form-control" name="gst" placeholder="22AAAAA0000A1Z5">
        </div>
        <div class="col-md-6">
          <label class="form-label">Address <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="businessAddress" id="businessAddress" placeholder="Enter business address">
        </div>

        <div class="text-end mt-4">
          <button class="btn btn-primary save-btn" id="saveBusinessBtn">
            <i class="bi bi-save me-1"></i> Save Business Info
          </button>
        </div>
      </form>
    </div> -->
  <div class="settings-card">
    <h4 class="section-title mb-3 d-flex align-items-center">
      <i class="bi bi-building-gear me-2"></i> Business Information
    </h4>

    <form id="businessForm" class="row g-4" method="POST" enctype="multipart/form-data" autocomplete="off">
      
      <!-- Left Column -->
      <div class="col-md-8">
        <div class="row g-3">
          <!-- Business Name -->
          <div class="col-md-12">
            <label class="form-label">Business Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="businessName" id="businessName" placeholder="Enter business name">
          </div>

          <!-- Email -->
          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="businessEmail" id="businessEmail" placeholder="Enter email address">
          </div>

          <!-- Phone -->
          <div class="col-md-6">
            <label class="form-label">Phone <span class="text-danger">*</span></label>
            <input type="tel" class="form-control" name="businessPhone" id="businessPhone" placeholder="Enter phone number">
          </div>

          <!-- Address -->
          <div class="col-md-12">
            <label class="form-label">Address <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="businessAddress" id="businessAddress" placeholder="Enter business address">
          </div>
        </div>
      </div>

      <!-- Right Column (Logo Upload) -->
      <div class="col-md-4">

        <!-- File Input -->
        <label class="form-label">Business Logo</label>
        <div class="input-group">
          <input 
            type="file" 
            class="form-control" 
            id="businessLogo" 
            name="logo" 
            accept="image/*"
          >
          <button 
            type="button" 
            class="btn btn-outline-secondary" 
            id="clearLogoBtn" 
            title="Remove selected logo"
          >
            <i class="bi bi-x-circle"></i>
          </button>
        </div>
        <small class="text-muted d-block mb-2">Supported formats: JPG, PNG (Max: 1MB)</small>

        <!-- Preview -->
        <div class="text-center border rounded p-2 bg-white">
          <img 
            id="logoPreview" 
            src="" 
            alt="Logo Preview" 
            class="img-fluid" 
            style="max-height: 100px; object-fit: contain;"
          >
        </div>
      </div>

      <!-- Save Button -->
      <div class="col-12 text-end mt-4">
        <button type="submit" class="btn btn-primary save-btn" id="saveBusinessBtn">
          <i class="bi bi-save me-1"></i> Save Business Info
        </button>
      </div>
    </form>
  </div>

    <!-- Printer Config -->
    <!-- <div class="settings-card mt-4">
      <h4 class="section-title"><i class="bi bi-printer me-2"></i> Printer Configuration</h4>

      <form id="printerForm" class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Printer Name</label>
          <input type="text" class="form-control" placeholder="EPSON L3150">
        </div>
        <div class="col-md-6">
          <label class="form-label">Connection Type</label>
          <select class="form-select">
            <option>USB</option>
            <option>Wi-Fi</option>
            <option>Bluetooth</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Paper Size</label>
          <select class="form-select">
            <option>A4</option>
            <option>A5</option>
            <option>Letter</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Default Copies</label>
          <input type="number" class="form-control" value="1" min="1" max="10">
        </div>
        <div class="text-end mt-4">
          <button class="btn btn-success save-btn">
            <i class="bi bi-printer me-1"></i> Save Printer Settings
          </button>
        </div>
      </form>
    </div> -->

    <!-- Advanced Features -->
    <!-- <div class="settings-card mt-4">
      <h4 class="section-title"><i class="bi bi-sliders me-2"></i> Advanced Settings</h4>

      <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" id="autoBackup">
        <label class="form-check-label" for="autoBackup">Enable Automatic Backup</label>
      </div>
      <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" id="autoPrint">
        <label class="form-check-label" for="autoPrint">Enable Auto Print after Invoice</label>
      </div>
      <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" id="darkMode">
        <label class="form-check-label" for="darkMode">Enable Dark Mode</label>
      </div>

      <div class="text-end mt-3">
        <button class="btn btn-dark">
          <i class="bi bi-gear-fill me-1"></i> Apply Changes
        </button>
      </div>
    </div>
  </div> -->

  <script src="./assets/js/settings.js"></script>

<?php
  include_once 'footer.php';
?>
