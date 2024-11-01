function validateEmail(email) {
    var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
}

function ValidateIPaddress(ipaddress) {
    if (/^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/.test(ipaddress)) {
        return (true);
    }
    return (false);
}

jQuery(function () {
    jQuery('.chosen-select').chosen();
    jQuery('.chosen-select-deselect').chosen({allow_single_deselect: true});

    var hash = window.location.hash;

    if(
        hash !== "" && hash !== "#" &&
        hash !== "#gawd_authenicate_tab" &&
        hash !== "#gawd_tracking_tab" &&
        hash !== "#gawd_advanced_tab" &&
        hash !== "#gawd_emails_tab" &&
        hash !== "#gawd_alerts_tab"
    ){
        window.location.hash = "";
        window.location.reload();
    }
});

function change_filter_account(that) {
    jQuery('#web_property_name').val(jQuery(that).find(':selected').closest('optgroup').attr('label'));
    jQuery('#gawd_form').submit();
}

jQuery(document).ready(function () {

    jQuery('#enable_cross_domain').on('change', function () {
        jQuery('#cross_domains').toggle();
    });

    jQuery('#enable_custom_code').on('change', function () {
        jQuery('#gawd_custom_code').toggle();
    });

    jQuery('#gawd_own_project').on('change', function () {
        jQuery('.own_inputs').toggle();
    });

    jQuery('#gawd_settings_logout').on('click', function () {
        jQuery('#gawd_settings_logout_val').val('1');
        jQuery('#gawd_form').submit();
    });

    jQuery('#gawd_settings_button').on('click', function () {
        jQuery('#alert_view_name').val(jQuery("#gawd_alert_view option:selected").closest('optgroup').attr('label'));
        jQuery('#pushover_view_name').val(jQuery("#gawd_pushover_view option:selected").closest('optgroup').attr('label'));

        jQuery('#gawd_settings_submit').val(1);
        if (window.location.hash === '#gawd_alerts_tab') {
            var submit_form = true;
            var gawd_alert_emails = jQuery("#gawd_alert_emails");
            if (gawd_alert_emails.val() === "" || !validateEmail(gawd_alert_emails.val())) {
                gawd_alert_emails.addClass('gawd_invalid');
                submit_form = false;
            } else {
                gawd_alert_emails.removeClass('gawd_invalid');
            }
            if (submit_form) {
                //jQuery('#gawd_form').submit();
                gawd_save_settings();
            }
        } else if (window.location.hash === '#gawd_filters_tab') {
            var submit_form1 = true;
            var gawd_filter_name_fild = jQuery(".gawd_filter_name_fild");
            var gawd_filter_value = jQuery("#gawd_filter_value");

            if (gawd_filter_name_fild.val() === "") {
                gawd_filter_name_fild.addClass('gawd_invalid');
                submit_form1 = false;
            }
            else {
                gawd_filter_name_fild.removeClass('gawd_invalid');
            }
            if (jQuery('#gawd_filter_type').val() == 'GEO_IP_ADDRESS') {
                if (gawd_filter_value.val() === "" || !ValidateIPaddress(gawd_filter_value.val())) {
                    gawd_filter_value.addClass('gawd_invalid');
                    submit_form1 = false;
                }
                else {
                    gawd_filter_value.removeClass('gawd_invalid');
                }
            }
            if (submit_form1) {
                gawd_save_settings();
                // jQuery('#gawd_form').submit();
            }

        } else if (window.location.hash === '#gawd_pushover_tab') {
            var submit_form2 = true;
            var gawd_pushover_name_fild = jQuery(".gawd_pushover_name_fild");
            var gawd_pushover_user_keys_fild = jQuery(".gawd_pushover_user_keys_fild");


            if (gawd_pushover_name_fild.val() === "") {
                gawd_pushover_name_fild.addClass('gawd_invalid');
                submit_form2 = false;
            } else {
                gawd_pushover_name_fild.removeClass('gawd_invalid');
            }
            if (gawd_pushover_user_keys_fild.val() === "") {
                gawd_pushover_user_keys_fild.addClass('gawd_invalid');
                submit_form2 = false;
            } else {
                gawd_pushover_user_keys_fild.removeClass('gawd_invalid');
            }
            if (submit_form2) {
                gawd_save_settings();
                // jQuery('#gawd_form').submit();
            }
        } else {
            gawd_save_settings();
            // jQuery('#gawd_form').submit();
        }


    });

    var gawd_emails = false;
    jQuery("#gawd_right_conteiner").show();
    if (window.location.hash === '') {
        var url = window.location.toString();
        if (url.indexOf('gawd_settings') > -1) {
            jQuery('.gawd_submit').width('100%');
        }
        jQuery('.gawd_authenicate').show();
        jQuery('#gawd_settings_logout').show();
        jQuery('#gawd_authenicate').addClass('gawd_active_li');
        if (jQuery(window).width() < 720) {
            jQuery('#gawd_authenicate').addClass('gawd_resp_active_li');

        }
    } else if (window.location.hash === '#gawd_alerts_tab') {
        jQuery('.gawd_submit').width('100%');
        jQuery('#gawd_alerts').addClass('gawd_active_li');
        jQuery('.gawd_alerts').show();
        if (jQuery(window).width() < 720) {
            jQuery('#gawd_alerts').addClass('gawd_resp_active_li');
        }
    } else if (window.location.hash === '#gawd_emails_tab') {
        jQuery('.gawd_submit').width('82.5%');
        jQuery('#gawd_emails').addClass('gawd_active_li');
        if (jQuery(window).width() < 720) {
            jQuery('#gawd_emails').addClass('gawd_resp_active_li');
        }
        if (jQuery(".gawd_emails table").length <= 0) {
            gawd_emails = true;
        }
        jQuery('#gawd_settings_button').hide();
        jQuery('.gawd_emails').show();
    }
    else if (window.location.hash === '#gawd_advanced_tab') {
        jQuery('.gawd_submit').width('92.9%');
        if (jQuery(window).width() < 720) {
            jQuery('#gawd_advanced').addClass('gawd_resp_active_li');
        }
        jQuery('#gawd_advanced').addClass('gawd_active_li');
        jQuery('.gawd_advanced').show();
    }
    else if (window.location.hash === '#gawd_pushover_tab') {
        jQuery('.gawd_submit').width('100%');
        if (jQuery(window).width() < 720) {
            jQuery('#gawd_pushover').addClass('gawd_resp_active_li');
        }
        jQuery('#gawd_pushover').addClass('gawd_active_li');
        jQuery('.gawd_pushover').show();
    }
    else if (window.location.hash === '#gawd_filters_tab') {
        jQuery('.gawd_submit').width('100%');
        if (jQuery(window).width() < 720) {
            jQuery('#gawd_filters').addClass('gawd_resp_active_li');
        }
        jQuery('#gawd_filters').addClass('gawd_active_li');
        jQuery('.gawd_filters').show();
    }
    else if (window.location.hash === '#gawd_authenicate_tab') {
        jQuery('.gawd_submit').width('100%');
        jQuery('#gawd_settings_logout').show();
        if (jQuery(window).width() < 720) {
            jQuery('#gawd_authenicate').addClass('gawd_resp_active_li');
        }
        jQuery('#gawd_authenicate').addClass('gawd_active_li');
        jQuery('.gawd_authenicate').show();
    }
    else if (window.location.hash === '#gawd_advanced') {
        if (jQuery(window).width() < 720) {
            jQuery('#gawd_advanced').addClass('gawd_resp_active_li');
        }
        jQuery('#gawd_advanced').addClass('gawd_active_li');
        jQuery('.gawd_advanced').show();
    } else if (window.location.hash === '#gawd_tracking_tab') {
        if (jQuery(window).width() < 720) {
            jQuery('#gawd_tracking').addClass('gawd_resp_active_li');
        }
        jQuery('#gawd_tracking').addClass('gawd_active_li');
        jQuery('.gawd_tracking').show();
        if(gawd_admin.gawd_has_property == '0'){
            jQuery('#gawd_settings_button').hide();
        }
    } else {
        jQuery('.gawd_authenicate').show();
        jQuery('#gawd_authenicate').addClass('gawd_active_li');
        if (jQuery(window).width() < 720) {
            jQuery('#gawd_authenicate').addClass('gawd_resp_active_li');
        }
    }


    jQuery('#gawd_own_project').on('change', function () {
        if (jQuery(this).is(":checked")) {
            jQuery('.own_inputs').show();
        }
        else {
            jQuery('.own_inputs').hide();
        }
    });

    if (jQuery(window).width() < 720) {
        jQuery('.gawd_menu_li').addClass('gawd_resp_li');
        jQuery('.gawd_resp_li').show();
        var elId = window.location.hash ? window.location.hash.substring(0, window.location.hash.length - 4) : "#gawd_authenicate";
        show_hide(jQuery(elId));
    }


    jQuery('.gawd_resp_li').on('click', function () {
        show_hide(jQuery(this));
    });

    jQuery('.gawd_settings_menu_coteiner').show();
    jQuery('#gawd_filter_type').on('change', function () {
        jQuery('#gawd_filter_name').html(jQuery('#gawd_filter_type :selected').attr('data-name'));
        if (jQuery('#gawd_filter_type :selected').attr('data-name') == 'Country') {
            var tooltip = 'Set the country to filter from Google Analytics tracking.';
        }
        else if (jQuery('#gawd_filter_type :selected').attr('data-name') == 'Region') {
            var tooltip = 'Set the region to filter from Google Analytics tracking.';
        }
        else if (jQuery('#gawd_filter_type :selected').attr('data-name') == 'City') {
            var tooltip = 'Set the city to filter from Google Analytics tracking.';
        }
        else {
            var tooltip = 'Enter the IP address to filter from Google Analytics tracking.';
        }
        jQuery('#gawd_filter_name').closest(jQuery('#gawd_filter_value_cont')).find(jQuery('.gawd_info')).attr('title', tooltip);
    });


    jQuery('.gawd_menu_li').on('click', function () {
        var current_hash = window.location.hash;
        var tab = jQuery(this).attr('id');
        jQuery('.gawd_menu_li').removeClass('gawd_active_li');
        jQuery('.gawd_menu_li').removeClass('gawd_resp_active_li');
        //jQuery(this).addClass('gawd_active_li');
        if (jQuery(window).width() < 720) {
            jQuery(this).addClass('gawd_resp_active_li');
        }

        jQuery('.gawd_settings_menu_coteiner .gawd_menu_ul li').each(function () {
            jQuery(this).removeClass('gawd_active_li');
        });
        jQuery('#gawd_settings_tab').val(tab);
        if (tab == 'gawd_alerts') {
            jQuery('.gawd_submit').width('100%');
            window.location.hash = "gawd_alerts_tab";
            jQuery(this).addClass('gawd_active_li');
            jQuery('.gawd_authenicate').hide();
            jQuery('.gawd_pushover').hide();
            jQuery('.gawd_filters').hide();
            jQuery('.gawd_advanced').hide();
            jQuery('.gawd_emails').hide();
            jQuery('.gawd_tracking').hide();
            jQuery('.gawd_alerts').show();
            if (gawd_emails) {
                gawd_emails = true;
                jQuery('#gawd_right_conteiner').show();
            }
            jQuery('#gawd_settings_button').show();
            jQuery('#gawd_settings_logout').hide();
        }
        else if (tab == 'gawd_emails') {
            jQuery('.gawd_submit').width('82.5%');
            window.location.hash = "gawd_emails_tab";
            jQuery(this).addClass('gawd_active_li');
            jQuery('.gawd_alerts').hide();
            jQuery('.gawd_authenicate').hide();
            jQuery('.gawd_pushover').hide();
            jQuery('.gawd_filters').hide();
            jQuery('.gawd_advanced').hide();
            jQuery('.gawd_tracking').hide();
            jQuery('.gawd_emails').show();
            if (jQuery(".gawd_emails table").length <= 0) {
                gawd_emails = true;
            }
            jQuery('#gawd_settings_button').hide();
            jQuery('#gawd_settings_logout').hide();
        }
        else if (tab == 'gawd_pushover') {
            jQuery('.gawd_submit').width('100%');
            window.location.hash = "gawd_pushover_tab";
            jQuery(this).addClass('gawd_active_li');
            jQuery('.gawd_alerts').hide();
            jQuery('.gawd_pushover').show();
            jQuery('.gawd_authenicate').hide();
            jQuery('.gawd_emails').hide();
            jQuery('.gawd_advanced').hide();
            jQuery('.gawd_tracking').hide();
            jQuery('.gawd_filters').hide();
            if (gawd_emails) {
                gawd_emails = true;
                jQuery('#gawd_right_conteiner').show();
            }
            jQuery('#gawd_settings_button').show();
            jQuery('#gawd_settings_logout').hide();
        }
        else if (tab == 'gawd_filters') {
            jQuery('.gawd_submit').width('100%');
            window.location.hash = "gawd_filters_tab";
            jQuery(this).addClass('gawd_active_li');
            jQuery('.gawd_alerts').hide();
            jQuery('.gawd_pushover').hide();
            jQuery('.gawd_authenicate').hide();
            jQuery('.gawd_emails').hide();
            jQuery('.gawd_advanced').hide();
            jQuery('.gawd_tracking').hide();
            jQuery('.gawd_filters').show();
            if (gawd_emails) {
                gawd_emails = true;
                jQuery('#gawd_right_conteiner').show();
            }
            jQuery('#gawd_settings_button').show();
            jQuery('#gawd_settings_logout').hide();
        }
        else if (tab == 'gawd_advanced') {
            jQuery('.gawd_submit').width('92.9%');
            window.location.hash = "gawd_advanced_tab";
            jQuery(this).addClass('gawd_active_li');
            jQuery('.gawd_alerts').hide();
            jQuery('.gawd_pushover').hide();
            jQuery('.gawd_authenicate').hide();
            jQuery('.gawd_emails').hide();
            jQuery('.gawd_filters').hide();
            jQuery('.gawd_tracking').hide();
            jQuery('.gawd_advanced').show();
            if (gawd_emails) {
                gawd_emails = true;
                jQuery('#gawd_right_conteiner').show();
            }
            jQuery('#gawd_settings_button').show();
            jQuery('#gawd_settings_logout').hide();
        }
        else if (tab == 'gawd_authenicate') {
            jQuery('.gawd_submit').width('100%');
            window.location.hash = "gawd_authenicate_tab";
            jQuery(this).addClass('gawd_active_li');
            jQuery('.gawd_alerts').hide();
            jQuery('.gawd_pushover').hide();
            jQuery('.gawd_advanced').hide();
            jQuery('.gawd_filters').hide();
            jQuery('.gawd_tracking').hide();
            jQuery('.gawd_emails').hide();
            jQuery('.gawd_authenicate').show();
            if (gawd_emails) {
                gawd_emails = true;
                jQuery('#gawd_right_conteiner').show();
            }
            jQuery('#gawd_settings_button').show();
            jQuery('#gawd_settings_logout').show();
        } else if (tab === "gawd_tracking") {
            jQuery('.gawd_submit').width('100%');
            window.location.hash = "gawd_tracking_tab";
            jQuery(this).addClass('gawd_active_li');
            jQuery('.gawd_alerts').hide();
            jQuery('.gawd_pushover').hide();
            jQuery('.gawd_advanced').hide();
            jQuery('.gawd_filters').hide();
            jQuery('.gawd_emails').hide();
            jQuery('.gawd_authenicate').hide();
            jQuery('.gawd_tracking').show();

            jQuery('#gawd_settings_logout').hide();
            if(gawd_admin.gawd_has_property == '0'){
                jQuery('#gawd_settings_button').hide();
            }
        }
    });

    jQuery('.gawd_tracking_notice_link').on('click', function (e) {
        if(jQuery('body').hasClass('analytics_page_gawd_settings')) {
            e.preventDefault();
            jQuery('.gawd_menu_ul #gawd_tracking').trigger('click');
            return false;
        }
    });

    var transient = jQuery('#gawd_refresh_user_info_transient');
    if(transient.length === 1 && transient.val() !== '1'){
        gawd_refresh_user_info(true);
    }
});

/***************TRACKING****************/
jQuery(function () {
    jQuery('.chosen-select').chosen();
    jQuery('.chosen-select-deselect').chosen({allow_single_deselect: true});
});

jQuery(document).ready(function () {

    jQuery('.button_gawd_add').on('click', function () {
        jQuery('#add_dimension_value').val(jQuery(this).data('name'));
        jQuery('#settings_submit').val('0');
        gawd_save_settings();
    });

    jQuery("#gawd_right_conteiner").show();

    function exclude_popup(popup_overlay, popup_body, popup_btn, exclude_content, add_content, excluded_items, remove_excluded, excluded_data, hidden_input) {
        popup_overlay = "." + popup_overlay;
        popup_btn = "." + popup_btn;
        popup_body = "." + popup_body;
        exclude_content = "#" + exclude_content;
        add_content = "#" + add_content;
        hidden_input = '#' + hidden_input;

        gawd_hidden_input();
        jQuery(popup_overlay + ', ' + popup_btn).on('click', function () {
            jQuery(popup_body).fadeOut('fast');
            jQuery(popup_overlay).fadeOut('fast');
        });

        jQuery(exclude_content).on('click', function () {
            jQuery(popup_body).fadeIn('fast');
            jQuery(popup_overlay).fadeIn('fast');
        });

        jQuery(add_content).on('click', function () {
            jQuery(popup_body).fadeOut('fast');
            jQuery(popup_overlay).fadeOut('fast');
            var span = '';
            jQuery(popup_body + " .gawd_table input[type='checkbox']:checked").each(function () {
                jQuery("." + excluded_items).remove();
                span += '<div class="time_wrap ' + excluded_items + '"><span class="' + excluded_data + '">' + jQuery(this).val() + '</span><span class="' + remove_excluded + '">X</span></div>';
            });
            jQuery(exclude_content).before(span);
            jQuery("." + remove_excluded).on('click', function () {
                var find = jQuery(this).closest('.time_wrap').find("." + excluded_data).html();
                jQuery(this).closest('div').remove();

                jQuery(popup_body + " .gawd_table input[type='checkbox']:checked").each(function () {
                    if (jQuery(this).val() == find) {
                        jQuery(this).removeAttr('checked');
                    }
                });
                gawd_hidden_input();
            });
            gawd_hidden_input();
        });

        jQuery("." + remove_excluded).on('click', function () {
            var find = jQuery(this).closest('.time_wrap').find("." + excluded_data).html();
            jQuery(this).closest('div').remove();

            jQuery(popup_body + " .gawd_table input[type='checkbox']:checked").each(function () {
                if (jQuery(this).val() == find) {
                    jQuery(this).removeAttr('checked');
                }
            });

            gawd_hidden_input();
        });

        function gawd_hidden_input() {
            var elements = [];
            jQuery(popup_body + " .gawd_table input[type='checkbox']:checked").each(function () {
                elements.push(jQuery(this).val());
            });

            jQuery(hidden_input).val(elements.join());
        }
    }

    exclude_popup('gawd_exclude_users_popup_overlay', 'gawd_exclude_users_popup', 'gawd_exclude_users_popup_btn', 'exclude_users', 'gawd_add_users', 'excluded_items', 'remove_excluded_user', 'excluded_username', 'gawd_excluded_users_list');
    exclude_popup('gawd_exclude_roles_popup_overlay', 'gawd_exclude_roles_popup', 'gawd_exclude_roles_popup_btn', 'exclude_roles', 'gawd_add_roles', 'excluded_roles', 'remove_excluded_role', 'excluded_role', 'gawd_excluded_roles_list');
});

function gawd_save_settings() {

    var serialized_form = jQuery("#gawd_form").serializeArray();
    var form_data = {};
    for (var i = 0; i < serialized_form.length; i++) {
        form_data[serialized_form[i].name] = serialized_form[i].value;
    }

    var args = gawd_custom_ajax_args();
    args.type = 'POST';
    args.async = true;
    args.data.gawd_action = "save_settings";
    args.data.gawd_data = {
        'form': form_data
    };

    var $loader_container = jQuery("#gawd_body");
    args.beforeSend = function () {
        gawd_add_loader($loader_container);
    };

    args.success = function (response) {
        window.location.reload();
    };

    args.error = function () {
        alert('Something went wrong.');
        window.location.reload();
    };

    jQuery.ajax(args).done(function () {
        gawd_remove_loader($loader_container);
    });
}
