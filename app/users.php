<?php
include_once 'header.php';
?>

<div class="container-fluid py-1">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div class="mb-2 mb-md-0">
                            <h1 class="h3 mb-1">
                                <i class="bi bi-people-fill"></i>
                                Manage Users
                            </h1>
                            <p class="text-white mb-0"></p>
                        </div>
                        <!-- <button class="btn btn-light" onclick="showNewUserForm()"> -->
                        <button class="btn btn-light"  data-bs-toggle="modal" data-bs-target="#newUserStaticBackdropModal">
                            <i class="bi bi-person-add"></i>
                            Add New User
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Search and Filter Section -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" class="form-control" id="searchInput" placeholder="Search by name, phone..." onkeyup="fetchUsers()">
                </div>
            </div>
            <div class="col-md-4 mt-2 mt-md-0">
                <select class="form-select" id="statusFilter" onchange="fetchUsers()">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Users List</h5>
                <span class="badge bg-secondary" id="totalCount">0 users</span>
            </div>
            <div class="card-body p-0">
                <!-- Desktop Table -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>User ID</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="d-md-none" id="usersCardsContainer"></div>

                <!-- Empty State -->
                <div class="text-center py-5 d-none" id="emptyState">
                    <i class="bi bi-person-x display-1 text-muted"></i>
                    <h4 class="text-muted mt-3">No Users Found</h4>
                    <p class="text-muted">Try adjusting your search criteria</p>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <nav aria-label="User pagination" class="mt-4">
            <ul class="pagination justify-content-center" id="pagination">
            </ul>
        </nav>
    </div>

    <!-- Create New User Modal -->
    <div class="modal fade" 
        id="newUserStaticBackdropModal" 
        data-bs-backdrop="static" 
        data-bs-keyboard="false" 
        tabindex="-1" 
        aria-labelledby="newUserStaticBackdropModalLabel" 
        aria-hidden="true">

        <!-- Fullscreen modal -->
        <div class="modal-dialog modal-fullscreen-sm-down">
            <div class="modal-content shadow-lg rounded-4">
            
            <!-- Modal Header -->
            <div class="modal-header bg-primary text-white shadow-sm">
                <h5 class="modal-title" id="newUserStaticBackdropModalLabel">Create New User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 bg-light">
                <div class="container">
                    <form id="newUserForm" class="row g-3">

                        <!-- Name -->
                        <div class="col-md-6">
                            <label for="newUserName" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control shadow-sm" id="newUserName" name="newUserName" placeholder="Enter full name" required>
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6">
                            <label for="newUserPhone" class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control shadow-sm" id="newUserPhone" name="newUserPhone" placeholder="Enter phone number" pattern="[0-9]{10}" required>
                        </div>

                        <!-- Role -->
                        <div class="col-md-12">
                            <label for="newUser" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select shadow-sm" id="newUserRole" name="newUserRole" required>
                                <option value="" selected>Select role</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>

                        <!-- Password -->
                        <div class="col-md-6">
                            <label for="newUserPassword" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control shadow-sm" id="newUserPassword" name="newUserPassword" placeholder="Enter password" required>
                        </div>

                        <!-- Confirm Password -->
                        <div class="col-md-6">
                            <label for="newUserConfirmPassword" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control shadow-sm" id="newUserConfirmPassword" name="newUserConfirmPassword" placeholder="Confirm password" required>
                        </div>

                        <!-- Terms Checkbox -->
                        <!-- <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input shadow-sm" type="checkbox" id="termsCheck" required>
                                <label class="form-check-label" for="termsCheck">
                                I agree to the terms and conditions
                                </label>
                            </div>
                        </div> -->

                        <!-- Submit Button -->
                        <div class="col-12 text-center mt-3">
                            <button type="button" class="btn btn-success btn-lg shadow-sm px-5" id="newUserFormBtn"><i class="fa-solid fa-square-plus"></i> Create User</button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer justify-content-between shadow-sm">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="fa-solid fa-ban"></i> Cancel</button>
            </div>

            </div>
        </div>
    </div>

    <!-- Update User Modal -->
    <div class="modal fade" 
        id="updateUserModal" 
        data-bs-backdrop="static" 
        data-bs-keyboard="false" 
        tabindex="-1" 
        aria-labelledby="updateUserModalLabel" 
        aria-hidden="true">

        <!-- Fullscreen modal -->
        <div class="modal-dialog modal-fullscreen-sm-down">
            <div class="modal-content shadow-lg rounded-4">
            
            <!-- Modal Header -->
            <div class="modal-header bg-primary text-white shadow-sm">
                <h5 class="modal-title" id="updateUserModalLabel">Update User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 bg-light">
                <div class="container">
                    <form id="updateUserForm" class="row g-3">

                        <!-- Name -->
                        <div class="col-md-6">
                            <label for="updateUserName" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control shadow-sm" id="updateUserName" name="updateUserName" placeholder="Enter full name" required>
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6">
                            <label for="updateUserPhone" class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control shadow-sm" id="updateUserPhone" name="updateUserPhone" placeholder="Enter phone number" pattern="[0-9]{10}" required>
                        </div>

                        <!-- Status -->
                        <div class="col-md-12">
                            <label for="updateUserStatus" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select shadow-sm" id="updateUserStatus" name="updateUserStatus" required>
                                <option value="" selected>Select status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <!-- Role -->
                        <div class="col-md-12">
                            <label for="updateUserRole" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select shadow-sm" id="updateUserRole" name="updateUserRole" required>
                                <option value="" selected>Select role</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>

                        <!-- Submit Button -->
                        <div class="col-12 text-center mt-3">
                            <button type="button" class="btn btn-primary btn-lg shadow-sm px-5" id="updateUserFormBtn"><i class="fa-solid fa-floppy-disk"></i> Update User</button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer justify-content-between shadow-sm">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="fa-solid fa-ban"></i> Cancel</button>
            </div>

            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg rounded-4">
            
            <!-- Modal Header -->
            <div class="modal-header bg-danger text-white shadow-sm">
                <h5 class="modal-title" id="deleteUserModalLabel">Delete User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Modal Body -->
            <div class="modal-body">
                <p id="deleteUserMessage">Are you sure you want to delete this user?</p>
                <p class="text-danger mb-0"><strong>Note: This action cannot be undone.</strong></p>
            </div>
            
            <!-- Modal Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                <i class="fa-solid fa-ban"></i> Cancel
                </button>
                <button type="button" class="btn btn-danger" id="deleteUserBtn">
                <i class="fa-solid fa-trash-can"></i> Delete
                </button>
            </div>
            
            </div>
        </div>
    </div>

</div>

  <script src="./assets/js/users.js"></script>

<?php
  include_once 'footer.php';
?>
