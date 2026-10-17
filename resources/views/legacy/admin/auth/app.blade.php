<!DOCTYPE html>
<html lang="en">
@section('page_title', config('app.name', 'itechorbit') . ' | ' . ($authPageTitle ?? 'Login'))
@include('legacy.admin.includes.header')
<body class="hold-transition login-page">
@yield('content')
<!-- /.login-box -->
@include('legacy.admin.includes.bottom')
</body>
</html>
