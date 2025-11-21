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
        console.log(response);
        const msg =
          response.message ||
          (response.body && response.body.message) ||
          "Login successful";

        if (response.status) {
          // Proceed to fetch business info
          const params = {
            id: response.data.businessId,
          };

          apiRequest(
            "GET",
            "/api/business",
            params,
            false,
            function (businessResponse) {
              const businessMsg =
                msg ||
                businessResponse.message ||
                "Business info fetched successfully";

              if (businessResponse.status) {
                // Store business info in localStorage
                sessionStorage.setItem(
                  "businessInfo",
                  JSON.stringify(businessResponse.data.records[0])
                );

                Swal.fire({
                  toast: true,
                  position: "top-end",
                  icon: "success",
                  title: businessMsg,
                  showConfirmButton: false,
                  timer: 1500,
                  timerProgressBar: true,
                  didClose: () => {
                    window.location.href = HOST_ROUTE_PATH + "/dashboard";
                  },
                });
              } else {
                // Business fetch failed
                Swal.fire({
                  toast: true,
                  position: "top-end",
                  icon: "error",
                  title: businessMsg || "Failed to fetch business info",
                  showConfirmButton: false,
                  timer: 1500,
                  timerProgressBar: true,
                  didClose: () => {
                    // Redirect to login after short delay
                    setTimeout(() => {
                      window.location.href = HOST_ROUTE_PATH + "/login";
                    }, 300);
                  },
                });

                $loginBtn.prop("disabled", false).html("Login");
              }
            },
            function (xhr, status, error) {
              $loginBtn.prop("disabled", false).html("Login");

              let msg = "Server error. Please try again.";

              try {
                const res = JSON.parse(xhr.responseText);
                if (res.message) msg = res.message;
              } catch (e) {}

              Swal.fire({
                toast: true,
                position: "top-end",
                icon: "error",
                title: msg,
                showConfirmButton: false,
                timer: 1500,
                timerProgressBar: true,
              });
            }
          );
        } else {
          // Login failed
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
        // Network or server error
        $loginBtn.prop("disabled", false).html("Login");

        let msg = "Server error. Please try again.";

        try {
          const res = JSON.parse(xhr.responseText);
          if (res.message) msg = res.message;
        } catch (e) {}

        Swal.fire({
          toast: true,
          position: "top-end",
          icon: "error",
          title: msg,
          showConfirmButton: false,
          timer: 1500,
          timerProgressBar: true,
        });
      }
    );
  });
});
