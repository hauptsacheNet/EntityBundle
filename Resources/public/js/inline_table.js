$(document).editable({
    selector: '.editable',
    params: function (params) {
        var $field = $(this);
        var fieldName = $field.data('name');
        var result = {};
        result[fieldName] = params.value;
        return result;
    }
});

//$(document).on('ready update', function (e) {
//    var $target = $(e.target);
//});
