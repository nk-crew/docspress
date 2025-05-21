/* eslint-disable */
const { jQuery: $, ajaxurl, Swal, Vue, docspress_admin_vars: adminVars } = window;

const __ = adminVars.__;

const swalConfig = {
  customClass: 'docspress-swal',
  showClass: {
    popup: 'swal2-noanimation',
    backdrop: 'swal2-noanimation',
  },
  hideClass: {
    popup: '',
    backdrop: '',
  },
};

Vue.directive('sortable', {
  bind: function (el) {
    const $el = $(el);

    $el.sortable({
      stop: function (event, ui) {
        const ids = [];

        $(ui.item.closest('ul'))
          .children('li')
          .each(function (index, li) {
            ids.push($(li).data('id'));
          });

        $.post(ajaxurl, {
          action: 'docspress_sortable_docs',
          ids: ids,
          _wpnonce: adminVars.nonce,
        });
      },
      cursor: 'move',
    });
    $el.on('mousedown', function () {
      // set fixed height to prevent scroll jump
      // when dragging from bottom
      $(this).css('min-height', $(this).height());
    });
    $el.on('mouseup', function () {
      $(this).css('min-height', '');
    });
  },
});

/**
 * Get categorized docs.
 *
 * @param {array} docs docs list.
 * @param {array} terms terms list.
 *
 * @return {object} categorized docs list.
 */
function getCategorizedDocs(docs, terms) {
  const data = Object.assign([], docs);

  const categorized = {
    [`_0`]: {
      name: '',
      docs: [],
    },
  };

  terms.forEach((term) => {
    categorized[`_${term.term_id}`] = {
      name: term.name,
      docs: [],
    };
  });

  data.forEach((doc) => {
    if (categorized[`_${doc.post.cat_id}`]) {
      categorized[`_${doc.post.cat_id}`].docs.push(doc);
    } else {
      categorized[`_0`].docs.push(doc);
    }
  });

  return categorized;
}

/**
 * Remove doc from list by id.
 *
 * @param {array} docs docs list.
 * @param {int} id post ID.
 *
 * @return {array} updated docs list.
 */
function removeDoc(docs, id) {
  for (let i = 0; i < docs.length; i++) {
    if (docs[i].post.id === id) {
      docs.splice(i, 1);
    } else if (docs[i].child && docs[i].child.length) {
      docs[i].child = removeDoc(docs[i].child, id);
    }
  }

  return docs;
}

new Vue({
  el: '#docspress-app',
  data: {
    editurl: '',
    viewurl: '',
    docs: [],
    categorized: [],
  },

  mounted() {
    const self = this;
    const dom = $(self.$el);

    this.editurl = adminVars.editurl;
    this.viewurl = adminVars.viewurl;

    self.docs = [];

    $.get(
      ajaxurl,
      {
        action: 'docspress_admin_get_docs',
        _wpnonce: adminVars.nonce,
      },
      function ({ data }) {
        dom.find('.docspress').removeClass('not-loaded').addClass('loaded');
        dom.find('.spinner').remove();
        dom.find('.no-docspress').removeClass('not-loaded');

        self.terms = Object.assign([], data.terms);
        self.docs = Object.assign([], data.docs);
        self.categorized = getCategorizedDocs(data.docs, self.terms);
      }
    );
  },

  methods: {
    onError: function (error) {
      Swal.showValidationMessage(
        `Request failed: ${error.message || error.statusText || error.responseText || error}`
      );
      // eslint-disable-next-line
      console.log(error);
    },

    addDoc: function () {
      const that = this;

      Swal.fire({
        title: __.enter_doc_title,
        input: 'text',
        showCancelButton: true,
        showLoaderOnConfirm: true,
        inputPlaceholder: __.enter_doc_title,
        preConfirm: (value) => {
          if (value === false) {
            // Swal.close();
            return false;
          }

          return $.post(ajaxurl, {
            action: 'docspress_create_doc',
            title: value,
            parent: 0,
            _wpnonce: adminVars.nonce,
          })
            .done((fetchedData) => {
              if (!fetchedData || !fetchedData.success || !fetchedData.data) {
                return false;
              }

              that.docs.unshift(fetchedData.data);
              that.categorized = getCategorizedDocs(that.docs, that.terms);
            })
            .fail(that.onError);
        },
        ...swalConfig,
      });
    },

    cloneDoc: function (doc) {
      const that = this;

      Swal.fire({
        title: __.enter_doc_title,
        input: 'text',
        showCancelButton: true,
        showLoaderOnConfirm: true,
        inputPlaceholder: __.enter_doc_title,
        inputValue: __.clone_default_title.replace('%s', doc.post.title),
        preConfirm: (value) => {
          if (value === false) {
            // Swal.close();
            return false;
          }

          return $.post(ajaxurl, {
            action: 'docspress_clone_doc',
            title: value,
            clone_from: doc.post.id,
            _wpnonce: adminVars.nonce,
          })
            .done((fetchedData) => {
              if (!fetchedData || !fetchedData.success || !fetchedData.data) {
                return false;
              }

              that.docs.unshift(fetchedData.data);
              that.categorized = getCategorizedDocs(that.docs, that.terms);
            })
            .fail(that.onError);
        },
        ...swalConfig,
      });
    },

    removeDoc: function (id) {
      const that = this;

      Swal.fire({
        title: __.remove_doc_title,
        text: __.remove_doc_text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: __.remove_doc_button_yes,
        showLoaderOnConfirm: true,
        preConfirm: () => {
          return that.removePost(id);
        },
        ...swalConfig,
      });
    },

    exportDoc: function (doc) {
      const that = this;

      Swal.fire({
        title: __.clone_default_title.replace('%s', '<strong>' + doc.post.title + '</strong>'),
        html: __.export_doc_text,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: __.export_doc_button_yes,
        ...swalConfig,
      }).then(function () {
        Swal.fire({
          title: __.exporting_doc_title,
          html:
            '<div class="docspress-export-response">' +
            __.exporting_doc_text +
            '</div><div class="docspress-export-progress"><div class="docspress-export-progress-bar"></div></div>',
          icon: 'info',
          showCancelButton: true,
          showConfirmButton: false,
          ...swalConfig,
        }).then(function () {
          evtSource.close();
        });

        const $response = $('.docspress-export-response');
        const $progress = $('.docspress-export-progress .docspress-export-progress-bar');
        let delta = 0;

        const evtSource = new window.EventSource(
          `${ajaxurl}?action=docspress_export_doc&doc_id=${doc.post.id}&_wpnonce=${adminVars.nonce}`
        );

        evtSource.onmessage = function (message) {
          const data = JSON.parse(message.data);

          // eslint-disable-next-line
          console.log(data);

          switch (data.action) {
            case 'message':
              delta++;
              $response.text(data.message);
              $progress.css('width', (100 * delta) / data.max_delta + '%');
              break;
            case 'complete':
              evtSource.close();
              Swal.fire({
                title: __.exported_doc_title,
                html:
                  '<a class="button button-primary button-hero" href="' +
                  data.message +
                  '">' +
                  __.exported_doc_download +
                  '</a>',
                icon: 'success',
                showCancelButton: true,
                showConfirmButton: false,
                closeOnCancel: false,
                cancelButtonText: __.exported_doc_cancel,
                ...swalConfig,
              });
              break;
          }
        };
        evtSource.onerror = function () {
          that.onError(this);
          evtSource.close();
        };
      });
    },

    addSection: function (doc) {
      const that = this;

      Swal.fire({
        title: __.enter_section_title,
        input: 'text',
        showCancelButton: true,
        showLoaderOnConfirm: true,
        inputPlaceholder: __.enter_section_title,
        preConfirm: (value) => {
          if (value === false) {
            return false;
          }

          return $.post(ajaxurl, {
            action: 'docspress_create_doc',
            title: value,
            parent: doc.post.id,
            order: doc.child.length,
            _wpnonce: adminVars.nonce,
          })
            .done((fetchedData) => {
              if (!fetchedData || !fetchedData.success || !fetchedData.data) {
                return false;
              }

              doc.child.push(fetchedData.data);
            })
            .fail(that.onError);
        },
        ...swalConfig,
      });
    },

    removeSection: function (id) {
      const that = this;

      Swal.fire({
        title: __.remove_section_title,
        text: __.remove_section_text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: __.remove_section_button_yes,
        showLoaderOnConfirm: true,
        preConfirm: () => {
          return that.removePost(id);
        },
        ...swalConfig,
      });
    },

    addArticle: function (section, event) {
      const parentEvent = event;
      const that = this;

      Swal.fire({
        title: __.enter_doc_title,
        input: 'text',
        showCancelButton: true,
        showLoaderOnConfirm: true,
        inputPlaceholder: __.enter_doc_title,
        preConfirm: (value) => {
          if (value === false) {
            return false;
          }

          return $.post(ajaxurl, {
            action: 'docspress_create_doc',
            title: value,
            parent: section.post.id,
            status: 'draft',
            order: section.child.length,
            _wpnonce: adminVars.nonce,
          })
            .done((fetchedData) => {
              if (!fetchedData || !fetchedData.success || !fetchedData.data) {
                return false;
              }

              section.child.push(fetchedData.data);

              const articles = $(parentEvent.target).closest('.section-title').next();

              if (articles.hasClass('collapsed')) {
                articles.removeClass('collapsed');
              }
            })
            .fail(that.onError);
        },
        ...swalConfig,
      });
    },

    removeArticle: function (id) {
      const that = this;

      Swal.fire({
        title: __.remove_article_title,
        text: __.remove_article_text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: __.remove_article_button_yes,
        showLoaderOnConfirm: true,
        preConfirm: () => {
          return that.removePost(id);
        },
        ...swalConfig,
      });
    },

    removePost: function (postId) {
      const that = this;

      return $.post(ajaxurl, {
        action: 'docspress_remove_doc',
        id: postId,
        _wpnonce: adminVars.nonce,
      })
        .done((fetchedData) => {
          if (!fetchedData || !fetchedData.success) {
            return false;
          }

          that.docs = removeDoc(that.docs, postId);
          that.categorized = getCategorizedDocs(that.docs, that.terms);
        })
        .fail(that.onError);
    },

    toggleCollapse: function (event) {
      event.preventDefault();

      $(event.target)
        .closest('.section-title')
        .toggleClass('collapsed')
        .siblings('ul.articles')
        .toggleClass('collapsed');
    },
  },
});
