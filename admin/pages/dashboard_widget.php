<form method="post" id="gawd_dashboard_wp">
    <select title="Click to pick the website, audience report of which you’d like to display."
            style="width: 41%; margin-right: 20px;"
            class="gawd_profile_select"
            name="gawd_id"
            onchange="gawd_wp_dashboard_change_account(this)">
      <?php foreach($profiles as $property_name => $property): ?>
          <optgroup label="<?php echo $property_name; ?>">
            <?php foreach($property as $profile):
              $webPropertyId = $profile['webPropertyId'];
              $id = $profile['id'];
              $name = $profile['name'];
              $selected = '';
              if($id == $gawd_last_viewed_profile['profile_id']) {
                $selected = 'selected="selected"';
              }
              ?>
                <option value="<?php echo $id; ?>" <?php echo $selected; ?>><?php echo $property_name . ' - ' . $name; ?></option>
            <?php endforeach ?>
          </optgroup>
      <?php endforeach ?>
    </select>
    <input type="hidden" name='web_property_name' id='web_property_name'/>
    <select name="gawd_widget_date" id="gawd_widget_date">
        <option value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>"><?php echo __('Last 7 Days', 'gawd'); ?></option>
        <option value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>"><?php echo __('Last 30 Days', 'gawd'); ?></option>
        <option value="realTime"><?php echo __('Real Time', 'gawd'); ?></option>
    </select>
    <select name="gawd_metric_widget" id="gawd_metric_widget">
        <option value="sessions"><?php echo __('Sessions', 'gawd'); ?></option>
        <option value="users"><?php echo __('Users', 'gawd'); ?></option>
        <option value="bounceRate"><?php echo __('Bounce Rate', 'gawd'); ?></option>
        <option value="pageviews"><?php echo __('Pageviews', 'gawd'); ?></option>
        <option value="percentNewSessions">% New Sessions</option>
        <option value="avgSessionDuration">Avg Session Duration</option>
        <option value="pageviewsPerSession"><?php echo __('Pages/Session', 'gawd'); ?></option>
    </select>
</form>
<div id="gawd_wp_dashboard_chart_container">
    <div class="gawd_opacity_div_compact">
        <div class="gawd_loading_div_compact">
            <img src="<?php echo GAWD_URL . '/assets/ajax_loader.gif'; ?>" style="margin-top: 200px; width:50px;">
        </div>
    </div>
    <div id="gawd_wp_dashboard_chart_wrapper"></div>
</div>
<script>
    function gawd_wp_dashboard_change_account(that) {
        jQuery('#web_property_name').val(jQuery(that).find(':selected').closest('optgroup').attr('label'));
        jQuery('#gawd_dashboard_wp').submit();
    }

    jQuery(document).ready(function () {
        var wp_dashboard_chart = new gawd_wp_dashboard_charts();
        wp_dashboard_chart.init();
    });
</script>