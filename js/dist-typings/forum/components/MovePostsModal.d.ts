import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import type Discussion from 'flarum/common/models/Discussion';
import GlobalSearchState from 'flarum/forum/states/GlobalSearchState';
export interface MovePostsModalAttrs extends IInternalModalAttrs {
    discussion: Discussion;
    postIds: string[];
}
export default class MovePostsModa extends Modal<MovePostsModalAttrs> {
    isLoading: string | boolean;
    newDiscussion: boolean;
    newDiscussionTitle: string;
    targetDiscussionId?: string;
    search: GlobalSearchState;
    className(): string;
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    content(): JSX.Element;
    data(): Record<string, unknown>;
    emulate(): void;
    onsubmit(e: any, emulate: boolean): Promise<any>;
}
