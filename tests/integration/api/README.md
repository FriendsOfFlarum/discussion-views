# Discussion Views Integration Tests

This directory contains integration tests for the FoF Discussion Views extension.

## Test Files

### DiscussionViewCountTest.php (4 tests)
Tests the view count field in API responses:
- **View count field in list**: Verifies that `views` attribute is included in discussion list endpoint
- **View count field in show**: Verifies that `views` attribute is included in show endpoint (accounts for increment)
- **Permission visibility for admins**: Verifies `canReset` attribute is shown to authorized users
- **Permission hidden from users**: Verifies `canReset` attribute is not shown to regular users

### DiscussionSortingTest.php (7 tests)
Tests sorting and filtering functionality:
- **Sort by view count descending**: Tests `sort=-view_count` (most viewed first)
- **Sort by view count ascending**: Tests `sort=view_count` (least viewed first)
- **Popular filter**: Tests `filter[popular]=` gambit (descending order)
- **Negated popular filter**: Tests `filter[-popular]=` gambit (ascending order)
- **Combined filters**: Tests sorting combined with author filter
- **Sort value accuracy**: Verifies view counts are correct in sorted results

### ViewIncrementTest.php (7 tests)
Tests the view count increment functionality:
- **Guest view increment**: Verifies viewing a discussion as guest increments the counter
- **Authenticated view increment**: Verifies viewing as logged-in user increments the counter
- **Zero to one increment**: Tests incrementing from initial zero value
- **Multiple increments**: Verifies multiple views increment correctly
- **List endpoint behavior**: Confirms listing discussions does NOT increment view counts
- **Response reflection**: Verifies the API response reflects the updated count

### CrawlerDetectionTest.php (5 tests)
Tests the crawler detection and filtering:
- **Crawler detection enabled**: Verifies bot views are ignored when setting is enabled
- **Crawler detection disabled**: Verifies bot views are counted when setting is disabled
- **Googlebot detection**: Tests detection of Google's crawler
- **Bingbot detection**: Tests detection of Bing's crawler
- **Normal browser**: Verifies regular browsers are not flagged as crawlers
- **Empty user agent**: Ensures the extension handles missing user agent headers gracefully

### ManualViewCountTest.php (8 tests)
Tests the manual view count setting functionality:
- **Admin can set view count**: Verifies admin users can manually set discussion view counts
- **Admin can reset to zero**: Tests resetting view counts to zero
- **Regular user cannot set**: Verifies regular users cannot set view counts (403 Forbidden)
- **Guest cannot update**: Verifies guests cannot update discussions (400 Bad Request)
- **No automatic increment**: Ensures manual setting doesn't trigger automatic view increment
- **Other fields unaffected**: Verifies updating other fields doesn't affect view count
- **Simultaneous updates**: Tests setting view count while updating other fields (e.g., title)

## Running Tests

Run all integration tests:
```bash
composer test:integration
```

Run only these API tests:
```bash
vendor/bin/phpunit -c tests/phpunit.integration.xml tests/integration/api/
```

Run a specific test file:
```bash
vendor/bin/phpunit -c tests/phpunit.integration.xml tests/integration/api/ManualViewCountTest.php
```

Run a specific test:
```bash
vendor/bin/phpunit -c tests/phpunit.integration.xml --filter admin_can_manually_set_view_count
```

## Test Database Setup

Before running integration tests for the first time:
```bash
composer test:setup
```

This creates the test database required for integration tests.

## Test Coverage

**Total: 28 integration tests** with **70 assertions** covering:

1. ✅ View count field is included in API responses (2 tests)
2. ✅ View counts increment correctly when viewing discussions (7 tests)
3. ✅ Sorting by view count works in both directions (7 tests)
4. ✅ Popular filter gambit works correctly (2 tests)
5. ✅ Crawler detection prevents bot views from being counted (5 tests)
6. ✅ Settings control crawler detection behavior (included above)
7. ✅ Permissions are respected for reset functionality (2 tests)
8. ✅ List endpoint does not increment counters (1 test)
9. ✅ Manual view count setting works correctly (8 tests)
10. ✅ Only admins can manually set view counts (3 tests)

## Notes

- Tests use `prepareDatabase()` to seed test data with specific view counts
- Each test is isolated and runs in its own transaction
- The tests follow Flarum core testing patterns for consistency
- API sorting uses field names (`view_count`, `-view_count`)
- Frontend uses aliases (`most_viewed`, `least_viewed`) which are translated by JavaScript
- Manual view count setting is handled via the writable `views` field in DiscussionResource
- Permission check `discussion.resetViews` controls who can manually set view counts
