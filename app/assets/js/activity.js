$(function () {
  // State
  let state = {
    page: 1,
    limit: 25,
    order: "DESC",
    query: "",
    lastResponse: null,
  };

  const $tbody = $("#logsTbody");
  const $pagination = $("#pagination");
  const $showingInfo = $("#showingInfo");
  const $totalRecords = $("#totalRecords");
  const $currentPage = $("#currentPage");
  const $totalPages = $("#totalPages");

  // Helper: render a single page of logs
  function renderLogs(records, page, limit, totalRecords) {
    $tbody.empty();

    if (!records || !records.length) {
      $tbody.html(
        '<tr><td colspan="6" class="no-data">No logs found.</td></tr>'
      );
      return;
    }

    records.forEach((r, idx) => {
      const rowIdx = (page - 1) * limit + idx + 1;
      const userId = escapeHtml(r.userId || "");
      const businessId = escapeHtml(r.businessId || "");
      const action = escapeHtml(r.action || "");
      const ip = escapeHtml(r.ipAddress || "");
      const when = escapeHtml(r.createdAtText || "");

      const tr = `
            <tr>
              <td class="align-middle">${rowIdx}</td>
              <td class="align-middle"><code>${userId}</code></td>
              <td class="align-middle"><code>${businessId}</code></td>
              <td class="align-middle">${action}</td>
              <td class="align-middle"><span class="text-monospace">${ip}</span></td>
              <td class="align-middle"><small class="text-muted">${when}</small></td>
            </tr>
          `;
      $tbody.append(tr);
    });

    // update summary
    const showingFrom = (page - 1) * limit + 1;
    const showingTo = Math.min(page * limit, totalRecords);
    $showingInfo.text(`${showingFrom}–${showingTo}`);
    $totalRecords.text(totalRecords);
  }

  // Helper: build pagination UI
  function renderPagination(currentPage, totalPages) {
    $pagination.empty();
    $currentPage.text(currentPage || 0);
    $totalPages.text(totalPages || 0);

    if (totalPages <= 1) return;

    // Prev
    const prevDisabled = currentPage <= 1 ? " disabled" : "";
    $pagination.append(
      `<li class="page-item${prevDisabled}"><a class="page-link" href="#" data-page="${
        currentPage - 1
      }">Prev</a></li>`
    );

    // Show up to 7 page links (center current)
    const maxLinks = 7;
    let start = Math.max(1, currentPage - Math.floor(maxLinks / 2));
    let end = Math.min(totalPages, start + maxLinks - 1);
    if (end - start + 1 < maxLinks) start = Math.max(1, end - maxLinks + 1);

    if (start > 1) {
      $pagination.append(
        `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`
      );
      if (start > 2)
        $pagination.append(
          `<li class="page-item disabled"><span class="page-link">…</span></li>`
        );
    }

    for (let p = start; p <= end; p++) {
      const active = p === currentPage ? " active" : "";
      $pagination.append(
        `<li class="page-item${active}"><a class="page-link" href="#" data-page="${p}">${p}</a></li>`
      );
    }

    if (end < totalPages) {
      if (end < totalPages - 1)
        $pagination.append(
          `<li class="page-item disabled"><span class="page-link">…</span></li>`
        );
      $pagination.append(
        `<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`
      );
    }

    // Next
    const nextDisabled = currentPage >= totalPages ? " disabled" : "";
    $pagination.append(
      `<li class="page-item${nextDisabled}"><a class="page-link" href="#" data-page="${
        currentPage + 1
      }">Next</a></li>`
    );
  }

  // Escape to avoid injection in table cells
  function escapeHtml(str) {
    if (!str && str !== 0) return "";
    return String(str)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  // Main: fetch logs
  function loadLogs(opts = {}) {
    state.page = opts.page ?? state.page;
    state.limit = parseInt(opts.limit ?? state.limit, 10);
    state.order = opts.order ?? state.order;
    state.query = typeof opts.query !== "undefined" ? opts.query : state.query;

    // UI: show spinner row while loading
    $tbody.html(
      '<tr><td colspan="6" class="spinner-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div></tr>'
    );
    $pagination.empty();
    $("#showingInfo").text("0");

    // Build query params for GET request
    const params = {
      page: state.page,
      limit: state.limit,
      ord: state.order,
    };
    if (state.query) params.q = state.query;

    // Call API. Adjust path if different.
    apiRequest(
      "GET",
      "/api/business/activity/logs",
      params,
      false,
      function (res) {
        if (res && res.status && res.data && Array.isArray(res.data.records)) {
          const records = res.data.records;
          const pag = res.pagination || {
            currentPage: 1,
            limit: state.limit,
            totalPages: 1,
            totalRecords: records.length,
          };
          // Render
          renderLogs(records, pag.currentPage, pag.limit, pag.totalRecords);
          renderPagination(pag.currentPage, pag.totalPages);
          state.lastResponse = res;

          // Success toast (silent by default)
          // Swal.fire({ toast: true, icon: 'success', title: 'Logs loaded', position: 'top-end', showConfirmButton: false, timer: 900 });
        } else {
          $tbody.html(
            '<tr><td colspan="6" class="no-data">No logs found.</td></tr>'
          );
          Swal.fire({
            toast: true,
            icon: "error",
            title: res?.message || "Failed to fetch logs",
            position: "top-end",
            showConfirmButton: false,
            timer: 1500,
          });
        }
      },
      function (jqXHR, textStatus, err) {
        $tbody.html(
          '<tr><td colspan="6" class="no-data">Unable to load logs.</td></tr>'
        );
        Swal.fire({
          toast: true,
          icon: "error",
          title: "Error fetching logs",
          text: err || textStatus,
          position: "top-end",
          showConfirmButton: false,
          timer: 1800,
        });
      }
    );
  }

  // Events
  $pagination.on("click", "a.page-link", function (e) {
    e.preventDefault();
    const p = parseInt($(this).attr("data-page"), 10);
    if (!p || p === state.page) return;
    state.page = p;
    loadLogs({ page: p });
  });

  $("#limitSelect").on("change", function () {
    state.limit = parseInt(this.value, 10);
    state.page = 1;
    loadLogs({ page: 1, limit: state.limit });
  });

  $("#orderSelect").on("change", function () {
    state.order = $(this).val();
    state.page = 1;
    loadLogs({ page: 1, order: state.order });
  });

  $("#refreshBtn").on("click", function () {
    loadLogs({ page: 1 });
  });

  $("#downloadJsonBtn").on("click", function () {
    if (!state.lastResponse) {
      Swal.fire({
        toast: true,
        icon: "info",
        title: "No data to download",
        position: "top-end",
        showConfirmButton: false,
        timer: 1200,
      });
      return;
    }
    const blob = new Blob([JSON.stringify(state.lastResponse, null, 2)], {
      type: "application/json",
    });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `activity-logs-page-${
      state.lastResponse.pagination?.currentPage || state.page
    }.json`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  });

  // Debounced search
  let searchTimer = null;
  $("#searchInput").on("input", function () {
    clearTimeout(searchTimer);
    const q = $(this).val().trim();
    searchTimer = setTimeout(() => {
      state.query = q;
      state.page = 1;
      loadLogs({ page: 1, query: q });
    }, 500);
  });

  // Initial load
  loadLogs({ page: 1, limit: state.limit });
});
