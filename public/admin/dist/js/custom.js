// Custom js
/*
Sidebar active link script start
*/
let path = window.location.href;
path = path.replace(/\/$/, "");
path = decodeURIComponent(path);
// console.log(path);
$("li.nav-link-child").each(function () {
    let thisNav = $(this).find('a.nav-link');
    let href = thisNav.attr('href');
    if (path === href || path.match(href)) {
        thisNav.addClass('active');
        thisNav.closest('li.nav-link-parent').addClass('menu-open');
        thisNav.closest('ul.nav-treeview').css('display', 'block');
    }
});
/*
----Sidebar active link script end
*/

/*
Onchange event start
*/
$(document).ready(function () {
    $('select.role-selected').on('select2:select', function (e) {
        let role_id = $(e.currentTarget).val();
        // console.log(role_id);
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            method: "post",
            url: "get-permissions",
            data: {role_id: role_id},
            datatype: 'html'
        }).done(function (data) {
            $('div.permission-list').html(data);
        });
    });
});
/*
Onchange event end
*/


/*
Global form submit start
*/
function formSubmit(form_data) {
    let editor_input_fields = $(form_data).find('textarea.text-editor');
    let url = $(form_data).attr('action');
    let request_type = $(form_data).attr('method');

    if (editor_input_fields.length > 0) {
        tinyMCE.triggerSave(); // update text editor value before request to server
    }

    $.ajax({
        type: request_type,
        url: url,
        data: new FormData(form_data),
        contentType: false,
        cache: false,
        processData: false,
        dataType: 'json'
    }).done(function (data) {
        console.log(data);
        let message = `${data.success}`;
        alert_message_handler('success', message);
        form_data.reset();
    }).fail(function (errors) {
        if (errors.status === 403) {
            alert_message_handler('error', errors.responseJSON.message);
            return false;
        }
        let errorMsg = null;
        errors = errors.responseJSON.errors;

        let validations = $(form_data).find('.required_validation');
        validations.each(function (key, element) {
            let dom_element = $(element);
            let search_key = dom_element.attr('name');
            dom_element.closest('div').find('.invalid-feedback').remove();
            if (errors.hasOwnProperty(search_key)) {
                errorMsg = `<span class="invalid-feedback"><strong>${errors[search_key][0]}</strong></span>`;
                dom_element.addClass('is-invalid').after(errorMsg);
            } else {
                dom_element.removeClass('is-invalid');
            }
        });
    });
}

/*
Global form submit end
*/

/*
Global delete form start
*/
function form_delete(element) {
    let action_url = element.attr('data-action');
    Swal.fire({
        title: 'Warning! Do you want to delete this item ?',
        showCancelButton: true,
        confirmButtonText: 'Delete',
    }).then((result) => {
        /* Read more about isConfirmed, isDenied below */
        if (result.isConfirmed) {
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                type: "DELETE",
                url: action_url,
                dataType: "JSON",
            }).done(function (data) {
                let message = data.success;
                if (message) {
                    let is_serverside_datatable = element.closest('table.datatable-custom');
                    if (is_serverside_datatable.length > 0) {
                        serverside_datatable.ajax.reload();
                    } else {
                        element.closest('tr').remove();
                    }
                }
                alert_message_handler('success', message);
            }).fail(function (errors) {
                if (errors.status === 403) {
                    alert_message_handler('error', errors.responseJSON.message);
                    return false;
                }
            });
        } else {
            alert_message_handler('error', 'Delete request canceled');
        }
    })
}

/*
Global delete form end
*/

/*
Global file delete form start
*/
function form_file_delete(element) {
    let action_url = element.attr('data-action');
    let id = element.attr('data-id');
    let field_name = element.attr('data-field');
    Swal.fire({
        title: 'Warning! Do you want to delete this item ?',
        showCancelButton: true,
        confirmButtonText: 'Delete',
    }).then((result) => {
        /* Read more about isConfirmed, isDenied below */
        if (result.isConfirmed) {
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                type: "POST",
                url: action_url,
                data:{ id: id, field_name: field_name},
                dataType: "JSON",
            }).done(function (data) {
                console.log(data);
                let message = data.success;
                if (message) {
                    element.closest('div.uploaded-file-view').remove();
                }
                alert_message_handler('success', message);
            }).fail(function (errors) {
                if (errors.status === 403) {
                    alert_message_handler('error', errors.responseJSON.message);
                    return false;
                }
            });
        } else {
            alert_message_handler('error', 'Delete request canceled');
        }
    })
}

/*
Global file delete form end
*/


/*
Permission handler globally start
*/
function permission_handler(link_id, route_name, e) {
    let is_checked = e.is(':checked');
    let role_id = $('select.role-selected').val();
    let permission_name = e.attr('data-view');
    // console.log(role_id, link_id, route, is_checked);
    $.ajax({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        method: "post",
        url: "cr-permission",
        data: {role_id: role_id, link_id: link_id, route_name: route_name, is_checked: is_checked},
    }).done(function (data) {
        // console.log(data);
        let role_name = $('select.role-selected').find('option:selected').text();
        let message = `<span class="text-primary">${to_capitalize(permission_name)}</span> permission <span class="text-success">${is_checked ? 'given' : 'remove'}</span> successfully for <span class="text-primary">${to_capitalize(role_name)}</span>`;
        alert_message_handler('success', message);
    }).fail(function (errors) {
        if (errors.status === 403) {
            alert_message_handler('error', errors.responseJSON.message);
            return false;
        }
    });
}

/*
Permission handler globally End
*/


/*
Alert message handler
*/
function alert_message_handler(alert_type, alert_message) {
    let Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 5000
    });
    Toast.fire({
        icon: `${alert_type}`,
        title: `${to_capitalize(alert_type)}! ${alert_message}.`
    });
}

/*
Alert message handler End
*/


/*
Text capitalize start
*/
function to_capitalize([first, ...rest]) {
    return first.toUpperCase() + rest.join('').toLowerCase();
}

/*
Text capitalize end
*/

/*
Text capitalize end
*/

/*
Check empty or undefined start
*/
function empty_or_null(data) {
    return typeof (data) !== 'undefined' && data !== '';
}

/*
Check empty or undefined
*/

