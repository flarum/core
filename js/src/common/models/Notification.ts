import Model from '../Model';
import User from './User';

export default class Notification extends Model {
  contentType = Model.attribute<string>('contentType');
  content = Model.attribute<any>('content');
  createdAt = Model.attribute<Date>('createdAt', Model.transformDate);

  isRead = Model.attribute<boolean>('isRead');

  user = Model.hasOne<User>('user');
  fromUser = Model.hasOne<User>('fromUser');
  subject = Model.hasOne<any>('subject');
}
