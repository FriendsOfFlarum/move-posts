<?php

/*
 * This file is part of fof/move-posts.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\MovePosts\tests\integration\migrations;

use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

class MigrateSychoMovePostsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-move-posts');
    }

    protected function runMigration(): void
    {
        $migration = require __DIR__.'/../../../migrations/2026_03_16_000000_migrate_sycho_move_posts.php';

        $migration['up']($this->database()->getSchemaBuilder());
    }

    #[Test]
    public function sycho_settings_and_permissions_are_renamed()
    {
        $this->prepareDatabase([
            'settings' => [
                ['key' => 'sycho-move-posts.group_sequential_event_posts', 'value' => '1'],
            ],
            'group_permission' => [
                ['group_id' => 4, 'permission' => 'sycho-move-posts:movePosts'],
            ],
            'migrations' => [
                ['migration' => '2021_08_18_000000_add_moved_to_discussions', 'extension' => 'sycho-move-posts'],
            ],
        ]);

        $this->runMigration();

        $db = $this->database();

        $this->assertSame('1', $db->table('settings')->where('key', 'fof-move-posts.group_sequential_event_posts')->value('value'));
        $this->assertFalse($db->table('settings')->where('key', 'LIKE', 'sycho-move-posts.%')->exists());

        $this->assertTrue($db->table('group_permission')->where('group_id', 4)->where('permission', 'fof-move-posts:movePosts')->exists());
        $this->assertFalse($db->table('group_permission')->where('permission', 'LIKE', 'sycho-move-posts:%')->exists());

        $this->assertFalse($db->table('migrations')->where('extension', 'sycho-move-posts')->exists());
    }

    #[Test]
    public function existing_fof_rows_are_kept_when_sycho_duplicates_exist()
    {
        $this->prepareDatabase([
            'settings' => [
                ['key' => 'sycho-move-posts.group_sequential_event_posts', 'value' => '0'],
                ['key' => 'fof-move-posts.group_sequential_event_posts', 'value' => '1'],
            ],
            'group_permission' => [
                ['group_id' => 4, 'permission' => 'sycho-move-posts:movePosts'],
                ['group_id' => 4, 'permission' => 'fof-move-posts:movePosts'],
                ['group_id' => 3, 'permission' => 'sycho-move-posts:movePosts'],
            ],
        ]);

        $this->runMigration();

        $db = $this->database();

        $this->assertSame('1', $db->table('settings')->where('key', 'fof-move-posts.group_sequential_event_posts')->value('value'));
        $this->assertFalse($db->table('settings')->where('key', 'LIKE', 'sycho-move-posts.%')->exists());

        $this->assertSame(1, $db->table('group_permission')->where('group_id', 4)->where('permission', 'fof-move-posts:movePosts')->count());
        $this->assertTrue($db->table('group_permission')->where('group_id', 3)->where('permission', 'fof-move-posts:movePosts')->exists());
        $this->assertFalse($db->table('group_permission')->where('permission', 'LIKE', 'sycho-move-posts:%')->exists());
    }
}
