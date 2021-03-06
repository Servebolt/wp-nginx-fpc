<?php
class Nginx_Fpc {
  static $post_types = [];
  /**
   * Setup
   */
  static function setup() {
    // Attach the method for setting cache headers
    add_filter( 'admin_init', __CLASS__.'::set_headers' );
    add_filter( 'posts_results', __CLASS__.'::set_headers' );
    add_filter( 'template_include', __CLASS__.'::last_call' );
  }

  /**
   * Last call
   * Run a last call to the set headers function before the template is loaded
   */
  static function last_call( $template ) {
    self::set_headers([get_post()]);
    return $template;
  }

  /**
   * Set cache headers
   * Determine and set the type of headers to be used
   */
  static function set_headers( $posts ) {
    global $wp_query;
    static $already_set = false;
    if ( $already_set ) {
      return $posts;
    }
    $already_set = true;
    // Set no-cache for all admin pages
    if ( is_admin() || is_user_logged_in() ) {
      self::no_cache_headers();
      // No cache cookie for logged in users
      if ( is_user_logged_in() ) {
        setcookie( "no_cache", 1, $_SERVER['REQUEST_TIME'] + 3600, COOKIEPATH, COOKIE_DOMAIN );
      }
      return $posts;
    }

    if ( ! isset( $wp_query ) || ! get_post_type() ) {
      $already_set = false;
      return $posts;
    }

    // Only trigger this function once.
    remove_filter( 'posts_results', __CLASS__.'::set_headers' );

    if ( ( is_front_page() || is_singular() || is_page() ) && in_array( get_post_type(), self::cacheable_post_types() ) ) {

      // Check if cache is disabled for the post
      foreach ( $posts as $post ) {
        if ( 'off' === get_post_meta( $post->ID, '_nginx_fpc', true ) ) {
          // Disable caching for this page / post
          self::no_cache_headers();
          return $posts;
        }
      }
      // Make sure the post type can be cached
      self::$post_types[] = get_post_type();
      self::cache_headers();
    }
    elseif ( is_archive() && self::can_cache_archive( $posts ) ) {
      // Make sure the archive has only cachable posts
      self::cache_headers();
    }
    else {
      // Default to no-cache headers
      self::no_cache_headers();
    }
    return $posts;
  }

  /**
   * Can cache archive
   * @param  array $posts Posts in the archive
   * @return boolean      Return true if all posts are cacheable
   */
  static function can_cache_archive( $posts ) {
    foreach ( $posts as $post ) {
      if ( !in_array( $post->post_type, self::cacheable_post_types() ) )
        return FALSE;
      elseif ( !in_array( $post->post_type, self::$post_types ) )
        self::$post_types[] = $post->post_type;
    }
    return TRUE;
  }

  /**
   * Cache headers
   * Print headers that encourage caching
   */
  static function cache_headers() {
    header_remove( 'Cache-Control' );
    header_remove( 'Pragma' );
    header_remove( 'Expires' );
    // Allow browser to cache content for 10 minutes
    header( 'Cache-Control:max-age=600, public' );
    header( 'Pragma: public' );
    // Expire in front-end caches and proxies after 10 minutes
    header( 'Expires: '. gmdate('D, d M Y H:i:s', $_SERVER['REQUEST_TIME'] + 600) .' GMT');
    header( 'X-Cache-Plugin: active' );
  }

  /**
   * No chache headers
   * Print headers that prevent caching
   */
  static function no_cache_headers() {
    header( 'Cache-Control: max-age=0,no-cache' );
    header( 'Pragma: no-cache' );
    header( 'X-Cache-Plugin: active' );
  }

  /**
   * Cacheable post types
   * @return array A list of cacheable post types
   */
  static function cacheable_post_types() {
    // Set default cacheable post types
    $post_types = ['post', 'page', 'product'];
    // Check option for values
    $options = get_option( 'fpc_settings' );
    if ( isset( $options['fpc_post_types'] ) ) {
      // Parse comma separated list into an array
      $fpc_post_types = $options['fpc_post_types'];
      $fpc_post_types = str_replace( "\n", ',', $fpc_post_types );
      $post_types = explode( ',', $fpc_post_types );
      foreach ( $post_types as &$post_type ) {
        $post_type = trim( $post_type );
      }
      // Filter empty values
      $post_types = array_filter( $post_types );
    }
    return $post_types;
  }
}
