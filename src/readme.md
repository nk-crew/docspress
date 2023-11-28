# DocsPress - Online Documentation

* Contributors: nko
* Tags: documentation, document, help, knowledge base, export
* Requires at least: 6.2.0
* Tested up to: 6.4
* Requires PHP: 7.2
* Stable tag: @@plugin_version
* License: GPLv2 or later
* License URI: <http://www.gnu.org/licenses/gpl-2.0.html>

Create, host and manage multiple products documentations.

## Description

Online documentation for your multiple products. Create, host and manage documentations in your WordPress site.

### Links

* [Live Demo](https://nkdev.info/docs/)
* [GitHub](https://github.com/nk-crew/docspress/)

## Features

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

## Installation

### Automatic installation

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install of DocsPress, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type `DocsPress` and click Search Plugins. Once you’ve found our plugin you can view details about it such as the point release, rating and description. Most importantly of course, you can install it by simply clicking “Install Now”.

### Manual installation

The manual installation method involves downloading our DocsPress plugin and uploading it to your webserver via your favourite FTP application. The WordPress codex contains [instructions on how to do this here](https://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

## Frequently Asked Questions

### Initialize JS after AJAX page loaded

If you need to initialize some JS after ajax loaded, you may use **DocsPress > Settings > Single Doc > AJAX custom JS** section or use predefined custom event `docspress_ajax_loaded`:

    jQuery( document ).on( 'docspress_ajax_loaded', function() {
        // your code here.
    } );

## Screenshots

1. Documentations Archive
2. Documentation
3. Documentations Admin
4. Documentations Admin Classic UI

## Changelog

= 2.4.2 - 28 Nov, 2023 =

* changed tested WP version to 6.4
* changed minimal WP version to 6.2
* fixed `docspress_ajax_loaded` usage error because of jQuery used

= 2.4.0 - 28 Nov, 2023 =

* added blocks version to v3 - allows to enable blocks iframe editor
* added output attributes with useBlockProps and get_block_wrapper_attributes in blocks
* hide FSE blocks from standard editor
* changed block templates align to wide
* changed Helpfulness old metabox to use Gutenberg API
* remove possibility to add FSE blocks multiple times
* removed jQuery dependency on frontend

= 2.3.1 - 6 May, 2023 =

* added support for DocSearch
* added support for navigation category titles when enabled "Display Parent Links" setting

= 2.3.0 - 1 Jul, 2022 =

* added support for FSE themes
* added CSS variables support
* improved styles for Twenty themes
* simplified styles
* changed required PHP version to 7.2
* removed IE support

= 2.2.7 - 24 Dec, 2021 =

* improved feedback email template
* improved feedback mailing function (better reply to and subject lines)

= 2.2.6 - 24 Dec, 2021 =

* fixed docs suggestion email Reply-To field (should refer to feedback sender email)

= 2.2.5 - 20 Aug, 2021 =

* fixed private docs displaying for admins

= 2.2.4 - 16 Jul, 2021 =

* tested up to WP 5.8

= 2.2.3 - 4 Mar, 2021 =

* removed usage of deprecated jQuery ready event
* tested up to WordPress 5.7
* changed GitHub repo url

= 2.2.2 - 19 Oct, 2020 =

* improved admin UI
* improved thumbnail size for admin documentation
* updated vendor scripts
* fixed bug when trying to delete documentation, but deleted another documentation

= 2.2.1 - 10 Aug, 2020 =

* added RTL support
* fixed email template long words break

= 2.2.0 - 23 Mar, 2020 =

* improved feedback suggestion email template
* enqueue assets on DocsPress pages only
* fixed breadcrumbs structured data error

= 2.1.2 =

* changed position of anchor link to right (fixes Ghost Kit numbered headings conflict)

= 2.1.1 =

* fixed DocsPress archive page title

= 2.1.0 =

* added Suggestion form option (show after user added feedback)
* added categories in admin docs list
* added possibility to change helpfulness in post metabox
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
