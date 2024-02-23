<?php

namespace wbb\system\page\handler;

/**
 * Page handler for thread-user-posts.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 * @package com.alexthill.notifications
 */
class ThreadUserPostsPageHandler extends ThreadPageHandler {
    /**
     * @inheritDoc
     */
    public function getLink($objectID): string {
        return str_replace('thread/', 'thread-user-posts/', parent::getLink($objectID));
    }
}
