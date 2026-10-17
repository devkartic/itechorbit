<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <!-- Navbar Search -->
        <li class="nav-item">
            <a class="nav-link" data-widget="navbar-search" href="#" role="button">
                <i class="fas fa-search"></i>
            </a>
            <div class="navbar-search-block">
                <form class="form-inline">
                    <div class="input-group input-group-sm">
                        <input class="form-control form-control-navbar" type="search" placeholder="Search"
                               aria-label="Search">
                        <div class="input-group-append">
                            <button class="btn btn-navbar" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                            <button class="btn btn-navbar" type="button" data-widget="navbar-search">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </li>

        <!-- Notifications Dropdown Menu -->
        @include('legacy.admin.compose.notifications')
        <!-- User Dropdown Menu -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-user text-success"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <div class="dropdown-item dropdown-header">
                    <!-- Sidebar user panel (optional) -->
                    <div class="image">
                        @if(!empty(Auth::user()->basic->profile_image_path) && file_exists(Auth::user()->basic->profile_image_path))
                            <img src="{{ asset(Auth::user()->basic->profile_image_path) }}"
                                 class="profile-user-img img-fluid img-circle" alt="User Image">
                        @else
                            <img src="{{ asset('admin/dist/img/user-avatar.webp') }}" class="img-circle elevation-2"
                                 alt="User Image">
                        @endif
                    </div>
                    <div class="info my-2">
                        <a href="#" class="d-block font-weight-bold">
                            @if(!empty(Auth::user()->basic))
                                {{ Auth::user()->basic->first_name . ' ' . Auth::user()->basic->last_name }}
                            @else
                                {{ Auth::user()->name }}
                            @endif</a>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <a href="{{ url('users/' . Auth::user()->id) }}" class="dropdown-item">
                    <i class="fas fa-eye mr-2"></i> View Profile
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault();
                    document.getElementById('logout-form').submit();">
                    <i class="fas fa-sign-out-alt mr-2"></i> {{ __('Logout') }}
                </a>

                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        </li>
    </ul>
</nav>
