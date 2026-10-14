<!DOCTYPE html>
<html lang="en">
@include('admin.includes.header')
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">

    <!-- Preloader -->
    <div class="preloader flex-column justify-content-center align-items-center">
        <x-brand-logo :width="240" />
    </div>

    <!-- Navbar -->
    @include('admin.includes.nav')
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    @include('admin.includes.side_bar')

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        @include('admin.includes.header_content')
        <!-- /.content-header -->

        <!-- Main content -->
        @yield('content')
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <!-- Footer Wrapper. Contains page footer -->
    @include('admin.includes.footer')
    <!-- /.footer-wrapper -->

    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
        <!-- Control sidebar content goes here -->
    </aside>
    <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

@include('admin.includes.bottom')
</body>
</html>
