import EditPostComposer from '../components/EditPostComposer';
import Button from '../../common/components/Button';
import Separator from '../../common/components/Separator';
import ItemList from '../../common/utils/ItemList';
import extractText from '../../common/utils/extractText';

/**
 * The `PostControls` utility constructs a list of buttons for a post which
 * perform actions on it.
 */
const PostControls = {
  /**
   * Get a list of controls for a post.
   *
   * @param {Post} post
   * @param {*} context The parent component under which the controls menu will
   *     be displayed.
   * @return {ItemList}
   * @public
   */
  controls(post, context) {
    const items = new ItemList();

    ['user', 'moderation', 'destructive'].forEach((section) => {
      const controls = this[section + 'Controls'](post, context).toArray();
      if (controls.length) {
        controls.forEach((item) => items.add(item.itemName, item));
        items.add(section + 'Separator', Separator.component());
      }
    });

    return items;
  },

  /**
   * Get controls for a post pertaining to the current user (e.g. report).
   *
   * @param {Post} post
   * @param {*} context The parent component under which the controls menu will
   *     be displayed.
   * @return {ItemList}
   * @protected
   */
  userControls(post, context) {
    return new ItemList();
  },

  /**
   * Get controls for a post pertaining to moderation (e.g. edit).
   *
   * @param {Post} post
   * @param {*} context The parent component under which the controls menu will
   *     be displayed.
   * @return {ItemList}
   * @protected
   */
  moderationControls(post, context) {
    const items = new ItemList();

    if (post.contentType() === 'comment' && post.canEdit()) {
      if (!post.isHidden()) {
        items.add(
          'edit',
          Button.component(
            {
              icon: 'fas fa-pencil-alt',
              onclick: this.editAction.bind(post),
            },
            app.translator.trans('core.forum.post_controls.edit_button')
          )
        );
      }
    }

    return items;
  },

  /**
   * Get controls for a post that are destructive (e.g. delete).
   *
   * @param {Post} post
   * @param {*} context The parent component under which the controls menu will
   *     be displayed.
   * @return {ItemList}
   * @protected
   */
  destructiveControls(post, context) {
    const items = new ItemList();

    if (post.contentType() === 'comment' && !post.isHidden()) {
      if (post.canHide()) {
        items.add(
          'hide',
          Button.component(
            {
              icon: 'far fa-trash-alt',
              onclick: this.hideAction.bind(post),
            },
            app.translator.trans('core.forum.post_controls.delete_button')
          )
        );
      }
    } else {
      if (post.contentType() === 'comment' && post.canHide()) {
        items.add(
          'restore',
          Button.component(
            {
              icon: 'fas fa-reply',
              onclick: this.restoreAction.bind(post),
            },
            app.translator.trans('core.forum.post_controls.restore_button')
          )
        );
      }
      if (post.canDelete()) {
        items.add(
          'delete',
          Button.component(
            {
              icon: 'fas fa-times',
              onclick: this.deleteAction.bind(post, context),
            },
            app.translator.trans('core.forum.post_controls.delete_forever_button')
          )
        );
      }
    }

    return items;
  },

  /**
   * Open the composer to edit a post.
   *
   * @return {Promise}
   */
  editAction() {
    return new Promise((resolve) => {
      app.composer.load(EditPostComposer, { post: this });
      app.composer.show();

      return resolve();
    });
  },

  /**
   * Hide a post.
   *
   * @return {Promise}
   */
  hideAction() {
    if (!confirm(extractText(app.translator.trans('core.forum.post_controls.hide_confirmation')))) return;
    this.pushAttributes({ hiddenAt: new Date(), hiddenUser: app.session.user });

    return this.save({ isHidden: true }).then(() => m.redraw());
  },

  /**
   * Restore a post.
   *
   * @return {Promise}
   */
  restoreAction() {
    this.pushAttributes({ hiddenAt: null, hiddenUser: null });

    return this.save({ isHidden: false }).then(() => m.redraw());
  },

  /**
   * Delete a post.
   *
   * @return {Promise}
   */
  deleteAction(context) {
    if (!confirm(extractText(app.translator.trans('core.forum.post_controls.delete_confirmation')))) return;
    if (context) context.loading = true;

    return this.delete()
      .then(() => {
        const discussion = this.discussion();

        discussion.removePost(this.id());

        // If this was the last post in the discussion, then we will assume that
        // the whole discussion was deleted too.
        if (!discussion.postIds().length) {
          app.discussions.removeDiscussion(discussion);

          if (app.viewingDiscussion(discussion)) {
            app.history.back();
          }
        }
      })
      .catch(() => {})
      .then(() => {
        if (context) context.loading = false;
        m.redraw();
      });
  },
};

export default PostControls;
