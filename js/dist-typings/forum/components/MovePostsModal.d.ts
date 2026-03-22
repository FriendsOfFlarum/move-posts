import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import type Discussion from 'flarum/common/models/Discussion';
import GlobalSearchState from 'flarum/forum/states/GlobalSearchState';
import Stream from 'flarum/common/utils/Stream';
export interface MovePostsModalAttrs extends IInternalModalAttrs {
    discussion: Discussion;
    postIds: string[];
}
export interface MovePostsResponse {
    data: {
        id: string;
        type: 'discussions';
        attributes: any;
    };
    meta: {
        status: string;
        postCount: number;
        firstMovedPostNumber: number;
        sourceDiscussionId: string;
        targetDiscussionId: string;
    };
}
export default class MovePostsModal extends Modal<MovePostsModalAttrs> {
    isLoading: Stream<'check' | 'submit' | boolean>;
    newDiscussion: Stream<boolean>;
    newDiscussionTitle: Stream<string>;
    targetDiscussionId: Stream<string>;
    search: GlobalSearchState;
    className(): string;
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    content(): JSX.Element;
    canSubmit(): any;
    data(): Record<string, unknown>;
    emulate(): void;
    onsubmit(event: SubmitEvent | null, emulate?: boolean): Promise<MovePostsResponse | undefined>;
}
