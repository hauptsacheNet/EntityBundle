(function ($) {
    $(document).on('ready update', function (e) {
        var $target = $(e.target);

        $('[data-trigger-updateables]', $target).on('change', function(evt) {
            var $this = $(this);
            var $trigger = $(evt.target);
            var $form = $trigger.parents('form');
            var data = $form.serialize();

            console.log($this);

            var $updateable = $this.closest(':has([data-update-url])').find('[data-update-url]');

            $updateable.prop('disabled', true);

            var url = $updateable.data('update-url');
            var cb = (function($element) {
                return function() {
                    $element.prop('disabled', false);
                    $element.trigger('update').trigger('change');
                }
            })($updateable);

            $updateable.load(url, data, cb);
        });
    });

})(jQuery);