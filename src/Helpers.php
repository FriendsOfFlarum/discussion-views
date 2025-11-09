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
        $ip = Arr::get($_SERVER, 'HTTP_CLIENT_IP')
            ?? Arr::get($_SERVER, 'HTTP_CF_CONNECTING_IP')
            ?? Arr::get($_SERVER, 'HTTP_X_FORWARDED_FOR')
            ?? Arr::get($_SERVER, 'REMOTE_ADDR');

        return is_string($ip) ? $ip : null;
    }

    public static function getUserAgentString(): ?string
    {
        $userAgent = Arr::get($_SERVER, 'HTTP_USER_AGENT');

        return is_string($userAgent) ? $userAgent : null;
    }
}
