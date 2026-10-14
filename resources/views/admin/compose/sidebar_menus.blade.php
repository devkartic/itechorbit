@foreach($menus as $menu)
    @if($menu->has_permission)
    <li class="nav-header">{{ $menu->name }}</li>
    @endif
    @php
        $modules = $menu->modules;
    @endphp
    @foreach($modules as $module)
        @if($module->has_permission)
            <li class="nav-item nav-link-parent">
                <a href="#" class="nav-link">
                    @if(!empty($module->fa_icon))
                        {!! $module->fa_icon !!}
                    @else
                        <i class="nav-icon fas {{ $module->fa_icon }}"></i>
                    @endif
                    <p>
                        {{ $module->name }}
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                @php
                    $links = $module->links;
                @endphp
                <ul class="nav nav-treeview">
                    @foreach($links as $link)
                        @if($link->has_permission)
                            <li class="nav-item nav-link-child">
                                <a href="{{ url($link->url) }}" class="nav-link">
                                    @if(!empty($link->fa_icon))
                                        {!! $link->fa_icon !!}
                                    @else
                                        <i class="far fa-circle nav-icon"></i>
                                    @endif
                                    <p>{{ $link->name }}</p>
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </li>
        @endif
    @endforeach
@endforeach
