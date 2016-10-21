<?php
/**
 * Plugin Name: Nginx ⚡ Full page cache
 * Description:
 * Version: 0.0.2
 * Author: Driv Digital
 * Contributors: Eivin Landa
 * Author URI: https://github.com/drivdigital
 */

// Include the class files
require_once 'class/class.nginx-fpc.php';
require_once 'class/class.nginx-fpc-interface.php';

// Setup the cache
Nginx_Fpc::setup();

// Setup the admin interface
Nginx_Fpc_Interface::setup();
