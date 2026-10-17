<li class="nav-item dropdown">
    <a class="nav-link" data-toggle="dropdown" href="#">
        <i class="far fa-bell"></i>
        <span class="badge badge-warning navbar-badge">{{ $notifications->count() }}</span>
    </a>
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
        <span class="dropdown-item dropdown-header">Notifications</span>
        <div class="dropdown-divider"></div>
        <a href="{{ url('contacts') }}" class="dropdown-item">
            <i class="fas fa-envelope mr-2"></i> {{ $notifications->count() }} new messages
            <span class="float-right text-muted text-sm">
                @if(sizeof($notifications))
                    {{ $notifications->first()->created_at->diffForHumans() }}
                @endif
            </span>
        </a>
        <div class="dropdown-divider"></div>
        <a href="#" class="dropdown-item dropdown-footer">See All Notifications</a>
    </div>
</li>
