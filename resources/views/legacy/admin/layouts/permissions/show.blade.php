<div class="col-md-12">
    @if(@$role)
    <h3 class="card-title my-2 text-primary">Note: The following permissions, which are checked, were assigned to the <span class="font-weight-bold text-success">{{ $role }}.</span></h3>
    <table id="example1" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Links</th>
            <th>View</th>
            <th>Create</th>
            <th>Edit</th>
            <th>Delete</th>
        </tr>
        </thead>
        <tbody>
        @foreach($links as $key => $link)
            <tr>
                <td class="font-weight-bold pl-3">{{ $link->name }}</td>
                <td class="pl-3">
                    <div class="icheck-primary d-inline">
                        <input type="checkbox" data-view="view" onclick="return permission_handler({{ $link->id }}, 'index', $(this))" id="link-show-{{ $key }}" @if($link->view) checked="checked" @endif />
                        <label for="link-show-{{ $key }}">
                        </label>
                    </div>
                </td>
                <td class="pl-3">
                    <div class="icheck-primary d-inline">
                        <input type="checkbox" data-view="create" onclick="return permission_handler({{ $link->id }}, 'create', $(this))" id="link-create-{{ $key }}" @if($link->create) checked="checked" @endif />
                        <label for="link-create-{{ $key }}">
                        </label>
                    </div>
                </td>
                <td class="pl-3">
                    <div class="icheck-primary d-inline">
                        <input type="checkbox" data-view="edit" onclick="return permission_handler({{ $link->id }}, 'edit', $(this))" id="link-edit-{{ $key }}" @if($link->edit) checked="checked" @endif />
                        <label for="link-edit-{{ $key }}">
                        </label>
                    </div>
                </td>
                <td class="pl-3">
                    <div class="icheck-primary d-inline">
                        <input type="checkbox" data-view="delete" onclick="return permission_handler({{ $link->id }}, 'destroy', $(this))" id="link-destroy-{{ $key }}" @if($link->destroy) checked="checked" @endif />
                        <label for="link-destroy-{{ $key }}">
                        </label>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <th>Links</th>
            <th>View</th>
            <th>Create</th>
            <th>Edit</th>
            <th>Delete</th>
        </tr>
        </tfoot>
    </table>
    <!-- /.form-group -->
    @else
        <div class="card">
            <div class="card-body font-weight-bold text-danger">
                Result not found!
            </div>
        </div>
    @endif
</div>
