@section('library_top')
    @parent
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('admin/dist/css/jquery.dataTables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/dist/css/datatable-custom.css') }}">
@endsection
@section('library_bottom')
    @parent
    <script src="{{ asset('admin/dist/js/jquery.dataTables.min.js') }}"></script>
    <script>
        let serverside_datatable = null;
        $(document).ready(function () {
            serverside_datatable = $('.datatable-custom').DataTable({
                processing: true,
                serverSide: true,
                order: [[0, 'desc']],
                ajax: {
                    "url": "{{ url('get-posts') }}",
                    "dataType": "json",
                    "type": "get",
                    data: function ($data) {
                        // console.log($data);
                    }
                },
                "columns": [
                    { data: 'id' },
                    { data: 'category'},
                    { data: 'title'},
                    { data: 'body'},
                    { data: 'created_at'},
                    { data: 'updated_at'},
                    { data: 'created_by'},
                    { data: 'updated_by'},
                    { data: 'actions'}
                ]
            });
        });
    </script>
@endsection
