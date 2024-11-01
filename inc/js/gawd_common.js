function gawd_upgrade_plugin(ajaxurl, nonce) {
    jQuery(document).ready(function () {
        jQuery.ajax({
            'url': ajaxurl,
            'type': "GET",
            'dataType': 'json',
            'async': true,
            'data': {
                'gawd_ajax': '1',
                'gawd_nonce': nonce,
                'gawd_nonce_data': {},
                'gawd_action': "upgrade_plugin",
                'gawd_data': []
            },
            success: function (data) {
            },
            error: function (data) {
            }
        });
    });
}