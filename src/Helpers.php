<?php

/*
 * This file is part of fof/discussion-views.
 *
 * Copyright (c) FriendsOfFlarum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\DiscussionViews;

use Illuminate\Support\Arr;

class Helpers
{
    public static function getIpAddress(): ?string
    {
        return Arr::get($_SERVER, 'HTTP_CLIENT_IP')
            ?? Arr::get($_SERVER, 'HTTP_CF_CONNECTING_IP')
            ?? Arr::get($_SERVER, 'HTTP_X_FORWARDED_FOR')
            ?? Arr::get($_SERVER, 'REMOTE_ADDR');
    }

    public static function getUserAgentString(): ?string
    {
        return Arr::get($_SERVER, 'HTTP_USER_AGENT');
    }
}
