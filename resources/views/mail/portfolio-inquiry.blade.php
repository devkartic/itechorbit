@extends('mail.layout', ['mailTitle' => ($personal ?? true) ? $owner->name : 'iTechOrbit', 'mailLabel' => ($personal ?? true) ? 'PERSONAL PORTFOLIO / NEW INQUIRY' : 'ITECHORBIT / NEW BUSINESS INQUIRY'])
@php($personal = $personal ?? true)
@section('content')
<h1 style="margin:0 0 18px;font-size:27px;letter-spacing:-.5px;line-height:1.3">A new conversation starts here.</h1>
<p style="margin:0 0 18px">Hello {{ $owner->name }},<br>{{ $inquiry->name }} {{ $personal ? 'reached out through your portfolio.' : 'sent an inquiry through the iTechOrbit website.' }} Here is their message.</p>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;table-layout:fixed;background:#f7f5fb;border:1px solid #e9e2f2;border-radius:10px">
<tr><td style="padding:18px 20px;color:#73677f;font-size:11px;width:70px">FROM</td><td style="padding:18px 20px 18px 0;font-size:14px;font-weight:bold">{{ $inquiry->name }}</td></tr>
<tr><td style="padding:0 20px 18px;color:#73677f;font-size:11px">EMAIL</td><td style="padding:0 20px 18px 0;font-size:13px;overflow-wrap:anywhere"><a href="mailto:{{ $inquiry->email }}" style="color:#6942d4">{{ $inquiry->email }}</a></td></tr>
<tr><td style="padding:0 20px 18px;color:#73677f;font-size:11px">SUBJECT</td><td style="padding:0 20px 18px 0;font-size:14px">{{ $inquiry->subject }}</td></tr>
</table>
<p style="margin:26px 0 12px;color:#73677f;font-size:10px;letter-spacing:1.5px">THE MESSAGE</p>
<div style="padding:20px;border-left:3px solid #7951c9;background:#fbfaff;color:#32283e;font-size:15px;line-height:1.85;overflow-wrap:anywhere">{!! nl2br(e($inquiry->message)) !!}</div>
<table role="presentation" cellspacing="0" cellpadding="0" style="max-width:100%;margin-top:28px"><tr><td style="background:#6942d4;border-radius:8px"><a href="mailto:{{ $inquiry->email }}?subject={{ rawurlencode('Re: '.$inquiry->subject) }}" style="display:inline-block;max-width:100%;box-sizing:border-box;overflow-wrap:anywhere;word-break:break-word;padding:13px 22px;color:#fff;font-size:13px;font-weight:bold;text-decoration:none">Reply to {{ $inquiry->name }} &rarr;</a></td></tr></table>
<p style="margin:18px 0 0;color:#73677f;font-size:12px">You can also reply directly to this email. Your reply will go to {{ $inquiry->name }}.</p>
<p style="margin:18px 0 0;font-size:12px"><a href="{{ url('/contacts/'.$inquiry->id) }}" style="color:#6942d4">{{ $personal ? 'Open this message in your personal inbox' : 'Open this inquiry in the business inbox' }}</a></p>

@endsection
@section('footer'){{ $personal ? 'This inquiry was addressed to '.$owner->name.' through their personal portfolio.' : 'An iTechOrbit website inquiry. Reply directly to continue the conversation.' }}@endsection
