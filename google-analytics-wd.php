<?php
/**
 * Plugin Name:     10WebAnalytics
 * Plugin URI:      https://10web.io/plugins/wordpress-google-analytics/
 * Version:         1.2.12
 * Author:          10Web
 * Author URI:      https://10web.io/plugins/
 * License:         GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

require_once "config.php";

if(version_compare(PHP_VERSION, '5.4.0') >= 0) {


  require_once 'library/gawd-logs.php';
  require_once 'library/gawd-helper-class.php';


  add_action('pre_update_option_gawd_user_data', array('GAWD_helper', 'gawd_user_data_updated'), 3, 10);

  if(isset($_REQUEST['gawd_ajax']) && $_REQUEST['gawd_ajax'] === '1') {

    require_once('library/gawd-custom-ajax-class.php');
    require_once('library/gawd-exception-handler.php');
    $gawd_ajax = new GAWD_custom_ajax_class();

  } else {
    add_action("init", "gawd_web_init", 9);

    require_once('gawd_class.php');
    add_action('plugins_loaded', array('GAWD', 'get_instance'));

    register_activation_hook(__FILE__, array('GAWD', 'global_activate'));
    register_deactivation_hook(__FILE__, array('GAWD', 'deactivate'));
  }
} else {
  add_action('admin_notices', 'gawd_php_version_admin_notice');
}

function gawd_php_version_admin_notice(){
  ?>
    <div class="notice notice-error">
        <h3>10Web Analytics</h3>
        <p><?php _e('This version of the plugin uses the latest Google library requiring PHP 5.4.0 or higher.', 'gawd'); ?></p>
        <p><?php _e('We recommend you to update PHP or ask your hosting provider to do that. ', 'gawd');
          printf(__('If it is not possible and you previously had 10Web Analytics Premium version installed, please ask 10Web to send you an older version of the plugin supporting PHP 5.3 %s', 'gawd'),
            '<a href="https://help.10web.io/hc/en-us/requests/new" target="_blank">' . __('Contact us', 'gawd') . '</a>'
          ); ?></p>

    </div>
  <?php
}

function gawd_web_init() {
  if (is_admin() && !isset($_REQUEST['ajax'])) {
    global $gawd_options;
    if ( !class_exists("TenWebLib") ) {
      $plugin_dir = apply_filters('tenweb_free_users_lib_path', array(
        'version' => '1.1.1',
        'path' => GAWD_DIR,
      ));
      require_once($plugin_dir['path'] . '/wd/start.php');
    }
    $gawd_options = array(
      "prefix" => "gawd",
      "wd_plugin_id" => 158,
      "plugin_id" => 45,
      "plugin_title" => "10Web Analytics",
      "plugin_wordpress_slug" => "wd-google-analytics",
      "plugin_dir" => GAWD_DIR,
      "plugin_main_file" => __FILE__,
      "description" => __('Analytics by 10Web WordPress plugin - a certified member of Google Analytics Technology Partners Program.
With a large number of detailed and user-friendly reports, 10Web Analytics plugin is just the right choice for you!', 'gawd'),
      "addons" => NULL,
      "plugin_features" => array(
        0 => array(
          "title" => __("Tracking Code and Options", "gawd"),
          "description" => __("You can add Google Analytics tracking to your website using this plugin. Various options let you configure desired tracking settings. You can also exclude traffic from certain users, role types, IP address, country, city or region.", "gawd"),
        ),
        1 => array(
          "title" => __("All Analytics Reports", "gawd"),
          "description" => __("10Web Analytics provides various reports, including Age, Gender, Demographics and Interests, Behavior and Technology, as well as Ecommerce tracking, Custom Dimensions and Custom Reports. Just as in Google Analytics, you can compare tracking reports of two metrics with elegantly designed Line, Pie and Column charts.", "gawd"),
        ),
        2 => array(
          "title" => __("Page and Post Reports", "gawd"),
          "description" => __("Each of your publications can have their own reports of Google Analytics. This lets authors to keep track of sessions on their posts and pages, giving full information about user activities.", "gawd"),
        ),
        3 => array(
          "title" => __("Frontend Reports", "gawd"),
          "description" => __("Permit Editors, Authors or other user roles to check page or post statistics, while reviewing content from the frontend of your website. A quick report will provide key information about activities within that particular post.", "gawd"),
        ),
        4 => array(
          "title" => __("Ecommerce Tracking", "gawd"),
          "description" => __("You can check revenues and performance of sales of your online shop. The plugin lets you keep up with product or category tracking, as well as product SKU and transaction IDs.", "gawd"),
        ),
      ),
      "user_guide" => array(
        0 => array(
          "main_title" => __("Installing 10Web Analytics", "gawd"),
          "url" => "https://help.10web.io/hc/en-us/articles/360017502592-Introducing-WordPress-Google-Analytics",
          "titles" => array(),
        ),
        1 => array(
          "main_title" => __("Quick Start", "gawd"),
          "url" => "https://help.10web.io/hc/en-us/articles/360017505232-Authenticating-Google-Analytics",
          "titles" => array(
            array(
              "title" => __("Overview", "gawd"),
              "url" => "https://help.10web.io/hc/en-us/articles/360017502592-Introducing-WordPress-Google-Analytics",
            ),
          ),
        ),
        2 => array(
          "main_title" => __("Report Types", "gawd"),
          "url" => "https://help.10web.io/hc/en-us/articles/360017506312-Google-Analytics-Reports",
          "titles" => array(),
        ),
        3 => array(
          "main_title" => __("Global description", "gawd"),
          "url" => "https://help.10web.io/hc/en-us/articles/360017506312-Google-Analytics-Reports",
          "titles" => array(
            array(
              "title" => __("Metrics", "gawd"),
              "url" => "https://help.10web.io/hc/en-us/articles/360017506312-Google-Analytics-Reports",
            ),
            array(
              "title" => __("Charts", "gawd"),
              "url" => "https://help.10web.io/hc/en-us/articles/360017506312-Google-Analytics-Reports",
            ),
            array(
              "title" => __("Date range", "gawd"),
              "url" => "https://help.10web.io/hc/en-us/articles/360017506312-Google-Analytics-Reports",
            ),
            array(
              "title" => __("Compare Date", "gawd"),
              "url" => "https://help.10web.io/hc/en-us/articles/360017506312-Google-Analytics-Reports",
            ),
            array(
              "title" => __("Export and Email", "gawd"),
              "url" => "https://help.10web.io/hc/en-us/articles/360017506312-Google-Analytics-Reports",
            ),
          ),
        ),
        4 => array(
          "main_title" => __("Settings", "gawd"),
          "url" => "https://help.10web.io/hc/en-us/articles/360018132671-Google-Analytics-Settings",
          "titles" => array(
            array(
              "title" => __("Use your own project", "gawd"),
              "url" => "https://help.10web.io/hc/en-us/articles/360018132771-Using-Your-Own-Project",
            ),
            array(
              "title" => __("Alerts & Pushover", "gawd"),
              "url" => "https://web-dorado.com/wordpress-google-analytics/settings/alerts-pushover.html",
            ),
            array(
              "title" => __("Filters", "gawd"),
              "url" => "https://web-dorado.com/wordpress-google-analytics/settings/filters.html",
            ),
            array(
              "title" => __("Emails", "gawd"),
              "url" => "https://web-dorado.com/wordpress-google-analytics/settings/emails.html",
            ),
            array(
              "title" => __("Advanced", "gawd"),
              "url" => "https://web-dorado.com/wordpress-google-analytics/settings/advanced.html",
            ),
            array(
              "title" => __("AdSense and AdWords account linking", "gawd"),
              "url" => "https://web-dorado.com/wordpress-google-analytics/settings/adsense-and-adwords-account-linking.html",
            ),
          ),
        ),
        5 => array(
          "main_title" => __("Tracking", "gawd"),
          "url" => "https://web-dorado.com/wordpress-google-analytics/tracking.html",
          "titles" => array(
            array(
              "title" => __("Custom Dimensions", "gawd"),
              "url" => "https://web-dorado.com/wordpress-google-analytics/tracking/custom-dimensions.html",
            ),
            array(
              "title" => __("Exclude", "gawd"),
              "url" => "https://web-dorado.com/wordpress-google-analytics/tracking/exclude.html",
            ),
          ),
        ),
        6 => array(
          "main_title" => __("Goal Management", "gawd"),
          "url" => "https://web-dorado.com/wordpress-google-analytics/goal-management.html",
          "titles" => array(),
        ),
        7 => array(
          "main_title" => __("Custom Reports", "gawd"),
          "url" => "https://help.10web.io/hc/en-us/articles/360018133271-Custom-Reports",
          "titles" => array(),
        ),
      ),
      "video_youtube_id" => "n1f7ECVFNPI",
      "plugin_wd_url" => "https://10web.io/plugins/wordpress-google-analytics/?utm_source=10web_analytics&utm_medium=free_plugin",
      "plugin_wd_demo_link" => "https://admindemo.10web.io/?product_name=google-analytics",
      "plugin_wd_addons_link" => "",
      "plugin_wizard_link" => NULL,
      "plugin_wd_docs_link" => "https://help.10web.io/hc/en-us/sections/360002488232-Google-Analytics",
      "after_subscribe" => admin_url('admin.php?page=gawd_settings'),
      "plugin_menu_title" => "Analytics",
      "plugin_menu_icon" => GAWD_URL . '/assets/main_icon.png',
      "menu_position" => "25, 13",
      "custom_post" => "gawd_analytics",
      "menu_capability" => "read",
      "deactivate" => FALSE,
      "subscribe" => TRUE,
      "display_overview" => FALSE,
    );
    ten_web_lib_init($gawd_options);
  }
}

add_filter('admin_init', 'gawd_privacy_policy');
function gawd_privacy_policy($content){
  $title = __('10Web Analytics', "gawd");

  $pp_link = '<a target="_blank" href="https://policies.google.com/privacy">' . __('Privacy Policy', "gawd") . '</a>';
  $text = sprintf(__('If you enable tracking code, explain that your site uses Google Analytics. Whether it will send any personal data to Google, depends on how you set up your website. For example, do not expose emails or other personally identifiable information to Google via URLs. Do not send form submissions via analytics to Google.  By default, all the IPs of visitors are anonymized. If you change that setting, you must inform your website visitors on that. Personal data processing by Google is explained in their %s.', "gawd"), $pp_link);
  $text .= "<br/>";
  $text .= __('10Web Disclaimer: The above text is for informational purposes only and is not a legal advice. You must not rely on it as an alternative to legal advice. You should contact your legal counsel to obtain advice with respect to your particular case.', "gawd");
  $pp_text = '<h3>' . $title . '</h3>' . '<p class="wp-policy-help">' . $text . '</p>';

  $content .= $pp_text;
  return $content;
}

if(!function_exists('gawd_wd_bp_install_notice')) {

  if(get_option('wds_bk_notice_status') === '' || get_option('wds_bk_notice_status') === '1') {
    return;
  }

  function gawd_wd_bp_script_style(){
    $wd_bp_plugin_url = GAWD_URL;

    $get_current = get_current_screen();
    $current_screen_id = array(
      'toplevel_page_gawd_analytics',
      'analytics_page_gawd_reports',
      'analytics_page_gawd_settings',
      'analytics_page_gawd_tracking',
      'analytics_page_gawd_goals',
      'analytics_page_gawd_custom_reports',
      'analytics_page_gawd_uninstall',
      'analytics_page_overview_gawd',
      'analytics_page_gawd_updates',
    );

    if(in_array($get_current->id, $current_screen_id)) {
      wp_enqueue_script('wd_bck_install', $wd_bp_plugin_url . '/inc/js/wd_bp_install.js', array('jquery'));
      wp_enqueue_style('wd_bck_install', $wd_bp_plugin_url . '/inc/css/wd_bp_install.css');
    }

  }

  add_action('admin_enqueue_scripts', 'gawd_wd_bp_script_style');

  /**
   * Show notice to install backup plugin
   */
  function gawd_wd_bp_install_notice(){
    $wd_bp_plugin_url = GAWD_URL;

    $get_current = get_current_screen();
    $current_screen_id = array(
      'toplevel_page_gawd_analytics',
      'analytics_page_gawd_reports',
      'analytics_page_gawd_settings',
      'analytics_page_gawd_tracking',
      'analytics_page_gawd_goals',
      'analytics_page_gawd_custom_reports',
      'analytics_page_gawd_uninstall',
      'analytics_page_overview_gawd',
      'analytics_page_gawd_updates',
    );

    if(!in_array($get_current->id, $current_screen_id)) {
      return;
    }

    $prefix = 'gawd';

    $meta_value = get_option('wd_seo_notice_status');
    if ($meta_value === '' || $meta_value === false) {
      ob_start();
      ?>
      <div class="notice notice-info" id="wd_bp_notice_cont">
        <p>
          <img id="wd_bp_logo_notice" src="<?php echo $wd_bp_plugin_url . '/assets/seo_logo.png'; ?>">
          <?php _e("10Web Analytics advises: Optimize your web pages for search engines with the", $prefix) ?>
          <a href="https://wordpress.org/plugins/seo-by-10web/" title="<?php _e("More details", $prefix) ?>"
             target="_blank"><?php _e("FREE SEO", $prefix) ?></a>
          <?php _e("plugin.", $prefix) ?>
          <a class="button button-primary"
             href="<?php echo esc_url(wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=seo-by-10web'), 'install-plugin_seo-by-10web')); ?>">
            <span onclick="wd_bp_notice_install()"><?php _e("Install", $prefix); ?></span>
          </a>
        </p>
        <button type="button" class="wd_bp_notice_dissmiss notice-dismiss"><span class="screen-reader-text"></span>
        </button>
      </div>
      <script>wd_bp_url = '<?php echo add_query_arg(array('action' => 'wd_seo_dismiss',), admin_url('admin-ajax.php')); ?>'</script>

      <?php
      echo ob_get_clean();
    }
  }

  if (!is_dir(plugin_dir_path(dirname(__FILE__)) . 'seo-by-10web')) {
    add_action('admin_notices', 'gawd_wd_bp_install_notice');
  }

  /**
   * Add usermeta to db
   *
   * empty: notice,
   * 1    : never show again
   */

  function gawd_wd_bp_install_notice_status() {
    update_option('wd_seo_notice_status', '1', 'no');
  }
  add_action('wp_ajax_wd_seo_dismiss', 'gawd_wd_bp_install_notice_status');

}