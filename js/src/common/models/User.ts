/*global ColorThief*/

import Model from '../Model';
import stringToColor from '../utils/stringToColor';
import ItemList from '../utils/ItemList';
import computed from '../utils/computed';
import GroupBadge from '../components/GroupBadge';
import Group from './Group';

export default class User extends Model {
  username = Model.attribute<string>('username');
  displayName = Model.attribute<string>('displayName');
  email = Model.attribute<string>('email');
  isEmailConfirmed = Model.attribute<boolean>('isEmailConfirmed');
  password = Model.attribute<string>('password');

  avatarUrl = Model.attribute<string>('avatarUrl');
  preferences = Model.attribute<any>('preferences');
  groups = Model.hasMany<Group>('groups');

  joinTime = Model.attribute<Date>('joinTime', Model.transformDate);
  lastSeenAt = Model.attribute<Date>('lastSeenAt', Model.transformDate);
  markedAllAsReadAt = Model.attribute<Date>('markedAllAsReadAt', Model.transformDate);
  unreadNotificationCount = Model.attribute<number>('unreadNotificationCount');
  newNotificationCount = Model.attribute<number>('newNotificationCount');

  discussionCount = Model.attribute<number>('discussionCount');
  commentCount = Model.attribute<number>('commentCount');

  canEdit = Model.attribute<boolean>('canEdit');
  canDelete = Model.attribute<boolean>('canDelete');

  avatarColor = null;
  color = computed<string>('username', 'avatarUrl', 'avatarColor', (username, avatarUrl, avatarColor) => {
    // If we've already calculated and cached the dominant color of the user's
    // avatar, then we can return that in RGB format. If we haven't, we'll want
    // to calculate it. Unless the user doesn't have an avatar, in which case
    // we generate a color from their username.
    if (avatarColor) {
      return 'rgb(' + avatarColor.join(', ') + ')';
    } else if (avatarUrl) {
      this.calculateAvatarColor();
      return '';
    }

    return '#' + stringToColor(username);
  });

  /**
   * Check whether or not the user has been seen in the last 5 minutes.
   *
   * @return {Boolean}
   * @public
   */
  isOnline(): boolean {
    return dayjs().subtract(5, 'minutes').isBefore(this.lastSeenAt());
  }

  /**
   * Get the Badge components that apply to this user.
   *
   * @return {ItemList}
   */
  badges(): ItemList {
    const items = new ItemList();
    const groups = this.groups();

    if (groups) {
      groups.forEach((group) => {
        items.add('group' + group.id(), GroupBadge.component({ group }));
      });
    }

    return items;
  }

  /**
   * Calculate the dominant color of the user's avatar. The dominant color will
   * be set to the `avatarColor` property once it has been calculated.
   *
   * @protected
   */
  calculateAvatarColor() {
    const image = new Image();
    const user = this;

    image.onload = function () {
      const colorThief = new ColorThief();
      user.avatarColor = colorThief.getColor(this);
      user.freshness = new Date();
      m.redraw();
    };
    image.crossOrigin = 'anonymous';
    image.src = this.avatarUrl();
  }

  /**
   * Update the user's preferences.
   *
   * @param {Object} newPreferences
   * @return {Promise}
   */
  savePreferences(newPreferences) {
    const preferences = this.preferences();

    Object.assign(preferences, newPreferences);

    return this.save({ preferences });
  }
}
