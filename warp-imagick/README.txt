=== Warp Compress, Sharpen, Optimize Images. Convert WebP. Resize Uploads ===
Plugin URI: https://warp-imagick.pagespeed.club/
Author: © 2017-2025, Dragan Đurić. All rights reserved.
License: GPLv2
Requires PHP: 7.4
Tested up to: 6.7.1
Stable tag: 1.12.1
Requires at least: 5.3

Optimize Images On-Site. No Cloud-Service/Signup. Convert to WebP On Upload/Regenerate. Resize Uploads. Set Sharpness, Quality & Big-Image-Threshold.

== Description ==

* **Warp iMagick | Optimize Images: EASY to use, PRIVATE and FREE.**
Install, activate, configure and start uploading new media images. To bulk (re)compress existing media images use ["Regenerate Thumbnails Plugin"](https://wordpress.org/plugins/regenerate-thumbnails/) or ["WP CLI media regenerate" command](https://developer.wordpress.org/cli/commands/media/regenerate/).
Image Optimization is performed on-site, free and at the "expense" of your site hosting provider. No limits in number or megabytes of optimized images. Plugin uses only PHP software installed on your server. By PHP-imagick and PHP-GD (PHP extensions). No external cloud/service or signup required.


* **Warp iMagick | Convert WebP: enables your site to serve images in next-gen formats.**
Original upload JPEG/PNG images are copied into compressed & optimized JPEG/PNG/WebP media types of original size and subsizes/thumbnails. WebP images are served by Apache/Nginx configuration. JPEG/PNG images are served only for older (NO-WebP compatible) browsers.


* **Warp iMagick | Image Compression Quality: flexible, easy to configure.**
Set JPEG/WebP compression quality % / image file-size ratio that best suits your WordPress site needs. Smaller compression quality results in smaller image file-size and faster page load.


* **Warp iMagick | Sharpen Image: enables you to sharpen blurry WordPress subsize/thumbnail images.**
Original JPEG/PNG images are copied into compressed & optimized JPEG/PNG/WebP media types, of original size and subsizes/thumbnails. WebP images are served by Apache/Nginx configuration. Original & optimized JPEG/PNG images are served only for older, NO-WebP compatible, browsers.


* **Warp iMagick | Resize on Uploads: enables you to set limit for Big image geometry, reduce file size and save disk space.**
Original big JPEG/PNG images are (optionally) resized to your image maximum geometry (configurable) limit. Replacing original upload image and saving your disk-space, when enabled. When site target is mobile or tablet, you may want to limit uploads to maximal/largest image size (780-1024) fot the target device.


* **Warp iMagick | Image Optimizer: gives you full control over image optimization, compression quality and file size.**
Images optimization is customizable along with other advanced image optimization & compression settings. See all options used to optimize images in the plugin **Settings** page.


* **After activation, settings you may want to change are JPEG Compression Quality and Sharpen blurry WordPress subsize/thumbnail images.**
Set your image compression quality from 30% to 95%. As recommended in video below, plugin default JPEG Compression Quality of 60% is good speed/file-size/quality compromise for most of the web sites. If not, choose your own best compression settings. Sharpen JPEG Image from 0.5 to 1.5 Sigma. For the rest of the settings, use defaults [(some recommended by Google)](https://developers.google.com/speed/docs/insights/OptimizeImages#optimizations-for-gif,-png,-and-jpeg-images) or feel free to experiment and have fun with. 😇
[youtube https://www.youtube.com/watch?v=6ckTVIpuCu8]


* **You don't have to resize or optimize images before uploading.**
Warp iMagick image optimizer plugin will do it for you and preserve your uploaded [or automatically resized] high quality original image as source for multiple re-compressions. Never ever over-compress or loose thumbnail quality.


* **Fastest and most comprehensive serving of converted WebP images ...**
... is achieved by your server software (not by slow PHP). Transparent serving WebP clones instead original JPEG/PNG uploads (for WebP enabled browsers) covers all image links: in HTML code, in CSS code (styles) and in JavaScript code. You do not have to rewrite HTML <img>, nor your (background image) CSS styles, nor dynamically invoked images via Java Script. Serving WebP images with [Apache Server](https://httpd.apache.org/) requires simple manual (DIY) modification of .htaccess file. Use [**WP Htaccess Editor Plugin**](https://wordpress.org/plugins/wp-htaccess-editor/) to safely modify (copy&paste) your Apache .htaccess configuration file. You will find instructions on how to configure server and deliver WebP images on the settings page. Click on **HELP** at the top right of the plugin **SETTINGS PAGE**. If you use [WordOps - Free WordPress sites Nginx stack & control CLI for VPS or Dedicated servers)](https://wordops.net/), your [Nginx Server](https://nginx.org/en/) is already [configured](https://github.com/WordOps/WordOps) to serve WebP clones instead original JPEG/PNG uploads & subsizes/thumbnails, but restricted to "wp-content/uploads/" directory only.


* **Improve your site speed, performance and [PageSpeed Insights](https://pagespeed.web.dev/) or [Lighthouse](https://developers.google.com/web/tools/lighthouse/) score and SEO ranking ...**
... by serving better compressed and next generation image formats (JPEG/PNG/WebP). When generating WebP images is enabled, every JPEG and PNG media image or thumbnail found during image optimization process is converted to WebP and saved as copy with .webp extension added. Default PNG&JPEG to WebP image compression quality is 75% (configurable). In addition, JPEG to WebP compression quality can be set to use & follow compression quality set for JPEG images.


* **Automatic image optimization to subsizes/thumbnails and conversion to WebP images:**
Compress image files more or keep higher image quality than WordPress image optimization does for subsizes/thumbnails. Compress images automatically on upload or on "regenerate thumbnails". Uploaded image is always preserved in original state. Image Compression will always start from original image quality. You can't "overoptimize" or "overcompress". Reoptimize existing images with ["Regenerate Thumbnails plugin"](https://wordpress.org/plugins/regenerate-thumbnails/) single or batch process, or with ["WP CLI media regenerate" command](https://developer.wordpress.org/cli/commands/media/regenerate/).


* **Generate & Convert WebP images (optional)**
JPEG image optimization is speed & pagespeed/SEO score efficient even without WebP. Because plugin has advanced JPEG image compression capabilities, not available in WordPress original image optimization.


* **Image optimization is compatible with ["WP CLI media regenerate" command](https://developer.wordpress.org/cli/commands/media/regenerate/) and/or with ["Regenerate Thumbnails" plugin](https://wordpress.org/plugins/regenerate-thumbnails/).**
**Important Note:** Since WordPress 5.3, BIG JPEG images reduced to 2560x2560 (by "Big Image Size Threshold" feature) and then manually edited by user, on regenerate, will be restored back to original (unedited) version. User edited modifications will be lost, unless this plugin is used. See [GitHub issue](https://github.com/Automattic/regenerate-thumbnails/issues/102). Same bug/issue applies both to ["WP CLI media regenerate" command](https://developer.wordpress.org/cli/commands/media/regenerate/) and ["Regenerate Thumbnails plugin"](https://wordpress.org/plugins/regenerate-thumbnails/). To fix that bug/issue, install or upgrade Warp iMagick plugin to version 1.6.2 or above.


* **Tested against WordPress Coding Standards.**
Tested with [PHP_CodeSniffer (phpcs)](https://github.com/squizlabs/PHP_CodeSniffer) using [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/) [GitHub](https://github.com/WordPress/WordPress-Coding-Standards) rules and [PHPCompatibility ](https://github.com/PHPCompatibility/PHPCompatibility) rules.


* **Multisite support**
Designed to work on WP multisite. No known reason to fail on WP multisite, but not extensively tested yet! Please, let us know if you find any incompatibility with WP multisite.


* **Clean uninstall**
By default, no plugin settings are left in your database after uninstall. Feel free to install and activate to make a trial. However, you can choose to preserve plugin options after plugin delete/uninstall. For detailed uninstall info related to added WebP images, see the **FAQ** section below.


* **Privacy**
Warp iMagick Image Compressor plugin does not collect nor send any identifiable data from your server, without your consent. WordPress admin cookie is used to store admin-settings page-state (current tab & expanded sections). No external (Google) fonts are used.


* **Known conflicts**
Due to use of [bfi_thumb library](https://github.com/bfintal/bfi_thumb) which completely takes over wordpress WP_Image_Editor classes, in [Ajax Search Lite](https://wordpress.org/plugins/ajax-search-lite/) plugin and in [Circles Gallery](https://wordpress.org/plugins/circles-gallery/) plugin, Warp iMagick plugin may fail to activate while those plugins are active. Activating those plugins after Warp iMagick is activated, may cause malfunction. Plugin [WP Email Users](https://wordpress.org/plugins/wp-email-users/) (tested with version 1.7.6.) causes fatal submit error when saving Warp-iMagick settings.
Recognized known conflict will automatically issue administrator warning notice.


= Features =

* **Option: JPEG Compression Quality. [Lossy Compression]**
Set [JPEG compression quality](https://developers.google.com/speed/docs/insights/OptimizeImages#optimizations-for-gif,-png,-and-jpeg-images) from 30% to 85%. Current WordPress default is 82%.


* **Option: JPEG Compression Type. [Lossy Compression]**
Select WordPress Default or Imagick Default.


* **Option: JPEG Colorspace Transform. [Lossless Color Compression]**
Select WordPress Default or [sRGB*](https://developers.google.com/speed/docs/insights/OptimizeImages#optimizations-for-gif,-png,-and-jpeg-images). Default sRGB colorspace is a [**World Wide Web Standard**](https://www.w3.org/Graphics/Color/sRGB.html).


* **Option: JPEG Color Sampling Factors. [Lossy Color Compression]**
Select WordPress Default, 4:1:0, 4:1:1, [4:2:0*](https://developers.google.com/speed/docs/insights/OptimizeImages#optimizations-for-gif,-png,-and-jpeg-images), 4:2:1, 4:2:2, 4:4:0, 4:4:1, 4:4:4.


* **Option: JPEG Interlace Scheme. [Lossless Compression]**
Select WordPress Default, Imagick Default, No Interlace, Progressive or **Auto Select**: Compare "No Interlace" with "Progressive" file size and save smaller file to the disk.


* **Option: PNG Color Reduction. [Lossy Color Compression]**
Enable to quantize PNG Colors into range between 16 and 1024 colors.


* **Option: PNG Color Dithering. [Lossy Compression]**
Enable Color Dithering to improve color transition quality (except transparent & less-than-257-colors).


* **Option: PNG Max Number of Colors. [Lossy Color Compression]**
Select maximal number of PNG colors. Images reduced to less than 257 colors are automatically converted to PNG Color Palette.


* **Automatic: PNG Interlace Compression. [Lossless Compression]**
WordPress Default and Imagick Default compression strategies are compared and smaller file size is written to disk.


* **Option: Strip Metadata. [Lossless Compression]**
Select WordPress Default settings, force WP Default Off, force WP Default On, Strip All Metadata* (on JPEG: only if colorspace is sRGB, else uses WP strip metadata).


* **Option: WebP Conversion. [Efficient Lossy Conversion]**
Enable to automatically generate optimized WebP versions of JPEG and PNG images & subsizes/thumbnails. See the Settings page **Help** on how about to configure server.


* **Option: WebP Compression Quality.[Efficient Lossy Compression]**
Select PNG & JPEG to WebP image compression quality (default 75%). In addition, JPEG to WebP compression quality can be set to use & follow compression quality set for JPEG images.


* **Option: Disable big image size threshold (JPEG only - Since WP 5.3)**
Disable WordPress big image size threshold which proportionally resizes large upload/original images to maximum frame of 2560x2560 pixels.


* **Option: Configure big image size threshold in pixels (JPEG only - Since WP 5.3)**
Configure WordPress big image size threshold frame WxH in pixels. Original is preserved, not saving disk space.


* **Option: Disable (maybe) Rotate Image (JPEG only - Since WP 5.3)**
Disable WordPress automatic image rotate when image orientation is set and not equal to 1.


* **Option: Disable generate compressed version of original image**
Disable generate (and attach) compressed version of upload/original image size.


* **Option: Enable & Reduce Upload Image Maximum Width & Height**
Enable proportional downsizing of large upload/original images over maximum width in pixels. Original upload image is resized and replaced, saving some disk space.


* **Option: Configure Upload Image Maximum Width in Pixels**
Configure maximum width for proportional downsizing of large original/upload images, in pixels. Original upload image is resized and replaced, saving some disk space.


= Featured =


[youtube https://www.youtube.com/watch?v=SnFOEhi0ym0]



[youtube https://www.youtube.com/watch?v=F1kYBnY6mwg]



[youtube https://www.youtube.com/watch?v=-7k3H2GxE5E]



[youtube https://www.youtube.com/watch?v=AQqFZ5t8uNc]


== Upgrade Notice ==

This Version require PHP 7.4+.

== Screenshots ==

1. **JPEG Settings**
2. **PNG Settings**
3. **WebP Settings**
4. **Other Settings**
5. **Regenerate Thumbnails**
6. **WebP Mobile Page Score**

== Frequently Asked Questions ==

= Where is the "GO" button? =

There is no "GO" button. When activated, plugin automatically applies compression on all new JPEG/PNG media uploads.

= How do I “bulk compress”? =

To bulk (re)compress existing media images use [“Regenerate Thumbnails Plugin”](https://wordpress.org/plugins/regenerate-thumbnails/) or [“WP CLI media regenerate” command](https://developer.wordpress.org/cli/commands/media/regenerate/).

= Do I need to keep ‘Regenerate Thumbnails’ plugin? =

You don’t have to keep [“Regenerate Thumbnails Plugin”](https://wordpress.org/plugins/regenerate-thumbnails/) plugin installed.

= Does this plugin support WooCommerce images? =

All image subsizes are supported, including any theme and/or WooCommerce specific image subsizes.

= How to serve converted WebP images? =

Open plugin settings and **CLICK** on **HELP** tab at the top right of the plugin **SETTINGS PAGE** and you will find instructions on how to configure server to redirect \*.jpg/\*.png to \*.jpg.webp/\*.png.webp and to deliver converted WebP images, if such image is generated by this plugin (file exists) and if visitor's browser suports WebP image format.

= How to modify Apache .htaccess configuration file? =

Use [**WP Htaccess Editor Plugin**](https://wordpress.org/plugins/wp-htaccess-editor/) to safely modify your Apache .htaccess configuration file.

= Which PHP extensions are required by this plugin? =

1. PHP-Imagick to compress JPEG/PNG files (required).
2. PHP-GD to convert JPEG/PNG to WebP files (optional, but usually installed).

In order to modify/resize/crop photos or images, Wordpress requires at least PHP-GD. When both extensions are installed, WordPress prefers PHP-Imagick over PHP-GD.

= Do I have both required PHP extensions installed? =

1. Administrator: Menu -> Tools -> Site Health -> Info -> Expand "Media Handling" and check if "ImageMagick version string" and "GD version" have values.
2. WordPress Editor class must be WP_Image_Editor_Imagick (or Warp_Image_Editor_Imagick) but **NOT** WP_Image_Editor_GD.
3. PHP-Imagick extension must be linked with ImageMagick library version **6.3.2** or newer.
4. PHP-GD extension version must be at least 2.0.0 to be accepted by WordPress Image Editor.

= Does my web hosting service provide PHP-Imagick and PHP-GD extensions? =

There is no excuse for hosting service providers not to allow or provide essential, free and open source PHP extensions. Both extensions are used by WordPress PHP code generating image subsizes and thumbnails!

See: [WordPress recommended PHP extensions](https://make.wordpress.org/hosting/handbook/server-environment/#php-extensions)

1. A2 Hosting : [Yes, according to plugin 'A2 Optimized WP' description, Warp iMagick plugin is embraced and web server configuration for it is provided](https://wordpress.org/plugins/a2-optimized-wp/).
2. EasyWP     : [Yes, documentation page provided](https://www.namecheap.com/support/knowledgebase/article.aspx/9697/2219/php-modules-and-extensions-on-shared-hosting-servers).
3. Hostpapa   : Yes, in cPanel. But may be not available in your hosting plan.
4. WPEngine   : Supposedly yes, but no web kb/help or documentation page found at the time of writting of this section.
5. Ask your hosting service provider support.

= How to install missing PHP-Imagick and PHP-GD extensions? =

1. [cPanel based hosting](https://documentation.cpanel.net/display/68Docs/PHP+Extensions+and+Applications+Package#PHPExtensionsandApplicationsPackage-PHPExtensionsandApplicationsPackageInstaller): Configuration may be not available in your hosting plan.
2. [Debian/Ubuntu using ssh access](https://deb.sury.org/): Via root user console, or prepend sudo to "apt install php-imagick php-gd".
3. [CentOS/RHEL/Fedora using ssh access](https://blog.remirepo.net/post/2019/12/03/Install-PHP-7.4-on-CentOS-RHEL-or-Fedora): "yum install php-imagick php-gd".
4. Ask your host-service provider support.

= Why WebP files have two extensions? =

To prevent overwriting duplicate "WebP" clones. With single extension, when you upload "image.png" and then "image.jpg", second "image.webp" would overwrite previous clone.

= Why is WebP setting (checkbox) disabled? =

Because your server has no PHP-GD graphic editing extension or your PHP-GD extension has no WebP support.

= What happens with images when plugin is deactivated or deleted? =

1. Existing images remain optimized. If you run ["Regenerate Thumbnails"](https://wordpress.org/plugins/regenerate-thumbnails/) batch process, after plugin is deactivated or deleted, batch process will restore original file-size and quality of WordPress thumbnails.
2. If you have WebP images, they won't be deleted. You can delete all WebP images while plugin is active. To delete WebP images, disable WebP option and then batch-run ["Regenerate Thumbnails"](https://wordpress.org/plugins/regenerate-thumbnails/) for all media images.

= Why is plugin disabled and/or fails to activate? =

Because missing of any plugin requirements listed below:
1. Server has no required/compatible PHP version installed.
2. Server has no required/compatible WP version installed.
3. Server has no required PHP-Imagick extension installed.
4. Site may not comply with other plugin requirements.

== Changelog ==

= 1.12.1 (Current Version) =
* Accordion & Help >= WP-6.7.

= 1.12 =
* PUC upgrade to Version 5.3.
* Use namespace in index.php.
* Plugin Meta Class refactored.
* Copyright Year updated to 2025.
* Remove 'Please update.' message.
* Move textdomain to wp_init hook.
* New Reflection Syntax (7.4-8.+).
* Fix Broken Accordion after wp 6.6
* Tested up WordPress version 6.7.1
* Tested WordPress Coding Standards.
* Tested PHP 8.2.26 Coding Standards.

= 1.11 =
* Tested up to WP 6.4
* Patch: PUC 5.1 PHP 8.2 Warnings.
* Wrap classes with uniform if class exist code.

= 1.10.4.1 =
* Fix: Delay Plugin Reactivation because test images
  are not created while plugin is in WP upgrader sandbox.

= 1.10.4 =
* Mute most of debug messages.
* Visual WebP redirection test.
* Prevent PHP 8.0+ warnings/errors.
* Display plugin version in Plugin Title.
* Preview Thumbnails: add link to image URL.
* Upgrade PUC 4.11 (PHP7) to PUC 5.11 (PHP8).
* Add "attach" metadata (Regenerate Thumbnails).
* Removed warning when auto-update is not enabled.
* Info notice when new version is ready to upgrade.
  (Only when plugin auto-update is disabled.)

= 1.10.3 (including 1.10.2-beta) =
* Update Checker in sync-with Update server.
* Re-generate is faster (skip existing clones).
* Default JPEG quality increased from 60% to 75%.
* New WebP-JPEG compression values: -5%, -10%, -15%
* Support for WP-CLI version 2.7.1 "media regenerate".
* Resolved conflict with Performance Lab plugin v2.2.0.
* Support future Dominant Color (Now in Performance Lab).
* Preview Thumbnails is now in reverse order, large first.
* Preview Thumbnails: inline WebP in addition to JPEG/PNG.
  Because browser may keep previous version in site cache.
