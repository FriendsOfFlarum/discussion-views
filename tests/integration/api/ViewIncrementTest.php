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
use PHPUnit\Framework\Attributes\Test;

class ViewIncrementTest extends TestCase
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
            ]
        ]);
    }

    #[Test]
    public function viewing_discussion_increments_view_count_for_guest()
    {
        // View the discussion as guest
        $response = $this->send(
            $this->request('GET', '/api/discussions/1')
        );

        $this->assertEquals(200, $response->getStatusCode());

        // Get discussion from database
        $discussion = Discussion::find(1);

        // View count should have incremented from initial 5 to 6
        $this->assertEquals(6, $discussion->view_count);
    }

    #[Test]
    public function viewing_discussion_increments_view_count_for_authenticated_user()
    {
        // View the discussion as authenticated user
        $response = $this->send(
            $this->request('GET', '/api/discussions/1', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        // Get discussion from database
        $discussion = Discussion::find(1);

        // View count should have incremented from initial 5 to 6
        $this->assertEquals(6, $discussion->view_count);
    }

    #[Test]
    public function viewing_discussion_from_zero_increments_correctly()
    {
        // View the discussion
        $response = $this->send(
            $this->request('GET', '/api/discussions/2')
        );

        $this->assertEquals(200, $response->getStatusCode());

        // Get discussion from database
        $discussion = Discussion::find(2);

        // View count should be 1 (incremented from 0)
        $this->assertEquals(1, $discussion->view_count);
    }

    #[Test]
    public function multiple_views_increment_count_multiple_times()
    {
        // Requests carry no REMOTE_ADDR and no actor, so each of these is an
        // unattributable view: there is nothing to dedupe against and every one
        // counts. A viewer that can be identified is counted once instead —
        // see ViewDedupeTest.
        for ($i = 0; $i < 3; $i++) {
            $this->send(
                $this->request('GET', '/api/discussions/1')
            );
        }

        // Get discussion from database
        $discussion = Discussion::find(1);

        // View count should be 8 (initial 5 + 3 views)
        $this->assertEquals(8, $discussion->view_count);
    }

    #[Test]
    public function listing_discussions_does_not_increment_view_count()
    {
        // List discussions
        $response = $this->send(
            $this->request('GET', '/api/discussions')
        );

        $this->assertEquals(200, $response->getStatusCode());

        // Get discussions from database
        $discussion1 = Discussion::find(1);
        $discussion2 = Discussion::find(2);

        // View counts should NOT have changed from initial values
        $this->assertEquals(5, $discussion1->view_count);
        $this->assertEquals(0, $discussion2->view_count);
    }

    #[Test]
    public function view_count_in_response_reflects_incremented_value()
    {
        // View the discussion
        $response = $this->send(
            $this->request('GET', '/api/discussions/1')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        // The view count field should be present
        $this->assertArrayHasKey('views', $data['data']['attributes']);

        // Verify database has incremented from 5 to 6
        $discussion = Discussion::find(1);
        $this->assertEquals(6, $discussion->view_count);
    }
}
