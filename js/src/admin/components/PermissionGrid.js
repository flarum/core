import Component from '../../common/Component';
import PermissionDropdown from './PermissionDropdown';
import SettingDropdown from './SettingDropdown';
import Button from '../../common/components/Button';
import ItemList from '../../common/utils/ItemList';
import icon from '../../common/helpers/icon';

export default class PermissionGrid extends Component {
  view() {
    const scopes = this.scopeItems().toArray();

    const permissionCells = (permission) => {
      return scopes.map((scope) => <td>{scope.render(permission)}</td>);
    };

    return (
      <table className="PermissionGrid">
        <thead>
          <tr>
            <th></th>
            {scopes.map((scope) => (
              <th>
                {scope.label}{' '}
                {scope.onremove
                  ? Button.component({ icon: 'fas fa-times', className: 'Button Button--text PermissionGrid-removeScope', onclick: scope.onremove })
                  : ''}
              </th>
            ))}
            <th>{this.scopeControlItems().toArray()}</th>
          </tr>
        </thead>
        {this.permissionItems()
          .toArray()
          .map((section) => (
            <tbody>
              <tr className="PermissionGrid-section">
                <th>{section.label}</th>
                {permissionCells(section)}
                <td />
              </tr>
              {section.children.map((child) => (
                <tr className="PermissionGrid-child">
                  <th>
                    {icon(child.icon)}
                    {child.label}
                  </th>
                  {permissionCells(child)}
                  <td />
                </tr>
              ))}
            </tbody>
          ))}
      </table>
    );
  }

  permissionItems() {
    const items = new ItemList();

    items.add(
      'view',
      {
        label: app.translator.trans('core.admin.permissions.read_heading'),
        children: this.viewItems().toArray(),
      },
      100
    );

    items.add(
      'start',
      {
        label: app.translator.trans('core.admin.permissions.create_heading'),
        children: this.startItems().toArray(),
      },
      90
    );

    items.add(
      'reply',
      {
        label: app.translator.trans('core.admin.permissions.participate_heading'),
        children: this.replyItems().toArray(),
      },
      80
    );

    items.add(
      'moderate',
      {
        label: app.translator.trans('core.admin.permissions.moderate_heading'),
        children: this.moderateItems().toArray(),
      },
      70
    );

    return items;
  }

  viewItems() {
    const items = new ItemList();

    items.add(
      'viewForum',
      {
        icon: 'fas fa-eye',
        label: app.translator.trans('core.admin.permissions.view_forum_label'),
        permission: 'viewForum',
        allowGuest: true,
      },
      100
    );

    items.add(
      'viewHiddenGroups',
      {
        icon: 'fas fa-users',
        label: app.translator.trans('core.admin.permissions.view_hidden_groups_label'),
        permission: 'viewHiddenGroups',
      },
      100
    );

    items.add(
      'searchUsers',
      {
        icon: 'fas fa-users',
        label: app.translator.trans('core.admin.permissions.search_users_label'),
        permission: 'searchUsers',
        allowGuest: true,
      },
      100
    );

    items.add(
      'signUp',
      {
        icon: 'fas fa-user-plus',
        label: app.translator.trans('core.admin.permissions.sign_up_label'),
        setting: () =>
          SettingDropdown.component({
            key: 'allow_sign_up',
            options: [
              { value: '1', label: app.translator.trans('core.admin.permissions_controls.signup_open_button') },
              { value: '0', label: app.translator.trans('core.admin.permissions_controls.signup_closed_button') },
            ],
          }),
      },
      90
    );

    items.add('viewLastSeenAt', {
      icon: 'far fa-clock',
      label: app.translator.trans('core.admin.permissions.view_last_seen_at_label'),
      permission: 'user.viewLastSeenAt',
    });

    items.merge(app.extensionData.getAllExtensionPermissions('view'));

    return items;
  }

  startItems() {
    const items = new ItemList();

    items.add(
      'start',
      {
        icon: 'fas fa-edit',
        label: app.translator.trans('core.admin.permissions.start_discussions_label'),
        permission: 'startDiscussion',
      },
      100
    );

    items.add(
      'allowRenaming',
      {
        icon: 'fas fa-i-cursor',
        label: app.translator.trans('core.admin.permissions.allow_renaming_label'),
        setting: () => {
          const minutes = parseInt(app.data.settings.allow_renaming, 10);

          return SettingDropdown.component({
            defaultLabel: minutes
              ? app.translator.trans('core.admin.permissions_controls.allow_some_minutes_button', { count: minutes })
              : app.translator.trans('core.admin.permissions_controls.allow_indefinitely_button'),
            key: 'allow_renaming',
            options: [
              { value: '-1', label: app.translator.trans('core.admin.permissions_controls.allow_indefinitely_button') },
              { value: '10', label: app.translator.trans('core.admin.permissions_controls.allow_ten_minutes_button') },
              { value: 'reply', label: app.translator.trans('core.admin.permissions_controls.allow_until_reply_button') },
            ],
          });
        },
      },
      90
    );

    items.merge(app.extensionData.getAllExtensionPermissions('start'));

    return items;
  }

  replyItems() {
    const items = new ItemList();

    items.add(
      'reply',
      {
        icon: 'fas fa-reply',
        label: app.translator.trans('core.admin.permissions.reply_to_discussions_label'),
        permission: 'discussion.reply',
      },
      100
    );

    items.add(
      'allowPostEditing',
      {
        icon: 'fas fa-pencil-alt',
        label: app.translator.trans('core.admin.permissions.allow_post_editing_label'),
        setting: () => {
          const minutes = parseInt(app.data.settings.allow_post_editing, 10);

          return SettingDropdown.component({
            defaultLabel: minutes
              ? app.translator.trans('core.admin.permissions_controls.allow_some_minutes_button', { count: minutes })
              : app.translator.trans('core.admin.permissions_controls.allow_indefinitely_button'),
            key: 'allow_post_editing',
            options: [
              { value: '-1', label: app.translator.trans('core.admin.permissions_controls.allow_indefinitely_button') },
              { value: '10', label: app.translator.trans('core.admin.permissions_controls.allow_ten_minutes_button') },
              { value: 'reply', label: app.translator.trans('core.admin.permissions_controls.allow_until_reply_button') },
            ],
          });
        },
      },
      90
    );

    items.merge(app.extensionData.getAllExtensionPermissions('reply'));

    return items;
  }

  moderateItems() {
    const items = new ItemList();

    items.add(
      'viewIpsPosts',
      {
        icon: 'fas fa-bullseye',
        label: app.translator.trans('core.admin.permissions.view_post_ips_label'),
        permission: 'discussion.viewIpsPosts',
      },
      110
    );

    items.add(
      'renameDiscussions',
      {
        icon: 'fas fa-i-cursor',
        label: app.translator.trans('core.admin.permissions.rename_discussions_label'),
        permission: 'discussion.rename',
      },
      100
    );

    items.add(
      'hideDiscussions',
      {
        icon: 'far fa-trash-alt',
        label: app.translator.trans('core.admin.permissions.delete_discussions_label'),
        permission: 'discussion.hide',
      },
      90
    );

    items.add(
      'deleteDiscussions',
      {
        icon: 'fas fa-times',
        label: app.translator.trans('core.admin.permissions.delete_discussions_forever_label'),
        permission: 'discussion.delete',
      },
      80
    );

    items.add(
      'postWithoutThrottle',
      {
        icon: 'fas fa-swimmer',
        label: app.translator.trans('core.admin.permissions.post_without_throttle_label'),
        permission: 'postWithoutThrottle',
      },
      70
    );

    items.add(
      'editPosts',
      {
        icon: 'fas fa-pencil-alt',
        label: app.translator.trans('core.admin.permissions.edit_posts_label'),
        permission: 'discussion.editPosts',
      },
      70
    );

    items.add(
      'hidePosts',
      {
        icon: 'far fa-trash-alt',
        label: app.translator.trans('core.admin.permissions.delete_posts_label'),
        permission: 'discussion.hidePosts',
      },
      60
    );

    items.add(
      'deletePosts',
      {
        icon: 'fas fa-times',
        label: app.translator.trans('core.admin.permissions.delete_posts_forever_label'),
        permission: 'discussion.deletePosts',
      },
      60
    );

    items.add(
      'userEditCredentials',
      {
        icon: 'fas fa-user-cog',
        label: app.translator.trans('core.admin.permissions.edit_users_credentials_label'),
        permission: 'user.editCredentials',
      },
      60
    );

    items.add(
      'userEditGroups',
      {
        icon: 'fas fa-users-cog',
        label: app.translator.trans('core.admin.permissions.edit_users_groups_label'),
        permission: 'user.editGroups',
      },
      60
    );

    items.add(
      'userEdit',
      {
        icon: 'fas fa-address-card',
        label: app.translator.trans('core.admin.permissions.edit_users_label'),
        permission: 'user.edit',
      },
      60
    );

    items.merge(app.extensionData.getAllExtensionPermissions('moderate'));

    return items;
  }

  scopeItems() {
    const items = new ItemList();

    items.add(
      'global',
      {
        label: app.translator.trans('core.admin.permissions.global_heading'),
        render: (item) => {
          if (item.setting) {
            return item.setting();
          } else if (item.permission) {
            return PermissionDropdown.component({
              permission: item.permission,
              allowGuest: item.allowGuest,
            });
          }

          return '';
        },
      },
      100
    );

    return items;
  }

  scopeControlItems() {
    return new ItemList();
  }
}
