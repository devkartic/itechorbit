@extends('mail.layout', ['mailTitle' => $subject ?? 'A message from iTechOrbit'])
@section('content')
<p>Hello {{ $name }},</p>
<div>{!! $message_body !!}</div>
<p>Kind regards,<br><strong>The iTechOrbit Team</strong></p>
@endsection
