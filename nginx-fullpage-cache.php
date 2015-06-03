<?php
class Nginx_Fullpage_Cache {
  static function setup() {
    require_once 'class/class.nginx-fpc-interface.php';
    add_filter( 'posts_results', array( 'Nginx_Fullpage_Cache', 'set_headers' ) );
  }

  /**
   * Set cache headers
   * Determine and set the type of headers to be used
   */
  static function set_headers( $posts ) {
    if ( is_admin() ) {
      nocache_headers();
      return $posts;
    }
    // Only trigger this function once.
    remove_filter( 'posts_results', 'nginx_cache_headers' );

    // Cache all archives
    if ( !is_archive() ) {
      // Loop through selected posts
      foreach ( $posts as $post ) {
        // Check if cache is disabled for the post
        if ( 'off' === get_post_meta( $post->ID, '_nginx_fpc', true ) ) {
          // Disable caching for this page
          nocache_headers();
          return $posts;
        }
      }
    }
    if ( is_singular() || is_page() || is_archive() ) {
      self::cache_headers();
    }
    else {
      self::no_cache_headers();
    }
    return $posts;
  }

  /**
   * Cache headers
   * Print headers that encourage caching
   */
  static function cache_headers() {
    header_remove( 'Cache-Control' );
    header_remove( 'Pragma' );
    header_remove( 'Expires' );
    header( 'Pragma: public' );
    header( 'Cache-Plugin: active' );
  }

  /**
   * No chache headers
   * Print headers that prevent caching
   */
  static function no_cache_headers() {
    // Set no-cache headers
    header( 'Cache-Control:max-age=0, no-cache' );
    header( 'Pragma: no-cache' );
    header( 'Cache-Plugin: pending' );
  }
}

Nginx_Fullpage_Cache::setup();