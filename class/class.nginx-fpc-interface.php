<?php

class Nginx_Fpc_Interface {
  static function setup() {
    add_action( 'post_submitbox_start', 'Nginx_Fpc_Interface::sync_checkbox' );
    add_action( 'save_post', 'Nginx_Fpc_Interface::sync_checkbox_submit', 100, 1 );


    add_action( 'admin_menu', 'Nginx_Fpc_Interface::add_admin_menu' );
    add_action( 'admin_init', 'Nginx_Fpc_Interface::settings_init' );
  }


  static function sync_checkbox() {
    $post = get_post();
    if ( !in_array( $post->post_type, ['product', 'post', 'page'] ) ) {
      // Return early if this is not a product
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

  static function sync_checkbox_submit( $post_id ) {
    $post = get_post( $post_id );
    // Only for products
    if ( in_array( $post->post_type, ['product', 'post', 'page'] ) ) {

      // Make sure the request was not triggered from the quick edit form
      if ( !isset( $_POST['nginx-fpc-confirm'] ) ) {
        return;
      }

      $sync = isset( $_POST['nginx-fpc'] );
      if ( FALSE !== get_post_meta( $post_id, '_nginx_fpc', 1 ) ) {
        update_post_meta( $post_id, '_nginx_fpc', $sync ? 'on' : 'off' );
      }
      else {
        add_post_meta( $post_id, '_nginx_fpc', $sync ? 'on' : 'off' );
      }
    }
  }




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


  static function settings_init(  ) {
    register_setting( 'nginx-fpc-options-page', 'fpc_settings' );

    add_settings_section(
      'nginx-fpc-section',
      __( 'Cache settings', 'nginx-fpc' ),
      'Nginx_Fpc_Interface::settings_section_callback',
      'nginx-fpc-options-page'
    );

    $settings = [
      'text_api_key'        => __( 'RaskeSider.no API key', 'nginx-fpc' ),
      'text_api_secret'     => __( 'RaskeSider.no API secret', 'nginx-fpc' ),
      'checkbox_devmode'    => __( 'RaskeSider.no Developer mode', 'nginx-fpc' ),
      // 'select_field_3'   => __( 'Settings field description', 'nginx-fpc' ),
      'textarea_post_types' => __( 'Post types to enable caching for', 'nginx-fpc' ),
    ];

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


  }

  function __callStatic( $name, $args ) {
    static $options = FALSE;
    if ( 0 !== strpos( $name, 'render_' ) ) {
      return FALSE;
    }
    // Explode the name into parts
    if ( !preg_match( '/^render_([^_]*)_(.*)$/', $name, $matches ) ) {
      return FALSE;
    }

    if ( !$options ) {
      $options = get_option( 'fpc_settings' );
    }

    list( $name, $type, $id ) = $matches;
    $format = '';
    $default = $options['fpc_'. $type .'_'. $id];
    if ( 'text' == $type ) {
      $format  = "<input type='text' name='fpc_settings[%s]' value='%s'>";
    }
    elseif ( 'checkbox' == $type ) {
      $format  = "<input type='checkbox' name='fpc_settings[%s]' %s value='1' id='%1\$s'> <label for='%1\$s'>On</label>";
      $default = checked( $options['fpc_checkbox_'. $id], 1, 0 );
    }
    elseif ( 'select' == $type ) {
      $format  = "<select name='fpc_settings[%s]' multiple=multiple>%s</select>";
      // selected( $options['fpc_select_field_3'], 2 );
      $default = '<option value=1>Option 1</option><option value=2>Option 2</option>';
    }
    elseif ( 'textarea' == $type ) {
      $format = "<textarea cols='40' rows='5' name='fpc_settings[%s]'>%s</textarea>";
    }
    printf( $format, 'fpc_'. $type .'_'. $id, $default );
  }


  static function settings_section_callback(  ) {
    // echo __( '', 'nginx-fpc' );
    // Any messages goes here?
  }


  static function options_page(  ) {

    ?>
    <div class="error settings-error">
      <p><strong>Connection to raskesider.no was refused</strong></p>
    </div>
    <div class="error settings-error">
      <p><strong>You must enter API credentials to toggle the developer mode</strong></p>
    </div>
    <div class="updated settings-error">
      <p><strong>Developer mode "On" has been requested</strong></p>
      <p><strong>Developer mode "Off" has been requested</strong></p>
    </div>
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

}

Nginx_Fpc_Interface::setup();