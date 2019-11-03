const $ = window.jQuery;

class DocsPress {
    constructor() {
        const self = this;

        self.cache = {};
        self.pendingAjax = false;
        self.xhrAjaxSearch = false;
        self.$body = $( 'body' );
        self.$window = $( window );
        self.$document = $( document );
        self.$preloader = $( '<div class="docspress-preloader"><span><span></span></span></div>' );
        self.$singleAjax = $( '.docspress-single-ajax' );

        self.initSearch();
        self.initAnchors();
        self.initFeedbacks();
        self.initAjax();
    }

    stripHash( href ) {
        return href.replace( /#.*/, '' );
    }

    initSearch() {
        const self = this;
        let timeout = false;

        self.$document.on( 'submit', '.docspress-search-form', function( e ) {
            e.preventDefault();
            self.prepareSearchResults( $( this ) );
        } );
        self.$document.on( 'input', '.docspress-search-form', function( e ) {
            e.preventDefault();

            clearTimeout( timeout );
            timeout = setTimeout( () => {
                self.prepareSearchResults( $( this ) );
            }, 500 );
        } );
    }

    prepareSearchResults( $form ) {
        const self = this;

        // abort if any request is in process already
        if ( self.xhrAjaxSearch ) {
            self.xhrAjaxSearch.abort();
        }

        // if empty search field.
        if ( ! $form.find( '.docspress-search-field' ).val() ) {
            $form.next( '.docspress-search-form-result' ).html( '' );
            return;
        }

        self.xhrAjaxSearch = $.ajax( {
            type: 'GET',
            url: $form.attr( 'action' ),
            data: $form.serialize(),
            success( data ) {
                const $data = $( data );
                const result = $data.find( '.docspress-search-list' ).get( 0 ).outerHTML;
                $form.next( '.docspress-search-form-result' ).html( result );
                self.xhrAjaxSearch = false;
            },
            error( e ) {
                // eslint-disable-next-line no-console
                console.log( e );
                self.xhrAjaxSearch = false;
            },
        } );
    }

    initAnchors() {
        const anchors = window.AnchorJS ? new window.AnchorJS() : false;

        if ( ! anchors ) {
            return;
        }

        anchors.options = {
            placement: 'right',
            visible: 'hover',
            icon: '#',
        };
        anchors.add( '.docspress-single-content .entry-content h2, .docspress-single-content .entry-content h3, .docspress-single-content .entry-content h4' );
    }

    initFeedbacks() {
        const self = this;

        // feedback links click
        self.$body.on( 'click', '.docspress-single-feedback a', function( e ) {
            self.onFeedbackClick( e, $( this ) );
        } );

        // feedback suggestion form send
        self.$body.on( 'submit', '.docspress-single-feedback + .docspress-single-feedback-suggestion', function( e ) {
            self.onFeedbackSuggestionSend( e, $( this ) );
        } );
    }

    initAjax() {
        const self = this;

        if ( ! self.$singleAjax.length ) {
            return;
        }

        // save current page data
        self.setCache( window.location.href, {
            href: window.location.href,
            title: document.title,
            doc: self.$singleAjax.html(),
            html: document.documentElement.outerHTML,
        } );

        // click on links
        self.$singleAjax.on( 'click', '.docspress-nav-list a, .docspress-single-breadcrumbs a, .docspress-single-articles a, .docspress-single-adjacent-nav a, .docspress-search-form-result a', function( e ) {
            self.onDocLinksClick( e );
        } );

        // on state change
        self.$window.on( 'popstate', function( e ) {
            self.renderDoc( e.target.location.href );
        } );
    }

    onFeedbackClick( e, $item ) {
        e.preventDefault();
        const self = this;

        // return if any request is in process already
        if ( self.pendingAjax ) {
            return;
        }

        self.pendingAjax = true;

        const $wrap = $item.closest( '.docspress-single-feedback' ).addClass( 'docspress-single-feedback-loading' );
        const $suggestionForm = $item.closest( '.docspress-single-content' ).find( '.docspress-single-feedback-suggestion' );

        const feedbackType = $item.data( 'type' );

        const data = {
            post_id: $item.data( 'id' ),
            type: feedbackType,
            action: 'docspress_ajax_feedback',
            _wpnonce: window.docspress_vars.nonce,
        };

        $wrap.append( self.$preloader.clone() );

        $.post( window.docspress_vars.ajaxurl, data, function( resp ) {
            $wrap.html( '<div>' + resp.data + '</div>' );

            if ( resp.success && $suggestionForm.length ) {
                $suggestionForm.show();
                $suggestionForm.append( `<input type="hidden" name="feedback_type" value="${ feedbackType }">` );
            }

            $wrap.removeClass( 'docspress-single-feedback-loading' );

            self.pendingAjax = false;
        } );
    }

    onFeedbackSuggestionSend( e, $form ) {
        e.preventDefault();
        const self = this;

        // return if any request is in process already
        if ( self.pendingAjax ) {
            return;
        }

        self.pendingAjax = true;

        const $wrap = $form.closest( '.docspress-single-feedback-suggestion' ).addClass( 'docspress-single-feedback-suggestion-loading' );
        const $button = $form.find( 'button' );

        const formData = $form.serializeArray().reduce( ( obj, item ) => {
            obj[ item.name ] = item.value;
            return obj;
        }, {} );

        const data = {
            post_id: formData.id,
            from: formData.from,
            suggestion: formData.suggestion,
            feedback_type: formData.feedback_type,
            action: 'docspress_ajax_feedback_suggestion',
            _wpnonce: window.docspress_vars.nonce,
        };

        $wrap.append( self.$preloader.clone() );

        $button.prop( 'disabled', 'disabled' );

        $.post( window.docspress_vars.ajaxurl, data, function( resp ) {
            $wrap.html( '<div>' + resp.data + '</div>' ).removeClass( 'docspress-single-feedback-suggestion-loading' );
            self.pendingAjax = false;
        } );
    }

    // cache ajax pages
    setCache( key, data ) {
        key = key || false;
        data = data || false;
        if ( ! key || ! data || this.cache[ key ] ) {
            return;
        }
        this.cache[ key ] = data;
    }

    getCache( key ) {
        key = key || false;
        if ( ! key || ! this.cache[ key ] ) {
            return false;
        }
        return this.cache[ key ];
    }

    renderDoc( href ) {
        const cached = this.getCache( href );

        // replace content.
        this.$singleAjax.html( cached.doc );
        $( 'title' ).text( cached.title );
        $( '.wp-admin-bar-edit .ab-item' ).attr( 'href', href );

        // scroll to top of doc.
        const top = $( '.docspress-single' )[ 0 ].getBoundingClientRect().top;
        if ( top < 0 ) {
            this.$document.scrollTop( this.$document.scrollTop() + top );
        }

        // init new anchors.
        this.initAnchors();

        this.$document.trigger( 'docspress_ajax_loaded', cached );
    }

    onDocLinksClick( e ) {
        const link = e.currentTarget;

        // Middle click, cmd click, and ctrl click should open
        // links in a new tab as normal.
        if ( e.which > 1 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey ) {
            return;
        }

        // Ignore cross origin links
        if ( window.location.protocol !== link.protocol || window.location.hostname !== link.hostname ) {
            return;
        }

        // Ignore case when a hash is being tacked on the current URL
        if ( link.href.indexOf( '#' ) > -1 && this.stripHash( link.href ) === this.stripHash( window.location.href ) ) {
            return;
        }

        // Ignore if local file protocol
        if ( window.location.protocol === 'file:' ) {
            return;
        }

        // Ignore e with default prevented
        if ( e.isDefaultPrevented() ) {
            return;
        }

        e.preventDefault();

        this.loadDocPage( link.href );
    }

    loadDocPage( href ) {
        const self = this;
        href = href || false;

        // stop when the same urls
        if ( ! href || self.stripHash( href ) === self.stripHash( window.location.href ) ) {
            return;
        }

        // return cached version
        const cached = self.getCache( href );
        if ( cached ) {
            // render doc
            self.renderDoc( href );

            // push state for new page
            window.history.pushState( null, cached.title, href );
            return;
        }

        // stop previous request
        if ( self.xhr && self.xhr.abort ) {
            self.xhr.abort();
            self.xhr = {};
        }

        // new ajax request
        const $ajaxBlock = self.$singleAjax.addClass( 'docspress-single-ajax-loading' );
        $ajaxBlock.find( '.docspress-single-content' ).append( self.$preloader.clone() );

        self.xhr = $.ajax( {
            url: href,
            success: function success( responseHtml ) {
                if ( ! responseHtml ) {
                    window.location = href;
                    return;
                }

                let $HTML = $( '<div>' ).html( responseHtml );
                const title = $HTML.find( 'title:eq(0)' ).text() || document.title;
                const $newDocContent = $HTML.find( '.docspress-single-ajax' ).html();

                if ( ! $newDocContent ) {
                    window.location = href;
                    return;
                }

                // save cache
                self.setCache( href, {
                    href: href,
                    title: title,
                    doc: $newDocContent,
                    html: responseHtml,
                } );

                // render
                self.renderDoc( href );

                // push state for new page
                window.history.pushState( null, title, href );

                // clear
                $HTML.remove();
                $HTML = null;

                $ajaxBlock.removeClass( 'docspress-single-ajax-loading' );
            },
            error: function error( msg ) {
                if ( msg.status !== 0 ) {
                    // eslint-disable-next-line no-console
                    console.log( 'error', msg );
                } else {
                    window.location = href;
                }

                $ajaxBlock.removeClass( 'docspress-single-ajax-loading' );
            },
        } );
    }
}

$( function() {
    new DocsPress();
} );
