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

class ManualViewCountTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-discussion-views');

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => 'Test Discussion', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'view_count' => 100],
                ['id' => 2, 'title' => 'Another Discussion', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 2, 'comment_count' => 1, 'view_count' => 50],
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
    public function admin_can_manually_set_view_count()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/discussions/1', [
                'authenticatedAs' => 1, // Admin user
                'json' => [
                    'data' => [
                        'type' => 'discussions',
                        'id' => '1',
                        'attributes' => [
                            'views' => 500,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        // View count should be updated to 500 (not incremented to 501)
        $this->assertEquals(500, $data['data']['attributes']['views']);

        // Verify in database
        $discussion = Discussion::find(1);
        $this->assertEquals(500, $discussion->view_count);
    }

    #[Test]
    public function admin_can_reset_view_count_to_zero()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/discussions/1', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'discussions',
                        'id' => '1',
                        'attributes' => [
                            'views' => 0,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(0, $data['data']['attributes']['views']);

        $discussion = Discussion::find(1);
        $this->assertEquals(0, $discussion->view_count);
    }

    #[Test]
    public function regular_user_cannot_set_view_count()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/discussions/2', [
                'authenticatedAs' => 2, // Normal user (owns discussion 2)
                'json' => [
                    'data' => [
                        'type' => 'discussions',
                        'id' => '2',
                        'attributes' => [
                            'views' => 999,
                        ],
                    ],
                ],
            ])
        );

        // Normal users don't have permission to update discussions (403 Forbidden)
        $this->assertEquals(403, $response->getStatusCode());

        // View count should NOT have changed from original 50
        $discussion = Discussion::find(2);
        $this->assertEquals(50, $discussion->view_count);
    }

    #[Test]
    public function guest_cannot_update_discussion()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/discussions/1', [
                'json' => [
                    'data' => [
                        'type' => 'discussions',
                        'id' => '1',
                        'attributes' => [
                            'views' => 999,
                        ],
                    ],
                ],
            ])
        );

        // Guest gets bad request (400) when trying to update
        $this->assertEquals(400, $response->getStatusCode());
    }

    #[Test]
    public function setting_view_count_does_not_trigger_automatic_increment()
    {
        // First, manually set the view count
        $response = $this->send(
            $this->request('PATCH', '/api/discussions/1', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'discussions',
                        'id' => '1',
                        'attributes' => [
                            'views' => 200,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        // The view count should be exactly 200, not 201 (no auto-increment)
        $discussion = Discussion::find(1);
        $this->assertEquals(200, $discussion->view_count);
    }

    #[Test]
    public function can_update_other_fields_without_affecting_view_count()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/discussions/1', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'discussions',
                        'id' => '1',
                        'attributes' => [
                            'title' => 'Updated Title',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        // Title should be updated
        $this->assertEquals('Updated Title', $data['data']['attributes']['title']);

        // View count should remain unchanged at 100
        $this->assertEquals(100, $data['data']['attributes']['views']);

        $discussion = Discussion::find(1);
        $this->assertEquals(100, $discussion->view_count);
    }

    #[Test]
    public function can_set_view_count_and_update_title_simultaneously()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/discussions/1', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'discussions',
                        'id' => '1',
                        'attributes' => [
                            'title' => 'New Title',
                            'views' => 300,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals('New Title', $data['data']['attributes']['title']);
        $this->assertEquals(300, $data['data']['attributes']['views']);

        $discussion = Discussion::find(1);
        $this->assertEquals('New Title', $discussion->title);
        $this->assertEquals(300, $discussion->view_count);
    }
}
