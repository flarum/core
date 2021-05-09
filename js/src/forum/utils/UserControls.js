import Button from '../../common/components/Button';
import Separator from '../../common/components/Separator';
import EditUserModal from '../../common/components/EditUserModal';
import UserPage from '../components/UserPage';
import ItemList from '../../common/utils/ItemList';

/**
 * The `UserControls` utility constructs a list of buttons for a user which
 * perform actions on it.
 */
const UserControls = {
  /**
   * Get a list of controls for a user.
   *
   * @param {User} user
   * @param {*} context The parent component under which the controls menu will
   *     be displayed.
   * @return {ItemList}
   * @public
   */
  controls(user, context) {
    const items = new ItemList();

    ['user', 'moderation', 'destructive'].forEach((section) => {
      const controls = this[section + 'Controls'](user, context).toArray();
      if (controls.length) {
        controls.forEach((item) => items.add(item.itemName, item));
        items.add(section + 'Separator', <Separator />);
      }
    });

    return items;
  },

  /**
   * Get controls for a user pertaining to the current user (e.g. poke, follow).
   *
   * @param {User} user
   * @param {*} context The parent component under which the controls menu will
   *     be displayed.
   * @return {ItemList}
   * @protected
   */
  userControls() {
    return new ItemList();
  },

  /**
   * Get controls for a user pertaining to moderation (e.g. suspend, edit).
   *
   * @param {User} user
   * @param {*} context The parent component under which the controls menu will
   *     be displayed.
   * @return {ItemList}
   * @protected
   */
  moderationControls(user) {
    const items = new ItemList();

    if (user.canEdit() || user.canEditCredentials() || user.canEditGroups()) {
      items.add(
        'edit',
        <Button icon="fas fa-pencil-alt" onclick={this.editAction.bind(this, user)}>
          {app.translator.trans('core.forum.user_controls.edit_button')}
        </Button>
      );
    }

    return items;
  },

  /**
   * Get controls for a user which are destructive (e.g. delete).
   *
   * @param {User} user
   * @param {*} context The parent component under which the controls menu will
   *     be displayed.
   * @return {ItemList}
   * @protected
   */
  destructiveControls(user) {
    const items = new ItemList();

    if (user.id() !== '1' && user.canDelete()) {
      items.add(
        'delete',
        <Button icon="fas fa-times" onclick={this.deleteAction.bind(this, user)}>
          {app.translator.trans('core.forum.user_controls.delete_button')}
        </Button>
      );
    }

    return items;
  },

  /**
   * Delete the user.
   *
   * @param {User} user
   */
  deleteAction(user) {
    if (!confirm(app.translator.trans('core.forum.user_controls.delete_confirmation'))) {
      return;
    }

    user
      .delete()
      .then(() => {
        this.showDeletionAlert(user, 'success');
        if (app.current.matches(UserPage, { user })) {
          app.history.back();
        } else {
          window.location.reload();
        }
      })
      .catch(() => this.showDeletionAlert(user, 'error'));
  },

  /**
   * Show deletion alert of user.
   *
   * @param {User} user
   * @param {string} type
   */
  showDeletionAlert(user, type) {
    const { username, email } = user.data.attributes;
    const message = {
      success: 'core.forum.user_controls.delete_success_message',
      error: 'core.forum.user_controls.delete_error_message',
    }[type];

    app.alerts.show({ type }, app.translator.trans(message, { username, email }));
  },

  /**
   * Edit the user.
   *
   * @param {User} user
   */
  editAction(user) {
    app.modal.show(EditUserModal, { user });
  },
};

export default UserControls;
