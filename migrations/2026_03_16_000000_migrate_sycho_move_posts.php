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

        $db->table('settings')
            ->where('key', 'LIKE', 'sycho-move-posts.%')
            ->update(['key' => $db->raw("REPLACE({$db->getQueryGrammar()->wrap('key')}, 'sycho-move-posts.', 'fof-move-posts.')")]);

        $db->table('group_permission')
            ->where('permission', 'LIKE', 'sycho-move-posts:%')
            ->update(['permission' => $db->raw("REPLACE({$db->getQueryGrammar()->wrap('permission')}, 'sycho-move-posts:', 'fof-move-posts:')")]);

        $db->table('migrations')->where('extension', 'sycho-move-posts')->delete();
    },
    'down' => function (Builder $schema) {
        // Not applicable.
        // There is no reason to revert keys back to the abandoned 'sycho' namespace.
        // Doing so would only create unnecessary junk in the database for new installations.
    }
];
