$(document).editable({
    selector: '.editable',
    onblur: 'ignore',
    params: function (params) {
        var $field = $(this);
        var fieldName = $field.data('name');
        var result = {};
        result[fieldName] = params.value;
        return result;
    }
});

var hideEditableOnOutsideClickHandlers = [];
var $editable = $('.editable');
$editable.on('shown', function (e, editable) {
    function hideEditableOnOutsideClick(e) {
        // ignore click event on body
        if (e.target.tagName.toLowerCase() === "body") {
            return;
        }

        // does editable popover contain the clicked element?
        var popover = editable.container.$form.parents('.editable-container');

        // of not, hide the editable
        if (!$.contains(popover[0], e.target)) {
            editable.hide();
        }
    }

    setTimeout(function () {
        document.addEventListener('click', hideEditableOnOutsideClick, true);
        hideEditableOnOutsideClickHandlers.push(hideEditableOnOutsideClick);
    }, 0);
});


$editable.on('hidden', function (e, editable) {
    hideEditableOnOutsideClickHandlers.forEach(function (handler) {
        document.removeEventListener('click', handler, true);
    });
    hideEditableOnOutsideClickHandlers = [];
});


//$(document).on('ready update', function (e) {
//    var $target = $(e.target);
//});
