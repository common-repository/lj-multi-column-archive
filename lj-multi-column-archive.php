<?php
/*
Plugin Name: LJ Multi Column Archive
Plugin URI: http://www.thelazysysadmin.net/software/wordpress-plugins/lj-multi-column-archive/
Description: LJ Multi Column Archive is a Wordpress plugin/widget that allows you to display your archive list with multiple columns. I developed this plugin as I wanted to make use of the space I had in the sidebar more effectively.
Author: Jon Smith
Version: 1.4
Author URI: http://www.thelazysysadmin.net/
*/

class LJMultiColumnArchive {

  private $pluginversion = "1.4";

  private $defaults = array
    (
      'title' => 'Archives',
      'showpostcount' => false,
      'numcolumns' => 1,
      'type' => 'monthly'
    );
  
  function LJMultiColumnArchive() {
    register_sidebar_widget("LJ Multi Column Archive", array(&$this, "widget"));

    register_widget_control("LJ Multi Column Archive", array(&$this, "widget_control"));

    add_action('wp_print_styles', array(&$this, 'add_styles'));
  }

  function widget($args) {
    global $wpdb, $wp_locale;

    extract($args);

    $options = get_option('LJMultiColumnArchive');

    if ($options === false) {
      $options = array();
    }

    foreach ($this->defaults as $key => $value) {
      if (!isset ($options[$key]))
        $options[$key] = $value;
    }

    $archive_day_date_format = get_option('date_format');
    
    switch ($options['type']) {
      case 'monthly':
        $query = "SELECT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, count(ID) as posts FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' GROUP BY YEAR(post_date), MONTH(post_date) ORDER BY post_date DESC $limit";
        $key = md5($query);
        break;
      case 'yearly':
        $query = "SELECT YEAR(post_date) AS `year`, count(ID) as posts FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' GROUP BY YEAR(post_date) ORDER BY post_date DESC $limit";
        $key = md5($query);
        break;
      case 'daily':
        $query = "SELECT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, DAYOFMONTH(post_date) AS `dayofmonth`, count(ID) as posts FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' GROUP BY YEAR(post_date), MONTH(post_date), DAYOFMONTH(post_date) ORDER BY post_date DESC $limit";
        $key = md5($query);
        break;
    }
    
    //$query = "SELECT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, count(ID) as posts FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' GROUP BY YEAR(post_date), MONTH(post_date) ORDER BY post_date DESC";
    //$key = md5($query);
    $cache = wp_cache_get( 'LJMultiColumnArchive_widget' , 'general');
    if ( !isset( $cache[ $key ] ) ) {
      $arcresults = $wpdb->get_results($query);
      $cache[ $key ] = $arcresults;
      wp_cache_add( 'LJMultiColumnArchive_widget', $cache, 'general' );
    } else {
      $arcresults = $cache[ $key ];
    }
    if ( $arcresults ) {
      $afterafter = $after;
      $i = 0;
      foreach ( (array) $arcresults as $arcresult ) {
        switch ($options['type']) {
          case 'monthly':
            $output[$i]['url'] = get_month_link( $arcresult->year, $arcresult->month );
            $output[$i]['text'] = sprintf(__('%1$s %2$d'), $wp_locale->get_month($arcresult->month), $arcresult->year);
            break;
          case 'yearly':
            $output[$i]['url'] = get_year_link($arcresult->year);
            $output[$i]['text'] = sprintf('%d', $arcresult->year);
            break;
          case 'daily':
            $output[$i]['url']  = get_day_link($arcresult->year, $arcresult->month, $arcresult->dayofmonth);
            $date = sprintf('%1$d-%2$02d-%3$02d 00:00:00', $arcresult->year, $arcresult->month, $arcresult->dayofmonth);
            $output[$i]['text'] = mysql2date($archive_day_date_format, $date);
            break;
        }
        $output[$i]['postcount'] = $arcresult->posts;
        if ($options['showpostcount']) {
          $after = '&nbsp;('.$arcresult->posts.')';
        }
        $output[$i]['link'] = get_archives_link($output[$i]['url'], $output[$i]['text'], 'html', '', $after);
        $i++;
      }
    }

    echo "\n<!-- LJMultiColumnArchive Version ".$this->pluginversion." Start -->\n";
    echo $before_widget;
    echo $before_title.$options['title'].$after_title;
    echo "<div class='ljmulticolumnarchive-box'>\n";

    $count = count($output);
    $mod = ceil($count / $options['numcolumns']);

    $i = 0;
    foreach ($output as $item) {
      if (($i % $mod) == 0) {
        echo "  <div class='ljmulticolumnarchive-section";
        if ($i > 0) {
          echo " ljmulticolumnarchive-section-next";
        }
        echo "'>\n";
        echo "    <ul>\n";
      }

      echo "      ".$item['link'];

      if ((($i+1) % $mod) == 0) {
        echo "    </ul>\n";
        echo "  </div>\n";
      }

      $i++;
    }

    if ((($i) % $mod) != 0) {
      echo "    </ul>\n";
      echo "  </div>\n";
    }

    echo "</div>\n";
    echo "<div class='ljmulticolumnarchive-clear'></div>\n";
    echo $after_widget;
    echo "\n<!-- LJMultiColumnArchive End -->\n";
  }

  function widget_control() {

    $options = get_option('LJMultiColumnArchive');

    if ($options === false) {
      $options = array();
    }

    foreach ($this->defaults as $key => $value) {
      if (!isset ($options[$key]))
        $options[$key] = $value;
    }

    if ($_POST['widget-LJMultiColumnArchive-submit']) {
      $options['title'] = $_REQUEST['widget-LJMultiColumnArchive-title'];
      $showpostcount = isset($_REQUEST['widget-LJMultiColumnArchive-showpostcount']) ? $_REQUEST['widget-LJMultiColumnArchive-showpostcount'] : 0;
      $options['showpostcount'] = ($showpostcount == 1) ? true : false;
      $options['numcolumns'] = $_REQUEST['widget-LJMultiColumnArchive-numcolumns'];
      $options['type'] = $_REQUEST['widget-LJMultiColumnArchive-type'];
      
      update_option('LJMultiColumnArchive', $options);
    }

    echo '<p><label for="widget-LJMultiColumnArchive-title">Title: <br />';
    echo '<input type="text" class="widefat" id="widget-LJMultiColumnArchive-title" name="widget-LJMultiColumnArchive-title" value="'.$options['title'].'" /></label></p>';

    echo '<p><input type="checkbox" name="widget-LJMultiColumnArchive-showpostcount" id="widget-LJMultiColumnArchive-showpostcount" value="1"';
    if ($options['showpostcount']) { echo 'checked'; }
    echo '><label for="widget-LJMultiColumnArchive-showpostcount">&nbsp;Show Post Count</label></p>';

    echo '<p><label for="widget-LJMultiColumnArchive-numcolumns">Num of Columns:</label><br/>';
    echo '<input type="radio" name="widget-LJMultiColumnArchive-numcolumns" id="widget-LJMultiColumnArchive-numcolumns" value="1"';
    if ($options['numcolumns'] == "1") { echo 'checked="checked"'; }
    echo '>One&nbsp;<input type="radio" name="widget-LJMultiColumnArchive-numcolumns" id="widget-LJMultiColumnArchive-numcolumns" value="2"';
    if ($options['numcolumns'] == "2") { echo 'checked="checked"'; }
    echo '>Two&nbsp;<input type="radio" name="widget-LJMultiColumnArchive-numcolumns" id="widget-LJMultiColumnArchive-numcolumns" value="3"';
    if ($options['numcolumns'] == "3") { echo 'checked="checked"'; }
    echo '>Three</p>';

    echo '<p><label for="widget-LJMultiColumnArchive-type">Archive Type:</label>';
    echo '<select name="widget-LJMultiColumnArchive-type" id="widget-LJMultiColumnArchive-type" class="widefat">';
    echo '<option value="monthly"';
    if ($options['type'] == 'monthly') echo 'selected="selected"';
    echo '>Monthly</option>';
    echo '<option value="yearly"';
    if ($options['type'] == 'yearly') echo 'selected="selected"';
    echo '>Yearly</option>';
    echo '<option value="daily"';
    if ($options['type'] == 'daily') echo 'selected="selected"';
    echo '>Daily</option>';
    echo '</select></p>';

    echo '<input type="hidden" id="widget-LJMultiColumnArchive-submit" name="widget-LJMultiColumnArchive-submit" value="1" />';

  }

  function add_styles() {
    $stylesheet = "/lj-multi-column-archive/css/lj-multi-column-archive.css";
    $stylesheeturl = WP_PLUGIN_URL.$stylesheet;
    $stylesheetfile = WP_PLUGIN_DIR.$stylesheet;
    if (file_exists($stylesheetfile)) {
      wp_register_style("LJMultiColumnArchiveStyleSheet", $stylesheeturl);
      wp_enqueue_style("LJMultiColumnArchiveStyleSheet");
    }
  }
  
  function upgrade() {
    $opt_title = get_option('widget-LJMultiColumnArchive-title');
    $opt_showpostcount = get_option('widget-LJMultiColumnArchive-showpostcount');
    $opt_numcolumns = get_option('widget-LJMultiColumnArchive-numcolumns');
  }

}

$LJMultiColumnArchive = new LJMultiColumnArchive();

?>