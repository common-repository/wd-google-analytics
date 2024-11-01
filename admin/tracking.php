<?php

//todo change to class
$custom_dimensions = GAWD_helper::get_custom_dimensions();

$gawd_settings = get_option('gawd_settings');
$enable_custom_code = isset($gawd_settings['enable_custom_code']) ? $gawd_settings['enable_custom_code'] : '';

$gawd_custom_code = isset($gawd_settings["gawd_custom_code"]) ? $gawd_settings["gawd_custom_code"] : '';
$gawd_enhanced = isset($gawd_settings['gawd_enhanced']) ? $gawd_settings['gawd_enhanced'] : '';

$domain = GAWD::get_domain(esc_html(get_option('siteurl')));
$current_user = '';
if(get_current_user_id() != 0) {
  $current_user = wp_get_current_user();
  $current_user = $current_user->data->user_nicename;
}

$gawd_user_data = GAWD_helper::get_user_data();
$ua_code = isset($gawd_user_data['property_id']) ? $gawd_user_data['property_id'] : '';
if(!isset($gawd_settings['gawd_excluded_users'])) {
  $gawd_settings['gawd_excluded_users'] = array();
}
/* TRACKING CODE ADD */
if(in_array($current_user, $gawd_settings['gawd_excluded_users'])) {
  return;
}
if(isset($gawd_settings['gawd_excluded_roles'])) {
  if(GAWD::gawd_roles($gawd_settings['gawd_excluded_roles'], true)) {
    return;
  }
}

$cross_domain_list = '';
if(isset($gawd_settings['enable_cross_domain']) && isset($gawd_settings['cross_domains']) && $gawd_settings['cross_domains'] != '' && $gawd_settings['enable_cross_domain'] != '') {
  $cross_domain_list = $gawd_settings['cross_domains'];
}
$gawd_outbound = isset($gawd_settings['gawd_outbound']) ? $gawd_settings['gawd_outbound'] : '';

?>


<script>
  <?php if ('on' == $gawd_settings['gawd_tracking_enable']) {
  ?>
  (function (i, s, o, g, r, a, m) {
      i['GoogleAnalyticsObject'] = r;
      i[r] = i[r] || function () {
          (i[r].q = i[r].q || []).push(arguments)
      }, i[r].l = 1 * new Date();
      a = s.createElement(o),
          m = s.getElementsByTagName(o)[0];
      a.async = 1;
      a.src = g;
      m.parentNode.insertBefore(a, m)
  })
  (window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga');
  ga('create', '<?php echo $ua_code ?>', 'auto', {
      'siteSpeedSampleRate': '<?php echo isset($gawd_settings['site_speed_rate']) ? $gawd_settings['site_speed_rate'] : 1; ?>' <?php echo $cross_domain_list != '' ? ",'allowLinker' : true" : ""; ?>
  });
  <?php if($gawd_enhanced === 'on'){ ?>
    ga('require', 'linkid', 'linkid.js');
  <?php }
  if ($cross_domain_list != '') { ?>
  ga('require', 'linker');
  ga('linker:autoLink', [' <?php echo $cross_domain_list; ?>']);
  <?php }
  if (isset($gawd_settings['gawd_anonymize']) && 'on' == $gawd_settings['gawd_anonymize']){ ?>
  ga('set', 'anonymizeIp', true);
  <?php }

foreach($custom_dimensions as $custom_dimension) {

  $optname = 'gawd_custom_dimension_' . str_replace(' ', '_', $custom_dimension['name']);
  if($gawd_settings[$optname] !== 'on') {
    continue;
  }

  $custom_dimension_value = "";
  switch($custom_dimension['name']) {
    case 'Logged in':
      $custom_dimension_value = (is_user_logged_in()) ? "yes" : "no";
      break;
    case 'Post type':
      $custom_dimension_value = GAWD_helper::get_post_type();
      break;
    case 'Author':
      $custom_dimension_value = GAWD_helper::get_author_nickname();
      break;
    case 'Category':
      $custom_dimension_value = GAWD_helper::get_categories();
      break;
    case 'Published Month':
      $custom_dimension_value = GAWD_helper::get_published_month();
      break;
    case 'Published Year':
      $custom_dimension_value = GAWD_helper::get_published_year();
      break;
    case 'Tags': {
      $custom_dimension_value = GAWD_helper::get_tags();
      break;
    }
  }

  if(!empty($custom_dimension_value)) {
    echo "ga('set', 'dimension" . $custom_dimension['id'] . "', '" . $custom_dimension_value . "');\n";
  }
}

  if($enable_custom_code == 'on') {
    echo "/*CUSTOM CODE START*/" . $gawd_custom_code . "/*CUSTOM CODE END*/";
  } ?>
  ga('send', 'pageview');
  <?php }

  $download_script = (isset($gawd_settings['gawd_file_formats']) && $gawd_settings['gawd_file_formats'] != '');
  $links_script = ($gawd_outbound != '' && isset($domain) && $domain);
  $adsence_acc_linking = (isset($gawd_settings['adsense_acc_linking']) && $gawd_settings['adsense_acc_linking'] != '');

  if($download_script || $links_script || $adsence_acc_linking){ ?>
  document.addEventListener("DOMContentLoaded", function (event) {
      window.addEventListener('load', function () {


        <?php
        if($download_script === true){ ?>

          //Track Downloads
          var links_download = document.querySelectorAll('a');
          links_download.forEach(function (link, key, listObj) {
              if (link.href.match(/.*\.(<?php echo esc_js($gawd_settings['gawd_file_formats']); ?>)(\?.*)?$/)) {
                  link.addEventListener('click', function (e) {
                      ga('send', 'event', 'download', 'click', e.target.href<?php
                        if(isset($gawd_settings['exclude_events']) && $gawd_settings['exclude_events']) {
                          echo ", {'nonInteraction': 1}";
                        }
                        ?>);
                  });
              }
          });


          //Track Mailto
          var links_mailto = document.querySelectorAll('a[href^="mailto"]');
          links_mailto.forEach(function (link, key, listObj) {


              link.addEventListener('click', function (e) {
                  ga('send', 'event', 'email', 'send', e.target.href<?php
                    if(isset($gawd_settings['exclude_events']) && $gawd_settings['exclude_events']) {
                      echo ", {'nonInteraction': 1}";
                    }
                    ?>);
              });
          });
        <?php }

        if($links_script === true){ ?>
          //Track Outbound Links
          var links_out = document.querySelectorAll('a[href^="http"]');
          links_out.forEach(function (link, key, listObj) {
              if (!link.href.match(/.*\.(<?php echo esc_js(isset($gawd_settings['gawd_file_formats']) && $gawd_settings['gawd_file_formats'] != ''); ?>)(\?.*)?$/)) {
                  if (link.href.indexOf('<?php echo $domain; ?>') == -1) {
                      link.addEventListener('click', function (e) {
                          ga('send', 'event', 'outbound', 'click', e.target.href<?php
                            if(isset($gawd_settings['exclude_events']) && $gawd_settings['exclude_events']) {
                              echo ", {'nonInteraction': 1}";
                            }
                            ?>);
                      });
                  }
              }


          });
        <?php }
        if ($adsence_acc_linking) { ?>
          window.google_analytics_uacct = "<?php echo esc_html($ua_code); ?>";
        <?php }?>
      });
  });
  <?php } ?>
</script>