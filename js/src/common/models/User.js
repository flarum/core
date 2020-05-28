/*global ColorThief*/

import Model from '../Model';
import stringToColor from '../utils/stringToColor';
import ItemList from '../utils/ItemList';
import computed from '../utils/computed';
import GroupBadge from '../components/GroupBadge';

export default class User extends Model {}

Object.assign(User.prototype, {
  username: Model.attribute('username'),
  displayName: Model.attribute('displayName'),
  email: Model.attribute('email'),
  isEmailConfirmed: Model.attribute('isEmailConfirmed'),
  password: Model.attribute('password'),

  avatarUrl: Model.attribute('avatarUrl'),
  preferences: Model.attribute('preferences'),
  ssoProviders: Model.attribute('ssoProviders'),
  groups: Model.hasMany('groups'),

  joinTime: Model.attribute('joinTime', Model.transformDate),
  lastSeenAt: Model.attribute('lastSeenAt', Model.transformDate),
  markedAllAsReadAt: Model.attribute('markedAllAsReadAt', Model.transformDate),
  unreadNotificationCount: Model.attribute('unreadNotificationCount'),
  newNotificationCount: Model.attribute('newNotificationCount'),

  discussionCount: Model.attribute('discussionCount'),
  commentCount: Model.attribute('commentCount'),

  canEdit: Model.attribute('canEdit'),
  canDelete: Model.attribute('canDelete'),

  avatarColor: null,
  color: computed('username', 'avatarUrl', 'avatarColor', function (username, avatarUrl, avatarColor) {
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
  }),

  /**
   * Check whether or not the user has been seen in the last 5 minutes.
   *
   * @return {Boolean}
   * @public
   */
  isOnline() {
    return this.lastSeenAt() > moment().subtract(5, 'minutes').toDate();
  },

  /**
   * Get the Badge components that apply to this user.
   *
   * @return {ItemList}
   */
  badges() {
    const items = new ItemList();
    const groups = this.groups();

    if (groups) {
      groups.forEach((group) => {
        items.add('group' + group.id(), GroupBadge.component({ group }));
      });
    }

    return items;
  },

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
  },

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
  },
});
