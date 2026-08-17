# FoF Discussion Views

[![MIT license](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/fof/discussion-views/blob/master/LICENSE) [![Latest Stable Version](https://img.shields.io/packagist/v/fof/discussion-views.svg)](https://packagist.org/packages/fof/discussion-views) [![Total Downloads](https://img.shields.io/packagist/dt/fof/discussion-views.svg)](https://packagist.org/packages/fof/discussion-views)

A lightweight discussion view tracker, with minimal settings and options.

## Features

- Tracks how many times a discussion has been viewed and displays it per discussion on the discussionlist, for guests and registered members alike. _Note: by default a viewer is counted once per discussion per 15 minutes, so refreshing or navigating back to a discussion does not inflate its count — see [View deduplication](#view-deduplication)_
- Adds 2 sorting options: popular and unpopular
- Adds 1 event which developers can listen for: `DiscussionWasViewed` - includes accessor IP and UserAgent strings
- Adds 1 new permission where people can (re)set the viewcount of a discussion (default to admins)
- Uses `view_count` column created on the `discussions` table, so should not impact load performance
- Identify known crawlers with an option to not increase the view count for their visit. Uses [jaybizzle/crawler-detect](https://github.com/JayBizzle/Crawler-Detect) for identification

### View deduplication

A viewer is counted at most once per discussion within a configurable window, 15 minutes by default. Registered members are identified by their user id, guests by their IP address; a view that can be attributed to neither is always counted.

The window is set in the admin panel, or directly as the `fsdv.dedupe-ttl` setting in seconds. Setting it to `0` disables deduplication and counts every view, as earlier versions did.

This keeps counts closer to the number of people who read a discussion rather than the number of requests made, and it removes a database write from most page loads — a refresh or a return visit costs a cache lookup instead.

Two consequences worth knowing:

- Guests sharing an address (an office or a household behind one connection) are deduplicated against each other for that window. Registered members are not, as they key on their user id.
- Deduplication uses Flarum's configured cache. No queue or Redis is required — the default file cache is enough — but clearing the cache lets recent viewers be counted again.

### Installation

```sh
composer require fof/discussion-views:"*"
```

### Links

- [Packagist](https://packagist.org/packages/fof/discussion-views)
- [GitHub](https://github.com/friendsofflarum/discussion-views)
- [Discuss](https://discuss.flarum.org/d/24002)
