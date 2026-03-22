import FormModal from 'flarum/common/components/FormModal';
import type Discussion from 'flarum/common/models/Discussion';
import GlobalSearchState from 'flarum/forum/states/GlobalSearchState';
import { type IInternalModalAttrs } from 'flarum/common/components/Modal';
import Stream from 'flarum/common/utils/Stream';
export interface MovePostsModalAttrs extends IInternalModalAttrs {
    discussion: Discussion;
    postIds: string[];
}
export interface MovePostsResponse {
    status: string;
    postCount: number;
    firstMovedPostNumber: number;
    sourceDiscussionId: string;
    targetDiscussionId: string;
}
export default class MovePostsModal extends FormModal<MovePostsModalAttrs> {
    isLoading: Stream<'check' | 'submit' | boolean>;
    newDiscussion: Stream<boolean>;
    newDiscussionTitle: Stream<string>;
    targetDiscussionId: Stream<string>;
    search: GlobalSearchState;
    className(): string;
    title(): string | any[];
    content(): JSX.Element;
    canSubmit(): any;
    data(): Record<string, unknown>;
    emulate(): void;
    onsubmit(event: SubmitEvent | null, emulate?: boolean): Promise<MovePostsResponse | undefined>;
}
