jQuery(function ($) {
    var messageTimer = null;
    var actionInFlight = false;
    var pendingOrderRequests = 0;
    var config = window.wooZoMygenikiLite || {};
    var strings = config.i18n || {};
    var $orderWrap = $('#woo-zo-mygeniki-lite-metabox[data-plugin="woo-zo-mygeniki-lite"]');

    function ensureConfirmDialog() {
        var $dialog = $('#woo-zo-mygeniki-lite-confirm');
        if ($dialog.length) {
            return $dialog;
        }

        $('body').append(
            '<div id="woo-zo-mygeniki-lite-confirm" class="woo-zo-mgl-confirm" style="display:none;">' +
                '<div class="woo-zo-mgl-confirm-backdrop"></div>' +
                '<div class="woo-zo-mgl-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="woo-zo-mgl-confirm-title">' +
                    '<h3 id="woo-zo-mgl-confirm-title" class="woo-zo-mgl-confirm-title"></h3>' +
                    '<p class="woo-zo-mgl-confirm-text"></p>' +
                    '<div class="woo-zo-mgl-confirm-actions">' +
                        '<button type="button" class="button button-primary woo-zo-mgl-confirm-yes"></button>' +
                        '<button type="button" class="button woo-zo-mgl-confirm-no"></button>' +
                    '</div>' +
                '</div>' +
            '</div>'
        );

        return $('#woo-zo-mygeniki-lite-confirm');
    }

    function openCancelConfirm(reference, onConfirm) {
        var $dialog = ensureConfirmDialog();
        var message = reference
            ? strings.cancelMessage.replace('%s', reference)
            : strings.cancelMessageEmpty;

        $dialog.find('.woo-zo-mgl-confirm-title').text(strings.cancelTitle);
        $dialog.find('.woo-zo-mgl-confirm-text').text(message);
        $dialog.find('.woo-zo-mgl-confirm-yes').text(strings.confirmYes);
        $dialog.find('.woo-zo-mgl-confirm-no').text(strings.confirmCancel);
        $dialog.fadeIn(120);

        $dialog.off('click.wooZoMygenikiLiteConfirm');
        $dialog.on('click.wooZoMygenikiLiteConfirm', '.woo-zo-mgl-confirm-no, .woo-zo-mgl-confirm-backdrop', function () {
            $dialog.fadeOut(120);
        });
        $dialog.on('click.wooZoMygenikiLiteConfirm', '.woo-zo-mgl-confirm-yes', function () {
            $dialog.fadeOut(120);
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
        });
    }

    function injectUpdatePageLogo() {
        if (!config.pluginFile || !config.logoUrl) {
            return;
        }

        var $input = $('input[name="checked[]"][value="' + config.pluginFile + '"]');
        if (!$input.length) {
            return;
        }

        var $row = $input.closest('tr');
        var $target = $row.find('.plugin-title strong').first();
        if (!$target.length) {
            $target = $row.find('td').first();
        }
        if (!$target.length || $target.find('.woo-zo-mgl-update-logo').length) {
            return;
        }

        $target.prepend('<img src="' + config.logoUrl + '" alt="" class="woo-zo-mgl-update-logo" />');
    }

    injectUpdatePageLogo();

    function showMessage($container, message, isError) {
        if (!$container.length) {
            return;
        }

        if (messageTimer) {
            clearTimeout(messageTimer);
        }

        $container
            .removeClass('notice-success notice-error')
            .addClass(isError ? 'notice notice-error inline' : 'notice notice-success inline')
            .empty()
            .append($('<p />').text(message));

        messageTimer = setTimeout(function () {
            $container.removeClass('notice notice-success notice-error inline').empty().hide();
        }, 25000);
    }

    function getOrderWrap() {
        return $orderWrap.length ? $orderWrap : $('#woo-zo-mygeniki-lite-metabox[data-plugin="woo-zo-mygeniki-lite"]');
    }

    function beginOrderRequest($wrap) {
        pendingOrderRequests += 1;
        $wrap.addClass('is-busy').attr('aria-busy', 'true');
        $wrap.find('.woo-zo-mgl-field, .woo-zo-mgl-action').prop('disabled', true);
        $wrap.find('.woo-zo-mgl-loading').prop('hidden', false);
    }

    function endOrderRequest($wrap) {
        pendingOrderRequests = Math.max(0, pendingOrderRequests - 1);
        if (pendingOrderRequests > 0) {
            return;
        }

        $wrap.removeClass('is-busy').attr('aria-busy', 'false');
        $wrap.find('.woo-zo-mgl-field, .woo-zo-mgl-action').prop('disabled', false);
        $wrap.find('.woo-zo-mgl-loading').prop('hidden', true);
    }

    function setReference($wrap, reference) {
        var $holder = $wrap.find('.woo-zo-mgl-reference-wrap');
        if (!$holder.length) {
            return;
        }

        if (reference) {
            $holder.html(
                '<a class="woo-zo-mgl-reference-link woo-zo-mgl-reference" href="https://www.taxydromiki.com/track/' + encodeURIComponent(reference) + '" target="_blank" rel="noopener noreferrer">' +
                    $('<div />').text(reference).html() +
                '</a>'
            );
        } else {
            $holder.html('<span class="woo-zo-mgl-reference"></span>');
        }
    }

    function runOrderAction(actionName, $wrap) {
        var map = {
            create_print: 'woo_zo_mygeniki_lite_create_print',
            cancel: 'woo_zo_mygeniki_lite_cancel',
            track: 'woo_zo_mygeniki_lite_track'
        };
        if (actionInFlight) {
            return;
        }

        actionInFlight = true;
        beginOrderRequest($wrap);

        $.post(config.ajaxUrl, {
            action: map[actionName],
            nonce: config.nonce,
            order_id: $wrap.data('order-id')
        }).done(function (response) {
            var payload = response && response.data ? response.data : {};
            var message = payload.message || (response && response.success ? strings.actionCompleted : strings.requestFailed);

            showMessage($wrap.find('.woo-zo-mgl-message'), message, !(response && response.success));
            if (response && response.success && payload.reference) {
                setReference($wrap, payload.reference);
            }
            if (response && response.success && payload.status) {
                $wrap.find('.woo-zo-mgl-tracking').text(message);
            }
            if (response && response.success && payload.pdf_url) {
                window.open(payload.pdf_url, '_blank');
            }
            if (response && response.success && actionName === 'cancel') {
                setReference($wrap, '');
                $wrap.find('.woo-zo-mgl-tracking').text('');
            }
        }).fail(function () {
            showMessage($wrap.find('.woo-zo-mgl-message'), strings.requestFailed, true);
        }).always(function () {
            actionInFlight = false;
            endOrderRequest($wrap);
        });
    }

    getOrderWrap().on('change', '.woo-zo-mgl-field', function (event) {
        event.stopPropagation();

        var $field = $(this);
        var $wrap = getOrderWrap();
        var field = $field.data('field');
        var value = $field.is(':checkbox') ? ($field.is(':checked') ? 1 : 0) : $field.val();
        if (!$wrap.length) {
            return;
        }

        beginOrderRequest($wrap);
        $.post(config.ajaxUrl, {
            action: 'woo_zo_mygeniki_lite_save_options',
            nonce: config.nonce,
            order_id: $wrap.data('order-id'),
            field: field,
            value: value
        }).done(function (response) {
            var payload = response && response.data ? response.data : {};
            showMessage(
                $wrap.find('.woo-zo-mgl-message'),
                payload.message || (response && response.success ? strings.actionCompleted : strings.requestFailed),
                !(response && response.success)
            );
        }).fail(function () {
            showMessage($wrap.find('.woo-zo-mgl-message'), strings.requestFailed, true);
        }).always(function () {
            endOrderRequest($wrap);
        });
    });

    getOrderWrap().on('click', '.woo-zo-mgl-action', function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        event.stopPropagation();

        var $wrap = getOrderWrap();
        var actionName = $(this).data('action');
        if (!$wrap.length) {
            return;
        }

        if ('cancel' === actionName) {
            openCancelConfirm($.trim($wrap.find('.woo-zo-mgl-reference').text()), function () {
                runOrderAction(actionName, $wrap);
            });

            return;
        }

        runOrderAction(actionName, $wrap);
    });

    $(document).on('click', '#woo-zo-mgl-clear-pdfs', function () {
        $.post(config.ajaxUrl, {
            action: 'woo_zo_mygeniki_lite_clear_pdfs',
            nonce: config.nonce
        }).done(function (response) {
            var payload = response && response.data ? response.data : {};
            showMessage(
                $('#woo-zo-mgl-settings-message'),
                payload.message || (response && response.success ? strings.actionCompleted : strings.requestFailed),
                !(response && response.success)
            );
            if (response && response.success) {
                setTimeout(function () { window.location.reload(); }, 800);
            }
        }).fail(function () {
            showMessage($('#woo-zo-mgl-settings-message'), strings.requestFailed, true);
        });
    });

});
