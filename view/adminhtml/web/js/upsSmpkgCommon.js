/**
 * Document load function
 * @type string
 */
const UpsSmUrl = 'https://eniture.com/magento-2-ups-small-package-quotes/';

require(['jquery','jquery/validate', 'domReady!'], function ($) {

    $('.bootstrap-tagsinput input').bind('keyup keydown', function (event) {
        upsSmValidateAlphaNumOnly($, this);
    });

    $('.decimalonly, .ngtvdecimal').bind('keyup keydown', function (e) {
        var id = this.id;
        if (id.indexOf('hndlngFee') !== -1) {
            var pattern = /^-?\d*(\.\d{0,2})?$/;
        } else {
            var pattern = /^\d*(\.\d{0,2})?$/;
        }
        var input = $(this);
        var oldVal = input.val();

        var regex = new RegExp(pattern, 'g');

        setTimeout(function () {
            var newVal = input.val();
            if (!regex.test(newVal)) {
                input.val(oldVal);
            }
        }, 4);
    });
    $('#upsQuoteSetting_third span, #upsconnsettings_first span').attr('data-config-scope', '');

    $('#upsQuoteSetting_third_hndlngFee').attr('title', 'Handling Fee / Markup');

});


function upsSmValidateAlphaNumOnly($, element) {
    var value = $(element);
    value.val(value.val().replace(/[^a-z0-9]/g, ''));
}

//This function serialize complete form data
function upsSmGetFormData($, formId) {
    // To initialize the Disabled inputs
    let disabled = $(formId).find(':input:disabled').removeAttr('disabled');
    let formData = $(formId).serialize();
    disabled.attr('disabled', 'disabled');
    let addData = '';
    $(formId + ' input[type=checkbox]').each(function () {
        if (!$(this).is(":checked")) {
            addData += '&' + $(this).attr('name') + '=';
        }
    });
    return formData + addData;
}

function upsSmCurrentPlanNote($, planMsg, carrierDiv) {
    let divAfter = '<div class="message message-notice notice upsSm-plan-note">' +
        '<div data-ui-id="messages-message-notice">' + planMsg + '</div></div>';
    upsSmNotesToggleHandling($, divAfter, '.upsSm-plan-note', carrierDiv);
}

function upsSmNotesToggleHandling($, divAfter, className, carrierDiv) {
    setTimeout(function () {
        if ($(carrierDiv).attr('class') === 'open') {
            console.log('');
            $(carrierDiv).after(divAfter);
        }
    }, 1000);
    $(carrierDiv).click(function () {
        if ($(carrierDiv).attr('class') === 'open') {
            $(carrierDiv).after(divAfter);
        } else if ($(className).length) {
            $(className).remove();
        }
    });
}

/**
 * @param canAddWh
 */
function upsSmAddWarehouseRestriction(canAddWh) {
    switch (canAddWh) {
        case 0:
            jQuery("#append-warehouse").find("tr").removeClass('inactiveLink');
            jQuery('#upsSm-add-wh-btn').addClass('inactiveLink');
            if (jQuery(".required-plan-msg").length == 0) {
                jQuery('#upsSm-add-wh-btn').after('<a href=' + UpsSmUrl + ' target="_blank" class="required-plan-msg">Standard Plan required</a>');
            }
            jQuery("#append-warehouse").find("tr:gt(1)").addClass('inactiveLink');
            break;
        case 1:
            jQuery('#upsSm-add-wh-btn').removeClass('inactiveLink');
            jQuery('.required-plan-msg').remove();
            jQuery("#append-warehouse").find("tr").removeClass('inactiveLink');
            break;
        default:
            break;
    }
}

/**
 * Restrict Quote Settings Fields
 * @param {string} qRestriction
 */
function upsSmPlanQuoteRestriction(qRestriction) {
    var quoteSecRowID = "#row_upsQuoteSetting_third_";
    var quoteSecID = "#upsQuoteSetting_third_";
    var parsedData = JSON.parse(qRestriction);
    if (parsedData['advance']) {
        jQuery('' + quoteSecRowID + 'transitDaysNumber').before('<tr><td><label><span data-config-scope=""></span></label></td><td class="value"><a href="'+UpsSmUrl+'" target="_blank" class="required-plan-msg adv-plan-err">Advance Plan required</a></td><td class=""></td></tr>');
        upsSmDisabledFieldsLoop(parsedData['advance'], quoteSecID);
    }

    if (parsedData['standard']) {
        var fields = ["UPSSurePost", "onlyGndService"];
        jQuery.each(fields, function (index, value) {
            jQuery(quoteSecRowID + value).before('<tr><td><label><span data-config-scope=""></span></label></td><td class="value"><a href="'+UpsSmUrl+'" target="_blank" class="required-plan-msg std-plan-err">Standard Plan required</a></td><td class=""></td></tr>');
            upsSmDisabledFieldsLoop(parsedData['standard'], quoteSecID);
        });
    }
}

function upsSmDisabledFieldsLoop(dataArr, quoteSecID) {
    jQuery.each(dataArr, function (index, value) {
        jQuery(quoteSecID + value).attr('disabled', 'disabled');
    });
}

function upsSmSetInspAndLdData(data, eleid) {
    var instore = JSON.parse(data.in_store);
    var localdel = JSON.parse(data.local_delivery);
    //Filling form data
    if (instore != null && instore != 'null') {
            instore.enable_store_pickup == 1 ? jQuery(eleid + 'enable-instore-pickup').prop('checked', true) : '';
            jQuery(eleid + 'within-miles').val(instore.miles_store_pickup);
            jQuery(eleid + 'postcode-match').tagsinput('add', instore.match_postal_store_pickup);
            jQuery(eleid + 'checkout-descp').val(instore.checkout_desc_store_pickup);
            instore.suppress_other == 1 ? jQuery(eleid + 'ld-sup-rates').prop('checked', true) : '';
    }

    if (localdel != null && localdel != 'null') {
            if (localdel.enable_local_delivery == 1) {
                jQuery(eleid + 'enable-local-delivery').prop('checked', true);
                jQuery(eleid + 'ld-fee').addClass('required');
            }
            jQuery(eleid + 'ld-within-miles').val(localdel.miles_local_delivery);
            jQuery(eleid + 'ld-postcode-match').tagsinput('add', localdel.match_postal_local_delivery);
            jQuery(eleid + 'ld-checkout-descp').val(localdel.checkout_desc_local_delivery);
            jQuery(eleid + 'ld-fee').val(localdel.fee_local_delivery);
            localdel.suppress_other == 1 ? jQuery(eleid + 'ld-sup-rates').prop('checked', true) : '';
    }
}

function upsSmModalClose(formId, ele, $) {
    $(formId).validation('clearError');
    $(formId).trigger("reset");
    $($(formId + " .bootstrap-tagsinput").find("span[data-role=remove]")).trigger("click");
    $(formId + ' ' + ele + 'ld-fee').removeClass('required');
    $(ele + 'edit-form-id').val('');
    $('.city-select').hide();
    $('.city-input').show();
}

/**
 * Get address against zipcode from smart street api
 * @param {string} ajaxUrl
 * @returns {Boolean}
 */
function upsSmGetAddressFromZip(ajaxUrl, $this, callfunction) {
    var zipCode = $this.value;
    if (zipCode === '') {
        return false;
    }
    var parameters = {'origin_zip': zipCode};

    upsSmAjaxRequest(parameters, ajaxUrl, callfunction);
}

/**
 * call for warehouse ajax requests
 * @param {array} parameters
 * @param {string} ajaxUrl
 * @param {string} responseFunction
 * @returns {function}
 */
function upsSmAjaxRequest(parameters, ajaxUrl, responseFunction) {
    new Ajax.Request(ajaxUrl, {
        method: 'POST',
        parameters: parameters,
        onSuccess: function (response) {
            var json = response.responseText;
            var data = JSON.parse(json);
            var callbackRes = responseFunction(data);
            return callbackRes;
        }
    });
}

function upsSmGetRowData(data, loc) {
    return '<td>' + data.origin_city + '</td>' +
        '<td>' + data.origin_state + '</td>' +
        '<td>' + data.origin_zip + '</td>' +
        '<td>' + data.origin_country + '</td>' +
        '<td><a href="javascript:;" data-id="' + data.id + '" title="Edit" class="upsSm-edit-' + loc + '">Edit</a>' +
        ' | ' +
        '<a href="javascript:;" data-id="' + data.id + '" title="Delete" class="upsSm-del-' + loc + '">Delete</a>' +
        '</td>';
}

/*
* Hide message
 */
function upsSmScrollHideMsg(scrollType, scrollEle, scrollTo, hideEle) {

    if (scrollType == 1) {
        jQuery(scrollEle).animate({scrollTop: jQuery(scrollTo).offset().top - 170});
    } else if (scrollType == 2) {
        jQuery(scrollTo)[0].scrollIntoView({behavior: "smooth"});
    }
    setTimeout(function () {
        jQuery(hideEle).hide('slow');
    }, 5000);
}

/**
 * @identifierElem (will be the id or class name)
 * @elemType (will be the type of identifier whether it an id or an class ) id = 1, class = 0
 * @msgClass (magento style class) [success, error, info, warning]
 * @msg (this will be the message which you want to print)
 * */
function upsSmResponseMessage(identifierId, msgClass, msg) {
    identifierId = '#' + identifierId;
    let finalClass = 'message message-';
    switch (msgClass) {
        case 'success':
            finalClass += 'success success';
            break;
        case 'info':
            finalClass += 'info info';
            break;
        case 'error':
            finalClass += 'error error';
            break;
        default:
            finalClass += 'warning warning';
            break;
    }
    jQuery(identifierId).addClass(finalClass);
    jQuery(identifierId).text(msg).show('slow');
    setTimeout(function () {
        jQuery(identifierId).hide('slow');
        jQuery(identifierId).removeClass(finalClass);
    }, 5000);
}
