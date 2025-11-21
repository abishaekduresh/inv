document.getElementById("logoutBtn").addEventListener("click", function () {
  apiRequest(
    "POST",
    "/api/auth/logout",
    null,
    false,
    function (res) {
      console.log("🧹 Logging out...");

      // Always clear site data regardless of API response
      try {
        // 1. Clear sessionStorage and localStorage
        sessionStorage.clear();
        localStorage.clear();

        // 2. Clear all cookies
        document.cookie.split(";").forEach((cookie) => {
          const cookieName = cookie.split("=")[0].trim();
          // Try to expire the cookie for all possible paths/domains
          document.cookie =
            cookieName +
            "=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/;SameSite=Lax;";
          document.cookie =
            cookieName +
            "=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/;domain=" +
            window.location.hostname +
            ";";
        });

        // 3. Clear IndexedDB databases (for apps using caching)
        if (window.indexedDB) {
          indexedDB.databases?.().then((dbs) => {
            (dbs || []).forEach((db) => {
              console.log("Deleting IndexedDB:", db.name);
              indexedDB.deleteDatabase(db.name);
            });
          });
        }

        // 4. Unregister all service workers (optional, for full clean logout)
        if ("serviceWorker" in navigator) {
          navigator.serviceWorker.getRegistrations().then((registrations) => {
            registrations.forEach((registration) => registration.unregister());
          });
        }

        console.log("Cleared all site data successfully!");
      } catch (err) {
        console.warn("⚠️ Error clearing site data:", err);
      }

      // 5. Show success message regardless of server response
      const message =
        (res && res.message) ||
        "Logged out and cleared all site data successfully";

      Swal.fire({
        toast: true,
        position: "top-end",
        icon: "success",
        title: message,
        showConfirmButton: false,
        timer: 1800,
        timerProgressBar: true,
        didClose: () => {
          // 6. Reload or redirect to login
          window.location.replace(HOST_ROUTE_PATH + "/login");
        },
      });
    },
    function (xhr, status, error) {
      // Handle logout request error — still clear everything
      console.error("❌ Logout request failed:", error);

      sessionStorage.clear();
      localStorage.clear();

      Swal.fire({
        toast: true,
        position: "top-end",
        icon: "error",
        title: "Logout failed, but local data was cleared.",
        showConfirmButton: false,
        timer: 1800,
        timerProgressBar: true,
        didClose: () => {
          window.location.replace(HOST_ROUTE_PATH + "/login");
        },
      });
    }
  );
});
