/* global Vue */
/* global wp */
/* global swal */
/* global ajaxurl */

const $ = window.jQuery;
const adminVars = window.docspress_admin_vars;
const __ = adminVars.__;

if ( typeof swal !== 'undefined' ) {
    swal.setDefaults( {
        animation: false,
    } );
}

Vue.directive( 'sortable', {
    bind: function( el ) {
        const $el = $( el );

        $el.sortable( {
            stop: function( event, ui ) {
                const ids = [];

                $( ui.item.closest( 'ul' ) ).children( 'li' ).each( function( index, li ) {
                    ids.push( $( li ).data( 'id' ) );
                } );

                wp.ajax.post( {
                    action: 'docspress_sortable_docs',
                    ids: ids,
                    _wpnonce: adminVars.nonce,
                } );
            },
            cursor: 'move',
        } );
        $el.on( 'mousedown', function() {
            // set fixed height to prevent scroll jump
            // when dragging from bottom
            $( this ).css( 'min-height', $( this ).height() );
        } );
        $el.on( 'mouseup', function() {
            $( this ).css( 'min-height', '' );
        } );
    },
} );

new Vue( {
    el: '#docspress-app',
    data: {
        editurl: '',
        viewurl: '',
        docs: [],
    },

    mounted() {
        const self = this;
        const dom = $( self.$el );

        this.editurl = adminVars.editurl;
        this.viewurl = adminVars.viewurl;

        $.get( ajaxurl, {
            action: 'docspress_admin_get_docs',
            _wpnonce: adminVars.nonce,
        }, function( data ) {
            dom.find( '.docspress' ).removeClass( 'not-loaded' ).addClass( 'loaded' );
            dom.find( '.spinner' ).remove();
            dom.find( '.no-docspress' ).removeClass( 'not-loaded' );

            self.docs = data.data;
        } );
    },

    methods: {

        onError: function( error ) {
            swal( {
                title: 'Error!',
                text: error.statusText || error.responseText || error,
                type: 'error',
                closeOnConfirm: true,
                customClass: 'docspress-swal',
            } );
            // eslint-disable-next-line
            console.log( error );
        },

        addDoc: function() {
            const that = this;
            this.docs = this.docs || [];

            swal( {
                title: __.enter_doc_title,
                type: 'input',
                showCancelButton: true,
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                inputPlaceholder: __.enter_doc_title,
                customClass: 'docspress-swal',
            }, function( inputValue ) {
                if ( inputValue === false ) {
                    swal.close();
                    return false;
                }

                wp.ajax.send( {
                    data: {
                        action: 'docspress_create_doc',
                        title: inputValue,
                        parent: 0,
                        _wpnonce: adminVars.nonce,
                    },
                    success: function( res ) {
                        that.docs.unshift( res );
                        swal.close();
                    },
                    error: that.onError,
                } );
            } );
        },

        cloneDoc: function( doc ) {
            const that = this;
            this.docs = this.docs || [];

            swal( {
                title: __.enter_doc_title,
                type: 'input',
                showCancelButton: true,
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                inputPlaceholder: __.enter_doc_title,
                inputValue: __.clone_default_title.replace( '%s', doc.post.title ),
                customClass: 'docspress-swal',
            }, function( inputValue ) {
                if ( inputValue === false ) {
                    swal.close();
                    return false;
                }

                wp.ajax.send( {
                    data: {
                        action: 'docspress_clone_doc',
                        title: inputValue,
                        clone_from: doc.post.id,
                        _wpnonce: adminVars.nonce,
                    },
                    success: function( res ) {
                        // eslint-disable-next-line
                        console.log( res );
                        that.docs.unshift( res );
                        swal.close();
                    },
                    error: that.onError,
                } );
            } );
        },

        removeDoc: function( doc, docs ) {
            const that = this;

            swal( {
                title: __.remove_doc_title,
                text: __.remove_doc_text,
                type: 'warning',
                showCancelButton: true,
                confirmButtonText: __.remove_doc_button_yes,
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                customClass: 'docspress-swal',
            }, function() {
                that.removePost( doc, docs );
            } );
        },

        exportDoc: function( doc ) {
            const that = this;

            swal( {
                html: true,
                title: __.clone_default_title.replace( '%s', '<strong>' + doc.post.title + '</strong>' ),
                text: __.export_doc_text,
                type: 'info',
                showCancelButton: true,
                confirmButtonText: __.export_doc_button_yes,
                closeOnConfirm: false,
                customClass: 'docspress-swal',
            }, function() {
                swal( {
                    html: true,
                    title: __.exporting_doc_title,
                    text: '<div class="docspress-export-response">' + __.exporting_doc_text + '</div><div class="docspress-export-progress"><div class="docspress-export-progress-bar"></div></div>',
                    type: 'info',
                    showCancelButton: true,
                    showConfirmButton: false,
                    closeOnCancel: false,
                    customClass: 'docspress-swal',
                }, function() {
                    evtSource.close();
                    swal.close();
                } );

                const $response = $( '.docspress-export-response' );
                const $progress = $( '.docspress-export-progress .docspress-export-progress-bar' );
                let delta = 0;

                const evtSource = new window.EventSource( ajaxurl + '?action=docspress_export_doc&doc_id=' + doc.post.id );

                evtSource.onmessage = function( message ) {
                    const data = JSON.parse( message.data );

                    // eslint-disable-next-line
                    console.log( data );

                    switch ( data.action ) {
                    case 'message':
                        delta++;
                        $response.text( data.message );
                        $progress.css( 'width', ( 100 * delta / data.max_delta ) + '%' );
                        break;
                    case 'complete':
                        evtSource.close();
                        swal( {
                            html: true,
                            title: __.exported_doc_title,
                            text: '<a class="button button-primary button-hero" href="' + data.message + '">' + __.exported_doc_download + '</a>',
                            type: 'success',
                            showCancelButton: true,
                            showConfirmButton: false,
                            closeOnCancel: false,
                            cancelButtonText: __.exported_doc_cancel,
                            customClass: 'docspress-swal',
                        }, function() {
                            swal.close();
                        } );
                        break;
                    }
                };
                evtSource.onerror = function() {
                    that.onError( this );
                    evtSource.close();
                };
            } );
        },

        addSection: function( doc ) {
            const that = this;

            swal( {
                title: __.enter_section_title,
                type: 'input',
                showCancelButton: true,
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                inputPlaceholder: __.enter_section_title,
                customClass: 'docspress-swal',
            }, function( inputValue ) {
                if ( inputValue === false ) {
                    swal.close();
                    return false;
                }

                inputValue = inputValue.trim();

                if ( inputValue ) {
                    wp.ajax.send( {
                        data: {
                            action: 'docspress_create_doc',
                            title: inputValue,
                            parent: doc.post.id,
                            order: doc.child.length,
                            _wpnonce: adminVars.nonce,
                        },
                        success: function( res ) {
                            doc.child.push( res );
                            swal.close();
                        },
                        error: that.onError,
                    } );
                }
            } );
        },

        removeSection: function( section, sections ) {
            const that = this;

            swal( {
                title: __.remove_section_title,
                text: __.remove_section_text,
                type: 'warning',
                showCancelButton: true,
                confirmButtonText: __.remove_section_button_yes,
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                customClass: 'docspress-swal',
            }, function() {
                that.removePost( section, sections );
            } );
        },

        addArticle: function( section, event ) {
            const parentEvent = event;
            const that = this;

            swal( {
                title: __.enter_doc_title,
                type: 'input',
                showCancelButton: true,
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                inputPlaceholder: __.enter_doc_title,
                customClass: 'docspress-swal',
            }, function( inputValue ) {
                if ( inputValue === false ) {
                    swal.close();
                    return false;
                }

                wp.ajax.send( {
                    data: {
                        action: 'docspress_create_doc',
                        title: inputValue,
                        parent: section.post.id,
                        status: 'draft',
                        order: section.child.length,
                        _wpnonce: adminVars.nonce,
                    },
                    success: function( res ) {
                        section.child.push( res );

                        const articles = $( parentEvent.target ).closest( '.section-title' ).next();

                        if ( articles.hasClass( 'collapsed' ) ) {
                            articles.removeClass( 'collapsed' );
                        }

                        swal.close();
                    },
                    error: that.onError,
                } );
            } );
        },

        removeArticle: function( article, articles ) {
            const that = this;

            swal( {
                title: __.remove_article_title,
                text: __.remove_article_text,
                type: 'warning',
                showCancelButton: true,
                confirmButtonText: __.remove_article_button_yes,
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                customClass: 'docspress-swal',
            }, function() {
                that.removePost( article, articles );
            } );
        },

        removePost: function( index, items ) {
            const that = this;

            wp.ajax.send( {
                data: {
                    action: 'docspress_remove_doc',
                    id: items[ index ].post.id,
                    _wpnonce: adminVars.nonce,
                },
                success: function() {
                    Vue.delete( items, index );
                    swal.close();
                },
                error: that.onError,
            } );
        },

        toggleCollapse: function( event ) {
            $( event.target ).siblings( 'ul.articles' ).toggleClass( 'collapsed' );
        },
    },
} );
