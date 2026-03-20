import FormModal from 'flarum/common/components/FormModal';
import type Discussion from 'flarum/common/models/Discussion';
import GlobalSearchState from 'flarum/forum/states/GlobalSearchState';
import { IInternalModalAttrs } from 'flarum/common/components/Modal';
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
    isLoading: 'check' | 'submit' | boolean;
    newDiscussion: boolean;
    newDiscussionTitle: string;
    targetDiscussionId?: string;
    search: GlobalSearchState;
    className(): string;
    title(): string | any[];
    content(): JSX.Element;
    data(): Record<string, unknown>;
    emulate(): void;
    onsubmit(event: SubmitEvent | null, emulate?: boolean): Promise<MovePostsResponse>;
}
