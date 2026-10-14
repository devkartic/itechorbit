@extends('blog.app')
@section('page_title', 'About')
@section('content')
    <div class="col-lg-12 entries">
        <article class="entry entry-single">
            <div class="row pb-5">
                <div class="col-md-6 col-sm-12">
                    @if(!empty($about))
                        <h2 class="entry-title">
                            <a href="#">{{ $about->title }}</a>
                        </h2>
                        <div class="entry-meta">{{ $about->subtitle }}</div>
                        <div class="entry-content">{!! $about->descriptions !!}</div>
                    @else
                        <h5 class="text-bold text-danger">Data not available!</h5>
                    @endif
                </div>
                <div class="col-md-6 col-sm-12 d-flex justify-content-md-center justify-content-sm-start">
                    <img class="img-fluid" src="{{ asset('blog/img/about_us/about-us.png') }}" alt="About Us" style="max-height:300px">
                </div>
            </div>
        </article>
    </div>
@endsection
