<?php
$get_custom_reports = get_option('gawd_custom_reports');
if(!isset($_GET['tab'])){
  $_GET['tab'] = 'general';
}
/*FREE*/
if($_GET['tab'] !== 'general' && $_GET['tab'] !== 'realtime'){
  $_GET['tab'] = 'general';
}

$tabs = get_option('gawd_menu_items');
$gawd_zoom_message = get_option('gawd_zoom_message');
$current_user = get_current_user_id();
$saved_user_menues = get_option('gawd_menu_for_user');
if($current_user != 1 && isset($saved_user_menues[$current_user])){
  $tabs = array_intersect_key($tabs, $saved_user_menues[$current_user]);
}

$line_column = array('general', 'siteSpeed', 'goals');
$pie_column = array(
  'userGender',
  'inMarket',
  'affinityCategory',
  'otherCategory',
  'location',
  'language',
  'behaviour',
  'os',
  'browser',
  'device_overview',
  'devices',
  'trafficSource',
);
$column_pie = array('userAge', 'engagement', 'adWords','eventsLabel', 'eventsAction', 'eventsCategory', 'productSku', 'productCategory', 'productName', 'custom');
$column = array();

if(in_array($_GET['tab'], $line_column)) {
  $chart_types = array('line' => 'Line Chart', 'column' => 'Columns');
} else if(in_array($_GET['tab'], $pie_column)) {
  $chart_types = array('pie' => 'Pie Chart', 'column' => 'Columns');
} else if(in_array($_GET['tab'], $column_pie)) {
  $chart_types = array('column' => 'Columns', 'pie' => 'Pie Chart');
} else if(in_array($_GET['tab'], $column)) {
  $chart_types = array('column' => 'Columns');
} else {
  $chart_types = array('line' => 'Line Chart', 'pie' => 'Pie Chart', 'column' => 'Columns');
}

?>
<form method="post" id="gawd_view">

<div class="gawd_profiles" id="gawd_profile_wrapper">
<?php if($gawd_zoom_message === false){
  ?>
<div class="gawd_zoom_message">
  <span>You can zoom chart by dragging the mouse over it</span><input class="button_gawd" type="button" id="gawd_got_it" value="GOT IT"/>
</div>
<?php
}
?>
  <select class="gawd_profile_select" id="gawd_id" name="gawd_id" onchange="change_account(this)">
    <?php
    $gawd_last_viewed_profile = GAWD_helper::get_last_viewed_profile();
    foreach ($profiles as $property_name => $property): ?>
    <optgroup label="<?php echo $property_name; ?>">
      <?php foreach ($property as $profile):
        $webPropertyId = $profile['webPropertyId'];
        $id = $profile['id'];
        $name = $profile['name'];
        $selected = '';
        if($id == $gawd_last_viewed_profile['profile_id']){
          $selected = 'selected="selected"';
        }
      ?>
      <option value="<?php echo $id; ?>" <?php echo $selected; ?>><?php echo $property_name.' - '.$name ; ?></option>
      <?php endforeach ?>
    </optgroup>
    <?php endforeach ?>
  </select>
  <div class="clear"></div>
  <input type="hidden" name='web_property_name' id='web_property_name'/>
</div>
<div id="gawd_body" data-gawd-tab="<?php echo esc_attr(stripslashes($_GET['tab'])); ?>">
  <?php
  include_once('dashboard_menu.php');
  $page = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';
  if(strpos($page,'custom_report')!==false){
    $tab = $page;
  }
  else{
       switch ($page) {
        case 'general':
          $tab = 'date';
          break;
        case 'location':
          $tab = 'country';
          break;
        case 'behaviour':
          $tab = 'userType';
          break;
        case 'engagement':
          $tab = 'sessionDurationBucket';
          break;
        case 'pagePath':
          $tab = 'pagePath';
          break;
        case 'landingPagePath':
          $tab = 'landingPagePath';
          break;
        case 'language':
          $tab = 'language';
          break;
        case 'browser':
          $tab = 'browser';
          break;
        case 'os':
          $tab = 'operatingSystem';
          break;
        case 'device_overview':
          $tab = 'deviceCategory';
          break;
        case 'devices':
          $tab = 'mobileDeviceInfo';
          break;
        case 'realtime':
          $tab = 'realTime';
          break;
        case 'custom':
          $tab = 'custom';
          break;
        case 'eventsCategory':
          $tab = 'eventCategory';
          break;
        case 'eventsAction':
          $tab = 'eventAction';
          break;
        case 'eventsLabel':
          $tab = 'eventLabel';
          break;
        case 'goals':
          $tab = 'goals';
          break;
        case 'userGender':
          $tab = 'userGender';
          break;
        case 'userAge':
          $tab = 'userAgeBracket';
          break;
        case 'adWords':
          $tab = 'adGroup';
          break;
        case 'otherCategory':
          $tab = 'interestOtherCategory';
          break;
        case 'affinityCategory':
          $tab = 'interestAffinityCategory';
          break;
        case 'inMarket':
          $tab = 'interestInMarketCategory';
          break;
        case 'trafficSource':
          $tab = 'source';
          break;
        case 'siteSpeed':
          $tab = 'siteSpeed';
          break;
        case 'adsense':
          $tab = 'adsense';
          break;
        case 'productName':
          $tab = 'productName';
          break;
        case 'productCategory':
          $tab = 'productCategory';
          break;
        case 'productSku':
          $tab = 'productSku';
          break;
        case 'transactionId':
          $tab = 'transactionId';
          break;
        case 'daysToTransaction':
          $tab = 'daysToTransaction';
          break;
        case 'sales_performance':
          $tab = 'sales_performance';
          break;
        default:
        if($tabs != ''){
          $tab = key($tabs);
        }
        else{
          $tab = 'date';
        }
          break;
      }
  }
  ?>
  <input id="gawd_filter_val" type="hidden" value="">
  <div class="resp_metrics_menu"><div class="menu_metrics_img"></div><div class="button_label">FILTERS</div><div class="clear"></div></div>
  <div id="gawd_right_conteiner">
    <h3 id="gawd_page_title">Audience</h3>
      <div class="filter_conteiner">
            <div id="metric_conteiner" class="float_conteiner">
              <div class="gawd_metrics">
                <?php
                if($tab == 'date'){
                  ?>
                <div id="first_metric" >
                  <select name="gawd_metric" id="gawd_metric" class="gawd_draw_analytics load_tooltip" data-hint="Choose a metric to view overview graph.">
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
                  <select name="gawd_metric_compare" id="gawd_metric_compare" class="gawd_draw_analytics load_tooltip" data-hint="Select the second metric to compare reports.">
                    <option value="0"><?php echo __('None', 'gawd'); ?></option>
                    <option value="sessions"><?php echo __('Sessions', 'gawd'); ?></option>
                    <option value="percentNewSessions"><?php echo __('% New Sessions', 'gawd'); ?></option>
                    <option value="users"  ><?php echo __('Users', 'gawd'); ?></option>
                    <option value="bounceRate"  ><?php echo __('Bounce Rate', 'gawd'); ?></option>
                    <option value="avgSessionDuration"  ><?php echo __('Avg Session Duration', 'gawd'); ?></option>
                    <option value="pageviews"  ><?php echo __('Pageviews', 'gawd'); ?></option>
                    <option value="pageviewsPerSession"  ><?php echo __('Pages/Session', 'gawd'); ?></option>
                  </select>
                  <img src="<?php echo GAWD_URL. '/assets/cleardot.gif';?>"/>
                </div>
                  <?php
                }
                elseif($tab == 'inMarket' || $tab == 'affinityCategory' || $tab == 'otherCategory' || $tab == 'country' || $tab == 'language' || $tab == 'userType' || $tab == 'sessionDurationBucket' || $tab == 'userAgeBracket' || $tab == 'userGender' || $tab == 'mobileDeviceInfo' || $tab == 'deviceCategory' || $tab == 'operatingSystem' || $tab == 'browser' || $tab =='interestInMarketCategory' || $tab == 'interestAffinityCategory' || $tab == 'interestOtherCategory' || $tab == 'source'){
                  ?>
                <div id="first_metric" >
                  <select name="gawd_metric" id="gawd_metric" class="gawd_draw_analytics load_tooltip" data-hint="Choose a metric to view overview graph.">
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
                  <select name="gawd_metric_compare" id="gawd_metric_compare" class="gawd_draw_analytics load_tooltip" data-hint="Select the second metric to compare reports.">
                    <option value="0"><?php echo __('None', 'gawd'); ?></option>
                    <option value="sessions"><?php echo __('Sessions', 'gawd'); ?></option>
                    <option value="percentNewSessions"><?php echo __('% New Sessions', 'gawd'); ?></option>
                    <option value="users"  ><?php echo __('Users', 'gawd'); ?></option>
                    <option value="bounceRate"  ><?php echo __('Bounce Rate', 'gawd'); ?></option>
                    <option value="avgSessionDuration"  ><?php echo __('Avg Session Duration', 'gawd'); ?></option>
                    <option value="pageviews"  ><?php echo __('Pageviews', 'gawd'); ?></option>
                    <option value="pageviewsPerSession"  ><?php echo __('Pages/Session', 'gawd'); ?></option>
                  </select>
                  <img src="<?php echo GAWD_URL. '/assets/cleardot.gif';?>"/>
                </div>
                  <?php
                }
                elseif($tab == 'eventLabel' || $tab == 'eventAction' || $tab == 'eventCategory'){
                  ?>
                <div id="first_metric" >
                  <select name="gawd_metric" id="gawd_metric" class="gawd_draw_analytics load_tooltip" data-hint="Choose a metric to view overview graph.">
                    <option value="totalEvents"  ><?php echo __('Total Events', 'gawd'); ?></option>
                    <option value="uniqueEvents"  ><?php echo __('Unique Events', 'gawd'); ?></option>
                    <option value="eventValue"  ><?php echo __('Event Value', 'gawd'); ?></option>
                    <option value="avgEventValue"  ><?php echo __('Average Event Value', 'gawd'); ?></option>
                    <option value="sessionsWithEvent"  ><?php echo __('Session with Event', 'gawd'); ?></option>
                    <option value="eventsPerSessionWithEvent"  ><?php echo __('Events per Session with Event ', 'gawd'); ?></option>
                  </select>
                </div>
                <div id="metric_compare">
                  <select name="gawd_metric_compare" id="gawd_metric_compare" class="gawd_draw_analytics load_tooltip" data-hint="Select the second metric to compare reports.">
                    <option value="0"><?php echo __('None', 'gawd'); ?></option>
                    <option value="totalEvents"  ><?php echo __('Total Events', 'gawd'); ?></option>
                    <option value="uniqueEvents"  ><?php echo __('Unique Events', 'gawd'); ?></option>
                    <option value="eventValue"  ><?php echo __('Event Value', 'gawd'); ?></option>
                    <option value="avgEventValue"  ><?php echo __('Average Event Value', 'gawd'); ?></option>
                    <option value="sessionsWithEvent"  ><?php echo __('Session with Event', 'gawd'); ?></option>
                    <option value="eventsPerSessionWithEvent"  ><?php echo __('Events per Session with Event ', 'gawd'); ?></option>
                  </select>
                  <img src="<?php echo GAWD_URL. '/assets/cleardot.gif';?>"/>
                </div>
                  <?php
                }
                elseif($tab == 'goals'){
                  ?>
                <div id="first_metric" >
                  <select name="gawd_metric" id="gawd_metric" class="gawd_draw_analytics load_tooltip" data-hint="Choose a metric to view overview graph.">
                  </select>
                </div>
                <div id="metric_compare">
                  <select name="gawd_metric_compare" id="gawd_metric_compare" class="gawd_draw_analytics load_tooltip" data-hint="Select the second metric to compare reports.">
                    <option value="0"><?php echo __('None', 'gawd'); ?></option>
                    <?php
                     /*   if (!empty($goals)) {
                            foreach ($goals as $goal) {
                                echo '<option value="' . $goal['name'] . '">' . $goal['name'] . ' (Goal' . $goal['id'] . ' Completions)' . '</option>';
                            }
                        }*/
                    ?>
                  </select>
                  <img src="<?php echo GAWD_URL. '/assets/cleardot.gif';?>"/>
                </div>
                  <?php
                }
                elseif($tab == 'siteSpeed'){
                  ?>
                <div id="first_metric" >
                  <select name="gawd_metric" id="gawd_metric" class="gawd_draw_analytics load_tooltip" data-hint="Choose a metric to view overview graph.">
                    <option value="avgPageLoadTime"  ><?php echo __('Avg. Page Load Time', 'gawd'); ?></option>
                    <option value="avgRedirectionTime"  ><?php echo __('Avg. Redirection Time', 'gawd'); ?></option>
                    <option value="avgServerResponseTime"  ><?php echo __('Avg. Server Response Time', 'gawd'); ?></option>
                    <option value="avgPageDownloadTime"  ><?php echo __('Avg. Page Download Time', 'gawd'); ?></option>
                  </select>
                </div>
                <div id="metric_compare">
                  <select name="gawd_metric_compare" id="gawd_metric_compare" class="gawd_draw_analytics load_tooltip" data-hint="Select the second metric to compare reports.">
                    <option value="0"><?php echo __('None', 'gawd'); ?></option>
                    <option value="avgPageLoadTime"  ><?php echo __('Avg. Page Load Time', 'gawd'); ?></option>
                    <option value="avgRedirectionTime"  ><?php echo __('Avg. Redirection Time', 'gawd'); ?></option>
                    <option value="avgServerResponseTime"  ><?php echo __('Avg. Server Response Time', 'gawd'); ?></option>
                    <option value="avgPageDownloadTime"  ><?php echo __('Avg. Page Download Time', 'gawd'); ?></option>
                  </select>
                  <img src="<?php echo GAWD_URL. '/assets/cleardot.gif';?>"/>
                </div>
                  <?php
                }
                elseif($tab == 'adsense'){
                  ?>
                <div id="first_metric" >
                  <select name="gawd_metric" id="gawd_metric" class="gawd_draw_analytics load_tooltip" data-hint="Choose a metric to view overview graph.">
                    <option value="adsenseRevenue"  ><?php echo __('AdSense Revenue', 'gawd'); ?></option>
                    <option value="adsenseAdsClicks"  ><?php echo __('AdSense Ads Clicked', 'gawd'); ?></option>
                  </select>
                </div>
                <div id="metric_compare">
                  <select name="gawd_metric_compare" id="gawd_metric_compare" class="gawd_draw_analytics load_tooltip" data-hint="Select the second metric to compare reports.">
                    <option value="0"><?php echo __('None', 'gawd'); ?></option>
                    <option value="adsenseRevenue"  ><?php echo __('AdSense Revenue', 'gawd'); ?></option>
                    <option value="adsenseAdsClicks"  ><?php echo __('AdSense Ads Clicked', 'gawd'); ?></option>
                  </select>
                  <img src="<?php echo GAWD_URL. '/assets/cleardot.gif';?>"/>
                </div>
                  <?php
                }
                elseif($tab == 'socialActivityNetworkAction' || $tab == 'socialActivityAction' || $tab == 'socialActivityTagsSummary' || $tab == 'socialActivityPost' || $tab == 'socialActivityTimestamp' || $tab == 'socialActivityUserProfileUrl' || $tab == 'socialActivityContentUrl' || $tab == 'socialActivityUserPhotoUrl' || $tab == 'socialActivityUserHandle' || $tab == 'socialActivityEndorsingUrl' || $tab == 'socialEndorsingUrl' || $tab == 'socialActivityDisplayName'){
                  ?>
                <div id="first_metric" >
                  <select name="gawd_metric" id="gawd_metric" class="gawd_draw_analytics load_tooltip" data-hint="Choose a metric to view overview graph.">
                    <option value="socialActivities"  ><?php echo __('Social Activity', 'gawd'); ?></option>
                  </select>
                </div>
                  <?php
                }
                elseif($tab == 'adGroup'){
                         ?>
                <div id="first_metric" >
                  <select name="gawd_metric" id="gawd_metric" class="gawd_draw_analytics load_tooltip" data-hint="Choose a metric to view overview graph.">
                    <option value="adClicks"  ><?php echo __('Clicks', 'gawd'); ?></option>
                    <option value="adCost"  ><?php echo __('Cost', 'gawd'); ?></option>
                  </select>
                </div>
                <div id="metric_compare">
                  <select name="gawd_metric_compare" id="gawd_metric_compare" class="gawd_draw_analytics load_tooltip" data-hint="Select the second metric to compare reports.">
                    <option value="0"><?php echo __('None', 'gawd'); ?></option>
                    <option value="adClicks"  ><?php echo __('Clicks', 'gawd'); ?></option>
                    <option value="adCost"  ><?php echo __('Cost', 'gawd'); ?></option>
                  </select>
                  <img src="<?php echo GAWD_URL. '/assets/cleardot.gif';?>"/>
                </div>
                  <?php
                }
                elseif($tab == 'productCategory' || $tab == 'productName' || $tab == 'productSku'){
                         ?>
                <div id="first_metric" >
                  <select name="gawd_metric" id="gawd_metric" class="gawd_draw_analytics load_tooltip" data-hint="Choose a metric to view overview graph.">
                    <option value="itemRevenue"  ><?php echo __('Revenue', 'gawd'); ?></option>
                    <option value="uniquePurchases"  ><?php echo __('Unique Purchases', 'gawd'); ?></option>
                    <option value="itemQuantity"  ><?php echo __('Quantity', 'gawd'); ?></option>
                    <option value="itemsPerPurchase"  ><?php echo __('Average QTY', 'gawd'); ?></option>
                  </select>
                </div>
                <div id="metric_compare">
                  <select name="gawd_metric_compare" id="gawd_metric_compare" class="gawd_draw_analytics load_tooltip" data-hint="Select the second metric to compare reports.">
                    <option value="0"><?php echo __('None', 'gawd'); ?></option>
                    <option value="itemRevenue"  ><?php echo __('Revenue', 'gawd'); ?></option>
                    <option value="uniquePurchases"  ><?php echo __('Unique Purchases', 'gawd'); ?></option>
                    <option value="itemQuantity"  ><?php echo __('Quantity', 'gawd'); ?></option>
                    <option value="itemsPerPurchase"  ><?php echo __('Average QTY', 'gawd'); ?></option>
                  </select>
                  <img src="<?php echo GAWD_URL. '/assets/cleardot.gif';?>"/>
                </div>
              <?php
                }
               elseif($tab == 'transactionId'){
                          ?>
                <div id="first_metric" >
                  <select name="gawd_metric" id="gawd_metric" class="gawd_draw_analytics load_tooltip" data-hint="Choose a metric to view overview graph.">
                    <option value="transactionRevenue"  ><?php echo __('Revenue', 'gawd'); ?></option>
                    <option value="transactionTax"  ><?php echo __('Tax', 'gawd'); ?></option>
                    <option value="transactionShipping"  ><?php echo __('Shipping', 'gawd'); ?></option>
                    <option value="itemQuantity"  ><?php echo __('Quantity', 'gawd'); ?></option>
                  </select>
                </div>
                <div id="metric_compare">
                  <select name="gawd_metric_compare" id="gawd_metric_compare" class="gawd_draw_analytics load_tooltip" data-hint="Select the second metric to compare reports.">
                    <option value="0"><?php echo __('None', 'gawd'); ?></option>
                    <option value="transactionRevenue"  ><?php echo __('Revenue', 'gawd'); ?></option>
                    <option value="transactionTax"  ><?php echo __('Tax', 'gawd'); ?></option>
                    <option value="transactionShipping"  ><?php echo __('Shipping', 'gawd'); ?></option>
                    <option value="itemQuantity"  ><?php echo __('Quantity', 'gawd'); ?></option>
                  </select>
                      <img src="<?php echo GAWD_URL. '/assets/cleardot.gif';?>"/>
                </div>
                  <?php
                }
                elseif($tab == 'sales_performance'){
                          ?>
                <div id="first_metric" >
                  <select name="gawd_metric" id="gawd_metric" class="gawd_draw_analytics load_tooltip" data-hint="Choose a metric to view overview graph.">
                    <option value="transactionRevenue"  ><?php echo __('Revenue', 'gawd'); ?></option>
                    <option value="transactionsPerSession"  ><?php echo __('Ecommerce Conversion Rate', 'gawd'); ?></option>
                  </select>
                </div>

                  <?php
                }
                 elseif($tab == 'daysToTransaction'){
                          ?>
                <div id="first_metric" >
                  <select name="gawd_metric" id="gawd_metric" class="gawd_draw_analytics load_tooltip" data-hint="Choose a metric to view overview graph.">
                    <option value="transactions"  ><?php echo __('Transactions', 'gawd'); ?></option>
                  </select>
                </div>

                  <?php
                }
                 elseif(strpos($tab,'custom_report')!==false){
                    $tab = substr($page,14);

                          ?>
                  <div id="first_metric" >
                    <select name="gawd_metric" id="gawd_metric" class="gawd_draw_analytics load_tooltip" data-hint="Choose a metric to view overview graph.">
                      <option value="<?php echo $get_custom_reports[$tab]['metric'];?>"  ><?php echo __(preg_replace('!\s+!',' ',trim(ucfirst(preg_replace('/([A-Z])/', ' $1', $get_custom_reports[$tab]['metric'])))), 'gawd'); ?></option>

                    </select>
                  </div>
                  <?php
                   $tab = 'custom_' . $get_custom_reports[$tab]['dimension'];
                }
                elseif($tab == 'custom') { ?>
                  <div id="first_metric" >
                    <select name="gawd_metric" id="gawd_metric" class="gawd_draw_analytics load_tooltip" data-hint="Choose a metric to view overview graph.">
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
                      <select style="display: none;" id="wdi_no_custom_dimensions"  class="load_tooltip" data-hint="Select the second metric to compare reports.">
                        <option value="0">There are no custom dimensions set for current profile.</option>
                      </select>
                      <select style="display: none;" name="gawd_custom_option" id="gawd_custom_option" class="gawd_draw_analytics">

                      </select>
                      <img src="<?php echo GAWD_URL. '/assets/cleardot.gif';?>"/>
                  </div>
            <?php } ?>
              </div>

                <input id="gawd_tab" type="hidden" value="<?php echo $tab; ?>">

              <?php if($tab != 'custom' && $tab != 'pagePath' && $tab != 'landingPagePath' && $tab != 'realTime' && $tab != 'daysToTransaction' && $tab != 'sales_performance' && strpos($_GET['tab'],'custom_report') === false) { ?>
              <div class="vs_image" class="gawd_metrics">
                <img width="30px" src="<?php echo GAWD_URL;?>/assets/vs.png">
              </div>
              <?php } ?>
              <div class='clear'></div>
            </div>
          <?php if($tab != 'realTime') { ?>
            <div id="date_chart_conteiner" class="float_conteiner">
              <div class="gawd_row load_tooltip" data-hint="Choose Line, Pie or Column chart type to view your Google Analytics report with.">
                <div id="gawd_text" class="gawd_text">
                  CHART
                </div>
                <div class="gawd_content" id="gawd_content_chart" >
                  <select name="gawd_chart_type" id="gawd_chart_type" class="gawd_draw_analytics">
                    <?php foreach($chart_types as $value => $title) { ?>
                        <option value="<?php echo $value; ?>"><?php echo $title; ?></option>
                    <?php } ?>
                  </select>
                </div>
                <div class='clear'></div>
              </div >
              <div class="gawd_row load_tooltip" data-hint="Select one of predefined date ranges or specify a custom period for your report.">
                <div class="gawd_text" >
                    DATE
                </div>
                <div class="gawd_content" id="gawd_content_range" >
                  <div id="reportrange" class="pull-right" style="float:none !important">
                    <span></span> <b class="caret"></b>
                  </div>
                  <input type="hidden" id="gawd_start_end_date"/>
                </div>
                <div class='clear'></div>
              </div>
                <div class='clear'></div>
            </div>
           <?php } ?>
            <div id="compare_time_conteiner" class="float_conteiner">
              <?php if(($tab == 'date') || ($tab == 'adsense') || ($tab == 'siteSpeed') || $tab == 'sales_performance' || $tab == 'pagePath' || $tab == 'landingPagePath'){ ?>
              <div class="gawd_date_filter_container load_tooltip" data-hint="Set the scale of your statistics graph. It will separate graph results hourly, daily, weekly and monthly.">
                <ul class="gawd_list">
                  <li class="gawd_list_item" id="gawd_hour"><a href='#'  class="gawd_filter_item" data-type="hour">Hourly</a></li>
                  <li class="gawd_list_item" id="gawd_day"><a href='#'  class="gawd_filter_item" data-type="date">Day</a></li>
                  <li class="gawd_list_item" id="gawd_week"><a href='#'  class="gawd_filter_item" data-type="week">Week</a></li>
                  <li class="gawd_list_item" id="gawd_month"><a href='#'  class="gawd_filter_item" data-type="month">Month</a></li>
                </ul>
              </div>
              <div id="compare_datepicker_wraper" class="load_tooltip" data-hint="Measure the results of Google Analytics tracking of two periods. Select Previous Period, Previous Year, or define a custom period using the datepicker.">COMPARE DATE</div>
                <div id="" class="pull-right" style="float:none !important">
                </div>
                <input type="hidden" id="gawd_start_end_date_compare"/>
              <div class='clear'></div>
              <?php } ?>
            </div>
            <div class='clear'></div>
      </div>
      <?php wp_nonce_field('gawd_save_form', 'gawd_save_form_fild'); ?>
    </form>

    <div class="gawd_chart_conteiner">
      <?php if($_GET['tab'] === 'adsense') { ?>
          <h4>
              AdSense reports are coming soon. View them
              <a target="_blank" href="https://analytics.google.com/">
                  here.
              </a>
          </h4>
      <?php } ?>
      <div id="opacity_div" style="display: none; background-color: rgba(0, 0, 0, 0.2); position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 99998;"></div>
      <div id="loading_div" style="display:none; text-align: center; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 99999;">
        <img src="<?php echo GAWD_URL . '/assets/ajax_loader.gif'; ?>"  style="margin-top: 200px; width:50px;">
      </div>
      <div id="chartdiv"></div>
      <?php if($tab != 'realTime'){
        ?>
      <div id="gawd_buttons">
        <span id="country_filter_reset" class="button_gawd">Back</span>
        <span class='clear'></span>
        <input class="button_gawd" type="button" id="gawd_export_buttons"   value="Export"/>
        <input class="button_gawd load_tooltip" type="button" id="gawd_email_button" data-hint="Click to forward this report to selected email recipients, or schedule emails to be sent out periodically."  value="Email"/>
        <div class="gawd_exports">
          <a class='button gawd_export_button_csv' href="">CSV</a>
          <a class='button gawd_export_button_pdf' href="#">PDF</a>
        </div>
      </div>
      <?php };?>
      <div class="gawd_data_table">
        <table class="display cell-border" style="width:100%">

        </table>
      </div>
    </div>
  </div>
  <div class='clear'></div>

</div>
<?php //get parameters for export?>

<div class="gawd_email_popup_overlay">
</div>
<div class="gawd_email_popup">
  <a href="#" class="gawd_btn">X</a>
  <div class="gawd_email_body">
    <form method="post" action="" id="gawd_email_form">
        <div class="gawd_email_row load_tooltip" data-hint="E-mail to send reports from. You can change it from WordPress Settings > General > Email Address.">
          <div class="gawd_email_label">From</div>
          <div class="gawd_email_input gawd_email_input_from">
            <?php echo get_option('admin_email'); ?>
          </div>
          <div class='clear'></div>
        </div>
        <div class="gawd_email_row load_tooltip" data-hint="Define one or more email report recipients separated by commas.">
          <div class="gawd_email_label">To</div>
          <div class="gawd_email_input">
            <input id="gawd_email_to" name="gawd_email_to" class="" type="text" value="">
          </div>
          <div class='clear'></div>
        </div>
        <div class="gawd_email_row load_tooltip" data-hint="Set the subject for email reports.">
          <div class="gawd_email_label">Subject</div>
          <div class="gawd_email_input">
            <input class="gawd_email_subject" name="gawd_email_subject" class="" type="text" value="">
          </div>
          <div class='clear'></div>
        </div>
        <div class="gawd_email_row">
          <div class="gawd_email_attachemnt load_tooltip" data-hint="Select type for report attachments, CSV or PDF.">Attachment</div>
          <div class="gawd_email_input_attachment" >
            <select id="gawd_attachment_type" name="export_type">
              <option value='csv'>CSV</option>
<!--              <option value='pdf'>PDF</option>-->
            </select>
          </div>
          <div class="gawd_email_input gawd_email_month_day_div" id="gawd_email_month_day" data-hint="Select the day of month to send report on.">
            <div class="gawd_email_day_of_week">Day of Month</div>
          </div>
          <div class="gawd_email_input gawd_email_week_day_div" data-hint="Click on weekday to choose email report sending day." id="gawd_email_week_day">
            <div class="gawd_email_day_of_week">Day of Week</div>
            <input type="hidden" name="gawd_email_week_day" id="gawd_email_week_day_hidden" >
          </div>
          <div class='clear'></div>
        </div>
        <div class="gawd_email_row">
          <div class="gawd_email_frequency load_tooltip" data-hint="Send email report Once or set its frequency to Daily, Weekly or Monthly.">Frequency</div>
          <div class="gawd_email_input_frequency " id="gawd_email_period">
            <select  name="gawd_email_period">
              <option value="once">Once</option>
              <option value="daily">Daily</option>
              <option value="gawd_weekly">Weekly</option>
              <option value="gawd_monthly">Monthly</option>
            </select>
          </div>
          <div class="gawd_email_input gawd_email_month_day_div" id="gawd_email_month_day">
            <div class="gawd_email_month_day">
               <select id="gawd_email_month_day_select"  name="gawd_email_month_day">
                <?php
                  for($i=1; $i<29; $i++){

                   echo '<option value="'.$i.'">'.$i.'</option>';
                  }
                ?>
                <option value="last">Last Day</option>
              </select>
            </div>
            <div class='clear'></div>
          </div>
          <div class="gawd_email_input gawd_email_week_day_div" id="gawd_email_week_day">
            <div class="gawd_email_week_days">
              <ul class="gawd_email_week_day_ul">
                <li class="gawd_email_week_day" data-atribute="sunday">Sun</li>
                <li class="gawd_email_week_day" data-atribute="monday">Mon</li>
                <li class="gawd_email_week_day" data-atribute="tuesday">Tue</li>
                <li class="gawd_email_week_day" data-atribute="wednesday">Wed</li>
                <li class="gawd_email_week_day" data-atribute="thursday">Thu</li>
                <li class="gawd_email_week_day" data-atribute="friday">Fri</li>
                <li class="gawd_email_week_day" data-atribute="saturday">Sat</li>
              </ul>
            </div>
            <input type="hidden" name="gawd_email_week_day" id="gawd_email_week_day_hidden" >
          </div>
          <div class='clear'></div>
        </div>
        <div class="gawd_email_row gawd_email_time_row">
          <div class="gawd_email_time load_tooltip" data-hint="Select the time, when you would like to receive this email.">Time</div>
          <div class="gawd_email_input_time" id="gawd_email_time">
            <input type="text" name="gawd_email_time_input" id="gawd_email_time_input" value="<?php echo date('H:i');?>"/>
          </div>

          <div class='clear'></div>
        </div>
        <div class="gawd_email_row gawd_email_message_label" data-hint="Compose email content to be sent with your report.">
          Additional Message
        </div>
        <div class="gawd_email_row gawd_email_message">
            <textarea name="gawd_email_body" id="gawd_email_body"></textarea>
        </div>
      <input name="gawd_email_from" id="gawd_email_from" class="" type="hidden" value="<?php echo get_option('admin_email'); ?>">
      <input name="gawd_metric" id="gawd_email_metric" class="" type="hidden" value="">
      <input name="gawd_metric_compare" id="gawd_metric_compare" class="" type="hidden" value="">
      <input name="gawd_dimension" id="gawd_dimension" class="" type="hidden" value="<?php echo $tab; ?>">
      <input name="gawd_start_date" id="gawd_start_date" class="" type="hidden" value="">
      <input name="gawd_end_date" id="gawd_end_date" class="" type="hidden" value="">
      <input name="action" id="" class="" type="hidden" value="gawd_export">
      <input name="report_type" id="report_type" class="" type="hidden" value="email">
      <div class="gawd_email_send" id="email_submit">
        Send
      </div>
      <?php wp_nonce_field('gawd_save_form', 'gawd_save_form_fild'); ?>
    </form>
  </div>
  <div class="email_message_cont"></div>
</div>
<canvas id='canvass' style="display:none"></canvas>
<input  id="first_data" class="" type="hidden" value="">
<input  id="second_data" class="" type="hidden" value="">
<input  id="dimension" class="" type="hidden" value="">
<input  id="first_data_sum" class="" type="hidden" value="">
<input  id="second_data_sum" class="" type="hidden" value="">
<input  id="second_start_date" class="" type="hidden" value="">
<input  id="second_end_date" class="" type="hidden" value="">
<?php
GAWD_helper::print_pro_popup();
?>