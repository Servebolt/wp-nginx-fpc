# Nginx fullpage cache plugin for WordPress

## Intended use
This plugin, as with other cache plugins, offers an advanced way to serve content faster and lowers the load impact on the server.
Before using this or any other cache plugin make sure you understand the technical requirements of the server and the limitations of cached content.

Using cache to serve content is usually only beneficial if you have a high traffic load on your website.
A cache setup will not help your server get faster, but it will help prevent overloading it.
It is not a cure if your website is slow without much traffic.

## Installation

1. First create the `mu-plugins` directory inside `wp-content` if it does not exist yet.
1. Copy `nginx-fullpage-cache.php` into the `wp-content/mu-plugins/` directory.


## Development
This plugin has been developed by [Driv Digital](https://www.drivdigital.no).  
The development has been funded by [Raskesider.no](https://www.raskesider.no) and [Norgeshandelen.no](https://www.norgeshandelen.no)