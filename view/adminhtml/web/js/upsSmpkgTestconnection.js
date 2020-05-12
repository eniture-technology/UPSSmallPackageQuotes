require(['jquery', 'domReady!'], function ($) {
    /* Test Connection Validation */
    $('#test_upssmpkg_connection').click(function () {
        if ($('#config-edit-form').valid()) {
            const ajaxURL = $(this).attr('upsSmconnAjaxUrl');
            upsSmpkgTestConnectionAjaxCall($, ajaxURL);
        }
        return false;
    });
});

/**
 * Test connection ajax call
 * @param {type} ajaxURL
 * @returns {Success or Error}
 */
function upsSmpkgTestConnectionAjaxCall($, ajaxURL)
{
    let common = '#upsconnsettings_first_';
    let credentials = {
        userName: $(common + 'username').val(),
        password: $(common + 'password').val(),
        upsLcnsKey: $(common + 'upsLicenseKey').val(),
        accountNumber: $(common + 'accountNumber').val(),
        pluginLicenceKey: $(common + 'licnsKey').val()
    };
    upsSmAjaxRequest(credentials, ajaxURL, upsSmConnectSuccessFunction);
}

/**
 *
 * @param {object} data
 * @returns void
 */
function upsSmConnectSuccessFunction(data)
{
    if (data.Success) {
        upsSmResponseMessage('upsSm-response-box', 'success', data.Success);
    } else if (data.Error) {
        upsSmResponseMessage('upsSm-response-box', 'error', data.Error);
    } else {
        let errorText = 'The credentials entered did not result in a successful test. Confirm your credentials and try again.';
        upsSmResponseMessage('upsSm-response-box', 'error', errorText);
    }
}
