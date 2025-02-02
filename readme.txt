=== Require Featured Image Extended ===
Contributors: delwinv, pressupinc, davidbhayes
Plugin URI: https://ampullate.com/wordpress-plugins/require-featured-image-extended/
Tags: featured image, images, edit, post, admin, require featured image, image, media, thumbnail, thumbnails, post thumbnail, photo, pictures
Requires at least: 5.8
Tested up to: 5.8
Stable tag: 1.0.1
License: MIT
License URI: http://opensource.org/licenses/MIT

Requires content you specify to have a featured image with an optional minimum size, that can be set per-post-type, before they can be published.

== Description ==

= Simplify Your Editing Life =

Requires your various post types — as specified in a simple options page — to have a featured image set before they can be published. If a lack of featured images causes your layout to break, or just look less-than-optimal, this is the plugin for you.

Rather than forcing you to manually enforce your editorial standards of including a featured image in every post, if your contributors fail to add a featured image to a post before publishing it they'll simply find it impossible to publish.

= Setting up the Plugin =

By default it works on the "Post" content type only, but you can specify other content types, or turn it off for Posts in the new options page in your left sidebar: Settings > Req Featured Image. Simply check and uncheck the appropriate types, set a minimum image size if you desire, hit save and you're all set. Happy publishing!

== Installation ==

Activate the plugin. No other steps are necessary to require featured images on Posts only.

If you want to require featured images on a different content type, or allow Posts to be published without them simply go to the settings page in your left sidebar: Settings > Req Featured Image. Check and uncheck the appropriate types, set a minimum image size if you desire, hit "Save", and you're all set. Happy publishing!

== Frequently Asked Questions ==

= What post (content) types does this plugin work for? =

Every "custom post type" — or variety of content — that supports thumbnails is supported.

= How does it prevent people from publishing a post without featured images? =

There are two methods: one is some strong Javascript on the edit screen that makes it very clear to people working there that they need to add a featured images and makes it impossible for them to press the Publish button unless they've added on.

If that failed for any reason, it also hooks into the publish method and stops publishing when no featured image is present. This should prevent publishing even if an author has Javascript off, or if publishing is attempted through more obscure methods.

= I'm not seeing one of my content types on the settings page. Why? =

To simplify the settings page, and avoid confusion, only content types that support Featured Images will appear on the page. It wouldn't make sense for us to try to enforce that a content type that can't have a featured image set can't be published without it. If you want to require that a content type has a featured image but it doesn't currently support it, get in touch with your developer, fiddle with the `register_post_type()` call creating the content type yourself, or get in touch with us at [Press Up](http://pressupinc.com/), we love to help!

= Why would I use this plugin? =

Because you want it to be *required* that your posts have featured images before they be published. If you'd like that your posts have featured images, but it's not a show-stopper for your editorial standards, this plugin may not be for you.

= Are there any options? =

Yes: for different "custom post types", and for their thumbnail dimensions, which can be all set to the same or individually set. In your left sidebar under Settings, you should see "Req Featured Image". This is where the options are set. You can choose which Post Types you want check as well as setting minimum size(s) for the featured image. Happy publishing!

= Support for other languages? =

The plugin is fully translatable. For now, contact Delwin Vriend at the github address and the pot file can be provided to you to translate into another language.

== Screenshots ==

1. The warning that you see when editing a post that doesn't have a featured image set. The "Publish" button is also disabled.

2. The settings page, which lets you specify which post types the plugin should operate on and other options.

== CHANGELOG ==

= 1.0.1 (2021.08.31) =
* Add option for all posts thumbnails to have the same minimum size (the default), restoring the old setting from the Require Featured Image plugin, if it was installed.

= 1.0 (2021.08.30) =
* First relase
* Branched from Require Featured Image v1.5.0 by <a href="https://profiles.wordpress.org/pressupinc/">pressupinc</a>
* Added capability to set minimum thumbnail dimesions on a per-post-type basis
* Clarified minimum dimensions in messaging on admin pages
* Corrected javascript image size determination to prevent bad detection
* Add option to enforce on existing posts - even those older than 2 weeks old
