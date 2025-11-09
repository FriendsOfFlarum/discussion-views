<?php

/*
 * This file is part of fof/discussion-views.
 *
 * Copyright (c) FriendsOfFlarum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\DiscussionViews\Events;

use DateTime;
use Flarum\Discussion\Discussion;
use Flarum\User\User;

class DiscussionWasViewed
{
    /**
     * DiscussionWasViewed constructor.
     *
     */
    public function __construct(public User $actor, public Discussion $discussion, public ?string $ip, public ?string $userAgent, public DateTime $timeStamp)
    {
    }
}
