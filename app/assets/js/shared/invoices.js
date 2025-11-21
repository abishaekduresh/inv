function getInvoiceId() {
  const params = new URLSearchParams(window.location.search);
  const queryId = params.get("id");
  if (queryId) return queryId;

  const parts = window.location.pathname.split("/").filter(Boolean);
  return parts[parts.length - 1] || null;
}

const invoiceId = getInvoiceId();

function checkInvoice() {
  if (!invoiceId) {
    Swal.fire({
      icon: "info",
      title: "Share Invoice",
      text: "No invoice ID found in the URL!",
    });
    showToast("warning", "Missing invoice ID");
    return; // Allowed inside function
  }
}

checkInvoice(); // Run the function

// 🔹 Share Invoice on WhatsApp
function shareOnWhatsapp(invoiceId) {
  Swal.fire({
    icon: "info",
    title: "Share Invoice",
    text: `Invoice #${invoiceId} shared on WhatsApp!`,
  });
}

// 🔹 Render Invoices Dynamically
function renderInvoices(invoices, businessInfo = null) {
  const container = document.getElementById("invoiceContainer");
  container.innerHTML = "";
  const logoUrl = businessInfo["logoPath"]
    ? `${window.location.origin}/app/backend/public${businessInfo["logoPath"]}`
    : "../assets/img/no-img.png";
  const businessName = businessInfo["name"] ?? null;
  const businessPhone = businessInfo["phone"] ?? null;
  const businessAddress = businessInfo["addr1"] ?? null;
  const businessEmail = businessInfo["email"] ?? null;
  const businessEmailOutput = `${
    businessEmail ? `Email: ${businessEmail}` : ""
  }`;
  setFavicon(logoUrl);
  if (!invoices || invoices.length === 0) {
    Swal.fire({
      icon: "warning",
      title: "No invoices found",
      text: "No invoices are available to display.",
      toast: true,
      position: "top-end",
      timer: 2000,
      showConfirmButton: false,
      timerProgressBar: true,
    });
    return;
  }

  invoices.forEach((inv) => {
    const div = document.createElement("div");
    div.className = "invoice-card";

    div.innerHTML = `
      <div class="business-header">
        <div class="business-logo">
          <img src="${logoUrl}" alt="Business Logo">
        </div>
        <div class="business-details">
          <strong>${businessName}</strong><br>
          ${businessAddress}<br>
          Phone: +91 ${businessPhone}<br>
          ${businessEmailOutput}
        </div>
      </div>

      <div class="invoice-header">
        <div>
          <h5>Invoice #${inv.invoiceNumber ?? "-"}</h5>
          <small>${inv.invoiceDate ?? "-"}</small>
        </div>
        <span class="badge badge-type">${inv.invoiceType ?? "-"}</span>
      </div>

      <div class="card-body">
        <div class="mb-4">
          <h6 class="section-title">Patient Info</h6>
          <div class="row g-2 info-row">
            <div class="col-6 col-md-4"><strong>Name:</strong> ${
              inv.name ?? "-"
            }</div>
            <div class="col-6 col-md-4"><strong>Phone:</strong> ${
              inv.phone ?? "-"
            }</div>
            <div class="col-6 col-md-4"><strong>DOB:</strong> ${
              inv.dob ?? "-"
            }</div>
            <div class="col-6 col-md-4"><strong>Age:</strong> ${
              inv.age ?? "-"
            }</div>
            <div class="col-12 col-md-8"><strong>Place:</strong> ${
              inv.place ?? "-"
            }</div>
          </div>
        </div>

        <div class="mb-4">
          <h6 class="section-title">Prescription</h6>
          <div class="table-responsive">
            <table class="table table-sm power-table">
              <thead class="table-light">
                <tr>
                  <th>Eye</th><th>Sph</th><th>Cyl</th><th>Axis</th><th>Via</th><th>Add</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Right</td>
                  <td>${inv.power.rSph}</td>
                  <td>${inv.power.rCyl}</td>
                  <td>${inv.power.rAxis}</td>
                  <td>${inv.power.rVia}</td>
                  <td>${inv.power.rAdd}</td>
                </tr>
                <tr>
                  <td>Left</td>
                  <td>${inv.power.lSph}</td>
                  <td>${inv.power.lCyl}</td>
                  <td>${inv.power.lAxis}</td>
                  <td>${inv.power.lVia}</td>
                  <td>${inv.power.lAdd}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="mb-4">
          <h6 class="section-title">Product & Payment</h6>
          <div class="row g-2 info-row">
            <div class="col-6 col-md-4"><strong>Frame:</strong> ${
              inv.frame ?? "-"
            }</div>
            <div class="col-6 col-md-4"><strong>Lens:</strong> ${
              inv.lence ?? "-"
            }</div>
            <div class="col-6 col-md-4"><strong>Amount:</strong> ₹${
              inv.amount ?? "-"
            }</div>
            <!--div class="col-6 col-md-4"><strong>Offer:</strong> ${
              inv.offer ?? "-"
            }</div>
            <div class="col-6 col-md-4"><strong>Claim:</strong> ${
              inv.claim ?? "-"
            }</div>
            <div class="col-6 col-md-4"><strong>Payment Mode:</strong> ${
              inv.paymentMode ?? "-"
            }</div>
            <div class="col-12"><strong>Remark:</strong> ${
              inv.remark ?? "-"
            }</div-->
          </div>
        </div>
      </div>

      <div class="card-footer d-flex flex-wrap gap-2 justify-content-center">
        <!--a href="https://wa.me/+91${businessPhone}?text=Hello%20${
      inv.name ?? "-"
    }" target="_blank" class="btn btn-success d-flex align-items-center gap-1 flex-grow-1 flex-md-grow-0">
          <i class="fa-brands fa-whatsapp"></i> WhatsApp
        </a-->
        <a href="tel:+91${businessPhone}" class="btn btn-primary d-flex align-items-center gap-1 flex-grow-1 flex-md-grow-0">
          <i class="bi bi-telephone-fill"></i> Call
        </a>
        <!--a href="/viewInvoice.php?id=${
          inv.invoiceId ?? "-"
        }" class="btn btn-info d-flex align-items-center gap-1 flex-grow-1 flex-md-grow-0 text-white">
          <i class="bi bi-eye"></i> View
        </a>
        <a href="mailto:info@opticalshop.com?subject=Invoice%20${
          inv.invoiceNumber ?? "-"
        }" class="btn btn-warning d-flex align-items-center gap-1 flex-grow-1 flex-md-grow-0 text-dark">
          <i class="bi bi-envelope-fill"></i> Email
        </a-->
      </div>
    `;

    container.appendChild(div);
  });
}

// 🔹 API Request
apiRequest(
  "GET",
  `/api/shared/invoices/${invoiceId}`,
  {},
  false,
  function onSuccess(response) {
    const invoices = response.data?.invoices || [];
    const businessInfo = response.data?.businessInfo || null;
    document.title = `#${invoices[0].invoiceNumber ?? null} | Shared Invoice`;
    renderInvoices(invoices, businessInfo);
  },
  function onError(xhr) {
    let message = "Something went wrong.";
    let icon = "warning";

    if (xhr.responseText) {
      try {
        const res = JSON.parse(xhr.responseText);
        if (res.message) message = res.message;
      } catch (e) {
        message = xhr.responseText || message;
      }
    }

    if (xhr.status >= 500) icon = "error";
    else if (xhr.status >= 400 && xhr.status < 500) icon = "warning";
    else icon = "info";

    Swal.fire({
      toast: true,
      icon: icon,
      text: message,
      showConfirmButton: false,
    });
  }
);
