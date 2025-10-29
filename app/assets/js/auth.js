$(document).ready(function () {
  $("#loginForm").on("submit", function (e) {
    e.preventDefault();

    const phone = $("#inputPhone").val().trim();
    const password = $("#inputPassword").val().trim();
    const $loginBtn = $("#loginBtn");

    if (!phone || !password) {
      Swal.fire({
        toast: true,
        position: "top-end",
        icon: "warning",
        title: "Please enter phone and password",
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
      });
      return;
    }

    // Disable button & show loader
    $loginBtn
      .prop("disabled", true)
      .html(
        `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Processing...`
      );

    const payload = { phone, password };

    apiRequest(
      "POST",
      "/api/auth/users/login",
      payload,
      false,
      function (response) {
        const msg =
          response.message ||
          (response.body && response.body.message) ||
          "Login successful";

        if (response.status) {
          Swal.fire({
            toast: true,
            position: "top-end",
            icon: "success",
            title: msg,
            showConfirmButton: false,
            timer: 1500,
            timerProgressBar: true,
            didClose: () => {
              window.location.href = HOST_ROUTE_PATH + "/dashboard";
            },
          });
        } else {
          $loginBtn.prop("disabled", false).html("Login");

          Swal.fire({
            toast: true,
            position: "top-end",
            icon: "error",
            title: msg || "Login failed",
            showConfirmButton: false,
            timer: 1500,
            timerProgressBar: true,
          });
        }
      },
      function (xhr, status, error) {
        // Restore button
        $loginBtn.prop("disabled", false).html("Login");

        let msg = "Server error. Please try again.";

        // Try to parse JSON error response (like 401)
        try {
          const res = JSON.parse(xhr.responseText);
          if (res.message) msg = res.message;
        } catch (e) {
          //   console.error("Error parsing API error response:", e);
        }

        Swal.fire({
          toast: true,
          position: "top-end",
          icon: "error",
          title: msg,
          showConfirmButton: false,
          timer: 1500,
          timerProgressBar: true,
        });

        // console.error("API Error:", status, error, xhr.responseText);
      }
    );
  });
});
