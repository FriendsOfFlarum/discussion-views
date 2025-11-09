import Discussion from 'flarum/common/models/Discussion';
import commonExtend from '../common/extend';
import Extend from 'flarum/common/extenders';

export default [
  ...commonExtend,

  new Extend.Model(Discussion) //
    .attribute<boolean>('canReset')
    .attribute<number>('views'),
];
