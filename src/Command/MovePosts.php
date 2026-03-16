<?php

/*
 * This file is part of fof/move-posts.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\MovePosts\Command;

use Flarum\User\User;

class MovePosts
{
    /**
     * @var \Flarum\User\User
     */
    public $actor;

    /**
     * @var array
     */
    public $data;

    /**
     * @var bool
     */
    public $emulate;

    public function __construct(User $actor, array $data, bool $emulate)
    {
        $this->actor = $actor;
        $this->data = $data;
        $this->emulate = $emulate;
    }
}
