@extends('legacy.admin.app')
@section('page_title', 'Dashboard')
@section('content_title', 'Dashboard')
@section('bread_camp', 'Dashboard')
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-body text-bold">Users information</div>
        </div>
        <div class="row">
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $users->count() }}</h3>
                        <p class="text-bold">Total Users</p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-person-add"></i>
                    </div>
                    <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $users->where('status', 1)->count() }}</h3>
                        <p class="text-bold">Enabled Users</p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-person-add"></i>
                    </div>
                    <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $users->where('status', 0)->count() }}</h3>
                        <p class="text-bold">Disabled Users</p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-person-add"></i>
                    </div>
                    <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ $users->where('portfolio_status', 1)->count() }}</h3>
                        <p class="text-bold">Enabled Portfolio</p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-pie-graph"></i>
                    </div>
                    <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <!-- ./col -->
        </div>
        <div class="card">
            <div class="card-body text-bold">Posts Information</div>
        </div>
        <div class="row">
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $posts->count() }}</h3>
                        <p class="text-bold">Total Posts</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-blog"></i>
                    </div>
                    <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $posts->where('status', 1)->count() }}</h3>
                        <p class="text-bold">Active Posts</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-blog"></i>
                    </div>
                    <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $posts->where('status', 0)->count() }}</h3>
                        <p class="text-bold">Inactive Posts</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-blog"></i>
                    </div>
                    <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <!-- ./col -->
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ $posts->where('is_private', 1)->count() }}</h3>
                        <p class="text-bold">Private Posts</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-blog"></i>
                    </div>
                    <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <!-- ./col -->
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-body text-bold">Portfolio Information</div>
        </div>
        <div class="row mb-5">
            <div class="col-12">
                @php
                    if(!empty($progress) && $progress == 100){
                        $progress_status_bg = 'bg-success';
                    }elseif (!empty($progress) && $progress < 100 && $progress > 70){
                        $progress_status_bg = 'bg-warning';
                    }else{
                        $progress_status_bg = 'bg-danger';
                    }
                @endphp
                <div class="progress" style="height: 30px;font-size: 16px;font-weight: bold;border-radius: 3px">
                    <div class="progress-bar {{ $progress_status_bg }} text-bold" role="progressbar"
                         style="width: {{ $progress }}%;" aria-valuenow="30" aria-valuemin="0"
                         aria-valuemax="100">{{ $progress }}% @if($progress==100)
                            Completed
                        @endif</div>
                </div>
            </div>
            <!-- ./col -->
        </div>
    </div>
    <!-- /.container-fluid -->
</section>
@endsection
