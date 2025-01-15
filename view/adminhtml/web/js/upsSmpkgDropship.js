var upsSmDsFormId = "#upsSm-ds-form";
var upsSmDsEditFormData = '';

require(
    [
        'jquery',
        'Magento_Ui/js/modal/modal',
        'Magento_Ui/js/modal/confirm',
        'domReady!',
    ],
    function ($, modal, confirmation) {

        var addDsModal = $('#upsSm-ds-modal');
        var options = {
            type: 'popup',
            modalClass: 'upsSm-add-ds-modal',
            responsive: true,
            innerScroll: true,
            title: 'Drop Ship',
            closeText: 'Close',
            focus: upsSmDsFormId + ' #upsSm-ds-nickname',
            buttons: [{
                text: $.mage.__('Save'),
                class: 'en-btn save-ds-ds',
                click: function (data) {
                    var $this = this;
                    var form_data = upsSmGetFormData($, upsSmDsFormId);
                    var ajaxUrl = upsSmDsAjaxUrl + 'SaveDropship/';

                    if ($(upsSmDsFormId).valid() && upsSmDsZipMilesValid()) {
                        //If form data is unchanged then close the modal and show updated message
                        if (upsSmDsEditFormData !== '' && upsSmDsEditFormData === form_data) {
                            jQuery('.upsSm-ds-msg').text('Drop ship updated successfully.').show('slow');
                            upsSmScrollHideMsg(1, 'html,body', '.ds', '.upsSm-ds-msg');
                            addDsModal.modal('closeModal');
                        } else {
                            $.ajax({
                                url: ajaxUrl,
                                type: 'POST',
                                data: form_data,
                                showLoader: true,
                                success: function (data) {
                                    if (upsSmDropshipSaveResSettings(data)) {
                                        addDsModal.modal('closeModal');
                                    }
                                },
                                error: function (result) {
                                    console.log('no response !');
                                }
                            });
                        }
                    }
                }
            }],
            keyEventHandlers: {
                tabKey: function () {
                    return;
                },
                /**
                 * Escape key press handler,
                 * close modal window
                 */
                escapeKey: function () {
                    if (this.options.isOpen && this.modal.find(document.activeElement).length ||
                        this.options.isOpen && this.modal[0] === document.activeElement) {
                        this.closeModal();
                    }
                }
            },
            closed: function () {
                upsSmModalClose(upsSmDsFormId, '#ds-', $);
            }
        };


        $('body').on('click', '.upsSm-del-ds', function (event) {
            event.preventDefault();
            confirmation({
                title: 'UPS Small Package Quotes',
                content: 'Warning! If you delete this location, Drop ship location settings will be disabled against products.',
                actions: {
                    always: function () {
                    },
                    confirm: function () {
                        var dataset = event.currentTarget.dataset;
                        upsSmDeleteDropship(dataset.id, upsSmDsAjaxUrl);
                    },
                    cancel: function () {
                    }
                }
            });
            return false;
        });


        //Add DS
        $('#upsSm-add-ds-btn').on('click', function () {
            var popup = modal(options, addDsModal);
            addDsModal.modal('openModal');
        });

        //Edit WH
        $('body').on('click', '.upsSm-edit-ds', function () {
            var dsId = $(this).data("id");
            if (typeof dsId !== 'undefined') {
                upsSmEditDropship(dsId, upsSmDsAjaxUrl);
                setTimeout(function () {
                    var popup = modal(options, addDsModal);
                    addDsModal.modal('openModal');
                }, 500);
            }
        });

        //Add required to Local Delivery Fee if Local Delivery is enabled
        // $(upsSmDsFormId + ' #ds-enable-local-delivery').on('change', function () {
        //     if ($(this).is(':checked')) {
        //         $(upsSmDsFormId + ' #ds-ld-fee').addClass('required');
        //     } else {
        //         $(upsSmDsFormId + ' #ds-ld-fee').removeClass('required');
        //     }
        // });

        //Get data of Zip Code
        $(upsSmDsFormId + ' #upsSm-ds-zip').on('change', function () {
            var ajaxUrl = upsSmAjaxUrl + 'UpsSmallPkgOriginAddress/';
            $(upsSmDsFormId + ' #ds-city').val('');
            $(upsSmDsFormId + ' #ds-state').val('');
            $(upsSmDsFormId + ' #ds-country').val('');
            upsSmGetAddressFromZip(ajaxUrl, this, upsSmGetDsAddressResSettings);
            $(upsSmDsFormId).validation('clearError');
        });
    }
);

/**
 * Set Address from zipCode
 * @param {type} data
 * @returns {Boolean}
 */
function upsSmGetDsAddressResSettings(data)
{
    let id = upsSmDsFormId;
    if (data.country === 'US' || data.country === 'CA') {
        var oldNick = jQuery('#upsSm-ds-nickname').val();
        var newNick = '';
        var zip = jQuery('#upsSm-ds-zip').val();
        if (data.postcode_localities === 1) {
            jQuery(id + ' .city-select').show();
            jQuery(id + ' #ds-actname').replaceWith(data.city_option);
            jQuery(id + ' .city-multiselect').replaceWith(data.city_option);
            jQuery(id).on('change', '.city-multiselect', function () {
                var city = jQuery(this).val();
                jQuery(id + ' #ds-city').val(city);
                jQuery(id + ' #upsSm-ds-nickname').val(upsSmSetDsNickname(oldNick, zip, city));
            });
            jQuery(id + " #ds-city").val(data.first_city);
            jQuery(id + ' #ds-state').val(data.state);
            jQuery(id + ' #ds-country').val(data.country);
            jQuery(id + ' .city-input').hide();
            newNick = upsSmSetDsNickname(oldNick, zip, data.first_city);
        } else {
            jQuery(id + ' .city-input').show();
            jQuery(id + ' #wh-multi-city').removeAttr('value');
            jQuery(id + ' .city-select').hide();
            jQuery(id + ' #ds-city').val(data.city);
            jQuery(id + ' #ds-state').val(data.state);
            jQuery(id + ' #ds-country').val(data.country);
            newNick = upsSmSetDsNickname(oldNick, zip, data.city);
        }
        jQuery(id + ' #upsSm-ds-nickname').val(newNick);
    } else if (data.msg) {
        jQuery('.upsSm-ds-er-msg').text(data.msg).show('slow');
        upsSmScrollHideMsg(2, '', '.upsSm-ds-er-msg', '.upsSm-ds-er-msg');
    }
    return true;
}


function upsSmDsZipMilesValid()
{
    let id = upsSmDsFormId;
    var enable_instore_pickup = jQuery(id + " #ds-enable-instore-pickup").is(':checked');
    var enable_local_delivery = jQuery(id + " #ds-enable-local-delivery").is(':checked');
    if (enable_instore_pickup || enable_local_delivery) {
        var instore_within_miles = jQuery(id + " #ds-within-miles").val();
        var instore_postal_code = jQuery(id + " #ds-postcode-match").val();
        var ld_within_miles = jQuery(id + " #ds-ld-within-miles").val();
        var ld_postal_code = jQuery(id + " #ds-ld-postcode-match").val();

        switch (true) {
            case (enable_instore_pickup && (instore_within_miles.length == 0 && instore_postal_code.length == 0)):
                jQuery(id + ' .ds-instore-miles-postal-err').show('slow');
                upsSmScrollHideMsg(2, '', id + ' #ds-is-heading-left', '.ds-instore-miles-postal-err');
                return false;

            case (enable_local_delivery && (ld_within_miles.length == 0 && ld_postal_code.length == 0)):
                jQuery(id + ' .ds-local-miles-postals-err').show('slow');
                upsSmScrollHideMsg(2, '', id + ' #ds-ld-heading-left', '.ds-local-miles-postals-err');
                return false;
        }
    }
    return true;
}


function upsSmDropshipSaveResSettings(data)
{
    if (data.insert_qry == 1) {
        jQuery('.upsSm-ds-msg').text(data.msg).show('slow');

        jQuery('#append-dropship tr:last').after(
            '<tr id="row_' + data.id + '" data-id="' + data.id + '">' +
            '<td>' + data.nickname + '</td>' +
            upsSmGetRowData(data, 'ds') + '</tr>'
        );

        upsSmScrollHideMsg(1, 'html,body', '.ds', '.upsSm-ds-msg');
    } else if (data.update_qry == 1) {
        jQuery('.upsSm-ds-msg').text(data.msg).show('slow');

        jQuery('tr[id=row_' + data.id + ']').html('<td>' + data.nickname + '</td>' + upsSmGetRowData(data, 'ds'));

        upsSmScrollHideMsg(1, 'html,body', '.ds', '.upsSm-ds-msg');
    } else {
        jQuery('.upsSm-ds-er-msg').text(data.msg).show('slow');
        upsSmScrollHideMsg(2, '', '.upsSm-ds-er-msg', '.upsSm-ds-er-msg');
        return false;
    }

    return true;
}

function upsSmEditDropship(dataId, ajaxUrl)
{
    ajaxUrl = ajaxUrl + 'EditDropship/';
    var parameters = {
        'action': 'edit_dropship',
        'edit_id': dataId
    };

    upsSmAjaxRequest(parameters, ajaxUrl, upsSmDropshipEditResSettings);
    return false;
}

function upsSmDropshipEditResSettings(data)
{
    let id = upsSmDsFormId;
    if (data[0]) {
        jQuery(id + ' #ds-edit-form-id').val(data[0].warehouse_id);
        jQuery(id + ' #upsSm-ds-zip').val(data[0].zip);
        jQuery(id + ' #upsSm-ds-nickname').val(data[0].nickname);
        jQuery(id + ' .city-select').hide();
        jQuery(id + ' .city-input').show();
        jQuery(id + ' #ds-city').val(data[0].city);
        jQuery(id + ' #ds-state').val(data[0].state);
        jQuery(id + ' #ds-country').val(data[0].country);
        jQuery(id + ' #ds-origin-markup').val(data[0].markup);

        if (upsSmAdvancePlan) {
            // Load instore pickup and local delivery data
            if ((data[0].in_store != null && data[0].in_store != 'null')
                || (data[0].local_delivery != null && data[0].local_delivery != 'null')) {
                upsSmSetInspAndLdData(data[0], '#ds-');
            }
        }

        upsSmDsEditFormData = upsSmGetFormData(jQuery, upsSmDsFormId);
    }
    return true;
}

function upsSmDeleteDropship(deleteid, ajaxUrl)
{
    ajaxUrl = ajaxUrl + 'DeleteDropship/';
    var parameters = {
        'action': 'delete_dropship',
        'delete_id': deleteid
    };
    upsSmAjaxRequest(parameters, ajaxUrl, upsSmDropshipDeleteResSettings);

    return false;
}

function upsSmDropshipDeleteResSettings(data)
{
    if (data.qryResp == 1) {
        jQuery('#row_' + data.deleteID).remove();
        jQuery('.upsSm-ds-msg').text(data.msg).show('slow');
        upsSmScrollHideMsg(1, 'html,body', '.ds', '.upsSm-ds-msg');
    }
    return true;
}

function upsSmSetDsNickname(oldNick, zip, city)
{
    var nickName = '';
    var curNick = 'DS_' + zip + '_' + city;
    var pattern = /DS_[0-9 a-z A-Z]+_[a-z A-Z]*/;
    var regex = new RegExp(pattern, 'g');
    if (oldNick !== '') {
        nickName = regex.test(oldNick) ? curNick : oldNick;
    }
    return nickName;
}
