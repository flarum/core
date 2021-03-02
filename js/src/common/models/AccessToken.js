import Model from '../Model';

export default class AccessToken extends Model {
  apiEndpoint() {
    return '/tokens' + (this.exists ? '/' + this.data.id : '');
  }
}

Object.assign(AccessToken.prototype, {
  token: Model.attribute('token'),
  userId: Model.attribute('userId'),
  createdAt: Model.attribute('createdAt', Model.transformDate),
  lastActivityAt: Model.attribute('lastActivityAt', Model.transformDate),
  lifetimeSeconds: Model.attribute('lifetimeSeconds'),
  current: Model.attribute('current'),
  title: Model.attribute('title'),
  lastIpAddress: Model.attribute('lastIpAddress'),
  lastUserAgent: Model.attribute('lastUserAgent'),
});
