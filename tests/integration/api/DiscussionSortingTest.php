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
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;

class DiscussionSortingTest extends TestCase
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
    public function discussions_can_be_sorted_by_view_count_descending()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
                ->withQueryParams([
                    'sort' => '-view_count', // Descending (most viewed first)
                ])
        );

        $body = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode(), $body);
        $data = json_decode($body, true);

        $this->assertArrayHasKey('data', $data);
        $this->assertNotEmpty($data['data']);

        $ids = Arr::pluck($data['data'], 'id');

        // Should be sorted by view_count DESC: 1 (100), 2 (50), 3 (10), 4 (0)
        $this->assertEquals(['1', '2', '3', '4'], $ids);
    }

    #[Test]
    public function discussions_can_be_sorted_by_view_count_ascending()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
                ->withQueryParams([
                    'sort' => 'view_count', // Ascending (least viewed first)
                ])
        );

        $body = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode(), $body);
        $data = json_decode($body, true);

        $this->assertArrayHasKey('data', $data);
        $this->assertNotEmpty($data['data']);

        $ids = Arr::pluck($data['data'], 'id');

        // Should be sorted by view_count ASC: 4 (0), 3 (10), 2 (50), 1 (100)
        $this->assertEquals(['4', '3', '2', '1'], $ids);
    }

    #[Test]
    public function popular_filter_sorts_by_view_count_descending()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
                ->withQueryParams([
                    'filter' => ['popular' => ''],
                ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        $ids = Arr::pluck($data['data'], 'id');

        // Should be sorted by view_count DESC when popular filter is applied
        $this->assertEquals(['1', '2', '3', '4'], $ids);
    }

    #[Test]
    public function popular_filter_negated_sorts_by_view_count_ascending()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
                ->withQueryParams([
                    'filter' => ['-popular' => ''],
                ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        $ids = Arr::pluck($data['data'], 'id');

        // Should be sorted by view_count ASC when negated
        $this->assertEquals(['4', '3', '2', '1'], $ids);
    }

    #[Test]
    public function sort_combines_with_other_filters()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
                ->withQueryParams([
                    'filter' => ['author' => 'normal'],
                    'sort' => '-view_count',
                ])
        );

        $body = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode(), $body);
        $data = json_decode($body, true);

        $ids = Arr::pluck($data['data'], 'id');

        // Should show only discussions by user 2 (normal), sorted by view_count DESC
        // Discussions 3 and 4 are by user 2, with view counts 10 and 0 respectively
        $this->assertEquals(['3', '4'], $ids);
    }

    #[Test]
    public function view_count_values_are_correct_in_sorted_results()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
                ->withQueryParams([
                    'sort' => '-view_count',
                ])
        );

        $data = json_decode($response->getBody()->getContents(), true);

        $viewCounts = array_map(
            fn($discussion) => $discussion['attributes']['views'],
            $data['data']
        );

        // View counts should be in descending order
        $this->assertEquals([100, 50, 10, 0], $viewCounts);
    }
}
