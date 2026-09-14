<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Dashboard</title>
  <!-- CSS Files -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined|Material+Icons" rel="stylesheet">
  <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="vendors/feather/feather.css">
  <link rel="stylesheet" href="vendors/base/vendor.bundle.base.css">
  <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css"/>
  <link rel="stylesheet" href="vendors/font-awesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="vendors/jquery-bar-rating/fontawesome-stars-o.css">
  <link rel="stylesheet" href="vendors/jquery-bar-rating/fontawesome-stars.css">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="plugins/datatables/dataTables.bootstrap.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.2.3/css/fixedHeader.dataTables.min.css">
  <link rel="shortcut icon" href="https://codehub94.io/favicon.ico" />
  <style>
    .cool-input {
        border: 2px solid #007bff;
        border-radius: 0.25rem;
        padding: 0.5rem 1rem;
        font-size: 1rem;
        transition: all 0.3s ease;
    }
    .cool-input:focus {
        border-color: #0056b3;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
    .cool-input::placeholder {
        color: #6c757d;
        opacity: 1;
    }
    .cool-button {
        padding: 0.5rem 1rem;
        font-size: 1rem;
        border-radius: 0.25rem;
        transition: all 0.3s ease;
    }
    .cool-button:hover {
        background-color: #0056b3;
        color: #fff;
    }
    .cool-button.btn-secondary:hover {
        background-color: #343a40;
        color: #fff;
    }
    #copied {
        visibility: hidden;
        z-index: 1;
        position: fixed;
        bottom: 50%;
        background-color: #333;
        color: #fff;
        border-radius: 6px;
        padding: 16px;
        max-width: 250px;
        font-size: 17px;
    }
    #copied.show {
        visibility: visible;
        animation: fadein 0.5s, fadeout 0.5s 2.5s;
    }
    .form-select {
        display: block;
        width: 100%;
        padding: 10px 40px 10px 15px;
        font-size: 16px;
        line-height: 1.5;
        color: #495057;
        background-color: #fff;
        background-image: url("data:image/svg+xml;utf8,<svg fill='black' height='24' viewBox='0 0 24 24' width='24' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/></svg>");
        background-repeat: no-repeat;
        background-position: right 10px center;
        background-size: 16px 16px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        appearance: none;
    }
    /* Bottom Navigation Bar for Mobile */
    .bottom-nav {
      display: none;
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: #fff;
      border-top: 1px solid #ced4da;
      z-index: 1000;
      padding: 8px 0;
      box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
    }
    .bottom-nav .nav-items {
      display: flex;
      justify-content: space-around;
      align-items: center;
    }
    .bottom-nav .nav-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-decoration: none;
      color: #555;
      font-size: 11px;
      padding: 6px;
      transition: all 0.3s ease;
    }
    .bottom-nav .nav-item .material-icons-outlined {
      font-size: 22px;
      margin-bottom: 4px;
      converse: all 0.3s ease;
    }
    .bottom-nav .nav-item.active .material-icons-outlined {
      font-family: 'Material Icons';
      color: #007bff;
    }
    .bottom-nav .nav-item:hover {
      color: #007bff;
    }
    .bottom-nav .nav-item.active {
      color: #007bff;
      font-weight: 500;
    }
    /* Menu button animation styles */
    .menu-toggle {
      position: relative;
    }
    .menu-toggle .material-icons-outlined {
      transition: all 0.3s ease;
    }
    .menu-toggle.active .material-icons-outlined {
      font-family: 'Material Icons';
      content: 'close';
      color: #007bff;
      transform: scale(1.2);
      opacity: 0.8;
      filter: drop-shadow(0 0 3px rgba(0, 123, 255, 0.3));
    }
    .menu-toggle.active span:not(.material-icons-outlined) {
      color: #007bff;
      font-weight: 500;
    }
    @media (max-width: 991px) {
      .bottom-nav {
        display: block;
      }
      .page-body-wrapper {
        padding-bottom: 60px; /* Add white space to prevent content overlap with bottom nav */
      }
    }
    /* Custom Overlay Header */
    .custom-header {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      background: #fff;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      z-index: 1000;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 15px 24px; /* Slightly increased padding for larger header */
    }
    .custom-header .header-logo {
      max-width: 150px; /* Slightly increased container for logo */
    }
    .custom-header .header-logo img {
      max-width: 100%;
      height: auto;
      max-height: 52px; /* Slightly increased logo size for desktop */
    }
    .custom-header .header-content {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .custom-header .header-toggle {
      background: transparent;
      border: none;
      cursor: pointer;
    }
    .custom-header .header-toggle .material-icons {
      font-size: 24px;
      color: #333;
    }
    @media (max-width: 991px) {
      .custom-header .header-logo {
        max-width: 130px; /* Slightly increased container for mobile */
      }
      .custom-header .header-logo img {
        max-height: 40px; /* Slightly increased logo size for mobile */
      }
      .custom-header .header-toggle {
        display: none; /* Hide sidebar toggle in mobile */
      }
    }
  </style>
</head>
<body>
   <div class="container-scroller">
   <!-- Include Material Icons in <head> if not done -->
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined|Material+Icons" rel="stylesheet">
<!-- Custom Overlay Header -->
<header class="custom-header">
  <div class="header-logo">
    <a href="dashboard.php">
      <img src="https://xentra1.site/Acevault247.png" alt="logo">
    </a>
  </div>
  <div class="header-content">
    <div class="text-muted small" id="datetime"></div>
  </div>
</header>
    <!-- Bottom Navigation Bar -->
    <div class="bottom-nav d-lg-none">
      <div class="nav-items">
        <a href="addupi.php" class="nav-item">
          <span class="material-icons-outlined">account_balance_wallet</span>
          Finance
        </a>
        <a href="deposit_update.php" class="nav-item">
          <span class="material-icons-outlined">savings</span>
          Deposit
        </a>
        <a href="dashboard.php" class="nav-item">
          <span class="material-icons-outlined">dashboard</span>
          Dashboard
        </a>
        <a href="manage_user.php" class="nav-item">
          <span class="material-icons-outlined">group</span>
          Manage Users
        </a>
        <a class="nav-item menu-toggle" data-toggle="offcanvas">
          <span class="material-icons-outlined">menu</span>
          Menu
        </a>
      </div>
    </div>
    <!-- Sidebar -->
    <div class="container-fluid page-body-wrapper">
      <nav class="sidebar sidebar-offcanvas" id="sidebar">
        <div class="user-profile">
        </div>
        <?php include 'compass.php'; ?>
      </nav>
   
    <script>
  function openPopup(type) {
    const content = {
      success: [
        "🟢 New user registration",
        "🟢 Withdrawal applied",
        "🟢 Deposit applied"
      ],
      danger: [
        "🔴 Deposit not received",
        "🔴 Withdrawal problem",
        "🔴 Change Bank name",
        "🔴 IFSC code change"
      ]
    };
    document.getElementById("popupTitle").innerText = type === 'success' ? "System Alerts" : "Customer Support";
    document.getElementById("popupContent").innerHTML = content[type].map(item => `<div>${item}</div>`).join("");
    document.getElementById("notificationPopup").style.display = "block";
  }
  function closePopup() {
    document.getElementById("notificationPopup").style.display = "none";
  }
  function openPopup(type) {
    fetch('get_notifications.php?type=' + type)
      .then(response => response.json())
      .then(data => {
        document.getElementById("popupTitle").innerText = type === 'success' ? "System Alerts" : "Customer Support";
        document.getElementById("popupContent").innerHTML = data.map(item => `<div>${item}</div>`).join("");
        document.getElementById("notificationPopup").style.display = "block";
      });
  }
  // Bottom nav active state toggle
  document.querySelectorAll('.bottom-nav .nav-item').forEach(item => {
    item.addEventListener('click', function () {
      document.querySelectorAll('.bottom-nav .nav-item').forEach(nav => nav.classList.remove('active'));
      this.classList.add('active');
    });
  });
  // Menu preview toggle with professional animation
  document.querySelector('.menu-toggle').addEventListener('click', function (e) {
    e.preventDefault();
    const menuPreview = document.getElementById('menuPreview');
    const isOpen = menuPreview.style.display === 'flex';
    menuPreview.style.display = isOpen ? 'none' : 'flex';
    this.classList.toggle('active', !isOpen);
  });
  function closeMenu() {
    document.getElementById('menuPreview').style.display = 'none';
    document.querySelector('.menu-toggle').classList.remove('active');
  }
  // Set active state based on URL
  window.addEventListener('load', function () {
    const currentPath = window.location.pathname.split('/').pop() || 'dashboard.php';
    const navPages = {
      'addupi.php': 'Finance',
      'deposit_update.php': 'Deposit',
      'dashboard.php': 'Dashboard',
      'manage_user.php': 'Manage Users'
    };
    let isNavPage = false;
    document.querySelectorAll('.bottom-nav .nav-item').forEach(item => {
      item.classList.remove('active');
      const itemPath = item.getAttribute('href');
      if (itemPath === currentPath) {
        item.classList.add('active');
        isNavPage = true;
      }
    });
    // Highlight Menu if current page is not in nav
    if (!isNavPage) {
      document.querySelector('.menu-toggle').classList.add('active');
    }
  });
</script>
</body>
</html>