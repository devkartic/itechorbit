<footer id="footer" class="footer">
    @include('portfolio.layouts.news_later')
    <div class="footer-top">
        <div class="container">
            <div class="row gy-4 justify-content-center">
                <div class="col-lg-12 col-md-12 footer-info text-center">
                    <div href="index.html" class="logo align-items-center">
                        @if($user->id===1)
                            <span class="p-1"><i class="fa-brands fa-dev"></i></span>
                        @endif
                        <span class="text-capitalize">{{ $user->name }}</span>
                    </div>
                    @if(sizeof($user->social_links))
                        <p>I am available in social media has been given below.</p>
                        <div class="d-inline-flex align-items-center justify-content-center align-self-center mt-3">
                            @foreach($user->social_links as $social_link)
                                <a type="button" href="{{ $social_link->social_url }}"
                                   class="btn btn-sm btn-outline-primary me-1"
                                   target="_blank">{!! $social_link->fa_icon !!}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="copyright">
            &copy; Copyright <strong><span>{{ config('app.name', 'itechorbit') }}</span></strong>. All Rights Reserved
        </div>
        <div class="credits">
            Designed & Developed by <a class="fw-bold" href="{{ url('/') }}">{{ config('app.name', 'itechorbit') }}</a>
        </div>
    </div>
</footer>
