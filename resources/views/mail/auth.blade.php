@extends('mail.layout', ['mailTitle' => $title, 'mailLabel' => 'ITECHORBIT · ACCOUNT SECURITY'])
@section('content')
<p>Hello {{ $recipientName }},</p>
<p>{{ $intro }}</p>
@include('mail.action')
<p>{{ $expiry }}</p>
<p>{{ $outro }}</p>
<p>Kind regards,<br><strong>The iTechOrbit Team</strong></p>
@endsection
