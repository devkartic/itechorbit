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
                    "url": "{{ url('get-users') }}",
                    "dataType": "json",
                    "type": "get",
                    data: function ($data) {
                        // console.log($data);
                    }
                },
                "columns": [
                    {data: 'id'},
                    {data: 'role'},
                    {data: 'name'},
                    {data: 'email'},
                    {data: 'status'},
                    {data: 'portfolio_status'},
                    {data: 'created_at'},
                    {data: 'updated_at'},
                    {data: 'actions'}
                ]
            });
        });

        /*
        Global User enable or disable start
        */
        function enable_or_disable_user(element) {
            let action_url = element.attr('data-action');
            let user_id = element.attr('data-id');
            let is_enabled = Boolean(parseInt(element.attr('data-is-enabled')));
            let action_name = 'enable';
            if (is_enabled) action_name = 'disable';

            Swal.fire({
                title: `Warning! Do you want to ${action_name} this User ?`,
                showCancelButton: true,
                confirmButtonText: to_capitalize(action_name),
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: "POST",
                        url: action_url,
                        data: {user_id: user_id, is_enable: !is_enabled},
                        dataType: "JSON",
                    }).done(function (data) {
                        let message = data.success;
                        if (message) {
                            serverside_datatable.ajax.reload();
                        }
                        alert_message_handler('success', message);
                    }).fail(function (errors) {
                        if (errors.status === 403) {
                            alert_message_handler('error', 'Access forbidden! Please contact with the site administrator');
                        }
                    });
                } else {
                    alert_message_handler('error', 'Module delete request canceled');
                }
            })
        }

        /*
        User enable or disable end
        */

        /*
        User portfolio enable or disable start
        */
        function enable_or_disable_portfolio(element) {
            let action_url = element.attr('data-action');
            let user_id = element.attr('data-id');
            let is_enabled = Boolean(parseInt(element.attr('data-is-enabled')));
            let action_name = 'enable';
            if (is_enabled) action_name = 'disable';

            Swal.fire({
                title: `Warning! Do you want to ${action_name} portfolio this User ?`,
                showCancelButton: true,
                confirmButtonText: to_capitalize(action_name),
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: "POST",
                        url: action_url,
                        data: {user_id: user_id, is_enable: !is_enabled},
                        dataType: "JSON",
                    }).done(function (data) {
                        let message = data.success;
                        if (message) {
                            serverside_datatable.ajax.reload();
                        }
                        alert_message_handler('success', message);
                    }).fail(function (errors) {
                        if (errors.status === 403) {
                            alert_message_handler('error', 'Access forbidden! Please contact with the site administrator');
                        }
                    });
                } else {
                    alert_message_handler('error', 'Module delete request canceled');
                }
            })
        }

        /*
        User portfolio enable or disable end
        */
    </script>
@endsection
