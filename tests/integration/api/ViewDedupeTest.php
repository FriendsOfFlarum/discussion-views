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
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Cache\Repository as Cache;
use PHPUnit\Framework\Attributes\Test;

class ViewDedupeTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-discussion-views');

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => 'Test Discussion', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'view_count' => 5],
                ['id' => 2, 'title' => 'Another Discussion', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 2, 'comment_count' => 1, 'view_count' => 0],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>First post</p></t>'],
                ['id' => 2, 'discussion_id' => 2, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Second post</p></t>'],
            ],
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REMOTE_ADDR']);

        parent::tearDown();
    }

    /**
     * Send a request as a viewer at the given address.
     *
     * Helpers::getIpAddress() reads the $_SERVER superglobal rather than the
     * request, so the address has to be set there. Tests carry no REMOTE_ADDR
     * by default, which sends guests down the "cannot identify the viewer"
     * path and skips dedupe entirely.
     */
    protected function sendFrom(string $uri, ?string $ip = null, array $options = [], ?string $userAgent = null)
    {
        if ($ip === null) {
            unset($_SERVER['REMOTE_ADDR']);
        } else {
            $_SERVER['REMOTE_ADDR'] = $ip;
        }

        $request = $this->request('GET', $uri, $options);

        if ($userAgent !== null) {
            $request = $request->withHeader('User-Agent', $userAgent);
        }

        return $this->send($request);
    }

    #[Test]
    public function repeated_views_by_the_same_guest_count_once()
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->sendFrom('/api/discussions/1', '203.0.113.10');
            $this->assertEquals(200, $response->getStatusCode());
        }

        $this->assertEquals(6, Discussion::find(1)->view_count);
    }

    #[Test]
    public function repeated_views_by_the_same_authenticated_user_count_once()
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->sendFrom('/api/discussions/1', null, [
                'authenticatedAs' => 2,
            ]);
            $this->assertEquals(200, $response->getStatusCode());
        }

        $this->assertEquals(6, Discussion::find(1)->view_count);
    }

    #[Test]
    public function different_guests_are_counted_separately()
    {
        foreach (['203.0.113.10', '203.0.113.11', '203.0.113.12'] as $ip) {
            $this->sendFrom('/api/discussions/1', $ip);
        }

        $this->assertEquals(8, Discussion::find(1)->view_count);
    }

    #[Test]
    public function the_same_viewer_is_counted_once_per_discussion()
    {
        // Being counted for one discussion must not suppress another.
        $this->sendFrom('/api/discussions/1', '203.0.113.10');
        $this->sendFrom('/api/discussions/2', '203.0.113.10');
        $this->sendFrom('/api/discussions/2', '203.0.113.10');

        $this->assertEquals(6, Discussion::find(1)->view_count);
        $this->assertEquals(1, Discussion::find(2)->view_count);
    }

    #[Test]
    public function a_guest_and_an_authenticated_user_from_one_address_are_counted_separately()
    {
        // Signed-in viewers key on their user id, so they are not deduped
        // against the guest traffic sharing their address.
        $this->sendFrom('/api/discussions/1', '203.0.113.10');
        $this->sendFrom('/api/discussions/1', '203.0.113.10', [
            'authenticatedAs' => 2,
        ]);

        $this->assertEquals(7, Discussion::find(1)->view_count);
    }

    #[Test]
    public function a_view_that_cannot_be_attributed_is_still_counted()
    {
        // With neither a user id nor an address there is nothing to dedupe
        // against, so the view counts rather than being silently dropped.
        $this->sendFrom('/api/discussions/1');
        $this->sendFrom('/api/discussions/1');

        $this->assertEquals(7, Discussion::find(1)->view_count);
    }

    #[Test]
    public function deduplication_is_disabled_when_the_ttl_is_zero()
    {
        $this->setting('fsdv.dedupe-ttl', 0);

        for ($i = 0; $i < 3; $i++) {
            $this->sendFrom('/api/discussions/1', '203.0.113.10');
        }

        $this->assertEquals(8, Discussion::find(1)->view_count);
    }

    #[Test]
    public function the_configured_ttl_is_applied_to_the_dedupe_window()
    {
        $this->setting('fsdv.dedupe-ttl', 60);

        $this->sendFrom('/api/discussions/1', '203.0.113.10');
        $this->sendFrom('/api/discussions/1', '203.0.113.10');

        $this->assertEquals(6, Discussion::find(1)->view_count);

        // The viewer is remembered for the configured window, not a hardcoded one.
        $cache = $this->app()->getContainer()->make(Cache::class);
        $this->assertNotNull($cache->get('fsdv.seen.1.203.0.113.10'));
    }

    #[Test]
    public function a_crawler_does_not_suppress_a_later_view_from_the_same_address()
    {
        // Claiming the dedupe key marks a viewer as counted, so it must happen
        // after the crawler check: a crawler that claimed the key first would
        // suppress the next real view from that address.
        $this->sendFrom('/api/discussions/1', '203.0.113.10', [], 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');

        $this->assertEquals(5, Discussion::find(1)->view_count, 'crawler should not be counted');

        $this->sendFrom('/api/discussions/1', '203.0.113.10', [], 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

        $this->assertEquals(6, Discussion::find(1)->view_count, 'real view after a crawler should count');
    }
}
