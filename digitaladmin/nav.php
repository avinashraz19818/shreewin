<?php
if (!isset($_SESSION)) {
    session_start();
}
?>

<style>
    /* Mobile-friendly navbar improvements */
    @media (max-width: 1199.98px) {
        .layout-navbar {
            padding: 0.75rem 0.5rem;
        }
        .navbar-nav .nav-link {
            padding: 0.625rem;
            min-height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .nav-item .dropdown-toggle {
            padding: 0.625rem;
        }
        .dropdown-menu {
            min-width: 200px;
        }
        .dropdown-item {
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
        }
    }
    
    /* Touch-friendly sizing */
    .navbar-nav .nav-link {
        min-width: 44px;
        min-height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.2s;
    }
    
    .navbar-nav .nav-link:active {
        background-color: rgba(0, 0, 0, 0.1);
    }
    
    /* Improved dropdown for mobile */
    @media (max-width: 576px) {
        .dropdown-menu {
            font-size: 1rem;
        }
        .dropdown-item {
            padding: 0.875rem 1.125rem;
        }
        .dropdown-item i {
            margin-right: 0.5rem;
        }
    }
    
    /* Search bar responsiveness */
    @media (max-width: 768px) {
        .navbar-search-wrapper .search-input {
            font-size: 16px;
            padding: 0.75rem;
        }
    }
</style>

<nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
    id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-2 py-2" href="javascript:void(0)" role="button" aria-label="Toggle navigation menu">
            <i class="ri-menu-fill ri-24px"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
        <!-- Search -->
        <div class="navbar-nav align-items-center d-none d-sm-flex">
            <div class="nav-item navbar-search-wrapper mb-0">
                <a class="nav-item nav-link search-toggler fw-normal px-2 py-2" href="javascript:void(0);" role="button" aria-label="Search">
                    <i class="ri-search-line ri-22px scaleX-n1-rtl me-2"></i>
                    <span class="d-none d-md-inline-block text-muted" style="font-size: 0.9rem;">Search (Ctrl+/)</span>
                </a>
            </div>
        </div>
        <!-- /Search -->

        <ul class="navbar-nav flex-row align-items-center ms-auto gap-1">
            <!-- Language -->
            <li class="nav-item dropdown-language dropdown">
                <a class="nav-link btn btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow p-2"
                    href="javascript:void(0);" data-bs-toggle="dropdown" role="button" aria-label="Language options"
                    aria-haspopup="true" aria-expanded="false">
                    <i class="ri-translate-2 ri-20px"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item py-2" href="javascript:void(0);" data-language="en"
                            data-text-direction="ltr">
                            <span class="align-middle">English</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2" href="javascript:void(0);" data-language="fr"
                            data-text-direction="ltr">
                            <span class="align-middle">French</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2" href="javascript:void(0);" data-language="ar"
                            data-text-direction="rtl">
                            <span class="align-middle">Arabic</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2" href="javascript:void(0);" data-language="de"
                            data-text-direction="ltr">
                            <span class="align-middle">German</span>
                        </a>
                    </li>
                </ul>
            </li>
            <!--/ Language -->

            <!-- Style Switcher -->
            <li class="nav-item dropdown-style-switcher dropdown me-1 me-xl-0">
                <a class="nav-link btn btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow p-2"
                    href="javascript:void(0);" data-bs-toggle="dropdown" role="button" aria-label="Theme options"
                    aria-haspopup="true" aria-expanded="false">
                    <i class="ri-sun-line ri-20px"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end dropdown-styles">
                    <li>
                        <a class="dropdown-item py-2" href="javascript:void(0);" data-theme="light">
                            <span class="align-middle"><i class="ri-sun-line ri-20px me-2"></i>Light</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2" href="javascript:void(0);" data-theme="dark">
                            <span class="align-middle"><i class="ri-moon-clear-line ri-20px me-2"></i>Dark</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2" href="javascript:void(0);" data-theme="system">
                            <span class="align-middle"><i class="ri-computer-line ri-20px me-2"></i>System</span>
                        </a>
                    </li>
                </ul>
            </li>
            <!-- / Style Switcher-->



            <!-- Notification -->
            <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-2 me-xl-1">
                <a class="nav-link btn btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow p-2"
                    href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                    role="button" aria-label="Notifications" aria-haspopup="true" aria-expanded="false">
                    <i class="ri-notification-2-line ri-20px"></i>
                    <span
                        class="position-absolute top-0 start-50 translate-middle-y badge badge-dot bg-danger mt-2 border"></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end py-0" style="min-width: 280px;">
                    <li class="dropdown-menu-header border-bottom py-2 px-3">
                        <div class="dropdown-header d-flex align-items-center py-1">
                            <h6 class="mb-0 me-auto small">Notification</h6>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge rounded-pill bg-label-primary fs-xsmall">8 New</span>
                                <a href="javascript:void(0)"
                                    class="btn btn-text-secondary rounded-pill btn-icon dropdown-notifications-all p-1"
                                    data-bs-toggle="tooltip" data-bs-placement="top" title="Mark all as read"><i
                                        class="ri-mail-open-line text-heading ri-18px"></i></a>
                            </div>
                        </div>
                    </li>
                    <li class="dropdown-notifications-list scrollable-container" style="max-height: 300px;">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item list-group-item-action dropdown-notifications-item">
                                <div class="d-flex gap-2">
                                    <div class="flex-shrink-0">
                                        <div class="avatar" style="width: 36px; height: 36px;">
                                            <img src="assets/img/avatars/1.png" alt="" class="rounded-circle" />
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 min-width-0">
                                        <h6 class="small mb-1">Congratulations Shree Win Pro Admin</h6>
                                        <small class="mb-1 d-block text-body">You have won the best Admin Developer
                                            badge</small>
                                        <small class="text-muted">1h ago</small>
                                    </div>
                                    <div class="flex-shrink-0 dropdown-notifications-actions">
                                        <a href="javascript:void(0)" class="dropdown-notifications-read"><span
                                                class="badge badge-dot"></span></a>
                                        <a href="javascript:void(0)" class="dropdown-notifications-archive"><span
                                                class="ri-close-line ri-18px"></span></a>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </li>
                    <li class="border-top">
                        <div class="d-grid p-3">
                            <a class="btn btn-primary btn-sm d-flex" href="javascript:void(0);">
                                <small class="align-middle">View all notifications</small>
                            </a>
                        </div>
                    </li>
                </ul>
            </li>
            <!--/ Notification -->

            <!-- User -->
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow p-2" href="javascript:void(0);" data-bs-toggle="dropdown"
                    role="button" aria-label="User menu" aria-haspopup="true" aria-expanded="false">
                    <div class="avatar avatar-online" style="width: 40px; height: 40px;">
                        <img src="assets/img/avatars/1.png" alt="" class="rounded-circle" />
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" style="min-width: 220px;">
                    <li>
                        <a class="dropdown-item py-2" href="https://t.me/zayro_o">
                            <div class="d-flex gap-2">
                                <div class="flex-shrink-0">
                                    <div class="avatar avatar-online" style="width: 36px; height: 36px;">
                                        <img src="assets/img/avatars/1.png" alt="" class="rounded-circle" />
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <span class="fw-medium d-block small">Shree Win Pro Admin</span>
                                    <small class="text-muted">Admin</small>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        <div class="d-grid px-3 pt-2 pb-1">
                            <a class="btn btn-sm btn-danger d-flex align-items-center justify-content-center gap-2" href="api/logout.php">
                                <small class="align-middle">Logout</small>
                                <i class="ri-logout-box-r-line ri-16px"></i>
                            </a>
                        </div>
                    </li>
                </ul>
            </li>
            <!--/ User -->
        </ul>
    </div>

    <!-- Search Small Screens -->
    <div class="navbar-search-wrapper search-input-wrapper d-none py-2">
        <div class="input-group">
            <input type="text" class="form-control search-input container-xxl border-0" placeholder="Search..."
                aria-label="Search..." />
            <i class="ri-close-fill search-toggler cursor-pointer px-2 align-self-center"></i>
        </div>
    </div>
</nav>
