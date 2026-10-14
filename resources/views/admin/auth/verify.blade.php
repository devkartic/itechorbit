@extends('admin.app')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card">
                <div class="card-header text-bold"><i class="fa-solid fa-lock text-danger"></i> {{ __('Email verification must be required.') }}</div>

                <div class="card-body">
                    @if (session('resent'))
                        <div class="alert alert-success" role="alert">
                            {{ __('A fresh verification link has been sent to your email address.') }}
                        </div>
                    @endif

                    {!!  __('Before proceeding, <span class="text-bold text-info">Please check your registered email for a verification link and verify your account</span>.') !!}
                    {{ __('If you did not receive the email') }}

                    <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                        @csrf
                        <button type="submit" class="btn btn-link p-0 m-0 align-baseline">{{ __('Click here') }}</button>
                    </form>
                        {{ __('to request another.') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
