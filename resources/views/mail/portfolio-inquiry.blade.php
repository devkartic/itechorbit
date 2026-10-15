@php($personal = $personal ?? true)
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>New portfolio inquiry</title></head>
<body style="margin:0;padding:0;background:#f3f1f8;color:#211b37;font-family:Arial,Helvetica,sans-serif;line-height:1.7">
<div style="display:none;max-height:0;overflow:hidden">{{ $inquiry->name }} sent you a personal portfolio inquiry: {{ $inquiry->subject }}</div>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f1f8"><tr><td align="center" style="padding:32px 16px">
<table role="presentation" width="600" cellspacing="0" cellpadding="0" style="width:100%;max-width:600px;background:#fff;border:1px solid #e2ddeb;border-radius:18px;overflow:hidden">
<tr><td style="padding:30px 32px;background:#261c40;color:#fff">
<p style="margin:0 0 12px;color:#d6c3ff;font-size:10px;letter-spacing:2px">{{ $personal ? 'PERSONAL PORTFOLIO / NEW INQUIRY' : 'ITECHORBIT / NEW BUSINESS INQUIRY' }}</p>
<p style="margin:0;font-size:23px;font-weight:bold;line-height:1.3">{{ $personal ? $owner->name : 'iTechOrbit' }}</p>
</td></tr>
<tr><td style="padding:32px">
<h1 style="margin:0 0 18px;font-size:27px;letter-spacing:-.5px;line-height:1.3">A new conversation starts here.</h1>
<p style="margin:0 0 18px">Hello {{ $owner->name }},<br>{{ $inquiry->name }} {{ $personal ? 'reached out through your portfolio.' : 'sent an inquiry through the iTechOrbit website.' }} Here is their message.</p>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f7f5fb;border:1px solid #e9e2f2;border-radius:10px">
<tr><td style="padding:18px 20px;color:#73677f;font-size:11px;width:70px">FROM</td><td style="padding:18px 20px 18px 0;font-size:14px;font-weight:bold">{{ $inquiry->name }}</td></tr>
<tr><td style="padding:0 20px 18px;color:#73677f;font-size:11px">EMAIL</td><td style="padding:0 20px 18px 0;font-size:13px;overflow-wrap:anywhere"><a href="mailto:{{ $inquiry->email }}" style="color:#6942d4">{{ $inquiry->email }}</a></td></tr>
<tr><td style="padding:0 20px 18px;color:#73677f;font-size:11px">SUBJECT</td><td style="padding:0 20px 18px 0;font-size:14px">{{ $inquiry->subject }}</td></tr>
</table>
<p style="margin:26px 0 12px;color:#73677f;font-size:10px;letter-spacing:1.5px">THE MESSAGE</p>
<div style="padding:20px;border-left:3px solid #7951c9;background:#fbfaff;color:#32283e;font-size:15px;line-height:1.85;overflow-wrap:anywhere">{!! nl2br(e($inquiry->message)) !!}</div>
<table role="presentation" cellspacing="0" cellpadding="0" style="margin-top:28px"><tr><td style="background:#6942d4;border-radius:8px"><a href="mailto:{{ $inquiry->email }}?subject={{ rawurlencode('Re: '.$inquiry->subject) }}" style="display:inline-block;padding:13px 22px;color:#fff;font-size:13px;font-weight:bold;text-decoration:none">Reply to {{ $inquiry->name }} &rarr;</a></td></tr></table>
<p style="margin:18px 0 0;color:#73677f;font-size:12px">You can also reply directly to this email. Your reply will go to {{ $inquiry->name }}.</p>
<p style="margin:18px 0 0;font-size:12px"><a href="{{ url('/contacts/'.$inquiry->id) }}" style="color:#6942d4">{{ $personal ? 'Open this message in your personal inbox' : 'Open this inquiry in the business inbox' }}</a></p>
</td></tr>
<tr><td style="padding:20px 32px;border-top:1px solid #e9e2f2;background:#fcfbfe;color:#73677f;font-size:11px">{{ $personal ? 'This inquiry was addressed to '.$owner->name.' through their personal portfolio.' : 'An iTechOrbit website inquiry. Reply directly to continue the conversation.' }}</td></tr>
</table>
</td></tr></table>
</body></html>
