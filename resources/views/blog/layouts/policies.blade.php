@extends('blog.app')
@section('page_title', 'Privacy & Policies')
@section('content')
    <div class="col-lg-12 entries">
        <article class="entry entry-single">
            @if(!empty($policy))
                <h2 class="entry-title">
                    <a href="#">{{ $policy->title }}</a>
                </h2>
                <div class="entry-meta">{{ $policy->subtitle }}</div>
                <div class="entry-content">{!! $policy->descriptions !!}</div>
            @else
                <h5 class="text-bold text-danger">Data not available!</h5>
            @endif
        </article>
    </div>
@endsection
