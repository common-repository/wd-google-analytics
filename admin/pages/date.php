<div class="gawd_wrapper">
  <div class="opacity_div_compact">  </div>
    <div class="loading_div_compact">
      <img src="<?php echo GAWD_URL . '/assets/ajax_loader.gif'; ?>"  style="margin-top: 200px; width:50px;">
    </div>
    <div style="float:left">
        <div id="first_metric" >
            <select class="gawd_compact_metric_change" name="gawd_metric_compact" id="gawd_metric_compact" >
                <option value="sessions"><?php echo __('Sessions', 'gawd'); ?></option>
                <option value="percentNewSessions"><?php echo __('% New Sessions', 'gawd'); ?></option>
                <option value="users"  ><?php echo __('Users', 'gawd'); ?></option>
                <option value="bounceRate"  ><?php echo __('Bounce Rate', 'gawd'); ?></option>
                <option value="avgSessionDuration"  ><?php echo __('Avg Session Duration', 'gawd'); ?></option>
                <option value="pageviews"  ><?php echo __('Pageviews', 'gawd'); ?></option>
                <option value="pageviewsPerSession"  ><?php echo __('Pages/Session', 'gawd'); ?></option>
            </select>
        </div>
        <div id="metric_compare">
            <select class="gawd_compact_metric_change" name="gawd_metric_compare" id="gawd_metric_compare_compact">
                <option value="users"  ><?php echo __('Users', 'gawd'); ?></option>
                <option value="sessions"><?php echo __('Sessions', 'gawd'); ?></option>
                <option value="percentNewSessions"><?php echo __('% New Sessions', 'gawd'); ?></option>
                <option value="bounceRate"  ><?php echo __('Bounce Rate', 'gawd'); ?></option>
                <option value="avgSessionDuration"  ><?php echo __('Avg Session Duration', 'gawd'); ?></option>
                <option value="pageviews"  ><?php echo __('Pageviews', 'gawd'); ?></option>
                <option value="pageviewsPerSession"  ><?php echo __('Pages/Session', 'gawd'); ?></option>
                <option value="0"><?php echo __('None', 'gawd'); ?></option>
            </select>
        </div>
    </div>
    <div style="float:left;margin:16px 0 0 0 " class="vs_image" class="gawd_metrics">
        <img width="30px" src="<?php echo GAWD_URL; ?>/assets/vs.png">
    </div>
        <div class="clear"></div>

    <div id="gawd_date_meta"></div>
</div> 
<script>
jQuery(document).ready(function(){
    hide_same_metric(jQuery('#gawd_metric_compare_compact'));
    hide_same_metric(jQuery('#gawd_metric_compact'));
    var _end_date = (Date.today().add(-1).days()).toString("yyyy-MM-dd");
    var start_date_7 = (Date.today().add(-1).days()).add(-7).days().toString("yyyy-MM-dd");
    var start_end_date = typeof jQuery('#gawd_start_end_date_compact').val() != 'undefined' ? jQuery('#gawd_start_end_date_compact').val() : start_date_7 + '/-/' + _end_date;

        var start_end_date = start_end_date.split('/-/');
        var start_date = start_end_date[0];
        var end_date = start_end_date[1];
        jQuery('#gawd_metric_compact').on('change', function () {
            hide_same_metric(this);
            gawd_draw_analytics_compact(jQuery('#gawd_metric_compact').val(), jQuery('#gawd_metric_compare_compact').val(), 'date', 'line', 'gawd_date_meta');
        })
        jQuery('#gawd_metric_compare_compact').on('change', function () {
            hide_same_metric(this);
            gawd_draw_analytics_compact(jQuery('#gawd_metric_compact').val(), jQuery('#gawd_metric_compare_compact').val(), 'date', 'line', 'gawd_date_meta');
        })
        //gawd_draw_analytics_compact('sessions', 'users', 'date', 'line', start_date, end_date, 'gawd_date_meta');

    });
</script>