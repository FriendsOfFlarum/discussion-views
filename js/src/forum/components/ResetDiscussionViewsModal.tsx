import app from 'flarum/forum/app';
import FormModal, { IFormModalAttrs } from 'flarum/common/components/FormModal';
import Button from 'flarum/common/components/Button';
import Stream from 'flarum/common/utils/Stream';
import type Discussion from 'flarum/common/models/Discussion';
import type Mithril from 'mithril';

export interface IResetDiscussionViewsModalAttrs extends IFormModalAttrs {
  discussion: Discussion;
}

export default class ResetDiscussionViewsModal extends FormModal<IResetDiscussionViewsModalAttrs> {
  discussion!: Discussion;
  currentViewsCount!: number;
  newViewsCount!: Stream<string>;

  oninit(vnode: Mithril.Vnode<IResetDiscussionViewsModalAttrs, this>) {
    super.oninit(vnode);

    this.discussion = this.attrs.discussion;
    this.currentViewsCount = this.attrs.discussion.views() || 0;
    this.newViewsCount = Stream(this.currentViewsCount.toString());
  }

  className(): string {
    return 'Modal--small ResetDiscussionViewsModal';
  }

  title(): Mithril.Children {
    return app.translator.trans('fof-discussion-views.forum.modal_resetviews.title');
  }

  content(): Mithril.Children {
    return (
      <div className="Modal-body">
        <div className="Form Form--centered">
          <div className="Form-group">
            <label>{app.translator.trans('fof-discussion-views.forum.modal_resetviews.label')}</label>
            <input className="FormControl" type="number" min="0" bidi={this.newViewsCount} />
          </div>
          <div className="Form-group">
            <Button className="Button Button--primary" type="submit" loading={this.loading}>
              {app.translator.trans('fof-discussion-views.forum.modal_resetviews.submit')}
            </Button>
          </div>
        </div>
      </div>
    );
  }

  onsubmit(e: SubmitEvent): void {
    e.preventDefault();
    this.loading = true;

    const newViews = parseInt(this.newViewsCount());
    const currentViews = this.currentViewsCount;

    if (isNaN(newViews) || newViews < 0) {
      this.loading = false;
      app.alerts.show({ type: 'error' }, app.translator.trans('fof-discussion-views.forum.modal_resetviews.invalid'));
      return;
    }

    if (newViews === currentViews) {
      this.hide();
      return;
    }

    this.discussion
      .save({ views: newViews })
      .then(() => {
        this.hide();
        m.redraw();
      })
      .catch(() => {
        this.loading = false;
        m.redraw();
      });
  }
}
