<?php

/*
 * This file is part of fof/discussion-views.
 *
 * Copyright (c) FriendsOfFlarum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\DiscussionViews;

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\DiscussionResource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Extend;

return [
    (new Extend\Frontend('forum'))
        ->css(__DIR__.'/resources/less/forum.less')
        ->js(__DIR__.'/js/dist/forum.js'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    new Extend\Locales(__DIR__.'/resources/locale'),

    (new Extend\Model(Discussion::class))
        ->cast('view_count', 'int'),

    (new Extend\Policy())
        ->modelPolicy(Discussion::class, Access\DiscussionPolicy::class),

    (new Extend\ApiResource(DiscussionResource::class))
        ->fields(function () {
            return [
                Schema\Integer::make('views')
                    ->property('view_count')
                    ->writable(function (Discussion $discussion, Context $context) {
                        return $context->updating()
                            && $context->getActor()->can('resetViews', $discussion);
                    })
                    ->set(function (Discussion $discussion, int $value) {
                        $discussion->view_count = $value;
                    }),
                Schema\Boolean::make('canReset')
                    ->get(function (Discussion $model, Context $context) {
                        return $context->getActor()->can('resetViews', $model);
                    })
                    ->visible(function (Discussion $model, Context $context) {
                        return $context->getActor()->can('resetViews', $model);
                    }),
            ];
        })
        ->sorts(fn () => [
            SortColumn::make('view_count')
                ->descendingAlias('most_viewed')
                ->ascendingAlias('least_viewed'),
        ])
        ->endpoint('show', function (Endpoint\Show $endpoint) {
            return $endpoint->beforeSerialization(function (Context $context, Discussion $discussion) {
                resolve(Listeners\AddDiscussionViewHandler::class)($context, $discussion);
            });
        }),

    (new Extend\Settings())
        ->default('fsdv.ignore-crawlers', true)
        // Seconds a viewer is remembered before the same discussion counts for
        // them again. Zero counts every view.
        ->default('fsdv.dedupe-ttl', 900),

    (new Extend\SearchDriver(\Flarum\Search\Database\DatabaseSearchDriver::class))
        ->addFilter(DiscussionSearcher::class, Search\PopularFilter::class),
];
