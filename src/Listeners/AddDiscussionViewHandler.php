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
use Illuminate\Contracts\Cache\Repository as Cache;
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

    public function __construct(public Dispatcher $bus, public SettingsRepositoryInterface $settings, public CrawlerDetect $crawler, protected Cache $cache)
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

        // Counting the same viewer repeatedly costs a durable write per request
        // and inflates the total, so a viewer only counts once per discussion
        // per fsdv.dedupe-ttl. This has to run after the crawler check: claiming
        // the key is what marks the viewer as counted, so doing it before a check
        // that can still reject the view would suppress the next real view from
        // the same address.
        if (! $this->countable($actor->id, $discussion->id)) {
            return;
        }

        $discussion->increment('view_count', 1);

        $this->bus->dispatch(new DiscussionWasViewed($actor, $discussion, Helpers::getIpAddress(), Helpers::getUserAgentString(), Carbon::now()));
    }

    /**
     * Whether this viewer has not already been counted for this discussion.
     *
     * add() is atomic and returns false when the key is already held, so two
     * concurrent requests from the same viewer cannot both count a view.
     */
    private function countable(?int $actorId, int $discussionId): bool
    {
        $ttl = (int) $this->settings->get('fsdv.dedupe-ttl');

        // Zero (or a nonsensical value) turns deduplication off, counting every
        // view as the extension did before it was introduced.
        if ($ttl <= 0) {
            return true;
        }

        $viewer = $actorId ?: Helpers::getIpAddress();

        // With no way to identify the viewer there is nothing to dedupe against,
        // so count the view rather than dropping it.
        if (empty($viewer)) {
            return true;
        }

        return $this->cache->add("fsdv.seen.$discussionId.$viewer", 1, $ttl);
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
