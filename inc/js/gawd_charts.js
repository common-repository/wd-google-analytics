var gawd_wp_dashboard_charts = function () {

    var _this = this;
    this.helper = new gawd_charts_helper();
    this.$container = jQuery('#gawd_wp_dashboard_chart_container');
    this.$metric_element = jQuery('#gawd_metric_widget');
    this.$date_element = jQuery('#gawd_widget_date');

    this.init = function () {
        this.add_action_listeners();
        this.get_report_data_and_print_chart();
    };

    this.print_chart = function (chart_data, x_key, y_key) {

        var trace = {
            x: [],
            y: [],
            mode: 'lines+markers',
            type: 'scatter'
        };

        for (var i = 0; i < chart_data.length; i++) {
            trace.x.push(chart_data[i][x_key]);
            trace.y.push(chart_data[i][y_key]);
        }

        var data = [trace];
        var layout = {
            xaxis: {
                type: "date",
                title: "Date",
                showline: true
            },
            yaxis: {
                rangemode: 'nonnegative',
                title: y_key,
                showline: true
            }
        };

        if(trace.x.length === 1){
            layout.xaxis.dtick = 60;
        }

        var configs = {displayModeBar: false};

        Plotly.newPlot('gawd_wp_dashboard_chart_wrapper', data, layout, configs);
    };

    this.get_report_data_and_print_chart = function () {
        console.log("ajax06");

        var args = gawd_custom_ajax_args();
        var report_filters = this.get_report_data_filters();

        args.type = 'POST';
        args.async = true;
        if (_this.$date_element.val() === "realTime") {
            args.data.gawd_action = "get_real_time_data";
        } else {
            args.data.gawd_action = "gawd_show_data";
            args.data.gawd_data = report_filters;
        }

        args.beforeSend = function () {
            _this.$container.find('.gawd_opacity_div_compact').show();
            _this.$container.find('.gawd_loading_div_compact').show();
        };

        args.success = function (data) {

            if (data.success === false) {
                _this.$container.html("");
                gawd_add_notice(data, '#gawd_wp_dashboard_chart_container');
                return false;
            }

            //remove chart and realtime container
            Plotly.purge('gawd_wp_dashboard_chart_wrapper');
            _this.$container.find('.gawd_realtime_conteiner').remove();

            //realtime data
            if (typeof data.data.real_time_data !== 'undefined') {
                _this.$container.append(
                    '<div class="gawd_realtime_conteiner">' +
                    _this.helper.calculate_realtime(data.data.real_time_data) +
                    '</div>'
                );
                return;
            }

            var reports_data = JSON.parse(data.data.gawd_reports_data);
            var chart_data = reports_data.chart_data;

            var metric = report_filters.metric;
            metric = metric.replace(/([A-Z])/g, " $1").trim();
            metric = metric.charAt(0).toUpperCase() + metric.slice(1);
            var y_key = metric.replace(/  +/g, ' ');
            var x_key = "Date";

            _this.print_chart(chart_data, x_key, y_key);
        };

        jQuery.ajax(args).done(function () {
            _this.$container.find('.gawd_opacity_div_compact').hide();
            _this.$container.find('.gawd_loading_div_compact').hide();
        });
    };

    this.get_report_data_filters = function () {

        var start_date;
        if (typeof this.$date_element.val() != 'undefined') {
            start_date = this.$date_element.val();
        } else {
            start_date = (Date.today().add(-1).days()).days().toString("yyyy-MM-dd");
        }
        var end_date = (Date.today().add(-1).days()).toString("yyyy-MM-dd");
        var metric = this.$metric_element.val();


        var filters = {
            "start_date": start_date,
            "end_date": end_date,
            "metric": metric
        };
        return filters;
    };

    this.refresh_chart = function () {
        _this.get_report_data_and_print_chart();
    };

    this.add_action_listeners = function () {
        this.$date_element.on('change', function () {
            if (_this.$date_element.val() === "realTime") {
                _this.$container.addClass('gawd_realtime_data');
            } else {
                _this.$container.removeClass('gawd_realtime_data');
            }
            _this.refresh_chart();
        });
        this.$metric_element.on('change', _this.refresh_chart);
    };

};

var gawd_charts_helper = function () {

    var _this = this;


    this.configs = {
        'first_line_color': 'rgba(52, 118, 191, 0.6)',
        'second_line_color': 'rgba(219, 64, 82, 0.6)'
    };

    this.calculate_realtime = function (result) {

        if (typeof result.rows != 'undefined') {

            var country = '';
            var desktop = 0;
            var mobile = 0;
            var country_data = [];
            var array = [];
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

            return mobile + desktop;
        }

        return 0;
    }

    this.print_posts_chart = function (chart_data, x_key, y_key, chart_type, div_id) {

        var type = 'scatter';
        if (chart_type === 'column') {
            type = 'bar';
        }

        var trace = {
            x: [],
            y: [],
            type: type
        };

        if(type === 'scatter'){
            trace.mode = 'lines+markers';
        }

        for (var i = 0; i < chart_data.length; i++) {
            trace.x.push(chart_data[i][x_key]);
            trace.y.push(chart_data[i][y_key]);
        }

        var y_title = jQuery('#gawd_metric_post_page_popup').find('option[value="' + jQuery('#gawd_metric_post_page_popup').val() + '"]').text();

        var data = [trace];
        var layout = {
            xaxis: {

                type: "date",
                // title: "Date",
                showline: true,
            },
            yaxis: {
                rangemode: 'nonnegative',
                title: y_title,
                showline: true
            }
        };

        if(trace.x.length === 1){
            layout.xaxis.dtick = 60;
        }

        var configs = {displayModeBar: false};

        Plotly.newPlot(div_id, data, layout, configs);
    };

    this.print_pie_chart = function (chart_data, label_key,metric, div_id) {

        var values = [];
        var labels = [];
        var i = 0;

        chart_data.sort(function (a, b) {
            if (a[metric] > b[metric]) {
                return -1;
            } else if (a[metric] < b[metric]) {
                return 1;
            } else {
                return 0;
            }
        });


        if (chart_data.length <= 10) {
            for (i = 0; i < chart_data.length; i++) {
                values.push(chart_data[i][metric]);
                labels.push(first_letter_to_uppercase(chart_data[i][label_key]));
            }
        } else {
            var other = 0;
            for (i = 0; i < chart_data.length; i++) {
                if (i <= 8) {

                    values.push(chart_data[i][metric]);
                    labels.push(first_letter_to_uppercase(chart_data[i][label_key]));
                } else {
                    other += chart_data[i][metric];
                }
            }
            if (other > 0) {
                values.push(other);
                labels.push('Other');
            }
        }

        //empty data
        if (typeof labels[0] !== 'undefined' && labels[0] === 0) {
            jQuery('#'+div_id).html('');
            jQuery('#'+div_id).append('<h4>There is no data to display as a pie chart.</h4>');
            return;
        }

        var values_sum = 0;
        for(i=0;i<values.length;i++){
            values_sum += values[i];
        }

        if(values_sum === 0){
            jQuery('#'+div_id).html('');
            jQuery('#'+div_id).append('<h4>There is no data to display as a pie chart.</h4>');
            return;
        }

        var data = [{
            values: values,
            labels: labels,
            type: 'pie'
        }];

        var show_legend = true;
        if (jQuery(window).width() < 1600 && jQuery('#gawd_body').length > 0) {

            var tab = jQuery('#gawd_body').data('gawd-tab');
            if (
                typeof tab !== 'undefined' &&
                (tab === "inMarket" || tab === 'otherCategory' || tab === 'affinityCategory')
            ) {
                show_legend = false;
            }
        }

        if(div_id === 'gawd_browser_meta'){
            show_legend = false;
        }

        var layout = {
            showlegend: show_legend
        };

        var configs = {displayModeBar: false};

        Plotly.newPlot(div_id, data, layout, configs);
    };

    this.print_compact_line_chart = function (chart_data, x_key, y_key1, y_key2, div_id) {

        var trace1 = {
            x: [],
            y: [],
            type: 'scatter',
            mode: 'lines+markers'
        };

        var trace2 = {
            x: [],
            y: [],
            type: 'scatter',
            mode: 'lines+markers',
            yaxis: 'y2',
            line: {
                color: _this.configs.second_line_color,
            }
        };

        var $gawd_wrapper = jQuery('#' + div_id).closest('.gawd_wrapper');

        var $select1 = $gawd_wrapper.find('#first_metric #gawd_metric_compact');
        var $select2 = $gawd_wrapper.find('#metric_compare .gawd_compact_metric_change');

        trace1.name = $select1.find('option[value="' + $select1.val() + '"]').text();
        trace2.name = $select2.find('option[value="' + $select2.val() + '"]').text();

        for (var i = 0; i < chart_data.length; i++) {
            trace1.x.push(chart_data[i][x_key]);
            trace1.y.push(chart_data[i][y_key1]);

            trace2.x.push(chart_data[i][x_key]);
            trace2.y.push(chart_data[i][y_key2]);
        }

        var layout = {
            xaxis: {
                type: "date",
                // title: "Date",
                showline: true
            },
            yaxis: {
                rangemode: 'nonnegative',
                title: trace1.name,
                showline: true
            }
        };

        var data = [];
        if (y_key2 !== "0") {
            data = [trace1, trace2];
            layout.yaxis2 = {
                rangemode: 'nonnegative',
                title: trace2.name,
                overlaying: 'y',
                side: 'right'
            }
        } else {
            data = [trace1];
        }

        if(trace1.x.length === 1){
            layout.xaxis.dtick = 60;
        }

        var configs = {displayModeBar: false};

        Plotly.newPlot(div_id, data, layout, configs);
    };

    this.print_reports_line_chart = function (chart_data, x_key, y_key1, y_key2, chart_type, div_id) {

        var type = (chart_type === 'column') ? 'bar' : 'scatter';
        var trace1 = {x: [], y: [], type: type};
        var trace2 = {x: [], y: [], type: type, yaxis: 'y2'};


        if (type === "scatter") {

            trace1.line = {
                color: _this.configs.first_line_color
            };

            trace2.line = {
                color: _this.configs.second_line_color
            };

            trace1.mode = 'lines+markers';
            trace2.mode = 'lines+markers';

        } else {

            trace2.marker = {
                color: _this.configs.second_line_color,
                line: {
                    color: _this.configs.second_line_color
                }
            };

            trace1.marker = {
                color: _this.configs.first_line_color,
                line: {
                    color: _this.configs.first_line_color
                }
            };
        }

        var $select1 = jQuery('#gawd_metric');
        var $select2 = jQuery('#gawd_metric_compare');

        trace1.name = $select1.find('option[value="' + $select1.val() + '"]').text();
        trace2.name = $select2.find('option[value="' + $select2.val() + '"]').text();

        for (var i = 0; i < chart_data.length; i++) {
            trace1.y.push(chart_data[i][y_key1]);
            trace2.y.push(chart_data[i][y_key2]);

            if(x_key === "Date" || x_key === "Hour" || x_key === "Week" || x_key === "Month"){
                trace1.x.push(chart_data[i][x_key]);
                trace2.x.push(chart_data[i][x_key]);
            }else {
                trace1.x.push(first_letter_to_uppercase(chart_data[i][x_key]));
                trace2.x.push(first_letter_to_uppercase(chart_data[i][x_key]));
            }

        }

        var layout = {
            xaxis: {
                rangemode: 'nonnegative',
                type: (x_key === 'Date') ? 'date' : '',
                showline: true
            },
            yaxis: {
                rangemode: 'nonnegative',
                title: trace1.name,
                showline: true
            }
        };

        var data = [];

        if (y_key2 !== "0" && y_key2 !== '') {
            data = [trace1, trace2];

            layout.yaxis2 = {
                rangemode: 'nonnegative',
                title: trace2.name,
                overlaying: 'y',
                side: 'right'
            }

        } else {
            data = [trace1];
        }

        if(layout.xaxis.type === "date" && trace1.x.length === 1){
            layout.xaxis.dtick = 60;
        }

        var configs = {displayModeBar: false};

        Plotly.newPlot(div_id, data, layout, configs);
    };

    this.print_reports_line_chart_compare = function (chart_data, chart_data_compare, x_key, y_key1, chart_type, div_id) {

        var type = (chart_type === 'column') ? 'bar' : 'scatter';
        var i = 0;

        var trace1 = {x: [], y: [], type: type};
        var trace2 = {x: [], y: [], type: type, xaxis: 'x2'};

        if (type === "scatter") {

            trace1.line = {
                color: _this.configs.first_line_color
            };

            trace2.line = {
                color: _this.configs.second_line_color
            };

            trace1.mode = 'lines+markers';
            trace2.mode = 'lines+markers';

        } else {

            trace2.marker = {
                color: _this.configs.second_line_color,
                line: {
                    color: _this.configs.second_line_color
                }
            };

            trace1.marker = {
                color: _this.configs.first_line_color,
                line: {
                    color: _this.configs.first_line_color
                }
            };
        }


        var $select1 = jQuery('#gawd_metric');

        trace1.name = $select1.find('option[value="' + $select1.val() + '"]').text();
        trace2.name = trace1.name + " compare";

        for (i = 0; i < chart_data.length; i++) {
            trace1.x.push(chart_data[i][x_key]);
            trace1.y.push(chart_data[i][y_key1]);
        }

        for (i = 0; i < chart_data_compare.length; i++) {
            trace2.x.push(chart_data_compare[i][x_key]);
            trace2.y.push(chart_data_compare[i][y_key1]);
        }

        var layout = {
            xaxis: {
                rangemode: 'nonnegative',
                type: "date",
                title: "Date",
                showline: true
            },
            yaxis: {
                rangemode: 'nonnegative',
                title: trace1.name,
                showline: true
            },
            xaxis2: {
                rangemode: 'nonnegative',
                type: "date",
                title: 'Compare Date',
                overlaying: 'x',
                side: 'top'
            },
            barmode: 'group'
        };

        if(layout.xaxis.type === "date" && trace1.x.length === 1){
            layout.xaxis.dtick = 60;
        }

        if(layout.xaxis2.type === "date" && trace2.x.length === 1){
            layout.xaxis2.dtick = 60;
        }

        var data = [trace1, trace2];
        var configs = {displayModeBar: false};

        Plotly.newPlot(div_id, data, layout, configs);

       //  var myPlot = document.getElementById(div_id);
       // // var hoverInfo = document.getElementById('hoverinfo');
       //
       //  myPlot.on('plotly_hover', function (data) {
       //
       //      var hover_layers = jQuery(Plotly.d3.selectAll(".hoverlayer"));
       //
       //      hover_layers.each(function () {
       //          jQuery(this).hide();
       //      });
       //
       //      var hovertext_container = jQuery(hover_layers.get(0));
       //      hovertext_container.show();
       //
       //      var text = "Date: " + data.points[0].x + "<br/>";
       //      text += "Value: " + data.points[0].y + "<br/>";
       //      text += "Date: " + data.points[1].x + "<br/>";
       //      text += "Value: " + data.points[1].y + "<br/>";
       //      hovertext_container.find('.hovertext').append(text);
       //
       //  }).on('plotly_unhover', function (data) {
       //      // hoverInfo.innerHTML = "";
       //  });

    };

    function first_letter_to_uppercase(str) {
        if (typeof str !== "string") {
            return str;
        }
        return str.replace(/\w\S*/g, function (txt) {
            return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
        });
    }
};