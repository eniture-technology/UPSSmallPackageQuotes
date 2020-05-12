/**
     * Document load function
     * @type type
     */
    
    require([ 'jquery', 'jquery/ui'], function ($) {
        $(document).ready(function ($) {
            if ($("#suspend-rad-use").length > 0 && $("#suspend-rad-use").is(":disabled") == false) {
                disablealwaysresidentialups();
                if (($('#suspend-rad-use:checkbox:checked').length)>0) {
                    $("#upsQuoteSetting_third_residentialDlvry").prop({disabled: false});
                } else {
                    $("#upsQuoteSetting_third_residentialDlvry").val('0');
                    $("#upsQuoteSetting_third_residentialDlvry").prop({disabled: true});
                }
            }
        });
        
        /**
        * windows onload
        */
        $(window).load(function () {
            if ($("#suspend-rad-use").length > 0 && $("#suspend-rad-use").is(":disabled") == false) {
                if (!isdisabled) {
                    if (($('#suspend-rad-use:checkbox:checked').length)>0) {
                        $("#upsQuoteSetting_third_residentialDlvry").prop({disabled: false});
                    } else {
                        $("#upsQuoteSetting_third_residentialDlvry").val('0');
                        $("#upsQuoteSetting_third_residentialDlvry").prop({disabled: true});
                    }
                }
            }
        });
    });
    
    /**
     *
     * @return {undefined}
     */
    function disablealwaysresidentialups()
    {
        jQuery("#suspend-rad-use").on('click', function () {
            if (this.checked) {
                jQuery("#upsQuoteSetting_third_residentialDlvry").prop({disabled: false});
            } else {
                jQuery("#upsQuoteSetting_third_residentialDlvry").val('0');
                jQuery("#upsQuoteSetting_third_residentialDlvry").prop({disabled: true});
            }
        });
    }