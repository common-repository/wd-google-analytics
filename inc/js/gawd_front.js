jQuery(document).ready(function(){
  jQuery('#wp-admin-bar-gawd').on('click', function(){
    //var uri = '#' + jQuery('#wp-admin-bar-gawd a span').attr('data-url');
    /*var uri = jQuery('header h1').text();*/
    var gawd_location_href = window.location.href;
    var gawd_location_hash = window.location.hash;
    var uri = gawd_location_href.replace(gawd_location_hash, "");
    gawd_chart_type_post_page_front(uri, 'gawd_post_page_popup');
  })
})
function gawd_chart_type_post_page_front(uri,divID){
    post_page_stats_front(uri,divID);
}

function post_page_stats_front(uri,divID){
  if(typeof divID == 'undefined'){
    divID = 'gawd_post_page_popup';
  }
  var chartType = 'line';
  var fillAlphas = 0;
  var checked_line = "";
  var checked_column = "";
  if(jQuery("#gawd_chart_type_post_page").val() == 'line'){
    chartType = 'line';
    var checked = 'selected="selected"';
    fillAlphas = 0;
  }
  else if(jQuery("#gawd_chart_type_post_page").val() == 'column'){
    chartType = 'column';
    checked_column = 'selected="selected"';
    fillAlphas = 1;
  }
  var metric = typeof jQuery("#gawd_metric_post_page_popup").val() != 'undefined' ? jQuery("#gawd_metric_post_page_popup").val() : 'sessions';
  var date_30 = gawd_front.date_30;
  var date_7 = gawd_front.date_7;
  var date_yesterday = gawd_front.date_yesterday;
  var date_today = gawd_front.date_today;
  var date_this_month = gawd_front.date_this_month;
  var date_last_month = gawd_front.date_last_month;
  var date_last_week = gawd_front.date_last_week;

  start_date = typeof jQuery("#gawd_post_page_date").val() != 'undefined' ? jQuery("#gawd_post_page_date").val() : (typeof jQuery("#gawd_post_page_popup_date").val() != 'undefined' ? jQuery("#gawd_post_page_popup_date").val() : date_30);
  var dimension = 'date';
  var timezone = -(new Date().getTimezoneOffset()/60);
  var chart_div = '<div id="opacity_div"></div><div id="loading_div" style="display:none; text-align: center; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999;"><img src="'+gawd_front.gawd_plugin_url+'/assets/ajax_loader.gif"  style="position: absolute;top: calc(50% - 27px);left: calc(50% - 27px);width: 54px;height: 54px;"></div><div class="page_chart_div"><div style="width:100%; height:100%; position:relative;" class="close_button_cont"><button class="gawd_btn" >X</button>';
  chart_div += '<select name="gawd_post_page_popup_date" id="gawd_post_page_popup_date" class="gawd_draw_analytics_front">';
  chart_div +=  '<option value="'+date_30+'">Last 30 Days</option><option value="'+date_7+'">Last 7 Days</option>';
  chart_div +=  '<option value="'+date_last_week+'">Last week</option><option value="'+date_last_month+'">Last month</option>';
  chart_div +=  '<option value="'+date_this_month+'">This month</option><option value="'+date_yesterday+'">Yesterday</option>';
  chart_div +=  '<option value="'+date_today+'">Today</option>';
  chart_div += '</select>';
  chart_div += '<select name="gawd_metric_post_page_popup" id="gawd_metric_post_page_popup" class="gawd_draw_analytics_front">';
  chart_div += '<option value="sessions" >Sessions</option><option value="users"  >Users</option><option value="bounceRate"  >Bounce Rate</option><option value="pageviews"  >Pageviews</option><option value="percentNewSessions">% New Sessions</option><option value="avgSessionDuration">Avg Session Duration</option><option value="pageviewsPerSession"  >Pages/Session</option>';
  chart_div += '</select>';
  chart_div += '<select name="gawd_chart_type_post_page" id="gawd_chart_type_post_page" class="gawd_draw_analytics_front">';
  chart_div += '<option '+checked_line+' value="line">Line Chart</option><option '+checked_column+'  value="column">Column Chart</option>';
  chart_div += '</select>';
  chart_div += '<div id="gawd_post_page_popup"></div></div></div>';
  jQuery(".page_chart_div").remove();
  jQuery('#opacity_div').remove();
  
  jQuery( "body" ).append(chart_div);
  jQuery("#gawd_metric_post_page_popup").val(metric)
  jQuery("#gawd_post_page_popup_date").val(start_date)
  jQuery( "#loading_div" ).show();
  jQuery( "#opacity_div" ).show();
  jQuery('#gawd_post_page_popup').height('400');
  jQuery('#gawd_metric_post_page_popup, #gawd_post_page_popup_date, #gawd_chart_type_post_page').on('change',function(){
    gawd_chart_type_post_page_front(uri,'gawd_post_page_popup');
  })
    jQuery("#gawd_post_page_meta").empty();
    jQuery('#gawd_post_page_meta').height('300');
    var dates = start_date.split('/-/');


  var args = gawd_custom_ajax_args();
  args.type = 'POST';
  args.async = true;
  args.data.gawd_action = "gawd_show_post_page_data";
  args.data.gawd_data = {
    "metric": metric,
    "start_date": dates[0],
    "end_date": dates[1],
    "dimension": dimension,
    "timezone": timezone,
    "chart": 'line',
    "security": gawd_front.ajaxnonce,
    "filter": uri,
  };
  args.beforeSend = function() {

  };
  args.success = function (data){

    data = JSON.parse(data.data.gawd_page_post_data);
    data = (typeof data.chart_data !== 'undefined') ? data.chart_data : data;

    if(divID == 'gawd_post_page_popup'){
      jQuery( "#loading_div" ).remove();
      jQuery( ".gawd_btn" ).show();
      jQuery('#opacity_div, .gawd_btn').on('click', function(){
        jQuery('#opacity_div').remove();
        jQuery( ".gawd_btn" ).remove();
        jQuery( ".page_chart_div" ).remove();
        jQuery( "#loading_div" ).remove();
      })

    }

      var x_key = 'date';
      var y_key = metric;

      var chart = new gawd_charts_helper();
      chart.print_posts_chart(data, x_key, y_key, chartType, divID);

  };
  args.error = function (data) {

  };
  jQuery.ajax(args);

}

function gawd_custom_ajax_args() {

  return {
    'url': gawd_front.ajaxurl,
    'type': "GET",
    'dataType': 'json',
    'async': false,
    'data': {
      'gawd_ajax': '1',
      'gawd_nonce': gawd_front.gawd_custom_ajax_nonce,
      'gawd_nonce_data': gawd_front.gawd_custom_ajax_nonce_data,
      'gawd_action': "",
      'gawd_data': []
    },
    success: function (data) {
    },
    error: function (data) {
    }
  };
}