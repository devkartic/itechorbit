@extends('mail.layout', ['mailTitle' => $title])
@section('content')
<p>Hello {{ $recipientName }},</p>
<p>Your {{ $kind }} has been <strong>{{ $enabled ? 'enabled' : 'disabled' }}</strong>.</p>
<p>{{ $description }}</p>
@include('mail.action', ['actionUrl' => $actionUrl, 'actionLabel' => $actionLabel])
<p>If you have questions about this change, <a href="{{ url('/contact/info') }}" style="color:#6942d4">contact our team</a>.</p>
<p>Kind regards,<br><strong>The iTechOrbit Team</strong></p>
@endsection
