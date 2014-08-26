(function ($) {
    var $modal = $('<div class="modal fade">');
    var $modalDialog = $('<div class="modal-dialog">').appendTo($modal);
    var $modalContent = $('<div class="modal-content">').appendTo($modalDialog);
    var modalRequest = null;
    $(document).on('ready', function () {
        $modal.appendTo('body');
    });

    function openModal(request, success) {
        if (modalRequest != null) {
            modalRequest.abort();
        }

        $modalContent.children().fadeTo(400, 0.4);
        $modal.modal('show');

        $modal.off('submit.modal');
        $modal.on('submit.modal', 'form', function (e) {
            e.preventDefault();
            var $form = $(this);
            var action = $.prop(this, 'action');
            var method = $.prop(this, 'method') || 'POST';
            var params = $form.serialize();
            var submit = $.ajax({
                url: action,
                data: params,
                method: method
            });
            openModal(submit, success);
        });

        request.done(function (response) {
            if (response != null) {
                $modalContent.html(response).trigger('update');
            } else {
                // in the case the response is empty don't show it
                closeModal();
                if ($.isFunction(success)) {
                    success();
                }
            }
        });
        request.fail(function (e) {
            alert("Unexpected error");
            console.error(arguments);
        });
    }

    function closeModal() {
        $modal.modal('hide');
    }

    //////////////////
    // FORM BINDING //
    //////////////////

    $(document).on('click', '[data-update-partial-url][data-modal-url] button', function (e) {
        e.preventDefault();
        var $partial = $(this).closest('[data-modal-url]');
        var entityClass = $partial.data('entity-class').replace(/\\/g, '\\\\');
        openModal($.get($partial.data('modal-url')), function () {
            // find all entity_plus widgets and update them
            var $siblings = $('div[data-entity-class="' + entityClass + '"]').not($partial);
            $siblings.trigger('reload-partial', [false]);
            $partial.trigger('reload-partial', [true]);
            console.log($partial, $siblings);
        });
    });

    $(document).on('reload-partial', '[data-update-partial-url][data-modal-url]', function (e, setNewValue) {
        var $partial = $(this);
        var url = $partial.data('update-partial-url');
        var oldValue = $partial.find('select').val();
        var oldOptions = $partial.find('option');

        var request = $.get(url);
        request.done(function (response) {
            var $newPartial = $(response);
            var $select = $newPartial.find('select');
            $partial.replaceWith($newPartial);
            $newPartial.trigger('update');

            if (setNewValue) {
                $select.find('option').prop('selected', function () {
                    var valueExistedBefore = oldOptions.filter('[value="' + this.value + '"]').length > 0;
                    return valueExistedBefore ? (void 0) : true;
                });
            } else {
                $select.val(oldValue);
            }
            $select.trigger('change');
        });
        request.fail(function () {
            alert("Unexpected error");
            console.error(arguments);
        });
    });

})(jQuery);