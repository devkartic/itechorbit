<header id="header" class="header fixed-top">
    <div class="container-fluid container-xl d-flex align-items-center justify-content-between">

        <a href="#" class="logo d-flex align-items-center">
            @if($user->id===1)
                <span class="p-1"><i class="fa-brands fa-dev"></i></span>
            @endif
            <span class="text-capitalize">{{ $user->name }}</span>
        </a>

        <nav id="navbar" class="navbar">
            <ul>
                <li><a class="nav-link scrollto active" href="{{ url('/') }}">Home</a></li>
                <li><a class="nav-link scrollto" href="#about">About</a></li>
                <li><a class="nav-link scrollto" href="#services">Services</a></li>
                <li><a class="nav-link scrollto" href="#portfolio">Projects</a></li>
                <li><a class="nav-link scrollto" href="#contact">Contact</a></li>
                <li><a href="{{ url('/') }}">Blog</a></li>
                @if(\Illuminate\Support\Facades\Auth::user())
                    <li class="dropdown"><a href="{{ url('login') }}" class="getstarted"><span>Dashboard</span> <i
                                class="bi bi-chevron-down"></i></a>
                        <ul>
                            <li><a href="{{ url('users/' . Auth::user()->id) }}">View Profile</a></li>
                            <li><a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault();
                    document.getElementById('logout-form').submit();">{{ __('Logout') }}</a></li>
                        </ul>
                    </li>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                @else
                    <li><a class="getstarted scrollto" href="{{ url('home') }}">Sign In</a></li>
                @endif
            </ul>
            <i class="bi bi-list mobile-nav-toggle"></i>
        </nav><!-- .navbar -->

    </div>
</header>
