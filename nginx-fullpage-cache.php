<?php
class Nginx_Fullpage_Cache {
  static $post_types = [];
  /**
   * Setup
   */
  static function setup() {
    // Include the interface
    require_once 'class/class.nginx-fpc-interface.php';

    // Attach the method for setting cache headers
    add_filter( 'posts_results', array( 'Nginx_Fullpage_Cache', 'set_headers' ) );
  }

  /**
   * Set cache headers
   * Determine and set the type of headers to be used
   */
  static function set_headers( $posts ) {
    // No cache for logged in users
    if ( is_user_logged_in() ) {
      setcookie( "no_cache", 1, $_SERVER['REQUEST_TIME'] + 3600, COOKIEPATH, COOKIE_DOMAIN );
    }
    // Set no-cache for all admin pages
    if ( is_admin() || is_user_logged_in() ) {
      nocache_headers();
      return $posts;
    }
    // Only trigger this function once.
    remove_filter( 'posts_results', 'nginx_cache_headers' );


    if ( ( is_singular() || is_page() ) && in_array( get_post_type(), self::cacheable_post_types() ) ) {

      // Check if cache is disabled for the post
      foreach ( $posts as $post ) {
        if ( 'off' === get_post_meta( $post->ID, '_nginx_fpc', true ) ) {
          // Disable caching for this page / post
          nocache_headers();
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
    // Allow browser to cache content for 1 hour
    header( 'Cache-Control: max-age=3600,public' );
    header( 'Pragma: public' );
    header( 'Cache-Plugin: active' );
    header( 'Cache-Post-Type: '. implode( ',', self::$post_types ) );
  }

  /**
   * No chache headers
   * Print headers that prevent caching
   */
  static function no_cache_headers() {
    header( 'Cache-Control: max-age=0,no-cache' );
    header( 'Pragma: no-cache' );
    header( 'Cache-Plugin: pending' );
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
      $post_types = explode( ',', $options['fpc_post_types'] );
      foreach ( $post_types as &$post_type ) {
        $post_type = trim( $post_type );
      }
      // Filter empty values
      $post_types = array_filter( $post_types );
    }
    return $post_types;
  }
}

// Start the plugin
Nginx_Fullpage_Cache::setup();