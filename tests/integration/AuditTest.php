<?php

/*
 * This file is part of fof/move-posts.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\MovePosts\tests\integration;

use Flarum\Audit\AuditLog;
use Flarum\Audit\AuditLogger;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class AuditTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        // Lifecycle events fired outside the test transaction shouldn't create stray entries.
        AuditLogger::$testMode = true;

        $this->extension('flarum-audit', 'fof-move-posts');

        $this->prepareDatabase([
            'audit_log' => [],
            User::class => [
                ['id' => 1, 'username' => 'Muralf', 'email' => 'muralf@machine.local', 'is_email_confirmed' => 1],
                ['id' => 2, 'username' => 'Potato', 'email' => 'potato@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => '2021-08-04 23:01:25', 'last_posted_at' => '2021-08-04 23:01:25', 'user_id' => 1, 'first_post_id' => 1, 'last_post_id' => 5, 'last_post_number' => 5, 'comment_count' => 5],
                ['id' => 2, 'title' => __CLASS__, 'created_at' => '2021-08-01 13:00:00', 'last_posted_at' => '2021-08-05 15:30:00', 'user_id' => 2, 'first_post_id' => 6, 'last_post_id' => 13, 'last_post_number' => 8, 'comment_count' => 8],
            ],
            Post::class => [
                ['id' => 1, 'created_at' => '2021-08-01 12:00:00', 'number' => 1, 'content' => '<t>potato</t>', 'user_id' => 1, 'discussion_id' => 1, 'type' => 'comment'],
                ['id' => 2, 'created_at' => '2021-08-01 18:43:00', 'number' => 2, 'content' => '<t>potato</t>', 'user_id' => 1, 'discussion_id' => 1, 'type' => 'comment'],
                ['id' => 3, 'created_at' => '2021-08-02 08:26:00', 'number' => 3, 'content' => '<t>potato</t>', 'user_id' => 1, 'discussion_id' => 1, 'type' => 'comment'],
                ['id' => 4, 'created_at' => '2021-08-03 15:23:52', 'number' => 4, 'content' => '<t>potato</t>', 'user_id' => 1, 'discussion_id' => 1, 'type' => 'comment'],
                ['id' => 5, 'created_at' => '2021-08-04 23:01:25', 'number' => 5, 'content' => '<t>potato</t>', 'user_id' => 1, 'discussion_id' => 1, 'type' => 'comment'],

                ['id' => 6, 'created_at' => '2021-08-05 00:00:00', 'number' => 1, 'content' => '<t>potato</t>', 'user_id' => 2, 'discussion_id' => 2, 'type' => 'comment'],
                ['id' => 7, 'created_at' => '2021-08-05 01:00:00', 'number' => 2, 'content' => '<t>potato</t>', 'user_id' => 2, 'discussion_id' => 2, 'type' => 'comment'],
                ['id' => 8, 'created_at' => '2021-08-05 02:00:00', 'number' => 3, 'content' => '<t>potato</t>', 'user_id' => 2, 'discussion_id' => 2, 'type' => 'comment'],
                ['id' => 9, 'created_at' => '2021-08-05 03:30:00', 'number' => 4, 'content' => '<t>potato</t>', 'user_id' => 2, 'discussion_id' => 2, 'type' => 'comment'],
                ['id' => 10, 'created_at' => '2021-08-05 05:30:00', 'number' => 5, 'content' => '<t>potato</t>', 'user_id' => 1, 'discussion_id' => 2, 'type' => 'comment'],
                ['id' => 11, 'created_at' => '2021-08-05 08:30:00', 'number' => 6, 'content' => '<t>potato</t>', 'user_id' => 1, 'discussion_id' => 2, 'type' => 'comment'],
                ['id' => 12, 'created_at' => '2021-08-05 10:30:00', 'number' => 7, 'content' => '<t>potato</t>', 'user_id' => 1, 'discussion_id' => 2, 'type' => 'comment'],
                ['id' => 13, 'created_at' => '2021-08-05 15:30:00', 'number' => 8, 'content' => '<t>potato</t>', 'user_id' => 1, 'discussion_id' => 2, 'type' => 'comment'],
            ],
        ]);
    }

    #[Test]
    public function moving_posts_to_an_existing_discussion_is_logged()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts/move', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'postIds' => [10, 11, 12, 13],
                        'sourceDiscussionId' => 2,
                        'targetDiscussionId' => 1,
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody()->getContents());

        $log = AuditLog::query()->where('action', 'posts.moved')->first();

        $this->assertNotNull($log, 'Moving posts should be audit logged');
        $this->assertEquals(1, $log->actor_id, 'The acting user should be recorded');
        $this->assertEquals([
            'discussion_id' => 2,
            'new_discussion_id' => 1,
            'post_count' => 4,
        ], $log->payload);

        $this->assertEquals(0, AuditLog::query()->where('action', 'posts.moved_to_new_discussion')->count(), 'A move to an existing discussion should not log the new-discussion action');
    }

    #[Test]
    public function moving_posts_to_a_new_discussion_is_logged()
    {
        $response = $this->send(
            $this->request('POST', '/api/posts/move', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'newDiscussion' => true,
                        'newDiscussionTitle' => 'Potato will take over the world',
                        'postIds' => [10, 11, 12, 13],
                        'sourceDiscussionId' => 2,
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody()->getContents());

        // A move that creates its target is logged as a single `posts.moved_to_new_discussion`
        // entry — not also as `posts.moved` — so one action produces one audit entry.
        $log = AuditLog::query()->where('action', 'posts.moved_to_new_discussion')->first();

        $this->assertNotNull($log, 'Moving posts into a new discussion should be audit logged');
        $this->assertEquals(1, $log->actor_id, 'The acting user should be recorded');
        $this->assertEquals(2, $log->payload['discussion_id'], 'The source discussion should be recorded');
        $this->assertArrayHasKey('new_discussion_id', $log->payload, 'The created target discussion should be recorded');
        $this->assertEquals(4, $log->payload['post_count']);

        $this->assertEquals(0, AuditLog::query()->where('action', 'posts.moved')->count(), 'A new-discussion move should not also log posts.moved');
        $this->assertEquals(1, AuditLog::query()->count(), 'A new-discussion move should produce exactly one audit entry');
    }
}
