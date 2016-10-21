<?php

class Nginx_Fpc_Interface {

  // Cache choices for the cache type setting
  static $cache_choices = [
    0 => 'none',
    1 => 'static',
    2 => 'all'
  ];

  /**
   * Setup
   */
  static function setup() {
    // Add a checkbox for every post
    add_action( 'post_submitbox_start', __CLASS__.'::cache_checkbox' );
    add_action( 'save_post', __CLASS__.'::cache_checkbox_submit', 100, 1 );

    // Add a settings page for cache settings
    add_action( 'admin_menu', __CLASS__.'::add_admin_menu' );
    add_action( 'admin_init', __CLASS__.'::settings_init' );
  }

  /**
   * Sync checkbox
   */
  static function cache_checkbox() {
    $post = get_post();
    // Only for allowed post-types
    if ( !in_array( $post->post_type, Nginx_Fpc::cacheable_post_types() ) ) {
      return;
    }

    $is_checked = get_post_meta( $post->ID, '_nginx_fpc', 1 ) !== 'off' ? ' checked="checked"' : '';
    ?>
    <div>
    <input type="hidden" name="nginx-fpc-confirm" value="1">
    <input type="checkbox" name="nginx-fpc" id="nginx-fpc"<?php echo $is_checked ?>><label for="nginx-fpc">Cache this post</label>
    </div>
  <?php
  }

  /**
   * Cache checkbox submit
   * @param  int $post_id
   */
  static function cache_checkbox_submit( $post_id ) {
    $post = get_post( $post_id );
    // Only for allowed post-types
    if ( ! in_array( $post->post_type, Nginx_Fpc::cacheable_post_types() ) ) {
      return;
    }

    // Make sure the request was not triggered from the quick edit form
    if ( ! isset( $_POST['nginx-fpc-confirm'] ) ) {
      return;
    }

    // Was the checkbox checked?
    $sync = isset( $_POST['nginx-fpc'] );

    // Set the post cache meta-setting
    if ( FALSE !== get_post_meta( $post_id, '_nginx_fpc', 1 ) ) {
      update_post_meta( $post_id, '_nginx_fpc', $sync ? 'on' : 'off' );
    }
    else {
      add_post_meta( $post_id, '_nginx_fpc', $sync ? 'on' : 'off' );
    }
  }

  /**
   * Add an admin menu item (under "Settings")
   */
  static function add_admin_menu(  ) {
    add_options_page(
      // Page <title>
      'Nginx Fullpage Cache',
      // Menu name
      'Nginx cache',
      // Required capabilities
      'manage_options',
      // Slug-name
      'nginx-fullpage-cache',
      // Callback function
      'Nginx_Fpc_Interface::options_page'
    );
  }

  /**
   * Settings init
   * Initialise settings and run submit handling
   */
  static function settings_init() {
    $vhost_id = self::get_vhost_number();
    register_setting( 'nginx-fpc-options-page', 'fpc_settings' );

    // Add a section for the fpc settings
    add_settings_section(
      'nginx-fpc-section',
      __( 'Cache settings for ', 'nginx-fpc' ) ." $vhost_id",
      'Nginx_Fpc_Interface::settings_section_callback',
      'nginx-fpc-options-page'
    );

    $settings = [
      'text_api_key'        => __( 'Servebolt.com API key', 'nginx-fpc' ),
      // API Secret is not yet implemented
      // 'text_api_secret'     => __( 'Servebolt.com API secret', 'nginx-fpc' ),
      // Developer mode (pagespeed) [on|off] is not yet implemented
      // 'checkbox_devmode'    => __( 'Servebolt.com Developer mode', 'nginx-fpc' ),
      // Button for flushing tengine cache
      // 'button_flush_cache'    => __( 'Flush cache', 'nginx-fpc' ),
      'select_cache_status'   => __( 'Cache type', 'nginx-fpc' ),
      'textarea_post_types' => __( 'Post types to enable caching for', 'nginx-fpc' ),
    ];

    // Filter cache status
    add_filter( 'nginx_fpc_field_default', 'Nginx_Fpc_Interface::filter_default_cache_status', 10 , 2 );

    // Run the settings through the add_settings_field function
    foreach ( $settings as $key => $value ) {
      $params = [
        "fpc_$key",
        $value,
        "Nginx_Fpc_Interface::render_$key",
        'nginx-fpc-options-page',
        'nginx-fpc-section'
      ];
      call_user_func_array( 'add_settings_field', $params );
    }

    // Submit handling
    if ( isset( $_POST['fpc_settings'], $_POST['fpc_settings']['fpc_cache_status'] ) ) {
      $choice = $_POST['fpc_settings']['fpc_cache_status'];
      if ( -1 != $choice && self::get_cache_status() != $choice ) {
        $choices = self::$cache_choices;
        $result = self::set_cache_status( $choice );
        // Add a message
        if ( 'success' != @$result->status )
          self::add_message( 'Could not update the nginx cache setting. Connection error!', 'error' );
        else
          self::add_message( 'Cache config set to <strong>'. $choices[$choice] .'</strong>' );
      }
    }
  }

  /**
   * Add message
   * @param string $message Message to be displayed
   * @param string $type    The type of message, "updated" or "error"
   */
  static function add_message( $message, $type = 'updated' ) {
    $messages = get_option( 'nginx_fpc_messages', [] );
    if ( !isset( $messages[$type] ) )
      $messages[$type] = [];
    $messages[$type][] = $message;
    update_option( 'nginx_fpc_messages', $messages );
  }

  /**
   * Call static (Magic method)
   * Used for render_ method calls
   * @param  string $name Called method name
   * @param  array  $args Arguments used
   * @return mixed        False for unmatched method names
   */
  static function __callStatic( $name, $args ) {
    static $options = FALSE;
    if ( 0 !== strpos( $name, 'render_' ) ) {
      return FALSE;
    }
    // Explode the name into parts
    if ( !preg_match( '/^render_([^_]*)_(.*)$/', $name, $matches ) ) {
      return FALSE;
    }
    // Assign parts to variables
    list( $name, $type, $id ) = $matches;

    // Get cache options
    if ( !$options ) {
      $options = get_option( 'fpc_settings' );
    }

    // Set defaults
    $format = '';
    $default = @$options['fpc_'. $id];

    // Parse different field types
    if ( 'text' == $type ) {
      $format  = "<input type=text size=25 name='fpc_settings[%s]' value='%s'>";
    }
    elseif ( 'checkbox' == $type ) {
      $format  = "<input type='checkbox' name='fpc_settings[%s]' %s value='1' id='%1\$s'> <label for='%1\$s'>On</label>";
      $default = checked( @$options['fpc_'. $id], 1, 0 );
    }
    elseif ( 'select' == $type ) {
      // Any select field should be parsed with the field_default filter
      $format  = "<select name='fpc_settings[%s]'>%s</select>";
      $default = '<option>- empty -</option>';
    }
    elseif ( 'textarea' == $type ) {
      $format = "<textarea cols='40' rows='5' name='fpc_settings[%s]'>%s</textarea>";
    }
    // Apply filters
    $format  = apply_filters( 'nginx_fpc_field_format',  $format,  $id, $type );
    $default = apply_filters( 'nginx_fpc_field_default', $default, $id, $type );
    printf( $format, 'fpc_'. $id, $default );

    return TRUE;
  }

  /**
   * Filter {$default} cache_status
   * @param  string $default HTML for the <option> tags
   * @param  string $id      id handle used to identify the correct field
   * @return                 HTML for the <option> tags
   */
  static function filter_default_cache_status( $default, $id ) {
    // Only process cache status
    if ( 'cache_status' !== $id )
      return $default;

    if ( !self::get_api_key() )
      return '<option value=-1>- missing api key -</option>';

    // Get available choices and selected status
    $choices = self::$cache_choices;
    $selected = self::get_cache_status();
    if ( -1 !== $selected ) {
      $default = '';
      foreach ( $choices as $key => $choice ) {
        $default .= sprintf( '<option value=%s%s>%s</option>', $key, selected( $selected, $key, 0 ), $choice );
      }
    }
    else {
      // Could not fetch the cache status.
      // Probably because of an API error
      $default = '<option value=-1>- api error -</option>';
    }
    return $default;
  }

  /**
   * Settings section callback
   * Render html for the section head
   */
  static function settings_section_callback() {
    // Empty
  }

  /**
   * Options page callback
   */
  static function options_page(  ) {
    $messages = get_option( 'nginx_fpc_messages', [] );
    update_option( 'nginx_fpc_messages', [] );

    foreach ($messages as $class_attr => $message ) {
      printf( '<div class="%s settings-error">%s</div>', $class_attr, '<p>'. implode( '</p><p>', $message ) .'</p>' );
    }
    ?>
    <form action='options.php' method='post'>

      <h2>Nginx Fullpage Cache</h2>

      <?php
      settings_fields( 'nginx-fpc-options-page' );
      do_settings_sections( 'nginx-fpc-options-page' );
      submit_button();
      ?>

    </form>
    <?php

  }

  /**
   * Get the api key
   * @return string Servebolt API key
   */
  static function get_api_key() {
    // Get the plugin options
    $options = get_option( 'fpc_settings' );
    return $options['fpc_api_key'];
  }
  /**
   * Api Endpoint
   * @param  string $which Which endpoint to get
   * @return mixed         (string) Endpoint on success. Otherwise FALSE
   */
  static function api_endpoint( $which = 'nginx' ) {
    $vhost_id = self::get_vhost_number();
    if ( -1 === $vhost_id )
      return FALSE;

    // Choose which endpoint to use
    if ( 'nginx' == $which )
      $endpoint = sprintf( '/api/vhost/%s/feature/nginx_simplecache', $vhost_id );
    else
      return FALSE;

    // Get the API-key
    $key = self::get_api_key();
    // API requires a KEY, return false if no key is set
    if ( !$key )
      return FALSE;
    $url = 'https://raskesider.no';

    return "{$url}{$endpoint}?token={$key}";
  }

  /**
   * Get the cache status from raskesider
   * @return int The status on success, -1 on failure
   */
  static function get_cache_status() {
    $endpoint = self::api_endpoint();
    if ( !$endpoint )
      return -1;
    $request = wp_remote_get( $endpoint );
    $value = -1;
    // Validate response
    if ( $request && !is_wp_error( $request ) ) {
      if ( 200 == $request['response']['code'] ) {
        $json = json_decode( $request['body'] );
        $value = $json->data->value;
      }
    }

    return $value;
  }
  /**
   * Set the cache status
   * @param int $value The value to set. Valid choices are: [0|none, 1|static files, 2|all]
   */
  static function set_cache_status( $value ) {
    if ( !in_array( $value, [0, 1, 2] ) )
      return FALSE;
    $vhost_id = self::get_vhost_number();
    $request = wp_remote_post( self::api_endpoint(), [
      'body' => ['value' => $value]
    ] );

    // Validate response
    if ( $request && !is_wp_error( $request ) ) {
      if ( 200 == $request['response']['code'] ) {
        $json = json_decode( $request['body'] );
      }
    }

    return $json;
  }
  /**
   * Get vhost number
   * @return int vhost number
   */
  static function get_vhost_number() {
    $reg = '/^\/'
    .'[^\/]*' .'\/'       // Usually /kunder/
    .'[^\/]*_(\d+)' .'\/' // Webhost number
    .'[^\/]*_(\d+)' .'\/' // Vhost number
    .'/';// End
    if ( !preg_match( $reg, getcwd(), $matches ) )
      return -1;

    return $matches[2];
  }

}
