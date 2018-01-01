/* jshint devel:true */
/* global Vue */
/* global docspress */
/* global wp */
/* global swal */
/* global ajaxurl */
(function ($) {
    'use strict';

    var __ = docspress_admin_vars.__;

    if (typeof swal !== 'undefined') {
        swal.setDefaults({
            animation: false
        });
    }

    Vue.directive('sortable', {
        bind: function(el) {
            var $el = $(el);

            $el.sortable({
                stop: function(event, ui) {
                    var ids = [];

                    $( ui.item.closest('ul') ).children('li').each(function(index, el) {
                        ids.push( $(el).data('id'));
                    });

                    wp.ajax.post({
                        action: 'docspress_sortable_docs',
                        ids: ids,
                        _wpnonce: docspress_admin_vars.nonce
                    });
                },
                cursor: 'move'
            });
            $el.on('mousedown', function() {
                // set fixed height to prevent scroll jump
                // when dragging from bottom
                $(this).css('min-height', $(this).height());
            });
            $el.on('mouseup', function() {
                $(this).css('min-height', '');
            });
        }
    });

    new Vue({
        el: '#docspress-app',
        data: {
            editurl: '',
            viewurl: '',
            docs: []
        },

        mounted: function() {
            var self = this,
                dom = $( self.$el );

            this.editurl = docspress_admin_vars.editurl;
            this.viewurl = docspress_admin_vars.viewurl;

            $.get(ajaxurl, {
                action: 'docspress_admin_get_docs',
                _wpnonce: docspress_admin_vars.nonce
            }, function(data) {
                dom.find('.docspress').removeClass('not-loaded').addClass('loaded');
                dom.find('.spinner').remove();
                dom.find('.no-docspress').removeClass('not-loaded');

                self.docs = data.data;
            });
        },

        methods: {

            onError: function(error) {
                swal({
                    title: "Error!",
                    text: error.statusText || error.responseText || error,
                    type: "error",
                    closeOnConfirm: true,
                    customClass: 'docspress-swal'
                });
                console.log(error);
            },

            addDoc: function() {

                var that = this;
                this.docs = this.docs || [];

                swal({
                    title: __.enter_doc_title,
                    type: "input",
                    showCancelButton: true,
                    closeOnConfirm: false,
                    showLoaderOnConfirm: true,
                    inputPlaceholder: __.enter_doc_title,
                    customClass: 'docspress-swal'
                }, function(inputValue){
                    if (inputValue === false) {
                        swal.close();
                        return false;
                    }

                    wp.ajax.send( {
                        data: {
                            action: 'docspress_create_doc',
                            title: inputValue,
                            parent: 0,
                            _wpnonce: docspress_admin_vars.nonce
                        },
                        success: function(res) {
                            that.docs.unshift(res);
                            swal.close();
                        },
                        error: that.onError
                    });

                });
            },

            cloneDoc: function(doc) {
                var that = this;
                this.docs = this.docs || [];

                swal({
                    title: __.enter_doc_title,
                    type: "input",
                    showCancelButton: true,
                    closeOnConfirm: false,
                    showLoaderOnConfirm: true,
                    inputPlaceholder: __.enter_doc_title,
                    inputValue: __.clone_default_title.replace('%s', doc.post.title),
                    customClass: 'docspress-swal'
                }, function(inputValue){
                    if (inputValue === false) {
                        swal.close();
                        return false;
                    }

                    wp.ajax.send( {
                        data: {
                            action: 'docspress_clone_doc',
                            title: inputValue,
                            clone_from: doc.post.id,
                            _wpnonce: docspress_admin_vars.nonce
                        },
                        success: function(res) {
                            console.log(res);
                            that.docs.unshift(res);
                            swal.close();
                        },
                        error: that.onError
                    });

                });
            },

            removeDoc: function(doc, docs) {
                var that = this;

                swal({
                    title: __.remove_doc_title,
                    text: __.remove_doc_text,
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonText: __.remove_doc_button_yes,
                    closeOnConfirm: false,
                    showLoaderOnConfirm: true,
                    customClass: 'docspress-swal'
                }, function() {
                    that.removePost(doc, docs);
                });
            },

            exportDoc: function(doc) {
                var that = this;

                swal({
                    html: true,
                    title: __.clone_default_title.replace('%s', '<strong>' + doc.post.title + '</strong>'),
                    text: __.export_doc_text,
                    type: "info",
                    showCancelButton: true,
                    confirmButtonText: __.export_doc_button_yes,
                    closeOnConfirm: false,
                    customClass: 'docspress-swal'
                }, function() {
                    swal({
                        html: true,
                        title: __.exporting_doc_title,
                        text: '<div class="docspress-export-response">' + __.exporting_doc_text + '</div><div class="docspress-export-progress"><div class="docspress-export-progress-bar"></div></div>',
                        type: "info",
                        showCancelButton: true,
                        showConfirmButton: false,
                        closeOnCancel: false,
                        customClass: 'docspress-swal'
                    }, function(a) {
                        evtSource.close();
                        swal.close();
                    });

                    var $response = $('.docspress-export-response');
                    var $progress = $('.docspress-export-progress .docspress-export-progress-bar');
                    var delta = 0;

                    var evtSource = new EventSource(ajaxurl + '?action=docspress_export_doc&doc_id=' + doc.post.id);
                    evtSource.onmessage = function ( message ) {
                        var data = JSON.parse( message.data );
                        console.log(data);

                        switch (data.action) {
                            case 'message':
                                delta++;
                                $response.text(data.message);
                                $progress.css('width', (100 * delta / data.max_delta) + '%');
                                break;
                            case 'complete':
                                evtSource.close();
                                swal({
                                    html: true,
                                    title: __.exported_doc_title,
                                    text: '<a class="button button-primary button-hero" href="' + data.message + '">' + __.exported_doc_download + '</a>',
                                    type: "success",
                                    showCancelButton: true,
                                    showConfirmButton: false,
                                    closeOnCancel: false,
                                    cancelButtonText: __.exported_doc_cancel,
                                    customClass: 'docspress-swal'
                                }, function(a) {
                                    swal.close();
                                });
                                break;
                        }
                    };
                    evtSource.onerror = function(e) {
                        that.onError(this);
                        evtSource.close();
                    };
                });
            },

            addSection: function(doc) {
                var that = this;

                swal({
                    title: __.enter_section_title,
                    type: "input",
                    showCancelButton: true,
                    closeOnConfirm: false,
                    showLoaderOnConfirm: true,
                    inputPlaceholder: __.enter_section_title,
                    customClass: 'docspress-swal'
                }, function(inputValue){
                    if (inputValue === false) {
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
                                _wpnonce: docspress_admin_vars.nonce
                            },
                            success: function(res) {
                                doc.child.push( res );
                                swal.close();
                            },
                            error: that.onError
                        });
                    }
                });
            },

            removeSection: function(section, sections) {
                var that = this;

                swal({
                    title: __.remove_section_title,
                    text: __.remove_section_text,
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonText: __.remove_section_button_yes,
                    closeOnConfirm: false,
                    showLoaderOnConfirm: true,
                    customClass: 'docspress-swal'
                }, function() {
                    that.removePost(section, sections);
                });
            },

            addArticle: function(section, event) {
                var parentEvent = event;
                var that = this;

                swal({
                    title: __.enter_doc_title,
                    type: "input",
                    showCancelButton: true,
                    closeOnConfirm: false,
                    showLoaderOnConfirm: true,
                    inputPlaceholder: __.enter_doc_title,
                    customClass: 'docspress-swal'
                }, function(inputValue){
                    if (inputValue === false) {
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
                            _wpnonce: docspress_admin_vars.nonce
                        },
                        success: function(res) {
                            section.child.push( res );

                            var articles = $( parentEvent.target ).closest('.section-title').next();

                            if ( articles.hasClass('collapsed') ) {
                                articles.removeClass('collapsed');
                            }

                            swal.close();
                        },
                        error: that.onError
                    });
                });
            },

            removeArticle: function(article, articles) {
                var that = this;

                swal({
                    title: __.remove_article_title,
                    text: __.remove_article_text,
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonText: __.remove_article_button_yes,
                    closeOnConfirm: false,
                    showLoaderOnConfirm: true,
                    customClass: 'docspress-swal'
                }, function(){
                    that.removePost(article, articles);
                });
            },

            removePost: function(index, items) {
                var that = this;

                wp.ajax.send( {
                    data: {
                        action: 'docspress_remove_doc',
                        id: items[index].post.id,
                        _wpnonce: docspress_admin_vars.nonce
                    },
                    success: function() {
                        Vue.delete(items, index);
                        swal.close();
                    },
                    error: that.onError
                });
            },

            toggleCollapse: function(event) {
                $(event.target).siblings('ul.articles').toggleClass('collapsed');
            }
        }
    });
})(jQuery);