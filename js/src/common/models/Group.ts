import Model from '../Model';

export default class Group extends Model {
  static ADMINISTRATOR_ID = '1';
  static GUEST_ID = '2';
  static MEMBER_ID = '3';

  nameSingular = Model.attribute<string>('nameSingular');
  namePlural = Model.attribute<string>('namePlural');
  color = Model.attribute<string>('color');
  icon = Model.attribute<string>('icon');
  isHidden = Model.attribute<boolean>('isHidden');
}
