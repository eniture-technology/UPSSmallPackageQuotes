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
    let apiEndpoint = $(common+'apiEndpoint').val();
    let credentials = {
        accountNumber: $(common + 'accountNumber').val(),
        pluginLicenceKey : $(common + 'licnsKey').val(),
        apiEndpoint      : apiEndpoint
    };

    if(apiEndpoint == 'new'){
        credentials.clientId            = $(common+'clientId').val();
        credentials.clientSecret        = $(common+'clientSecret').val();
        credentials.userName            = $(common+'usernameNewAPI').val();
        credentials.password            = $(common+'passwordNewAPI').val();
    }else{
        credentials.userName            = $(common+'username').val();
        credentials.password            = $(common+'password').val();
        credentials.upsLcnsKey          = $(common+'upsLicenseKey').val();
    }

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

/**
 * Plan Refresh ajax call
 * @param {object} $
 * @param {string} ajaxURL
 * @returns {function}
 */
function upsSmpkgPlanRefresh(e){
    let ajaxURL = e.getAttribute('planRefAjaxUrl');
    let parameters = {};
    upsSmAjaxRequest(parameters, ajaxURL, upsSmpkgPlanRefreshResponse);
}

/**
 * Handle response
 * @param {object} data
 * @returns {void}
 */
function upsSmpkgPlanRefreshResponse(data){}
