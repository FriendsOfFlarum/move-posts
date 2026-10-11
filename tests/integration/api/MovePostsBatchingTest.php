<?php

/*
 * This file is part of fof/move-posts.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\MovePosts\tests\integration\api;

use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use FoF\MovePosts\PostMovedPost;
use PHPUnit\Framework\Attributes\Test;

/**
 * Moves enough posts that per-post queries would be reported as an N+1,
 * and pins down the renumbering and event posts those moves produce.
 */
class MovePostsBatchingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-move-posts');

        $posts = [];

        // Target discussion: 12 posts, one an hour from midnight.
        foreach (range(0, 11) as $i) {
            $posts[] = $this->post(101 + $i, 1, $i + 1, sprintf('2021-09-01 %02d:00:00', $i));
        }

        // Source discussion: an older first post, six posts that fall between
        // the target's posts, then six posts later than all of the target's.
        $posts[] = $this->post(201, 2, 1, '2021-08-31 12:00:00');

        foreach (range(0, 5) as $i) {
            $posts[] = $this->post(202 + $i, 2, $i + 2, sprintf('2021-09-01 %02d:30:00', $i * 2));
        }

        foreach (range(0, 5) as $i) {
            $posts[] = $this->post(208 + $i, 2, $i + 8, sprintf('2021-09-02 %02d:00:00', $i));
        }

        $this->prepareDatabase([
            User::class => [
                ['id' => 2, 'username' => 'Potato', 'email' => 'potato@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Target', 'created_at' => '2021-09-01 00:00:00', 'last_posted_at' => '2021-09-01 11:00:00', 'user_id' => 2, 'first_post_id' => 101, 'last_post_id' => 112, 'last_post_number' => 12, 'comment_count' => 12],
                ['id' => 2, 'title' => 'Source', 'created_at' => '2021-08-31 12:00:00', 'last_posted_at' => '2021-09-02 05:00:00', 'user_id' => 2, 'first_post_id' => 201, 'last_post_id' => 213, 'last_post_number' => 13, 'comment_count' => 13],
            ],
            Post::class => $posts,
        ]);
    }

    protected function post(int $id, int $discussionId, int $number, string $createdAt): array
    {
        return ['id' => $id, 'discussion_id' => $discussionId, 'number' => $number, 'created_at' => $createdAt, 'user_id' => 2, 'type' => 'comment', 'content' => '<t>potato</t>'];
    }

    protected function move(array $postIds): void
    {
        $response = $this->send(
            $this->request('POST', '/api/posts/move', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'postIds' => $postIds,
                        'sourceDiscussionId' => 2,
                        'targetDiscussionId' => 1,
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }

    /**
     * @return array<int, array{number: int, content: array, user_id: int, created_at: string}>
     */
    protected function eventPostsInSource(): array
    {
        return PostMovedPost::query()
            ->where('discussion_id', 2)
            ->where('type', 'postMoved')
            ->orderBy('number')
            ->get()
            ->map(fn (PostMovedPost $post) => [
                'number' => $post->number,
                'content' => $post->content,
                'user_id' => $post->user_id,
                'created_at' => $post->created_at->format('Y-m-d H:i:s'),
            ])
            ->all();
    }

    #[Test]
    public function complex_move_slots_posts_between_target_posts_by_date()
    {
        $this->move([202, 203, 204, 205, 206, 207]);

        $target = Post::query()->where('discussion_id', 1)->orderBy('number')->get();

        $this->assertEquals(
            [101, 202, 102, 103, 203, 104, 105, 204, 106, 107, 205, 108, 109, 206, 110, 111, 207, 112],
            $target->pluck('id')->all()
        );
        $this->assertEquals(range(1, 18), $target->pluck('number')->all());

        $discussion = Discussion::query()->find(1);
        $this->assertEquals(18, $discussion->last_post_number);
        $this->assertEquals(18, $discussion->comment_count);
        $this->assertEquals(112, $discussion->last_post_id);
    }

    #[Test]
    public function complex_move_leaves_an_event_post_in_place_of_each_moved_post()
    {
        $this->move([202, 203, 204, 205, 206, 207]);

        $newNumbers = [202 => 2, 203 => 5, 204 => 8, 205 => 11, 206 => 14, 207 => 17];

        $expected = [];
        foreach (array_keys($newNumbers) as $i => $id) {
            $expected[] = [
                'number' => $i + 2,
                'content' => ['targetDiscussionId' => 1, 'targetDiscussionTitle' => 'Target', 'count' => 1, 'number' => $newNumbers[$id], 'originalPostId' => $id],
                'user_id' => 1,
                'created_at' => sprintf('2021-09-01 %02d:30:00', $i * 2),
            ];
        }

        $this->assertEquals($expected, $this->eventPostsInSource());
    }

    #[Test]
    public function simple_move_leaves_an_event_post_in_place_of_each_moved_post()
    {
        $this->move([208, 209, 210, 211, 212, 213]);

        $this->assertEquals(range(13, 18), Post::query()->whereIn('id', range(208, 213))->orderBy('id')->pluck('number')->all());

        $expected = [];
        foreach (range(0, 5) as $i) {
            $expected[] = [
                'number' => $i + 8,
                'content' => ['targetDiscussionId' => 1, 'targetDiscussionTitle' => 'Target', 'count' => 1, 'number' => $i + 13, 'originalPostId' => 208 + $i],
                'user_id' => 1,
                'created_at' => sprintf('2021-09-02 %02d:00:00', $i),
            ];
        }

        $this->assertEquals($expected, $this->eventPostsInSource());
    }

    #[Test]
    public function simple_move_groups_sequential_event_posts_when_enabled()
    {
        $this->setting('fof-move-posts.group_sequential_event_posts', '1');

        $this->move([208, 209, 210, 211, 212, 213]);

        $this->assertEquals([
            [
                'number' => 13,
                'content' => ['targetDiscussionId' => 1, 'targetDiscussionTitle' => 'Target', 'count' => 6, 'number' => 13, 'originalPostId' => 208],
                'user_id' => 1,
                'created_at' => '2021-09-02 00:00:00',
            ],
        ], $this->eventPostsInSource());
    }
}
