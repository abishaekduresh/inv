<?php
require_once 'common.php';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= AppConfig::APP_NAME ?? null ?> - Login</title>
    <link rel="icon" href="./assets/img/favicon.png" type="image/png">
    <!-- Apple/Android devices -->
    <link rel="apple-touch-icon" href="./assets/img/favicon.png">
    <link rel="shortcut icon" href="./assets/img/favicon.png" type="image/png">

    <link href="assets/lib/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="assets/lib/sweetalert2/sweetalert2.min.css" />
    <link
      href="assets/lib/tabulator/dist/css/tabulator.min.css"
      rel="stylesheet"
    />
    <link href="assets/css/login.css" rel="stylesheet" />
  </head>
  <body
    class="d-flex align-items-center justify-content-center vh-100 bg-light"
  >
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">
          <div class="card shadow-lg border-0 rounded-3 overflow-hidden">
            <div class="row g-0">
              <!-- Left side (form) -->
              <div class="col-lg-6">
                <div class="p-5">
                  <div class="text-center mb-4">
                    <h3 class="fw-bold">Login</h3>
                    <p class="text-muted">Welcome back! Please sign in.</p>
                  </div>

                  <form id="loginForm" method="post">
                    <div class="mb-3">
                      <label for="inputPhone" class="form-label">Phone</label>
                      <input
                        type="number"
                        class="form-control"
                        id="inputPhone"
                        placeholder="Enter phone number"
                      />
                    </div>
                    <div class="mb-4">
                      <label for="inputPassword" class="form-label"
                        >Password</label
                      >
                      <input
                        type="password"
                        class="form-control"
                        id="inputPassword"
                        placeholder="Enter password"
                      />
                    </div>
                    <div
                      class="d-flex justify-content-between align-items-center"
                    >
                      <button
                        type="submit"
                        id="loginBtn"
                        class="btn btn-primary"
                      >
                        Login
                      </button>
                      <!-- <a href="#" class="text-decoration-none"
                        >Forgot password?</a
                      > -->
                    </div>
                  </form>
                </div>
              </div>

                <!-- Right side (branding / testimonial) -->
                <div
                  class="col-lg-6 d-none d-lg-flex bg-primary text-white align-items-center justify-content-center"
                >
                  <div class="p-4 text-center">
                    <h4 class="mb-3">Manage Your Optical Shop Invoice Effortlessly!</h4>
                    <p class="mb-3">
                      "This software has completely simplified our invoice
                      management. Quick, reliable, and perfect for optical stores."
                      <br/> Version: <?= AppConfig::API_VERSION ?? null ?>
                    </p>
                    <!--<p>- Optica Admin</p>-->
                  </div>
                </div>
            </div>
          </div>

          <p class="text-muted text-center mt-3 mb-0">
            Developed by
            <a href="#" class="text-primary">Duresh Tech</a>
          </p>
        </div>
      </div>
    </div>

    <!-- JS -->
    <script src="assets/lib/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/lib/jquery/jquery.min.js"></script>
    <script src="assets/lib/sweetalert2/sweetalert2.all.min.js"></script>
    <script src="assets/lib/tabulator/dist/js/tabulator.min.js"></script>
    <script src="assets/js/common.js"></script>
    <script src="assets/js/auth.js"></script>
  </body>
</html>
