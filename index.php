git<?php
include 'db.php';
session_start();

// Redirect if user is already logged in and has a valid type
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    $user_type = $_SESSION['user_type'];

    if ($user_type === 'student') {
        header("Location: index.php");
        exit();
    } elseif ($user_type === 'donor' || $user_type === 'foundation') {
        header("Location: provider_home.php");
        exit();
    }
}

// If session exists but user_type missing, clear it to prevent redirect loop
if (isset($_SESSION['user_id']) && !isset($_SESSION['user_type'])) {
    session_unset();
    session_destroy();
}

// Initialize messages
$login_error = '';
$register_error = '';
$register_success = false;

// --- LOGIN HANDLER (with login success toast support) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['name'] = $user['email'];

            // Add login success message (shown after redirect in next page)
            $_SESSION['login_success'] = "Login successful! Welcome back.";

            if ($user['user_type'] === 'student') {
                header("Location: Home.php");
            } elseif ($user['user_type'] === 'donor' || $user['user_type'] === 'foundation') {
                header("Location: provider_home.php");
            } else {
                session_destroy();
                header("Location: index.php");
            }
            exit();
        } else {
            $login_error = "Incorrect password.";
        }
    } else {
        $login_error = "No account found with that email.";
    }
}

// --- REGISTRATION HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm = trim($_POST['confirm_password']);
    $user_type = $_POST['user_type'];

    if ($password !== $confirm) {
        $register_error = "Passwords do not match.";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check_result = $check->get_result();

        if ($check_result->num_rows > 0) {
            $register_error = "Email already exists. Please use another email.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (email, password, user_type) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $email, $hash, $user_type);

            if ($stmt->execute()) {
                $user_id = $conn->insert_id;

                session_regenerate_id(true);
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_type'] = $user_type;
                $_SESSION['name'] = $email;

                if ($user_type === 'student') {
                    header("Location: Welcome.php");
                } elseif ($user_type === 'donor' || $user_type === 'foundation') {
                    header("Location: provider_home.php");
                } else {
                    session_destroy();
                    header("Location: index.php");
                }
                exit();
            } else {
                $register_error = "An error occurred. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ISKOLar – Empowering Education</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
  :root {
    --iskolar-primary: #0055FF;
    --iskolar-secondary: #FDC500;
    --iskolar-dark: #012A4A;
    --iskolar-light: #F8F9FA;
  }

  body {
    font-family: 'Poppins', sans-serif;
    background-color: var(--iskolar-light);
    scroll-behavior: smooth;
  }

  /* Navbar */
  .navbar {
    transition: all 0.4s ease;
    padding: 15px 0;
  }
  .navbar .nav-link {
    color: white !important;
    font-weight: 500;
    margin: 0 10px;
  }
  .navbar.scrolled {
    background-color: white !important;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  }
  .navbar.scrolled .nav-link {
    color: var(--iskolar-dark) !important;
  }
  .btn-register {
    color: white !important;
    border: 2px solid white;
    border-radius: 50px;
    font-weight: 500;
    transition: all 0.3s ease;
    background: transparent;
  }
  .btn-register:hover {
    background: white;
    color: var(--iskolar-primary) !important;
  }
  .navbar.scrolled .btn-register {
    color: var(--iskolar-primary) !important;
    border: 2px solid var(--iskolar-primary);
  }
  .navbar.scrolled .btn-register:hover {
    background: var(--iskolar-primary);
    color: white !important;
  }
  .btn-login {
    background: var(--iskolar-secondary);
    color: var(--iskolar-dark);
    border-radius: 50px;
    font-weight: 600;
    transition: all 0.3s ease;
  }
  .btn-login:hover {
    background: #e6bf00;
    transform: translateY(-2px);
  }
  .navbar-toggler-icon {
    filter: invert(1);
  }
  .navbar.scrolled .navbar-toggler-icon {
    filter: invert(0);
  }
  .hero {
    height: 100vh;
    background: linear-gradient(rgba(1,42,74,0.85), rgba(0,85,255,0.6)), url('images/hero-bg.png') center/cover no-repeat;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 0 20px;
  }
  .hero-content {
    max-width: 700px;
  }
  .hero .btn-get-started {
    background: var(--iskolar-secondary);
    color: var(--iskolar-dark);
    border-radius: 50px;
    padding: 12px 40px;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.3s ease;
  }
  .hero .btn-get-started:hover {
    background: #e6bf00;
    transform: translateY(-3px);
  }
  #foundation {
    background-color: var(--iskolar-light);
    padding: 80px 0;
  }
  footer {
    background-color: var(--iskolar-dark);
    color: white;
  }
  </style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg fixed-top" id="mainNavbar">
  <div class="container">
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav align-items-lg-center">
        <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="#foundation">About</a></li>
        <li class="nav-item ms-lg-3">
          <button class="btn btn-register btn-sm" data-bs-toggle="modal" data-bs-target="#registerModal">Register</button>
        </li>
        <li class="nav-item ms-2">
          <button class="btn btn-login btn-sm" data-bs-toggle="modal" data-bs-target="#loginModal">Login</button>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- HERO -->
<section id="home" class="hero">
  <div class="hero-content">
    <img src="images/ISKOLAR_LOGO.png" width="130" class="mb-4">
    <h1 class="fw-bold display-4 mb-3">Empowering <span class="text-warning">Education</span></h1>
    <p class="lead mb-4">Connecting Filipino students with life-changing scholarship opportunities.</p>
    <button class="btn-get-started btn" id="getStartedBtn">Get Started</button>
  </div>
</section>

<!-- ABOUT -->
<section id="foundation" class="text-center">
  <div class="container">
    <h2 class="fw-bold text-primary mb-3">Our Foundation</h2>
    <p class="text-muted mb-5">What drives ISKOLar to support every student’s journey</p>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="foundation-card">
          <h5>🎯 Mission</h5>
          <p>To connect students with scholarships that match their potential and need.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="foundation-card">
          <h5>🌟 Vision</h5>
          <p>A future where no student is left behind due to financial limitations.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="foundation-card">
          <h5>🎓 Core Values</h5>
          <p>Equity, accessibility, empowerment, and community.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- REGISTER MODAL -->
<div class="modal fade" id="registerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Register Account</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body p-4">

          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" required>

          <label class="form-label mt-3">Password</label>
          <input type="password" name="password" class="form-control" required>

          <label class="form-label mt-3">Confirm Password</label>
          <input type="password" name="confirm_password" class="form-control" required>

          <label class="form-label mt-3">User Type</label>
          <select name="user_type" class="form-select" required>
            <option value="" disabled selected>Select your role</option>
            <option value="student">Student</option>
            <option value="donor">Donor / Foundation</option>
          </select>

          <div class="text-center mt-3">
            <small>Already have an account?
              <a href="#" id="openLoginFromRegister" class="text-primary fw-semibold text-decoration-none">Log in</a>
            </small>
          </div>

        </div>
        <div class="modal-footer">
          <button type="submit" name="register" class="btn btn-primary w-100 py-2">Register</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- LOGIN MODAL -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Login</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <form method="POST">
        <div class="modal-body p-4">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" required>
          <label class="form-label mt-3">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <div class="modal-footer">
          <button type="submit" name="login" class="btn btn-primary w-100 py-2">Login</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- FOOTER -->
<footer class="footer text-center py-3 mt-auto">
  <small>&copy; 2025 ISKOLar | Built for Students. Powered by Purpose.</small>
</footer>

<!-- TOAST CONTAINER -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:1080">

  <?php if ($login_error): ?>
  <div id="loginToast" class="toast align-items-center text-bg-danger border-0 mb-2">
    <div class="d-flex">
      <div class="toast-body"><?php echo $login_error; ?></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($register_error): ?>
  <div id="registerErrorToast" class="toast align-items-center text-bg-danger border-0 mb-2">
    <div class="d-flex">
      <div class="toast-body"><?php echo $register_error; ?></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($register_success): ?>
  <div id="registerSuccessToast" class="toast align-items-center text-bg-success border-0">
    <div class="d-flex">
      <div class="toast-body">Registration successful! Please login to continue.</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
  <?php endif; ?>

</div>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const navbar = document.getElementById('mainNavbar');
  const getStartedBtn = document.getElementById('getStartedBtn');

  window.addEventListener('scroll', () => {
    navbar.classList.toggle('scrolled', window.scrollY > 50);
  });

  if (getStartedBtn) {
    getStartedBtn.addEventListener('click', () => {
      const registerModal = new bootstrap.Modal(document.getElementById('registerModal'));
      registerModal.show();
    });
  }

  // Show all toast notifications including login success
  ['loginToast', 'registerErrorToast', 'registerSuccessToast', 'loginSuccessToast'].forEach(id => {
    const toastEl = document.getElementById(id);
    if (toastEl) {
      const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
      toast.show();
    }
  });

  const openLoginLink = document.getElementById('openLoginFromRegister');
  if (openLoginLink) {
    openLoginLink.addEventListener('click', (e) => {
      e.preventDefault();
      const registerModalEl = document.getElementById('registerModal');
      const regInstance = bootstrap.Modal.getInstance(registerModalEl);
      if (regInstance) regInstance.hide();

      const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
      loginModal.show();
    });
  }

});
</script>

</body>
</html>
