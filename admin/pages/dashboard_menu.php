<?php
function gawd_write_menu($tabs, $title = true){
  $sub_arrow = '<span class="gawd_menu_li_sub_arrow"></span>';
  $free_tabs = array('general', 'realtime');
  foreach($tabs as $tab_key => $tab_data) {
    if(!$title) {
      $tab_data["title"] = "";
      $sub_arrow = '';
    }
    if($tab_data["childs"] == array()) {
      $active_tab = sanitize_text_field($_GET['tab']) == $tab_key ? 'gawd_active_li' : '';
      /**  FREE **/
      if($tab_key === "Pro"){
        echo ' <li class="gawd_inactive_pro gawd_menu_li  '.$active_tab.' " id="gawd_'.$tab_key.'">
              <span  class="gawd_menu_item gawd_pro_menu" >'.$tab_data["title"].'</span>
              <div style="display: inline-block;float: right;margin-top: 6px;"><a href="https://10web.io/plugins/wordpress-google-analytics/?utm_source=10web_analytics&utm_medium=free_plugin" target="_blank" class="gawd-topbar-upgrade-button">Upgrade</a></div>
           </li>';
      }else if(!in_array($tab_key, $free_tabs)){
        echo ' <li class="gawd_inactive gawd_menu_li  '.$active_tab.' " id="gawd_'.$tab_key.'">
              <span class=" gawd_menu_item gawd_pro_menu" >'.$tab_data["title"].'
              </span><span class="gawd_description"  data-hint="'.$tab_data["desc"].'"></span>
           </li>';
      }/**  END FREE **/
      else if($tab_key == 'customReport') {
        echo ' <li class="gawd_menu_li  ' . $active_tab . '" id="gawd_' . $tab_key . '" >
              <a class="gawd_menu_item " href="' . admin_url() . 'admin.php?page=gawd_custom_reports">' . $tab_data["title"] . '</a>
           </li><span class="gawd_description"  data-hint="' . $tab_data["desc"] . '"></span>';
      } else {
        echo ' <li class="gawd_menu_li  ' . $active_tab . '" id="gawd_' . $tab_key . '" >
              <a class="gawd_menu_item " href="' . admin_url() . 'admin.php?page=gawd_reports&tab=' . $tab_key . '">' . $tab_data["title"] . '</a>
              <span class="gawd_description"  data-hint="' . $tab_data["desc"] . '"></span>
           </li>';
      }
    } else {
        /**  FREE **/
        if(!in_array($tab_key, $free_tabs)){
          echo ' <li class="gawd_inactive gawd_menu_li " id="gawd_'.$tab_key.'_li">
                  <span id="gawd_'.$tab_key.'s" class="gawd_menu_li_sub">'.$tab_data["title"].$sub_arrow.'
                  </span>
                  <span class="gawd_description"  data-hint="'.$tab_data["desc"].'"></span>
                  <ul id="gawd_'.$tab_key.'_ul">';
        }/** END FREE **/
        else if($tab_key == 'customReport') {
        echo ' <li class="gawd_menu_li " id="gawd_' . $tab_key . '_li">
                <span class="gawd_description"  data-hint="' . $tab_data["desc"] . '"></span>
                <span id="gawd_' . $tab_key . 's" class="gawd_menu_li_sub">' . $tab_data["title"] . $sub_arrow . '
                </span>
                <ul id="gawd_' . $tab_key . '_ul">';
      } else {
        echo ' <li class="gawd_menu_li" id="gawd_' . $tab_key . '_li" ">
        <span class="gawd_description"  data-hint="' . $tab_data["desc"] . '"></span>
          <span id="gawd_' . $tab_key . '" class="gawd_menu_li_sub">' . $tab_data["title"] . $sub_arrow . '
          </span>
          <ul id="gawd_' . $tab_key . '_ul">';
      }
      foreach($tab_data["childs"] as $child_key => $child_title) {
        if(!$title) {
          $child_title = "";
        }
        $active_tab = sanitize_text_field($_GET['tab']) == $child_key ? 'gawd_active_li' : '';
        if(!in_array($tab_key, $free_tabs)){
          echo '  <li class=" gawd_menu_ul_li '.$active_tab.'">
                     <span class="gawd_menu_item " >'.$child_title.'</span>
                 </li> ';

        }/** END FREE **/
        else {
          echo '  <li class="gawd_menu_ul_li ' . $active_tab . '">
                   <a class="gawd_menu_item " href="' . admin_url() . 'admin.php?page=gawd_reports&tab=' . $child_key . '">' . $child_title . '</a>
               </li> ';
        }
      }
      echo '</ul>
                </li>';
    }
  }
}

function gawd_write_menu_collapse($tabs, $title = true){
  $sub_arrow = '<span class="gawd_menu_li_sub_arrow"></span>';
  foreach($tabs as $tab_key => $tab_data) {
    if(!$title) {
      $tab_data["title"] = "";
      $sub_arrow = '';
    }
    if($tab_data["childs"] == array()) {
      $active_tab = sanitize_text_field($_GET['tab']) == $tab_key ? 'gawd_active_li' : '';
      if($tab_key == 'customReport') {
        echo '<a id="gawd_' . $tab_key . '" class="' . $active_tab . ' gawd_menu_item " href="' . admin_url() . 'admin.php?page=gawd_custom_reports">' . $tab_data["title"] . '</a>';
      } else {
        echo '<a id="gawd_' . $tab_key . '" class="' . $active_tab . ' gawd_menu_item " href="' . admin_url() . 'admin.php?page=gawd_reports&tab=' . $tab_key . '">' . $tab_data["title"] . '</a>';
      }
    } else {
      if($tab_key == 'customReport') {
        echo '<span id="gawd_' . $tab_key . '_li" id="gawd_' . $tab_key . 's" class="gawd_menu_li_sub">' . $tab_data["title"] . $sub_arrow . '
             <div class="collapse_ul" id="gawd_' . $tab_key . '_ul">';
      } else {
        echo '<span id="gawd_' . $tab_key . '_li" id="gawd_' . $tab_key . '" class="gawd_menu_li_sub">' . $tab_data["title"] . $sub_arrow . '
          <div class="collapse_ul" id="gawd_' . $tab_key . '_ul">';
      }
      foreach($tab_data["childs"] as $child_key => $child_title) {
        $active_tab = sanitize_text_field($_GET['tab']) == $child_key ? 'gawd_active_li_text' : '';
        echo '<a class="' . $active_tab . ' gawd_menu_item " href="' . admin_url() . 'admin.php?page=gawd_reports&tab=' . $child_key . '">' . $child_title . '</a>';
      }
      echo '</div></span>';
    }
  }

}

?>
<div class="resp_menu">
    <div class="menu_img"></div>
    <div class="button_label">REPORTS</div>
    <div class="clear"></div>
</div>

<div class="gawd_menu_coteiner_collapse">
    <div class="gawd_menu_ul">
      <?php
      gawd_write_menu_collapse($tabs, false);
      ?>
        <span class='gawd_collapsed'></span>
    </div>
</div>
<div class="gawd_menu_coteiner">
    <input onkeyup="gawd_search()" type="text" class='gawd_search_input' placeholder="<?php _e('Search', 'gawd'); ?>"
           autofocus/>
    <ul class="gawd_menu_ul">
      <?php
      gawd_write_menu($tabs);
      ?>
        <li class='gawd_collapse'>Collapse menu</li>
    </ul>
</div>