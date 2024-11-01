<?php
/**
 * Admin page
 */
// Exit if accessed directly.
if(!defined('ABSPATH')) {
  exit;
}

//global $gawd_options;
?>

<div class="wrap">
  <?php settings_errors(); ?>
    <div id="settings">
        <div id="settings-content">
            <h2 id="add_on_title"><?php echo esc_html(get_admin_page_title()); ?></h2>


            <div class="main-plugin_desc-cont">
                You can download the latest version of your plugins from your <a href="https://10web.io"
                                                                                 target="_blank"> 10Web</a>
                account. After deactivating and deleting the current version, install the downloaded version of the
                plugin.
            </div>

            <br/>
            <br/>

          <?php
          if($gawd_plugins) {
            $update = 0;
            if(isset($gawd_plugins[158])) {

              $project = $gawd_plugins[158];
              unset($gawd_plugins[158]);
              if(isset($updates[158])) {
                $update = 1;
              }
              ?>
                <div class="main-plugin">
                    <div class="add-on">
                      <?php if($project['gawd_data']['image']) { ?>
                          <div class="figure-img">
                              <a href="<?php echo $project['gawd_data']['url'] ?>" target="_blank">
                                  <img src="<?php echo $project['gawd_data']['image'] ?>"/>
                              </a>
                          </div>
                      <?php } ?>

                    </div>
                    <div class="main-plugin-info">
                        <h2>
                            <a href="<?php echo $project['gawd_data']['url'] ?>"
                               target="_blank"><?php echo $project['Title'] ?></a>
                        </h2>

                        <div class="main-plugin_desc-cont">
                            <div class="main-plugin-desc"><?php echo $project['gawd_data']['description'] ?></div>
                            <div class="main-plugin-desc main-plugin-desc-info">
                                <p><a href="<?php echo $project['gawd_data']['url'] ?>"
                                      target="_blank">Version <?php echo $project['Version'] ?></a></p>
                            </div>

                          <?php if(isset($updates[158][0])) { ?>
                              <span class="update-info">There is an new <?php echo $updates[158][0]['version'] ?>
                                  version</span>
                              <p><span>What's new:</span></p>
                              <div class="last_update"><b><?php echo $updates[158][0]['version'] ?></b>
                                <?php echo strip_tags(str_ireplace('Important', '', $updates[158][0]['note']), '<p>') ?>
                              </div>
                            <?php unset($updates[158][0]); ?>
                            <?php if(count($updates[158]) > 0) { ?>

                                  <div class="more_updates">
                                    <?php foreach($updates[158] as $update) {
                                      ?>
                                        <div class="update"><b><?php echo $update['version'] ?></b>
                                          <?php echo strip_tags(str_ireplace('Important', '', $update['note']), '<p>') ?>
                                        </div>
                                      <?php
                                    }
                                    ?>
                                  </div>
                                  <a href="#" class="show_more_updates">More updates</a>
                              <?php
                            }
                            ?>


                          <?php } ?>

                        </div>
                    </div>
                </div>
            <?php }
            ?>
              <div class="addons_updates">
                <?php
                foreach($gawd_plugins as $id => $project) {
                  $last_index = 0;
                  if(isset($updates[$id]) && !empty($updates[$id])) {
                    $last_index = count($updates[$id]) - 1;
                  }
                  ?>
                    <div class="add-on">
                        <figure class="figure">
                            <div class="figure-img">
                                <a href="<?php echo $project['gawd_data']['url'] ?>" target="_blank">
                                  <?php if($project['gawd_data']['image']) { ?>
                                      <img src="<?php echo $project['gawd_data']['image'] ?>"/>
                                  <?php } ?>
                                </a>
                            </div>
                            <figcaption class="addon-descr figcaption">
                              <?php if(isset($updates[$id][$last_index])) { ?>
                                  <p>What's new:</p>
                                <?php echo strip_tags($updates[$id][$last_index]['note'], '<p>') ?>
                              <?php } else { ?><?php echo $project['Title'] ?> is up to date
                              <?php } ?>
                            </figcaption>
                        </figure>
                        <h2><?php echo $project['Title'] ?></h2>

                        <div class="main-plugin-desc-info">
                            <p><a href="<?php echo $project['gawd_data']['url'] ?>"
                                  target="_blank"><?php echo $project['Version'] ?></a> | 10Web</p>
                        </div>
                      <?php if(isset($updates[$id]) && isset($updates[$id][$last_index]['version'])) { ?>
                          <div class="addon-descr-update">
                                            <span
                                                    class="update-info">There is an new <?php echo $updates[$id][$last_index]['version'] ?>
                                                version</span><br/>
                          </div>
                      <?php } ?>
                    </div>
                <?php }
                ?>
              </div>
            <?php
          }
          ?>

        </div>
        <!-- #gawd-settings-content -->
    </div>
    <!-- #gawd-settings -->
</div><!-- .wrap -->
