<?php
/*
 * Plugin Name: Social Proof for WooCommerce
 * Plugin URI: https://www.ilmigo.com/wooproof/
 * Version: 1.0
 * Description: Motivate your customers to buy from your online store. Show them social proof that other people are already buying from your store.
 * Author: Ilmigo
 * Author URI: https://www.ilmigo.com
 * WC requires at least: 3.7.0
 * WC tested up to: 3.7.0
 */


if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {


    function igproof_install()
    {
        global $wpdb;

        $table_name = "wooProof_analytics";

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        product varchar(255) NOT NULL,
        total_clicks int(20) NOT NULL,
        click_day date NOT NULL,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }


    igproof_install();

    add_action('admin_enqueue_scripts', 'igproof_admin_scripts');
    add_action('admin_menu', 'igproof_add_pages');
    add_action('admin_init', 'igproof_api_setting_init');


    function igproof_add_pages()
    {
        add_submenu_page('woocommerce', 'Social Proof for WooCommerce', 'Social Proof', 'manage_options', 'ig-woo_proof', 'igProof_settings_page');
    }

    function igproof_admin_scripts()
    {
        wp_register_style('igproof-admin-css', plugins_url('assets/css/igproof-admin.css', __FILE__));
        wp_register_script('igproof-admin', plugins_url('assets/js/igproof-admin.js', __FILE__), array('jquery','jquery-ui-core','jquery-ui-dialog'), '1.0', true);
        
        // Localize the script with new data
        $translation_array = array(
            'plugin_id' => plugin_basename( __FILE__ )
        );
        wp_localize_script( 'igproof-admin', 'ig_wooproof', $translation_array );

        // Get admin screen ID.
        $screen    = get_current_screen();
        $screen_id = $screen ? $screen->id : '';

        if(in_array($screen_id, array('woocommerce_page_ig-woo_proof', 'plugins'))) {
            wp_enqueue_script('igproof-admin');
            wp_enqueue_style('igproof-admin-css');

			// Including assets for analytics

	        wp_enqueue_style('daterangepicker-css',  plugins_url('assets/css/daterangepicker.css', __FILE__));
	        wp_enqueue_script('igproof-daterange', plugins_url('assets/js/daterangepicker.min.js', __FILE__), array('moment'));
	        wp_enqueue_script('igproof-chart', plugins_url('assets/js/Chart.min.js', __FILE__));
        }
    }


    function igproof_api_setting_init()
    {
        // Registering the settings
        register_setting('igProofPlugin', 'igProof_api_settings', array('sanitize_callback' => 'igproof_sanitize_callback'));
        register_setting('igProofAnalytics', 'igProof_api_settings');

        // Start New Section
        add_settings_section(
            'igProof_section',
            'Social Proof Settings',
            '',
            'igProofPlugin'
        );
        add_settings_section(
            'igProof_analytics',
            'Social Proof Analytics',
            '',
            'igProofAnalytics'
        );

        // Start Fields Setting
        add_settings_field(
            'total_orders_display',
            'Choose the number of orders to display',
            'igproof_total_orders_display_field',
            'igProofPlugin',
            'igProof_section'
        );

        add_settings_field(
            'min_product_price',
            'Minimum product price',
            'igproof_min_product_price_field',
            'igProofPlugin',
            'igProof_section'
        );


        add_settings_field(
            'repeat_orders_display',
            'Repeat Orders',
            'igproof_repeat_orders_field',
            'igProofPlugin',
            'igProof_section'
        );

        add_settings_field(
            'disable_verified_by',
            'Disable “Verified by Ilmigo”?',
            'igproof_disable_verified_by_field',
            'igProofPlugin',
            'igProof_section'
        );

        add_settings_field(
            'display_order_time',
            'Display Order Time (i.e. days ago, minutes ago etc)?',
            'igproof_hide_order_time_field',
            'igProofPlugin',
            'igProof_section'
        );

        add_settings_field(
            'items_interval',
            'Interval between items',
            'igproof_items_interval_field',
            'igProofPlugin',
            'igProof_section'
        );

        add_settings_field(
            'slide_show_time',
            'Slide show time',
            'igproof_slide_show_time_field',
            'igProofPlugin',
            'igProof_section'
        );

        add_settings_field(
            'cookie_expiry_time',
            'Cookie expire time',
            'igproof_cookie_expiry_time_field',
            'igProofPlugin',
            'igProof_section'
        );

        add_settings_field(
            'widget_location[]',
            'Please select the pages where this plugin should be display?',
            'igproof_widget_location_field',
            'igProofPlugin',
            'igProof_section'
        );

        // Date Range Analytics
        add_settings_field(
            'date_range',
            'Select Date Range',
            'igproof_date_range',
            'igProofAnalytics',
            'igProof_analytics',
            array('class' => 'date_range_row')
        );
    }
    
    function igproof_sanitize_callback($settings){
        $new_settings = array();
        if(isset($_POST['reset']) && sanitize_text_field($_POST['reset']) && !empty(sanitize_text_field($_POST['reset']))){
            $new_settings = array(
                'total_orders_display' => 10,
                'min_product_price' => 15,
                'repeat_orders_value' => 'yes',
                'disable_verified_by' => 'yes',
                'display_order_time' => 'yes',
                'items_interval' => 10,
                'slide_show_time' => 10,
                'cookie_expiry_time' => 7,
                'widget_location' => array('homepage')
            );
            add_settings_error('igProofPlugin', 'igProof_api_settings', __('Your settings have been reset to defaults.', 'ig-wooproof'), 'updated');
            return $new_settings;
        }
        return $settings;
    }


    // Start the output layout of form
    function igproof_total_orders_display_field()
    {

        $options = get_option('igProof_api_settings', igproof_get_default_settings());
        ?>

        <input type="number" name="igProof_api_settings[total_orders_display]" max="50"
               value="<?php echo empty($options['total_orders_display']) ? 10 : esc_attr($options['total_orders_display']); ?>">

        <?php
    }

    function igproof_min_product_price_field()
    {
        $options = get_option('igProof_api_settings', igproof_get_default_settings());
        ?>
        <input type="number" name="igProof_api_settings[min_product_price]" value="<?php echo empty($options['min_product_price']) ? 15 : esc_attr($options['min_product_price']); ?>">
        <?php
    }

    function igproof_repeat_orders_field()
    {
        $options = get_option('igProof_api_settings', igproof_get_default_settings());
        ?>

        <input type="checkbox" name="igProof_api_settings[repeat_orders_value]" value="yes" <?php if(isset($options['repeat_orders_value']) && $options['repeat_orders_value'] == 'yes') { ?> checked <?php } ?>>

        <?php
    }

    function igproof_disable_verified_by_field()
    {
        $options = get_option('igProof_api_settings', igproof_get_default_settings());
        ?>

        <input type="radio" name="igProof_api_settings[disable_verified_by]"
            <?php if (empty($options['disable_verified_by'])) { ?> checked <?php } ?>
               value="yes" <?php if (in_array('yes', (array)$options['disable_verified_by'])) { ?> checked <?php } ?>>
        <label class="location-chectkbox-label"><b>Yes</b></label>

        <input type="radio" name="igProof_api_settings[disable_verified_by]" style="margin-left: 20px"
               value="no" <?php if (in_array('no', (array)$options['disable_verified_by'])) { ?> checked <?php } ?>>
        <label class="location-chectkbox-label"><b>No</b></label>
        <?php
    }

    function igproof_hide_order_time_field()
    {
        $options = get_option('igProof_api_settings', igproof_get_default_settings());
        ?>

        <input type="radio" name="igProof_api_settings[display_order_time]"
            <?php if (empty($options['display_order_time'])) { ?> checked <?php } ?>
               value="yes" <?php if (in_array('yes', (array)$options['display_order_time'])) { ?> checked <?php } ?>>
        <label class="location-chectkbox-label"><b>Yes</b></label>
        <input type="radio" name="igProof_api_settings[display_order_time]" style="margin-left: 20px"
               value="no" <?php if (in_array('no', (array)$options['display_order_time'])) { ?> checked <?php } ?>>
        <label class="location-chectkbox-label"><b>No</b></label>
        <?php
    }

    function igproof_items_interval_field()
    {
        $options = get_option('igProof_api_settings', igproof_get_default_settings());
        ?>
        <div class="slider">
            <input type="range" name="igProof_api_settings[items_interval]" min="1" max="59" class="range_slider"
                   value="<?php echo empty($options['items_interval']) ? 10 : esc_attr($options['items_interval']); ?>"
                   onchange="items_interval_output.value=value"/>
            <output id="items_interval_output"><?php echo empty($options['items_interval']) ? 10 : esc_html($options['items_interval']); ?></output>
            Seconds
        </div>
        <?php
    }

    function igproof_slide_show_time_field()
    {

        $options = get_option('igProof_api_settings', igproof_get_default_settings());
        ?>
        <div class="slider">
            <input type="range" name="igProof_api_settings[slide_show_time]" min="1" max="59" class="range_slider"
                   value="<?php echo empty($options['slide_show_time']) ? 10 : esc_attr($options['slide_show_time']); ?>"
                   onchange="slide_show_time_output.value=value"/>
            <output id="slide_show_time_output"><?php echo empty($options['slide_show_time']) ? 10 : esc_html($options['slide_show_time']); ?></output>
            Seconds
        </div>
        <?php
    }

    function igproof_cookie_expiry_time_field()
    {
        $options = get_option('igProof_api_settings', igproof_get_default_settings());
        ?>
        <select name="igProof_api_settings[cookie_expiry_time]">
            <option value="7"<?php selected($options['cookie_expiry_time'], "7"); ?>>7 Days</option>
            <option value="15"<?php selected($options['cookie_expiry_time'], "15"); ?>>15 Days</option>
            <option value="30"<?php selected($options['cookie_expiry_time'], "30"); ?>>30 Days</option>
        </select>
        <?php
    }

    function igproof_widget_location_field()
    {
        $options = get_option('igProof_api_settings', igproof_get_default_settings());
        $pages = get_pages(); ?>


        <div class="location_checkbox_section">

            <input type="checkbox" name="igProof_api_settings[widget_location][]" id="wooproof_location_all"
                   onClick="toggle(this)"
                   value="all" <?php if (in_array('all', (array)$options['widget_location'])) { ?> checked <?php } ?>/>
            <label class="location-chectkbox-label"><b>All Pages</b></label> <br><br>

            <input type="checkbox" name="igProof_api_settings[widget_location][]" id="wooproof_location_all"
                   class="wooproof_page_location"

                   value="homepage" <?php if (in_array('homepage', $options['widget_location'])) { ?> checked <?php } ?>/>
            <label class="location-chectkbox-label">Homepage</label> <br><br>

            <input type="checkbox" name="igProof_api_settings[widget_location][]" id="wooproof_location_prod_cat"
                   class="wooproof_page_location"

                   value="prod_arch" <?php if (in_array('prod_arch', $options['widget_location'])) { ?> checked <?php } ?>/>
            <label class="location-chectkbox-label" for="wooproof_location_prod_cat">Product Categories (including shop page)</label> <br><br>

            <input type="checkbox" name="igProof_api_settings[widget_location][]" id="wooproof_location_single_prod"
                   class="wooproof_page_location"

                   value="single_prod" <?php if (in_array('single_prod', $options['widget_location'])) { ?> checked <?php } ?>/>
            <label class="location-chectkbox-label" for="wooproof_location_single_prod">Single Product</label> <br><br>

            <input type="checkbox" name="igProof_api_settings[widget_location][]" id="wooproof_location_single_post"
                   class="wooproof_page_location"

                   value="single_post" <?php if (in_array('single_post', $options['widget_location'])) { ?> checked <?php } ?>/>
            <label class="location-chectkbox-label" for="wooproof_location_single_post">Single Post</label> <br><br>

            <?php
            foreach ($pages as $page) { ?>

                <input id="page_<?php echo $page->ID; ?>" type="checkbox" name="igProof_api_settings[widget_location][]"
                       class="wooproof_page_location"
                       value="<?php echo esc_attr($page->ID); ?>" <?php if (in_array($page->ID, (array)$options['widget_location'])) { ?> checked <?php } ?>/>
                <label class="location-chectkbox-label"
                       for="page_<?php echo esc_html($page->ID); ?>"><?php echo esc_html($page->post_title); ?></label> <br><br>

            <?php } ?>
        </div>
        <?php
    }

    // Date Range field callback
    function igproof_date_range()
    {
        $options = get_option('igProof_api_settings', igproof_get_default_settings());
        ?>
        <input type="text" name="daterange" id="dateRange" class="dataRange_input" />
        <?php
    }


    // Initializing the setting options page of wooProof
    function igproof_settings_page()
    {
        global $igProof_active_tab;
        $igProof_active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'welcome';
        ?>

        <h2 class="nav-tab-wrapper">
            <?php
            do_action('igProof_settings_tab');
            ?>
        </h2>
        <?php do_action('igProof_settings_content');
    }


    // Setting the default tab
    add_action('igProof_settings_tab', 'igproof_welcome_tab', 1);
    function igproof_welcome_tab()
    {
        global $igProof_active_tab; ?>
        <a class="nav-tab <?php echo $igProof_active_tab == 'welcome' || '' ? 'nav-tab-active' : ''; ?>"
           href="<?php echo esc_url(admin_url('admin.php?page=ig-woo_proof&tab=welcome')); ?>">
            <?php _e('General Settings', 'igProof'); ?>
        </a>
        <?php
    }


    // setting the Content in default tab
    add_action('igProof_settings_content', 'igproof_welcome_render_options_page');
    function igproof_welcome_render_options_page()
    {
        global $igProof_active_tab;
        if ('' || 'welcome' != $igProof_active_tab)
            return;
        ?>

        <form action="options.php" method="post">
            <?php
            settings_fields('igProofPlugin');
            do_settings_sections('igProofPlugin');
            submit_button(__('Reset'), 'primary reset', 'reset', false, array('onclick' => 'reset_to_defaults(this.form);return false;'));
            submit_button('Save Changes', 'primary', 'submit', FALSE);
            ?>
        </form>


        <?php
    }


    // creating the Analytics tab
    add_action('igProof_settings_tab', 'igproof_analytics_tab', 2);
    function igproof_analytics_tab()
    {
        global $igProof_active_tab; ?>
        <a class="nav-tab <?php echo $igProof_active_tab == 'igproof_analytics_tab' ? 'nav-tab-active' : ''; ?>"
           href="<?php echo esc_url(admin_url('admin.php?page=ig-woo_proof&tab=igproof_analytics_tab')); ?>">
            <?php _e('Analytics', 'iProof'); ?>
        </a>
        <?php
    }


    // Inserting data for Analytics when click on any wooProof UI box from front-end
    function igproof_clicks_counter()
    {

        global $wpdb;

        $table_name = 'wooProof_analytics';

        $product = sanitize_text_field($_POST['product']);

        $chk_duplicate = $wpdb->get_row("SELECT * FROM $table_name WHERE product = '$product' AND click_day = CURRENT_DATE", OBJECT);


        if ($chk_duplicate == null) {

            $insert_data = array(
                'product' => esc_sql($product),
                'total_clicks' => 1,
                'click_day' => date('Y-m-d'),
                'created_at' => date('Y-m-d H:i:s')
            );

            $wpdb->insert(
                $table_name,
                $insert_data
            );

        } else {

            $total_clicks = $chk_duplicate->total_clicks + 1;
            $update_data = array(
                'total_clicks' => $total_clicks,
            );

            $wpdb->update(
                $table_name,
                $update_data,
                array('product' => $product, 'click_day' => date('Y-m-d'))
            );
        }

        echo $product;
        die();
    }

    // Analytics tab content
    add_action('igProof_settings_content', 'igproof_analytics_render_options_page');
    function igproof_analytics_render_options_page()
    {
        global $igProof_active_tab;
        global $wpdb;
        $table_name = 'wooProof_analytics';

        if ('igproof_analytics_tab' != $igProof_active_tab)
            return;
        ?>

        <?php
        
        $date_from = date('m/d/Y', strtotime('-7 days'));
        $date_to = date('m/d/Y');
        if(isset($_POST['daterange']) && sanitize_text_field($_POST['daterange']) && !empty(sanitize_text_field($_POST['daterange']))){
            $get_date= explode('-', sanitize_text_field($_POST['daterange']));
            $date_from = date('Y-m-d', strtotime(trim($get_date[0])));
            $date_to = date('Y-m-d', strtotime(trim($get_date[1])));

            $qry_day_clicks = $wpdb->get_results("SELECT *, SUM(total_clicks) AS total_clicks_by_day FROM $table_name WHERE click_day BETWEEN '$date_from' AND '$date_to' GROUP BY click_day ORDER BY click_day ASC ", ARRAY_A);
            $qry_products = $wpdb->get_results("SELECT * FROM $table_name WHERE click_day BETWEEN '$date_from' AND '$date_to' GROUP BY product ORDER BY click_day ASC ", ARRAY_A);
        } else {
        $qry_day_clicks = $wpdb->get_results("SELECT *, SUM(total_clicks) AS total_clicks_by_day FROM $table_name WHERE click_day BETWEEN CURDATE()-INTERVAL 1 WEEK AND CURDATE() GROUP BY click_day ORDER BY click_day ASC ", ARRAY_A);
        $qry_products = $wpdb->get_results("SELECT * FROM $table_name WHERE click_day BETWEEN CURDATE()-INTERVAL 1 WEEK AND CURDATE() GROUP BY product ORDER BY click_day ASC ", ARRAY_A);
        }


        $i = 0;
        $arr_click_days = array();
        foreach ($qry_day_clicks as $qry_day) {

            $arr_click_days[$i] = $qry_day;
            $i++;
        }


        $c = 0;
        $arr_clicked_products = array();
        foreach ($qry_products as $qry_product) {
            if (!in_array($qry_day['product'], $arr_clicked_products)) {
                $product = $qry_product['product'];
                if(isset($_POST['daterange']) && sanitize_text_field($_POST['daterange']) && !empty(sanitize_text_field($_POST['daterange']))) {
                    $qry_product_clicks = $wpdb->get_results("SELECT * FROM $table_name WHERE product = '$product' AND click_day BETWEEN '$date_from' AND '$date_to' GROUP BY click_day ORDER BY click_day ASC ", ARRAY_A);
                } else {
                $qry_product_clicks = $wpdb->get_results("SELECT * FROM $table_name WHERE product = '$product' AND click_day BETWEEN CURDATE()-INTERVAL 1 WEEK AND CURDATE() GROUP BY click_day ORDER BY click_day ASC ", ARRAY_A);
                }

                $arr_clicked_products[$qry_product['product']] = $qry_product_clicks;
            }
            $c++;
        }
        ?>


        <form action="" method="post">
            <?php
            settings_fields('igProofAnalytics');
            do_settings_sections('igProofAnalytics');
            submit_button('Submit');
            ?>
        </form>



        <div class="analytics_graph">
            <div id="chart-container" style="margin:15px auto;">
                <?php if (empty($arr_click_days)) { ?>
                    <div align="center" style="color: #ff000a; "><b>No Data Found</b></div>
                <?php } ?>
                <canvas id="wooproof-analytics"></canvas>
            </div>
        </div>

        <?php
        
		// Setting config options for date range calendar
		wp_add_inline_script('image-edit', ' jQuery("#dateRange").daterangepicker({
                "startDate":  "'.date("m/d/Y", strtotime($date_from)).'" ,
                "endDate": "'.date("m/d/Y", strtotime($date_to)).'",
                "maxDate": "'.date("m/d/Y").'",
                 locale: {
                    format: \'M/DD/YYYY\'
                    }
            }, function(start, end, label) {
                console.log("New date range selected: " + start.format("YYYY-MM-DD") + " to " + end.format("YYYY-MM-DD") + "(predefined range: " + label + ")");
            });');

        $chart_date_range = array_map(function ($entry) {
                        return $entry['click_day'];
                      }, $arr_click_days);
                      
        $date_labels = implode(', ', array_map(function ($entry) {
            return date('Y-m-d', strtotime($entry));
          }, $chart_date_range));

          $total_clicks = implode(', ', array_map(function ($entry) {
            return $entry['total_clicks_by_day'];
          }, $arr_click_days));
          
          function igproof_child_clicks($products, $chart_date_range) {
              $clicks_array = array();
              
              foreach($chart_date_range as $index => $day){
                  
                  $array_map = array_map(function ($entry){
                      return $entry['click_day'];
                  }, $products);
                  
                  if(in_array($day, $array_map)){
                      $clicks_array[] = $products[array_search($day, $array_map)]['total_clicks'];
                  }else{
                      $clicks_array[] = 'null';
                  }
              }
              
              return implode(', ', $clicks_array);
          }
          
          $product_clicks = array();
          
          foreach($arr_clicked_products as $key => $product){
              $product_clicks[$key] = igproof_child_clicks($product, $chart_date_range);
          }

        wp_enqueue_script('igproof-analytics', plugins_url('assets/js/igproof-admin-analytics.js', __FILE__));


        ?>

        <input type="hidden" id="date_labels" value="<?php echo esc_html($date_labels); ?>" />

            <?php
            $clickArray = explode(',', $total_clicks);
            $totalclickArray = array_map('trim', $clickArray); // remove white spaces
            $totalclickArray = array_map('intval', $totalclickArray); // cast everything to int

            $main_arr[] = array( "label" =>  'Total Clicks',
                        "data" => $totalclickArray,
                        "backgroundColor" => "rgba(255, 0, 0, 1)",
                        "fill" => false,);
            foreach($product_clicks as $key => $clicks) {
                $array = array(
                    'label' => esc_html($key),
                    'data' => array_map('trim', explode(', ', $clicks)),
                    'backgroundColor' => "rgba(0, 0, 255, 1)",
                    'fill' => false,
                    'showLine' => 0,
                    'radius' => 0,);
                $main_arr[] = $array;
            }
            wp_add_inline_script('image-edit', ' var arrayFromPhp = ' . json_encode($main_arr) . '; ');
    }


    //Assets
    function igproof_scripts()
    {
        $setting_options = get_option('igProof_api_settings', igproof_get_default_settings());

        if (isset($_COOKIE['wooproof_stopped'])) return;

        //if (is_cart() || is_checkout() || is_order_received_page()) return;

        if (!igproof_allowed_pages()) return;

        wp_enqueue_style('igproof-fonts', '//fonts.googleapis.com/css?family=Source+Sans+Pro:300,300i,600&display=swap');
        wp_enqueue_style('igproof', plugins_url('assets/css/igproof.css', __FILE__));
        wp_enqueue_script('igproof', plugins_url('assets/js/igproof.js', __FILE__), array('jquery'), '1.0', true);

        wp_localize_script('igproof', 'postigproof', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'interval_between_items' => empty($setting_options['items_interval']) ? 10000 : esc_html($setting_options['items_interval']) * 1000,
            'slide_show_time' => empty($setting_options['slide_show_time']) ? 10000 : esc_html($setting_options['slide_show_time']) * 1000,
            'cookie_expiry' => empty($setting_options['cookie_expiry_time']) ? 7 : esc_html($setting_options['cookie_expiry_time']),
            'repeat_orders' => $setting_options['repeat_orders_value']
        ));
    }

    add_action('wp_enqueue_scripts', 'igproof_scripts');


    //AJAX call
    function igproof_get_orders()
    {
        require_once('igproof_engine.php');
        echo '<ul class="ig-proof-ul ig-proof-slideup">';
        echo igproof_get_order_items();
        echo '</ul>';
        die();
    }


    //Checking if the current viewing page is allowed to show IGProof plugin
    add_action("wp", "igproof_allowed_pages");
    function igproof_allowed_pages()
    {
        wp_reset_query();
        $igproof_settings = get_option('igProof_api_settings', igproof_get_default_settings());
        $pages_allowed = empty($igproof_settings)?array():(isset($igproof_settings['widget_location'])?$igproof_settings['widget_location']:array());

        //$pages_allowed = empty($pages_allowed) ? $pages_allowed = get_all_page_ids() : $pages_allowed;

        $is_currentPage_allowed = false;

        global $post;
        $post_id = is_null($post) ? NULL : $post->ID;


        if (is_page($pages_allowed)) {
            $is_currentPage_allowed = true;
        }

        if (( (isset($pages_allowed[0]) && $pages_allowed[0] == 'homepage') || (isset($pages_allowed[1]) && $pages_allowed[1] == 'homepage') ) && is_front_page()) {
            $is_currentPage_allowed = true;
        }

        if (in_array('single_post',$pages_allowed) && is_singular('post')) {
            $is_currentPage_allowed = true;
        }

        if (in_array('prod_arch',$pages_allowed) && ( is_shop() || is_product_category() )) {
            $is_currentPage_allowed = true;
        }
        
        if (in_array('single_prod',$pages_allowed) && is_product()) {
            $is_currentPage_allowed = true;
        }

        $is_currentPage_allowed = apply_filters('is_display_igproof', $is_currentPage_allowed, $post_id);

        return $is_currentPage_allowed;
    }
    
    function igproof_get_default_settings(){
        return array(
            'total_orders_display' => 10,
            'min_product_price' => 15,
            'repeat_orders_value' => 'yes',
            'disable_verified_by' => 'yes',
            'display_order_time' => 'yes',
            'items_interval' => 10,
            'slide_show_time' => 10,
            'cookie_expiry_time' => 7,
            'widget_location' => array('homepage')
        );
    }


    add_action('wp_ajax_nopriv_get_igproof', 'igproof_get_orders');
    add_action('wp_ajax_get_igproof', 'igproof_get_orders');
    add_action('wp_ajax_nopriv_wooProof_clicks_counter', 'igproof_clicks_counter');
    add_action('wp_ajax_wooProof_clicks_counter', 'igproof_clicks_counter');
    add_action('wp_ajax_wooproof_deactivation_feedback', 'igproof_deactivation_feedback');
    
    function igproof_deactivation_feedback(){
        $action = sanitize_text_field($_POST['action']);
        if(isset($action) && 'wooproof_deactivation_feedback' == $action){
            $post_delete_data = sanitize_text_field($_POST['delete_data']);
            $post_deactivation_reason = sanitize_text_field($_POST['deactivation_reason']);
            $delete_data = isset($post_delete_data)?$post_delete_data:NULL;
            $deactivation_reason = isset($post_deactivation_reason)?$post_deactivation_reason:NULL;
            
            if(isset($delete_data) && $delete_data == 'Yes'){
                $delete_data = delete_option('igProof_api_settings');
                
                global $wpdb;
                //$delete_analytics = $wpdb->delete('wooProof_analytics');
                $delete_analytics = $wpdb->query("TRUNCATE TABLE `wooProof_analytics`");
            }
        }
        wp_die();
    }
    
    /*
     * Redirect user to the settings page upon plugin activation
     * ---------------------------------------------------------------------------*/
    add_action( 'activated_plugin', 'igproof_activation_redirect');
    function igproof_activation_redirect( $plugin ) {
        if( $plugin == plugin_basename( __FILE__ ) ) {
            exit( wp_redirect( esc_url(admin_url( 'admin.php?page=ig-woo_proof' )) ) );
        }
    }
    
    /*
    * Add the settings link to the plugin in the WP Admin plugins table
    * -----------------------------*/    

   function igproof_plugin_action_links( $links ) {

           $links[] = '<a href="'.esc_url(admin_url( 'admin.php?page=ig-woo_proof' )).'">Settings</a>';

           return $links;

   }
   add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'igproof_plugin_action_links' );
   
   /*
    * Ask user if data should be also deleted or kept before the plugin is deleted
    * ---------------------------------------------------------------------------------*/ 
   
    add_action('admin_footer', 'igproof_delete_dialog');
    function igproof_delete_dialog(){
        $slug = plugin_basename( __FILE__ );
        ?>
        <div id="wooproof-delete-dialog" class="hidden">
            <form id="wooproof-deactivation-feedback-form">
                <div class="form-row">
                    <label>
                        <input type="checkbox" name="delete_data" />
                        Delete plugin data? Check for yes
                    </label>
                </div>
                <hr />
                <p class="deactivation_reason_label">Please help us improve the plugin by letting us know why you are deactivating?</p>
                <div class="form-row">
                    <label>
                        <input type="radio" name="deactivation_reason" value="I can’t understand the plugin functionality" />
                        I can’t understand the plugin functionality
                    </label>
                </div>
                <div class="form-row">
                    <label>
                        <input type="radio" name="deactivation_reason" value="My site just went broken after installing this plugin" />
                        My site just went broken after installing this plugin
                    </label>
                </div>
                <div class="form-row">
                    <label>
                        <input type="radio" name="deactivation_reason" value="It is just a temporary deactivation for testing" />
                        It is just a temporary deactivation for testing
                    </label>
                </div>
                
            </form>
        </div>
        <?php
    }
}