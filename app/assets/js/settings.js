// === Retrieve Business Data from Session Storage ===
const businessData = sessionStorage.getItem("businessInfo");
let business = null;
let businessId = null;

try {
  if (businessData) {
    business = JSON.parse(businessData);
    businessId = business?.businessId || null;
  }
} catch (err) {
  console.error("Error parsing business info:", err);
  showToast("error", "Failed to load business data");
}

// === Populate Form Fields ===
function populateBusinessForm(businessObj) {
  if (!businessObj) return;

  const fieldMap = {
    businessName: businessObj.name || "",
    businessEmail: businessObj.email || "",
    businessPhone: businessObj.phone || "",
    businessAddress: businessObj.addr1 || "",
  };

  Object.entries(fieldMap).forEach(([id, value]) => {
    const el = document.getElementById(id);
    if (el) el.value = value;
  });

  // === Set Logo Preview ===
  const logoPreview = document.getElementById("logoPreview");
  if (logoPreview) {
    if (businessObj.logoPath && businessObj.logoPath.trim() !== "") {
      const logoUrl = `${window.location.origin}/app/backend/public${businessObj.logoPath}`;
      logoPreview.src = logoUrl;
      logoPreview.alt = `${businessObj.name || "Business"} Logo`;
    } else {
      logoPreview.src = "/app/assets/img/no-img.png"; // fallback placeholder
    }
  }
}

// === Initialize Form ===
if (business) {
  populateBusinessForm(business);
} else {
  showToast("warning", "No business data found");
}

// === Handle Business Form Submit ===
document
  .getElementById("businessForm")
  .addEventListener("submit", function (e) {
    e.preventDefault();

    const btn = document.getElementById("saveBusinessBtn");
    btn.disabled = true;

    if (!businessId) {
      showToast("warning", "Business ID missing. Please log in again.");
      btn.disabled = false;
      return;
    }

    // Collect form fields
    const name = document.getElementById("businessName").value.trim();
    const email = document.getElementById("businessEmail").value.trim();
    const phone = document.getElementById("businessPhone").value.trim();
    const addr1 = document.getElementById("businessAddress").value.trim();
    const logoFile = document.getElementById("businessLogo").files[0];

    // === Validate Email ===
    const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    if (email && !emailPattern.test(email)) {
      showToast("warning", "Please enter a valid email address.");
      btn.disabled = false;
      return;
    }

    // === Validate Phone ===
    const phonePattern = /^\d{10}$/;
    if (!phonePattern.test(phone)) {
      showToast("warning", "Please enter a valid 10-digit phone number."),
        (btn.disabled = false);
      return;
    }

    // === Prepare FormData ===
    const formData = new FormData();
    formData.append("name", name);
    formData.append("email", email);
    formData.append("phone", phone);
    formData.append("addr1", addr1);
    if (logoFile) formData.append("logo", logoFile);

    // === Send Request via apiRequest() ===
    apiRequest(
      "PUT",
      `/api/business/${businessId}`,
      formData,
      true, // isFormData
      function (response) {
        if (response.status) {
          const updatedBusiness = response.data || {};
          sessionStorage.setItem(
            "businessInfo",
            JSON.stringify(updatedBusiness)
          );
          business = updatedBusiness;
          populateBusinessForm(updatedBusiness);
          console.log(business);

          showToast(
            "success",
            response.message || "Business info updated successfully!"
          );
        } else {
          showToast(
            "warning",
            response.message || "Unable to update business info."
          );
        }

        btn.disabled = false;
      },
      function (xhr, status, error) {
        let message = "Failed to update business info. Please try again.";
        try {
          console.log("XHR Response Text:", xhr.responseText);
          if (xhr.responseJSON?.message) {
            message = xhr.responseJSON.message;
          } else if (xhr.responseText) {
            const trimmed = xhr.responseText.trim();
            if (trimmed.startsWith("<!DOCTYPE")) {
              message = "Server returned HTML (likely a PHP error).";
            } else if (trimmed.startsWith("{")) {
              const parsed = JSON.parse(trimmed);
              message = parsed.message || message;
            } else {
              message = trimmed;
            }
          }
        } catch (e) {
          console.error("Response parse error:", e);
        }

        showToast("error", message ?? "Update Failed");

        btn.disabled = false;
      }
    );
  });

// === File Upload Change Event ===
document
  .getElementById("businessLogo")
  .addEventListener("change", function (e) {
    const file = e.target.files[0];
    const preview = document.getElementById("logoPreview");

    if (file) {
      const allowedTypes = ["image/jpeg", "image/png", "image/webp"];
      if (!allowedTypes.includes(file.type)) {
        showToast("error", "Please upload an image file (JPG, PNG, WEBP).");
        e.target.value = "";
        preview.src = "/app/assets/img/no-img.png";
        return;
      }

      if (file.size > 1 * 1024 * 1024) {
        showToast("warning", "Logo must be less than 1MB.");
        e.target.value = "";
        preview.src = "/app/assets/img/no-img.png";
        return;
      }

      const reader = new FileReader();
      reader.onload = (event) => {
        preview.src = event.target.result;
      };
      reader.readAsDataURL(file);
    } else {
      preview.src = "/app/assets/img/no-img.png";
    }
  });

// === Clear Logo Button ===
document.getElementById("clearLogoBtn").addEventListener("click", () => {
  const input = document.getElementById("businessLogo");
  const preview = document.getElementById("logoPreview");
  input.value = "";
  preview.src = "/app/assets/img/no-img.png";
});
