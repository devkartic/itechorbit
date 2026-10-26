<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $mailTitle ?? 'iTechOrbit' }}</title><style>@media only screen and (max-width:600px){.mail-section{padding:16px!important}}</style></head>
<body style="margin:0;padding:0;background:#f4f3f9;font-family:Arial,Helvetica,sans-serif;color:#26223b;line-height:1.7">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;table-layout:fixed"><tr><td align="center" style="padding:12px">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" data-mail-theme="itechorbit" style="width:100%;table-layout:fixed;border:1px solid #e5e1ef;border-radius:12px;background:#fff;overflow:hidden;overflow-wrap:anywhere;word-wrap:break-word">
<tr><td class="mail-section" style="padding:24px;background:#261c40;color:#fff"><p style="margin:0;color:#d6c3ff;font-size:11px;letter-spacing:1px">{{ $mailLabel ?? 'ITECHORBIT · ACCOUNT UPDATE' }}</p><h1 style="margin:10px 0 0;font-size:24px;line-height:1.4">{{ $mailTitle ?? 'iTechOrbit' }}</h1></td></tr>
<tr><td class="mail-section" style="padding:24px;font-size:15px;line-height:1.85">@yield('content')</td></tr>
<tr><td class="mail-section" style="padding:24px;border-top:1px solid #e5e1ef;font-size:12px;color:#686477">@yield('footer', 'This email was sent by iTechOrbit. If you need help, please contact our team.')</td></tr>
</table></td></tr></table></body></html>
