<!DOCTYPE html>
<html lang="en">
@section('page_title', config('app.name', 'itechorbit') . ' | ' . ($authPageTitle ?? 'Login'))
@include('admin.includes.header')
<body class="hold-transition login-page">
@yield('content')
<!-- /.login-box -->
@include('admin.includes.bottom')
</body>
</html>
