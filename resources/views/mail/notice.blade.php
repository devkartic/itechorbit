<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $campaign->subject }}</title><style>@media only screen and (max-width:600px){.notice-section{padding:16px!important}}</style></head>
<body style="margin:0;padding:0;background:#f4f3f9;font-family:Arial,Helvetica,sans-serif;color:#26223b;line-height:1.7">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;table-layout:fixed"><tr><td style="padding:12px">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;table-layout:fixed;border:1px solid #e5e1ef;border-radius:12px;background:#fff;overflow:hidden;overflow-wrap:anywhere;word-wrap:break-word">
<tr><td class="notice-section" style="padding:24px;background:#261c40;color:#fff"><p style="margin:0;color:#d6c3ff;font-size:11px;letter-spacing:1px">ITECHORBIT · NOTICE &amp; UPDATE</p><h1 style="margin:10px 0 0;font-size:24px;line-height:1.4">{{ $campaign->subject }}</h1></td></tr>
<tr><td class="notice-section" style="padding:24px"><p style="margin:0 0 20px;font-size:15px">Dear {{ trim((string) $recipient->name) ?: 'iTechOrbit member' }},</p><div style="font-size:15px;line-height:1.85;overflow-wrap:anywhere">{!! $campaign->body !!}</div><p style="margin:24px 0 0;padding-top:20px;border-top:1px solid #e5e1ef;font-size:15px;line-height:1.7">Kind regards,<br><strong style="font-weight:600;color:#261c40">The iTechOrbit Team</strong></p></td></tr>
<tr><td class="notice-section" style="padding:24px;border-top:1px solid #e5e1ef;font-size:12px;color:#686477">This notice was sent to you as an iTechOrbit account holder.</td></tr>
</table></td></tr></table></body></html>
