import { extend } from 'flarum/common/extend';
import DiscussionListState from 'flarum/forum/states/DiscussionListState';

export default function () {
  extend(DiscussionListState.prototype, 'sortMap', function (map: any) {
    map.most_viewed = '-view_count';
    map.least_viewed = 'view_count';
  });
}
