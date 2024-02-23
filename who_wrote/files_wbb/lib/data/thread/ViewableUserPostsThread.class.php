<?php

namespace wbb\data\thread;

use wcf\system\request\LinkHandler;

/**
 * This just overwrites some methodes of ViewableThread for use in the ThreadUserPostsPage
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 * @package com.alexthill.who_wrote
 */
class ViewableUserPostsThread extends ViewableThread {

    /**
     * @inheritDoc
     */
    public function canReply($checkForDoublePostOnly = null) {
        // disable the quick reply editor
        return false;
    }

    /**
     * @inheritDoc
     */
    public function canEdit() {
        // prevent the wbb.thread.noPosts error from being shown and telling mods that the thread can be deleted
        return false;
    }

    /**
     * @inheritDoc
     */
    public function canMarkAsDone() {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getLink(): string {
        return LinkHandler::getInstance()->getLink('ThreadUserPosts', [
            'application' => 'wbb',
            'object' => $this,
            'forceFrontend' => true,
        ]);
    }
}
