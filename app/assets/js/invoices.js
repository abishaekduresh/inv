document.addEventListener("DOMContentLoaded", function () {
  // Global variables
  let invoiceIdToUpdate = null;
  let invoiceIdToDelete = null;
  let selectedInput = null;
  let invoiceCurrentPage = 1;
  const invoicePageSize = 10;

  // ==== INIT ====
  initInvoicesPage();

  // ==== Bind Create/Update/Delete Buttons ====
  const createBtn = document.getElementById("createInvoiceFormBtn");
  const updateBtn = document.getElementById("updateInvoiceFormBtn");
  const deleteBtn = document.getElementById("deleteInvoiceBtn");

  const searchInvIdInputBtn = document.getElementById("searchInvIdInputBtn");

  if (createBtn) createBtn.addEventListener("click", handleCreateInvoice);
  if (updateBtn) updateBtn.addEventListener("click", handleUpdateInvoice);
  if (deleteBtn) deleteBtn.addEventListener("click", handleDeleteInvoice);
  if (searchInvIdInputBtn)
    searchInvIdInputBtn.addEventListener("click", fetchInvoices);
  document
    .getElementById("searchInvBtn")
    .addEventListener("click", function () {
      fetchInvoices(); // perform search
    });

  document.getElementById("resetFilterBtn").addEventListener("click", () => {
    [
      "invoiceIdInput",
      "searchInput",
      "invoiceTypeFilter",
      "invoiceStatusFilter",
      // "fromDate",
      // "toDate",
      "invoiceNumberInput",
      // "invoicePlaceInput",
    ].forEach((id) => (document.getElementById(id).value = ""));

    invoiceCurrentPage = 1;
    fetchInvoices();
  });

  // ==== Initialize Search and Filter ====
  function initInvoicesPage() {
    const searchInput = document.getElementById("searchInput");
    const invoiceStatusFilter = document.getElementById("invoiceStatusFilter");
    const invoiceTypeFilter = document.getElementById("invoiceTypeFilter");
    // const invoicePlaceInput = document.getElementById("invoicePlaceInput");
    const invoiceNumberInput = document.getElementById("invoiceNumberInput");
    const invoiceIdInput = document.getElementById("invoiceIdInput");

    // if (!searchInput || !invoiceStatusFilter) return;

    // --- Search as you type (with debounce) ---
    searchInput.addEventListener("keyup", () => {
      [
        "invoiceIdInput",
        "invoiceTypeFilter",
        "invoiceStatusFilter",
        // "fromDate",
        // "toDate",
        "invoiceNumberInput",
        // "invoicePlaceInput",
      ].forEach((id) => (document.getElementById(id).value = ""));
      const query = searchInput.value.trim();
      invoiceCurrentPage = 1;
      setUniqueTimeout(
        "searchInput",
        () => {
          if (query.length >= 4 || query.length === 0) {
            fetchInvoices();
          }
        },
        500
      ); // waits 0.5s after typing stops
    });
    // Search by Place as you type (with debounce)
    // invoicePlaceInput.addEventListener("keyup", () => {
    //   [
    //     "invoiceIdInput",
    //     "invoiceTypeFilter",
    //     "invoiceStatusFilter",
    //     "searchInput",
    //     "fromDate",
    //     "toDate",
    //     "invoiceNumberInput",
    //   ].forEach((id) => (document.getElementById(id).value = ""));
    //   const query = invoicePlaceInput.value.trim();
    //   invoiceCurrentPage = 1;
    //   setUniqueTimeout(
    //     "invoicePlaceInput",
    //     () => {
    //       if (query.length >= 3 || query.length === 0) {
    //         fetchInvoices();
    //       }
    //     },
    //     500
    //   ); // waits 0.5s after typing stops
    // });

    // Search by Invoice ID as you type (with debounce)
    invoiceIdInput.addEventListener("keyup", () => {
      [
        "searchInput",
        "invoiceTypeFilter",
        "invoiceStatusFilter",
        // "fromDate",
        // "toDate",
        "invoiceNumberInput",
        // "invoicePlaceInput",
      ].forEach((id) => (document.getElementById(id).value = ""));
      const query = invoiceIdInput.value.trim();
      invoiceCurrentPage = 1;
      setUniqueTimeout(
        "invoiceIdInput",
        () => {
          if (query.length >= 1 || query.length === 0) {
            fetchInvoices();
          }
        },
        500
      ); // waits 0.5s after typing stops
    });
    // Search by Invoice Number as you type (with debounce)
    invoiceNumberInput.addEventListener("keyup", () => {
      [
        "invoiceIdInput",
        "invoiceTypeFilter",
        "invoiceStatusFilter",
        "searchInput",
        // "fromDate",
        // "toDate",
        // "invoicePlaceInput",
      ].forEach((id) => (document.getElementById(id).value = ""));
      const query = invoiceNumberInput.value.trim();
      invoiceCurrentPage = 1;
      setUniqueTimeout(
        "invoiceNumberInput",
        () => {
          if (query.length >= 3 || query.length === 0) {
            fetchInvoices();
          }
        },
        500
      ); // waits 0.5s after typing stops
    });

    invoiceStatusFilter.addEventListener("change", () => {
      [
        "invoiceIdInput",
        "invoiceTypeFilter",
        "searchInput",
        // "invoiceNumberInput",
        // "fromDate",
        // "toDate",
        // "invoicePlaceInput",
      ].forEach((id) => (document.getElementById(id).value = ""));
      invoiceCurrentPage = 1;
      fetchInvoices();
    });

    invoiceTypeFilter.addEventListener("change", () => {
      [
        "invoiceIdInput",
        "invoiceStatusFilter",
        "searchInput",
        "invoiceNumberInput",
        // "fromDate",
        // "toDate",
        // "invoicePlaceInput",
      ].forEach((id) => (document.getElementById(id).value = ""));
      invoiceCurrentPage = 1;
      fetchInvoices();
    });

    fetchInvoices(); // Initial load
  }

  // ==== Fetch invoices from API ====
  function fetchInvoices() {
    const q = document.getElementById("searchInput").value.trim();
    const id = document.getElementById("invoiceIdInput").value.trim();
    const invno = document.getElementById("invoiceNumberInput").value.trim();
    const invtype = document.getElementById("invoiceTypeFilter").value;
    const sts = document.getElementById("invoiceStatusFilter").value;
    // const pl = document.getElementById("invoicePlaceInput").value;

    const params = {
      q,
      id,
      invno,
      invtype,
      // pl,
      sts,
      page: invoiceCurrentPage,
      limit: invoicePageSize,
    };

    apiRequest(
      "GET",
      "/api/invoices",
      params,
      false,
      (res) => {
        const invoices = res.data?.invoices || [];
        const totalRecords = res.pagination?.totalRecords || 0;
        renderInvoicesTable(invoices, totalRecords);
        renderPagination(totalRecords);
      },
      (xhr) => {
        let message = "Error fetching invoice.";
        // Safely parse JSON response
        if (xhr.responseText) {
          try {
            const res = JSON.parse(xhr.responseText);
            if (res.message) message = res.message;
          } catch (err) {
            console.warn("Response is not valid JSON:", xhr.responseText);
            message = xhr.responseText;
          }
        }
        // Show proper icon type
        const icon = xhr.status >= 500 ? "error" : "warning";
        showToast(icon, message);
      }
    );
  }

  // ==== Create Invoice ====
  function handleCreateInvoice() {
    const btn = this;
    const form = document.getElementById("createInvoiceForm");
    if (!form) return;

    const data = collectFormData("create");
    if (!data.name || !data.phone || !data.invoiceNumber) {
      return showToast("error", "Please fill in all required fields.");
    }

    btn.disabled = true;
    apiRequest(
      "POST",
      "/api/invoices",
      data,
      false,
      (res) => {
        showToast("success", res.message || "Invoice created successfully!");
        form.reset();
        bootstrap.Modal.getInstance(
          document.getElementById("createInvoiceStaticBackdropModal")
        )?.hide();
        fetchInvoices();
        btn.disabled = false;
      },
      (xhr) => {
        let message = "Error creating invoice.";
        // Safely parse JSON response
        if (xhr.responseText) {
          try {
            const res = JSON.parse(xhr.responseText);
            if (res.message) message = res.message;
          } catch (err) {
            console.warn("Response is not valid JSON:", xhr.responseText);
            message = xhr.responseText;
          }
        }
        // Show proper icon type
        const icon = xhr.status >= 500 ? "error" : "warning";
        showToast(icon, message);
        btn.disabled = false;
      }
    );
  }

  // ==== Update Invoice ====
  function handleUpdateInvoice() {
    const btn = this;
    const form = document.getElementById("updateInvoiceForm");
    if (!form || !invoiceIdToUpdate) return;

    const data = collectFormData("update");
    if (!data.name || !data.phone || !data.invoiceNumber) {
      return showToast("error", "Please fill in all required fields.");
    }

    btn.disabled = true;
    apiRequest(
      "PUT",
      `/api/invoices/${invoiceIdToUpdate}`,
      data,
      false,
      (res) => {
        showToast("success", res.message || "Invoice updated!");
        form.reset();
        const modalInstance = bootstrap.Modal.getInstance(
          document.getElementById("updateInvoiceModal")
        );
        if (modalInstance) modalInstance.hide();
        fetchInvoices();
        btn.disabled = false;
      },
      (xhr) => {
        let message = "Error updating invoice.";
        // Safely parse JSON response
        if (xhr.responseText) {
          try {
            const res = JSON.parse(xhr.responseText);
            if (res.message) message = res.message;
          } catch (err) {
            console.warn("Response is not valid JSON:", xhr.responseText);
            message = xhr.responseText;
          }
        }
        // Show proper icon type
        const icon = xhr.status >= 500 ? "error" : "warning";
        showToast(icon, message);
        btn.disabled = false;
      }
    );
  }

  // ==== Delete Invoice ====
  function handleDeleteInvoice() {
    if (!invoiceIdToDelete) return;
    const btn = this;

    btn.disabled = true;
    apiRequest(
      "DELETE",
      `/api/invoices/${invoiceIdToDelete}`,
      {},
      false,
      (res) => {
        showToast(
          "success",
          res.message || `Invoice #${invoiceIdToDelete} deleted`
        );
        bootstrap.Modal.getInstance(
          document.getElementById("deleteInvoiceModal")
        )?.hide();
        fetchInvoices();
        invoiceIdToDelete = null;
        btn.disabled = false;
      },
      (xhr) => {
        let message = "Error deleting invoice.";
        // Safely parse JSON response
        if (xhr.responseText) {
          try {
            const res = JSON.parse(xhr.responseText);
            if (res.message) message = res.message;
          } catch (err) {
            console.warn("Response is not valid JSON:", xhr.responseText);
            message = xhr.responseText;
          }
        }
        // Show proper icon type
        const icon = xhr.status >= 500 ? "error" : "warning";
        showToast(icon, message);
        btn.disabled = false;
      }
    );
  }

  // ==== Collect Form Data ====
  function collectFormData(prefix) {
    const get = (id) =>
      document.getElementById(`${prefix}Invoice${id}`)?.value.trim() || "";
    return {
      invoiceType: get("Type"),
      name: get("Name"),
      phone: get("Phone"),
      invoiceDate: get("Date"),
      invoiceNumber: get("Number"),
      dob: get("Dob"),
      place: get("Place"),
      frame: get("Frame"),
      lence: get("Lense"),
      rSph: get("RSph"),
      rCyl: get("RCyl"),
      rAxis: get("RAxis"),
      rVia: get("RVia"),
      rAdd: get("RAdd"),
      rPd: get("RPd"),
      lSph: get("LSph"),
      lCyl: get("LCyl"),
      lAxis: get("LAxis"),
      lVia: get("LVia"),
      lAdd: get("LAdd"),
      lPd: get("LPd"),
      amount: get("Amount"),
      offer: get("Offer"),
      claim: get("Claim"),
      remark: get("Remark"),
      paymentMode: get("PaymentMode"),
      invoiceStatus: get("Status"),
    };
  }

  // ==== Render Invoice Table ====
  function renderInvoicesTable(invoices, totalRecords = 0) {
    const tbody = document.getElementById("invoicesTableBody");
    const mobileContainer = document.getElementById("invoicesCardsContainer");
    const empty = document.getElementById("emptyState");
    const count = document.getElementById("totalCount");

    if (!tbody || !mobileContainer || !count) return;

    tbody.innerHTML = "";
    mobileContainer.innerHTML = "";
    count.textContent = `${totalRecords} invoices`;

    if (!invoices.length) {
      empty.classList.remove("d-none");
      return;
    } else empty.classList.add("d-none");

    invoices.forEach((inv, i) => {
      const sn = (invoiceCurrentPage - 1) * invoicePageSize + i + 1;
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td>${sn}</td>
        <td>${inv.invoiceId}</td>
        <td>${inv.invoiceNumber}</td>
        <td>${formatTimestamp(inv.invoiceDate, "DD-MM-YYYY")}</td>
        <td>${inv.invoiceType}</td>
        <td>${StringUtils.toTitleCase(inv.name)}</td>
        <td>${inv.phone}</td>
        <td>${StringUtils.toTitleCase(inv.place)}</td>
        <td><span class="badge ${
          inv.invoiceStatus === "active" ? "bg-success" : "bg-secondary"
        }">${StringUtils.capitalize(inv.invoiceStatus)}</span></td>
        <td>
          <button class="btn btn-sm btn-info me-1" data-id="${
            inv.invoiceId
          }"><i class="fa-solid fa-eye"></i></button>
          <button class="btn btn-sm btn-primary me-1" data-id="${
            inv.invoiceId
          }"><i class="fa-solid fa-pen"></i></button>
          <button class="btn btn-sm btn-danger" data-id="${
            inv.invoiceId
          }"><i class="fa-solid fa-trash"></i></button>
        </td>
      `;
      const [viewBtn, editBtn, delBtn] = tr.querySelectorAll("button");
      viewBtn.addEventListener("click", () => viewInvoice(inv));
      editBtn.addEventListener("click", () => updateInvoice(inv));
      delBtn.addEventListener("click", () => openDeleteModal(inv));
      tbody.appendChild(tr);
    });
  }

  // ==== Pagination ====
  function renderPagination(totalItems) {
    const pagination = document.getElementById("pagination");
    if (!pagination) return;

    pagination.innerHTML = "";
    const totalPages = Math.ceil(totalItems / invoicePageSize);
    if (totalPages <= 1) return;

    const maxVisiblePages = 5; // Number of page buttons to show besides first/last
    let startPage = Math.max(
      1,
      invoiceCurrentPage - Math.floor(maxVisiblePages / 2)
    );
    let endPage = startPage + maxVisiblePages - 1;

    if (endPage > totalPages) {
      endPage = totalPages;
      startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }

    // --- First button ---
    const firstLi = document.createElement("li");
    firstLi.className = `page-item ${
      invoiceCurrentPage === 1 ? "disabled" : ""
    }`;
    firstLi.innerHTML = `<a class="page-link" href="#">First</a>`;
    firstLi.addEventListener("click", (e) => {
      e.preventDefault();
      if (invoiceCurrentPage === 1) return;
      invoiceCurrentPage = 1;
      fetchInvoices();
    });
    pagination.appendChild(firstLi);

    // --- Pages with ellipsis ---
    if (startPage > 1) {
      const li = document.createElement("li");
      li.className = "page-item disabled";
      li.innerHTML = `<span class="page-link">...</span>`;
      pagination.appendChild(li);
    }

    for (let i = startPage; i <= endPage; i++) {
      const li = document.createElement("li");
      li.className = `page-item ${i === invoiceCurrentPage ? "active" : ""}`;
      li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
      li.addEventListener("click", (e) => {
        e.preventDefault();
        if (invoiceCurrentPage === i) return;
        invoiceCurrentPage = i;
        fetchInvoices();
      });
      pagination.appendChild(li);
    }

    if (endPage < totalPages) {
      const li = document.createElement("li");
      li.className = "page-item disabled";
      li.innerHTML = `<span class="page-link">...</span>`;
      pagination.appendChild(li);
    }

    // --- Last button ---
    const lastLi = document.createElement("li");
    lastLi.className = `page-item ${
      invoiceCurrentPage === totalPages ? "disabled" : ""
    }`;
    lastLi.innerHTML = `<a class="page-link" href="#">Last</a>`;
    lastLi.addEventListener("click", (e) => {
      e.preventDefault();
      if (invoiceCurrentPage === totalPages) return;
      invoiceCurrentPage = totalPages;
      fetchInvoices();
    });
    pagination.appendChild(lastLi);
  }

  // ==== View Invoice ====
  function viewInvoice(inv) {
    if (!inv) return;

    const setText = (id, value) => {
      const el = document.getElementById(id);
      if (el) el.textContent = value ?? "-";
    };

    // Open the modal
    const modalEl = document.getElementById("viewInvoiceModal");
    const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
    modalInstance.show();

    // Modal header
    setText("viewInvoiceModalLabel", `View Invoice #${inv.invoiceId}`);
    // Basic details
    setText("viewInvoiceName", StringUtils.toTitleCase(inv.name));
    setText("viewInvoicePhone", inv.phone);
    setText("viewInvoiceNumber", inv.invoiceNumber);
    setText("viewInvoiceDate", formatTimestamp(inv.invoiceDate, "DD-MM-YYYY"));
    setText("viewInvoiceDob", formatTimestamp(inv.dob, "DD-MM-YYYY"));
    setText("viewInvoiceAge", inv.age);
    setText("viewInvoicePlace", StringUtils.toTitleCase(inv.place));
    setText("viewInvoiceAmount", inv.amount);
    setText("viewInvoiceOffer", inv.offer);
    setText("viewInvoiceClaim", inv.claim);
    setText("viewInvoiceRemark", inv.remark);
    setText("viewInvoiceType", inv.invoiceType);
    setText("viewInvoicePaymentMode", StringUtils.capitalize(inv.paymentMode));
    setText("viewInvoiceFrame", inv.frame);
    setText("viewInvoiceLense", inv.lense);

    // Prescription fields
    const eyes = ["R", "L"];
    const fields = ["Sph", "Cyl", "Axis", "Via", "Add", "Pd"];
    eyes.forEach((eye) => {
      fields.forEach((f) => {
        const id = `viewInvoice${eye}${f}`;
        if (inv.power && inv.power[`${eye.toLowerCase()}${f}`] !== undefined) {
          setText(id, inv.power[`${eye.toLowerCase()}${f}`]);
        } else {
          setText(id, "-");
        }
      });
    });
  }

  // ==== Update Invoice ====
  function updateInvoice(inv) {
    invoiceIdToUpdate = inv.invoiceId ?? null;
    // Open the modal
    document.getElementById(
      "updateInvoiceModalLabel"
    ).textContent = `Update Invoice #${inv.invoiceId}`;
    const modalEl = document.getElementById("updateInvoiceModal");
    const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
    modalInstance.show();
    getAge(inv.dob, "updateInvoiceDob", "updateInvoiceAge");

    // Populate basic fields
    document.getElementById("updateInvoiceName").value =
      StringUtils.toTitleCase(inv.name) || "";
    document.getElementById("updateInvoicePhone").value = inv.phone || "";
    document.getElementById("updateInvoiceNumber").value =
      inv.invoiceNumber || "";
    document.getElementById("updateInvoiceDate").value = inv.invoiceDate || "";
    document.getElementById("updateInvoiceDob").value = inv.dob || "";
    document.getElementById("updateInvoicePlace").value = inv.place || "";
    document.getElementById("updateInvoiceAmount").value = inv.amount || "";
    document.getElementById("updateInvoiceOffer").value = inv.offer || "";
    document.getElementById("updateInvoiceClaim").value = inv.claim || "";
    document.getElementById("updateInvoiceRemark").value = inv.remark || "";

    // Populate prescription fields (Right Eye)
    document.getElementById("updateInvoiceRSph").value = inv.power.rSph ?? "-";
    document.getElementById("updateInvoiceRCyl").value = inv.power.rCyl || "";
    document.getElementById("updateInvoiceRAxis").value = inv.power.rAxis || "";
    document.getElementById("updateInvoiceRVia").value = inv.power.rVia || "";
    document.getElementById("updateInvoiceRAdd").value = inv.power.rAdd || "";
    document.getElementById("updateInvoiceRPd").value = inv.power.rPd || "";

    // Populate prescription fields (Left Eye)
    document.getElementById("updateInvoiceLSph").value = inv.power.lSph || "";
    document.getElementById("updateInvoiceLCyl").value = inv.power.lCyl || "";
    document.getElementById("updateInvoiceLAxis").value = inv.power.lAxis || "";
    document.getElementById("updateInvoiceLVia").value = inv.power.lVia || "";
    document.getElementById("updateInvoiceLAdd").value = inv.power.lAdd || "";
    document.getElementById("updateInvoiceLPd").value = inv.power.lPd || "";
    document.getElementById("updateInvoiceFrame").value = inv.frame || "";
    document.getElementById("updateInvoiceLense").value = inv.lence || "";
    document.getElementById("createInvoiceAmount").value = inv.amount || "";

    // Bootstrap event: runs when the modal is fully shown
    const updateInvoiceModal = document.getElementById("updateInvoiceModal");
    updateInvoiceModal.addEventListener("shown.bs.modal", function () {
      setSelectValue("updateInvoiceType", inv.invoiceType);
      setSelectValue("updateInvoicePaymentMode", inv.paymentMode);
      setSelectValue("updateInvoiceStatus", inv.invoiceStatus);
    });
  }

  // ==== Delete Modal ====
  function openDeleteModal(inv) {
    invoiceIdToDelete = inv.invoiceId;
    document.getElementById(
      "deleteInvoiceMessage"
    ).innerHTML = `Are you sure you want to delete <b>${inv.name}</b>?`;
    new bootstrap.Modal(document.getElementById("deleteInvoiceModal")).show();
  }

  // -------------------- Optical Picker --------------------
  document.addEventListener("click", function (e) {
    const picker = document.getElementById("optical-picker");

    // If no picker exists, exit
    if (!picker) return;

    const isPowerInput = e.target.classList.contains("power-input");
    const clickedInsidePicker = picker.contains(e.target);

    // 1️⃣ Show picker if clicking on a .power-input field
    if (isPowerInput) {
      showPicker(e.target);
      return; // prevent immediate hide check below
    }

    // 2️⃣ Hide picker if clicking outside of picker and not on any input
    if (!clickedInsidePicker && !isPowerInput) {
      picker.style.display = "none";
    }
  });

  // Initialize Select2
  $("#createInvoiceSearchInput").select2({
    theme: "bootstrap-5",
    placeholder: "Search customer...",
    allowClear: true,
    dropdownParent: $("#createInvoiceStaticBackdropModal"),
    ajax: {
      transport: function (params, success, failure) {
        const searchTerm = params.data.term || "";

        apiRequest(
          "GET",
          "/api/invoices",
          { q: searchTerm, limit: 10 },
          false,
          (res) => {
            const data = res.data?.invoices || [];
            // Map full object for later use
            success({
              results: data.map((inv) => ({
                id: inv.invoiceId,
                text: `${inv.name} (${inv.phone})`,
                name: inv.name,
                phone: inv.phone,
                place: inv.place || "",
                dob: inv.dob || "",
              })),
            });
          },
          (xhr) => {
            let message = "Error fetching customers.";
            if (xhr.responseText) {
              try {
                const res = JSON.parse(xhr.responseText);
                if (res.message) message = res.message;
              } catch {
                message = xhr.responseText;
              }
            }
            const icon = xhr.status >= 500 ? "error" : "warning";
            showToast(icon, message);
            console.error("API Error:", xhr);
            failure();
          }
        );
      },
      delay: 300,
      processResults: (data) => data,
      cache: true,
    },
  });

  // Focus the input field when Select2 opens
  $("#createInvoiceSearchInput").on("select2:open", () => {
    setTimeout(() => {
      document.querySelector(".select2-search__field").focus();
    }, 0);
  });

  // Handle Select2 selection — corrected to target the ID
  $("#createInvoiceSearchInput").on("select2:select", function (e) {
    const selectedItem = e.params.data;

    if (!selectedItem) {
      console.warn("⚠️ No item selected");
      return;
    }

    $("#createInvoiceName").val(selectedItem.name || "");
    $("#createInvoicePhone").val(selectedItem.phone || "");
    $("#createInvoicePlace").val(selectedItem.place || "");
    let dob = selectedItem.dob || "";
    // Validate DOB format — skip invalid or placeholder dates
    if (!dob || dob === "0000-00-00" || dob === "null" || dob === null) {
      dob = "";
    }
    // Safely set the date
    $("#createInvoiceDob").val(dob);
  });
});

let selectedInput = null;

/**
 * Creates and appends a button to the grid.
 */
function createAndAppendButton(grid, value, type, inputElement) {
  const isVision = type === "via";
  const btn = document.createElement("button");
  btn.className = `btn btn-sm ${
    isVision ? "btn-outline-success" : "btn-outline-primary"
  } optical-value-btn`;
  btn.textContent = value;
  btn.setAttribute("data-value", value); // Store the raw value for matching

  btn.onclick = () => {
    if (inputElement) {
      inputElement.value = btn.textContent;
      document.getElementById("optical-picker").style.display = "none";
      selectedInput = null; // Clear selected input
    }
  };
  grid.appendChild(btn);
}

/**
 * Generates the list of optical value buttons based on the type.
 */
function generateOpticalValues(type) {
  const grid = document.getElementById("optical-grid");
  grid.innerHTML = "";
  let values = [];
  let start, end, step;

  // --- 1. Generate all possible values ---
  switch (type) {
    case "sph":
    case "cyl":
      start = type === "sph" ? -20.0 : -10.0;
      end = type === "sph" ? 20.0 : 10.0;
      step = 0.25;
      for (
        let val = start;
        val <= end;
        val = Math.round((val + step) * 100) / 100
      ) {
        let formatted = val.toFixed(2);
        values.push((val > 0 ? "+" : "") + formatted);
      }
      break;

    case "axis":
      start = 0;
      end = 180;
      step = 5;
      for (let val = start; val <= end; val += step) {
        values.push(String(Math.round(val)));
      }
      break;

    case "add":
      start = 0.5;
      end = 4.0;
      step = 0.25;
      for (
        let val = start;
        val <= end;
        val = Math.round((val + step) * 100) / 100
      ) {
        let formatted = val.toFixed(2);
        values.push("+" + formatted);
      }
      break;

    // You didn't request 'via' but including for completeness
    case "via":
      values = ["6/60", "6/36", "6/24", "6/18", "6/12", "6/9", "6/6"];
      break;

    default:
      return;
  }

  // --- 2. Create and append buttons ---
  values.forEach((val) => {
    createAndAppendButton(grid, val, type, selectedInput);
  });
}

/**
 * Hides the picker and removes the click-outside listener.
 */
function hidePicker() {
  const picker = document.getElementById("optical-picker");
  picker.style.display = "none";
  if (selectedInput) {
    // Remove the highlight from the active input on close
    selectedInput.classList.remove("active-input");
    selectedInput = null;
  }
  // Remove the global event listener for click-outside
  document.removeEventListener("click", closePickerOnClickOutside);
}

/**
 * Hides the picker if the click is outside the picker and the selected input.
 * This function needs to be defined in the global scope.
 */
function closePickerOnClickOutside(event) {
  const picker = document.getElementById("optical-picker");
  // Check if the click was NOT on the picker and NOT on the selected input
  // The 'selectedInput' check is important to prevent the initial input click from immediately closing it.
  if (
    selectedInput &&
    !picker.contains(event.target) &&
    event.target !== selectedInput
  ) {
    hidePicker();
  }
}

/**
 * Shows the optical picker with enhanced responsive positioning and UX features.
 */
function showPicker(input) {
  // 1. Toggling: If the same input is clicked again, close it.
  if (selectedInput === input) {
    hidePicker();
    return;
  }

  // Hide any previously open picker and clear state
  if (selectedInput) {
    hidePicker();
  }

  selectedInput = input;
  selectedInput.classList.add("active-input"); // Add a class to highlight the active input

  const type = input.dataset.type;
  const picker = document.getElementById("optical-picker");
  const grid = document.getElementById("optical-grid");

  // 1. Generate all values
  // NOTE: This function (generateOpticalValues) must be defined elsewhere
  generateOpticalValues(type);

  const inputRect = input.getBoundingClientRect();
  const viewportWidth = window.innerWidth;
  const viewportHeight = window.innerHeight;
  const mobileBreakpoint = 576;

  // Temporarily show picker to measure height/width accurately
  picker.style.display = "block";
  picker.style.visibility = "hidden";
  picker.classList.remove("picker-mobile-fixed"); // Clean up mobile class

  if (viewportWidth < mobileBreakpoint) {
    // --- Mobile UX: Full-width fixed bottom sheet ---
    picker.classList.add("picker-mobile-fixed");
    picker.style.width = `100%`;
    picker.style.left = `0`;
    picker.style.top = "auto"; // Let CSS handle positioning

    // Add a close button for mobile convenience
    let closeBtn = document.getElementById("optical-picker-close");
    if (!closeBtn) {
      closeBtn = document.createElement("button");
      closeBtn.id = "optical-picker-close";
      closeBtn.textContent = "Done";
      closeBtn.className = "btn btn-secondary w-100 mt-2";
      closeBtn.onclick = hidePicker;
      picker.appendChild(closeBtn);
    } else {
      // Ensure it's visible if it already exists
      closeBtn.style.display = "block";
    }
  } else {
    // --- Desktop UX: Dropdown below input ---
    // Ensure mobile close button is hidden on desktop
    const closeBtn = document.getElementById("optical-picker-close");
    if (closeBtn) closeBtn.style.display = "none";

    // Set Dynamic Width to match the input
    picker.style.width = `${inputRect.width}px`;

    let left = inputRect.left + window.scrollX;
    let top = inputRect.bottom + window.scrollY + 5; // 5px offset

    // Horizontal bounds check
    const pickerWidth = picker.offsetWidth;
    if (left + pickerWidth > viewportWidth - 16) {
      left = viewportWidth - pickerWidth - 16;
      if (left < 0) left = 8;
    }

    picker.style.left = `${left}px`;
    picker.style.top = `${top}px`;
    picker.style.borderRadius = "0.25rem"; // normal corners
  }

  // 2. Scroll Centering Logic (remains unchanged)
  const currentValue = input.value.trim();
  const buttons = Array.from(grid.querySelectorAll(".optical-value-btn"));
  let targetButton = buttons.find(
    (btn) => btn.getAttribute("data-value") === currentValue
  );

  // Clear all previous highlights
  buttons.forEach((btn) => {
    btn.classList.remove("btn-primary");
    // Re-apply correct outline class
    const typeClass = btn.getAttribute("data-value").includes("/")
      ? "btn-outline-success"
      : "btn-outline-primary";
    btn.classList.add(typeClass);
  });

  if (targetButton) {
    // Apply Highlight
    targetButton.classList.remove("btn-outline-primary", "btn-outline-success");
    targetButton.classList.add("btn-primary");

    const buttonHeight = targetButton.offsetHeight;
    const pickerHeight = picker.clientHeight;

    const scrollOffset =
      targetButton.offsetTop - pickerHeight / 2 + buttonHeight / 2;
    picker.scrollTop = scrollOffset;
  } else {
    picker.scrollTop = 0;
  }

  // 3. Final visibility
  picker.style.visibility = "visible";

  // Add event listener to close the picker when clicking outside (Desktop UX)
  // Delay necessary to avoid immediate close
  if (viewportWidth >= mobileBreakpoint) {
    setTimeout(() => {
      document.addEventListener("click", closePickerOnClickOutside);
    }, 0);
  }
}
