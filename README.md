[![WP Version compatible](https://img.shields.io/badge/WP%205.3--6.2.2-compatible-darkgreen)](https://wordpress.org/download/releases/#branch-53) [![PHP Version compatible](https://img.shields.io/badge/PHP%207.4/8.1/8.2-compatible-darkgreen)](https://www.php.net/supported-versions.php) [![WordOps compatible](https://img.shields.io/badge/WordOps-compatible-darkgreen)](https://wordops.net/) 

# Warp iMagick - WordPress Image Compressor Plugin. Resize, Optimize, Sharpen, Compress, Regenerate, Clone & Convert to WebP - Next Gen Image Format.

* Unlike almost all of other WordPress Image Optimizer Plugins, this plugin is __NOT__ just connector to external Image Optimization Service. Image Optimization is performed by your WordPress site server, free and at the "expense" of your hosting provider CPU time. No external image optimization service/signup required. No limits in number or megabytes of optimized images. No risky/external binaries required to install on your site server. Plugin uses only safe PHP extension software available or installed on your server by your hosting provider: PHP-imagick and PHP-GD. **If you find any comparable and free plugin that is compressing images without using external service, please let me know 😎.**

* Unlike some other WordPress Image Optimization Plugins, this plugin allows you to regenerate images to high-compression/low-quality and back to high-quality/low-compression images. You can't overcompress or unrecoverably degrade images quality by regenerating thumbnails again and again. Uploaded original image quality is always preserved. In some other plugins, once original image is (re) compressed to lower compression quality then on next regeneration, original and regenerated thumbnails are permanently degraded in quality. In this plugin, instead of compressing original, attached image is replaced with optimized version of original, but that can be disabled if you want to show high quality original photo to site visitors.

* Unlike any other plugin I have seen, this plugin has real visual size preview of media image and all generated thumbnails, including WebP clones. When mouse hovers over any image in a preview, a tooltip is shown with size-name (webp clone), pixel-size, file-name and byte-size.

* Because of using server redirection rules, it does't matter if the image is served via HTML ``<img>`` or ``<picture>`` tag, CSS ``background-image:`` style or via JavaScript. It always works!

* Due to flexible configuration and compression quality, Warp iMagick Plugin is endorsed and recommended by photografic site owner [KennyLL support post](https://wordpress.org/support/topic/disable-full-size-compressed-version/#post-14381959).

* Resizing images on upload (optional) allows you to reduce uploaded images to maximal number of pixels used on site. Feature is usefull on WordPress multisite/multiowner installs or on mobile-only sites.

* Converting and cloning images to file-size efficient WebP format, allows you cover site compatibility with older and newer browsers and better pagespeed score by Google Pagespeed Insights and GTmetrix by serving Next-Gen image format.

* Sharpening images allows you to fix blurry WordPress thumbnails.

* WooCommerce and any theme compatible.

* Project is active since 2019 and reached almost 20.000 active installs, in February-April 2022 moved out of WordPress cvs repository. Why? Because of [dispute about plugin TITLE LENGTH in README.txt (See the comment)](https://themekraft.com/open-letter-to-the-wordpress-plugin-review-team/).

* Plugin is extended with [YahnisElsts/plugin-update-checker](https://github.com/YahnisElsts/plugin-update-checker) in order to replace WordPress.org hosting & update service.

* Tested with WordPress version 5.3 - 6.2. Compatible with PHP version 7.3 up to and including 8.2.

* Tested with Apache Server and NGinx Server on [WordOps](https://github.com/WordOps/WordOps) configuration.

* My favorite comment (later removed by someone):
> WordPress.org Forums <noreply@wordpress.org>
> Jan 19, 2021, 8:26 PM
> undisclosed wrote:
> 
> Thank you so much for an amazing plugin, Helped my website from 7 seconds to 2.5 seconds!!! keep up the great work!

* If you like and/or or use this plugin, please share it with your colegues, friends and please award this project with a __GitHub Star__ (see top right on this page).


# Share

* [![Facebook share](https://img.shields.io/badge/Share%20on-Facebook-darkblue)](https://www.facebook.com/sharer/sharer.php?u=https%3A%2F%2Fgithub.com%2Fddur%2FWarp-iMagick%2F)

* [![Twitter share](https://img.shields.io/badge/Share%20on-Twitter-blue)](https://twitter.com/intent/tweet?url=https%3A%2F%2Fgithub.com%2Fddur%2FWarp-iMagick%2F&amp;text=Warp%20iMagick%20WordPress%20Image%20Compressor%20Plugin%3A%20)


# Listed on

* [14 Best WordPress Plugins for Using WebP Images](https://www.cloudzat.com/webp-images-wordpress-plugins/)
* [Awesome WP Speed Up - Image Optimization Plugins](https://github.com/lukecav/awesome-wp-speed-up#image-optimization-plugins)


# Install last WordPress release - Version 1.9.6:

* [__Plugin Reviews__](https://wordpress.org/support/plugin/warp-imagick/reviews/?filter=5)
* [__10.000+ active installs__](https://plugintests.com/plugins/wporg/warp-imagick/latest#)
* [![WP compatibility](https://plugintests.com/plugins/wporg/warp-imagick/wp-badge.svg)](https://plugintests.com/plugins/wporg/warp-imagick/latest)
* [![PHP compatibility](https://plugintests.com/plugins/wporg/warp-imagick/php-badge.svg)](https://plugintests.com/plugins/wporg/warp-imagick/latest)
* Download Warp-iMagick Version 1.9.6 from [WordPress Server](https://downloads.wordpress.org/plugin/warp-imagick.1.9.6.zip)
* Use Your Wordpress Site Admin Menu -> Plugins -> Add New -> Press [Upload Plugin] Button and select just downloaded warp-imagick.1.9.6.zip file. Follow WordPress instructions ....
* __Attention: Any Plugin version (including 1.9.6) released via Wordpress server can't and will __NOT__ be updated from WordPress-Server and can't automatically check for updates on own Update-Server. To be able to check for future version updates, download and install (or update to) latest version from Warp iMagick Update-Server as described in section below (or from this Github repository).__


# Attention: Plugin version 1.9.5.1 may be a spyware.

* In WordPress support forums some users in their [plugin list report having Warp iMagick plugin version 1.9.5.1](https://wordpress.org/support/topic/calculated-fields-form-not-visible/). That version is never released by author and can't be found or downloaded from [WordPress Server](https://downloads.wordpress.org/plugin/warp-imagick.1.9.5.1.zip).


# Install or Update from Update Server:

* Go to [Plugin Update Server](https://warp-imagick.pagespeed.club/)
* Press [Download Plugin] button and download & save free Warp iMagick plugin.
* Use Your Wordpress Site Admin Menu -> Plugins -> Add New -> Press [Upload Plugin] Button and select just downloaded warp-imagick.zip file. Follow WordPress instructions ....


# Register for Automated Updates:

* Open Your Wordpress Site Warp-iMagick Plugin Settings Page at "General Settings" Tab -> "Plugin Settings" Section. Select and copy text from "Plugin Update Hostname" field.
* In another browser Tab/Window, open [Plugin Update Server](https://warp-imagick.pagespeed.club/), press [Automatic Updates] button, (register &) login. In your user profile go to section "Application Passwords". Paste copied "Plugin Update Hostname" into "New Application Password Name" field. Press "Add New Application Password" button and you will be presented with your new password. Copy text from "new password".
* Go back to your Wordpress Site at Warp-iMagick Plugin Settings Page -> General Settings Tab -> Plugin Settings Section and paste your new password into "Plugin Update Password" Field.
* Press [Save Changes] Button.
* __Note__: __Your email will NEVER be sold to anyone or used for spam purposes.__
* __Note__: __You can delete your Update Server user profile and email at any time.__
* __Note__: __You can add/register and get update passwords for more than one Warp iMagick Plugin (for each site/host where is installed).__ Each Warp iMagick Plugin has different "Plugin Update Password" for every WordPress site (hostname).
* __Note__: Without registration and password, plugin should let you know that new update is available but may fail to download & update plugin via WordPress administration panel/interface, either on-click-update or auto-update. In that case you may still have option to download/update plugin from Update Server as described in section above.


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



Below is annotated version of ``/.htaccess`` snippet with options/variations and explanations:
```
# BEGIN Warp-iMagick - First line of .htaccess file.
# Transparently serve WebP images instead of JPEG/PNG to WebP enabled browsers.

<IfModule mod_mime.c>
	AddType image/webp .webp
</IfModule>
<ifModule mod_rewrite.c>
	RewriteEngine On
	RewriteBase /
	
	# If browser accepts WebP files?
	RewriteCond %{HTTP_ACCEPT} image/webp

	# If request is inside wp-content directory
	RewriteCond %{REQUEST_URI} /wp-content/

	# If request is for jpg/png file?
	RewriteCond %{REQUEST_URI} (?i)(.*)\.(?i)(jpe?g|png)$
	
	# If JPG/PNG file exists?
	RewriteCond %{REQUEST_FILENAME} -f

	# If WebP Clone file exists?
	RewriteCond %{REQUEST_FILENAME}.webp -f

	# Transparently serve existing WebP Clone.
	# For each requested JPEG/PNG image, returns HTTP code [200].
	# This is default configuration. Same as by WordOps configured NGinx server.
	# PROS:
		# Fastest, no redirection time taken, ever.
		# If Webp Clone image is deleted/removed, browser will receive JPEG/PNG image content.
	# CONS OR PROS?:
		# If Webp Clone image is found, via browser saved JPEG/PNG image will have JPEG/PNG name.extension but WebP content.
		# Some image viewers may not recognize saved JPEG/PNG with WebP content and may declare saved image invalid.
	# CONS:
		# If CDN/external cache is used, it should support Vary: header. CDN cache may choose to cache none, one or both image versions.
	# DEFAULT REWRITE RULE - if you enable (uncomment) any OPTIONAL REWRITE RULE below, you MUST comment-out this RewriteRule.
	RewriteRule .* %1.%2.webp [T=image/webp,E=webp:1,L]

	# Temporary redirect to existing WebP Clone.
	# For each requested JPEG/PNG image, if WebP Clone exists, returns HTTP code [302], else [200]
	# This is an optional, disabled (commented-out) RewriteRule configuration.
	# PROS:
		# Via browser saved image will have JPEG/PNG/WebP extension and extension matching content,
		# If CDN/external cache is used, CDN/cache does not have to support Vary: header.
		# If Webp Clone image is deleted/removed, browser will receive JPEG/PNG original.
	# CONS:
		# Redirection takes small/short time every time when JPEG/PNG image is requested.
	# OPTIONAL REWRITE RULE - If you enable/un-comment this RewriteRule, you MUST comment-out other two RewriteRule-s.
	# RewriteRule .* %1\.%2\.webp [R=302,L]

	# Permanent redirect to existing WebP Clone.
	# For each requested JPG/PNG image, if WebP Clone exists returns HTTP code [301] once and then [200], else [200].
	# This is an optional, disabled (commented-out) RewriteRule configuration.
	# PROS:
		# Redirection takes small/short time but only first time JPEG/PNG image with WebP clone is requested.
		# Via browser saved image will have JPEG/PNG/WebP extension and extension matching content,
		# If CDN/external cache is used, it does not have to support Vary: header.
	# CONS:
		# If Webp Clone image is deleted/removed, browser will receive error 404.
		# For browser to receive JPEG/PNG again, user has to clear your site data in his browser site data cache.
	# OPTIONAL REWRITE RULE - If you enable/un-comment this RewriteRule, you MUST comment-out other two RewriteRule-s.
	# RewriteRule .* %1.%2.webp [R=301,L]

	<IfModule mod_headers.c>
		Header append Vary Accept env=REDIRECT_webp
	</IfModule>

</IfModule>
# END Warp-iMagick

```

__Looking for more details? Then press [Help] button at the top-right of Warp iMagick Settings page.__


# __Post Install: [WordOps Nginx Server](https://wordops.net/) WebP Configuration:__

No Nginx configuration changes are required to automatically serve Webp images to WebP enabled browsers. WebP redirection just works with default [WordOps Nginx Server configuration](https://github.com/WordOps/WordOps).


# __Post Install: [Open Litespeed](https://openlitespeed.org/) WebP Configuration:__

Using Open Litespeed was never tested nor recommended for this plugin. I especially discourage you from buying services from a2hosting which has redistributed WP versions (below 1.9.6) of Warp iMagick plugin privatelly, only to it's paying clients. They used Warp iMagick name for advertizing purposes without linking to plugin web page. Although they probably having thousands of Warp iMagick plugin installs, they refused to cooperate with plugin author. I do not know what version or modifications you got from them. Plugin support will be provided only to latest original unmodified version released by author. So, if you are client of a2hosting with older or modified Warp iMagick plugin, do not expect help here.

:vulcan_salute:
