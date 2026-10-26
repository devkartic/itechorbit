@extends('mail.layout', ['mailTitle' => $campaign->subject, 'mailLabel' => 'ITECHORBIT · NOTICE & UPDATE'])
@section('content')
<p style="margin:0 0 20px">Dear {{ trim((string) $recipient->name) ?: 'iTechOrbit member' }},</p>
<div style="overflow-wrap:anywhere">{!! $campaign->body !!}</div>
<p style="margin:24px 0 0;padding-top:20px;border-top:1px solid #e5e1ef">Kind regards,<br><strong>The iTechOrbit Team</strong></p>
@endsection
@section('footer')This notice was sent to you as an iTechOrbit account holder.@endsection
