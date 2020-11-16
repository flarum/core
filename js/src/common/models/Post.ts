import Model from '../Model';
import computed from '../utils/computed';
import { getPlainContent } from '../utils/string';
import Discussion from './Discussion';
import User from './User';

export default class Post extends Model {
  number = Model.attribute<number>('number');
  discussion = Model.hasOne<Discussion>('discussion');

  createdAt = Model.attribute<Date>('createdAt', Model.transformDate);
  user = Model.hasOne<User>('user');
  contentType = Model.attribute<string>('contentType');
  content = Model.attribute<string>('content');
  contentHtml = Model.attribute<string>('contentHtml');
  contentPlain = computed<string>('contentHtml', getPlainContent);

  editedAt = Model.attribute<Date>('editedAt', Model.transformDate);
  editedUser = Model.hasOne<User>('editedUser');
  isEdited = computed<boolean>('editedAt', (editedAt) => !!editedAt);

  hiddenAt = Model.attribute<Date>('hiddenAt', Model.transformDate);
  hiddenUser = Model.hasOne<User>('hiddenUser');
  isHidden = computed<boolean>('hiddenAt', (hiddenAt) => !!hiddenAt);

  canEdit = Model.attribute<boolean>('canEdit');
  canHide = Model.attribute<boolean>('canHide');
  canDelete = Model.attribute<boolean>('canDelete');
}
