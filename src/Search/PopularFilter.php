<?php

/*
 * This file is part of fof/discussion-views.
 *
 * Copyright (c) FriendsOfFlarum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\DiscussionViews\Search;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class PopularFilter implements FilterInterface
{
    /**
     * {@inheritDoc}
     */
    public function getFilterKey(): string
    {
        return 'popular';
    }

    public function filter(SearchState $state, array|string $value, bool $negate): void
    {
        $state->getQuery()->orderBy('view_count', $negate ? 'asc' : 'desc');
    }
}
