<?php

/*
 * This file is part of fof/discussion-views.
 *
 * Copyright (c) FriendsOfFlarum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\DiscussionViews\Listeners;

use Carbon\Carbon;
use Flarum\Api\Context;
use Flarum\Discussion\Discussion;
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\DiscussionViews\Events\DiscussionWasViewed;
use FoF\DiscussionViews\Helpers;
use Illuminate\Contracts\Events\Dispatcher;
use Jaybizzle\CrawlerDetect\CrawlerDetect;

class AddDiscussionViewHandler
{
    /**
     * Allows disabling the handler ahead of any internal API calls.
     *
     * @var bool
     */
    public static $enabled = true;

    public function __construct(public Dispatcher $bus, public SettingsRepositoryInterface $settings, public CrawlerDetect $crawler)
    {
    }

    public function __invoke(Context $context, Discussion $discussion): void
    {
        if (static::$enabled === false) {
            return;
        }

        $actor = $context->getActor();
        $request = $context->request;

        if ($this->settings->get('fsdv.ignore-crawlers') && $this->isCrawler($request->getHeader('User-Agent'))) {
            return;
        }

        $discussion->increment('view_count', 1);

        $this->bus->dispatch(new DiscussionWasViewed($actor, $discussion, Helpers::getIpAddress(), Helpers::getUserAgentString(), Carbon::now()));
    }

    private function isCrawler(array $agents): bool
    {
        $detected = false;

        foreach ($agents as $agent) {
            if (empty($agent)) {
                continue;
            }

            if ($this->crawler->isCrawler($agent)) {
                $detected = true;
            }
        }

        return $detected;
    }
}
