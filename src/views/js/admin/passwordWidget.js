var Biller = Biller || {};

(function () {
    Biller.passwordWidget = {
        init: function (wrapper) {
            let isPasswordVisible = false;
            let button = $(wrapper).find('button[data-action="show-hide-password"]');
            let input = $(wrapper).find('[type=password]');
            button.on('click', function () {
                if (isPasswordVisible) {
                    input.attr('type', 'password');
                    $(this).html($(this).data('show-label'));
                } else {
                    input.attr('type', 'text');
                    $(this).html($(this).data('hide-label'));
                }
                isPasswordVisible = !isPasswordVisible;
            });
        }
    };
})();
