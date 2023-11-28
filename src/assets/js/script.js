const { on, trigger } = window.ivent;

class DocsPress {
  constructor() {
    const self = this;

    self.cache = {};
    self.pendingAjax = false;
    self.xhrAjaxSearch = false;

    self.$preloader = document.createElement('div');
    self.$preloader.className = 'docspress-preloader';
    self.$preloader.innerHTML = '<span><span></span></span>';

    self.$singleAjax = document.querySelector('.docspress-single-ajax');

    self.initSearch();
    self.initDocSearch();
    self.initAnchors();
    self.initFeedbacks();
    self.initAjax();
  }

  // eslint-disable-next-line class-methods-use-this
  stripHash(href) {
    return href.replace(/#.*/, '');
  }

  initSearch() {
    const self = this;
    let timeout = false;

    on(document, 'submit', '.docspress-search-form', (e) => {
      e.preventDefault();
      self.prepareSearchResults(e.delegateTarget);
    });
    on(document, 'input', '.docspress-search-form', (e) => {
      e.preventDefault();

      clearTimeout(timeout);
      timeout = setTimeout(() => {
        self.prepareSearchResults(e.delegateTarget);
      }, 500);
    });
  }

  prepareSearchResults($form) {
    const self = this;

    // abort if any request is in process already
    if (self.xhrAjaxSearch) {
      self.xhrAjaxSearch.abort();
    }

    // if empty search field.
    if (!$form.querySelector('.docspress-search-field')?.value) {
      $form.nextElementSibling.querySelector('.docspress-search-form-result').innerHTML = '';
      $form.classList.remove('docspress-search-form-existence');
      return;
    }

    let actionUrl = $form.getAttribute('action');
    actionUrl +=
      (-1 < actionUrl.indexOf('?') ? '&' : '?') +
      new URLSearchParams(new FormData($form)).toString();

    self.xhrAjaxSearch = new XMLHttpRequest();
    self.xhrAjaxSearch.open('GET', actionUrl);
    self.xhrAjaxSearch.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    self.xhrAjaxSearch.onload = function () {
      if (200 === self.xhrAjaxSearch.status) {
        const parser = new DOMParser();
        const data = parser.parseFromString(self.xhrAjaxSearch.responseText, 'text/html');
        const result = data.querySelector('.docspress-search-list').outerHTML;

        const $resultEl = $form.parentElement.querySelector(
          ':scope > .docspress-search-form-result'
        );
        if ($resultEl) {
          $resultEl.innerHTML = result;
        }

        $form.classList.add('docspress-search-form-existence');
      } else {
        // eslint-disable-next-line no-console
        console.log('Error:', self.xhrAjaxSearch.status);
      }

      // self.xhrAjaxSearch = false;
    };
    self.xhrAjaxSearch.onerror = function () {
      // eslint-disable-next-line no-console
      console.log('Request failed');
      self.xhrAjaxSearch = false;
    };

    self.xhrAjaxSearch.send();
  }

  // eslint-disable-next-line class-methods-use-this
  initDocSearch() {
    if ('undefined' === typeof window.docsearch) {
      return;
    }

    const docsearchElements = document.querySelectorAll('.docspress-docsearch');
    docsearchElements.forEach((element) => {
      const appId = element.getAttribute('data-docsearch-app-id');
      const apiKey = element.getAttribute('data-docsearch-api-key');
      const indexName = element.getAttribute('data-docsearch-index-name');

      if (appId && apiKey && indexName) {
        window.docsearch({
          appId,
          apiKey,
          indexName,
          container: element,
          debug: false,
        });
      }
    });
  }

  // eslint-disable-next-line class-methods-use-this
  initAnchors() {
    const anchors = window.AnchorJS ? new window.AnchorJS() : false;

    if (!anchors) {
      return;
    }

    anchors.options = {
      placement: 'right',
      visible: 'hover',
      icon: '#',
    };
    anchors.add(
      '.docspress-single-content .entry-content h2, .docspress-single-content .entry-content h3, .docspress-single-content .entry-content h4'
    );
  }

  initFeedbacks() {
    const self = this;

    // feedback links click
    on(document, 'click', '.docspress-single-feedback a', (e) => {
      self.onFeedbackClick(e);
    });

    // feedback suggestion form send
    on(
      document,
      'submit',
      '.docspress-single-feedback + .docspress-single-feedback-suggestion',
      (e) => {
        self.onFeedbackSuggestionSend(e);
      }
    );
  }

  initAjax() {
    const self = this;

    if (!self.$singleAjax) {
      return;
    }

    // save current page data
    self.setCache(window.location.href, {
      href: window.location.href,
      editHref: document.querySelector('#wp-admin-bar-edit .ab-item')?.getAttribute('href'),
      title: document.title,
      doc: self.$singleAjax.innerHTML,
      html: document.documentElement.outerHTML,
    });

    // click on links
    on(
      self.$singleAjax,
      'click',
      '.docspress-nav-list a, .docspress-single-breadcrumbs a, .docspress-single-articles a, .docspress-single-adjacent-nav a, .docspress-search-form-result a',
      (e) => {
        self.onDocLinksClick(e);
      }
    );

    // on state change
    // we have to check the hash change and prevent render doc,
    // because for some reason `popstate` event fires on hash change.
    let popOld = document.location.pathname;
    let popNew = '';

    on(window, 'popstate', (e) => {
      popNew = document.location.pathname;

      // Path changed.
      if (popNew !== popOld) {
        self.renderDoc(e.delegateTarget.location.href);
      }

      popOld = popNew;
    });
  }

  onFeedbackClick(e) {
    e.preventDefault();

    const self = this;
    const $button = e.delegateTarget;

    // return if any request is in process already
    if (self.pendingAjax) {
      return;
    }

    self.pendingAjax = true;

    const $wrap = $button.closest('.docspress-single-feedback');
    const $suggestionForm = $button
      .closest('.docspress-single-content')
      .querySelector('.docspress-single-feedback-suggestion');

    $wrap.classList.add('docspress-single-feedback-loading');

    const feedbackType = $button.getAttribute('data-type');

    const data = {
      post_id: $button.getAttribute('data-id'),
      type: feedbackType,
      action: 'docspress_ajax_feedback',
      _wpnonce: window.docspress_vars.nonce,
    };

    $wrap.appendChild(self.$preloader.cloneNode(true));

    fetch(window.docspress_vars.ajaxurl, {
      method: 'POST',
      body: new URLSearchParams(data),
    })
      .then((response) => response.json())
      .then((resp) => {
        $wrap.innerHTML = `<div>${resp.data}</div>`;

        if (resp.success && $suggestionForm) {
          $suggestionForm.style.display = 'block';
          $suggestionForm.innerHTML += `<input type="hidden" name="feedback_type" value="${feedbackType}">`;
        }

        $wrap.classList.remove('docspress-single-feedback-loading');

        self.pendingAjax = false;
      });
  }

  onFeedbackSuggestionSend(e) {
    e.preventDefault();

    const self = this;
    const form = e.delegateTarget;

    // return if any request is in process already
    if (self.pendingAjax) {
      return;
    }

    self.pendingAjax = true;

    const wrap = form.closest('.docspress-single-feedback-suggestion');
    const button = form.querySelector('button');

    wrap.classList.add('docspress-single-feedback-suggestion-loading');

    const formData = Array.from(new FormData(form)).reduce((obj, [key, value]) => {
      obj[key] = value;
      return obj;
    }, {});

    const data = {
      post_id: formData.id,
      from: formData.from,
      suggestion: formData.suggestion,
      feedback_type: formData.feedback_type,
      action: 'docspress_ajax_feedback_suggestion',
      _wpnonce: window.docspress_vars.nonce,
    };

    wrap.appendChild(self.$preloader.cloneNode(true));

    button.disabled = true;

    fetch(window.docspress_vars.ajaxurl, {
      method: 'POST',
      body: new URLSearchParams(data),
    })
      .then((response) => response.json())
      .then((resp) => {
        wrap.innerHTML = `<div>${resp.data}</div>`;
        wrap.classList.remove('docspress-single-feedback-suggestion-loading');
        self.pendingAjax = false;
      });
  }

  // cache ajax pages
  setCache(key, data) {
    key = key || false;
    data = data || false;
    if (!key || !data || this.cache[key]) {
      return;
    }
    this.cache[key] = data;
  }

  getCache(key) {
    key = key || false;
    if (!key || !this.cache[key]) {
      return false;
    }
    return this.cache[key];
  }

  renderDoc(href) {
    const cached = this.getCache(href);

    // replace content.
    this.$singleAjax.innerHTML = cached.doc;
    document.title = cached.title;

    if (cached.editHref) {
      document.querySelector('#wp-admin-bar-edit .ab-item')?.setAttribute('href', cached.editHref);
    }

    // scroll to top of doc.
    const $content = document.querySelector('.docspress-single');
    const { top } = $content.getBoundingClientRect();

    if (0 > top && $content) {
      $content.scrollIntoView();
    }

    // init new anchors.
    this.initAnchors();

    trigger(document, 'docspress_ajax_loaded', { data: cached });
  }

  onDocLinksClick(e) {
    const link = e.delegateTarget;

    // Middle click, cmd click, and ctrl click should open
    // links in a new tab as normal.
    if (1 < e.which || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
      return;
    }

    // Ignore cross origin links
    if (window.location.protocol !== link.protocol || window.location.hostname !== link.hostname) {
      return;
    }

    // Ignore case when a hash is being tacked on the current URL
    if (
      -1 < link.href.indexOf('#') &&
      this.stripHash(link.href) === this.stripHash(window.location.href)
    ) {
      return;
    }

    // Ignore if local file protocol
    if ('file:' === window.location.protocol) {
      return;
    }

    // Ignore e with default prevented
    if (e.defaultPrevented) {
      return;
    }

    e.preventDefault();

    this.loadDocPage(link.href);
  }

  loadDocPage(href) {
    const self = this;
    href = href || false;

    // stop when the same urls
    if (!href || self.stripHash(href) === self.stripHash(window.location.href)) {
      return;
    }

    // return cached version
    const cached = self.getCache(href);
    if (cached) {
      // render doc
      self.renderDoc(href);

      // push state for new page
      window.history.pushState(null, cached.title, href);
      return;
    }

    // stop previous request
    if (self.xhr && self.xhr.abort) {
      self.xhr.abort();
      self.xhr = {};
    }

    // new ajax request
    const xhr = new XMLHttpRequest();

    self.$singleAjax.classList.add('docspress-single-ajax-loading');
    self.$singleAjax
      .querySelector('.docspress-single-content')
      .appendChild(self.$preloader.cloneNode(true));

    xhr.open('GET', href);
    xhr.onload = function () {
      if (200 === xhr.status) {
        const responseHtml = xhr.responseText;

        if (!responseHtml) {
          window.location = href;
          return;
        }

        let $HTML = document.createElement('div');
        $HTML.innerHTML = responseHtml;
        const title = $HTML.querySelector('title').textContent || document.title;
        const editHref = $HTML.querySelector('#wp-admin-bar-edit .ab-item')?.getAttribute('href');
        const newDocContent = $HTML.querySelector('.docspress-single-ajax').innerHTML;

        if (!newDocContent) {
          window.location = href;
          return;
        }

        // save cache
        self.setCache(href, {
          href,
          editHref,
          title,
          doc: newDocContent,
          html: responseHtml,
        });

        // render
        self.renderDoc(href);

        // push state for new page
        window.history.pushState(null, title, href);

        // clear
        $HTML.remove();
        $HTML = null;

        self.$singleAjax.classList.remove('docspress-single-ajax-loading');
      } else {
        if (0 !== xhr.status) {
          // eslint-disable-next-line no-console
          console.log('error', xhr);
        } else {
          window.location = href;
        }

        self.$singleAjax.classList.remove('docspress-single-ajax-loading');
      }
    };

    xhr.send();
  }
}

on(document, 'ready', () => {
  // eslint-disable-next-line no-new
  new DocsPress();
});
