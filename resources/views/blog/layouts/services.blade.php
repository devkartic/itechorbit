@extends('blog.app')
@section('page_title', 'Services')
@section('content')
    <div class="col-lg-12 entries">
        <article class="entry entry-single">
            <div class="row pb-5">
                <div class="col-md-6 col-sm-12">
                    @if(!empty($service))
                        <h2 class="entry-title">
                            <a href="#">{{ $service->title }}</a>
                        </h2>
                        <div class="entry-meta">{{ $service->subtitle }}</div>
                        <div class="entry-content">{!! $service->descriptions !!}</div>
                    @else
                        <h5 class="text-bold text-danger">Data not available!</h5>
                    @endif
                </div>
                <div class="col-md-6 col-sm-12">
                    <div class="d-flex justify-content-center">
                        <img class="img-fluid shadow-lg rounded" src="{{ asset('blog/img/services/blog.jpg') }}" alt="Blog"
                             style="max-height:300px">
                    </div>
                    <div class="d-flex justify-content-center mt-lg-5 pt-3">
                        <img class="img-fluid shadow-lg rounded" src="{{ asset('blog/img/services/portfolio.jpg') }}" alt="Portfolio"
                             style="max-height:300px">
                    </div>
                </div>
            </div>
        </article>
    </div>
@endsection
