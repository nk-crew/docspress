(function($) {
    'use strict';

    var pending_ajax = false;
    var $preloader = $('<div class="docspress-preloader"><span><span></span></span></div>');

    function stripHash (href) {
        return href.replace(/#.*/, '');
    }

    var docspress = {
        initialize: function() {
            var self = this;
            var $body = $('body');

            $body.on('click', '.docspress-single-feedback a', self.feedback);

            // ajax
            var $ajax = $('.docspress-single-ajax');
            if ($ajax.length) {
                // save current page data
                self.setCache(window.location.href, {
                    href: window.location.href,
                    title: document.title,
                    doc: $ajax.html(),
                    html: document.documentElement.outerHTML
                });

                // click on links
                $ajax.on('click', '.docspress-nav-list a, .docspress-single-breadcrumbs a, .docspress-single-articles a, .docspress-single-adjacent-nav a', function (e) {
                    self.onDocLinksClick(e);
                });

                // on state change
                $(window).on('popstate', function(e) {
                    self.renderDoc(e.target.location.href);
                });
            }
        },

        feedback: function(e) {
            e.preventDefault();

            // return if any request is in process already
            if ( pending_ajax ) {
                return;
            }

            pending_ajax = true;

            var self = $(this),
                wrap = self.closest('.docspress-single-feedback').addClass('docspress-single-feedback-loading'),
                data = {
                    post_id: self.data('id'),
                    type: self.data('type'),
                    action: 'docspress_ajax_feedback',
                    _wpnonce: docspress_vars.nonce
                };

            wrap.append($preloader.clone());
            $.post(docspress_vars.ajaxurl, data, function(resp) {
                wrap.html('<div>' + resp.data + '</div>').removeClass('docspress-single-feedback-loading');
                pending_ajax = false;
            });
        },

        // cache ajax pages
        cache: {},
        setCache: function setCache(key, data) {
            key = key || false;
            data = data || false;
            if(!key || !data || this.cache[key]) {
                return;
            }
            this.cache[key] = data;
        },
        getCache: function getCache(key) {
            key = key || false;
            if(!key || !this.cache[key]) {
                return false;
            }
            return this.cache[key];
        },

        renderDoc: function renderDoc (href) {
            var cached = this.getCache(href);
            $('.docspress-single-ajax').html(cached.doc);
            $('title').text(cached.title);
            $(document).trigger('docspress_ajax_loaded', cached);
        },

        onDocLinksClick: function onDocLinksClick (e) {
            var link = e.currentTarget;

            // Middle click, cmd click, and ctrl click should open
            // links in a new tab as normal.
            if (e.which > 1 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
                return;
            }

            // Ignore cross origin links
            if (window.location.protocol !== link.protocol || window.location.hostname !== link.hostname) {
                return;
            }

            // Ignore case when a hash is being tacked on the current URL
            if (link.href.indexOf('#') > -1 && stripHash(link.href) === stripHash(window.location.href)) {
                return;
            }

            // Ignore if local file protocol
            if(window.location.protocol === 'file:') {
                return;
            }

            // Ignore e with default prevented
            if (e.isDefaultPrevented()) {
                return;
            }

            e.preventDefault();

            this.loadDocPage(link.href);
        },

        loadDocPage: function loadDocPage (href) {
            var self = this;
            href = href || false;

            // stop when the same urls
            if (!href || stripHash(href) === stripHash(window.location.href)) {
                return;
            }

            // return cached version
            var cached = self.getCache(href);
            if (cached) {
                // render doc
                self.renderDoc(href);

                // push state for new page
                window.history.pushState(null, cached.title, href);
                return;
            }

            // stop previous request
            if(self.xhr && self.xhr.abort) {
                self.xhr.abort();
                self.xhr = {};
            }

            // new ajax request
            var $ajaxBlock = $('.docspress-single-ajax').addClass('docspress-single-ajax-loading');
            $ajaxBlock.find('.docspress-single-content').append($preloader.clone());

            self.xhr = $.ajax({
                url: href,
                success: function success (responseHtml) {
                    if(!responseHtml) {
                        window.location = href;
                        return;
                    }

                    var $HTML = $('<div>').html(responseHtml);
                    var title = $HTML.find('title:eq(0)').text() || document.title;
                    var $newDocContent = $HTML.find('.docspress-single-ajax').html();

                    if (!$newDocContent) {
                        window.location = href;
                        return;
                    }

                    // save cache
                    self.setCache(href, {
                        href: href,
                        title: title,
                        doc: $newDocContent,
                        html: responseHtml
                    });

                    // render
                    self.renderDoc(href);

                    // push state for new page
                    window.history.pushState(null, title, href);

                    // clear
                    $HTML.remove();
                    $HTML = null;

                    $ajaxBlock.removeClass('docspress-single-ajax-loading');
                },
                error: function error (msg) {
                    if(msg.status !== 0) {
                        console.log('error', msg);
                    } else {
                        window.location = href;
                    }

                    $ajaxBlock.removeClass('docspress-single-ajax-loading');
                }
            });
        }
    };

    $(function() {
        docspress.initialize();
    });

})(jQuery);
