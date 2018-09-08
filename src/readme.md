# DocsPress - Online Documentation #

* Contributors: nko
* Tags: documentation, document, help, ajax, export
* Requires at least: 4.6.0
* Tested up to: 4.9
* Requires PHP: 5.4
* Stable tag: @@plugin_version
* License: GPLv2 or later
* License URI: http://www.gnu.org/licenses/gpl-2.0.html

Online Products Documentation.

## Description ##

Online documentation manager for your multiple products. Create and manage your documentations in WordPress admin panel.

### Links ###

* [Live Demo](https://demo.nkdev.info/#docspress)

## Features ##

* Multiple products support
* AJAX for documentation pages
* Users feedback buttons
* Comments section for articles
* Export documentation to static HTML
* Templates for theme developers

Our plugin originally based on `weDocs` plugin.

## Installation ##

### Automatic installation ###

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install of DocsPress, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type `DocsPress` and click Search Plugins. Once you’ve found our plugin you can view details about it such as the point release, rating and description. Most importantly of course, you can install it by simply clicking “Install Now”.

### Manual installation ###

The manual installation method involves downloading our DocsPress plugin and uploading it to your webserver via your favourite FTP application. The WordPress codex contains [instructions on how to do this here](https://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

## Frequently Asked Questions ##

### Initialize JS after AJAX page loaded ####

If you need to initialize some JS after ajax loaded, you may use **DocsPress > Settings > Single Doc > AJAX custom JS** section or use predefined custom event `docspress_ajax_loaded`:

    jQuery( document ).on( 'docspress_ajax_loaded', function() {
        // your code here.
    } );

## Screenshots ##

1. Documentations Archive
2. Documentation
3. Documentations Admin
4. Documentations Admin Classic UI

## Changelog ##

= 1.0.0 =

* Initial Release
