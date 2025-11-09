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

use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\User\User;
use Illuminate\Database\Query\Builder;

class PopularFilter implements FilterInterface
{
    /**
     * {@inheritDoc}
     */
    public function getFilterKey(): string
    {
        return 'popular';
    }

    /**
     * {@inheritDoc}
     */

    /**
     * {@inheritDoc}
     */
    public function filter(SearchState $state, array|string $value, bool $negate): void
    {
        $this->sort($state->getQuery(), $state->getActor(), $negate);
    }

    protected function sort(Builder $query, User $actor, bool $negate)
    {
        $query->orderBy('view_count', 'desc');
    }

    /**
     * @param SearchState $search
     * @param array       $matches
     * @param             $negate
     */
}
