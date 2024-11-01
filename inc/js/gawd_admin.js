var _data;
var data_of_compared;
var _data_compare = [];
var d_start_date = "";
var d_second_start_date = "";
var d_second_end_date = "";
var d_end_date = "";
var d_metric_export = "";
var d_metric_compare_export = "";
var d_dimension_export = "";
var d_tab_name = "";
var d_filter_type = "";
var d_geo_type = "";
var d_country_filter = "";
var d_custom = "";
var weekdays = new Array(7);
weekdays[0] = "Sunday";
weekdays[1] = "Monday";
weekdays[2] = "Tuesday";
weekdays[3] = "Wednesday";
weekdays[4] = "Thursday";
weekdays[5] = "Friday";
weekdays[6] = "Saturday";
var monthnames = new Array();
monthnames[01] = 'January';
monthnames[02] = 'February';
monthnames[03] = 'March';
monthnames[04] = 'April';
monthnames[05] = 'May';
monthnames[06] = 'June';
monthnames[07] = 'July';
monthnames[08] = 'August';
monthnames[09] = 'September';
monthnames[10] = 'October';
monthnames[11] = 'November';
monthnames[12] = 'December';
var gawd_chart_data = [];
var gawd_compare_request_last_args;
var gawd_request_last_args;

function gawd_compare() {
    console.log("ajax111");
    jQuery("#gawd_metric_compare").show();
    jQuery("#filter_conteiner").show();
    var _end_date = (Date.today().add(-1).days()).toString("yyyy-MM-dd");
    var start_date_7 = (Date.today().add(-1).days()).add(-7).days().toString("yyyy-MM-dd");
    var start_end_date_compare = jQuery('#gawd_start_end_date_compare').val().split('/-/');
    var start_date_compare = start_end_date_compare[0] ? start_end_date_compare[0] : start_date_7;
    var end_date_compare = start_end_date_compare[1] ? start_end_date_compare[1] : _end_date;
    if (!start_date_compare && !end_date_compare) {
        return;
    }

    var filter_type = jQuery("#gawd_filter_val").val();
    metrics = [];
    var metric = jQuery("#gawd_metric").val();
    var metric_compare = jQuery("#gawd_metric_compare").val();
    metrics.push("ga:" + metric);
    if (metric_compare != 0) {
        metrics.push("ga:" + metric_compare);
    }

    if (jQuery("#gawd_chart_type").val() == 'line') {
        var chartType = 'line';
        var fillAlphas = 0;
    } else if (jQuery("#gawd_chart_type").val() == 'column') {
        var chartType = 'column';
        var fillAlphas = 1;
    }
    var dimension = jQuery("#gawd_tab").val();

    if (dimension == 'date') {
        var parseDates = true;
        var rotateAngle = 0;
    } else if (dimension == 'realTime') {
        jQuery(".filter_conteiner").hide();
        jQuery("#chartdiv").empty();
        gawd_widget_real_time();
        return;
    } else if (dimension == 'custom') {
        var custom = jQuery("#gawd_custom_option").val();
        dimension = custom.substring(3);
    } else {
        var parseDates = false;
        var rotateAngle = 90;
    }
    if (filter_type == "week" || filter_type == "month" || filter_type == "hour") {
        var parseDates = false;
    }

    var args = gawd_custom_ajax_args();
    args.type = 'POST';
    args.async = true;
    args.data.gawd_action = "gawd_show_data";
    args.data.gawd_data = {
        "start_date": start_date_compare,
        "end_date": end_date_compare,
        "metric": metrics,
        "dimension": dimension,
        "security": gawd_admin.ajaxnonce,
        "filter_type": filter_type
    };

    args.beforeSend = function () {
        jQuery('#opacity_div').show();
        jQuery('#loading_div').show();
    };

    jQuery.ajax(args).done(function (data) {
        gawd_compare_request_last_args = args;
        jQuery('#compare_datepicker_wraper').css('background-color','#4F9A55');
        jQuery('#opacity_div').hide();
        jQuery('#loading_div').hide();
        var data = JSON.parse(data.data.gawd_reports_data);
        data_of_compared = data;
        var data_compare = [];
        var __data = JSON.parse(JSON.stringify(_data));
        for (var i = 0; i < __data.chart_data.length; i++) {
          var row = __data.chart_data[i];

         // var __metrics = Object.keys(row);
          var __metrics = [];
          for(key in row){
            __metrics.push(key);
          }

          if (typeof data.chart_data[i] != 'undefined') {
            for (var j = 0; j < __metrics.length; j++) {
              if (__metrics[j] == "color" || __metrics[j] == "No")
                  continue;
              if(__metrics[j].indexOf('compare') == -1){
                row[__metrics[j] + ' compare'] = data.chart_data[i][__metrics[j]];
              }
            }
          }
          data_compare.push(row);
        }
        var data_sum = data.data_sum;
        var _data_sum = _data.data_sum;
        var dataSums = {};
        var d_second_start_date = start_date_compare;
        var d_second_end_date = end_date_compare;
          jQuery('#second_end_date').val(end_date_compare);
          jQuery('#second_start_date').val(start_date_compare);
          jQuery('#second_data_sum').val(JSON.stringify(data_sum));
          jQuery('#first_data_sum').val(JSON.stringify(_data_sum));
        if(dimension == 'pagePath' || dimension == 'landingPagePath'){
          jQuery('#dimension').val(dimension);
          jQuery('#second_data').val(JSON.stringify(data.chart_data));
          jQuery('#first_data').val(JSON.stringify(_data.chart_data));
          gawd_draw_table_pages_compare(JSON.stringify(_data.chart_data),JSON.stringify(data.chart_data), dimension, data_sum, _data_sum,start_date_compare,end_date_compare);
          return;
        }
        if (dimension != "pagePath" && dimension != "landingPagePath") {

          for (metric in data_sum) {
            var dataSum = {};
            dataSum[metric] = _data_sum[metric];
            dataSum[metric + " compare"] = data_sum[metric];
            dataSums[metric] = dataSum;
          }
          metric = jQuery("#gawd_metric").val();
          var metric_compare_export = metric + ' compare';
          metric = metric.replace(/([A-Z])/g, " $1").trim();
          metric = metric.charAt(0).toUpperCase() + metric.slice(1);
          metric = metric.replace(/  +/g, ' ');
          var metric_export = metric;
          var metric_compare = metric + ' compare';
         /*  metric_compare = metric_compare.replace(/([A-Z])/g, " $1").trim();
          metric_compare = metric_compare.charAt(0).toUpperCase() + metric_compare.slice(1);
          metric_compare = metric_compare.replace(/  +/g, ' '); */
          var percent = (dataSums[metric][metric] - dataSums[metric][metric + " compare"])/dataSums[metric][metric + " compare"]*100;
          percent = isNaN(percent) ? 0 : isFinite(percent) ? percent : 0;
          percent = percent.toFixed(2)
          var metric_value =  parseInt(dataSums[metric][metric]).toLocaleString();
          var metric_compare_value =  parseInt(dataSums[metric][metric + " compare"]).toLocaleString();
          if(metric + " compare" == 'Avg Session Duration compare' || metric + " compare" == 'Avg Page Load Time compare' || metric + " compare" == 'Avg Server Response Time compare' || metric + " compare" == 'Avg Redirection Time compare' || metric + " compare" == 'Avg Page Download Time compare'){
            metric_compare_value = sec_to_normal(metric_compare_value);
          }
          else if(metric + " compare" == 'Percent New Sessions compare' || metric + " compare" == 'Bounce Rate compare'){
            metric_compare_value = metric_compare_value + '%';
          }
          if(metric == 'Avg Session Duration' || metric == 'Avg Page Load Time' || metric == 'Avg Server Response Time' || metric == 'Avg Redirection Time' || metric == 'Avg Page Download Time'){
            metric_value = sec_to_normal(metric_value);
          }
          else if(metric == 'Percent New Sessions' || metric == 'Bounce Rate'){
            metric_value = metric_value + '%';
          }
          //percent = percent.substring(0, percent.indexOf(".") + 2);
          var percent_color = percent < 0 || metric == 'bounceRate' ? "gawd_red" : "gawd_green";
          jQuery(".sum_box").remove();
          jQuery("._sum_box").remove();
          var sumBox = "<div class='sum_box'>";
          var metric_title = metric == 'Percent New Sessions' ? '% New Sessions' : metric;
          sumBox += "<div class='box_left'>";
          sumBox += "<div class='box_title'>" +  metric_title + "</div>";
          sumBox += "<div class='" + percent_color + "'>" + percent + " % </div>";
          sumBox += "</div>";
          sumBox += "<div class='vs_image_small'>";
          sumBox += "<img src='" + gawd_admin.gawd_plugin_url + "/assets/vs_rev.png'>";
          sumBox += "</div>";
          sumBox += "<div class='box_right'>";
          sumBox += "<div class='box_value'>" + metric_value + "</div>" + "<div class='box_value'>" + metric_compare_value + "</div>";
          sumBox += "</div>";
          sumBox += "<div class='clear'></div>";
          sumBox += "</div>";
          jQuery('#chartdiv').after(sumBox);
          if (dimension == 'goals') {
              dimension = 'date';
          }
            jQuery("#chartdiv").show();
            dimension = dimension == 'date' || dimension == 'siteSpeed' || dimension == 'adsense' || dimension == 'sales_performance' ? filter_type == '' ? 'date' : filter_type : dimension;
            //dimension = dimension == 'siteSpeed' || dimension == 'adsense' ? 'date' : dimension;
            var dimension_export = dimension;
            dimension = dimension.replace(/([A-Z])/g, " $1").trim();
            dimension = dimension.charAt(0).toUpperCase() + dimension.slice(1);
            _data_compare = data_compare;
            var duration = "";
            var durationUnits = "";
             if(metric == 'Avg Session Duration'){
                duration = "ss";
                durationUnits = {
                    "mm": "m ",
                    "ss": "s"
                };
             }

            if (chartType === "line" || chartType === "column") {
                var chart = new gawd_charts_helper();
                chart.print_reports_line_chart_compare(
                    gawd_chart_data,
                    data.chart_data,
                    dimension,
                    metric,
                    chartType,
                    'chartdiv'
                );
            }


            //CANVAS//


            var svg = document.getElementsByTagName('svg')[0];
            var canvas = document.getElementById("canvass");
            draw_canvas(svg, canvas);
            url = canvas.toDataURL();
            //CANVAS URL//
            var tab_name = window.location.href.split('tab=');

            d_start_date = start_date_compare;
            d_end_date = end_date_compare;
            d_metric_export = metric_export;
            d_metric_compare_export = metric_compare_export;
            d_dimension_export = dimension_export;
            d_tab_name = tab_name;
            d_filter_type = filter_type;

          jQuery("#chartdiv").find('a').hide();
          jQuery("#gbox_griddiv").remove();
          var grid = '<table id="griddiv"></table><div id="pager"></div>';
          jQuery('.gawd_chart_conteiner').append(grid);
          gawd_draw_table(JSON.stringify(data_compare), metric, metric + ' compare', dimension, dataSums);
        }
    })
}
function gawd_draw_analytics_compact(metric, metric_compare, dimension, chart_type, chart_id) {
    var _end_date = (Date.today().add(-1).days()).toString("yyyy-MM-dd");
    var start_date_7 = (Date.today().add(-1).days()).add(-7).days().toString("yyyy-MM-dd");
    var start_end_date = typeof jQuery('#gawd_start_end_date_compact').val() != 'undefined' ? jQuery('#gawd_start_end_date_compact').val() : start_date_7 + '/-/' + _end_date;

    var start_end_date = start_end_date.split('/-/');
    var start_date = start_end_date[0];
    var end_date = start_end_date[1];
    metrics = [];
    metrics.push("ga:" + metric);
    if (metric_compare != 0) {
        metrics.push("ga:" + metric_compare);
    }

    if (dimension == 'date') {
        var parseDates = true;
        var rotateAngle = 90;
    } else {
        var parseDates = false;
        var rotateAngle = 90;
    }
    if (chart_type == 'column') {
        var fillAlphas = 1;
    }
    var timezone = -(new Date().getTimezoneOffset() / 60);
  console.log("ajax03");
  var args = gawd_custom_ajax_args();
  args.type = 'POST';
  args.async = true;
  args.data.gawd_action = "gawd_show_data_compact";
  args.data.gawd_data = {
    "start_date": start_date,
    "end_date": end_date,
    "metric": metrics,
    "dimension": dimension,
    "security": gawd_admin.ajaxnonce,
    "timezone": timezone,
  };
  args.beforeSend = function() {
    jQuery('#' + chart_id).closest(".postbox").find('.opacity_div_compact').show();
    jQuery('#' + chart_id).closest(".postbox").find('.loading_div_compact').show();
  };
  args.success = function (result){

    jQuery('.opacity_div_compact').hide();
    jQuery('.loading_div_compact').hide();

    if(result.success === false){
        if(chart_id === "gawd_country_meta" ) {
            gawd_add_notice(result, '#gawd_overview_location');
        }else{
            gawd_add_notice(result, chart_id);
        }
        return;
    }
    dimension = dimension.replace(/([A-Z])/g, " $1").trim();
    dimension = dimension.charAt(0).toUpperCase() + dimension.slice(1);
    metric = metric.replace(/([A-Z])/g, " $1").trim();
    metric = metric.charAt(0).toUpperCase() + metric.slice(1);
    metric = metric.replace(/  +/g, ' ');
    metric_compare = metric_compare.replace(/([A-Z])/g, " $1").trim();
    metric_compare = metric_compare.charAt(0).toUpperCase() + metric_compare.slice(1);
    metric_compare = metric_compare.replace(/  +/g, ' ');
    if(dimension == 'Date'){
      var data = JSON.parse(result.data.gawd_show_data_compact);
      var c = '';
      var duration = "";
      var durationUnits = "";
      if(metric == 'Avg Session Duration'){
        duration = "ss";
        durationUnits = {
          "mm": "m ",
          "ss": "s"
        };
      }
      if (metric_compare != '' && metric_compare != 0) {
        var _duration = '';
        var _durationUnits = '';
        metric_compare = metric_compare.replace(/([A-Z])/g, "$1").trim();
        metric_compare = metric_compare.charAt(0).toUpperCase() + metric_compare.slice(1);
        metric_compare = metric_compare.replace(/  +/g, ' ');
        metric_compare_title = /* ' vs ' + */ metric_compare.charAt(0).toUpperCase() + metric_compare.slice(1) == "Percent New Sessions" ? '% New Sessions' : metric_compare.charAt(0).toUpperCase() + metric_compare.slice(1);
        if(metric_compare == 'Avg Session Duration'){
          _duration = "ss";
          _durationUnits = {
            "mm": "m ",
            "ss": "s"
          };
        }
        c =  {
          "id": "g2",
          "axisAlpha": 0.4,
          "position": "right",
          "title":  metric_compare_title,
          "ignoreAxisWidth": false,
          "duration": _duration,
          "durationUnits": _durationUnits,
          'minimum': 0
        };

      }

      jQuery("#_sum_comp_" + chart_id).remove();
      jQuery('#_sum_comp_' + chart_id).remove();

      if(typeof data.data_sum != 'undefined'){
        var float = '';
        if (metric_compare != '' && metric_compare != 0){
          float="style='float:left'";
          var total = data.data_sum[metric_compare];
          var avg = '';
          var diff = ((Date.parse(end_date).getTime() - Date.parse(start_date).getTime()) / 3600 / 24 / 1000)+1;
          var show_hide = 'gawd_show_total';
          if(metric == 'Bounce Rate' || metric == 'Percent New Sessions' || metric == 'Pageviews Per Session'){
            avg = parseFloat(total).toFixed(2);
            if(metric != 'Pageviews Per Session'){
              avg = avg + '%';
            }
            show_hide = 'gawd_hide_total';
          }
          else if(metric_compare == 'Avg Session Duration'){
            avg = sec_to_normal(avg);
            show_hide = 'gawd_hide_total';
          }
          else{
            //avg = Math.ceil(total/diff);
            avg = parseFloat(total/diff).toFixed(2);
          }
          var percent_color = total == 0 || metric_compare == 'Bounce Rate' ? "gawd_red" : "gawd_green";
          var sumBox = "<div class='_sum_box' id='_sum_comp_" + chart_id + "'>";
          metric_compare_title = metric_compare == 'Percent New Sessions' ? '% New Sessions' : metric_compare;
          sumBox += "<div class='box_metric'>" + metric_compare_title + "</div>"
          sumBox += "<div class='_box_left " + show_hide + "'>";
          sumBox += "<div class='box_title'>Total</div>";
          sumBox += "<div class='" + percent_color + "'>" + parseInt(total).toLocaleString() + "</div>";
          sumBox += "</div>";

          sumBox += "<div class='_box_right'>";
          sumBox += "<div class='box_title'>Average</div>";
          sumBox += "<div class='" + percent_color + "'>" + parseInt(avg).toLocaleString() + "</div>";
          sumBox += "</div>";
          sumBox += "<div class='clear'></div>";
          sumBox += "</div>";
          sumBox += "<div class='clear'></div>";
          jQuery('#'+chart_id).after(sumBox);
        }
        var total = data.data_sum[metric];

        var avg = '';
        var diff = ((Date.parse(end_date).getTime() - Date.parse(start_date).getTime()) / 3600 / 24 / 1000)+1;
        var show_hide = 'gawd_show_total';
        if(metric == 'Bounce Rate' || metric == 'Percent New Sessions' || metric == 'Pageviews Per Session'){
          avg = parseFloat(total).toFixed(2);
          if(metric != 'Pageviews Per Session'){
            avg = avg + '%';
          }
          show_hide = 'gawd_hide_total';
        }
        else if(metric == 'Avg Session Duration'){
          avg = sec_to_normal(avg);
          show_hide = 'gawd_hide_total';
        }
        else{
          //avg = Math.ceil(total/diff);
          avg = parseFloat(total/diff).toFixed(2);
        }
        var percent_color = total == 0 || metric == 'Bounce Rate' ? "gawd_red" : "gawd_green";
        jQuery('#_sum_' + chart_id).remove();
        var sumBox = "<div " + float + "  class='_sum_box' id='_sum_" + chart_id + "'>";
        var metric_title = metric == 'Percent New Sessions' ? '% New Sessions' : metric;
        sumBox += "<div class='box_metric'>" +  metric_title + "</div>"
        sumBox += "<div class='_box_left " + show_hide + "'>";
        sumBox += "<div class='box_title'>Total</div>";
        sumBox += "<div class='" + percent_color + "'>" + parseInt(total).toLocaleString() + "</div>";
        sumBox += "</div>";

        sumBox += "<div class='_box_right'>";
        sumBox += "<div class='box_title'>Average</div>";
        sumBox += "<div class='" + percent_color + "'>" + parseInt(avg).toLocaleString() + "</div>";
        sumBox += "</div>";
        sumBox += "<div class='clear'></div>";
        sumBox += "</div>";
        jQuery('#'+chart_id).after(sumBox);
      }

      var chart = new gawd_charts_helper();
      chart.print_compact_line_chart(data.chart_data, dimension, metric, metric_compare, chart_id);
    }
    else{
      gawd_draw_table(result.data.gawd_show_data_compact, metric, 0, dimension);
    }
  };

  args.error = function (data) {
    jQuery(".opacity_div_compact").hide();
    jQuery(".loading_div_compact").hide();
  };

  jQuery.ajax(args);
}

function gawd_draw_analytics() {

    console.log("ajax2");
    jQuery('#country_filter_reset').hide();
    jQuery("#metric_compare").show();
    jQuery(".vs_image").show();
    jQuery(".sum_box").remove();
    jQuery("#filter_conteiner").show();
    //jQuery('#chartdiv').height(700);
    jQuery("#chartdiv").show();
    var _end_date = (Date.today().add(-1).days()).toString("yyyy-MM-dd");
    var start_date_7 = (Date.today().add(-1).days()).add(-7).days().toString("yyyy-MM-dd");
    var start_end_date = typeof jQuery('#gawd_start_end_date').val() != 'undefined' ? jQuery('#gawd_start_end_date').val() : start_date_7 + '/-/' + _end_date;

    var start_end_date = start_end_date.split('/-/');
    var start_date = start_end_date[0];
    var end_date = start_end_date[1];
    var filter_type = jQuery("#gawd_filter_val").val();
    var timezone = -(new Date().getTimezoneOffset() / 60);

    metrics = [];
    var metric = jQuery("#gawd_metric").val();
    var metric_compare = jQuery("#gawd_metric_compare").val();
    metrics.push("ga:" + metric);
    var metric_compare_title = '';
    if (metric_compare != 0) {
        metrics.push("ga:" + metric_compare);
    } else {
        metric_compare = '';
    }

    if (jQuery("#gawd_chart_type").val() == 'line') {
        var chartType = 'line';
        var fillAlphas = 0;
    } else if (jQuery("#gawd_chart_type").val() == 'column') {
        var chartType = 'column';
        var fillAlphas = 1;
    }
    var dimension = jQuery("#gawd_tab").val();
    var customReport = "";

    if (dimension == 'socialActivityNetworkAction' || dimension == 'socialActivityAction' || dimension == 'socialActivityTagsSummary' || dimension == 'socialActivityPost' || dimension == 'socialActivityTimestamp' || dimension == 'socialActivityUserProfileUrl' || dimension == 'socialActivityContentUrl' || dimension == 'socialActivityUserPhotoUrl' || dimension == 'socialActivityUserHandle' || dimension == 'socialActivityEndorsingUrl' || dimension == 'socialEndorsingUrl' || dimension == 'socialActivityDisplayName') {
        jQuery('#metric_compare').hide();
        jQuery('.vs_image').hide();
    }
    var parseDates = false;
    if (dimension == 'date') {
        parseDates = true;
        var rotateAngle = 0;
    } else if (dimension == 'realTime') {
        jQuery(".filter_conteiner").hide();
        jQuery("#chartdiv").empty();
        gawd_widget_real_time("#chartdiv");
        return;
    } else if (dimension == 'custom')
    {

      var custom = jQuery("#gawd_custom_option").val();
      var not_exist = '<div id="gawd_error">There are no custom dimensions set for current profile.</div>';
      if(custom === null || typeof custom === 'undefined'){
        var args = gawd_custom_ajax_args();
        args.type = 'POST';
        args.async = true;
        args.data.gawd_action = "get_custom_dimensions";
        args.data.gawd_data = {
          "is_last_viewed_profile": 1,
        };
        args.beforeSend = function() {

        };
        args.success = function (data){
          if(data.data.length === 0){

            jQuery("#wdi_no_custom_dimensions").css({
              'display':'block'
            });
            jQuery('#chartdiv').append(not_exist);
            return;

          }
          else{
            jQuery("#gawd_custom_option").css({
              'display':'block'
            });
            if(typeof data.data != "undefined"){
              for(i in data.data){
                var custom_dimension_el = data.data[i];
                jQuery("#gawd_custom_option").append("<option value='"+custom_dimension_el['id']+"'>"+custom_dimension_el['name']+"</option>");
              }
            }
            custom = jQuery("#gawd_custom_option").val();
            if (typeof custom != 'undefined') {
              dimension = custom.substring(3);
            }
            else {
              if (jQuery('#gawd_error').length <= 0) {
                jQuery('#chartdiv').append(not_exist);
              }
              return;
            }

          }
          gawd_show_data_ajax();
        }
        args.error = function (data) {
          jQuery("#wdi_no_custom_dimensions").css({
            'display':'block'
          });
        }
        jQuery.ajax(args);
        return;
      }else{
        if (typeof custom != 'undefined') {
          dimension = custom.substring(3);
        }
        else {
          if (jQuery('#gawd_error').length <= 0) {
            jQuery('#chartdiv').append(not_exist);
          }
          return;
        }
        gawd_show_data_ajax();
        return;
      }
          /*ajax for custom */
    }
    else if(typeof dimension != "undefined" && dimension.indexOf("custom_") > -1){
      customReport = true;
      dimension = dimension.substring(7);
    }
    else {
        var rotateAngle = 90;
    }

    gawd_show_data_ajax();

    function gawd_show_data_ajax() {
      var args = gawd_custom_ajax_args();
      args.type = 'POST';
      args.async = true;
      args.data.gawd_action = "gawd_show_data";
      args.data.gawd_data = {
        "start_date": start_date,
        "end_date": end_date,
        "metric": metrics,
        "dimension": dimension,
        "security": gawd_admin.ajaxnonce,
        "filter_type": filter_type,
        "custom": customReport,
        "timezone": timezone,
      };
      args.beforeSend = function() {
        jQuery('#opacity_div').show();
        jQuery('#loading_div').show();
      };
      args.success = function (data) {
        jQuery('#opacity_div').hide();
        jQuery('#loading_div').hide();

        if(data.success === false){
          gawd_add_notice(data);
          return;
        }
        gawd_request_last_args = args;
        data = data.data.gawd_reports_data;

        var result = JSON.parse(data);
        if (result.error_message != undefined) {
          var not_exist = '<div id="gawd_error">' + result.error_message + '</div>';
          if (jQuery('#gawd_error').length < 1) {
            jQuery('#chartdiv').append(not_exist);
          }
          return;
        }
        _data = result;

        if (result.chart_data) {
          result = result.chart_data;
        }
        var result_length = result.length;
        if (filter_type == "week" || filter_type == "month" || filter_type == "hour") {
          parseDates = false;
          result_length = 20;
        }
        if (dimension == 'goals') {
          dimension = 'date';
        }

        if (dimension != "pagePath" && dimension != "landingPagePath" && dimension != "daysToTransaction" && dimension != "transactionId") {
          jQuery("#chartdiv").show();
          if (dimension == 'date' || dimension == 'siteSpeed' || dimension == 'adsense' || dimension == 'sales_performance') {
            jQuery("#compare_time_conteiner").show();
            var dimension_export = dimension;
            if (filter_type == '') {
              dimension = 'date';
            } else {
              dimension = filter_type;
            }
            dimension = dimension.replace(/([A-Z])/g, " $1").trim().charAt(0).toUpperCase() + dimension.replace(/([A-Z])/g, " $1").trim().slice(1);
          } else {
            dimension = dimension.replace(/([A-Z])/g, " $1").trim().charAt(0).toUpperCase() + dimension.replace(/([A-Z])/g, " $1").trim().slice(1);
            var dimension_export = dimension;
          }
          metric = metric.replace(/([A-Z])/g, " $1").trim().charAt(0).toUpperCase() + metric.replace(/([A-Z])/g, " $1").trim().slice(1);
          metric = metric.replace(/  +/g, ' ');
          var metric_export = metric;
          var metric_compare_export = metric_compare;
          var c ='';
          gawd_chart_data = result;
          if (_data_compare.length > 0) {
            gawd_compare();
            return;
          }

          var duration = "";
          var durationUnits = "";
          if(metric == 'Avg Session Duration' || metric == 'Avg Session Duration' || metric == 'Duration' || metric == 'Avg Session Duration' || metric == 'Avg Page Load Time' || metric == 'Avg Server Response Time' || metric == 'Avg Redirection Time' || metric == 'Avg Page Download Time'){
            duration = "ss";
            durationUnits = {
              "mm": "m ",
              "ss": "s"
            };
          }
          if (metric_compare != '') {
            var _duration = '';
            var _durationUnits = '';
            metric_compare = metric_compare.replace(/([A-Z])/g, " $1").trim();
            metric_compare = metric_compare.charAt(0).toUpperCase() + metric_compare.slice(1);
            metric_compare = metric_compare.replace(/  +/g, ' ');
            metric_compare_title = /* ' vs ' + */ metric_compare.charAt(0).toUpperCase() + metric_compare.slice(1) == "Percent New Sessions" ? '% New Sessions' : metric_compare.charAt(0).toUpperCase() + metric_compare.slice(1);

            if(metric_compare == 'Avg Session Duration' || metric_compare == 'Duration' || metric_compare == 'Avg Session Duration' || metric_compare == 'Avg Page Load Time' || metric_compare == 'Avg Server Response Time' || metric_compare == 'Avg Redirection Time' || metric_compare == 'Avg Page Download Time'){
              _duration = "ss";
              _durationUnits = {
                "mm": "m ",
                "ss": "s"
              };
            }
            c =  {
              "id": "g2",
              "axisAlpha": 0.4,
              "position": "right",
              "title":  metric_compare_title,
              "ignoreAxisWidth": false,
              "duration": _duration,
              "durationUnits": _durationUnits,
              'minimum': 0,
              "boldLabels": true,
              "zeroGridAlpha": 1
            };
          }
          jQuery('#_sum_comp_chartdiv').remove();
          jQuery('#_sum_box').remove();
          if(typeof _data.data_sum != 'undefined'){
            var float = '';
            if(metric_compare != ''){
              float="style='float:left'";
              var total = _data.data_sum[metric_compare];
              var avg = '';
              var diff = ((Date.parse(end_date).getTime() - Date.parse(start_date).getTime()) / 3600 / 24 / 1000)+1;
              var show_hide = 'gawd_show_total';
              if(metric_compare == 'Bounce Rate' || metric_compare == 'Percent New Sessions' || metric_compare == 'Pageviews Per Session'){
                avg = parseFloat(total).toFixed(2);
                if(metric_compare != 'Pageviews Per Session'){
                  avg = avg + '%';
                }
                show_hide = 'gawd_hide_total';
              }
              else if(metric_compare == 'Avg Session Duration' || metric_compare == 'Avg Page Load Time' || metric_compare == 'Avg Server Response Time' || metric_compare == 'Avg Redirection Time' || metric_compare == 'Avg Page Download Time' || metric_compare == 'Duration'){
                avg = sec_to_normal(total);
                show_hide = 'gawd_hide_total';
              }
              else{
                //avg = Math.ceil(total/diff);
                avg = parseFloat(total/diff).toFixed(2);
              }

              var percent_color = total == 0 || metric_compare == 'Bounce Rate' ? "gawd_red" : "gawd_green";
              var sumBox = "<div class='_sum_box' id='_sum_comp_chartdiv'>";
              var metric_compare_title = metric_compare == 'Percent New Sessions' ? '% New Sessions' : metric_compare;
              sumBox += "<div class='box_metric'>" + metric_compare_title + "</div>"
              sumBox += "<div class='_box_left " + show_hide + "'>";
              sumBox += "<div class='box_title'>Total</div>";
              sumBox += "<div class='" + percent_color + "'>" + parseInt(total).toLocaleString() + "</div>";
              sumBox += "</div>";

              sumBox += "<div class='_box_right'>";
              sumBox += "<div class='box_title'>Average</div>";
              sumBox += "<div class='" + percent_color + "'>" +  parseInt(avg).toLocaleString() + "</div>";
              sumBox += "</div>";
              sumBox += "<div class='clear'></div>";
              sumBox += "</div>";
              sumBox += "<div class='clear'></div>";
              jQuery('#chartdiv').after(sumBox);
            }

            var total = _data.data_sum[metric];
            var avg = '';
            var diff = ((Date.parse(end_date).getTime() - Date.parse(start_date).getTime()) / 3600 / 24 / 1000)+1;
            var show_hide = 'gawd_show_total';
            if(metric == 'Bounce Rate' || metric == 'Percent New Sessions' || metric == 'Pageviews Per Session'){
              avg = parseFloat(total).toFixed(2);
              if(metric != 'Pageviews Per Session'){
                avg = avg + '%';
              }
              show_hide = 'gawd_hide_total';
            }
            else if(metric == 'Avg Session Duration' || metric == 'Avg Page Load Time' || metric == 'Avg Server Response Time' || metric == 'Avg Redirection Time' || metric == 'Avg Page Download Time' || metric == 'Duration'){
              avg = sec_to_normal(total);
              show_hide = 'gawd_hide_total';
            }
            else{
              //avg = Math.ceil(total/diff);
              avg = parseFloat(total/diff).toFixed(2);
            }
            var percent_color = total == 0 || metric == 'Bounce Rate' ? "gawd_red" : "gawd_green";
            var sumBox = "<div " + float + "  class='_sum_box' id='_sum_box'>";
            var metric_title = metric == 'Percent New Sessions' ? '% New Sessions' : metric;
            sumBox += "<div class='box_metric'>" + metric_title + "</div>"
            sumBox += "<div class='_box_left " + show_hide + "'>";
            sumBox += "<div class='box_title'>Total</div>";
            sumBox += "<div class='" + percent_color + "'>" + parseInt(total).toLocaleString() + "</div>";
            sumBox += "</div>";

            sumBox += "<div class='_box_right'>";
            sumBox += "<div class='box_title'>Average</div>";
            sumBox += "<div class='" + percent_color + "'>" + parseInt(avg).toLocaleString() + "</div>";
            sumBox += "</div>";
            sumBox += "<div class='clear'></div>";
            sumBox += "</div>";
            jQuery('#chartdiv').after(sumBox);
          }

            if (chartType === "line" || chartType === "column") {
                var chart = new gawd_charts_helper();
                chart.print_reports_line_chart(result, dimension, metric, metric_compare, chartType, 'chartdiv');
            }
        }
        else {
          var metric_export = metric;
          var metric_compare_export = metric_compare;
          var dimension_export = dimension;
          jQuery("#chartdiv").hide();
          //jQuery(".filter_conteiner").hide();
          jQuery(".filter_conteiner").css({'height': '75px','position': 'relative'});
          if(typeof jQuery.cookie("collapsed") != 'undefined' && jQuery.cookie("collapsed") == 1){
            jQuery("#compare_time_conteiner").css({'float': 'none','width': '35%','position': 'absolute','right':'4%',"top": "35px"});
            jQuery('#compare_datepicker_wraper').width('98.5%');
            jQuery('.gawd_content').width('50.6%');
          }
          else{
            jQuery("#compare_time_conteiner").css({'float': 'none','width': '36.6%','position': 'absolute','right':'2.3%',"top": "35px"});
            jQuery('#compare_datepicker_wraper').width('98.5%');
          }

          jQuery("#date_chart_conteiner").css({'position': 'absolute','float': 'none','right':'0'});
          jQuery(".gawd_date_filter_container").hide();
          jQuery("#date_chart_conteiner .gawd_row:first-child").hide();
        }

        var tab_name = window.location.href.split('tab=');
        d_start_date = start_date;
        d_end_date = end_date;
        d_second_start_date = start_date;
        d_second_end_date = end_date;
        d_metric_export = metric_export;
        d_metric_compare_export = metric_compare_export;
        d_dimension_export = dimension_export;
        d_custom = customReport,
          d_tab_name = tab_name;
        d_filter_type = filter_type;
        jQuery("#gbox_griddiv").remove();
        var grid = '<table id="griddiv"></table><div id="pager"></div>';
        jQuery('.gawd_chart_conteiner').append(grid);

        if (typeof data_of_compared != 'undefined' && typeof data_of_compared['chart_data'] != 'undefined' && (dimension == "pagePath" || dimension == "landingPagePath")) {
          gawd_compare();
          return;
        }

        gawd_draw_table(data, metric, metric_compare, dimension);
      }
      args.error = function (data) {
      }
      jQuery.ajax(args).done(function () {
          jQuery(".opacity_div_compact").hide();
          jQuery(".loading_div_compact").hide();

          jQuery('#opacity_div').hide();
          jQuery('#loading_div').hide();
      });
    }

}

////////
jQuery(document).ready(function () {

    jQuery('.gawd_export_button_csv').on('click', function (e) {
        e.preventDefault();

        var data = gawd_get_report_file_data();
        data['action'] = 'gawd_create_csv_file';


        jQuery.post(gawd_admin.ajaxurl, data ).done(function (response) {
            response = JSON.parse(response);
            if(response.success === true){
                window.location.href =response['data']['download_url'];
            }

        });

        return false;
    });

    jQuery('.gawd_export_button_csvs').on('click', function () {

        if (typeof d_tab_name[1] == 'undefined') {
            d_tab_name[1] = 'general';
        }
/*         var location = gawd_admin.exportUrl +
                '&export_type=csv&gawd_start_date=' + d_start_date +
                '&gawd_end_date=' + d_end_date +
                '&gawd_metric=' + d_metric_export +
                '&gawd_metric_compare=' + d_metric_compare_export +
                '&gawd_dimension=' + d_dimension_export +
                '&tab_name=' + d_tab_name[1] +
                '&filter_type=' + d_filter_type +
                '&report_type=alert&security=' + gawd_admin.ajaxnonce +
                '&country_filter=' + d_country_filter +
                '&geo_type=' + d_geo_type; */

        var order = get_order_data_for_export();

        //location += "&sort=" + order.order + "&sort_by=" + order.order_by;
       // window.location = location;
        var data = {
            action: 'create_csv_file',
            export_type: 'csv',
            tab_name: d_tab_name[1],
            report_type: 'alert',
            security: gawd_admin.ajaxnonce,
            sort: order.order,
            sort_by: order.order_by
        };

        if(jQuery('#second_data').val() != ''){
          if(d_tab_name[1] == 'pagePath' || d_tab_name[1] == 'landingPagePath'){
            data.second_end_date =  jQuery('#second_end_date').val();
            data.second_start_date = jQuery('#second_start_date').val();
            data.second_data_sum = jQuery('#second_data_sum').val();
            data.first_data_sum = jQuery('#first_data_sum').val();
            data.dimension = jQuery('#dimension').val();
            data.second_data = jQuery('#second_data').val();
            data.first_data = jQuery('#first_data').val();
          }
        }
        else{
          data._data_compare = _data_compare;
          data.second_data_sum = jQuery('#second_data_sum').val(),
          data.first_data_sum = jQuery('#first_data_sum').val(),
          data.gawd_start_date = d_start_date,
          data.gawd_end_date = d_end_date,
          data.second_end_date = d_second_end_date,
          data.second_start_date = d_second_start_date,
          data.gawd_metric = d_metric_export,
          data.gawd_metric_compare = d_metric_compare_export,
          data.gawd_dimension = d_dimension_export,
          data.filter_type = d_filter_type,
          data.country_filter = d_country_filter,
          data.geo_type = d_geo_type
        }
      console.log("ajax04");
        jQuery.post(gawd_admin.ajaxurl, data ).done(function (url) {
            jQuery('#opacity_div').hide();
            jQuery('#loading_div').hide();

            window.location.href = gawd_admin.exportUrl + '&export_type=csv&report_type=alert&sort=' + order.order + '&sort_by=' + order.order_by+'&security='+gawd_admin.ajaxnonce;
        });
        return false;

    });
    jQuery('.gawd_export_button_pdf').on('click', function () {
        jQuery('#opacity_div').show();
        jQuery('#loading_div').show();

        var order = get_order_data_for_export();

        var tab_name = window.location.href.split('tab=');
        if (typeof tab_name[1] == 'undefined') {
            tab_name[1] = 'general';
        }
        //CANVAS//
        if (tab_name[1] != 'pagePath' && tab_name[1] != 'landingPagePath' && tab_name[1] != 'daysToTransaction' && tab_name[1] != 'pagePath#' && tab_name[1] != 'landingPagePath#' & tab_name[1] != 'daysToTransaction#') {
            jQuery('svg').find('desc').remove();
            var svg = document.getElementsByTagName('svg')[0];
            var canvas = document.getElementById("canvass");
            draw_canvas(svg, canvas);
            url = canvas.toDataURL();
        }
        if (typeof url == 'undefined') {
            url = '';
        }
        var data = {
            action: 'create_pdf_file',
            export_type: 'pdf',
            tab_name: tab_name[1],
            report_type: 'alert',
            security: gawd_admin.ajaxnonce,
            sort: order.order,
            sort_by: order.order_by,
            gawd_start_date: d_start_date,
            gawd_end_date: d_end_date
        };

        if(d_custom) {
            data.custom = d_custom;
          }

        if(jQuery('#second_data').val() != ''){
          if(tab_name[1] == 'pagePath' || tab_name[1] == 'landingPagePath'){
            data.second_end_date = jQuery('#second_end_date').val();
            data.second_start_date = jQuery('#second_start_date').val();
            data.second_data_sum = jQuery('#second_data_sum').val();
            data.first_data_sum = jQuery('#first_data_sum').val();
            data.dimension = jQuery('#dimension').val();
            data.second_data = jQuery('#second_data').val();
            data.first_data = jQuery('#first_data').val();
          }
        }
/*         else if(_data_compare != ''){
          data.img = url;
          data._data_compare = _data_compare;
        } */
        else{
          data.img = url;
          data._data_compare = _data_compare;
          data.second_data_sum = jQuery('#second_data_sum').val(),
          data.first_data_sum = jQuery('#first_data_sum').val(),
          data.second_end_date = d_second_end_date;
          data.second_start_date = d_second_start_date;
          data.gawd_metric = d_metric_export,
          data.gawd_metric_compare = d_metric_compare_export,
          data.gawd_dimension = d_dimension_export,
          data.filter_type = d_filter_type,
          data.country_filter = d_country_filter,
          data.geo_type = d_geo_type
        }
      console.log("ajax05");
        jQuery.post(gawd_admin.ajaxurl, data ).done(function (url) {

            jQuery('#opacity_div').hide();
            jQuery('#loading_div').hide();

            window.location.href = gawd_admin.exportUrl + '&export_type=pdf&report_type=alert';
        });


        return false;
    });

    /*  FREE */

    var pro_features_list = {
        'reports': {
            title1: 'The free version is limited up to 2 report types.',
            title2: 'Upgrade to Premium version to see the rest.',
            list: [
                'Аdvanced GA reports',
                'Ecommerce & AdWords reports',
                'Advanced tracking',
                // 'AdWords reports',
                // 'Google AdSense & AdWords reports',
                'Custom reports',
                //'Alerts and Pushover Notifications'
            ]
        },
        'custom_reports': {
            title1: 'Custom reports are not available in free version.',
            title2: 'Upgrade to unlock all reports.',
            list: [
                'Аdvanced GA reports',
                'Ecommerce & AdWords reports',
                'Advanced tracking',
                // 'AdWords reports',
                // 'Google AdSense & AdWords reports',
                'Custom reports',
                //'Alerts and Pushover Notifications'
            ]
        },
        'custom_dimensions': {
            title1: 'Custom dimensions are not available in free version.',
            title2: 'Upgrade to Premium version to configure custom tracking.',
            list: [
                'Аdvanced GA reports',
                'Ecommerce & AdWords reports',
                'Advanced tracking',
                // 'AdWords reports',
                // 'Google AdSense & AdWords reports',
                'Custom reports',
                //'Alerts and Pushover Notifications'
            ]
        },
        'exclude_tracking': {
            title1: 'Custom tracking settings are not available.',
            title2: 'Upgrade to Premium version to customize tracking.',
            list: [
                'Аdvanced GA reports',
                'Ecommerce & AdWords reports',
                'Advanced tracking',
                // 'AdWords reports',
                // 'Google AdSense & AdWords reports',
                'Custom reports',
                //'Alerts and Pushover Notifications'
            ]
        }
    };

    jQuery('.gawd_inactive').each(function () {
        var $_this = jQuery(this);

        if ($_this.find('.gawd_menu_ul_li').length > 0) {
            $_this.find('.gawd_menu_ul_li').on('click', {feature_key: 'reports'}, gawd_open_pro_popup);
        } else {
            $_this.on('click', {feature_key: 'reports'}, gawd_open_pro_popup);
        }

    });

    jQuery('.gawd_menu_coteiner_collapse .gawd_menu_ul span.gawd_menu_li_sub, .gawd_menu_coteiner_collapse .gawd_menu_ul a.gawd_menu_item').each(function(){
        var id = jQuery(this).attr('id');
        if(id !== 'gawd_general' && id !== 'gawd_realtime') {
            jQuery(this).on('click', {feature_key: 'reports'}, gawd_open_pro_popup);
        }
    });

    jQuery('.gawd_pro_img').each(function(){
        var $_this = jQuery(this);
        $_this.on('click', {feature_key: $_this.data('gawd-screenshot')}, gawd_open_pro_popup);
    });

    jQuery('.gawd_pro_popup_close_btn').on('click', gawd_close_pro_popup);
    jQuery('.gawd_pro_popup_overlay').on('click', gawd_close_pro_popup);

    function gawd_open_pro_popup(args) {
        args.preventDefault();

        var key = args.data.feature_key;

        jQuery('.gawd_pro_popup').find('.gawd_pro_popup_title1').html(pro_features_list[key].title1);
        jQuery('.gawd_pro_popup').find('.gawd_pro_popup_title2').html(pro_features_list[key].title2);

        var list_html = "";
        for (var i in pro_features_list[key].list) {
            list_html += "<li>" + pro_features_list[key].list[i] + "</li>";
        }

        jQuery('.gawd_pro_popup').find('.gawd_pro_popup_content ul').html(list_html);

        jQuery('.gawd_pro_popup_overlay').show();
        jQuery('.gawd_pro_popup').show();

        return false;
    }

    function gawd_close_pro_popup() {
        jQuery('.gawd_pro_popup_overlay').hide();
        jQuery('.gawd_pro_popup').hide();
    }
    /* END FREE*/
});

function gawd_get_report_file_data(){
    if (typeof  gawd_request_last_args === "undefined") {
        return false;
    }

    var last_args = gawd_request_last_args.data;
    var compare_last_args = (typeof  gawd_compare_request_last_args !== 'undefined' && typeof  gawd_compare_request_last_args.data !== 'undefined') ? gawd_compare_request_last_args.data : null;

    var compare_by = (typeof jQuery('#gawd_metric_compare').attr('disabled') === 'undefined') ? "metric" : "date";
    if(compare_by === "date"){
        var compare_metric_name = jQuery('#gawd_metric_compare').val();

        if(last_args.gawd_data.metric.length > 1){

            if (last_args.gawd_data.metric[0] === "ga:" + compare_metric_name) {
                last_args.gawd_data.metric = [last_args.gawd_data.metric[1]];
            } else if (last_args.gawd_data.metric[1] === "ga:" + compare_metric_name) {
                last_args.gawd_data.metric = [last_args.gawd_data.metric[0]];
            }

        }
    }

    var gawd_daterange = jQuery('.gawd_daterange .ranges li.active').text();
    if(gawd_daterange === ""){
        gawd_daterange = "Last 30 Days ";
    }

    var gawd_compare_daterange = jQuery('.gawd_compare_daterange .ranges li.active').text();
    if(gawd_compare_daterange === ""){
        gawd_compare_daterange = "Previous period";
    }

    var data = {
        security: gawd_admin.ajaxnonce,
        gawd_request_last_args: gawd_request_last_args.data,
        gawd_compare_request_last_args: (typeof  gawd_compare_request_last_args !== 'undefined' && typeof  gawd_compare_request_last_args.data !== 'undefined') ? gawd_compare_request_last_args.data : null,
        menu_name: jQuery('#gawd_page_title').text().trim(),
        compare_by: (typeof jQuery('#gawd_metric_compare').attr('disabled') === 'undefined') ? "metric" : "date",
        info: {
            'date_ranges': [gawd_daterange, gawd_compare_daterange]
        }
    };

    return data;
}

function get_order_data_for_export() {
    var el = jQuery('.ui-jqgrid-htable').find('th[aria-selected="true"]');
    if (el.length == 0) {
        el = jQuery('.ui-jqgrid-htable').find('th:first');
    }

    var order_by = el.find('.ui-jqgrid-sortable').text();
    var order_el = el.find('span.s-ico').find('span').not('.ui-state-disabled');
    if (order_el.length == 0) {
        order_el = el.find('span.s-ico:first');
    }
    var order = order_el.attr('sort');
    return    {
        'order': order,
        'order_by': order_by
    };
}
function draw_canvas(svg, canvas) {
    var serializer = new XMLSerializer();
    var svgString = serializer.serializeToString(svg);
    canvg(canvas, svgString);
}
function gawd_draw_analytics_widget() {
    var end_date = (Date.today().add(-1).days()).toString("yyyy-MM-dd");
    var start_date_30 = (Date.today().add(-1).days()).add(-30).days().toString("yyyy-MM-dd");
    var start_date_7 = (Date.today().add(-1).days()).days().toString("yyyy-MM-dd");
    start_date = typeof jQuery("#gawd_widget_date").val() != 'undefined' ? jQuery("#gawd_widget_date").val() : start_date_7;
    var metric = jQuery("#gawd_metric_widget").val();
    if (start_date == 'realTime') {
        jQuery("#gawd_metric_widget").hide();
        gawd_widget_real_time('#chart_widget');
    } else {
        jQuery("#gawd_metric_widget").show();
        gawd_widget_all(start_date, end_date, metric);
    }
}

function gawd_widget_all(start_date, end_date, metric) {
    jQuery("#chart_widget").empty();
    jQuery('#chart_widget').height('300');
    jQuery(".opacity_div_compact").show();
    jQuery(".loading_div_compact").show();
    console.log("ajax06");
    var args = gawd_custom_ajax_args();
    args.type = 'POST';
    args.async = true;
    args.data.gawd_action = "gawd_show_data";
    args.data.gawd_data = {
        "start_date": start_date,
        "end_date": end_date,
        "metric": metric,
    };
    args.beforeSend = function () {

    };
    args.success = function (data) {
        jQuery(".opacity_div_compact").hide();
        jQuery(".loading_div_compact").hide();
        if (data.success === false) {
            gawd_add_notice(data, '#chart_widget');
            return;
        }
        gawd_request_last_args = args;
        data = data.data.gawd_reports_data;
        data = JSON.parse(data);
        data = data.chart_data;
        metric = metric.replace(/([A-Z])/g, " $1").trim();
        metric = metric.charAt(0).toUpperCase() + metric.slice(1);
        metric = metric.replace(/  +/g, ' ');
    }
    args.error = function (data) {
        jQuery(".opacity_div_compact").hide();
        jQuery(".loading_div_compact").hide();
    }
    jQuery.ajax(args);

}
function gawd_pie_chart() {
  //jQuery("#chart").empty();
    console.log('ajax2.5');
    var labels_enabled = true;
    if(jQuery(window).width()<=750){
      labels_enabled = false;
    }
    jQuery('#country_filter_reset').hide();
    jQuery("#metric_compare").hide();
    jQuery("#compare_time_conteiner").hide();
    jQuery(".vs_image").hide();
    var _end_date = (Date.today().add(-1).days()).toString("yyyy-MM-dd");
    var start_date_7 = (Date.today().add(-1).days()).add(-7).days().toString("yyyy-MM-dd");
    var start_end_date = typeof jQuery('#gawd_start_end_date').val() != 'undefined' ? jQuery('#gawd_start_end_date').val() : start_date_7 + '/-/' + _end_date;

    var start_end_date = start_end_date.split('/-/');
    var start_date = start_end_date[0];
    var end_date = start_end_date[1];
    metrics = [];
    var metric = jQuery("#gawd_metric").val();
    var metric_compare = jQuery("#gawd_metric_compare").val();
    if(jQuery("#metric_compare").is(":visible") != true){
      metric_compare = 0;
    }
    metrics.push("ga:" + metric);
    if (metric_compare != 0) {
        metrics.push("ga:" + metric_compare);
    }

    var dimension = jQuery("#gawd_tab").val();
    var customReport = "";
    if (dimension == 'custom') {
        var custom = jQuery("#gawd_custom_option").val();
        if (typeof jQuery("#gawd_custom_option").val() != 'undefined') {
            dimension = custom.substring(3);
        } else {
            var not_exist = '<div id="gawd_error">There are no custom dimensions set for current profile.</div>';
            if (jQuery('#gawd_error').length <= 0) {
                jQuery('#chartdiv').append(not_exist);
            }
            return;
        }
    } else if (typeof dimension != "undefined" && dimension.indexOf("custom_") > -1) {
        customReport = true;
        dimension = dimension.substring(7);
    }

    var args = gawd_custom_ajax_args();
    args.type = 'POST';
    args.async = true;
    args.data.gawd_action = "gawd_show_data";
    args.data.gawd_data = {
      "start_date": start_date,
      "end_date": end_date,
      "metric": metrics,
      "security": gawd_admin.ajaxnonce,
      "dimension": dimension,
      "custom": customReport
    };
    args.beforeSend = function() {
      jQuery('#opacity_div').show();
      jQuery('#loading_div').show();
    };
    args.success = function (data) {
      jQuery('#opacity_div').hide();
      jQuery('#loading_div').hide();

        if(data.success === false){
            gawd_add_notice(data);
            return;
        }
      gawd_request_last_args = args;
      data = data.data.gawd_reports_data;
      var result = JSON.parse(data);
      _data = result;
      if (result.error_message != undefined) {
        var not_exist = '<div id="gawd_error">' + result.error_message + '</div>';
        if (typeof jQuery('#gawd_error') == 'undefined') {
          jQuery('#chartdiv').append(not_exist);
        }
        return;
      }
      if (result.chart_data) {
        result = result.chart_data;
      }

        //jQuery('#chartdiv').height(700);

      if (dimension == 'goals') {
        dimension = 'date';
      }
      if (dimension == 'date' || dimension == 'siteSpeed' || dimension == 'adsense' || dimension == 'sales_performance') {
        //jQuery("#compare_time_conteiner").show();
        var dimension_export = dimension;
        if (filter_type == '' || typeof filter_type == 'undefined') {
          dimension = 'date';
        } else {
          dimension = filter_type;
        }

        dimension = dimension.replace(/([A-Z])/g, " $1").trim().charAt(0).toUpperCase() + dimension.replace(/([A-Z])/g, " $1").trim().slice(1);
      }
      else {
        dimension = dimension.replace(/([A-Z])/g, " $1").trim().charAt(0).toUpperCase() + dimension.replace(/([A-Z])/g, " $1").trim().slice(1);
        var dimension_export = dimension;
      }

      metric = metric.replace(/([A-Z])/g, " $1").trim();
      metric = metric.charAt(0).toUpperCase() + metric.slice(1);
      metric = metric.replace(/  +/g, ' ');
      var a = '';
      var baloon = true
      if(metric == "Bounce Rate" || metric == "Percent New Sessions"){
        a = '[[title]]: [[value]]%';
        baloon = false;
      }
      var metric_export = metric;
      var metric_compare_export = '';
      var filter_type = '';
      if(dimension.indexOf('Dimension') != -1){
        dimension = jQuery('#gawd_custom_option option:selected').text();
      }

        var chart = new gawd_charts_helper();
        chart.print_pie_chart(result, dimension, metric, "chartdiv");

      //CANVAS//
      var tab_name = window.location.href.split('tab=');
      if (tab_name[1] != 'pagePath' && tab_name[1] != 'landingPagePath' && tab_name[1] != 'daysToTransaction') {
        var svg = document.getElementsByTagName('svg')[0];
        var canvas = document.getElementById("canvass");
      if (typeof  svg !== 'undefined') {
          draw_canvas(svg, canvas);
          url = canvas.toDataURL();
      }
      }
      if (typeof url == 'undefined') {
        url = '';
      }
      //CANVAS URL//

      d_start_date = start_date;
      d_end_date = end_date;
      d_metric_export = metric_export;
      d_metric_compare_export = metric_compare_export;
      d_dimension_export = dimension_export;
      d_tab_name = tab_name;
      d_filter_type = filter_type;

      jQuery("#gbox_griddiv").remove();
      var grid = '<table id="griddiv"></table><div id="pager"></div>';
      jQuery('.gawd_chart_conteiner').append(grid);
      gawd_draw_table(data, metric, metric_compare, dimension);
    };
    args.error = function (data) {
      jQuery(".opacity_div_compact").hide();
      jQuery(".loading_div_compact").hide();
    };
    jQuery.ajax(args);
}
function gawd_pie_chart_compact(metric, dimension, chart_type, chart_id) {
    var _end_date = (Date.today().add(-1).days()).toString("yyyy-MM-dd");
    var start_date_7 = (Date.today().add(-1).days()).add(-7).days().toString("yyyy-MM-dd");
    var start_end_date = typeof jQuery('#gawd_start_end_date_compact').val() != 'undefined' ? jQuery('#gawd_start_end_date_compact').val() : start_date_7 + '/-/' + _end_date;

    var start_end_date = start_end_date.split('/-/');
    var start_date = start_end_date[0];
    var end_date = start_end_date[1];
    metrics = [];
    metrics.push("ga:" + metric);
  console.log("ajax07");

  var args = gawd_custom_ajax_args();
  args.type = 'POST';
  args.async = true;
  args.data.gawd_action = "gawd_show_data_compact";
  args.data.gawd_data = {
    "start_date": start_date,
    "end_date": end_date,
    "metric": metrics,
    "security": gawd_admin.ajaxnonce,
    "dimension": dimension,
  };
  args.beforeSend = function() {
    jQuery('#' + chart_id).closest(".postbox").find('.opacity_div_compact').show();
    jQuery('#' + chart_id).closest(".postbox").find('.loading_div_compact').show();
  };
  args.success = function (result){
      jQuery('#' + chart_id).closest(".postbox").find('.opacity_div_compact').hide();
      jQuery('#' + chart_id).closest(".postbox").find('.loading_div_compact').hide();

      if(result.success === false){
          gawd_add_notice(result, chart_id);
          return;
      }

    result = JSON.parse(result.data.gawd_show_data_compact);
    dimension = dimension.replace(/([A-Z])/g, " $1").trim();
    dimension = dimension.charAt(0).toUpperCase() + dimension.slice(1);
    metric = metric.replace(/([A-Z])/g, " $1").trim();
    metric = metric.charAt(0).toUpperCase() + metric.slice(1);
    metric = metric.replace(/  +/g, ' ');
    var total = 0;
    if(typeof result.data_sum != 'undefined'){

      total = result.data_sum[metric];
      var avg = '';
      var diff = ((Date.parse(end_date).getTime() - Date.parse(start_date).getTime()) / 3600 / 24 / 1000)+1;
      var show_hide = 'gawd_show_total';
      if(metric == 'Bounce Rate' || metric == 'Percent New Sessions' || metric == 'Pageviews Per Session'){
        avg = parseFloat(total).toFixed(2);
        if(metric != 'Pageviews Per Session'){
          avg = avg + '%';
        }
        show_hide = 'gawd_hide_total';
      }
      else if(metric == 'Avg Session Duration'){
        avg = sec_to_normal(avg);
        show_hide = 'gawd_hide_total';
      }
      else{
        //avg = Math.ceil(total/diff);
        avg = parseFloat(total/diff).toFixed(2);
      }
      var percent_color = total == 0 || metric == 'Bounce Rate' ? "gawd_red" : "gawd_green";
      jQuery('#_sum_' + chart_id).remove();
      var sumBox = "<div class='_sum_box' id='_sum_" + chart_id + "'>";
      var metric_title = metric == 'Percent New Sessions' ? '% New Sessions' : metric;
      sumBox += "<div class='box_metric'>" + metric_title + "</div>"
      sumBox += "<div class='_box_left " + show_hide + "'>";
      sumBox += "<div class='box_title'>Total</div>";
      sumBox += "<div class='" + percent_color + "'>" + parseInt(total).toLocaleString() + "</div>";
      sumBox += "</div>";

      sumBox += "<div class='_box_right'>";
      sumBox += "<div class='box_title'>Average</div>";
      sumBox += "<div class='" + percent_color + "'>" + parseInt(avg).toLocaleString() + "</div>";
      sumBox += "</div>";
      sumBox += "<div class='clear'></div>";
      sumBox += "</div>";
      jQuery('#'+chart_id).after(sumBox);
    }
    result = result.chart_data;

    if(total > 0) {
        var chart = new gawd_charts_helper();
        chart.print_pie_chart(result, dimension,metric, chart_id);
    }else{
        jQuery('#'+chart_id).height('auto');
    }
  };
  args.error = function (data) {

  };
  jQuery.ajax(args);

}
function gawd_chart_type() {
    if(typeof jQuery.cookie("collapsed") != 'undefined' && jQuery.cookie("collapsed") == 1){
      jQuery('.gawd_menu_coteiner').hide();
      jQuery('.gawd_menu_coteiner_collapse').show();
      jQuery('#gawd_right_conteiner').width('93%');
    }
    else{
      jQuery('.gawd_menu_coteiner').show();
      jQuery('.gawd_menu_coteiner_collapse').hide();
      jQuery('#gawd_right_conteiner').width('73%');
      jQuery('#compare_time_conteiner').css('width','31%');
      jQuery('#compare_time_conteiner').css('right','4%');
      jQuery('#compare_datepicker_wraper').width('98.5%');
    }
    var tab = jQuery('#gawd_body').data('gawd-tab');
    if(
        typeof tab !== 'undefined' &&
        ( tab === 'adsense')
    ){
        jQuery('#gawd_right_conteiner .filter_conteiner').hide();
        return;
    }
    if (jQuery("#gawd_chart_type").val() == 'pie') {
        gawd_pie_chart();
    } else {
      var current_tab_name = get_tab_name();
      if(current_tab_name === "goals" && jQuery("#gawd_metric").val()===null){
        var args = gawd_custom_ajax_args();
        args.type = 'POST';
        args.async = true;
        args.data.gawd_action = "gawd_get_management_goals";
        args.data.gawd_data = {

        };
        args.beforeSend = function() {

        };
        args.success = function (data) {
          data = JSON.parse(data.data.gawd_goals_data);
          for(i in data){
            jQuery("#gawd_metric").append(      "<option class='gawd_metric_option' value='"+data[i]["name"]+"'>"+data[i]["name"]+"(Goal"+data[i]["id"]+" Completions</option>");
            jQuery("#gawd_metric_compare").append(      "<option class='gawd_metric_option' value='"+data[i]["name"]+"'>"+data[i]["name"]+"(Goal"+data[i]["id"]+" Completions</option>");
          }
          gawd_draw_analytics();
        }
        args.error = function (data) {};
        jQuery.ajax(args);
      }
      else{
        gawd_draw_analytics();
      }

    }
    var _end_date = (Date.today().add(-1).days()).toString("yyyy-MM-dd");
    var start_date_7 = (Date.today().add(-1).days()).add(-7).days().toString("yyyy-MM-dd");
    var start_end_date = typeof jQuery('#gawd_start_end_date').val() != 'undefined' ? jQuery('#gawd_start_end_date').val() : start_date_7 + '/-/' + _end_date;
    var start_end_date = start_end_date.split('/-/');
    var start_date = start_end_date[0];
    var end_date = start_end_date[1];
    var diff = ((Date.parse(end_date).getTime() - Date.parse(start_date).getTime()) / 3600 / 24 / 1000)+1;
    var period_compare_start_date = Date.parse(start_date).add(-diff).days().toString("yyyy-MM-dd");
    var year_compare_start_date = Date.parse(start_date).add(-1).years().toString("yyyy-MM-dd");
    var year_compare_end_date = Date.parse(end_date).add(-1).years().toString("yyyy-MM-dd");

    var compare_daterange = jQuery('#compare_datepicker_wraper').daterangepicker({
        'ranges': {
            'Previous period': [moment(period_compare_start_date), moment(start_date).subtract(1, 'days')],
            'Previous year': [moment(year_compare_start_date), moment(year_compare_end_date)]
        },
        "startDate": moment(period_compare_start_date),
        "endDate": moment(start_date).subtract(1, 'days'),
        "maxDate": moment(),
        "alwaysShowCalendars": true,
        "opens": "left",
        "applyClass": 'gawd_compare_apply',
        "cancelClass": "gawd_compare_cancel"
    }, gawd_datepicker_compare);

    if(typeof compare_daterange.data('daterangepicker') != "undefined"){
        compare_daterange.data('daterangepicker').container.addClass('gawd_compare_daterange');
    }

    jQuery(document).mouseup(function (e) {



        if (!jQuery('.gawd_compare_apply').is(e.target) && ( jQuery('.gawd_compare_cancel').is(e.target))) {
        jQuery('#gawd_metric_compare').attr('disabled',false);
        jQuery('#gawd_metric_compare').removeClass('gawd_disabled');
      }
    });
    jQuery('.cancelBtn').on('click', function () {
      _data_compare = [];
      data_of_compared = [];
      jQuery('#compare_datepicker_wraper').css('background-color','#AFAFAF');
      gawd_draw_analytics();
    });
    jQuery('.gawd_compare_apply').on('click', function () {
      if(jQuery('.ranges li.active').text() == 'Previous period' || jQuery('.ranges li.active').text() == 'Custom RangePrevious period' || jQuery('.ranges li.active').text() == 'Previous periodLast 30 Days' || jQuery('.ranges li.active').text() == 'Last 30 DaysPrevious period' || jQuery('.ranges li.active').text() == 'Previous periodLast 7 Days' || jQuery('.ranges li.active').text() == 'Last 7 DaysPrevious period' || jQuery('.ranges li.active').text() == 'Previous periodLast Week' || jQuery('.ranges li.active').text() == 'Last WeekPrevious period' || jQuery('.ranges li.active').text() == 'Previous periodThis Month' || jQuery('.ranges li.active').text() == 'This MonthPrevious period' || jQuery('.ranges li.active').text() == 'Previous periodLast Month' || jQuery('.ranges li.active').text() == 'Last MonthPrevious period' || jQuery('.ranges li.active').text() == 'Previous periodYesterday' || jQuery('.ranges li.active').text() == 'YesterdayPrevious period' || jQuery('.ranges li.active').text() == 'TodayPrevious period' || jQuery('.ranges li.active').text() == 'Previous periodToday'){

        gawd_datepicker_compare(moment(period_compare_start_date), moment(start_date).subtract(1, 'days'));
      }
      //gawd_compare();
    })
}
function gawd_datepicker_compare(start, end) {
    jQuery('#compare_datepicker_wraper span').html(start.format('Y-MM-DD') + ' - ' + end.format('Y-MM-DD'));
    jQuery('#gawd_start_end_date_compare').val(start.format('Y-MM-DD') + '/-/' + end.format('Y-MM-DD'));
    gawd_compare();
}
function gawd_chart_type_post_page(uri, divID) {
    if (jQuery("#gawd_chart_type_post_page").val() == 'pie') {
        //gawd_pie_chart_post_page(uri, divID);
    } else {
        post_page_stats(uri, divID);
    }
}

function post_page_stats(uri, divID) {
    jQuery(".opacity_div_compact").show();
    jQuery(".loading_div_compact").show();
    if (typeof divID == 'undefined') {
        divID = 'gawd_post_page_popup';
    }
    var chartType = 'line';
    var fillAlphas = 0;

    if (jQuery("#gawd_chart_type_post_page").val() == 'line') {
        chartType = 'line';
        fillAlphas = 0;
    } else if (jQuery("#gawd_chart_type_post_page").val() == 'column') {
        chartType = 'column';
        fillAlphas = 1;
    }
    var metric = typeof jQuery("#gawd_metric_post_page").val() != 'undefined' ? jQuery("#gawd_metric_post_page").val() : (typeof jQuery("#gawd_metric_post_page_popup").val() != 'undefined' ? jQuery("#gawd_metric_post_page_popup").val() : 'sessions');
    var dimension = 'date';
    var date_30 = gawd_admin.date_30;
    var date_7 = gawd_admin.date_7;
    var date_yesterday = gawd_admin.date_yesterday;
    var date_today = gawd_admin.date_today;
    var date_this_month = gawd_admin.date_this_month;
    var date_last_month = gawd_admin.date_last_month;
    var date_last_week = gawd_admin.date_last_week;
    var _end_date = (Date.today().add(-1).days()).toString("yyyy-MM-dd");
    var start_date_7 = (Date.today().add(-1).days()).add(-7).days().toString("yyyy-MM-dd");
    var start_end_date = start_date_7 + '/-/' + _end_date;
    if (typeof jQuery('#gawd_start_end_date').val() != 'undefined') {
        start_end_date = jQuery('#gawd_start_end_date').val();
    }
    else {
        if (typeof jQuery('#gawd_post_page_popup_date').val() != 'undefined') {
            start_end_date = jQuery('#gawd_post_page_popup_date').val();
        }
    }
    var start_end_date = start_end_date.split('/-/');
    var start_date = start_end_date[0];
    var end_date = start_end_date[1];
    var timezone = -(new Date().getTimezoneOffset() / 60);
    if (divID == 'gawd_post_page_popup') {
        var chart_div = '<div id="opacity_div"></div><div id="loading_div" style="display:none; text-align: center; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999;"><img src="' + gawd_admin.gawd_plugin_url + '/assets/ajax_loader.gif"  style="position: absolute;top: calc(50% - 27px);left: calc(50% - 27px);width: 54px;height: 54px;"></div><div class="page_chart_div"><div class="close_button_cont"><button class="gawd_btn">X</button>';
        chart_div += '<select name="gawd_post_page_popup_date" id="gawd_post_page_popup_date">';
        chart_div += '<option value="' + date_7 + '">Last 7 Days</option><option value="' + date_30 + '">Last 30 Days</option>';
        chart_div += '<option value="' + date_last_month + '">Last month</option><option value="' + date_last_week + '">Last week</option>';
        chart_div += '<option value="' + date_this_month + '">This month</option><option value="' + date_yesterday + '">Yesterday</option>';
        chart_div += '<option value="' + date_today + '">Today</option>';
        chart_div += '</select>';
        chart_div += '<select name="gawd_metric_post_page_popup" id="gawd_metric_post_page_popup" >';
        chart_div += '<option value="sessions">Sessions</option><option value="users"  >Users</option><option value="bounceRate"  >Bounce Rate</option><option value="pageviews">Pageviews</option><option value="percentNewSessions">New Sessions</option><option value="avgSessionDuration">Session Duration</option><option value="pageviewsPerSession">Pages/Session</option>';
        chart_div += '</select>';
        chart_div += '<select name="gawd_chart_type_post_page" id="gawd_chart_type_post_page" class="gawd_draw_analytics">';
        chart_div += '<option value="line">Line Chart</option><option value="column">Column Chart</option>';
        chart_div += '</select>';
        chart_div += '<div id="gawd_post_page_popup"></div></div></div>';
        jQuery(".page_chart_div").remove();
        jQuery('#opacity_div').remove();

        jQuery("body").append(chart_div);
        jQuery("#gawd_metric_post_page_popup").val(metric);
        jQuery("#gawd_chart_type_post_page").val(chartType);
        jQuery("#gawd_post_page_popup_date").val(start_date + '/-/' + end_date);
        jQuery("#loading_div").show();
        jQuery("#opacity_div").show();
        jQuery('#gawd_post_page_popup').height('400');
        jQuery('#gawd_metric_post_page_popup, #gawd_post_page_popup_date, #gawd_chart_type_post_page').on('change', function () {
            gawd_chart_type_post_page(uri, 'gawd_post_page_popup');
        })
    }
    jQuery("#gawd_post_page_meta").empty();
    jQuery('#gawd_post_page_meta').height('300');
    console.log("ajax10");
    var args = gawd_custom_ajax_args();
    args.type = 'POST';
    args.async = true;
    args.data.gawd_action = "gawd_show_post_page_data";
    args.data.gawd_data = {
        "metric": metric,
        "start_date": start_date,
        "end_date": end_date,
        "dimension": dimension,
        "timezone": timezone,
        "security": gawd_admin.ajaxnonce,
        "filter": uri,
        "chart": 'line',
    };
    args.beforeSend = function () {

    };
    args.success = function (data) {
        var original_data = data;
        data = JSON.parse(data.data.gawd_page_post_data);
        data = data.chart_data ? data.chart_data : data;

        jQuery(".opacity_div_compact").hide();
        jQuery(".loading_div_compact").hide();

        if (divID == 'gawd_post_page_popup') {
            jQuery("#loading_div").hide();
            jQuery(".gawd_btn").show();
            jQuery('#opacity_div, .gawd_btn').on('click', function () {
                jQuery('#opacity_div').remove();
                jQuery(".gawd_btn").remove();
                jQuery(".page_chart_div").remove();
                jQuery("#loading_div").remove();
            })
        }

        if (original_data.success === false) {
            gawd_add_notice(original_data, divID);
            return;
        }

        if (data.length === 0) {
            return;
        }


        var x_key = 'date';
        var y_key = metric;

        var chart = new gawd_charts_helper();
        chart.print_posts_chart(data, x_key, y_key, chartType, divID);

    };
    args.error = function (data) {
        jQuery(".opacity_div_compact").hide();
        jQuery(".loading_div_compact").hide();
    };
    jQuery.ajax(args);

}
function show_hide(obj) {
    jQuery('.gawd_resp_li').hide();
    obj.show();
    jQuery('.gawd_menu_coteiner').removeClass("gawd_open");
}

function gawd_add_notice(data, chart_id) {
    if (typeof chart_id === 'undefined') {
        chart_id = "#chartdiv";
    }else{
        if(chart_id.charAt(0) !== '#'){
            chart_id = '#' + chart_id;
        }
    }

    var notice = '';
    if (typeof data.error === 'undefined') {
        jQuery('#chartdiv').find('.gawd_chart_error_msg').remove();
        return notice;
    }

    if (
        data.error.code === "show_data_error" &&
        data.error.msg === "Selected dimensions and metrics cannot be queried together"
    ) {
        notice = '<div class="gawd_chart_error_msg">' + data.error.msg + '</div>';
    } else if (
        data.error.code === "gawd_no_property" ||
        data.error.code === 'show_data_error' ||
        data.error.code === 'show_data_compact_error' ||
        data.error.code === 'real_time_error' ||
        data.error.code === 'gawd_page_post_error'
    ) {
        notice = '<div class="gawd_chart_error_msg">Can not get data for this property, maybe the property is deleted or changed. <a href="admin.php?page=gawd_settings">Refresh user info.</a></div>';
    }else{
        notice = '<div class="gawd_chart_error_msg">Some unknown error happened when getting report data. <a href="admin.php?page=gawd_settings">Refresh user info.</a></div>';
    }

    if (notice !== '') {
        jQuery(chart_id).find('.gawd_chart_error_msg').remove();
        jQuery(chart_id).html("");
        jQuery(chart_id).append(notice);
    }

    return notice;
}

jQuery(document).ready(function () {


  jQuery('#gawd_email_time_input').timepicker({ 'scrollDefault': 'now','timeFormat': 'H:i' });

   jQuery('#gawd_page_title').text(jQuery('.gawd_active_li').text());

  if(jQuery('.gawd_menu_coteiner_collapse .gawd_active_li_text').closest('span').length > 0){
    jQuery('.gawd_menu_coteiner_collapse .gawd_active_li_text').closest('span').addClass('gawd_active_li');
    //jQuery('.gawd_menu_coteiner_collapse .collapse_ul .gawd_menu_item').removeClass('gawd_active_li');
  }
  jQuery('.collapse_ul').on('mouseover',function(){
     jQuery(this).show();
   } ,
   function(){
    jQuery(this).hide()
   });

  jQuery('.gawd_collapse').on('click',function(){
    //jQuery('.gawd_menu_coteiner_collapse').toggle();
    //jQuery('.gawd_menu_coteiner').toggle();
    //jQuery('#gawd_right_conteiner').width('94%');
    jQuery.cookie("collapsed", "1");
    gawd_chart_type();
  })
  jQuery('.gawd_collapsed').on('click',function(){
    // jQuery('.gawd_menu_coteiner_collapse').toggle();
    // jQuery('#gawd_right_conteiner').width('73%');
    // jQuery('.gawd_menu_coteiner').toggle();
    jQuery.cookie("collapsed", "0");
    gawd_chart_type();
  })

  jQuery('#gawd_got_it').on('click',function(){
    remove_zoom_message();
  })
  jQuery('#compare_datepicker_wraper').on('click',function(){
    jQuery('#gawd_metric_compare').attr('disabled',true);
    jQuery('#gawd_metric_compare').addClass('gawd_disabled');
  })
  // jQuery('.gawd_add_prop').width('51.2%');
  jQuery('.load_tooltip , .gawd_description').mouseenter(function(){
    if(gawd_admin.enableHoverTooltip == 'on'){
      jQuery(this).attr('title',jQuery(this).data('hint'));
    }
  })
  jQuery('#gawd_dimensions_form, #gawd_custom_report_form').tooltip();
  jQuery('#gawd_goal_form').tooltip();
    jQuery(window).resize(function () {
    if(typeof jQuery.cookie("collapsed") == 'undefined' || jQuery.cookie("collapsed") == 0){
      jQuery('.gawd_menu_coteiner').show();
    }
    })

    jQuery('#gawd_metric_compare').on('change', function () {
        if (jQuery(this).val() != '0') {
            jQuery('#metric_compare img').show();
        } else {
          jQuery('#_sum_comp_chartdiv').remove();
          jQuery('#metric_compare img').hide();
        }
    })
    jQuery('#metric_compare img').on('click', function () {
        jQuery('#gawd_metric_compare option:first-child').attr('selected', 'selected');
        jQuery(this).hide();
        jQuery('#_sum_comp_chartdiv').remove();
        gawd_chart_type();
    })

    jQuery('.gawd_settings_menu_coteiner').show();
    jQuery('.resp_menu').on('click', function () {
        jQuery('.gawd_resp_li').show();
        if (jQuery('.gawd_menu_coteiner').hasClass("gawd_open")) {
            jQuery('.gawd_menu_coteiner').removeClass("gawd_open");
            var elId = window.location.hash ? window.location.hash.substring(0,window.location.hash.length-4) : "#gawd_authenicate";
            show_hide(jQuery(elId));
            //jQuery('.gawd_menu_coteiner').hide();
        }
        else {
            jQuery('.gawd_menu_coteiner').addClass("gawd_open");
            jQuery('.gawd_menu_coteiner').show();
        }
    })
    jQuery('.gawd_resp_li').on('click', function () {
        show_hide(jQuery(this));
    })
    jQuery('.resp_metrics_menu').on('click', function () {
        jQuery('.float_conteiner').toggle();
        jQuery('.filter_conteiner').toggle();
    })

    jQuery('.gawd_page_post_stats').on('click', function () {
      var page_id = jQuery(this).parent().parent().find('th').find('input').val();
      var gawd_page_permalink = jQuery(this).data("permalink");
      gawd_chart_type_post_page(gawd_page_permalink);
    });

    jQuery('.gawd_settings_wrapper').tooltip();
    if (jQuery('.gawd_menu_coteiner').length > 0) {
      jQuery('.settings_row, .gawd_checkbox, .gawd_goal_row, .gawd_own_wrap').tooltip();
      if(gawd_admin.enableHoverTooltip == 'on'){
        jQuery('.gawd_menu_coteiner, #gawd_right_conteiner').tooltip({position: {
                        my: "center",
                        at: "right+200",
                        track: false,
                        using: function(position, feedback) {
                            jQuery(this).css(position);
                        }
                    }
                });
      }
      onDashboardLoad();
    }
    if (jQuery('.gawd_chart_conteiner').length > 0) {
        datepicker_js();
    }
    jQuery('#country_filter_reset').on('click', function () {
        gawd_chart_type();
    })
    jQuery('.gawd_menu_li_sub').on('click', function () {
        if (jQuery('#' + jQuery(this).closest('li').attr('id') + ' ul').is(":visible") != true) {
            jQuery('#' + jQuery(this).closest('li').attr('id') + ' ul').closest('li').find('.gawd_menu_li_sub_arrow').css("background-position", "83% 82.5%");
        } else {
            jQuery('#' + jQuery(this).closest('li').attr('id') + ' ul').closest('li').find('.gawd_menu_li_sub_arrow').css("background-position", "87% 85.6%");
        }
        jQuery(jQuery('#' + jQuery(this).closest('li').attr('id') + ' ul')).toggle(500);
    });
    if (jQuery('.gawd_active_li')) {
        jQuery('.gawd_active_li').closest('ul').show();
    }
    jQuery('.gawd_menu_li ul').each(function () {
        if (jQuery(this).is(':visible') == true) {
            jQuery(this).closest('li').find('.gawd_menu_li_sub_arrow').css("background-position", "83% 82.5%");
        } else {
            jQuery(this).closest('li').find('.gawd_menu_li_sub_arrow').css("background-position", "87% 85.6%");
        }
    });

    jQuery('.gawd_list_item').on('click', function () {
        jQuery('.gawd_list_item a').css('color', '#fff');
        jQuery('#gawd_hour').css({'background-color': '#FB8583', 'border-color': '#FB8583'});
        jQuery('#gawd_day').css({'background-color': '#7DB5D8', 'border-color': '#7DB5D8'});
        jQuery('#gawd_week').css({'background-color': '#F0B358', 'border-color': '#F0B358'});
        jQuery('#gawd_month').css({'background-color': '#9DCFAC', 'border-color': '#9DCFAC'});
        jQuery(this).find('a').css('color', jQuery(this).css('border-top-color'));
        jQuery(this).css('background-color', '#fff');

        jQuery('#gawd_filter_val').val(jQuery(this).find(".gawd_filter_item").attr('data-type'));
        gawd_chart_type();
        return false;
    })
    //google.charts.load('current', {'packages': ['table']});

    jQuery(".gawd_draw_analytics").on("change", function () {
      if(jQuery(this).closest('#metric_conteiner').length > 0){
        var option = jQuery(this).val();
        var metric_id = "gawd_metric";
        var metric_compare_id = "gawd_metric_compare";
        var select_id = jQuery(this).attr("id");
        if(select_id == metric_id){
            jQuery("#"+metric_compare_id).find('option').show();
            jQuery("#"+metric_compare_id).find("option[value='"+option+"']").hide();
        }
        else if(select_id == metric_compare_id){
            jQuery("#"+metric_id).find('option').show();
            jQuery("#"+metric_id).find("option[value='"+option+"']").hide();
        }
      }
        gawd_chart_type();
    })

    if (jQuery('#chart_widget').length > 0) {
        gawd_draw_analytics_widget();
    }
    gawd_tracking_display_manage();
});
function default_start() {
    if (gawd_admin.default_date != 'undefined') {

        switch (gawd_admin.default_date) {
            case 'today':
                date = moment();
                break;
            case 'yesterday':
                date = moment().subtract(1, 'days');
                break;
            case 'last_7_days':
                date = moment().subtract(7, 'days');
                break;
            case 'last_week':
                date = moment().subtract(1, 'week').startOf('week');
                break;
            case 'this_month':
                date = moment().startOf('month');
                break;
            case 'last_30days':
                date = moment().subtract(30, 'days');
                break;
            case 'last_month':
                date = moment().subtract(1, 'month').startOf('month');
                break;
            default:
                date = moment().subtract(7, 'days');
                break;
        }
        return date;
    } else {
        return moment().subtract(7, 'days');
    }
}
function default_end() {
    if (gawd_admin.default_date != 'undefined') {
        switch (gawd_admin.default_date) {
            case 'today':
                date = moment();
                break;
            case 'yesterday':
                date = moment().subtract(1, 'days');
                break;
            case 'last_7_days':
                date = moment().subtract(1, 'days');
                break;
            case 'last_week':
                date = moment().subtract(1, 'week').endOf('week');
                break;
            case 'this_month':
                date = moment().subtract(1, 'days') <= moment().startOf('month') ? moment().startOf('month') : moment().subtract(1, 'days');
                break;
            case 'last_30days':
                date = moment().subtract(1, 'days');
                break;
            case 'last_month':
                date = moment().subtract(1, 'month').endOf('month')
                break;
            default:
                date = moment().subtract(1, 'days');
                break;
        }
        return date;
    } else {
        return moment().subtract(1, 'days');
    }
}
function datepicker_js(opens,callback) {
  if(typeof opens == 'undefined'){
    opens = 'left';
  }
  if(typeof callback == 'undefined'){
    callback = 'gawd_datepicker_main';
  }
    window[callback](default_start(), default_end());
    var daterange = jQuery('#reportrange').daterangepicker({
        "ranges": {
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
        "opens": opens,
        "applyClass": 'gawd_main_apply'

    }, window[callback]);
    var _end_date = (Date.today().add(-1).days()).toString("yyyy-MM-dd");
    var start_date_7 = (Date.today().add(-1).days()).add(-7).days().toString("yyyy-MM-dd");
    var start_end_date = typeof jQuery('#gawd_start_end_date').val() != 'undefined' ? jQuery('#gawd_start_end_date').val() : start_date_7 + '/-/' + _end_date;
    var start_end_date = start_end_date.split('/-/');
    var start_date = start_end_date[0];
    var end_date = start_end_date[1];
    if(typeof  daterange.data('daterangepicker') != "undefined") {
        daterange.data('daterangepicker').container.addClass('gawd_daterange');
    }

}
function onDashboardLoad() {
    jQuery(".gawd_email_week_day").on('click', function () {
        jQuery('#gawd_email_week_day_hidden').val(jQuery(this).attr('data-atribute'));
        jQuery(".gawd_email_week_day").removeClass('gawd_selected_day');
        jQuery(this).addClass('gawd_selected_day');
    })
    jQuery("#gawd_export_buttons, .gawd_exports, #ui-id-6").on({
        mouseover: function () {
            jQuery(".gawd_exports").show();
        },
        mouseleave: function () {
            jQuery(".gawd_exports").hide();
        }
    });
    jQuery("#gawd_email_button").on('click', function () {
        jQuery('.gawd_email_body').show();
        if(gawd_admin.enableHoverTooltip == 'on'){
          jQuery('.gawd_email_body').tooltip();
        }
        jQuery('.email_message_cont').html('');
        jQuery('.email_message_cont').hide();

        jQuery('#gawd_email_to').val('');
        jQuery('#gawd_attachment_type').val('csv');
        jQuery('#gawd_email_period').find('select').val('once');
        jQuery('#gawd_email_body').val('');

        jQuery('.gawd_email_row').find('.gawd_email_week_day_div').hide();
        jQuery('.gawd_email_row').find('.gawd_email_month_day_div').hide();
        jQuery('.gawd_email_time_row').hide();




        var _end_date = (Date.today().add(-1).days()).toString("yyyy-MM-dd");
        var start_date_7 = (Date.today().add(-1).days()).add(-7).days().toString("yyyy-MM-dd");
        var start_end_date = typeof jQuery('#gawd_start_end_date').val() != 'undefined' ? jQuery('#gawd_start_end_date').val() : start_date_7 + '/-/' + _end_date;

        var start_end_date = start_end_date.split('/-/');
        var start_date = start_end_date[0];
        var end_date = start_end_date[1];
        jQuery("#gawd_start_date").val(start_date);
        jQuery("#gawd_end_date").val(end_date);
        if(_data_compare != ''){
          jQuery("#gawd_email_period select option").hide();
          jQuery("#gawd_email_period select option:first-child").show();
        }
        else{
          jQuery("#gawd_email_period select option").show();
        }
        jQuery(".gawd_email_popup").fadeIn('fast');
        jQuery(".gawd_email_popup_overlay").fadeIn('fast');
        jQuery(".gawd_btn").fadeIn('fast');
        var _tab_name = jQuery('#gawd_tab').val() == 'date' ? 'Audience' : jQuery('#gawd_tab').val();
        jQuery(".gawd_email_subject").val('Google Analytics: ' + _tab_name.replace(/([A-Z])/g, " $1").trim().charAt(0).toUpperCase() + _tab_name.replace(/([A-Z])/g, " $1").trim().slice(1) + ' (' + start_date + ' - ' + end_date + ')');
    })
    jQuery(".gawd_email_popup_overlay, .gawd_btn").on('click', function () {
        jQuery(".gawd_email_popup").fadeOut('fast');
        jQuery(".gawd_email_popup_overlay").fadeOut('fast');
        jQuery(".gawd_btn").fadeOut('fast');
    })
    jQuery("#gawd_email_period").on('change', function () {
        if (jQuery("#gawd_email_period :selected").val() == "gawd_weekly") {
            jQuery(".gawd_email_week_day_div").show();
        }
        else {
            jQuery(".gawd_email_week_day_div").hide();
        }
        if (jQuery("#gawd_email_period :selected").val() == "gawd_monthly") {
            jQuery(".gawd_email_month_day_div").show();
        }
        else {
            jQuery(".gawd_email_month_day_div").hide();
        }
        if (jQuery("#gawd_email_period :selected").val() != "once") {
            jQuery(".gawd_email_time_row").show();
        }
        else {
            jQuery(".gawd_email_time_row").hide();
        }
    })
    jQuery("#email_submit").on('click', function () {
        gawd_send_email_info();
        //email_popup();
    })
}

function gawd_send_email_info() {

    var email_info = {
        'period': jQuery("#gawd_email_period :selected").val(),
        'email_from': jQuery("#gawd_email_from").val(),
        'email_to': jQuery("#gawd_email_to").val(),
        'subject': jQuery(".gawd_email_subject").val(),
        'content': jQuery("#gawd_email_body").val(),
        'week_day': jQuery("#gawd_email_week_day_hidden").val(),
        'month_day': jQuery("#gawd_email_month_day_select").val(),
        'email_time': jQuery("#gawd_email_time_input").val(),
        'view_id': jQuery("#gawd_id option:selected").val(),
    };

    var empty_subject = false;
    var is_valid_email = true;

    jQuery("#gawd_email_to").removeClass('gawd_invalid')
    jQuery("#gawd_email_subject").removeClass('gawd_invalid')
    var emails = email_info.email_to.split(',');
    var invalid_emalis = [];
    for (var j = 0; j < emails.length; j++) {
        var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        if (re.test(emails[j]) == false) {
            invalid_emalis.push(emails[j])
        }
    }
    if (invalid_emalis.length > 0) {
        is_valid_email = false;
        jQuery("#gawd_email_to").addClass('gawd_invalid');
    }
    if (email_info.subject == '') {
        empty_subject = true;
        jQuery("#gawd_email_subject").addClass('gawd_email_subject');
    }


    if (!(is_valid_email == true && empty_subject == false)) {
        return;
    }

    var data = gawd_get_report_file_data();
    data['action'] = 'gawd_send_email';
    data['email_info'] = email_info;

    jQuery('#loading_div').css('z-index','999999');
    jQuery('#loading_div').show();

    jQuery.post(gawd_admin.ajaxurl, data).done(function (response) {
        response = JSON.parse(response);

        jQuery('.gawd_email_body').hide();
        jQuery('#loading_div').css('z-index', '99999');
        jQuery('#loading_div').hide();
        jQuery('.email_message_cont').show();

        jQuery('.email_message_cont').html(response.data.msg);


    });
}

function email_popup() {
    jQuery("#gawd_email_metric").val(jQuery("#gawd_metric").val());
    var metric_compare_email = jQuery("#gawd_metric_compare").val();
    if(_data_compare != ''){
      metric_compare_email = jQuery("#gawd_metric").val() + ' compare';
    }
    var dimension = jQuery('#gawd_tab').val();
    if (dimension == 'custom') {
      // alert("bbbbbbbbbbbbb");
        var custom = jQuery("#gawd_custom_option").val();
        if (typeof jQuery("#gawd_custom_option").val() != 'undefined') {
            dimension = custom.substring(3);
        }
    }
    var url = '';
    if(dimension != "pagePath" && dimension != 'landingPagePath' && dimension != 'daysToTransaction'){
        jQuery('svg').find('desc').remove();
        var svg = document.getElementsByTagName('svg')[0];
        var canvas = document.getElementById("canvass");
        draw_canvas(svg, canvas);
        url = canvas.toDataURL();
    }
    if (typeof url == 'undefined') {
        url = '';
    }
    var gawd_email_period = jQuery("#gawd_email_period :selected").val();
    var filter_type = jQuery("#gawd_filter_val").val();
    var gawd_email_from = jQuery("#gawd_email_from").val();
    var gawd_email_to = jQuery("#gawd_email_to").val();
    var gawd_email_subject = jQuery(".gawd_email_subject").val();
    var start_date = jQuery("#gawd_start_date").val();
    var end_date = jQuery("#gawd_end_date").val();
    var metric = jQuery("#gawd_email_metric").val();
    var metric_compare = metric_compare_email;
    var export_type = jQuery("#gawd_attachment_type").val();
    var report_type = jQuery("#report_type").val();
    var gawd_email_body = jQuery("#gawd_email_body").val();
    var week_day = jQuery("#gawd_email_week_day_hidden").val();
    var month_day = jQuery("#gawd_email_month_day_select").val();
    var email_time = jQuery("#gawd_email_time_input").val();
    var view_id = jQuery("#gawd_id option:selected").closest('optgroup').attr('label');
    var empty_subject = false;
    var is_valid_email = true;
    jQuery("#gawd_email_to").removeClass('gawd_invalid')
    jQuery("#gawd_email_subject").removeClass('gawd_invalid')
    var emails = gawd_email_to.split(',');
    var invalid_emalis = [];
    for (var j = 0; j < emails.length; j++) {
        var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        if (re.test(emails[j]) == false) {
            invalid_emalis.push(emails[j])
        }
    }
    if (invalid_emalis.length > 0) {
        is_valid_email = false;
        jQuery("#gawd_email_to").addClass('gawd_invalid');
    }
    if (gawd_email_subject == '') {
        empty_subject = true;
        jQuery("#gawd_email_subject").addClass('gawd_email_subject');
    }

        var order = get_order_data_for_export();
    //jQuery("#gawd_email_form").submit();
    if (is_valid_email == true && empty_subject == false) {
        jQuery('#loading_div').css('z-index','999999');
        jQuery('#loading_div').show();
      console.log("ajax11");
        jQuery.post(gawd_admin.ajaxurl, {
            action: 'gawd_export',
            _data_compare: _data_compare,
            second_data_sum: jQuery('#second_data_sum').val(),
            first_data_sum: jQuery('#first_data_sum').val(),
            second_end_date: d_end_date,
            second_start_date: d_start_date,
            second_data: jQuery('#second_data').val(),
            first_data: jQuery('#first_data').val(),
            gawd_dimension: dimension,
            img:url,
            gawd_metric: metric,
            gawd_metric_compare: metric_compare,
            gawd_start_date: start_date,
            gawd_end_date: end_date,
            filter_type: filter_type,
            gawd_email_period: gawd_email_period,
            gawd_email_month_day: month_day,
            gawd_email_week_day: week_day,
            email_time: email_time,
            gawd_email_to: gawd_email_to,
            gawd_email_from: gawd_email_from,
            gawd_email_subject: gawd_email_subject,
            gawd_email_body: gawd_email_body,
            view_id: view_id,
            report_type: report_type,
            export_type: export_type,
            tab_name: d_tab_name[1],
            sort: order.order,
            sort_by: order.order_by,
            security: gawd_admin.ajaxnonce,
        }).done(function (data) {
            result = JSON.parse(data);
            if(result.status=='success'){
              jQuery('.gawd_email_body').hide();
              jQuery('#loading_div').css('z-index','99999');
              jQuery('#loading_div').hide();
              jQuery('.email_message_cont').show();
              jQuery('.email_message_cont').html(result.msg);
            }

        })
    }
}

function numFormat( cellvalue, options, rowObject ){
  if(typeof options != 'undefined' ){
    if(options.colModel.index != 'Pageviews Per Session' && options.colModel.index != 'Pageviews Per Session compare'){
      if(options.colModel.index.indexOf('compare') > -1){
        //options.colModel.index =  options.colModel.index.replace(' compare','');
        var percentage_of_total = isNaN(cellvalue/data_of_compared.data_sum[options.colModel.index]) ? 0 : ((cellvalue/data_of_compared.data_sum[options.colModel.index])*100).toFixed(2);
      }
      else{
        if(typeof _data != 'undefined'){
          var percentage_of_total = isNaN(cellvalue/_data.data_sum[options.colModel.index]) ? 0 : ((cellvalue/_data.data_sum[options.colModel.index])*100).toFixed(2);
        }
      }
      var sum_percent = typeof percentage_of_total != 'undefined' ? ' <span class="row_percent">(' + percentage_of_total +'%)</span>' : '';
      return typeof cellvalue != 'undefined' ? cellvalue.toLocaleString() + sum_percent : '' ;
    }
  }
  return typeof cellvalue != 'undefined' ? cellvalue.toLocaleString() : '' ;
}
function timeFormat( cellvalue, options, rowObject ){
  if(typeof cellvalue == 'string'){
    if(cellvalue.indexOf(':') > -1){
      return cellvalue;
    }
  }
  var hours = (Math.floor(cellvalue / 3600)).toString().length < 2 ?  '0' + Math.floor(cellvalue / 3600) : Math.floor(cellvalue / 3600);
  var mins = (Math.floor(cellvalue / 60 % 60)).toString().length < 2 ? '0' + Math.floor(cellvalue / 60 % 60) : Math.floor(cellvalue / 60 % 60);
  var secs = (Math.ceil(cellvalue % 60)).toString().length < 2 ? '0' + Math.ceil(cellvalue % 60) : Math.ceil(cellvalue % 60);
  metric_value = hours + ':' + mins + ':' + secs;
    return metric_value;
}
function percentFormat( cellvalue, options, rowObject ){
    return parseFloat(cellvalue).toFixed(2) + "%";
}
function dollarFormat( cellvalue, options, rowObject ){
  if(typeof options != 'undefined' ){
      var percentage_of_total = isNaN(cellvalue/_data.data_sum[options.colModel.index]) ? 0 : ((cellvalue/_data.data_sum[options.colModel.index])*100).toFixed(2);
     return '$<span class="row_percent">' + parseFloat(cellvalue).toFixed(2) + ' (' + percentage_of_total +'%)</span>';
  }
  return "$" + parseFloat(cellvalue).toFixed(2);
}
function secFormat( cellvalue, options, rowObject ){
    if(cellvalue == '1801'){
      return cellvalue.toLocaleString() + "+ seconds";
    }
    return cellvalue.toLocaleString() + " seconds";
}
function sec_to_normal(data){
  var hours = (Math.floor(data / 3600)).toString().length < 2 ?  '0' + Math.floor(data / 3600) : Math.floor(data / 3600);
  var mins = (Math.floor(data / 60 % 60)).toString().length < 2 ? '0' + Math.floor(data / 60 % 60) : Math.floor(data / 60 % 60);
  var secs = (Math.ceil(data % 60)).toString().length < 2 ? '0' + Math.ceil(data % 60) : Math.ceil(data % 60);
  return data = hours + ':' + mins + ':' + secs;
}
var gawd_data_table = null;
function gawd_draw_table_pages_compare(_data, data, dimension, data_sum, _data_sum,start_date, end_date){
  _data = JSON.parse(_data);
  data = JSON.parse(data);
  var metrics = Object.keys(_data[0]);
  var all_pages = new Array();
  var max_length_data = _data.length >= data.length ? _data : data;
  var min_length_data = _data.length >= data.length ? data : _data;

  var  max_length = max_length_data.length;
  var  min_length = min_length_data.length;

  var min_paths = [];
  for(var i=0 ;i<min_length; i++){
    min_paths.push(min_length_data[i][metrics[1]]);
  }

  for(var i=0 ;i<max_length; i++){
    all_pages[max_length_data[i][metrics[1]]] = new Array();
    all_pages[max_length_data[i][metrics[1]]][0] = max_length_data[i];
    if(typeof min_length_data[i] == 'undefined' || min_paths.indexOf(max_length_data[i][metrics[1]]) == -1){
      all_pages[max_length_data[i][metrics[1]]][1] = 'no';
    }
    else{
      var key = i;
      for(var j=0; j<min_length; j++){
        if(min_length_data[j][metrics[1]] == max_length_data[i][metrics[1]]){
          key = j;
          break;
        }
      }
      all_pages[max_length_data[i][metrics[1]]][1] = min_length_data[key];
    }
  }

  var table = '';
    table += '<thead>';
    table += '<tr>';
    for(var j=0 ;j<metrics.length; j++){
      table += '<th>' + metrics[j] + '</th>';
    }
    table += '</tr>';
    table += '<tr>';
    for(var j=0 ;j<metrics.length; j++){

      if(metrics[j] == 'No' || metrics[j] == 'Page Path' || metrics[j] == 'Landing Page'){
        table += '<td></td>';
      }
      else{
        _data_sum[metrics[j]] = parseFloat(_data_sum[metrics[j]]);
        data_sum[metrics[j]] = parseFloat(data_sum[metrics[j]]);
        var arrow = '<img src=""/>';
        var percent = (((_data_sum[metrics[j]] - data_sum[metrics[j]])/data_sum[metrics[j]]) * 100).toFixed(2);
        if(isNaN(percent)){
          percent = 0;
        }
        else if(percent.indexOf('-') > -1){
          percent = percent.substr(1);
          arrow = '<img src="' + gawd_admin.gawd_plugin_url + '/assets/arrow-down.png"/>';
        }
        else{
          arrow = '<img src="' + gawd_admin.gawd_plugin_url + '/assets/arrow-up.png"/>';
        }
        percent = percent == 'Infinity' ? 100 : percent;
        table += '<td style="font-weight:bold; width: 9%;"><div>' + percent + '%' + arrow + '</div>';
        var data_sum_value = data_sum[metrics[j]].toLocaleString();
        var _data_sum_value = _data_sum[metrics[j]].toLocaleString();
        if(metrics[j] == 'Avg Time On Page' || metrics[j] == 'Avg Page Load Time' || metrics[j] == 'Avg Session Duration'){
          data_sum_value = sec_to_normal(parseFloat(data_sum_value));
          _data_sum_value = sec_to_normal(parseFloat(_data_sum_value));
        }
        if(metrics[j] == 'Bounce Rate' || metrics[j] == 'Exit Rate' || metrics[j] == 'Percent New Sessions' || metrics[j] == 'Ecommerce Conversion Rate'){
          _data_sum_value = parseFloat(_data_sum_value).toFixed(2) + '%';
          data_sum_value = parseFloat(data_sum_value).toFixed(2) + '%';
        }
        else if(metrics[j] == 'Page Value' || metrics[j] == 'Revenue'){
          _data_sum_value = '$' + parseFloat(_data_sum_value).toFixed(2);
          data_sum_value = '$' + parseFloat(data_sum_value).toFixed(2);
        }
        table += '<div>' + _data_sum_value + ' vs ' + data_sum_value + '</div>';
        table += '</td>';
      }
    }
    table += '</tr>';
    table += '</thead>';
    table += '<tbody class="gawd_datatable_hide_button">';

    Object.size = function(obj) {
        var size = 0, key;
        for (key in obj) {
            if (obj.hasOwnProperty(key)) size++;
        }
        return size;
    };
    var length = Object.size(all_pages);
    var keys = Object.keys(all_pages);
    for(var i=0 ;i<length; i++){
    table += '<tr>';
    table += '<td>' + parseInt(i+1) +'</td>';
    table += '<td>' + keys[i] +'</td>';
    table += '<td></td>';
    table += '<td></td>';
    table += '<td></td>';
    table += '<td></td>';
    table += '<td></td>';
    table += '<td></td>';
    table += '<td></td>';
    table += '<td></td>';
    table += '</tr>';

    table += '<tr>';
    for(var j = 0; j < metrics.length; j++){
      if(metrics[j] == 'No'){
        table += '<td ></td>';
      }
      else if(metrics[j] == 'Page Path' || metrics[j] == 'Landing Page'){
        table += '<td>' + Date.parse(d_start_date).toString('MMMM dd, yyyy') + ' - ' + Date.parse(d_end_date).toString('MMMM dd, yyyy') +'</td>';
      }
      else{
        if(all_pages[keys[i]][0] != 'no'){
          var line_value = all_pages[keys[i]][0][metrics[j]].toLocaleString();
          if(metrics[j] == 'Bounce Rate' || metrics[j] == 'Exit Rate' || metrics[j] == 'Percent New Sessions' || metrics[j] == 'Ecommerce Conversion Rate'){
            line_value = parseFloat(line_value).toFixed(2) + '%';
          }
          else if(metrics[j] == 'Page Value' || metrics[j] == 'Transaction Revenue' || metrics[j] == 'Revenue'){
            var percentage_of_total = isNaN(parseFloat(line_value)/_data_sum[metrics[j]]) ? 0 : ((parseFloat(line_value)/_data_sum[metrics[j]])*100).toFixed(2);
            line_value = parseFloat(line_value).toFixed(2) + '$ <span class="row_percent">(' + percentage_of_total +'%)</span>';
          }
          else if(metrics[j] == 'Avg Time On Page' || metrics[j] == 'Avg Page Load Time' || metrics[j] == 'Avg Session Duration'){
            line_value = line_value.indexOf(':') > -1 ? line_value : sec_to_normal(parseFloat(line_value));
          }
          else{
            var percentage_of_total = isNaN(parseFloat(line_value)/_data_sum[metrics[j]]) ? 0 : ((parseFloat(line_value)/_data_sum[metrics[j]])*100).toFixed(2);
            line_value = (line_value).toLocaleString() + ' <span class="row_percent">(' + percentage_of_total +'%)</span>';
          }
          table += '<td>' + line_value + '</td>';
        }
        else{
          table += '<td>0</td>';
        }
      }
    }
    table += '</tr>';
    table += '<tr>';
    for(var j = 0; j < metrics.length; j++){
      if(metrics[j] == 'No'){
        table += '<td></td>';
      }
      else if(metrics[j] == 'Page Path' || metrics[j] == 'Landing Page'){
        table += '<td>' + Date.parse(start_date).toString('MMMM dd, yyyy') + ' - ' + Date.parse(end_date).toString('MMMM dd, yyyy') +'</td>';
      }
      else{
        if(all_pages[keys[i]][1] != 'no'){
          var line_value = all_pages[keys[i]][1][metrics[j]].toLocaleString();
          if(metrics[j] == 'Bounce Rate' || metrics[j] == 'Exit Rate' || metrics[j] == 'Percent New Sessions' || metrics[j] == 'Ecommerce Conversion Rate'){
            line_value = parseFloat(line_value).toFixed(2) + '%';
          }
          else if(metrics[j] == 'Page Value' || metrics[j] == 'Transaction Revenue' || metrics[j] == 'Revenue'){
            var percentage_of_total = isNaN(parseFloat(line_value)/data_sum[metrics[j]]) ? 0 : ((parseFloat(line_value)/data_sum[metrics[j]])*100).toFixed(2);
            line_value = parseFloat(line_value).toFixed(2) + '$ <span class="row_percent">(' + percentage_of_total +'%)</span>';
          }
          else if(metrics[j] == 'Avg Time On Page' || metrics[j] == 'Avg Page Load Time' || metrics[j] == 'Avg Session Duration'){
            line_value = line_value.indexOf(':') > -1 ? line_value : sec_to_normal(parseFloat(line_value));
          }
          else{
            var percentage_of_total = isNaN(parseFloat(line_value)/data_sum[metrics[j]]) ? 0 : ((parseFloat(line_value)/data_sum[metrics[j]])*100).toFixed(2);
            line_value = (line_value).toLocaleString() + ' <span class="row_percent">(' + percentage_of_total +'%)</span>';
          }
          table += '<td>' + line_value + '</td>';
        }
        else{
          table += '<td>0</td>';
        }
      }
    }
    table += '</tr>';
    for(var j = 0; j < metrics.length; j++){
      if(metrics[j] == 'No'){
        table += '<td></td>';
      }
      else if(metrics[j] == 'Page Path' || metrics[j] == 'Landing Page'){
        table += '<td style="font-weight:bold;">% Change</td>';
      }
      else{
        if(all_pages[keys[i]][0] != 'no'){
          _single_data = all_pages[keys[i]][0][metrics[j]];
        }
        else{
          _single_data = 0;
        }
        if(all_pages[keys[i]][1] != 'no'){
          single_data = all_pages[keys[i]][1][metrics[j]];
        }
        else{
          single_data = 0;
        }
        if(metrics[j] == 'Avg Time On Page' || metrics[j] == 'Avg Page Load Time' || metrics[j] == 'Avg Session Duration'){

          _single_data = _single_data.toString().split(":");

          if(_single_data[0].indexOf('0') == 0){
            _single_data[0] = _single_data[0].substr(1);
            _single_data[0] = _single_data[0];
          }
          if(_single_data.length > 1){
            if(_single_data[1].indexOf('0') == 0){
            _single_data[1] = _single_data[1].substr(1);
            _single_data[1] = _single_data[1]*60;
            }
            if(_single_data[2].indexOf('0') == 0){
              _single_data[2] = _single_data[2].substr(1);
              _single_data[2] = _single_data[2];
            }
          }
          var a  = typeof _single_data[0] == 'undefined' ? 0 :  parseInt(_single_data[0]);
          var b =  typeof _single_data[1] == 'undefined' ? 0 :  parseInt(_single_data[1]);
          var c =  typeof _single_data[2] == 'undefined' ? 0 :  parseInt(_single_data[2]);
          _single_data = a + b + c;
          single_data = single_data.toString().split(":");
          if(single_data[0].indexOf('0') == 0){
            single_data[0] = single_data[0].substr(1);
            single_data[0] = single_data[0];
          }
          if(single_data.length > 1){
            if(single_data[1].indexOf('0') == 0){
              single_data[1] = single_data[1].substr(1);
              single_data[1] = single_data[1]*60;
            }
            if(single_data[2].indexOf('0') == 0){
              single_data[2] = single_data[2].substr(1);
              single_data[2] = single_data[2];
            }
          }
          var a  = typeof single_data[0] == 'undefined' ? 0 :  parseInt(single_data[0]);
          var b =  typeof single_data[1] == 'undefined' ? 0 :  parseInt(single_data[1]);
          var c =  typeof single_data[2] == 'undefined' ? 0 :  parseInt(single_data[2]);
          single_data = a + b + c;
        }
        var single_percent = (((_single_data - single_data)/single_data) * 100).toFixed(2);
        single_percent = isNaN(single_percent) ? '&infin;' : isFinite(single_percent) ? single_percent : '&infin;';
        table += '<td style="font-weight:bold;">' + single_percent + '%</td>';
      }
    }
  }
  table += '</tbody>';
  table += '</table>';


  if(gawd_data_table!=null){
    gawd_data_table.destroy();
    gawd_data_table = null;
  }

  jQuery('.gawd_data_table table').html(table);
  gawd_data_table = jQuery('.gawd_data_table table').DataTable( {
    "ordering": false,
    searching: false
  });

}

function gawd_draw_table(data, metric, metric_compare, dimension, dataSums) {
  jQuery('.gawd_page_table').remove();
  jQuery('.paging-nav').remove();
  if(typeof  data == 'undefined'){
      return;
  }
  if(typeof dataSums == 'undefined'){
    dataSums = false;
  }
    data = dimension != 'Active Page' ? JSON.parse(data) : data;
    var data_sum = 0;
    var data_sum_compare = 0;
    var col_total = false;
    if (typeof data.data_sum != "undefined" || dataSums){
      col_total = true;
      if(dimension == 'pagePath' || dimension == 'landingPagePath' || dimension == 'Active Page'){
        data_sum = data.data_sum;
        data = data.chart_data;

      }
      else{
        metric = metric.replace(/([A-Z])/g, "$1").trim();
        metric = metric.charAt(0).toUpperCase() + metric.slice(1);
        metric = metric.replace(/  +/g, ' ');
        if(!dataSums){
            data_sum = data.data_sum[metric];
            data_sum_origin = data.data_sum[metric];
            if(data.data_sum[metric] == 0){
              data_sum = 1;
            }
          if(metric_compare != 0 || metric_compare != ""){
            data_sum_compare = data.data_sum[metric_compare];
            data_sum_compare_origin = data.data_sum[metric_compare];
            if(data.data_sum[metric_compare] == 0){
              data_sum_compare = 1;
            }
          }
          data = data.chart_data;
        }
        else{
          data_sum = dataSums[metric][metric];
          data_sum_compare = dataSums[metric][metric + ' compare'];
          data_sum_origin = dataSums[metric][metric];
          data_sum_compare_origin = dataSums[metric][metric + ' compare'];
        }
      }
    }

    var count = 0;
    for (var property in data[0]) {
        if (Object.prototype.hasOwnProperty.call(data[0], property)) {
            count++;
        }
    }

    if (dimension == "pagePath" || dimension == "landingPagePath" || dimension == "daysToTransaction" || dimension == "transactionId" ||(dimension == "date" && metric == 'transactionRevenue')) {
        if (typeof data[0] != 'undefined') {
            labels = Object.keys(data[0]);

        } else {
            labels = ['No', dimension, metric];
            count = 3;
        }
    }
    else {
        if (metric_compare != 0) {
          if(metric+" compare" === metric_compare){
            labels = ['No', dimension, dimension + ' compare', metric, metric_compare];
            count = 5;
          }else{
            labels = ['No', dimension, metric, metric_compare];
            count = 4;
          }
        } else {
          labels = ['No', dimension, metric];
          count = 3;
        }
    }
    var colModel = [];
    var width = 15;
    if (dimension == "pagePath") {
        width = 40;
    }
    colModel.push({name: labels[0], index: labels[0], sorttype: 'int', width: width});
    if(labels[1] == 'Days To Transaction'){
      colModel.push({name: labels[1], index: labels[1], sorttype: 'int'});
    }
    else if(labels[1] == 'Session Duration Bucket'){
      colModel.push({name: labels[1], index: labels[1], formatter:secFormat});
    }
    else{
      colModel.push({name: labels[1], index: labels[1]});
    }
    for (var i = 2; i < count; i++) {
        if(labels[i] == 'Avg Session Duration' || labels[i] == 'Avg Session Duration compare' || labels[i] == 'Avg Page Load Time' || labels[i] == 'Avg Redirection Time' || labels[i] == 'Avg Server Response Time' || labels[i] == 'Avg Page Download Time' || labels[i] == 'Avg Page Load Time compare' || labels[i] == 'Avg Redirection Time compare' || labels[i] == 'Avg Server Response Time compare' || labels[i] == 'Avg Page Download Time compare' || labels[i] == 'Duration' || labels[i] == 'Avg Time On Page'){
          colModel.push({name: labels[i], index: labels[i], sorttype: 'int',editable: true, formatter:timeFormat});
        }
        else if(labels[i] == 'Bounce Rate' || labels[i] == 'Bounce Rate compare' || labels[i] == 'Transactions Per Session' || labels[i] == 'Transactions Per Session compare' || labels[i] == 'Exit Rate' || labels[i] == 'Percent New Sessions' || labels[i] == 'Percent New Sessions compare'){
          colModel.push({name: labels[i], index: labels[i], sorttype: 'int',editable: true, formatter:percentFormat});
        }
        else if(labels[i] == dimension + ' compare'){
          colModel.push({name: labels[i], index: labels[i] });
        }
        else if(labels[i].indexOf('Revenue') > -1 || labels[i] == 'Page Value'){
          colModel.push({name: labels[i], index: labels[i], sorttype: 'int',editable: true, formatter:dollarFormat});
        }
        else{
          colModel.push({name: labels[i], index: labels[i], sorttype: 'int',editable: true, formatter:numFormat});
        }
    }



  if(gawd_data_table!=null){
    gawd_data_table.destroy();
    gawd_data_table = null;
  }
    var table_tfoot = "";

    var table_columns = jQuery.map(colModel, function(value, index) {
      table_tfoot += '<th data-value="'+value.name+'"></th>';
      return {title: value.name};
    });

    var array = jQuery.map(data, function(value, index) {
      var datatable_row_data = gawd_datatable_formater(colModel , value);
      return [datatable_row_data];
    });

  jQuery('.gawd_data_table table').html("<thead></thead><tbody></tbody><tfoot><tr>"+table_tfoot+"</tr></tfoot>");

  if(dimension == 'pagePath' || dimension == 'landingPagePath' || dimension == 'Active Page'){

    metric = dimension == 'pagePath' ? ['Avg Page Load Time','Avg Time On Page','Bounce Rate','Entrances','Exit Rate','Page Value','Pageviews','Unique Pageviews'] : ['Sessions','Percent New Sessions','New Users','Bounce Rate','Pageviews Per Session','Avg Session Duration','Transactions','Transaction Revenue','Transactions Per Session'];
    for(var i = 0; i < metric.length; i++){
      _data_sum = data_sum[metric[i]];
      var sum = {};
      var colSum = parseFloat(_data_sum).toFixed(2);
      if(metric[i] == 'Avg Time On Page' || metric[i] == 'Avg Session Duration' || metric[i] == 'Avg Page Load Time'){
        colSum = sec_to_normal(colSum);
      }
      if(metric[i] == 'Percent New Sessions' || metric[i] == 'Avg Session Duration' || metric[i] == 'Transactions Per Session' || metric[i] == 'Avg Time On Page' || metric[i] == 'Exit Rate' || metric[i] == 'Pageviews Per Session'|| metric[i] == 'Bounce Rate' || metric[i] == 'Avg Page Load Time' || metric[i] == 'Page Value'){
        sum[metric[i]] = 'Avg: ' + colSum;
        if(metric[i] == 'Percent New Sessions' ||  metric[i] == 'Bounce Rate' || metric[i] == 'Transactions Per Session' || metric[i] == 'Exit Rate'){
          sum[metric[i]] = 'Avg: ' + colSum + '%';
        }
        else if(metric[i] == 'Page Value'){
          sum[metric[i]] = 'Avg: ' +  '$' + colSum;
        }
      }
      else{
        sum[metric[i]] = 'Total: ' + colSum;
      }
      if(colSum != ""){
        gawd_datatable_footer(sum);
      }
    }
  }
  else{
    if(typeof data_sum_origin != "undefined"){
      var colSum = parseFloat(data_sum_origin).toLocaleString();
    }else{
      var colSum = "";
    }
    var sum = {};
    if(metric == 'Avg Session Duration' || metric == 'Avg Page Load Time' || metric == 'Avg Redirection Time' || metric == 'Avg Server Response Time' || metric == 'Avg Page Download Time' || metric == 'Duration'){
      colSum = sec_to_normal(colSum);
      //gawd_datatable_footer(colSum)
    }
    sum[metric] = metric == 'Avg Session Duration' || metric == 'Avg Page Load Time' || metric == 'Avg Redirection Time' || metric == 'Avg Server Response Time' || metric == 'Avg Page Download Time' || metric == 'Duration' ? 'Average: ' + colSum : metric == 'Percent New Sessions' ||  metric == 'Bounce Rate' || metric == 'Transactions Per Session' ? 'Average: ' + colSum + '%': 'Total: ' + colSum;
    if(colSum != ""){
      gawd_datatable_footer(sum);
    }
    if(metric_compare != 0 || metric_compare != "" ){

      var colSum_compare = parseFloat(data_sum_compare_origin).toLocaleString();
      if(metric_compare == 'Avg Session Duration' || metric + ' compare' == 'Avg Session Duration compare' || metric_compare == 'Avg Page Load Time' || metric_compare == 'Avg Redirection Time' || metric_compare == 'Avg Server Response Time' || metric_compare == 'Avg Page Download Time' ||  metric + " compare" == 'Avg Page Load Time compare' || metric + " compare" == 'Avg Redirection Time compare' || metric + " compare" == 'Avg Server Response Time compare' || metric + " compare" == 'Avg Page Download Time compare' || metric_compare == 'Duration'){
        colSum_compare = sec_to_normal(colSum_compare);
      }
      var sum_compare = {};
      sum_compare[metric_compare] = metric_compare == 'Duration' || metric_compare == 'Avg Session Duration' || metric_compare == 'Avg Page Load Time' || metric_compare == 'Avg Redirection Time' || metric_compare == 'Avg Server Response Time' || metric_compare == 'Avg Page Download Time' || metric + " compare" == 'Avg Session Duration compare' || metric + " compare" == 'Avg Page Load Time compare' || metric + " compare" == 'Avg Redirection Time compare' || metric + " compare" == 'Avg Server Response Time compare' || metric + " compare" == 'Avg Page Download Time compare' ? 'Average: ' + colSum_compare  : metric_compare == 'Percent New Sessions' || metric_compare == 'Bounce Rate' || metric_compare == 'Percent New Sessions compare' || metric_compare == 'Bounce Rate compare' || metric_compare == 'Transactions Per Session compare' || metric_compare == 'Pageviews Per Session' ? 'Average: ' + colSum_compare + '%' : 'Total: ' + colSum_compare;
      if(sum_compare != ""){
        gawd_datatable_footer(sum_compare);
      }
    }
  }



    gawd_data_table = jQuery('.gawd_data_table table').DataTable( {
      data: array,
      columns: table_columns,
      responsive: true,
    });
    for (type in data[0]) {
      if(type != 'No'){
        geo_type = type;
        break;
      }
    }


    jQuery('#jqgh_griddiv_Language .s-ico').before('<span title="The language settings in your users browsers. Analytics uses the following ISO codes:Language 639 For example: en (English), en-us (English, United States), en-gb (English, Great Britain)" class="lang_info">?</span>');
    jQuery('#jqgh_griddiv_Language').tooltip();
    jQuery('.ui-search-toolbar th:first-child').css('background-color','#fff');
    jQuery('.ui-search-toolbar th:nth-child(2)').css('background-color','#fff');
    jQuery('.ui-search-toolbar th:last-child').css('background-color','#fff');
    jQuery('.ui-search-toolbar th:first-child table ').hide();
    jQuery('.ui-search-toolbar th:last-child table ').hide();
    if( jQuery('.ui-search-toolbar th').length >3){
          jQuery('.ui-search-toolbar th:nth-child(3)').css('background-color','#fff');
          jQuery('.ui-search-toolbar th:nth-child(3) table ').hide();
    }
}
function gawd_datatable_formater(colModel , rowObject) {
  var col_data = jQuery.map(colModel, function(value, index) {
    var col_model_name = value.index;
    var col_val;

      if (
          typeof rowObject['date_period'] !== 'undefined' && rowObject['date_period'] !== '' &&
          (col_model_name === 'Week' || col_model_name === 'Week compare' || col_model_name === 'Month' || col_model_name === 'Month compare')
      ) {
          if(col_model_name === 'Week compare' || col_model_name === 'Month compare'){
              col_val = rowObject['date_period compare'];
          }else{
              col_val = rowObject['date_period'];
          }
      } else {
          col_val = rowObject[col_model_name];
      }


      if(typeof value.formatter != "undefined" && typeof col_val != "undefined"){
        var col_value = value.formatter(col_val, {colModel:value}, rowObject);
        return col_value;
      }else{
        if(typeof col_val === "undefined"){
          col_val = "";
        }
        return col_val;
      }
  });
  return col_data;
}
function gawd_datatable_footer(sum) {
  jQuery.map(sum, function(value, index) {
    var table_footer_element = jQuery(".gawd_data_table table tfoot").find("[data-value='"+index+"']");
    table_footer_element.html(value);
  });

}
function gawd_draw_analytics_country_type(country_name, geo_type) {
    if (jQuery("#gawd_chart_type").val() == 'pie') {
        gawd_draw_analytics_country_pie(country_name, geo_type);
    } else {
        gawd_draw_analytics_country(country_name, geo_type);
    }
}
function gawd_draw_analytics_country(country_name, geo_type) {
  console.log("ajax3");
    if (geo_type == 'Country') {
        jQuery('#country_filter_reset').show();
    }
    jQuery("#gawd_metric_compare").show();
    jQuery("#filter_conteiner").show();
    jQuery("#chartdiv").show();
    var _end_date = (Date.today().add(-1).days()).toString("yyyy-MM-dd");
    var start_date_7 = (Date.today().add(-1).days()).add(-7).days().toString("yyyy-MM-dd");
    var start_end_date = typeof jQuery('#gawd_start_end_date').val() != 'undefined' ? jQuery('#gawd_start_end_date').val() : start_date_7 + '/-/' + _end_date;

    var start_end_date = start_end_date.split('/-/');
    var start_date = start_end_date[0];
    var end_date = start_end_date[1];
    var country_filter = country_name;
    metrics = [];
    var metric = jQuery("#gawd_metric").val();
    var metric_compare = jQuery("#gawd_metric_compare").val();
    metrics.push("ga:" + metric);
    if (metric_compare != 0) {
        metrics.push("ga:" + metric_compare);
    }
    if (jQuery("#gawd_chart_type").val() == 'line') {
        var chartType = 'line';
        var fillAlphas = 0;
    } else if (jQuery("#gawd_chart_type").val() == 'column') {
        var chartType = 'column';
        var fillAlphas = 1;
    }
    var dimension = country_filter == 'United States' ? 'Region' : 'City';
    var dimension_export = dimension;
    var metric_export = metric;
    var metric_compare_export = metric_compare == 0 ? '' : metric_compare;
    var parseDates = false;
    var rotateAngle = 90;
    var chartscrollbar = false;
  console.log("ajax12");
    jQuery.post(gawd_admin.ajaxurl, {
        action: 'show_data',
        start_date: start_date,
        end_date: end_date,
        metric: metrics,
        dimension: dimension,
        country_filter: country_filter,
        security: gawd_admin.ajaxnonce,
        geo_type: geo_type,
        beforeSend: function () {
            jQuery('#opacity_div').show();
            jQuery('#loading_div').show();
        }
    }).done(function (data) {
        jQuery('#opacity_div').hide();
        jQuery('#loading_div').hide();
        var result = JSON.parse(data).chart_data;
        jQuery("#chartdiv").show();
        metric = metric.replace(/([A-Z])/g, " $1").trim();
        metric = metric.charAt(0).toUpperCase() + metric.slice(1);
        metric = metric.replace(/  +/g, ' ');
        metric_compare = metric_compare.replace(/([A-Z])/g, " $1").trim();
        metric_compare = metric_compare.charAt(0).toUpperCase() + metric_compare.slice(1);
        metric_compare = metric_compare.replace(/  +/g, ' ');
        dimension = dimension.replace(/([A-Z])/g, " $1").trim();
        dimension = dimension.charAt(0).toUpperCase() + dimension.slice(1);
        filter_type = '';
        var tab_name = window.location.href.split('tab=');

        d_start_date = start_date;
        d_end_date = end_date;
        d_metric_export = metric_export;
        d_metric_compare_export = metric_compare_export;
        d_dimension_export = dimension_export;

        d_tab_name = tab_name;
        d_filter_type = filter_type;
        d_country_filter = country_name;
        d_geo_type = geo_type;
        /* jQuery("#gbox_griddiv").remove();
         var grid = '<table id="griddiv"></table>';
         jQuery('.gawd_chart_conteiner').append(grid); */
        jQuery("#gbox_griddiv").remove();
        var grid = '<table id="griddiv"></table><div id="pager"></div>';
        jQuery('.gawd_chart_conteiner').append(grid);
        gawd_draw_table(data, metric, metric_compare, dimension);
    })
}

function gawd_draw_analytics_country_pie(country_name, geo_type) {
  if (geo_type == 'Country') {
        jQuery('#country_filter_reset').show();
    }
    jQuery("#gawd_metric_compare").show();
    jQuery("#filter_conteiner").show();
    jQuery("#chartdiv").show();
    var _end_date = (Date.today().add(-1).days()).toString("yyyy-MM-dd");
    var start_date_7 = (Date.today().add(-1).days()).add(-7).days().toString("yyyy-MM-dd");
    var start_end_date = typeof jQuery('#gawd_start_end_date').val() != 'undefined' ? jQuery('#gawd_start_end_date').val() : start_date_7 + '/-/' + _end_date;

    var start_end_date = start_end_date.split('/-/');
    var start_date = start_end_date[0];
    var end_date = start_end_date[1];
    var country_filter = country_name;
    metrics = [];
    var metric = jQuery("#gawd_metric").val();
    var metric_compare = jQuery("#gawd_metric_compare").val();
    metrics.push("ga:" + metric);
    if (metric_compare != 0) {
        metrics.push("ga:" + metric_compare);
    }
    if (jQuery("#gawd_chart_type").val() == 'line') {
        var chartType = 'line';
        var fillAlphas = 0;
    } else if (jQuery("#gawd_chart_type").val() == 'column') {
        var chartType = 'column';
        var fillAlphas = 1;
    }
    var dimension = country_filter == 'United States' ? 'Region' : 'City';

    var parseDates = false;
    var rotateAngle = 90;
    var chartscrollbar = false;
  console.log("ajax01");
    jQuery.post(gawd_admin.ajaxurl, {
        action: 'show_data',
        start_date: start_date,
        end_date: end_date,
        metric: metrics,
        dimension: dimension,
        country_filter: country_filter,
        security: gawd_admin.ajaxnonce,
        geo_type: geo_type,
        beforeSend: function () {
            jQuery('#opacity_div').show();
            jQuery('#loading_div').show();
        }
    }).done(function (data) {
        jQuery('#opacity_div').hide();
        jQuery('#loading_div').hide();
        var result = JSON.parse(data).chart_data;
        jQuery("#chartdiv").show();
        var dimension_export = dimension;
        var metric_export = metric;
        metric = metric.replace(/([A-Z])/g, " $1").trim();
        metric = metric.charAt(0).toUpperCase() + metric.slice(1);
        metric = metric.replace(/  +/g, ' ');
        var metric_compare_export = '';
        var filter_type = '';

        //CANVAS//
        var tab_name = window.location.href.split('tab=');
        if (tab_name[1] != 'pagePath' && tab_name[1] != 'daysToTransaction') {
            var svg = document.getElementsByTagName('svg')[0];
            var canvas = document.getElementById("canvass");
            draw_canvas(svg, canvas);
            url = canvas.toDataURL();
        }
        if (typeof url == 'undefined') {
            url = '';
        }
        //CANVAS URL//
        var tab_name = window.location.href.split('tab=');

        d_start_date = start_date;
        d_end_date = end_date;
        d_metric_export = metric_export;
        d_metric_compare_export = metric_compare_export;
        d_dimension_export = dimension_export;
        d_tab_name = tab_name;
        d_filter_type = filter_type;
        d_country_filter = country_name;
        d_geo_type = geo_type;
        jQuery("#gbox_griddiv").remove();
        var grid = '<table id="griddiv"></table><div id="pager"></div>';
        jQuery('.gawd_chart_conteiner').append(grid);
        gawd_draw_table(data, metric, metric_compare, dimension);
    })
}
function gawd_datepicker_main(start, end) {
    jQuery('#reportrange span').html(start.format('Y-MM-DD') + ' - ' + end.format('Y-MM-DD'));
    jQuery('#gawd_start_end_date').val(start.format('Y-MM-DD') + '/-/' + end.format('Y-MM-DD'));
    gawd_chart_type();
}

function change_account(that) {
    jQuery('#web_property_name').val(jQuery(that).find(':selected').closest('optgroup').attr('label'));
    jQuery('#gawd_view').submit();
}
function gawd_tracking_display_manage() {
    hljs.configure({useBR: true});
    jQuery(document).ready(function () {
        jQuery('div code').each(function (i, block) {
            hljs.highlightBlock(block);
        });
    });
    jQuery('.gawd_tracking .onoffswitch-checkbox').on('change', function () {
        var name = jQuery(this).attr('name');
        var checked = jQuery(this).attr('checked');
        if ('checked' == checked) {
            jQuery('#' + name + '_code').show();
        } else {
            jQuery('#' + name + '_code').hide();
        }
    });
    jQuery('#gawd_tracking_enable').on('change', function() {
        checked = (jQuery(this).attr('checked'));
        if (checked == 'checked') {
            jQuery('.independent_switch').each(function() {
                jQuery(this).removeClass('onoffswitch_disabled');
            });
            jQuery('.independent_input').each(function() {
                jQuery(this).removeAttr('disabled');
            });
        } else {
            jQuery('.independent_switch').each(function() {
                jQuery(this).addClass('onoffswitch_disabled');
            });
            jQuery('.independent_input').each(function() {
                jQuery(this).attr('disabled','disabled');
            });
        }
    });
}


jQuery(document).ready(function () {
    jQuery("#gawd_refresh_management_accounts").on('click', function (e) {
        e.preventDefault();
        gawd_refresh_management_accounts();
        return false;
    });

    jQuery('#gawd_add_property').on('click', function(e){
        e.preventDefault();

        var property_name = jQuery('#gawd_property_name').val();

        jQuery("#gawd_property_name").removeClass('gawd_invalid')
        if (property_name == '') {
            jQuery("#gawd_property_name").addClass('gawd_invalid');
            return false;
        }

        var args = gawd_custom_ajax_args();
        args.type = 'POST';
        args.async = true;
        args.data.gawd_action = "add_property";
        args.data.gawd_data = {
            "gawd_account_select": jQuery('.gawd_account_select').val(),
            "gawd_property_name": property_name
        };

        var $loader_container = jQuery("#gawd_right_conteiner");
        args.beforeSend = function () {
            gawd_add_loader($loader_container);
        };

        args.success = function (response) {
            window.location.reload();
        };

        args.error = function () {
            window.location.reload();
        };

        jQuery.ajax(args).done(function () {
            gawd_remove_loader($loader_container);
        });

    });

    jQuery('#gawd_choose_property').on('click', function(e){
        e.preventDefault();

        var $el = jQuery(".gawd_property_select")
        var property_val = $el.val();

        if(property_val === '0'){
            $el.addClass('gawd_invalid');
            return false;
        }

        var args = gawd_custom_ajax_args();
        args.type = 'POST';
        args.async = true;
        args.data.gawd_action = "choose_property";
        args.data.gawd_data = {
            "gawd_property": property_val
        };

        var $loader_container = jQuery("#gawd_right_conteiner");
        args.beforeSend = function () {
            gawd_add_loader($loader_container);
        };

        args.success = function (response) {
            window.location.reload();
        };

        args.error = function () {
            window.location.reload();
        };

        jQuery.ajax(args).done(function () {
            gawd_remove_loader($loader_container);
        });
    });

    jQuery('.gawd_property_select').on('change',function(){
       if(jQuery(this).val() === '0'){
           jQuery('#gawd_choose_property').addClass('gawd_disabled_button');
       }else{
           jQuery('#gawd_choose_property').removeClass('gawd_disabled_button');
           jQuery(this).removeClass('gawd_invalid');
       }
    });

    jQuery('#gawd_reset_credentials').on('click', function (e) {
        e.preventDefault();

        var args = gawd_custom_ajax_args();
        args.type = 'POST';
        args.async = true;
        args.data.gawd_action = "delete_credentials";

        var $loader_container = jQuery("#wpwrap");
        args.beforeSend = function () {
            gawd_add_loader($loader_container);
        };

        args.success = function (response) {
            window.location.reload();
        };

        args.error = function () {
            window.location.reload();
        };

        jQuery.ajax(args).done(function () {
            gawd_remove_loader($loader_container);
        });

        return false;
    });
});

function gawd_refresh_user_info(hidden, remove_reports_data,reload) {

    hidden = (typeof hidden === 'undefined' || hidden === false) ? false : true;
    remove_reports_data = (typeof remove_reports_data === 'undefined' || remove_reports_data === false) ? false : true;
    reload = (typeof reload === 'undefined' || reload === false) ? false : true;

    var args = gawd_custom_ajax_args();
    args.type = 'POST';
    args.async = true;
    args.data.gawd_action = "refresh_user_info";
    args.data.gawd_data = {
        'add_notice': (hidden === true) ? '0' : '1',
        'remove_reports_data': (remove_reports_data === true) ? '1' : '0',
    };

    var $loader_container = jQuery("#wpcontent");
    if (hidden === false) {
        args.beforeSend = function () {
            gawd_add_loader($loader_container);
        };

        args.complete = function () {
            gawd_remove_loader($loader_container);
        };

        args.success = function (response) {
            if(response.error.code === "no_properties"){
                window.location.hash  = "#gawd_tracking_tab"
            }
            window.location.reload();
        };

        args.error = function () {
            window.location.reload();
        };
    } else {
        args.error = function () {
        };

        if(reload == true){
            window.location.reload();
        }
    }

    jQuery.ajax(args);
}

function gawd_refresh_management_accounts() {
    var args = gawd_custom_ajax_args();
    args.type = 'POST';
    args.async = true;
    args.data.gawd_action = "refresh_management_accounts";

    var $loader_container = jQuery("#wpbody");
    args.beforeSend = function () {
        gawd_add_loader($loader_container);
    };

    args.success = function (response) {
        if(response.success === true){
            gawd_refresh_user_info(true, true, true);
        }else{
            window.location.reload();
        }
    };

    args.error = function () {
        window.location.reload();
    };

    jQuery.ajax(args).done(function(){
        // gawd_remove_loader($loader_container);
    });
}


function gawd_auth_popup(w, h) {
    var left = (screen.width / 2) - (w / 2);
    var top = (screen.height / 8);
    jQuery('#gawd_auth_code').show();
    jQuery('.auth_description').hide();
    jQuery('#gawd_auth_url').hide();
    jQuery('.gawd_submit').hide();

    var ajax_args = gawd_custom_ajax_args();
    ajax_args.data.gawd_action = "get_auth_url";
    ajax_args.success = function (response) {
        var url = response.data.url;
        window.open(url, 'gawd_auth_window', 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);

        jQuery('#gawd_auth_code_submit').on('click', function () {
            gawd_auth_ajax();
        });

        jQuery('#gawd_token').bind("enterKey", function (e) {
            if (jQuery('#gawd_token').val() != '') {
                gawd_auth_ajax();
            }
        });

        jQuery('#gawd_token').keyup(function (e) {
            if (e.keyCode == 13) {
                jQuery(this).trigger("enterKey");
            }
        });

    };

    ajax_args.error = function () {
        //window.location.reload();
    };

    jQuery.ajax(ajax_args);
}

function gawd_auth_ajax() {
    var args = gawd_custom_ajax_args();
    args.type = 'POST';
    args.async = true;
    args.data.gawd_action = "authenticate";
    args.data.gawd_data = {'token': jQuery('#gawd_token').val()};

    var $loader_container = jQuery(".gawd_auth_authenticate");
    args.beforeSend = function () {
        gawd_add_loader($loader_container);
    };

    args.success = function (response) {

        if(response.success === false){
            window.location.reload();
            return false;
        }

        if (typeof response.data.redirect !== 'undefined') {
            if (response.data.redirect === "tracking_tab") {
                window.location.href += "#gawd_tracking_tab";
                window.location.reload();
            } else if (response.data.redirect === "dashboard") {
                window.location.href = gawd_admin.wp_admin_url + "admin.php?page=gawd_analytics";
            } else {
                window.location.reload();
            }
        }

        window.location.reload();
    };

    args.error = function (response) {
        window.location.reload();
    };

    jQuery.ajax(args).done(function () {
        gawd_remove_loader($loader_container);
    });
}

function gawd_widget_real_time(chart_id) {

    var args = gawd_custom_ajax_args();
    args.type = 'POST';
    args.async = true;
    args.data.gawd_action = "get_real_time_data";

    args.beforeSend = function() {
        jQuery('#opacity_div').show();
        jQuery('#loading_div').show();
        jQuery(chart_id).closest(".postbox").find('.opacity_div_compact').show();
        jQuery(chart_id).closest(".postbox").find('.loading_div_compact').show();
    };

    args.success = function (data) {

        if(data.success === false){
            gawd_add_notice(data, chart_id);
            return;
        }

        var result = data.data.real_time_data;
        jQuery(chart_id).empty();
        jQuery("#gawd_right_conteiner").find('#gbox_griddiv').remove();
        var grid = '<table id="griddiv"></table><div id="pager"></div>';
        jQuery('#gawd_right_conteiner').append(grid);
        var array = [];
        var desktop = 0;
        var mobile = 0;

        var country_data = {};

        if (typeof result.rows != 'undefined') {

            var country = '';
            jQuery.each(result.rows, function (index, value) {
                country = value[4];
                if (typeof country_data[country] == 'undefined') {
                    country_data[country] = 0;
                }
                country_data[country] += Number(value[7]);

                if (value[6] == 'DESKTOP') {
                    desktop += Number(value[7]);
                } else if (value[6] == 'MOBILE') {
                    mobile += Number(value[7]);
                }
                if (typeof array[value[0]] == 'undefined') {
                    array[value[0]] = Number(value[7]);
                } else {
                    array[value[0]] += Number(value[7]);
                }
            });
            var i = 0;
            var sortable = [];
            for (var key in array) {
                sortable.push([key, array[key]]);
            }
            sortable.sort(function (a, b) {
                return b[1] - a[1]
            });
            array = [];
            for (var j = 0; j < sortable.length; j++) {
                var row = {};
                row.No = (j + 1);
                row["Active Page"] = sortable[j][0];
                row["Active Users"] = sortable[j][1];
                array.push(row);
            }

            var realtime_cont = '<div class="gawd_realtime_conteiner">' + (mobile + desktop) + '</div><div class="device_type"></div><div class="gawd_table_conteiner">';

        } else {
            var realtime_cont = '<div class="gawd_realtime_conteiner">' + 0 + '</div>';
        }
        if (chart_id != "#chart_widget" && chart_id != "#gawd_real_time_meta") {
            var refresh_button = '<input class="button_gawd" type="button" id="gawd_real_time_refresh_button" value="Refresh" />';
            var d = (desktop !== 0) ? Math.round((desktop / (desktop + mobile)) * 100) : 0;
            var m = (mobile != 0) ? (100 - d) : 0;

            var desktop_color = '#ed561b';
            var mobile_color = '#50b432';

            var desktop_modile_bar = '';
            desktop_modile_bar += '<div>';

            if (d != 0 || m != 0) {
                var bar_html = '<div class="realtime_bar">';
                var bar_color_html = '<div class="realtime_colors">';
                if (d != 0) {
                    bar_color_html += '<span>';
                    bar_color_html += '<em style="background-color:' + desktop_color + '"></em>';
                    bar_color_html += '<span style="padding:6px;">Desktop</span></span>';

                    bar_html += '<div style="background-color:' + desktop_color + ';width:' + d + '%;">';
                    bar_html += d + '%' + '</div>';
                }

                if (m != 0) {
                    bar_color_html += '<span>';
                    bar_color_html += '<em style="background-color:' + mobile_color + '"></em>';
                    bar_color_html += '<span style="padding-left: 6px;">Mobile</span></span>';

                    bar_html += '<div style="background-color:' + mobile_color + ';width:' + m + '%;">';
                    bar_html += m + '%' + '</div>';

                }
                bar_color_html += '</div>';
                bar_html += '</div>'
                desktop_modile_bar += bar_color_html + bar_html;
            }
            desktop_modile_bar += '</div>';


            jQuery('#chartdiv').css('height', 'auto');
            jQuery('#chartdiv').append(refresh_button);
            jQuery('#chartdiv').append(realtime_cont);
            jQuery('#chartdiv').append(desktop_modile_bar);

            if (typeof result.rows != 'undefined') {
                jQuery('#chartdiv').append('<div id="realtime_map" style="height:auto;"></div>');
                jQuery('#chartdiv').css('margin-bottom', '20px');
                jQuery('#chartdiv').css('min-height', '60px');

                //show_realtime_map(country_data);
            }
            gawd_draw_table(array, 'Active Users', 0, 'Active Page')
        }
        else {
            jQuery(chart_id).height('110');
            jQuery(chart_id).append(realtime_cont);
            jQuery('.gawd_table_conteiner').hide();
        }
        jQuery("#chart_widget").find('a').remove();

        jQuery('#gawd_real_time_refresh_button').on('click', function () {
            gawd_widget_real_time('#chartdiv');
        });
        jQuery('#opacity_div').hide();
        jQuery('#loading_div').hide();
        jQuery(chart_id).closest(".postbox").find('.opacity_div_compact').hide();
        jQuery(chart_id).closest(".postbox").find('.loading_div_compact').hide();

    }
    jQuery.ajax(args).done(function(){
        jQuery('#opacity_div').hide();
        jQuery('#loading_div').hide();
        jQuery(chart_id).closest(".postbox").find('.opacity_div_compact').hide();
        jQuery(chart_id).closest(".postbox").find('.loading_div_compact').hide();
    });
}

function do_gawd_ajax(){

    var args = gawd_custom_ajax_args();
    args.type = 'POST';
    args.data.gawd_action = "get_custom_dimensions";
    jQuery.ajax(args);
}

function gawd_custom_ajax_args() {

    return {
        'url': gawd_admin.ajaxurl,
        'type': "GET",
        'dataType': 'json',
        'async': false,
        'data': {
            'gawd_ajax': '1',
            'gawd_nonce': gawd_admin.gawd_custom_ajax_nonce,
            'gawd_nonce_data': gawd_admin.gawd_custom_ajax_nonce_data,
            'gawd_action': "",
            'gawd_data': []
        },
        success: function (data) {
        },
        error: function (data) {
        }
    };
}

function gawd_reload_account() {
  jQuery(".gwd_refresh_page").removeClass('gawd_hidden');
}
function gawd_show_authenticate_form() {
  jQuery(".gawd_auth_authenticate").removeClass('gawd_hidden');
  jQuery(".no_account_content").addClass('gawd_hidden');
}

function gawd_add_loader($container) {
    var html = "<div class='gawd_loader_opacity'></div>";
    html += "<div class='gawd_loader_container'>" +
        "<img src='" + gawd_admin.gawd_plugin_url + "/assets/ajax_loader.gif" + "'/>" +
        "</div>";
    $container.append(html);
}

function gawd_remove_loader($container) {
    $container.find('.gawd_loader_opacity').remove();
    $container.find('.gawd_loader_container').remove();
}


function gawd_remove_item(dataKey,hidden){
  jQuery('#'+hidden).val(dataKey);
  jQuery('#gawd_form').submit();
}
function gawd_search() {
jQuery('.gawd_menu_li').find("ul").show();
        var search, not_match = false;
        search = jQuery('.gawd_search_input').val();
        var search_text = new RegExp(search, "gi");
        var Exp = /^[a-z\d\-_\s]+$/gi;
        jQuery('.gawd_menu_li').show();
        jQuery('.gawd_menu_ul_li').show();
        jQuery('.gawd_active_li').closest('ul').show();
          if (search.length > 0 && search.match(Exp)) {
            jQuery('.gawd_menu_li').each(function (){
              var hide = true;
              if(jQuery(this).find('.gawd_menu_li_sub').length > 0){
                if (jQuery(this).find('.gawd_menu_li_sub').text().search(search_text) < 0 ) {
                  jQuery(this).find('.gawd_menu_item').each(function (){
                    if ((jQuery(this).text()).search(search_text) >= 0 ) {
                      hide = false;
                      return;
                    }
                  });
                }
                else{
                    hide = false;
                }
              }
              else{
                jQuery(this).find('.gawd_menu_item').each(function (){
                    if ((jQuery(this).text()).search(search_text) >= 0 ) {
                      hide = false;
                        jQuery(this).closest('ul').show();
                      return;
                    }
                  });
              }
              if(hide){
                jQuery(this).hide();
              }
               /*  if ((jQuery(this).text()).search(search_text) < 0 ) {
                   jQuery(this).closest('li').hide();
                }
                else{
                  jQuery(this).closest('li').show();
                  if(not_match == false){
                    not_match = true;
                  }
                } */
            });
          }else{
            jQuery('.gawd_menu_li').find("ul").hide();
          }
            /*
            jQuery('.gawd_menu_item').each(function (){
                if ((jQuery(this).text()).search(search_text) < 0 ) {
                   jQuery(this).closest('li').hide();
                }
                else{
                  jQuery(this).closest('li').show();
                  if(not_match == false){
                    not_match = true;
                  }
                }
            });
            jQuery('.gawd_menu_li').each(function (){
              var t = false;
              jQuery(this).find('li').each(function (){
                if(jQuery(this).is(':visible')){
                  t = true;
                  return;
                }
              });
              if(jQuery(this).find('li ').length >0 && t == false){
                jQuery(this).hide();
              }
            });
*/
      if(not_match == false){

      }
}
function remove_zoom_message(){
  var got_it = jQuery('#gawd_got_it').val();
  console.log("ajax12");
  jQuery.post(gawd_admin.ajaxurl, {
      action: 'remove_zoom_message',
      got_it: got_it,
      security: gawd_admin.ajaxnonce,
  }).done(function (data) {
    jQuery('.gawd_zoom_message').remove();
  })
}


function get_tab_name() {
  var tab_name = window.location.href.split('tab=');
  if(typeof tab_name[1] != "undefined"){
    return tab_name[1];
  }
}