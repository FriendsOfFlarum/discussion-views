import app from 'flarum/admin/app';
import Extend from 'flarum/common/extenders';
import commonExtend from '../common/extend';

export default [
  ...commonExtend,

  new Extend.Admin()
    .permission(
      () => ({
        icon: 'far fa-eye',
        label: app.translator.trans('fof-discussion-views.admin.permissions.reset_views_label'),
        permission: 'discussion.resetViews',
      }),
      'moderate'
    )
    .setting(() => ({
      setting: 'fsdv.ignore-crawlers',
      type: 'boolean',
      label: app.translator.trans('fof-discussion-views.admin.settings.ignore_crawlers'),
    }))
    .setting(() => ({
      setting: 'fsdv.dedupe-ttl',
      type: 'number',
      min: 0,
      label: app.translator.trans('fof-discussion-views.admin.settings.dedupe_ttl'),
      help: app.translator.trans('fof-discussion-views.admin.settings.dedupe_ttl_help'),
    })),
];
