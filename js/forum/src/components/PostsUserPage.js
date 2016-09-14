import UserPage from 'flarum/components/UserPage';
import LoadingIndicator from 'flarum/components/LoadingIndicator';
import Button from 'flarum/components/Button';
import CommentPost from 'flarum/components/CommentPost';
import Placeholder from 'flarum/components/Placeholder';

/**
 * The `PostsUserPage` component shows a user's activity feed inside of their
 * profile.
 */
export default class PostsUserPage extends UserPage {
  init() {
    super.init();

    /**
     * Whether or not the activity feed is currently loading.
     *
     * @type {Boolean}
     */
    this.loading = true;

    /**
     * Whether or not there are any more activity items that can be loaded.
     *
     * @type {Boolean}
     */
    this.moreResults = false;

    /**
     * The Post models in the feed.
     *
     * @type {Post[]}
     */
    this.posts = [];

    /**
     * The number of activity items to load per request.
     *
     * @type {Integer}
     */
    this.loadLimit = 20;

    this.loadUser(m.route.param('username'));
  }

  content() {
    let posts_remaining = (this.user.data.attributes.commentsCount - this.posts.length);
    let footer;

    if (this.loading) {
      footer = LoadingIndicator.component();
    } else if (this.moreResults) {
      footer = (
        <div className="PostsUserPage-loadMore">
          {Button.component({
            children: app.translator.trans('core.forum.user.posts_load_more_button'),
            className: 'Button',
            onclick: this.loadMore.bind(this)
          })}
        </div>
      );
    }

    if (this.posts.length === 0 && !this.loading && posts_remaining == 0) {
      const text = app.translator.trans('core.forum.user.posts_empty_text');
      return (
        <div className="PostsUserPage">
          {Placeholder.component({text})}
        </div>
      );
    }

    return (
      <div className="PostsUserPage">
        <ul className="PostsUserPage-list">
          {this.posts.map(post => (
            <li>
              <div className="PostsUserPage-discussion">
                {app.translator.trans('core.forum.user.in_discussion_text', {discussion: <a href={app.route.post(post)} config={m.route}>{post.discussion().title()}</a>})}
              </div>
              {CommentPost.component({post})}
            </li>
          ))}
          {(posts_remaining > 0 && ! this.moreResults && !this.loading) ? (
          <li>
            <div class="PostsUserPage-discussion"></div>
              <article class="Post CommentPost">
                <div>
                  <header class="Post-header">
                    <ul>
                      <li class="item-user">
                        <div class="PostUser">
                          <h3>
                            <span>
                              <span class="Avatar PostUser-avatar" style="background: rgb(255, 214, 108);">?</span>
                              <span class="username">{app.translator.transChoice('core.forum.user.posts_missing_head', posts_remaining, {count: posts_remaining})}!</span>
                            </span>
                          </h3>
                        </div>
                      </li>
                    </ul>
                  </header>
                <div class="Post-body">
                  <p>{app.translator.trans('core.forum.user.content_missing_text')}</p>
                </div>
                <footer class="Post-footer">
                  <ul></ul>
                </footer>
              </div>
            </article>
          </li>
          ) : ''}
        </ul>
        {footer}
      </div>
    );
  }

  /**
   * Initialize the component with a user, and trigger the loading of their
   * activity feed.
   */
  show(user) {
    super.show(user);

    this.refresh();
  }

  /**
   * Clear and reload the user's activity feed.
   *
   * @public
   */
  refresh() {
    this.loading = true;
    this.posts = [];

    m.lazyRedraw();

    this.loadResults().then(this.parseResults.bind(this));
  }

  /**
   * Load a new page of the user's activity feed.
   *
   * @param {Integer} [offset] The position to start getting results from.
   * @return {Promise}
   * @protected
   */
  loadResults(offset) {
    return app.store.find('posts', {
      filter: {
        user: this.user.id(),
        type: 'comment'
      },
      page: {offset, limit: this.loadLimit},
      sort: '-time'
    });
  }

  /**
   * Load the next page of results.
   *
   * @public
   */
  loadMore() {
    this.loading = true;
    this.loadResults(this.posts.length).then(this.parseResults.bind(this));
  }

  /**
   * Parse results and append them to the activity feed.
   *
   * @param {Post[]} results
   * @return {Post[]}
   */
  parseResults(results) {
    this.loading = false;

    [].push.apply(this.posts, results);

    this.moreResults = results.length >= this.loadLimit;
    m.redraw();

    return results;
  }
}
