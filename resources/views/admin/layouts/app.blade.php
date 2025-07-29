<!-- resources/views/admin/layouts/app.blade.php -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>QR Menu - Super Admin</title>
    
    <!-- Tabler CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="page">
        <!-- Sidebar -->
        <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <h1 class="navbar-brand navbar-brand-autodark">
                    <a href="{{ route('admin.dashboard') }}">
                        QR Menu Admin
                    </a>
                </h1>
                
                <div class="navbar-nav flex-row d-lg-none">
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown">
                            <span class="avatar avatar-sm bg-primary">SA</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="collapse navbar-collapse" id="sidebar-menu">
                    <ul class="navbar-nav pt-lg-3">
                        <!-- Dashboard -->
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                                <span class="nav-link-icon">
                                    <svg class="icon">
                                        <use xlink:href="#tabler-home"></use>
                                    </svg>
                                </span>
                                <span class="nav-link-title">Dashboard</span>
                            </a>
                        </li>
                        
                        <!-- Restaurants -->
                        <li class="nav-item dropdown {{ request()->routeIs('admin.restaurants.*') ? 'active' : '' }}">
                            <a class="nav-link dropdown-toggle" href="#navbar-restaurants" data-bs-toggle="dropdown" role="button">
                                <span class="nav-link-icon">
                                    <svg class="icon">
                                        <use xlink:href="#tabler-building-store"></use>
                                    </svg>
                                </span>
                                <span class="nav-link-title">Restaurants</span>
                            </a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="{{ route('admin.restaurants.index') }}">All Restaurants</a>
                                <a class="dropdown-item" href="{{ route('admin.restaurants.create') }}">Add Restaurant</a>
                                <a class="dropdown-item" href="{{ route('admin.restaurants.analytics') }}">Analytics</a>
                            </div>
                        </li>
                        
                        <!-- Users -->
                        <li class="nav-item dropdown {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <a class="nav-link dropdown-toggle" href="#navbar-users" data-bs-toggle="dropdown" role="button">
                                <span class="nav-link-icon">
                                    <svg class="icon">
                                        <use xlink:href="#tabler-users"></use>
                                    </svg>
                                </span>
                                <span class="nav-link-title">Users</span>
                            </a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="{{ route('admin.users.index') }}">All Users</a>
                                <a class="dropdown-item" href="{{ route('admin.users.create') }}">Add User</a>
                                <a class="dropdown-item" href="{{ route('admin.users.roles') }}">Roles & Permissions</a>
                            </div>
                        </li>
                        
                        <!-- Subscriptions -->
                        <li class="nav-item dropdown {{ request()->routeIs('admin.subscriptions.*') ? 'active' : '' }}">
                            <a class="nav-link dropdown-toggle" href="#navbar-subscriptions" data-bs-toggle="dropdown" role="button">
                                <span class="nav-link-icon">
                                    <svg class="icon">
                                        <use xlink:href="#tabler-credit-card"></use>
                                    </svg>
                                </span>
                                <span class="nav-link-title">Subscriptions</span>
                            </a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="{{ route('admin.subscriptions.index') }}">All Subscriptions</a>
                                <a class="dropdown-item" href="{{ route('admin.subscriptions.plans') }}">Plans</a>
                                <a class="dropdown-item" href="{{ route('admin.subscriptions.invoices') }}">Invoices</a>
                            </div>
                        </li>
                        
                        <!-- Analytics -->
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.analytics') ? 'active' : '' }}" href="{{ route('admin.analytics') }}">
                                <span class="nav-link-icon">
                                    <svg class="icon">
                                        <use xlink:href="#tabler-chart-line"></use>
                                    </svg>
                                </span>
                                <span class="nav-link-title">Analytics</span>
                            </a>
                        </li>
                        
                        <!-- Settings -->
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}" href="{{ route('admin.settings') }}">
                                <span class="nav-link-icon">
                                    <svg class="icon">
                                        <use xlink:href="#tabler-settings"></use>
                                    </svg>
                                </span>
                                <span class="nav-link-title">Settings</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </aside>
        
        <!-- Header -->
        <header class="navbar navbar-expand-md navbar-light d-print-none">
            <div class="container-xl">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="navbar-nav flex-row order-md-last">
                    <!-- Notifications -->
                    <div class="d-none d-md-flex">
                        <a href="#" class="nav-link px-0" title="Show notifications" data-bs-toggle="dropdown">
                            <svg class="icon">
                                <use xlink:href="#tabler-bell"></use>
                            </svg>
                            <span class="badge bg-red"></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Last updates</h3>
                                </div>
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <span class="status-dot status-dot-animated bg-red d-block"></span>
                                            </div>
                                            <div class="col text-truncate">
                                                <a href="#" class="text-body d-block">New restaurant registration</a>
                                                <div class="d-block text-muted text-truncate mt-n1">
                                                    Pizza Palace submitted documents
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <a href="#" class="list-group-item-actions">
                                                    <svg class="icon text-muted">
                                                        <use xlink:href="#tabler-star"></use>
                                                    </svg>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Profile -->
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown">
                            <span class="avatar avatar-sm bg-primary">{{ strtoupper(substr(auth()->user()->name ?? 'SA', 0, 2)) }}</span>
                            <div class="d-none d-xl-block ps-2">
                                <div>{{ auth()->user()->name ?? 'Super Admin' }}</div>
                                <div class="mt-1 small text-muted">Super Administrator</div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="#" class="dropdown-item">Profile</a>
                            <a href="#" class="dropdown-item">Settings</a>
                            <div class="dropdown-divider"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Page Content -->
        <div class="page-wrapper">
            <!-- Page header -->
            @if(isset($pageTitle))
            <div class="page-header d-print-none">
                <div class="container-xl">
                    <div class="row g-2 align-items-center">
                        <div class="col">
                            <h2 class="page-title">{{ $pageTitle }}</h2>
                            @if(isset($pageDescription))
                                <div class="text-muted mt-1">{{ $pageDescription }}</div>
                            @endif
                        </div>
                        @if(isset($pageActions))
                            <div class="col-auto ms-auto d-print-none">
                                {{ $pageActions }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Page body -->
            <div class="page-body">
                <div class="container-xl">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <div class="d-flex">
                                <div>
                                    <svg class="icon alert-icon">
                                        <use xlink:href="#tabler-check"></use>
                                    </svg>
                                </div>
                                <div>{{ session('success') }}</div>
                            </div>
                            <a class="btn-close" data-bs-dismiss="alert"></a>
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <div class="d-flex">
                                <div>
                                    <svg class="icon alert-icon">
                                        <use xlink:href="#tabler-alert-circle"></use>
                                    </svg>
                                </div>
                                <div>{{ session('error') }}</div>
                            </div>
                            <a class="btn-close" data-bs-dismiss="alert"></a>
                        </div>
                    @endif
                    
                    @yield('content')
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabler Icons -->
    <svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
        <symbol id="tabler-home" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9,22 9,12 15,12 15,22"></polyline>
        </symbol>
        <symbol id="tabler-building-store" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="21" x2="21" y2="21"></line>
            <path d="M5 21V7l8-4v18"></path>
            <path d="m14 7 7 7v7"></path>
            <path d="m8 12 13 0"></path>
            <path d="m8 16 13 0"></path>
        </symbol>
        <symbol id="tabler-users" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <path d="m22 21v-2a4 4 0 0 0-3-3.87"></path>
            <path d="m16 3.13a4 4 0 0 1 0 7.75"></path>
        </symbol>
        <symbol id="tabler-credit-card" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="20" height="14" x="2" y="5" rx="2"></rect>
            <line x1="2" x2="22" y1="10" y2="10"></line>
        </symbol>
        <symbol id="tabler-chart-line" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 3v18h18"></path>
            <path d="m19 9-5 5-4-4-3 3"></path>
        </symbol>
        <symbol id="tabler-settings" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path>
            <circle cx="12" cy="12" r="3"></circle>
        </symbol>
        <symbol id="tabler-bell" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
            <path d="m13.73 21a2 2 0 0 1-3.46 0"></path>
        </symbol>
        <symbol id="tabler-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20,6 9,17 4,12"></polyline>
        </symbol>
        <symbol id="tabler-alert-circle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" x2="12" y1="8" y2="12"></line>
            <line x1="12" x2="12.01" y1="16" y2="16"></line>
        </symbol>
        <symbol id="tabler-star" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"></polygon>
        </symbol>
    </svg>
</body>
</html>