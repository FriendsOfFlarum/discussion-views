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

class DiscussionViewCountTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-discussion-views');

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => 'Popular Discussion', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'view_count' => 100],
                ['id' => 2, 'title' => 'Moderate Discussion', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'first_post_id' => 2, 'comment_count' => 1, 'view_count' => 50],
                ['id' => 3, 'title' => 'Unpopular Discussion', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 3, 'comment_count' => 1, 'view_count' => 10],
                ['id' => 4, 'title' => 'New Discussion', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 4, 'comment_count' => 1, 'view_count' => 0],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>First post</p></t>'],
                ['id' => 2, 'discussion_id' => 2, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Second post</p></t>'],
                ['id' => 3, 'discussion_id' => 3, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Third post</p></t>'],
                ['id' => 4, 'discussion_id' => 4, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Fourth post</p></t>'],
            ],
            User::class => [
                $this->normalUser(),
            ]
        ]);
    }

    #[Test]
    public function view_count_is_included_in_discussion_list()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        // Find discussion 1 in the response
        $discussion = collect($data['data'])->firstWhere('id', '1');

        $this->assertNotNull($discussion);
        $this->assertArrayHasKey('views', $discussion['attributes']);
        $this->assertEquals(100, $discussion['attributes']['views']);
    }

    #[Test]
    public function view_count_is_included_in_discussion_show()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions/2')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('views', $data['data']['attributes']);
        // The view count will be 51 because viewing the discussion increments it
        $this->assertEquals(51, $data['data']['attributes']['views']);
    }

    #[Test]
    public function can_reset_permission_is_visible_to_authorized_user()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions/1', [
                'authenticatedAs' => 1, // Admin user
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('canReset', $data['data']['attributes']);
        $this->assertTrue($data['data']['attributes']['canReset']);
    }

    #[Test]
    public function can_reset_permission_is_not_visible_to_regular_user()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions/1', [
                'authenticatedAs' => 2, // Normal user
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        // canReset should not be in attributes for users without permission
        $this->assertArrayNotHasKey('canReset', $data['data']['attributes']);
    }
}
