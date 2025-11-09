<?php

/*
 * This file is part of fof/discussion-views.
 *
 * Copyright (c) FriendsOfFlarum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\DiscussionViews\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class CrawlerDetectionTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-discussion-views');

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => 'Test Discussion', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'view_count' => 0],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Test post</p></t>'],
            ],
            User::class => [
                $this->normalUser(),
            ]
        ]);
    }

    #[Test]
    public function crawler_views_are_ignored_when_setting_enabled()
    {
        // Enable crawler detection (default is true)
        $settings = $this->app()->getContainer()->make(SettingsRepositoryInterface::class);
        $settings->set('fsdv.ignore-crawlers', true);

        $discussion = Discussion::find(1);
        $initialCount = $discussion->view_count;

        // View with Googlebot user agent
        $response = $this->send(
            $this->request('GET', '/api/discussions/1')
                ->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)')
        );

        $this->assertEquals(200, $response->getStatusCode());

        // Refresh discussion from database
        $discussion = Discussion::find(1);

        // View count should NOT have incremented
        $this->assertEquals($initialCount, $discussion->view_count);
    }

    #[Test]
    public function crawler_views_are_counted_when_setting_disabled()
    {
        // Disable crawler detection
        $settings = $this->app()->getContainer()->make(SettingsRepositoryInterface::class);
        $settings->set('fsdv.ignore-crawlers', false);

        $discussion = Discussion::find(1);
        $initialCount = $discussion->view_count;

        // View with Googlebot user agent
        $response = $this->send(
            $this->request('GET', '/api/discussions/1')
                ->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)')
        );

        $this->assertEquals(200, $response->getStatusCode());

        // Refresh discussion from database
        $discussion = Discussion::find(1);

        // View count SHOULD have incremented
        $this->assertEquals($initialCount + 1, $discussion->view_count);
    }

    #[Test]
    public function bingbot_is_detected_as_crawler()
    {
        $settings = $this->app()->getContainer()->make(SettingsRepositoryInterface::class);
        $settings->set('fsdv.ignore-crawlers', true);

        $discussion = Discussion::find(1);
        $initialCount = $discussion->view_count;

        // View with Bingbot user agent
        $response = $this->send(
            $this->request('GET', '/api/discussions/1')
                ->withHeader('User-Agent', 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)')
        );

        $this->assertEquals(200, $response->getStatusCode());

        // Refresh discussion from database
        $discussion = Discussion::find(1);

        // View count should NOT have incremented
        $this->assertEquals($initialCount, $discussion->view_count);
    }

    #[Test]
    public function normal_browser_is_not_detected_as_crawler()
    {
        $settings = $this->app()->getContainer()->make(SettingsRepositoryInterface::class);
        $settings->set('fsdv.ignore-crawlers', true);

        $discussion = Discussion::find(1);
        $initialCount = $discussion->view_count;

        // View with normal browser user agent
        $response = $this->send(
            $this->request('GET', '/api/discussions/1')
                ->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36')
        );

        $this->assertEquals(200, $response->getStatusCode());

        // Refresh discussion from database
        $discussion = Discussion::find(1);

        // View count SHOULD have incremented
        $this->assertEquals($initialCount + 1, $discussion->view_count);
    }

    #[Test]
    public function empty_user_agent_does_not_crash()
    {
        $settings = $this->app()->getContainer()->make(SettingsRepositoryInterface::class);
        $settings->set('fsdv.ignore-crawlers', true);

        $discussion = Discussion::find(1);
        $initialCount = $discussion->view_count;

        // View without user agent
        $response = $this->send(
            $this->request('GET', '/api/discussions/1')
        );

        $this->assertEquals(200, $response->getStatusCode());

        // Refresh discussion from database
        $discussion = Discussion::find(1);

        // View count should have incremented (empty user agent is not a crawler)
        $this->assertEquals($initialCount + 1, $discussion->view_count);
    }
}
