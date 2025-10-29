document.getElementById("logoutBtn").addEventListener("click", function () {
  apiRequest(
    "POST",
    "/api/auth/logout", // ✅ relative to BASE_API_URL
    {},
    false,
    function (res) {
      if (res.status) {
        Swal.fire({
          toast: true,
          // position: "top-end",
          icon: "success",
          title: res.message || "You have been logged out successfully",
          showConfirmButton: false,
          timer: 1500,
          timerProgressBar: true,
          didClose: () => {
            window.location.href = HOST_ROUTE_PATH + "/logout";
          },
        });
      } else {
        Swal.fire("Error", res.error || "Logout failed", "error");
      }
    },
    function (xhr, status, error) {
      Swal.fire("Error", "Logout request failed: " + error, "error");
    }
  );
});
