$(document).ready(function () {
    /*
    Scroll Adjustment with header nav
    */
    const getOffset = () => {
        let topPos = $(window).scrollTop();
        if (topPos > 0) $('header#header').addClass('header-scrolled');
    }
    $(document).scroll(() => getOffset());

});


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
                    element.closest('.reply_block').remove();
                }
                alert_message_handler('success', message);
            }).fail(function (errors) {
                if (errors.status === 403) {
                    alert_message_handler('error', errors.responseJSON.message);
                    return false;
                }
            });
        } else {
            alert_message_handler('error', 'Module delete request canceled');
        }
    })
}

/*
Global delete form end
*/

/*
Comment edit start
*/
function comment_edit(element) {
    let action_edit = element.attr('data-action');
    let action_update = element.attr('data-action-update');
    let block = element.closest('.reply_block')
    $.ajax({
        type: "GET",
        url: action_edit,
        dataType: "JSON",
    }).done(function (data) {
        if (data) {
            let form_class = `.comment-${data.id}`;
            let form = block.find(form_class);
            form.attr('action', action_update);
            let _method = form.find("[name='_method']")
            if (_method.length === 0) form.prepend('<input type="hidden" name="_method" value="PATCH">');
            form.find('textarea.comment').html(data.comment);
            form.find('button.btn-primary').html('Update comment');
        }
    }).fail(function (errors) {
        if (errors.status === 403) {
            alert_message_handler('error', errors.responseJSON.message);
            return false;
        }
    });
}

/*
Comment edit end
*/

/*
Alert message handler
*/
function alert_message_handler(alert_type, alert_message) {
    if(!empty_or_null(alert_message)) alert_message = 'Access forbidden! Please contact with the site administrator';
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
Check empty or undefined start
*/
function empty_or_null(data) {
    return typeof (data) !== 'undefined' && data !== '';
}

/*
Check empty or undefined
*/
