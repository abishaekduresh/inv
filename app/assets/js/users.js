document.addEventListener("DOMContentLoaded", function () {
  let userIdToDelete = null;
  let userCurrentPage = 1;
  const userPageSize = 10;

  // ==== INIT ====
  initUsersPage();

  // -------------------- CREATE USER --------------------
  document.addEventListener("click", function (e) {
    // ✅ CREATE USER
    if (e.target && e.target.id === "newUserFormBtn") {
      const btn = e.target;
      const form = document.getElementById("newUserForm");
      if (!form) return;

      const data = {
        name: form.newUserName.value.trim(),
        phone: form.newUserPhone.value.trim(),
        role: form.newUserRole.value,
        password: form.newUserPassword.value,
        confirmPassword: form.newUserConfirmPassword.value,
      };

      if (
        !data.name ||
        !data.phone ||
        !data.role ||
        !data.password ||
        !data.confirmPassword
      ) {
        Swal.fire("Error", "Please fill in all required fields.", "error");
        return;
      }

      if (data.password !== data.confirmPassword) {
        Swal.fire("Error", "Passwords do not match!", "error");
        return;
      }

      btn.disabled = true;

      apiRequest(
        "POST",
        "/api/users",
        data,
        false,
        (res) => {
          Swal.fire({
            toast: true,
            icon: "success",
            text: res.message || "New user created successfully!",
            position: "top-end",
            showConfirmButton: false,
            timer: 1500,
            timerProgressBar: true,
          });

          form.reset();
          bootstrap.Modal.getInstance(
            document.getElementById("newUserStaticBackdropModal")
          )?.hide();

          btn.disabled = false;
          fetchUsers();
        },
        (xhr) => handleApiError(xhr, btn, "Error creating user.")
      );
    }

    // ✅ UPDATE USER
    if (e.target && e.target.id === "updateUserFormBtn") {
      const btn = e.target;
      const form = document.getElementById("updateUserForm");
      if (!form) return;

      const userId = form.dataset.userId;
      const data = {
        name: form.updateUserName.value.trim(),
        phone: form.updateUserPhone.value.trim(),
        role: form.updateUserRole.value,
        status: form.updateUserStatus.value,
      };

      if (!data.name || !data.phone || !data.role) {
        Swal.fire("Error", "Please fill in all required fields.", "error");
        return;
      }

      btn.disabled = true;
      apiRequest(
        "PUT",
        `/api/users/${userId}`,
        data,
        false,
        (res) => {
          Swal.fire({
            toast: true,
            icon: "success",
            text: res.message || "User updated successfully!",
            position: "top-end",
            showConfirmButton: false,
            timer: 1500,
            timerProgressBar: true,
          });

          form.reset();
          bootstrap.Modal.getInstance(
            document.getElementById("updateUserModal")
          )?.hide();

          btn.disabled = false;
          fetchUsers();
        },
        (xhr) => handleApiError(xhr, btn, "Error updating user.")
      );
    }

    // ✅ DELETE USER
    if (e.target && e.target.id === "deleteUserBtn") {
      if (!userIdToDelete) return;
      const btn = e.target;
      btn.disabled = true;

      apiRequest(
        "DELETE",
        `/api/users/${userIdToDelete}`,
        {},
        false,
        (res) => {
          Swal.fire({
            toast: true,
            icon: "success",
            text:
              res.message || `User #${userIdToDelete} deleted successfully!`,
            position: "top-end",
            showConfirmButton: false,
            timer: 1500,
            timerProgressBar: true,
          });

          bootstrap.Modal.getInstance(
            document.getElementById("deleteUserModal")
          )?.hide();

          btn.disabled = false;
          userIdToDelete = null;
          fetchUsers();
        },
        (xhr) => handleApiError(xhr, btn, "Error deleting user.")
      );
    }
  });

  // ==== INIT USERS PAGE ====
  function initUsersPage() {
    const searchInput = document.getElementById("searchInput");
    const statusFilter = document.getElementById("statusFilter");

    if (!searchInput || !statusFilter) return;

    // Search input listener
    searchInput.addEventListener("keyup", () => {
      userCurrentPage = 1;
      fetchUsers();
    });

    // Status filter listener
    statusFilter.addEventListener("change", () => {
      userCurrentPage = 1;
      fetchUsers();
    });

    // Initial fetch
    fetchUsers();
  }

  // ==== FETCH USERS ====
  function fetchUsers() {
    const search = document.getElementById("searchInput")?.value.trim() || "";
    const status = document.getElementById("statusFilter")?.value || "";

    const params = {
      q: search,
      sts: status,
      page: userCurrentPage,
      limit: userPageSize,
    };

    apiRequest(
      "GET",
      "/api/users",
      params,
      false,
      (res) => {
        const users = res.data?.users || [];
        const totalRecords = res.pagination?.totalRecords || 0;
        renderUsersTable(users, totalRecords);
        renderPagination(totalRecords);
      },
      (xhr) => handleApiError(xhr, null, "Error fetching users.")
    );
  }

  // ==== RENDER TABLE ====
  function renderUsersTable(users, totalRecords = 0) {
    const tbody = document.getElementById("usersTableBody");
    const mobileContainer = document.getElementById("usersCardsContainer");
    const empty = document.getElementById("emptyState");
    const count = document.getElementById("totalCount");

    if (!tbody || !mobileContainer || !count) return;

    tbody.innerHTML = "";
    mobileContainer.innerHTML = "";
    count.textContent = `${totalRecords} users`;

    if (!users.length) {
      empty.classList.remove("d-none");
      return;
    } else {
      empty.classList.add("d-none");
    }

    users.forEach((user, i) => {
      const sn = (userCurrentPage - 1) * userPageSize + i + 1;
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td>${sn}</td>
        <td>${user.userId}</td>
        <td>${StringUtils.toTitleCase(user.name)}</td>
        <td>${user.phone}</td>
        <td>${StringUtils.capitalize(user.role) || "-"}</td>
        <td>
          <span class="badge ${
            user.status === "active" ? "bg-success" : "bg-secondary"
          }">${StringUtils.capitalize(user.status)}</span>
        </td>
        <td>${formatUnix(user.createdAt, "d-m-Y")}</td>
        <td>
          <button class="btn btn-sm btn-primary me-1" data-id="${
            user.userId
          }"><i class="fa-solid fa-user-pen"></i></button>
          <button class="btn btn-sm btn-danger" data-id="${
            user.userId
          }"><i class="fa-solid fa-trash"></i></button>
        </td>
      `;

      const [editBtn, delBtn] = tr.querySelectorAll("button");
      editBtn.addEventListener("click", () => editUser(user));
      delBtn.addEventListener("click", () => deleteUser(user));

      tbody.appendChild(tr);
    });
  }

  // ==== PAGINATION ====
  function renderPagination(totalItems) {
    const pagination = document.getElementById("pagination");
    if (!pagination) return;

    pagination.innerHTML = "";
    const totalPages = Math.ceil(totalItems / userPageSize);
    if (totalPages <= 1) return;

    for (let i = 1; i <= totalPages; i++) {
      const li = document.createElement("li");
      li.className = `page-item ${i === userCurrentPage ? "active" : ""}`;
      li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
      li.addEventListener("click", (e) => {
        e.preventDefault();
        if (userCurrentPage === i) return;
        userCurrentPage = i;
        fetchUsers();
      });
      pagination.appendChild(li);
    }
  }

  // ==== EDIT USER ====
  function editUser(user) {
    const modalEl = document.getElementById("updateUserModal");
    const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
    modalInstance.show();

    document.getElementById(
      "updateUserModalLabel"
    ).textContent = `Update User #${user.userId}`;
    document.getElementById("updateUserName").value = user.name || "";
    document.getElementById("updateUserPhone").value = user.phone || "";
    document.getElementById("updateUserRole").value = user.role || "";
    document.getElementById("updateUserForm").dataset.userId = user.userId;

    setSelectValue("updateUserStatus", user.status);
  }

  // ==== DELETE USER ====
  function deleteUser(user) {
    userIdToDelete = user.userId;
    document.getElementById(
      "deleteUserModalLabel"
    ).textContent = `Delete User #${user.userId}`;
    document.getElementById(
      "deleteUserMessage"
    ).innerHTML = `Are you sure you want to delete <b>${user.name}</b>?`;
    new bootstrap.Modal(document.getElementById("deleteUserModal")).show();
  }

  // ==== HELPER ====
  function setSelectValue(selectId, value) {
    const selectEl = document.getElementById(selectId);
    if (!selectEl) return;

    const val = (value || "").toLowerCase();
    for (let option of selectEl.options) {
      if (option.value.toLowerCase() === val) {
        selectEl.value = option.value;
        return;
      }
    }
    selectEl.value = "";
  }

  function handleApiError(xhr, btn, defaultMsg) {
    let message = defaultMsg;
    if (xhr.responseText) {
      try {
        const res = JSON.parse(xhr.responseText);
        if (res.message) message = res.message;
      } catch {
        message = xhr.responseText;
      }
    }
    Swal.fire({
      toast: true,
      icon: xhr.status >= 500 ? "error" : "warning",
      text: message,
      position: "top-end",
      showConfirmButton: false,
      timer: 1500,
      timerProgressBar: true,
    });
    if (btn) btn.disabled = false;
  }
});
