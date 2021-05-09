import DiscussionPage from '../components/DiscussionPage';
import ReplyComposer from '../components/ReplyComposer';
import LogInModal from '../components/LogInModal';
import Button from '../../common/components/Button';
import Separator from '../../common/components/Separator';
import RenameDiscussionModal from '../components/RenameDiscussionModal';
import ItemList from '../../common/utils/ItemList';
import extractText from '../../common/utils/extractText';

/**
 * The `DiscussionControls` utility constructs a list of buttons for a
 * discussion which perform actions on it.
 */
const DiscussionControls = {
  /**
   * Get a list of controls for a discussion.
   *
   * @param {Discussion} discussion
   * @param {*} context The parent component under which the controls menu will
   *     be displayed.
   * @return {ItemList}
   * @public
   */
  controls(discussion, context) {
    const items = new ItemList();

    ['user', 'moderation', 'destructive'].forEach((section) => {
      const controls = this[section + 'Controls'](discussion, context).toArray();
      if (controls.length) {
        controls.forEach((item) => items.add(item.itemName, item));
        items.add(section + 'Separator', Separator.component());
      }
    });

    return items;
  },

  /**
   * Get controls for a discussion pertaining to the current user (e.g. reply,
   * follow).
   *
   * @param {Discussion} discussion
   * @param {*} context The parent component under which the controls menu will
   *     be displayed.
   * @return {ItemList}
   * @protected
   */
  userControls(discussion, context) {
    const items = new ItemList();

    // Only add a reply control if this is the discussion's controls dropdown
    // for the discussion page itself. We don't want it to show up for
    // discussions in the discussion list, etc.
    if (context instanceof DiscussionPage) {
      items.add(
        'reply',
        !app.session.user || discussion.canReply()
          ? Button.component(
              {
                icon: 'fas fa-reply',
                onclick: () => {
                  // If the user is not logged in, the promise rejects, and a login modal shows up.
                  // Since that's already handled, we dont need to show an error message in the console.
                  return this.replyAction
                    .bind(discussion)(true, false)
                    .catch(() => {});
                },
              },
              app.translator.trans(
                app.session.user ? 'core.forum.discussion_controls.reply_button' : 'core.forum.discussion_controls.log_in_to_reply_button'
              )
            )
          : Button.component(
              {
                icon: 'fas fa-reply',
                className: 'disabled',
                title: app.translator.trans('core.forum.discussion_controls.cannot_reply_text'),
              },
              app.translator.trans('core.forum.discussion_controls.cannot_reply_button')
            )
      );
    }

    return items;
  },

  /**
   * Get controls for a discussion pertaining to moderation (e.g. rename, lock).
   *
   * @param {Discussion} discussion
   * @param {*} context The parent component under which the controls menu will
   *     be displayed.
   * @return {ItemList}
   * @protected
   */
  moderationControls(discussion) {
    const items = new ItemList();

    if (discussion.canRename()) {
      items.add(
        'rename',
        Button.component(
          {
            icon: 'fas fa-pencil-alt',
            onclick: this.renameAction.bind(discussion),
          },
          app.translator.trans('core.forum.discussion_controls.rename_button')
        )
      );
    }

    return items;
  },

  /**
   * Get controls for a discussion which are destructive (e.g. delete).
   *
   * @param {Discussion} discussion
   * @param {*} context The parent component under which the controls menu will
   *     be displayed.
   * @return {ItemList}
   * @protected
   */
  destructiveControls(discussion) {
    const items = new ItemList();

    if (!discussion.isHidden()) {
      if (discussion.canHide()) {
        items.add(
          'hide',
          Button.component(
            {
              icon: 'far fa-trash-alt',
              onclick: this.hideAction.bind(discussion),
            },
            app.translator.trans('core.forum.discussion_controls.delete_button')
          )
        );
      }
    } else {
      if (discussion.canHide()) {
        items.add(
          'restore',
          Button.component(
            {
              icon: 'fas fa-reply',
              onclick: this.restoreAction.bind(discussion),
            },
            app.translator.trans('core.forum.discussion_controls.restore_button')
          )
        );
      }

      if (discussion.canDelete()) {
        items.add(
          'delete',
          Button.component(
            {
              icon: 'fas fa-times',
              onclick: this.deleteAction.bind(discussion),
            },
            app.translator.trans('core.forum.discussion_controls.delete_forever_button')
          )
        );
      }
    }

    return items;
  },

  /**
   * Open the reply composer for the discussion. A promise will be returned,
   * which resolves when the composer opens successfully. If the user is not
   * logged in, they will be prompted. If they don't have permission to
   * reply, the promise will be rejected.
   *
   * @param {Boolean} goToLast Whether or not to scroll down to the last post if
   *     the discussion is being viewed.
   * @param {Boolean} forceRefresh Whether or not to force a reload of the
   *     composer component, even if it is already open for this discussion.
   * @return {Promise}
   */
  replyAction(goToLast, forceRefresh) {
    return new Promise((resolve, reject) => {
      if (app.session.user) {
        if (this.canReply()) {
          if (!app.composer.composingReplyTo(this) || forceRefresh) {
            app.composer.load(ReplyComposer, {
              user: app.session.user,
              discussion: this,
            });
          }
          app.composer.show();

          if (goToLast && app.viewingDiscussion(this) && !app.composer.isFullScreen()) {
            app.current.get('stream').goToNumber('reply');
          }

          return resolve(app.composer);
        } else {
          return reject();
        }
      }

      app.modal.show(LogInModal);

      return reject();
    });
  },

  /**
   * Hide a discussion.
   *
   * @return {Promise}
   */
  hideAction() {
    this.pushAttributes({ hiddenAt: new Date(), hiddenUser: app.session.user });

    return this.save({ isHidden: true });
  },

  /**
   * Restore a discussion.
   *
   * @return {Promise}
   */
  restoreAction() {
    this.pushAttributes({ hiddenAt: null, hiddenUser: null });

    return this.save({ isHidden: false });
  },

  /**
   * Delete the discussion after confirming with the user.
   *
   * @return {Promise}
   */
  deleteAction() {
    if (confirm(extractText(app.translator.trans('core.forum.discussion_controls.delete_confirmation')))) {
      // If we're currently viewing the discussion that was deleted, go back
      // to the previous page.
      if (app.viewingDiscussion(this)) {
        app.history.back();
      }

      return this.delete().then(() => app.discussions.removeDiscussion(this));
    }
  },

  /**
   * Rename the discussion.
   *
   * @return {Promise}
   */
  renameAction() {
    return app.modal.show(RenameDiscussionModal, {
      currentTitle: this.title(),
      discussion: this,
    });
  },
};

export default DiscussionControls;
