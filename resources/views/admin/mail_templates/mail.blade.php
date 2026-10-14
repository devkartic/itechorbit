<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<div style="background: #ffffff;  box-shadow: 0 3px 10px rgb(0 0 0 / 0.2);border-radius: 15px;">
    <h2 style="text-align: center;padding: 15px;margin-top: 0;color: darkblue">
        <a href="{{ url('/') }}" style="text-decoration: none;" target="_blank">{{ config('app.name', 'itechorbit') }}</a>
    </h2>
    <div style="padding-left: 15px">
        <h4>Hello {{ $name }},</h4>
        <p>{!! $message_body !!}</p>
        <h4>Thanks,</h4>
        <h4 style="color: darkblue">{{ config('app.name', 'itechorbit') }} Team</h4>
    </div>
    <h3 style="padding: 15px;color:darkgreen">Thank you for being with us!!!</h3>
</div>
</body>
</html>
