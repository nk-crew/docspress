<?php

/**
 * Export to HTML Class
 */
class DocsPress_Export {

    public $img_extensions = array('gif', 'jpg', 'jpeg', 'png', 'tiff', 'tif', 'bmp', 'svg');
    public $clean_html_regexp =
'/<link.*(rel=(\"|\')profile(\"|\'))[^>]*?>/i
/<link.*(rel=(\"|\')pingback(\"|\'))[^>]*?>/i
/<link.*(rel=(\"|\')dns-prefetch(\"|\'))[^>]*?>/i
/<link.*(rel=(\"|\')alternate(\"|\'))[^>]*?>/i
/<link.*(rel=(\"|\')https:\/\/api.w.org\/(\"|\'))[^>]*?>/i
/<link.*(rel=(\"|\')EditURI(\"|\'))[^>]*?>/i
/<link.*(rel=(\"|\')wlwmanifest(\"|\'))[^>]*?>/i
/<link.*(rel=(\"|\')next(\"|\'))[^>]*?>/i
/<link.*(rel=(\"|\')prev(\"|\'))[^>]*?>/i
/<link.*(rel=(\"|\')canonical(\"|\'))[^>]*?>/i
/<link.*(rel=(\"|\')shortlink(\"|\'))[^>]*?>/i
/<meta.*(name=(\"|\')generator(\"|\'))[^>]*?>/i';

    public $custom_css =
".docspress-single-feedback {
    display: none;
}
body {
    background-color: #fff;
}";
    public $custom_js = "";
    public $max_delta = 1;

    function __construct() {
        if (function_exists('domain_mapping_siteurl')) {
            $this->wp_site_url = domain_mapping_siteurl(get_current_blog_id());
            $this->wp_content_url = str_replace(get_original_url($this->wp_site_url), $this->wp_site_url, content_url());
        } else {
            $this->wp_site_url = site_url();
            $this->wp_content_url = content_url();
        }
        $this->wp_content_name = '/' . wp_basename(WP_CONTENT_DIR);
        $this->wp_root_url = str_replace($this->wp_content_name, '', $this->wp_content_url);
        $this->wp_root_dir = str_replace($this->wp_content_name, '', WP_CONTENT_DIR);

        $export_folder = 'docspress-export';
        $wp_upload_dir = wp_upload_dir();
        $this->export_path = $wp_upload_dir['basedir'] . '/' .  $export_folder;
        $this->export_url = $wp_upload_dir['baseurl'] . '/' .  $export_folder;
    }

    /**
     * Cache urls
     */
    protected $saved_urls = array();
    public function saveUrl ($old_url, $new_url) {
        $this->saved_urls[$old_url] = $new_url;
    }
    public function getUrl ($url) {
        return isset($this->saved_urls[$url]) ? $this->saved_urls[$url] : false;
    }
    public function replaceUrls ($content) {
        foreach ($this->saved_urls as $oldUrl => $newUrl) {
            if (preg_match('/\.html$/', $newUrl)) {
                $content = str_replace('"' . $oldUrl . '"', '"' . $newUrl . '"', $content);
                $content = str_replace('\'' . $oldUrl . '\'', '\'' . $newUrl . '\'', $content);
            } else {
                $content = str_replace($oldUrl, $newUrl, $content);
            }
        }
        return $content;
    }

    public function run ($doc_id) {
        // Turn off PHP output compression
        $previous = error_reporting( error_reporting() ^ E_WARNING );
        ini_set( 'output_buffering', 'off' );
        ini_set( 'zlib.output_compression', false );
        error_reporting( $previous );

        if ( $GLOBALS['is_nginx'] ) {
            // Setting this header instructs Nginx to disable fastcgi_buffering
            // and disable gzip for this request.
            header( 'X-Accel-Buffering: no' );
            header( 'Content-Encoding: none' );
        }

        // Start the event stream.
        header( 'Content-Type: text/event-stream' );

        // 2KB padding for IE
        echo ':' . str_repeat( ' ', 2048 ) . "\n\n";

        // Time to run the import!
        set_time_limit(0);

        // Ensure we're not buffered.
        wp_ob_end_flush_all();
        flush();

        // get all pages
        $docs = get_pages( array(
            'post_type'      => 'docs',
            'child_of'       => $doc_id,
            'post_status'    => array( 'publish', 'draft' ),
            'posts_per_page' => '-1',
            'sort_column'    => 'menu_order',
            'order'          => 'ASC'
        ) );
        $docs = $this->build_tree( $docs, $doc_id );

        // get main doc
        array_unshift($docs, array(
            'id'     => $doc_id,
            'file_name' => 'index.html',
            'permalink' => get_permalink($doc_id)
        ));

        // save urls
        foreach ($docs as $doc) {
            $this->saveUrl($doc['permalink'], $doc['file_name']);
        }
        $this->max_delta += count($docs);

        // export folder path
        $export_folder_name = get_post_field('post_name', $doc_id);
        $export_path = trailingslashit($this->export_path . '/' . $export_folder_name);

        // remove export folder
        $this->rimrafDir($export_path);

        // run export
        foreach ($docs as $doc) {
            $this->emit_sse_message($doc['file_name']);
            $this->run_single($doc['permalink'], $doc['file_name'], $export_path);
        }

        // zip
        $this->emit_sse_message('ZIP Documentation');
        $result = $this->zip($doc_id);

        $this->emit_sse_message($result, 'complete');
    }

    public function run_single ($permalink, $file_name, $export_path) {
        // request page
        $content = wp_remote_get($permalink);

        if (is_wp_error($content)) {
            return 'Can\'t get post content.';
        }

        // get page html content
        $content = wp_remote_retrieve_body($content);

        // check if html
        if ( ((stripos($content,"<html") === false) && (stripos($content,"<!DOCTYPE html") === false)) || preg_match('/<html[^>]*(?:amp|⚡)/',$content) === 1 || stripos($content,"<xsl:stylesheet") !== false ) {
            return 'Failed page request.';
        }

        // parse and save all js files
        if(preg_match_all('#<script.*</script>#Usmi',$content,$matches)) {
            foreach ( $matches[0] as $tag ) {
                if ( preg_match( '#<script[^>]*src=("|\')([^>]*)("|\')#Usmi', $tag, $source ) ) {
                    $url = $this->normalizeUrl($source[2]);
                    $path = $this->getPath( $url );

                    // copy file
                    if ($path && !$this->getUrl($url)) {
                        $relative_path = $this->getAssetsRelativePath($path);
                        $copy_to = $export_path . '/' . $relative_path;

                        $this->copy_file($path, $copy_to);

                        $this->saveUrl($url, '.' . $relative_path);
                    }
                }
            }
        }

        // parse and save all css files
        if(preg_match_all('#(<link[^>]*stylesheet[^>]*>)#Usmi',$content,$matches)) {
            foreach($matches[0] as $tag) {
                if ( preg_match( '#<link.*href=("|\')(.*)("|\')#Usmi', $tag, $source ) ) {
                    $url = $this->normalizeUrl($source[2]);
                    $path = $this->getPath( $url );

                    // copy file
                    if ($path && !$this->getUrl($url)) {
                        $relative_path = $this->getAssetsRelativePath($path);
                        $copy_to = $export_path . '/' . $relative_path;

                        if ($this->copy_file($path, $copy_to)) {
                            // find imported images, fonts and other files in css content
                            $this->parseCSS($url, $export_path);
                        }

                        $this->saveUrl($url, '.' . $relative_path);
                    }
                }
            }
        }

        // find all links in html
        preg_match_all('#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#', $content, $matches);
        if (is_array($matches)) {
            foreach ( $matches[0] as $url ) {
                $url = $this->normalizeUrl($url);

                // save images
                $urlExt = pathinfo($url, PATHINFO_EXTENSION);
                if (in_array($urlExt, $this->img_extensions) && !$this->getUrl($url)) {
                    $path = $this->getPath( $url );

                    // copy file
                    if ($path) {
                        $relative_path = $this->getAssetsRelativePath($path);
                        $copy_to = $export_path . '/' . $relative_path;

                        $this->copy_file($path, $copy_to);

                        $this->saveUrl($url, '.' . $relative_path);
                    }
                }
            }
        }

        // replace all document links
        $content = $this->replaceUrls($content);

        // add custom js and css
        $custom_js = docspress()->get_option( 'custom_js', 'docspress_export', $this->custom_js );
        if ($custom_js) {
            $content = str_replace("</head>", "\n<script>" . $custom_js . "</script>\n\n</head>", $content);
        }
        $custom_css = docspress()->get_option( 'custom_css', 'docspress_export', $this->custom_css );
        if ($custom_css) {
            if ( false !== stripos( $custom_css, '</style>' ) ) {
                $custom_css = trim( preg_replace( '#<style[^>]*>(.*)</style>#is', '$1', $custom_css ) );
            }
            $content = str_replace("</head>", "<style type=\"text/css\">" . $custom_css . "</style>\n\n</head>", $content);
        }

        $content = $this->cleanHTML($content);

        // save html file
        $html_path = $export_path . '/' . $file_name;
        if (!file_exists($html_path)) {
            wp_mkdir_p(pathinfo($html_path, PATHINFO_DIRNAME));
            file_put_contents($html_path, $content, 0644);
        }

        unset($content);

        return 'Exported';
    }

    public function build_tree( $docs, $parent = 0, $name_pre = '' ) {
        $result = array();

        if ( ! $docs ) {
            return $result;
        }

        $i = 1;
        foreach ($docs as $key => $doc) {
            if ( $doc->post_parent == $parent ) {
                $name_pre_cur = $name_pre . ($i++) . '.';

                unset( $docs[ $key ] );
                $result[] = array(
                    'id'        => $doc->ID,
                    'file_name' => $name_pre_cur . $doc->post_name . '.html',
                    'permalink' => get_permalink($doc->ID)
                );

                // get childs
                $result = array_merge($result, $this->build_tree( $docs, $doc->ID, $name_pre_cur ));
            }
        }

        return $result;
    }

    public function parseCSS ($css_url, $export_path) {
        $css_path = $this->getPath( $css_url );
        $assets = array();

        if (!$css_path) {
            return;
        }

        // get css content and remove comments
        $css_content_nocomments = preg_replace('#/\*.*\*/#Um', '', file_get_contents($css_path));

        // find imported files in css content
        preg_match_all("/@import[ ]*['\"]{0,}(url\()*['\"]*([^;'\"\)]*)['\"\)]*/ui", $css_content_nocomments, $matches);
        if (is_array($matches)) {
            foreach ( $matches[2] as $import ) {
                $url = $this->normalizeUrl($import);
                $path = $this->getPath( $url, $css_url );
                $assets[] = $path;

                if ( $path && file_exists( $path ) && is_readable( $path ) ) {
                    $assets[] = $path;

                    // copy CSS file
                    $relative_path = $this->getAssetsRelativePath($path);
                    $copy_to = $export_path . '/' . $relative_path;

                    if ($this->copy_file($path, $copy_to)) {
                        // find imported images, fonts and other files in css content
                        $this->parseCSS($url, $export_path);
                    }
                }
            }
        }

        // find images
        preg_match_all('#(background[^;{}]*url\((?!\s?"?\'?\s?data)(.*)\)[^;}]*)(?:;|$|})#Usm', $css_content_nocomments, $matches);
        if (is_array($matches)) {
            foreach ( $matches[2] as $count => $url ) {
                $path = $this->getPath( $url, $css_url );

                if ($path) {
                    $assets[] = $path;
                }
            }
        }

        // find fonts
        $fonturl_regex = $this->getFontsRegex();
        preg_match_all($fonturl_regex, $css_content_nocomments, $matches);
        if (is_array($matches)) {
            foreach($matches[0] as $count => $url) {
                $path = $this->getPath($url, $css_url);

                if ($path) {
                    $assets[] = $path;
                }
            }
        }

        // copy all assets to destination folder
        $assets = array_unique($assets);
        foreach($assets as $asset) {
            $relative_path = $this->getAssetsRelativePath($asset);
            $copy_to = $export_path . '/' . $relative_path;

            $this->copy_file($asset, $copy_to);
        }

        unset($css_content_nocomments);
        unset($assets);
    }

    public function getFontsRegex () {
        // regex to find fonts, externalised to avoid nasty errors for php < 5.3
        // http://stackoverflow.com/questions/21392684/extracting-urls-from-font-face-by-searching-within-font-face-for-replacement
        return <<<'LOD'
~
(?(DEFINE)
    (?<quoted_content>
        (["']) (?>[^"'\\]++ | \\{2} | \\. | (?!\g{-1})["'] )*+ \g{-1}
    )
    (?<comment> /\* .*? \*/ )
    (?<url_skip> (?: https?: | data: ) [^"'\s)}]*+ )
    (?<other_content>
        (?> [^u}/"']++ | \g<quoted_content> | \g<comment>
          | \Bu | u(?!rl\s*+\() | /(?!\*) 
          | \g<url_start> \g<url_skip> ["']?+
        )++
    )
    (?<anchor> \G(?<!^) ["']?+ | @font-face \s*+ { )
    (?<url_start> url\( \s*+ ["']?+ )
)

\g<comment> (*SKIP)(*FAIL) |

\g<anchor> \g<other_content>?+ \g<url_start> \K [./]*+ 

( [^"'\s)}]*+ )    # url
~xs
LOD;
    }

    public function getPath ($url, $base_url = false) {
        $url = $this->normalizeUrl($url);

        $siteHost = parse_url($this->wp_site_url, PHP_URL_HOST);

        // normalize
        if (strpos($url, '//') === 0) {
            if (is_ssl()) {
                $url = "https:" . $url;
            } else {
                $url = "http:" . $url;
            }
        } else if ((strpos($url, '//') === false) && (strpos($url, $siteHost) === false)) {
            if ($this->wp_site_url === $siteHost) {
                $url = $this->wp_site_url . $url;
            } else if ($base_url && strpos($url, 'http') !== 0) {
                if (strpos($url, './') === 0) {
                    $url = preg_replace('^/.\//', '', $url);
                }
                $base_url_folder = pathinfo($base_url, PATHINFO_DIRNAME);
                $url = trailingslashit($base_url_folder) . $url;
            } else {
                $subdir_levels = substr_count(preg_replace("/https?:\/\//", "", $this->wp_site_url), "/");
                $url = $this->wp_site_url . str_repeat("/..", $subdir_levels) . $url;
            }
        }

        // first check; hostname wp site should be hostname of url
        $thisHost = @parse_url($url, PHP_URL_HOST);
        if ($thisHost !== $siteHost) {
            return false;
        }

        // try to remove "wp root url" from url while not minding http<>https
        $tmp_ao_root = preg_replace('/https?:/', '', $this->wp_root_url);
        $tmp_url = preg_replace('/https?:/', '', $url);
        $path = str_replace($tmp_ao_root, '', $tmp_url);

        // final check; if path starts with :// or //, this is not a URL in the WP context and we have to assume we can't aggregate
        if (preg_match('#^:?//#', $path)) {
            /** External script/css (adsense, etc) */
            return false;
        }

        $path = str_replace('//', '/', $this->wp_root_dir . $path);
        return $path;
    }

    public function getAssetsRelativePath ($path) {
        $path = str_replace($this->wp_root_dir, '', $path);
        $path = preg_replace('/^\/wp-content/', '/assets/', $path);
        $path = preg_replace('/^\/wp-includes/', '/assets/', $path);
        $path = str_replace('//', '/', $path);
        return $path;
    }

    public function copy_file ($from, $to) {
        if (!file_exists($to) && file_exists($from)) {
            wp_mkdir_p( pathinfo( $to, PATHINFO_DIRNAME ) );
            @copy( $from, $to );
            return true;
        }
        return false;
    }

    public function normalizeUrl ($url) {
        $url = trim( $url, " \t\n\r\0\x0B\"'" );
        if (strpos($url,'%') !== false) {
            $url = urldecode($url);
        }
        if ( strpos( $url, '?' ) !== false ) {
            $url = strtok( $url, '?' );
        }
        return $url;
    }

    public function cleanHTML ($html) {
        $regexp_array = explode("\n", docspress()->get_option( 'clean_html', 'docspress_export', $this->clean_html_regexp ));
        foreach ($regexp_array as $reg) {
            $reg = trim($reg);
            if ($reg && preg_match($reg, null) !== false) {
                $html = preg_replace($reg, "", $html);
            }
        }
        $html = preg_replace("/(\r?\n){2,}/", "\n\n", $html);
        return $html;
    }

    public function zip ($main_doc_id) {
        $export_folder_name = get_post_field('post_name', $main_doc_id);
        $source = $this->export_path . '/' . $export_folder_name . '/';
        $destination = $this->export_path . '/' . $export_folder_name . '.zip';
        $dest_url = $this->export_url . '/' . $export_folder_name . '.zip';

        if (!extension_loaded('zip') || !file_exists($source)) {
            return false;
        }

        // remove old zip
        if (file_exists($destination)) {
            unlink($destination);
        }

        $zip = new ZipArchive();
        if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
            return false;
        }

        $source = str_replace('\\', '/', realpath($source));

        if (is_dir($source) === true)
        {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

            foreach ($files as $file)
            {
                $file = str_replace('\\', '/', $file);

                // Ignore "." and ".." folders
                if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) )
                    continue;

                $file = realpath($file);

                if (is_dir($file) === true)
                {
                    $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                }
                else if (is_file($file) === true)
                {
                    $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
                }
            }
        }
        else if (is_file($source) === true)
        {
            $zip->addFromString(basename($source), file_get_contents($source));
        }

        $zip->close();

        return $dest_url;
    }

    public function rimrafDir ($dir) {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->rimrafDir("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    /**
     * Emit a Server-Sent Events message.
     *
     * @param mixed $data Data to be JSON-encoded and sent in the message.
     */
    protected function emit_sse_message ( $message, $action = 'message' ) {
        $data = array(
            'action'  => $action,
            'message' => $message,
            'max_delta' => $this->max_delta
        );

        echo "event: message\n";
        echo 'data: ' . wp_json_encode( $data ) . "\n\n";
        // Extra padding.
        echo ':' . str_repeat( ' ', 2048 ) . "\n\n";
        flush();
    }
}
