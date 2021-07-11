import Dropdown from '../../common/components/Dropdown';
import Button from '../../common/components/Button';
import Separator from '../../common/components/Separator';
import Group from '../../common/models/Group';
import Badge from '../../common/components/Badge';
import GroupBadge from '../../common/components/GroupBadge';

function badgeForId(id) {
  const group = app.store.getById('groups', id);

  return group ? GroupBadge.component({ group, label: null }) : '';
}

function filterByRequiredPermissions(groupIds, permission) {
  app.getRequiredPermissions(permission).forEach((required) => {
    const restrictToGroupIds = app.data.permissions[required] || [];

    if (restrictToGroupIds.indexOf(Group.GUEST_ID) !== -1) {
      // do nothing
    } else if (restrictToGroupIds.indexOf(Group.MEMBER_ID) !== -1) {
      groupIds = groupIds.filter((id) => id !== Group.GUEST_ID);
    } else if (groupIds.indexOf(Group.MEMBER_ID) !== -1) {
      groupIds = restrictToGroupIds;
    } else {
      groupIds = restrictToGroupIds.filter((id) => groupIds.indexOf(id) !== -1);
    }

    groupIds = filterByRequiredPermissions(groupIds, required);
  });

  return groupIds;
}

export default class PermissionDropdown extends Dropdown {
  static initAttrs(attrs) {
    super.initAttrs(attrs);

    attrs.className = 'PermissionDropdown';
    attrs.buttonClassName = 'Button Button--text';
    attrs.lazyDraw = true;
  }

  view(vnode) {
    const children = [];

    let groupIds = app.data.permissions[this.attrs.permission] || [];

    groupIds = filterByRequiredPermissions(groupIds, this.attrs.permission);

    const everyone = groupIds.indexOf(Group.GUEST_ID) !== -1;
    const members = groupIds.indexOf(Group.MEMBER_ID) !== -1;
    const adminGroup = app.store.getById('groups', Group.ADMINISTRATOR_ID);

    if (everyone) {
      this.attrs.label = Badge.component({ icon: 'fas fa-globe' });
    } else if (members) {
      this.attrs.label = Badge.component({ icon: 'fas fa-user' });
    } else {
      this.attrs.label = [badgeForId(Group.ADMINISTRATOR_ID), groupIds.map(badgeForId)];
    }

    if (this.showing) {
      if (this.attrs.allowGuest) {
        children.push(
          Button.component(
            {
              icon: everyone ? 'fas fa-check' : true,
              onclick: () => this.save([Group.GUEST_ID]),
              disabled: this.isGroupDisabled(Group.GUEST_ID),
            },
            [Badge.component({ icon: 'fas fa-globe' }), ' ', app.translator.trans('core.admin.permissions_controls.everyone_button')]
          )
        );
      }

      children.push(
        Button.component(
          {
            icon: members ? 'fas fa-check' : true,
            onclick: () => this.save([Group.MEMBER_ID]),
            disabled: this.isGroupDisabled(Group.MEMBER_ID),
          },
          [Badge.component({ icon: 'fas fa-user' }), ' ', app.translator.trans('core.admin.permissions_controls.members_button')]
        ),

        Separator.component(),

        Button.component(
          {
            icon: !everyone && !members ? 'fas fa-check' : true,
            disabled: !everyone && !members,
            onclick: (e) => {
              if (e.shiftKey) e.stopPropagation();
              this.save([]);
            },
          },
          [badgeForId(adminGroup.id()), ' ', adminGroup.namePlural()]
        )
      );

      [].push.apply(
        children,
        app.store
          .all('groups')
          .filter((group) => [Group.ADMINISTRATOR_ID, Group.GUEST_ID, Group.MEMBER_ID].indexOf(group.id()) === -1)
          .map((group) =>
            Button.component(
              {
                icon: groupIds.indexOf(group.id()) !== -1 ? 'fas fa-check' : true,
                onclick: (e) => {
                  if (e.shiftKey) e.stopPropagation();
                  this.toggle(group.id());
                },
                disabled: this.isGroupDisabled(group.id()) && this.isGroupDisabled(Group.MEMBER_ID) && this.isGroupDisabled(Group.GUEST_ID),
              },
              [badgeForId(group.id()), ' ', group.namePlural()]
            )
          )
      );
    }

    return super.view({ ...vnode, children });
  }

  save(groupIds) {
    const permission = this.attrs.permission;

    app.data.permissions[permission] = groupIds;

    app.request({
      method: 'POST',
      url: app.forum.attribute('apiUrl') + '/permission',
      body: { permission, groupIds },
    });
  }

  toggle(groupId) {
    const permission = this.attrs.permission;

    let groupIds = app.data.permissions[permission] || [];

    const index = groupIds.indexOf(groupId);

    if (index !== -1) {
      groupIds.splice(index, 1);
    } else {
      groupIds.push(groupId);
      groupIds = groupIds.filter((id) => [Group.GUEST_ID, Group.MEMBER_ID].indexOf(id) === -1);
    }

    this.save(groupIds);
  }

  isGroupDisabled(id) {
    return filterByRequiredPermissions([id], this.attrs.permission).indexOf(id) === -1;
  }
}
