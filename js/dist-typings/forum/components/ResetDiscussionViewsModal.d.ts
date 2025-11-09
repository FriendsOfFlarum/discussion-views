import FormModal, { IFormModalAttrs } from 'flarum/common/components/FormModal';
import Stream from 'flarum/common/utils/Stream';
import type Discussion from 'flarum/common/models/Discussion';
import type Mithril from 'mithril';
export interface IResetDiscussionViewsModalAttrs extends IFormModalAttrs {
    discussion: Discussion;
}
export default class ResetDiscussionViewsModal extends FormModal<IResetDiscussionViewsModalAttrs> {
    discussion: Discussion;
    currentViewsCount: number;
    newViewsCount: Stream<string>;
    oninit(vnode: Mithril.Vnode<IResetDiscussionViewsModalAttrs, this>): void;
    className(): string;
    title(): Mithril.Children;
    content(): Mithril.Children;
    onsubmit(e: SubmitEvent): void;
}
