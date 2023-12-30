<?php

namespace wbb\system\page\handler;

/**
 * Page handler for thread-user-posts.
 *
 * @author  Alex Thill
 */
class ThreadUserPostsPageHandler extends ThreadPageHandler {
    /**
     * @inheritDoc
     */
    public function getLink($objectID): string {
        return str_replace('thread/', 'thread-user-posts/', parent::getLink($objectID));
    }
}
