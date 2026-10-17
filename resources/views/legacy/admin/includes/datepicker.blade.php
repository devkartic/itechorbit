@section('library_top')
    @parent
    <!-- Datepicker -->
    <link rel="stylesheet" href="{{ asset('admin/bootstrap-datepicker-1.9.0-dist/css/bootstrap-datepicker.css') }}">
@endsection
@section('library_bottom')
    @parent
    <script src="{{ asset('admin/bootstrap-datepicker-1.9.0-dist/js/bootstrap-datepicker.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            //Date picker initialization
            $('.datepicker').datepicker({
                format: 'dd-mm-yyyy',
                startDate: '-42y',
                endDate: '+1d'
            });
        });
    </script>
@endsection
