    </div>
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
            <a href="dashboard" class="nav-link text-white py-2 px-2 d-flex align-items-center gap-2">
                <i class="fa-solid fa-gauge"></i><span>Dashboard</span>
            </a>
            <a href="invoices" class="nav-link text-white py-2 px-2 d-flex align-items-center gap-2">
                <i class="fa-solid fa-file-invoice"></i><span>Invoices</span>
            </a>
            <a href="clients" class="nav-link text-white py-2 px-2 d-flex align-items-center gap-2">
                <i class="fa-solid fa-user-group"></i><span>Clients</span>
            </a>
            <a href="payments" class="nav-link text-white py-2 px-2 d-flex align-items-center gap-2">
                <i class="fa-solid fa-credit-card"></i><span>Payments</span>
            </a>

            <!-- Reports dropdown -->
            <a href="#" class="nav-link dropdown-toggle text-white py-2 px-2 d-flex align-items-center gap-2">
                <i class="fa-solid fa-chart-line"></i><span>Reports</span>
            </a>

            <div class="submenu ps-4" style="margin-top: -4px;">
                <a href="daily-report" class="text-white-50 py-1 d-block">Daily Report</a>
                <a href="monthly-report" class="text-white-50 py-1 d-block">Monthly Report</a>
                <a href="yearly-report" class="text-white-50 py-1 d-block">Yearly Report</a>
            </div>
            </nav>
        </div>
    </div>

  <!-- JS -->
  <script src="./assets/lib/jquery/jquery.min.js"></script>
  <script src="./assets/lib/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="./assets/lib/sweetalert2/sweetalert2.all.min.js"></script>
  <script src="./assets/lib/tabulator/dist/js/tabulator.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="./assets/js/common.js"></script>
  <script src="./assets/js/header.js"></script>

  <script>
    // Dropdown submenu
    document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
      toggle.addEventListener('click', e => {
        e.preventDefault();
        const submenu = toggle.nextElementSibling;
        const isVisible = submenu.style.display === 'flex';

        document.querySelectorAll('.submenu').forEach(menu => menu.style.display = 'none');
        document.querySelectorAll('.dropdown-toggle').forEach(btn => btn.classList.remove('open'));

        submenu.style.display = isVisible ? 'none' : 'flex';
        toggle.classList.toggle('open', !isVisible);
      });
    });

    // Highlight current active link
    const currentPage = window.location.pathname.split('/').pop().split('.').shift();
    document.querySelectorAll('.nav-link').forEach(link => {
      const linkPage = link.getAttribute('href');
      if (linkPage === currentPage) link.classList.add('active');
    });
  </script>
</body>
</html>
