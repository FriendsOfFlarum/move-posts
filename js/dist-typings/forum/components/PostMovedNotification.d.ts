import Notification from 'flarum/forum/components/Notification';
export interface PostMovedNotificationContent {
    targetDiscussionId: number | string;
    targetDiscussionTitle: string;
}
export default class PostMovedNotification extends Notification {
    icon(): string;
    href(): string;
    content(): any[];
    excerpt(): null;
}
