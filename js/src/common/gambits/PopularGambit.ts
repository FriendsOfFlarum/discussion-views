import app from 'flarum/common/app';
import { BooleanGambit } from 'flarum/common/query/IGambit';

export default class PopularGambit extends BooleanGambit {
  key() {
    return app.translator.trans('fof-discussion-views.lib.gambits.popular.key', {}, true);
  }

  filterKey() {
    return 'popular';
  }
}
