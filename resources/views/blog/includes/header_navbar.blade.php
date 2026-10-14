<header id="header" class="header fixed-top">
    <div class="container-fluid container-xl d-flex align-items-center justify-content-between">

        <a href="{{ url('/') }}" class="logo d-flex align-items-center">
            <x-brand-logo />
        </a>

        <nav id="navbar" class="navbar">
            <ul>
                <li><a class="nav-link scrollto" href="{{ url('/') }}">Home</a></li>
                <li><a class="nav-link scrollto" href="{{ route('blogs.about') }}">About</a></li>
                <li><a class="nav-link scrollto" href="{{ route('blogs.services') }}">Services</a></li>
                <li><a class="nav-link scrollto" href="{{ route('blogs.privacy-policies') }}">Privacy & Policy</a></li>
                <li><a class="nav-link scrollto" href="{{ route('blogs.contact') }}">Contact</a></li>
                <li><a class="nav-link scrollto" href="{{ url('portfolios') }}">Portfolio</a></li>
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
