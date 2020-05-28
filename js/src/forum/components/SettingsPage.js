import UserPage from './UserPage';
import ItemList from '../../common/utils/ItemList';
import Switch from '../../common/components/Switch';
import Button from '../../common/components/Button';
import FieldSet from '../../common/components/FieldSet';
import NotificationGrid from './NotificationGrid';
import SsoGrid from './SsoGrid';
import ChangePasswordModal from './ChangePasswordModal';
import ChangeEmailModal from './ChangeEmailModal';
import listItems from '../../common/helpers/listItems';

/**
 * The `SettingsPage` component displays the user's settings control panel, in
 * the context of their user profile.
 */
export default class SettingsPage extends UserPage {
  init() {
    super.init();

    this.show(app.session.user);
    app.setTitle(app.translator.trans('core.forum.settings.title'));
  }

  content() {
    return (
      <div className="SettingsPage">
        <ul>{listItems(this.settingsItems().toArray())}</ul>
      </div>
    );
  }

  /**
   * Build an item list for the user's settings controls.
   *
   * @return {ItemList}
   */
  settingsItems() {
    const items = new ItemList();

    items.add(
      'account',
      FieldSet.component({
        label: app.translator.trans('core.forum.settings.account_heading'),
        className: 'Settings-account',
        children: this.accountItems().toArray(),
      })
    );

    if (Object.keys(app.forum.data.attributes.ssoProviders).length > 0) {
      items.add(
        'sso',
        FieldSet.component({
          label: app.translator.trans('core.forum.settings.sso_heading'),
          className: 'Settings-sso',
          children: this.ssoItems().toArray(),
        })
      );
    }

    items.add(
      'notifications',
      FieldSet.component({
        label: app.translator.trans('core.forum.settings.notifications_heading'),
        className: 'Settings-notifications',
        children: this.notificationsItems().toArray(),
      })
    );

    items.add(
      'privacy',
      FieldSet.component({
        label: app.translator.trans('core.forum.settings.privacy_heading'),
        className: 'Settings-privacy',
        children: this.privacyItems().toArray(),
      })
    );

    return items;
  }

  /**
   * Build an item list for the user's account settings.
   *
   * @return {ItemList}
   */
  accountItems() {
    const items = new ItemList();

    if (app.forum.attribute('enablePasswordAuth')) {
      items.add(
        'changePassword',
        Button.component({
          children: app.translator.trans('core.forum.settings.change_password_button'),
          className: 'Button',
          onclick: () => app.modal.show(new ChangePasswordModal()),
        })
      );
    }

    items.add(
      'changeEmail',
      Button.component({
        children: app.translator.trans('core.forum.settings.change_email_button'),
        className: 'Button',
        onclick: () => app.modal.show(new ChangeEmailModal()),
      })
    );

    return items;
  }

  ssoItems() {
    const items = new ItemList();

    items.add('ssoGrid', SsoGrid.component());

    return items;
  }

  /**
   * Build an item list for the user's notification settings.
   *
   * @return {ItemList}
   */
  notificationsItems() {
    const items = new ItemList();

    items.add('notificationGrid', NotificationGrid.component({ user: this.user }));

    return items;
  }

  /**
   * Generate a callback that will save a value to the given preference.
   *
   * @param {String} key
   * @return {Function}
   */
  preferenceSaver(key) {
    return (value, component) => {
      if (component) component.loading = true;
      m.redraw();

      this.user.savePreferences({ [key]: value }).then(() => {
        if (component) component.loading = false;
        m.redraw();
      });
    };
  }

  /**
   * Build an item list for the user's privacy settings.
   *
   * @return {ItemList}
   */
  privacyItems() {
    const items = new ItemList();

    items.add(
      'discloseOnline',
      Switch.component({
        children: app.translator.trans('core.forum.settings.privacy_disclose_online_label'),
        state: this.user.preferences().discloseOnline,
        onchange: (value, component) => {
          this.user.pushAttributes({ lastSeenAt: null });
          this.preferenceSaver('discloseOnline')(value, component);
        },
      })
    );

    return items;
  }
}
