# Nginx fullpage cache plugin for WordPress

## Intended use

This plugin, as with other cache plugins, offers an advanced way to serve content faster and lowers the load impact on the server.
Before using this or any other cache plugin make sure you understand the technical requirements of the server and the limitations of cached content.

Using cache to serve content is usually only beneficial if you have a high traffic load on your website.
A cache setup will not help your server get faster, but it will help prevent overloading it.
It is not a cure if your website is slow without much traffic.

This plugin is very efficient for sites with high traffic to single pages.

## What the plugin does

This cache plugin leverages Servebolt.coms application stack, by enabling nginx (or rather tengine) to cache the HTML output of php files. It is a very simple, yet solid implementation that will bypass the cache when necessary, and that will serve cached content in a fairly secure manner. The HTML ouput will only be cached for a period of 10 minutes, but this may provide great performance improvements on landing pages for campaigns, or other high traffic pages.

The cache is bypassed whenever a user establishes a session (logs in, checks out etc).

## What to test after installation

You must verify that checkout, login, cart and other dyamic functionality behaves like expected after installing this plugin. You should do this in multiple browsers, and make sure your cookies and sessions are deleted before doing so.

## Installation

1. First create the `mu-plugins` directory inside `wp-content` if it does not exist yet.
1. Copy `nginx-fullpage-cache.php` and the `class` folder into the `wp-content/mu-plugins/` directory.
1. Activate Full Page Caching in the Servebolt.com control panel, by setting `Enable caching of static files` to `All` under the section `Front End Caching`

### Important when deploying using composer
Add the following to your projects composer.json to ensure that the plugin installs in the correct folder
```"extra": {
    "installer-paths": {
    	"wp-content/mu-plugins": ["servebolt/wp-nginx-fpc"]
    	}
    }```

## Development
This plugin has been developed by [Driv Digital](https://www.drivdigital.no) and is funded by [Servebolt.com](https://servebolt.com)