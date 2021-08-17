# Root cache cleaner

Removes duplicate filecache entry from the root filecache.

## Why

In certain circumstances it is possible that Nextcloud creates duplicate items in the filesystem cache,
adding the items of a user both to the per-user cache and the global root cache.

While these duplicates generally don't cause any issues they can bloat the filecache significantly and lead
to degraded performance.

## What

This app can clean the duplicate filecache entries by going through each user and looking for cache items in the root
cache that should be in the per-user cache instead, deleting any it finds.

While these steps should be perfectly safe, an abundance of caution should always be taking with these kinds of
large database deletions, and it is **strongly** recommended to ensure that proper database backup is in place before
running the cleanup.

## How

- Ensure proper database backends are in place.
- Install the app.
- Run the cleaner with `occ root_cache_cleaner:clean`.

Note that the cleanup process involves some fairly heavy database queries and can take a long time on large instances.
