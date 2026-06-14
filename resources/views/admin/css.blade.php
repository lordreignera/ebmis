<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Emuria Micro Finance Limited - Banking & Investment Management System</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="{{ asset('admin/assets/vendors/mdi/css/materialdesignicons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/assets/vendors/css/vendor.bundle.base.css') }}">
    <!-- Bootstrap 5 CSS for modals -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="{{ asset('admin/assets/vendors/jvectormap/jquery-jvectormap.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/assets/vendors/flag-icon-css/css/flag-icon.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/assets/vendors/owl-carousel-2/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/assets/vendors/owl-carousel-2/owl.theme.default.min.css') }}">
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <!-- endinject -->
    <!-- Layout styles -->
    <link rel="stylesheet" href="{{ asset('admin/assets/css/style.css') }}">
    <!-- Enhanced Table Styles -->
    <link rel="stylesheet" href="{{ asset('css/enhanced-tables.css') }}">
    <!-- Modern Clean Table Styles -->
    <link rel="stylesheet" href="{{ asset('css/modern-tables.css') }}">
    <!-- Force Scroll CSS -->
    <link rel="stylesheet" href="{{ asset('css/force-scroll.css') }}">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <!-- Custom Select2 Styling -->
    <style>
        /* Select2 Custom Styling */
        .select2-container--default .select2-selection--single {
            height: 45px !important;
            border: 1px solid #e3e6f0 !important;
            border-radius: 8px !important;
            padding: 8px 12px !important;
            background-color: #ffffff !important;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #2d3748 !important;
            line-height: 28px !important;
            padding-left: 0 !important;
            font-size: 0.9rem !important;
            font-weight: 500 !important;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 43px !important;
            right: 10px !important;
        }
        
        .select2-dropdown {
            border: 1px solid #e3e6f0 !important;
            border-radius: 8px !important;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
        }
        
        .select2-container--default .select2-search--dropdown .select2-search__field {
            border: 1px solid #e3e6f0 !important;
            border-radius: 6px !important;
            padding: 8px 12px !important;
            font-size: 0.9rem !important;
        }
        
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #111827 !important;
            color: #ffffff !important;
        }
        
        .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: #f8f9fc !important;
            color: #5a5c69 !important;
        }
        
        .select2-results__option {
            padding: 10px 15px !important;
            font-size: 0.9rem !important;
        }
        
        /* Custom member result styling */
        .select2-result-member {
            padding: 8px 0;
        }
        
        .select2-result-member__title {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 2px;
        }
        
        .select2-result-member__description {
            font-size: 0.8rem;
            color: #718096;
        }
        
        /* Focus styles */
        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #111827 !important;
            box-shadow: 0 0 0 0.2rem rgba(17, 24, 39, 0.12) !important;
        }
        
        /* Placeholder styling */
        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #6c757d !important;
            font-style: italic !important;
        }
        
        /* Clear button styling */
        .select2-container--default .select2-selection--single .select2-selection__clear {
            color: #111827 !important;
            font-size: 1.2rem !important;
            margin-right: 10px !important;
            margin-top: -2px !important;
        }
        
        /* Selected text styling improvements */
        .select2-container--default .select2-selection--single .select2-selection__rendered[title] {
            display: block !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
            max-width: calc(100% - 30px) !important;
        }
    </style>
    
    <!-- Critical table hover fix -->
    <style>
        /* Override Bootstrap table hover variables */
        :root {
            --bs-table-hover-bg: #f1f5f9 !important;
            --bs-table-hover-color: #1a202c !important;
        }
        
        /* Force override for all table hover states */
        .table-hover > tbody > tr:hover > *,
        .table tbody tr:hover,
        .table tbody tr:hover td,
        .table tbody tr:hover th {
            background-color: #f1f5f9 !important;
            color: #1a202c !important;
        }
    </style>
    <!-- End layout styles -->
    <link rel="shortcut icon" href="{{ asset('admin/assets/images/icon1.png') }}" />
    
    <base href="{{ url('/') }}/">
   <style>

    body {
        background-color: #f4f5f7; /* Light gray background */
        font-family: "Poppins", sans-serif;
    }
    
    .sidebar {
        background: #eef6ff !important;
        color: #111827 !important;
        box-shadow: none;
    }
    
    /* Force sidebar to be visible on desktop */
    @media (min-width: 992px) {
        .sidebar-offcanvas {
            left: 0 !important;
            transform: translateX(0) !important;
        }
        .sidebar-icon-only .sidebar {
            width: 70px;
        }
    }

    .sidebar .nav .nav-item.active .nav-link {
        background-color: #dbeafe !important;
        border-left: 4px solid #2563eb;
    }

    .navbar {
        background: #ffffff !important;
        color: #111827 !important;
        box-shadow: 0 1px 6px rgba(17, 24, 39, 0.08);
        padding: 1rem 1.5rem;
    }

    .main-panel {
        background-color: #f4f5f7 !important;
        color: #000000 !important;
    }

    .content-wrapper {
        background-color: #f4f5f7 !important;
        color: #000000 !important;
        padding: 30px;
        min-height: calc(100vh - 60px);
    }

    /* Page Header Improvements */
    .page-header {
        margin-bottom: 30px !important;
        padding: 20px;
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 6px rgba(17, 24, 39, 0.06);
    }

    .page-header .page-title {
        color: #111827 !important;
        font-size: 1.75rem;
        font-weight: 600;
        margin: 0;
    }

    .page-header .breadcrumb {
        background: transparent;
        margin: 0;
        padding: 0;
    }

    .page-header .breadcrumb-item {
        color: #111827 !important;
        font-size: 0.95rem;
    }

    .page-title-icon {
        width: 48px;
        height: 48px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: #f3f4f6 !important;
        margin-right: 15px;
    }

    /* Dashboard Cards - Enhanced Design */
    .card, .audit-card {
        background-color: #ffffff !important;
        color: #333333 !important;
        border: none !important;
        border-radius: 12px !important;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08) !important;
        margin-bottom: 30px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .card:hover, .audit-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
    }

    .card-body {
        background-color: #ffffff !important;
        color: #333333 !important;
        padding: 25px;
    }

    /* Icon Boxes */
    .icon-box-success {
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .icon-box-primary {
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .icon-box-danger {
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .icon-box-warning {
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .icon-item {
        font-size: 24px;
        color: #111827;
    }

    /* Card Titles and Text */
    .card-title {
        color: #2d3748 !important;
        font-weight: 600;
        font-size: 1.1rem;
        margin-bottom: 20px;
        border-bottom: 2px solid #f4f5f7;
        padding-bottom: 10px;
    }

    /* Statistics Numbers */
    .card h3 {
        color: #1a202c !important;
        font-weight: 700;
        font-size: 2rem;
        margin-bottom: 10px;
    }

    .card h6 {
        color: #718096 !important;
        font-weight: 500;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 10px;
    }

    /* Grid Spacing */
    .grid-margin {
        margin-bottom: 30px;
    }

    /* Overview Cards Styling */
    .card-bordered {
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05) !important;
    }

    .card-inner {
        padding: 25px;
    }

    .analytic-ov-group {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .analytic-au-data {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        transition: background 0.2s ease;
    }

    .analytic-au-data:hover {
        background: #e9ecef;
    }

    .analytic-au-data .title {
        color: #4a5568 !important;
        font-weight: 500;
        font-size: 0.9rem;
    }

    .analytic-au-data .amount {
        color: #1a202c !important;
        font-weight: 700;
        font-size: 1.25rem;
    }

    /* Traffic Channel Table */
    .traffic-channel-table .nk-tb-item {
        padding: 15px 0;
        border-bottom: 1px solid #e2e8f0;
    }

    .traffic-channel-table .nk-tb-head {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 12px 15px;
        margin-bottom: 10px;
    }

    .traffic-channel-table .tb-lead {
        color: #2d3748 !important;
        font-weight: 500;
        font-size: 0.9rem;
    }

    .traffic-channel-table .tb-amount {
        color: #1a202c !important;
        font-weight: 600;
        font-size: 1rem;
    }

    /* Tables - Enhanced with proper hover effects */
    .table {
        background-color: #ffffff !important;
        color: #333333 !important;
        border-radius: 8px;
        overflow: hidden;
        border-collapse: separate !important;
        border-spacing: 0 !important;
    }

    .table thead th {
        background: #eef4f8 !important;
        color: #243447 !important;
        border: none !important;
        font-weight: 600;
        padding: 15px;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }

    .table tbody td {
        background-color: #ffffff !important;
        color: #4a5568 !important;
        border-bottom: 1px solid #e2e8f0 !important;
        padding: 15px;
        vertical-align: middle;
        transition: all 0.2s ease !important;
    }

    .table tbody tr:hover {
        background-color: #f3f8fb !important;
        color: #1a202c !important;
    }

    .table tbody tr:hover td {
        background-color: #f3f8fb !important;
        color: #1a202c !important;
        border-color: #cbd5e0 !important;
    }

    /* Ensure text elements maintain readability on hover */
    .table tbody tr:hover td * {
        color: inherit !important;
    }

    .table tbody tr:hover .badge {
        color: #ffffff !important;
    }

    .table tbody tr:hover .text-primary {
        color: #3182ce !important;
    }

    .table tbody tr:hover .text-muted {
        color: #718096 !important;
    }

    .table tbody tr:last-child td {
        border-bottom: none !important;
    }

    /* Buttons */
    .btn {
        font-weight: 500;
        border-radius: 8px;
        padding: 10px 20px;
        transition: all 0.3s ease;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    /* Badges */
    .badge {
        padding: 6px 12px;
        border-radius: 6px;
        font-weight: 500;
        font-size: 0.8rem;
    }

    /* Text Colors */
    .text-success {
        color: #111827 !important;
    }

    .text-primary {
        color: #111827 !important;
    }

    .text-danger {
        color: #111827 !important;
    }

    .text-warning {
        color: #111827 !important;
    }

    .text-info {
        color: #111827 !important;
    }

    .text-muted {
        color: #718096 !important;
    }

    /* Responsive Improvements */
    @media (max-width: 991px) {
        .content-wrapper {
            padding: 20px;
        }

        .page-header {
            padding: 15px;
        }

        .card-body {
            padding: 20px;
        }
    }

    /* Chart Container */
    .nk-ck {
        padding: 20px;
        background: #ffffff;
    }

    canvas {
        max-height: 350px !important;
    }

    /* Footer Styling */
    footer {
        background: #ffffff;
        padding: 20px;
        border-top: 1px solid #e2e8f0;
        text-align: center;
        color: #718096;
        font-size: 0.9rem;
    }

    /* Sidebar Additional Styling */
    .sidebar .sidebar-brand-wrapper {
        background: #e0f2fe !important;
        height: 70px;
        border-bottom: 1px solid #bfdbfe;
    }

    .sidebar .sidebar-brand-wrapper .brand-logo {
        padding: 0 15px;
    }

    .sidebar .nav .nav-item .nav-link {
        padding: 12px 20px;
        transition: all 0.3s ease;
    }

    .sidebar .nav .nav-item .nav-link:hover {
        background-color: rgba(255,255,255,0.08);
        border-left: 4px solid #4fd1c7;
    }

    .sidebar .nav .nav-item.active > .nav-link {
        background-color: rgba(255,255,255,0.12);
        border-left: 4px solid #4fd1c7;
        font-weight: 600;
    }

    .sidebar .nav .nav-item .menu-icon i {
        font-size: 20px;
        margin-right: 10px;
    }

    .sidebar .nav .nav-item .sub-menu .nav-item .nav-link {
        padding-left: 60px;
        font-size: 0.9rem;
    }

    .sidebar .nav .nav-item .sub-menu .nav-item .nav-link i {
        margin-right: 8px;
        font-size: 16px;
    }

    .sidebar .nav-category .nav-link {
        color: rgba(255,255,255,0.6) !important;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 15px 20px 10px;
    }

    /* Profile Section in Sidebar */
    .sidebar .nav .nav-item.profile {
        padding: 20px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        margin-bottom: 10px;
    }

    .sidebar .nav .nav-item.profile .profile-name h5 {
        color: #ffffff !important;
        font-size: 0.95rem;
        font-weight: 600;
    }

    .sidebar .nav .nav-item.profile .profile-name span {
        color: rgba(255,255,255,0.7) !important;
        font-size: 0.8rem;
    }

    /* Navbar Improvements */
    .navbar .navbar-menu-wrapper {
        padding: 0 1.5rem;
    }

       
        /* Fix dropdown issues */
        .navbar .dropdown-menu {
            background-color: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
            border-radius: 8px !important;
            padding: 0.5rem 0 !important;
            z-index: 9999 !important;
            display: none !important;
            position: absolute !important;
            min-width: 200px !important;
        }
        
        .navbar .dropdown-menu.show {
            display: block !important;
        }
        
        .navbar .dropdown-item {
            color: #2d3748 !important;
            padding: 0.5rem 1rem !important;
            display: flex !important;
            align-items: center !important;
            text-decoration: none !important;
            background: transparent !important;
            border: none !important;
            width: 100% !important;
            text-align: left !important;
        }
        
        .navbar .dropdown-item:hover {
            background-color: #f7fafc !important;
            color: #2d3748 !important;
        }
        
        .navbar .dropdown-header {
            color: #718096 !important;
            padding: 0.5rem 1rem !important;
        }
        
        .navbar .dropdown-divider {
            border-top: 1px solid #e2e8f0 !important;
            margin: 0.25rem 0 !important;
        }
        
        /* Ensure dropdown toggle works */
        .navbar .nav-link.dropdown-toggle::after {
            display: none !important;
        }
        
        .navbar .dropdown-toggle {
            cursor: pointer !important;
        }
        
        /* Position dropdown correctly */
        .navbar .nav-item.dropdown {
            position: relative !important;
        }
        
        .navbar .dropdown-menu-right {
            right: 0 !important;
            left: auto !important;
        }
        
    /* Search Bar */
    .navbar .search .form-control {
        background-color: rgba(255,255,255,0.15) !important;
        border: 1px solid rgba(255,255,255,0.2) !important;
        color: #ffffff !important;
        border-radius: 25px;
        padding: 10px 20px;
    }

    .navbar .search .form-control::placeholder {
        color: rgba(255,255,255,0.6) !important;
    }

    .navbar .search .form-control:focus {
        background-color: rgba(255,255,255,0.25) !important;
        border-color: rgba(255,255,255,0.4) !important;
        color: #ffffff !important;
    }

    /* Quick Actions Button */
    .create-new-button {
        background: #111827 !important;
        border: 1px solid #111827 !important;
        border-radius: 6px !important;
        padding: 10px 25px !important;
        font-weight: 600 !important;
        color: #ffffff !important;
        box-shadow: none !important;
        transition: all 0.3s ease !important;
    }

    .create-new-button:hover {
        transform: none !important;
        box-shadow: none !important;
    }

    /* Dropdown Menus */
    .dropdown-menu {
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        border: none;
        padding: 10px 0;
        margin-top: 10px;
    }

    .dropdown-item {
        padding: 12px 20px;
        transition: background 0.2s ease;
    }

    .dropdown-item:hover {
        background-color: #f8f9fa;
    }

    .dropdown-divider {
        margin: 5px 0;
        border-top-color: #e9ecef;
    }

    /* Scrollbar Styling */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    ::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    ::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    /* System-wide light table palette */
    .content-wrapper .table {
        --bs-table-bg: #ffffff;
        --bs-table-striped-bg: #f8fafc;
        --bs-table-hover-bg: #f3f8fb;
        --bs-table-hover-color: #1f2937;
        --bs-table-border-color: #dbe5ec;
        background: #ffffff !important;
        border-color: #dbe5ec !important;
    }

    .content-wrapper .table thead,
    .content-wrapper .table thead th,
    .content-wrapper .table .table-light,
    .content-wrapper .table-light th,
    .content-wrapper .table-light td {
        background: #eef4f8 !important;
        color: #243447 !important;
        border-color: #dbe5ec !important;
    }

    .content-wrapper .table tbody td,
    .content-wrapper .table tbody th {
        background: #ffffff !important;
        color: #344054 !important;
        border-color: #e5edf3 !important;
    }

    .content-wrapper .table tfoot,
    .content-wrapper .table tfoot th,
    .content-wrapper .table tfoot td,
    .content-wrapper .table .table-dark,
    .content-wrapper .table-dark th,
    .content-wrapper .table-dark td,
    .content-wrapper .table .table-secondary,
    .content-wrapper .table-secondary th,
    .content-wrapper .table-secondary td,
    .content-wrapper .table .table-primary,
    .content-wrapper .table-primary th,
    .content-wrapper .table-primary td,
    .content-wrapper .table .table-info,
    .content-wrapper .table-info th,
    .content-wrapper .table-info td,
    .content-wrapper .table .table-success,
    .content-wrapper .table-success th,
    .content-wrapper .table-success td,
    .content-wrapper .table .table-warning,
    .content-wrapper .table-warning th,
    .content-wrapper .table-warning td,
    .content-wrapper .table .table-danger,
    .content-wrapper .table-danger th,
    .content-wrapper .table-danger td,
    .content-wrapper .table .table-active,
    .content-wrapper .table-active th,
    .content-wrapper .table-active td {
        background: #f6f9fc !important;
        color: #243447 !important;
        border-color: #dbe5ec !important;
    }

    .content-wrapper .table-hover > tbody > tr:hover > *,
    .content-wrapper .table tbody tr:hover,
    .content-wrapper .table tbody tr:hover td,
    .content-wrapper .table tbody tr:hover th {
        background: #f3f8fb !important;
        color: #1f2937 !important;
        box-shadow: none !important;
        transform: none !important;
    }

    /* Monochrome system surface overrides */
    .content-wrapper .page-header {
        background: #ffffff !important;
        border: 1px solid #e5e7eb !important;
        border-radius: 8px !important;
        box-shadow: 0 2px 10px rgba(17, 24, 39, 0.06) !important;
    }

    .content-wrapper .page-header .page-title,
    .content-wrapper .page-header .breadcrumb,
    .content-wrapper .page-header .breadcrumb-item,
    .content-wrapper .page-header .breadcrumb-item.active,
    .content-wrapper .page-header .breadcrumb-item a,
    .content-wrapper .page-header code {
        color: #111827 !important;
    }

    .content-wrapper .page-title-icon,
    .content-wrapper .bg-gradient-primary,
    .content-wrapper .bg-gradient-success,
    .content-wrapper .bg-gradient-info,
    .content-wrapper .bg-gradient-warning,
    .content-wrapper .bg-gradient-danger {
        background: #f3f4f6 !important;
        color: #111827 !important;
        box-shadow: none !important;
    }

    .content-wrapper .page-title-icon i,
    .content-wrapper .bg-gradient-primary i,
    .content-wrapper .bg-gradient-success i,
    .content-wrapper .bg-gradient-info i,
    .content-wrapper .bg-gradient-warning i,
    .content-wrapper .bg-gradient-danger i {
        color: #111827 !important;
    }

    .content-wrapper .card > .card-header {
        background: #f8fafc !important;
        border-bottom: 1px solid #e5e7eb !important;
        color: #111827 !important;
        box-shadow: none !important;
    }

    .content-wrapper .card > .card-header *,
    .content-wrapper .card > .card-header a {
        color: #111827 !important;
    }

    .content-wrapper .nav-pills,
    .content-wrapper .nav-tabs,
    .content-wrapper .btn-toolbar,
    .content-wrapper .btn-group,
    .content-wrapper .filter-bar,
    .content-wrapper .table-header {
        background: #ffffff !important;
        color: #111827 !important;
    }

    .content-wrapper .nav-pills .nav-link,
    .content-wrapper .nav-tabs .nav-link {
        background: #ffffff !important;
        border: 1px solid #d1d5db !important;
        color: #111827 !important;
        font-weight: 600;
    }

    .content-wrapper .nav-pills .nav-link.active,
    .content-wrapper .nav-tabs .nav-link.active {
        background: #111827 !important;
        border-color: #111827 !important;
        color: #ffffff !important;
    }

    .content-wrapper .btn-primary {
        background: #2563eb !important;
        border-color: #2563eb !important;
        color: #ffffff !important;
        box-shadow: none !important;
    }

    .content-wrapper .btn-info {
        background: #0891b2 !important;
        border-color: #0891b2 !important;
        color: #ffffff !important;
        box-shadow: none !important;
    }

    .content-wrapper .btn-success {
        background: #16a34a !important;
        border-color: #16a34a !important;
        color: #ffffff !important;
        box-shadow: none !important;
    }

    .content-wrapper .btn-warning {
        background: #f59e0b !important;
        border-color: #f59e0b !important;
        color: #111827 !important;
        box-shadow: none !important;
    }

    .content-wrapper .btn-danger {
        background: #dc2626 !important;
        border-color: #dc2626 !important;
        color: #ffffff !important;
        box-shadow: none !important;
    }

    .content-wrapper .btn-secondary {
        background: #6b7280 !important;
        border-color: #6b7280 !important;
        color: #ffffff !important;
        box-shadow: none !important;
    }

    .content-wrapper .btn-outline-primary,
    .content-wrapper .btn-outline-info,
    .content-wrapper .btn-outline-success,
    .content-wrapper .btn-outline-warning,
    .content-wrapper .btn-outline-danger,
    .content-wrapper .btn-outline-secondary {
        background: #ffffff !important;
        box-shadow: none !important;
    }

    .content-wrapper .btn-outline-primary {
        border-color: #2563eb !important;
        color: #1d4ed8 !important;
    }

    .content-wrapper .btn-outline-info {
        border-color: #0891b2 !important;
        color: #0e7490 !important;
    }

    .content-wrapper .btn-outline-success {
        border-color: #16a34a !important;
        color: #15803d !important;
    }

    .content-wrapper .btn-outline-warning {
        border-color: #f59e0b !important;
        color: #92400e !important;
    }

    .content-wrapper .btn-outline-danger {
        border-color: #dc2626 !important;
        color: #b91c1c !important;
    }

    .content-wrapper .btn-outline-secondary {
        border-color: #6b7280 !important;
        color: #374151 !important;
    }

    .content-wrapper .btn:hover {
        transform: none !important;
        box-shadow: none !important;
    }

    </style>
