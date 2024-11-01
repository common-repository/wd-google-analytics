<?php

// Exit if accessed directly.

if (!defined('ABSPATH')) {
    exit;
}

class GAWD_Widget extends WP_Widget {
    private $Uwidget = 0;
    public function __construct() {

        parent::__construct(
                false, $name = __('Google Anyalytics WD', 'gawd'), array('description' => __('Google Anyalytics WD', 'gawd'))
        );
        
    }

    
  public function widget($args, $instance) {
    extract($args);
    echo $before_widget;
    $gawd_widget_title = $instance["gawd_widget_title"];
    $gawd_widget_default_date = $instance["gawd_widget_default_date"];


    switch ($gawd_widget_default_date) {
      case 'all_days':
        $gawd_widget_date = "All Time";
        break;
      case 'last_30days':
        $gawd_widget_date = "Last 30 Days";
        break;
      case 'last_7days':
        $gawd_widget_date = "Last 7 Days";
        break;
      case 'last_week':
        $gawd_widget_date = "Last Week";
        break;
      case 'this_month':
        $gawd_widget_date = "This Month";
        break;
      case 'last_month':
        $gawd_widget_date = "Last Month";
        break;
      case 'today':
        $gawd_widget_date = "Today";
        break;
      case 'yesterday':
        $gawd_widget_date = "Yesterday";
        break;
      default:
        $gawd_widget_date = "Last 7 Days";
        break;
    }

      ?>
        <script>
          jQuery(document).ready(function () {
            var gawd_location_href = window.location.href;
            var gawd_location_hash = window.location.hash;
            var uri = gawd_location_href.replace(gawd_location_hash, "");
            var gawd_data = <?php echo json_encode($instance)?>;

            gawd_data.gawd_location_href = uri;
            var gawd_widget_args = {
              'url': "<?php echo admin_url('admin-ajax.php'); ?>",
              'type': "GET",
              'dataType': 'json',
              'async': true,
              'data': {
                'gawd_ajax': '1',
                'gawd_nonce': "<?php echo wp_create_nonce("gawd_custom_ajax"); ?>",
                'gawd_nonce_data': {
                  'action':'gawd_widget_view',
                  'nonce': "<?php echo wp_create_nonce('gawd_widget_view'); ?>"
                },
                'gawd_action': "get_widget_data",
                'gawd_data': gawd_data,
              },
              success: function (data) {
                  if(data.success === false){
                      return;
                  }
                var result = JSON.parse(data.data.gawd_page_post_data);
                if(typeof result.data_sum.sessions != "undefined"){
                  jQuery(".gawd_sessions_cont_numeric").html(result.data_sum.sessions);
                  jQuery(".gawd_widget_data").css({
                    "display":'block'
                  });
                }
              },
              error: function (data) {

              }
            };
            jQuery.ajax(gawd_widget_args);
          });
        </script>

        <div style="display: none;" class="gawd_widget_data">
          <h3 class="gawd_widget_title"><?php echo $gawd_widget_title;?></h3>
          <div class="gawd_widget_container" id="gawd_num_cont" style="height:auto; text-align:center; border: 1px solid #000; background: #fff; color:#000">
            <div class='gawd_sessions_cont gawd_sessions_cont_numeric'></div>
            <div class='gawd_sessions_cont'>Sessions <?php echo $gawd_widget_date;?></div>
          </div>
        </div>
      <?php

    echo $after_widget;
    $this->Uwidget++;
	}

  // update
    public function update($new_instance, $old_instance) {
      $instance = $old_instance;
      $instance['gawd_widget_title'] = ( $new_instance['gawd_widget_title'] );
      $instance['gawd_widget_default_date'] = ( $new_instance['gawd_widget_default_date'] );
      $instance['gawd_widget_report_data'] = ( $new_instance['gawd_widget_report_data'] );

      return $instance;
    }
	// admin form
   	public function form( $instance ) {
      $gawd_widget_title = isset($instance[ 'gawd_widget_title' ]) ? $instance[ 'gawd_widget_title' ] : "";
      $gawd_widget_default_date = isset($instance[ 'gawd_widget_default_date' ]) ? $instance[ 'gawd_widget_default_date' ] : "last_7days";
      $gawd_widget_report_data = isset($instance[ 'gawd_widget_report_data' ]) ? $instance[ 'gawd_widget_report_data' ] : "site_data";
      ?>
      <p>
        <label for="<?php echo $this->get_field_id( 'gawd_widget_title' ); ?>"><?php _e( 'Title', "gawd" ); ?>:</label>
        <input class="widefat" id="<?php echo $this->get_field_id( 'gawd_widget_title' ); ?>" name="<?php echo $this->get_field_name( 'gawd_widget_title' ); ?>" type="text" value="<?php echo esc_attr( $gawd_widget_title ); ?>">
      </p>
      <p>
        <label for="<?php echo $this->get_field_id( 'gawd_widget_default_date' ); ?>"><?php _e( 'Date Range', "gawd" ); ?>:</label>
        <select style="width: 100%" name="<?php echo $this->get_field_name( 'gawd_widget_default_date');?>" id="<?php echo $this->get_field_id( 'gawd_widget_default_date' ); ?>">
          <option id='gawd_all_days' <?php selected($gawd_widget_default_date,'all_days');?> value="all_days"><?php _e('All Time', "gawd" ); ?></option>
          <option id='gawd_last_30days' <?php selected($gawd_widget_default_date,'last_30days');?> value="last_30days"><?php _e('Last 30 Days', "gawd" ); ?></option>
          <option id='gawd_last_7days' <?php selected($gawd_widget_default_date,'last_7days');?> value="last_7days"><?php _e('Last 7 Days', "gawd" ); ?></option>
          <option id='gawd_last_week' <?php selected($gawd_widget_default_date,'last_week');?> value="last_week"><?php _e('Last Week', "gawd" ); ?></option>
          <option id='gawd_this_month' <?php selected($gawd_widget_default_date,'this_month');?> value="this_month"><?php _e('This Month', "gawd" ); ?></option>
          <option id='gawd_last_month' <?php selected($gawd_widget_default_date,'last_month');?> value="last_month"><?php _e('Last Month', "gawd" ); ?></option>
          <option id='gawd_yesterday' <?php selected($gawd_widget_default_date,'yesterday');?> value="yesterday"><?php _e('Yesterday', "gawd" ); ?></option>
          <option id='gawd_today' <?php selected($gawd_widget_default_date,'today');?> value="today"><?php _e('Today', "gawd" ); ?></option>
        </select>
      </p>
      <p>
        <label for="<?php echo $this->get_field_id( 'gawd_widget_report_data' ); ?>"><?php _e( 'Report Data', "gawd" ); ?>:</label>
        <select style="width: 100%" name="<?php echo $this->get_field_name( 'gawd_widget_report_data');?>" id="<?php echo $this->get_field_id( 'gawd_widget_report_data' ); ?>">
          <option id='gawd_site_data' <?php selected($gawd_widget_report_data,'site_data');?> value="site_data"><?php _e('Site data', "gawd" ); ?></option>
          <option id='gawd_current_url_data' <?php selected($gawd_widget_report_data,'current_url_data');?> value="current_url_data"><?php _e('Current URL data', "gawd" ); ?></option>
        </select>
      </p>
      <?php
    }


}

add_action('widgets_init', 'GAWD_Widget_Init');  


function GAWD_Widget_Init(){
	register_widget("GAWD_Widget");
}

