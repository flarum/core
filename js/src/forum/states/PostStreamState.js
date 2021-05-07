import { throttle } from 'throttle-debounce';
import anchorScroll from '../../common/utils/anchorScroll';

class PostStreamState {
  constructor(discussion, includedPosts = []) {
    /**
     * The discussion to display the post stream for.
     *
     * @type {Discussion}
     */
    this.discussion = discussion;

    /**
     * Whether or not the infinite-scrolling auto-load functionality is
     * disabled.
     *
     * @type {Boolean}
     */
    this.paused = false;

    this.loadPageTimeouts = {};
    this.pagesLoading = 0;

    this.index = 0;
    this.previousIndex = 0;
    this.number = 1;

    /**
     * The number of posts that are currently visible in the viewport.
     *
     * @type {Number}
     */
    this.visible = 1;

    /**
     * The description to render on the scrubber.
     *
     * @type {String}
     */
    this.description = '';

    /**
     * When the page is scrolled, goToIndex is called, or the page is loaded,
     * various listeners result in the scrubber being updated with a new
     * position and values. However, if goToNumber is called, the scrubber
     * will not be updated. Accordingly, we add logic to the scrubber's
     * onupdate to update itself, but only when needed, as indicated by this
     * property.
     *
     * @type {Boolean}
     */
    this.forceUpdateScrubber = false;

    this.loadNext = throttle(300, this._loadNext);
    this.loadPrevious = throttle(300, this._loadPrevious);

    this.show(includedPosts);
  }

  /**
   * Update the stream so that it loads and includes the latest posts in the
   * discussion, if the end is being viewed.
   *
   * @public
   */
  update() {
    if (!this.viewingEnd()) return Promise.resolve();

    this.visibleEnd = this.count();

    return this.loadRange(this.visibleStart, this.visibleEnd);
  }

  /**
   * Load and scroll up to the first post in the discussion.
   *
   * @return {Promise}
   */
  goToFirst() {
    return this.goToIndex(0);
  }

  /**
   * Load and scroll down to the last post in the discussion.
   *
   * @return {Promise}
   */
  goToLast() {
    return this.goToIndex(this.count() - 1, true);
  }

  /**
   * Load and scroll to a post with a certain number.
   *
   * @param {number|String} number The post number to go to. If 'reply', go to
   *     the last post and scroll the reply preview into view.
   * @param {Boolean} noAnimation
   * @return {Promise}
   */
  goToNumber(number, noAnimation = false) {
    // If we want to go to the reply preview, then we will go to the end of the
    // discussion and then scroll to the very bottom of the page.
    if (number === 'reply') {
      const resultPromise = this.goToLast();
      this.targetPost.reply = true;
      return resultPromise;
    }

    this.paused = true;

    this.loadPromise = this.loadNearNumber(number);

    this.needsScroll = true;
    this.targetPost = { number };
    this.animateScroll = !noAnimation;
    this.number = number;

    // In this case, the redraw is only called after the response has been loaded
    // because we need to know the indices of the post range before we can
    // start scrolling to items. Calling redraw early causes issues.
    // Since this is only used for external navigation to the post stream, the delay
    // before the stream is moved is not an issue.
    return this.loadPromise.then(() => m.redraw());
  }

  /**
   * Load and scroll to a certain index within the discussion.
   *
   * @param {number} index
   * @param {Boolean} noAnimation
   * @return {Promise}
   */
  goToIndex(index, noAnimation = false) {
    this.paused = true;

    this.loadPromise = this.loadNearIndex(index);

    this.needsScroll = true;
    this.targetPost = { index };
    this.animateScroll = !noAnimation;
    this.previousIndex = index === this.previousIndex ? index : this.index;
    this.index = index;

    m.redraw();

    return this.loadPromise;
  }

  /**
   * Clear the stream and load posts near a certain number. Returns a promise.
   * If the post with the given number is already loaded, the promise will be
   * resolved immediately.
   *
   * @param {number} number
   * @return {Promise}
   */
  loadNearNumber(number) {
    if (this.posts().some((post) => post && Number(post.number()) === Number(number))) {
      return Promise.resolve();
    }

    this.reset();

    return app.store
      .find('posts', {
        filter: { discussion: this.discussion.id() },
        page: { near: number },
      })
      .then(this.show.bind(this));
  }

  /**
   * Clear the stream and load posts near a certain index. A page of posts
   * surrounding the given index will be loaded. Returns a promise. If the given
   * index is already loaded, the promise will be resolved immediately.
   *
   * @param {number} index
   * @return {Promise}
   */
  loadNearIndex(index) {
    if (index >= this.visibleStart && index < this.visibleEnd) {
      return Promise.resolve();
    }

    const start = this.sanitizeIndex(index - this.constructor.loadCount / 2);
    const end = start + this.constructor.loadCount;

    this.reset(start, end);

    return this.loadRange(start, end).then(this.show.bind(this));
  }

  /**
   * Load the next page of posts.
   */
  _loadNext() {
    const start = this.visibleEnd;
    const end = (this.visibleEnd = this.sanitizeIndex(this.visibleEnd + this.constructor.loadCount));

    // Unload the posts which are two pages back from the page we're currently
    // loading.
    const twoPagesAway = start - this.constructor.loadCount * 2;
    if (twoPagesAway > this.visibleStart && twoPagesAway >= 0) {
      this.visibleStart = twoPagesAway + this.constructor.loadCount + 1;

      if (this.loadPageTimeouts[twoPagesAway]) {
        clearTimeout(this.loadPageTimeouts[twoPagesAway]);
        this.loadPageTimeouts[twoPagesAway] = null;
        this.pagesLoading--;
      }
    }

    this.loadPage(start, end);
  }

  /**
   * Load the previous page of posts.
   */
  _loadPrevious() {
    const end = this.visibleStart;
    const start = (this.visibleStart = this.sanitizeIndex(this.visibleStart - this.constructor.loadCount));

    // Unload the posts which are two pages back from the page we're currently
    // loading.
    const twoPagesAway = start + this.constructor.loadCount * 2;
    if (twoPagesAway < this.visibleEnd && twoPagesAway <= this.count()) {
      this.visibleEnd = twoPagesAway;

      if (this.loadPageTimeouts[twoPagesAway]) {
        clearTimeout(this.loadPageTimeouts[twoPagesAway]);
        this.loadPageTimeouts[twoPagesAway] = null;
        this.pagesLoading--;
      }
    }

    this.loadPage(start, end, true);
  }

  /**
   * Load a page of posts into the stream and redraw.
   *
   * @param {number} start
   * @param {number} end
   * @param {Boolean} backwards
   */
  loadPage(start, end, backwards = false) {
    this.pagesLoading++;

    const redraw = () => {
      if (start < this.visibleStart || end > this.visibleEnd) return;

      const anchorIndex = backwards ? this.visibleEnd - 1 : this.visibleStart;
      anchorScroll(`.PostStream-item[data-index="${anchorIndex}"]`, m.redraw.sync);
    };
    redraw();

    this.loadPageTimeouts[start] = setTimeout(
      () => {
        this.loadRange(start, end).then(() => {
          redraw();
          this.pagesLoading--;
        });
        this.loadPageTimeouts[start] = null;
      },
      this.pagesLoading - 1 ? 1000 : 0
    );
  }

  /**
   * Load and inject the specified range of posts into the stream, without
   * clearing it.
   *
   * @param {number} start
   * @param {number} end
   * @return {Promise}
   */
  loadRange(start, end) {
    const loadIds = [];
    const loaded = [];

    this.discussion
      .postIds()
      .slice(start, end)
      .forEach((id) => {
        const post = app.store.getById('posts', id);

        if (post && post.discussion() && typeof post.canEdit() !== 'undefined') {
          loaded.push(post);
        } else {
          loadIds.push(id);
        }
      });

    if (loadIds.length) {
      return app.store.find('posts', loadIds).then((newPosts) => {
        return loaded.concat(newPosts).sort((a, b) => a.createdAt() - b.createdAt());
      });
    }

    return Promise.resolve(loaded);
  }

  /**
   * Set up the stream with the given array of posts.
   *
   * @param {Post[]} posts
   */
  show(posts) {
    this.visibleStart = posts.length ? this.discussion.postIds().indexOf(posts[0].id()) : 0;
    this.visibleEnd = this.sanitizeIndex(this.visibleStart + posts.length);
  }

  /**
   * Reset the stream so that a specific range of posts is displayed. If a range
   * is not specified, the first page of posts will be displayed.
   *
   * @param {number} [start]
   * @param {number} [end]
   */
  reset(start, end) {
    this.visibleStart = start || 0;
    this.visibleEnd = this.sanitizeIndex(end || this.constructor.loadCount);
  }

  /**
   * Get the visible page of posts.
   *
   * @return {Post[]}
   */
  posts() {
    return this.discussion
      .postIds()
      .slice(this.visibleStart, this.visibleEnd)
      .map((id) => {
        const post = app.store.getById('posts', id);

        return post && post.discussion() && typeof post.canEdit() !== 'undefined' ? post : null;
      });
  }

  /**
   * Get the total number of posts in the discussion.
   *
   * @return {number}
   */
  count() {
    return this.discussion.postIds().length;
  }

  /**
   * Check whether or not the scrubber should be disabled, i.e. if all of the
   * posts are visible in the viewport.
   *
   * @return {Boolean}
   */
  disabled() {
    return this.visible >= this.count();
  }

  /**
   * Are we currently viewing the end of the discussion?
   *
   * @return {boolean}
   */
  viewingEnd() {
    // In some cases, such as if we've stickied a post, an event post
    // may have been added / removed. This means that `this.visibleEnd`
    // and`this.count()` will be out of sync by 1 post, but we are still
    // "viewing the end" of the post stream, so we should still reload
    // all posts up until the last one.
    return Math.abs(this.count() - this.visibleEnd) <= 1;
  }

  /**
   * Make sure that the given index is not outside of the possible range of
   * indexes in the discussion.
   *
   * @param {number} index
   */
  sanitizeIndex(index) {
    return Math.max(0, Math.min(this.count(), Math.floor(index)));
  }
}

/**
 * The number of posts to load per page.
 *
 * @type {number}
 */
PostStreamState.loadCount = 20;

export default PostStreamState;
