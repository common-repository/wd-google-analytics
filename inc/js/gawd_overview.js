function gawd_onclick_refresh() {
    var end_date = Date.today().add(-1).days().toString("yyyy-MM-dd");
    var start_date_30 = (Date.today().add(-1).days()).add(-30).days().toString("yyyy-MM-dd");
    var start_date_7 = (Date.today().add(-1).days()).add(-7).days().toString("yyyy-MM-dd");
    start_date = start_date_7;
    var current_id = jQuery(this).attr('id');
    switch (current_id) {
        case 'gawd-date-meta_refresh_button':
            gawd_draw_analytics_compact('sessions', 'users', 'date', 'line', 'gawd_date_meta');
            break;

        case 'gawd-country-box_refresh_button':
            gawd_draw_analytics_compact('sessions', '', 'country', 'column', 'gawd_country_meta');
            break;

        case 'gawd-real-time_refresh_button':
            gawd_widget_real_time('#gawd_real_time_meta');
            break;

        case 'gawd-visitors-meta_refresh_button':
            gawd_pie_chart_compact('sessions', 'userType', 'pie', 'gawd_visitors_meta');
            break;

        case 'gawd-browser-meta_refresh_button':
            gawd_pie_chart_compact('sessions', 'browser', 'pie', 'gawd_browser_meta');
            break;
    }
}
function gawd_onclick_more() {

    var current_id = jQuery(this).attr('id');

    switch (current_id) {
        case 'gawd-date-meta_more_button':
            window.location.href = gawd_overview.wp_admin_url + 'admin.php?page=gawd_reports&tab=general';
            break;

        case 'gawd-country-box_more_button':
            window.location.href = gawd_overview.wp_admin_url + 'admin.php?page=gawd_reports&tab=location';
            break;

        case 'gawd-real-time_more_button':
            window.location.href = gawd_overview.wp_admin_url + 'admin.php?page=gawd_reports&tab=realtime';
            break;

        case 'gawd-visitors-meta_more_button':
            window.location.href = gawd_overview.wp_admin_url + 'admin.php?page=gawd_reports&tab=behaviour';
            break;

        case 'gawd-browser-meta_more_button':
            window.location.href = gawd_overview.wp_admin_url + 'admin.php?page=gawd_reports&tab=browser';
            break;
    }
}
function gawd_onclick_toggle() {
    if (jQuery(this).attr('aria-expanded') == 'true') {
        jQuery(this).attr('aria-expanded', 'false');
        jQuery(this).closest('.postbox').addClass('closed');
    } else {
        jQuery(this).attr('aria-expanded', 'true');
        jQuery(this).closest('.postbox').removeClass('closed');
    }
}
function hide_same_metric(el){
  var option = jQuery(el).val();
  var metric_id = "gawd_metric_compact";
  var metric_compare_id = "gawd_metric_compare_compact";
  var select_id = jQuery(el).attr("id");
  if(select_id == metric_id){            
      jQuery("#"+metric_compare_id).find('option').show();
      jQuery("#"+metric_compare_id).find("option[value='"+option+"']").hide();
  }
  else if(select_id == metric_compare_id){            
      jQuery("#"+metric_id).find('option').show();
      jQuery("#"+metric_id).find("option[value='"+option+"']").hide();
  }    
}
jQuery(document).ready(function () {

    if(gawd_overview.enableHoverTooltip == 'on'){
      jQuery('.load_tooltip').mouseenter(function(){
        jQuery(this).attr('title',jQuery(this).data('hint'));
      })
      jQuery('.postbox-container, .gawd_wrap').tooltip({position: {
                        my: "center",
                        at: "right+200",
                        track: false,
                        using: function(position, feedback) {
                            jQuery(this).css(position);                   
                        }
                    }
                });
    }
    jQuery('.gawd_wrap').find('.postbox').each(function () {
        var temp = jQuery(this);
        
          jQuery(this).find('.hndle span').html();
            var temp_id = temp.attr('id');
            var temp_html = temp.html();
            new_text_refresh = buttons_refresh;
            new_text_full = buttons_full;
            var new_text ='</button>';
        if(temp.attr('id') == 'gawd-real-time'){
            new_text += new_text_refresh.replace('{{refreshid}}', temp_id + '_refresh_button');
            
          
        }
        new_text += new_text_full.replace( '{{moreid}}', temp_id + '_more_button' );
          temp_html = temp_html.replace( '</button>', new_text );
            temp.html( temp_html );
    });
      jQuery('.gawd-refresh').unbind('click').on('click', gawd_onclick_refresh);
      jQuery('.gawd-more').unbind('click').on('click', gawd_onclick_more);
      jQuery('.gawd_wrap').find('.toggle-indicator').closest('button').unbind('click').on('click', gawd_onclick_toggle);

})

function datepicker_js_compact() {
    gawd_datepicker_main_compact(default_start(), default_end());
    jQuery('#reportrange').daterangepicker({
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(7, 'days'), moment().subtract(1, 'days')],
            'Last Week': [moment().subtract(1, 'week').startOf('week'), moment().subtract(1, 'week').endOf('week')],
            'Last 30 Days': [moment().subtract(30, 'days'), moment().subtract(1, 'days')],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        },
        "startDate": default_start(),
        "endDate": default_end(),
        "maxDate": moment(),
        "alwaysShowCalendars": true,
        "opens": "right",
        "applyClass": 'gawd_main_apply'
    }, gawd_datepicker_main_compact);

    function gawd_datepicker_main_compact(start, end) {
        jQuery('#reportrange span').html(start.format('Y-MM-DD') + ' - ' + end.format('Y-MM-DD'));
        jQuery('#gawd_start_end_date_compact').val(start.format('Y-MM-DD') + '/-/' + end.format('Y-MM-DD'));
        gawd_draw_analytics_compact(jQuery('#gawd_metric_compact').val(), jQuery('#gawd_metric_compare_compact').val(), 'date', 'line', 'gawd_date_meta');
        gawd_pie_chart_compact('sessions', 'userType', 'pie', 'gawd_visitors_meta');
        gawd_draw_analytics_compact('sessions', '', 'country', 'column', 'gawd_country_meta');
        gawd_pie_chart_compact('sessions', 'browser', 'pie', 'gawd_browser_meta');
        jQuery('.gawd_wrap').find('.postbox').each(function () {
            var text = jQuery(this).find('.hndle span').html();
            if (typeof text !== "undefined") {
              text = text.split(' (');
              var new_text = text[0];
              if (new_text != 'Real Time') {
                new_text += ' (' + start.format('Y-MM-DD') + ' - ' + end.format('Y-MM-DD') + ')';
                jQuery(this).find('.hndle span').html(new_text);
              }
            }
        });

    }
}
