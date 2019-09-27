# DocsPress - Online Documentation #

* Contributors: nko
* Tags: documentation, document, help, ajax, export
* Requires at least: 4.6.0
* Tested up to: 5.2
* Requires PHP: 5.4
* Stable tag: @@plugin_version
* License: GPLv2 or later
* License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create, host and manage multiple products documentations.

## Description ##

Online documentation for your multiple products. Create, host and manage documentations in your WordPress site.

### Links ###

* [Live Demo](https://nkdev.info/docs)
* [GitHub](https://github.com/nk-o/docspress)

## Features ##

* Multiple products support
* AJAX search
* AJAX loading for documentation pages
* Documentation categories
* Users feedback buttons
* Automatic anchor links on headings on docs content
* Comments section for articles
* Export documentation to static HTML
* Templates for theme developers
* Custom ordering

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

= 2.1.0 =

* added Suggestion form option (show after user added feedback)
* added categories in admin docs list
* added possibility to change helpfullness in post metabox
* fixed anchors initialization after ajax load
* prevent cloning helpfulness meta

= 2.0.1 =

* fixed feedback click action js error
* fixed search result in the end of the excerpt text showed "1"

= 2.0.0 =

* updated overall styles
* added ajax search field in sidebar
* added 3rd-level docs support
* added categories support
* added option to hide feedback count
* added support for anchor links in content headings
* added helper styles for default wp themes
* added option to show all parent documentations in sidebar (if you don't need multiple documentations)
* added label in breadcrumbs archive page from selected archive page title
* added option to disable comments on single doc
* added scroll to top when ajax loading doc
* changed archive docs titles to h2
* rename permalink 'docs' to the selected archive page slug
* fixed [] array usage
* fixed archive articles number -1
* a lot of minor changed and fixes

= 1.0.0 =

* Initial Release
