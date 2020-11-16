import Model from '../Model';
import computed from '../utils/computed';
import ItemList from '../utils/ItemList';
import Badge from '../components/Badge';
import User from './User';
import Post from './Post';

export default class Discussion extends Model {
  title = Model.attribute<string>('title');
  slug = Model.attribute<string>('slug');

  createdAt = Model.attribute<Date>('createdAt', Model.transformDate);
  user = Model.hasOne<User>('user');
  firstPost = Model.hasOne<Post>('firstPost');

  lastPostedAt = Model.attribute<Date>('lastPostedAt', Model.transformDate);
  lastPostedUser = Model.hasOne<User>('lastPostedUser');
  lastPost = Model.hasOne<Post>('lastPost');
  lastPostNumber = Model.attribute<number>('lastPostNumber');

  commentCount = Model.attribute<number>('commentCount');
  replyCount = computed<number>('commentCount', (commentCount) => Math.max(0, commentCount - 1));
  posts = Model.hasMany<Post>('posts');
  mostRelevantPost = Model.hasOne<Post>('mostRelevantPost');

  lastReadAt = Model.attribute<Date>('lastReadAt', Model.transformDate);
  lastReadPostNumber = Model.attribute<number>('lastReadPostNumber');
  isUnread = computed<boolean>('unreadCount', (unreadCount) => !!unreadCount);
  isRead = computed<boolean>('unreadCount', (unreadCount) => app.session.user && !unreadCount);

  hiddenAt = Model.attribute<Date>('hiddenAt', Model.transformDate);
  hiddenUser = Model.hasOne<User>('hiddenUser');
  isHidden = computed<boolean>('hiddenAt', (hiddenAt) => !!hiddenAt);

  canReply = Model.attribute<boolean>('canReply');
  canRename = Model.attribute<boolean>('canRename');
  canHide = Model.attribute<boolean>('canHide');
  canDelete = Model.attribute<boolean>('canDelete');

  /**
   * Remove a post from the discussion's posts relationship.
   *
   * @param {Integer} id The ID of the post to remove.
   * @public
   */
  removePost(id) {
    const relationships = this.data.relationships;
    const posts = relationships && relationships.posts;

    if (posts) {
      posts.data.some((data, i) => {
        if (id === data.id) {
          posts.data.splice(i, 1);
          return true;
        }
      });
    }
  }

  /**
   * Get the estimated number of unread posts in this discussion for the current
   * user.
   *
   * @return {Integer}
   * @public
   */
  unreadCount(): number {
    const user = app.session.user;

    if (user && user.markedAllAsReadAt() < this.lastPostedAt()) {
      const unreadCount = Math.max(0, this.lastPostNumber() - (this.lastReadPostNumber() || 0));
      // If posts have been deleted, it's possible that the unread count could exceed the
      // actual post count. As such, we take the min of the two to ensure this isn't an issue.
      return Math.min(unreadCount, this.commentCount());
    }

    return 0;
  }

  /**
   * Get the Badge components that apply to this discussion.
   *
   * @return {ItemList}
   * @public
   */
  badges(): ItemList {
    const items = new ItemList();

    if (this.isHidden()) {
      items.add('hidden', <Badge type="hidden" icon="fas fa-trash" label={app.translator.trans('core.lib.badge.hidden_tooltip')} />);
    }

    return items;
  }

  /**
   * Get a list of all of the post IDs in this discussion.
   *
   * @return {Array}
   * @public
   */
  postIds(): string[] {
    const posts = this.data.relationships.posts;

    return posts ? posts.data.map((link) => link.id) : [];
  }
}
