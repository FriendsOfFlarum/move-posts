<?php

namespace FoF\MovePosts\Event;

use Flarum\Discussion\Discussion;
use Flarum\User\User;

class CreatedTargetDiscussion
{
    /** @var Discussion $targetDiscussion */
    public $targetDiscussion;
    /** @var Discussion $sourceDiscussion */
    public $sourceDiscussion;
    /** @var User $actor */
    public $actor;

    public function __construct(Discussion $targetDiscussion, Discussion $sourceDiscussion, User $actor)
    {
        $this->actor = $actor;
        $this->sourceDiscussion = $sourceDiscussion;
        $this->targetDiscussion = $targetDiscussion;
    }
}
