<?php

/*
 * This file is part of fof/move-posts.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\MovePosts;

use Flarum\Extend;
use Flarum\Api\Context;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Audit\Extend\Audit;
use Flarum\Discussion\Discussion;

return [
    (new \FoF\UiKit\Extend\Register),

    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->jsDirectory(__DIR__.'/js/dist/forum')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\ApiResource(Resource\DiscussionResource::class))
        ->fields(fn () => [
            Schema\Boolean::make('isFirstMoved'),
        ]),

    (new Extend\ApiResource(Resource\ForumResource::class))
        ->fields(fn () => [
            Schema\Boolean::make('canMovePosts')
                ->get(fn (object $forum, Context $context) => $context->getActor()->can('movePosts'))
        ]),

    (new Extend\Formatter)
        ->render(Formatter\FormatPostMentions::class)
        ->unparse(Formatter\UnparsePostMentions::class),

    (new Extend\Routes('api'))
        ->post('/posts/move', 'move-posts.move', Api\Controller\MovePostsController::class)
        ->post('/posts/move/check', 'move-posts.check', Api\Controller\ShowMovePostsStatusController::class),

    (new Extend\Policy())
        ->globalPolicy(Access\GlobalPolicy::class),

    (new Extend\ErrorHandling())
        ->status('move_old_post_to_newer_discussion', 409)
        ->status('move_posts_from_different_discussions', 409)
        ->status('move_posts_to_same_discussion', 409),

    (new Extend\Model(Discussion::class))
        ->cast('is_first_moved', 'boolean'),

    (new Extend\Post)
        ->type(PostMovedPost::class),

    (new Extend\Notification)
        ->type(Notification\PostMovedBlueprint::class, ['alert']),

    (new Extend\Event)
        ->listen(Event\PostsMoved::class, Listener\SendNotificationsWhenPostsAreMoved::class)
        ->listen(Event\CreatedTargetDiscussion::class, Listener\CopyTagsWhenPostsAreMovedToNewDiscussion::class),

    (new Extend\Conditional())
        ->whenExtensionEnabled('flarum-audit', fn () => [
            // The payload keys `discussion_id` and `new_discussion_id` are recognised by
            // flarum/audit, which resolves them into linked discussions in the audit browser
            // (rendered as the {discussion} and {new_discussion} translation parameters).
            (new Audit())
                ->listen(Event\PostsMoved::class, 'posts.moved', fn (Event\PostsMoved $e) => [
                    'discussion_id' => $e->sourceDiscussion->id,
                    'new_discussion_id' => $e->targetDiscussion->id,
                    'count' => $e->posts->count(),
                ])
                ->listen(Event\CreatedTargetDiscussion::class, 'posts.moved_to_new_discussion', fn (Event\CreatedTargetDiscussion $e) => [
                    'discussion_id' => $e->sourceDiscussion->id,
                    'new_discussion_id' => $e->targetDiscussion->id,
                ]),
        ]),
];
