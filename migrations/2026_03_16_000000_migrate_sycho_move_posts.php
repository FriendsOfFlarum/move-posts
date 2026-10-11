<?php

/*
 * This file is part of fof/move-posts.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Builder;

/** Migration from sycho/flarum-move-posts */
return [
    'up' => function (Builder $schema) {
        $db = $schema->getConnection();

        // Rows are renamed one at a time because a forum can already hold the fof-namespaced
        // equivalent (e.g. a leftover from an earlier install), which a bulk rename would
        // collide with on the primary key. In that case the fof row wins and the sycho row is dropped.
        $db->table('settings')
            ->where('key', 'LIKE', 'sycho-move-posts.%')
            ->pluck('key')
            ->each(function (string $old) use ($db) {
                $new = 'fof-move-posts.'.substr($old, strlen('sycho-move-posts.'));
                $query = $db->table('settings')->where('key', $old);

                $db->table('settings')->where('key', $new)->exists()
                    ? $query->delete()
                    : $query->update(['key' => $new]);
            });

        $db->table('group_permission')
            ->where('permission', 'LIKE', 'sycho-move-posts:%')
            ->get(['group_id', 'permission'])
            ->each(function (object $row) use ($db) {
                $new = 'fof-move-posts:'.substr($row->permission, strlen('sycho-move-posts:'));
                $query = $db->table('group_permission')
                    ->where('group_id', $row->group_id)
                    ->where('permission', $row->permission);

                $db->table('group_permission')
                    ->where('group_id', $row->group_id)
                    ->where('permission', $new)
                    ->exists()
                    ? $query->delete()
                    : $query->update(['permission' => $new]);
            });

        $db->table('migrations')->where('extension', 'sycho-move-posts')->delete();
    },
    'down' => function (Builder $schema) {
        // Not applicable.
        // There is no reason to revert keys back to the abandoned 'sycho' namespace.
        // Doing so would only create unnecessary junk in the database for new installations.
    }
];
