const { on, trigger } = window.ivent;

class DocsPress {
  constructor() {
    const self = this;

    self.cache = {};
    self.pendingStylesheets = new WeakMap();
    self.pendingAjax = false;
    self.xhrAjaxSearch = false;

    self.$preloader = document.createElement('div');
    self.$preloader.className = 'docspress-preloader';
    self.$preloader.innerHTML = '<span><span></span></span>';

    self.$singleAjax = document.querySelector('.docspress-single-ajax');

    self.initEarlier();

    on(document, 'ready', () => {
      self.init();
    });
  }

  // eslint-disable-next-line class-methods-use-this
  stripHash(href) {
    return href.replace(/#.*/, '');
  }

  initEarlier() {
    this.initSidebarScroll();
  }

  init() {
    this.initSearch();
    this.initDocSearch();
    this.initAnchors();
    this.initFeedbacks();
    this.initAjax();
  }

  initSearch() {
    const self = this;
    let timeout = false;

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
    const searchField = $form.querySelector('.docspress-search-field');
    const query = searchField?.value;
    const $resultEl = $form.parentElement.querySelector(':scope > .docspress-search-form-result');

    // abort if any request is in process already
    if (self.xhrAjaxSearch) {
      self.xhrAjaxSearch.abort();
    }

    // if empty search field.
    if (!query) {
      if ($resultEl) {
        $resultEl.innerHTML = '';
      }
      $form.classList.remove('docspress-search-form-existence');
      return;
    }

    const { search_url: searchUrl, search_nonce: searchNonce } = window.docspress_vars;
    const url = new URL(searchUrl);
    const parent = new FormData($form).get('child_of');

    url.searchParams.set('s', query);
    if (parent) {
      url.searchParams.set('child_of', parent);
    }

    self.xhrAjaxSearch = new XMLHttpRequest();
    self.xhrAjaxSearch.open('GET', url);
    self.xhrAjaxSearch.responseType = 'json';
    if (searchNonce) {
      self.xhrAjaxSearch.setRequestHeader('X-WP-Nonce', searchNonce);
    }

    self.xhrAjaxSearch.onload = function () {
      if (!$form.isConnected || searchField.value !== query) {
        return;
      }

      if (self.xhrAjaxSearch.status === 200) {
        const { response } = self.xhrAjaxSearch;
        const parser = new DOMParser();
        const data = parser.parseFromString(
          typeof response === 'string' ? response : '',
          'text/html'
        );
        const result = data.querySelector('.docspress-search-list');

        if ($resultEl) {
          $resultEl.innerHTML = result?.outerHTML || '';
        }
        $form.classList.toggle('docspress-search-form-existence', !!result);
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
    if (typeof window.docsearch === 'undefined') {
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

  // eslint-disable-next-line class-methods-use-this
  initSidebarScroll() {
    const sidebar = document.querySelector('.docspress-single-sidebar-wrap');

    // Skip if no sidebar available or it is not scrollable.
    if (!sidebar || sidebar.scrollHeight <= sidebar.clientHeight) {
      return;
    }

    const item = sidebar.querySelector('.current_page_item');

    if (!item) {
      return;
    }

    const link = item.querySelector(':scope > a');
    const linkTop = link.getBoundingClientRect().top;
    const sidebarTop = sidebar.getBoundingClientRect().top;
    const sidebarScrollTop = sidebar.scrollTop;

    const sidebarHeight = sidebar.clientHeight;
    const linkOffsetTop = linkTop + sidebarScrollTop - sidebarTop;
    const linkHeight = link.clientHeight;
    const a = linkOffsetTop - sidebarHeight + linkHeight;

    if (sidebar.scrollTop > linkOffsetTop || sidebar.scrollTop < a) {
      sidebar.scrollTop = linkOffsetTop - sidebarHeight / 2 + linkHeight / 2;
    }
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
      .catch(() => false)
      .then((resp) => {
        $wrap.innerHTML = `<div>${self.getResponseMessage(resp)}</div>`;

        if (resp && resp.success && $suggestionForm) {
          $suggestionForm.style.display = 'block';
          $suggestionForm.innerHTML += `<input type="hidden" name="feedback_type" value="${feedbackType}">`;
        }

        $wrap.classList.remove('docspress-single-feedback-loading');

        self.pendingAjax = false;
      });
  }

  // A stale nonce makes check_ajax_referer() answer -1, which has no message to show.
  // eslint-disable-next-line class-methods-use-this
  getResponseMessage(resp) {
    return resp && resp.data ? resp.data : window.docspress_vars.error_message;
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
      // Theme overrides of the form template may lack the honeypot field.
      docspress_website: formData.docspress_website || '',
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
      .catch(() => false)
      .then((resp) => {
        wrap.innerHTML = `<div>${self.getResponseMessage(resp)}</div>`;
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

  loadDocStyles(html, href) {
    const selector = 'link[rel="stylesheet"][href], style[id]';
    const page = new DOMParser().parseFromString(html, 'text/html');
    const inPage = (asset) => !asset.closest('.docspress-single-ajax');
    const existingAssets = Array.from(document.querySelectorAll(selector)).filter(inPage);
    const pending = [];
    const updates = [];
    let nextAsset = null;

    Array.from(page.querySelectorAll(selector))
      .filter(inPage)
      .reverse()
      .forEach((asset) => {
        const isStylesheet = asset.tagName === 'LINK';
        if (isStylesheet) {
          asset.href = new URL(asset.getAttribute('href'), href).href;
        }

        const existing = existingAssets.find(
          (element) =>
            element.tagName === asset.tagName &&
            (isStylesheet
              ? element.href === asset.href && element.media === asset.media
              : element.id === asset.id)
        );

        if (existing) {
          if (!isStylesheet && existing.textContent !== asset.textContent) {
            updates.push([existing, asset.textContent]);
          }

          const loading = this.pendingStylesheets.get(existing);
          if (loading) {
            pending.push(loading);
          }
          nextAsset = existing;
          return;
        }

        if (
          isStylesheet &&
          !asset.disabled &&
          (!asset.media || window.matchMedia(asset.media).matches)
        ) {
          const loading = new Promise((resolve) => {
            asset.addEventListener('load', resolve, { once: true });
            asset.addEventListener('error', resolve, { once: true });
          });
          this.pendingStylesheets.set(asset, loading);
          pending.push(loading);
        }

        if (nextAsset) {
          nextAsset.before(asset);
        } else {
          document.head.appendChild(asset);
        }
        existingAssets.push(asset);
        nextAsset = asset;
      });

    return { loading: Promise.all(pending), updates };
  }

  async renderDoc(href, updateHistory = false) {
    const cached = this.getCache(href);
    if (!cached) {
      return;
    }

    const navigation = Symbol(href);
    this.navigation = navigation;
    const { loading, updates } = this.loadDocStyles(cached.html, cached.responseUrl || href);
    await loading;
    if (this.navigation !== navigation) {
      return;
    }

    updates.forEach(([style, content]) => {
      style.textContent = content;
    });

    // replace content.
    this.$singleAjax.innerHTML = cached.doc;
    document.title = cached.title;

    if (cached.editHref) {
      document.querySelector('#wp-admin-bar-edit .ab-item')?.setAttribute('href', cached.editHref);
    }

    // scroll to top of doc.
    const $content = document.querySelector('.docspress-single');

    if ($content) {
      const { top } = $content.getBoundingClientRect();

      if (top < 0) {
        $content.scrollIntoView();
      }
    }

    // init new anchors.
    this.initAnchors();

    trigger(document, 'docspress_ajax_loaded', { data: cached });

    if (updateHistory) {
      window.history.pushState(null, cached.title, href);
    }
    this.$singleAjax.classList.remove('docspress-single-ajax-loading');
  }

  onDocLinksClick(e) {
    const link = e.delegateTarget;

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
    if (
      link.href.indexOf('#') > -1 &&
      this.stripHash(link.href) === this.stripHash(window.location.href)
    ) {
      return;
    }

    // Ignore if local file protocol
    if (window.location.protocol === 'file:') {
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

    if (self.xhr) {
      self.xhr.abort();
    }

    if (self.getCache(href)) {
      self.renderDoc(href, true);
      return;
    }

    const xhr = new XMLHttpRequest();
    self.xhr = xhr;
    self.navigation = xhr;

    if (!self.$singleAjax.classList.contains('docspress-single-ajax-loading')) {
      self.$singleAjax.classList.add('docspress-single-ajax-loading');
      self.$singleAjax
        .querySelector('.docspress-single-content')
        .appendChild(self.$preloader.cloneNode(true));
    }

    xhr.open('GET', href);
    xhr.onload = function () {
      if (self.navigation !== xhr) {
        return;
      }

      if (xhr.status === 200) {
        const responseHtml = xhr.responseText;

        if (!responseHtml) {
          window.location = href;
          return;
        }

        let $HTML = document.createElement('div');
        $HTML.innerHTML = responseHtml;
        const title = $HTML.querySelector('title').textContent || document.title;
        const editHref = $HTML.querySelector('#wp-admin-bar-edit .ab-item')?.getAttribute('href');
        const newDocContent = $HTML.querySelector('.docspress-single-ajax')?.innerHTML;

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
          responseUrl: xhr.responseURL,
        });

        self.renderDoc(href, true);

        $HTML.remove();
        $HTML = null;
      } else {
        if (xhr.status !== 0) {
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

// eslint-disable-next-line no-new
new DocsPress();
