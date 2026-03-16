import Notification from 'flarum/forum/components/Notification';
export interface PostMovedNotificationContent {
    targetDiscussionId: number | string;
    targetDiscussionTitle: string;
}
export default class PostMovedNotification extends Notification {
    icon(): string;
    href(): string;
    content(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    excerpt(): null;
}
