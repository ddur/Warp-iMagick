[![WP Version compatible](https://img.shields.io/badge/WP%205.3--6.7.1-compatible%20(after%20update)-darkgreen)](https://wordpress.org/download/releases/#branch-53)
[![PHP Version compatible](https://img.shields.io/badge/PHP%207.4/8.1/8.2-compatible-darkgreen)](https://www.php.net/supported-versions.php)
[![WordOps compatible](https://img.shields.io/badge/WordOps-compatible-darkgreen)](https://wordops.net/)

# Warp iMagick - WebP Converter - Plugin 4 WordPress. Resize, Optimize, Sharpen, Compress, Regenerate, Convert/Clone to WebP - Next Generation Image Format.

* Unlike almost all of other WordPress Image Optimizer Plugins, this plugin is __NOT__ just connector to external Image Optimization Service. Image Optimization is performed by your WordPress site server, free and at the "expense" of your hosting provider CPU time. No external image optimization service/signup required. No limits in number or megabytes of optimized images. No risky/external binaries required to install on your site server. Plugin uses only safe PHP extension software available or installed on your server by your hosting provider: PHP-imagick and PHP-GD.

* Unlike some other WordPress Image Optimization Plugins, this plugin allows you to regenerate images to high-compression/low-quality and back to high-quality/low-compression images. You can't overcompress or unrecoverably degrade images quality by regenerating thumbnails again and again. Uploaded original image quality is always preserved. In some other plugins, once original image is (re) compressed to lower compression quality then on next regeneration, original and regenerated thumbnails are permanently degraded in quality. In this plugin, instead of compressing uploaded original, attached image is replaced with optimized version of uploaded original, but that can be disabled if you want to show high quality original upload photo to site visitors.

* Unlike any other plugin I have seen, this plugin has real visual size preview of media image and all generated thumbnails, including WebP clones. When mouse hovers over any image in a preview, a tooltip is shown with size-name (webp clone), pixel-size, file-name and byte-size.

* Because of using server redirection rules, it does't matter if the image is served via HTML ``<img>`` or ``<picture>`` tag, CSS ``background-image:`` style or via JavaScript. It always works!

* Due to flexible configuration and compression quality, Warp iMagick Plugin is endorsed and recommended by photografic site owner [KennyLL support post](https://wordpress.org/support/topic/disable-full-size-compressed-version/#post-14381959).

* Resizing images on upload (optional) allows you to reduce uploaded images to maximal number of pixels used on site. Feature is usefull on WordPress multisite/multiowner installs or on mobile-only sites.

* Converting and cloning images to file-size efficient WebP format, allows you cover site compatibility with older and newer browsers and better pagespeed score by Google Pagespeed Insights and GTmetrix by serving Next-Gen image format.

* Sharpening images allows you to fix blurry WordPress thumbnails.

* WooCommerce and any theme compatible.

* Project is published & active since 2019 and reached almost 20.000 active installs, in February-April 2022 moved out of WordPress cvs repository. Why? Because of [dispute about plugin TITLE LENGTH in README.txt (See the comment)](https://themekraft.com/open-letter-to-the-wordpress-plugin-review-team/).

* Plugin is extended with [YahnisElsts/plugin-update-checker](https://github.com/YahnisElsts/plugin-update-checker) in order to replace WordPress.org hosting & update service.

* Tested with WordPress version 5.3 - 6.7.1 Compatible with PHP version 7.3 up to and including 8.2.

* Tested with Apache Server and NGinx Server on [WordOps](https://github.com/WordOps/WordOps) configuration.

# Do not install last WordPress release - Version 1.9.6:

* [__Plugin Reviews__](https://wordpress.org/support/plugin/warp-imagick/reviews/?filter=5)
* [__10.000+ active installs__](https://plugintests.com/plugins/wporg/warp-imagick/latest#)
* [![WP compatibility](https://plugintests.com/plugins/wporg/warp-imagick/wp-badge.svg)](https://plugintests.com/plugins/wporg/warp-imagick/latest)
* [![PHP compatibility](https://plugintests.com/plugins/wporg/warp-imagick/php-badge.svg)](https://plugintests.com/plugins/wporg/warp-imagick/latest)
* ~~Download Warp-iMagick Version 1.9.6 from [WordPress Server](https://downloads.wordpress.org/plugin/warp-imagick.1.9.6.zip)~~
* Use Your Wordpress Site Admin Menu -> Plugins -> Add New -> Press [Upload Plugin] Button and select just downloaded warp-imagick.1.9.6.zip file. Follow WordPress instructions ....
* __Attention: Any Plugin version (including 1.9.6) released via Wordpress server can't and will __NOT__ be updated from WordPress-Server and can't automatically check for updates on own Update-Server. To be able to check for future version updates, download and install (or update to) latest version from Warp iMagick Update-Server as described in section below (or from this Github repository).__


# Attention: Plugin version 1.9.5.1 may be a spyware.

* In WordPress support forums some users in their [plugin list report having Warp iMagick plugin version 1.9.5.1](https://wordpress.org/support/topic/calculated-fields-form-not-visible/). That version is never released by author and can't be found or downloaded from [WordPress Server](https://downloads.wordpress.org/plugin/warp-imagick.1.9.5.1.zip).


# Install or Update from this repository Releases:

* Go to this repository Releases and download latest release.
* Use Your Wordpress Site Admin Menu -> Plugins -> Add New -> Press [Upload Plugin] Button and select just downloaded warp-imagick.zip file. Follow WordPress instructions ....

# Install or Update from Update Server [currently not available]:

* ~~Go to [Plugin Update Server](https://warp-imagick.pagespeed.club/)~~
* ~~Press [Download Plugin] button and download & save free Warp iMagick plugin.~~
* ~~Use Your Wordpress Site Admin Menu -> Plugins -> Add New -> Press [Upload Plugin] Button and select just downloaded warp-imagick.zip file. Follow WordPress instructions ....~~

# __Post Install: Apache Server WebP Configuration:__

__To serve (from JPEG/PNG converted) WebP clones to WebP enabled browsers, you must configure your Apache server!__

__Configuring server to serve WebP images is fastest (page-speed) way to deliver WebP images. Server configuration is not changed by this plugin because programmatic change could potentially break your site. You will have to DIY (Do It Yourself) or break your site by yourself.__ 😎

To __safely__ modify your Apache ``/.htaccess`` file, use [Htaccess File Editor plugin](https://wordpress.org/plugins/wp-htaccess-editor/).
If you use other ways to modify/edit Apache ``/.htaccess`` file, always backup/save your original ``/.htaccess`` file __before__ applying changes!

Below is Apache ``/.htaccess`` configuration snippet that should work on most Apache servers. Snippet is tested on Apache 2.4 installed on Linux Debian.

__Use Copy button at top-right (visible on mouse hover) of code-snippet below and paste snippet at the top of ``/.htaccess`` file. Do not remove WordPress configuration or any other content of ``/.htaccess`` file.__

```
# BEGIN Warp-iMagick - First line of .htaccess file.
# Serve WebP images instead of JPEG/PNG to WebP enabled browsers.

<IfModule mod_mime.c>
	AddType image/webp .webp
</IfModule>
<ifModule mod_rewrite.c>
	RewriteEngine On
	RewriteBase /

	RewriteCond %{HTTP_ACCEPT} image/webp
	RewriteCond %{REQUEST_URI} /wp-content/
	RewriteCond %{REQUEST_URI} (.*)\.(?i)(jpe?g|png)$
	RewriteCond %{REQUEST_FILENAME} -f
	RewriteCond %{REQUEST_FILENAME}.webp -f
	RewriteRule .* %1.%2.webp [T=image/webp,E=webp:1,L]

	<IfModule mod_headers.c>
		Header append Vary Accept env=REDIRECT_webp
	</IfModule>
</IfModule>

# END Warp-iMagick


```
After Apache ``/.htaccess`` file is successfully modified and your site is serving WebP images, you don't need [Htaccess File Editor plugin](https://wordpress.org/plugins/wp-htaccess-editor/) anymore and feel free to __uninstall__ it.

__Looking for more details? Then press [Help] button at the top-right of Warp iMagick Settings page.__


# __Post Install: [WordOps Nginx Server](https://wordops.net/) WebP Configuration:__

No Nginx configuration changes are required to automatically serve Webp images to WebP enabled browsers. WebP redirection just works with default [WordOps Nginx Server configuration](https://github.com/WordOps/WordOps).


# __Post Install: [Open Litespeed](https://openlitespeed.org/) WebP Configuration:__

Using Open Litespeed was never tested nor recommended for this plugin.

:vulcan_salute:
