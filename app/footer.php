    </div>
  </div>
  
  <script src="<?= $base_url ?>assets/js/header.js"></script>

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
