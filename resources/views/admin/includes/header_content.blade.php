<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-8">
                @php
                    $route_resource_name = null;
                    $route_resource_name_array = explode('.', request()->route()->getName());
                    if(sizeof($route_resource_name_array)>1) $route_resource_name = $route_resource_name_array[0];
                @endphp
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ url('home') }}">Dashboard</a></li>
                    @if(!empty($route_resource_name))
                    <li class="breadcrumb-item"><a href="{{ url($route_resource_name) }}" class="text-capitalize">{{ $route_resource_name }}</a></li>
                    @endif
                    <li class="breadcrumb-item active">@yield('bread_camp', '')</li>
                </ol>
            </div><!-- /.col -->
            <div class="col-4">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item">
                        @hasSection('action_level')
                            <a href="@yield('action_url', '')" class="btn-group mr-2" role="group"
                               aria-label="First group">
                                <button type="button"
                                        class="btn btn-xs btn-outline-primary">@yield('action_level', '')</button>
                            </a>
                        @endif
                    </li>
                </ol>
            </div><!-- /.col -->
        </div><!-- /.row -->
    </div><!-- /.container-fluid -->
</div>
