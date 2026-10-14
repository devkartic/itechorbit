@extends('blog.app')
@section('page_title', 'Contact Us')
@section('content')
    <div class="col-lg-12 entries">
        <article class="entry entry-single">
            <div class="row pb-5">
                <div class="col-md-6 col-sm-12">
                    @if(!empty($contact))
                        <h2 class="entry-title">
                            <a href="#">{{ $contact->title }}</a>
                        </h2>
                        <div class="entry-meta">{{ $contact->subtitle }}</div>
                        <div class="entry-content">{!! $contact->descriptions !!}</div>
                    @else
                        <h5 class="text-bold text-danger">Data not available!</h5>
                    @endif
                </div>
                <div class="col-md-6 col-sm-12 d-flex justify-content-sm-start justify-content-md-center">
                    <img class="img-fluid" src="{{ asset('blog/img/contact/contact.png') }}" alt="Contact" style="max-height:300px">
                </div>
            </div>
        </article>
    </div>
@endsection
