<?php

/*
 * This file is part of fof/move-posts.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\MovePosts\Api\Controller;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Api\Serializer\DiscussionSerializer;
use Flarum\Http\RequestUtil;
use FoF\MovePosts\Command\MovePosts;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class MovePostsController extends AbstractShowController
{
    /**
     * The serializer instance for this request.
     *
     * @var string
     */
    public $serializer = DiscussionSerializer::class;

    /**
     * @var Dispatcher
     */
    protected $bus;

    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $data = Arr::get($request->getParsedBody(), 'data', []);

        $result = $this->bus->dispatch(new MovePosts($actor, $data, false));

        $document->setMeta([
            'status' => $result['status'],
            'postCount' => $result['postCount'],
            'firstMovedPostNumber' => $result['firstMovedPostNumber'],
            'sourceDiscussionId' => (string) $result['sourceDiscussion']->id,
            'targetDiscussionId' => (string) $result['targetDiscussion']->id,
        ]);

        return $result['targetDiscussion'];
    }
}
