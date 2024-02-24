<?php

namespace wbb\page;

use wbb\data\post\ThreadPostList;
use wbb\data\thread\ViewableUserPostsThread;
use wcf\data\user\UserProfileList;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;

/**
 * Shows the thread page but only the posts of certain users.
 *
 * @author  Xaver
 * @license MIT License <https://mit-license.org/>
 * @package com.xaver.notifications
 */
class ThreadUserPostsPage extends ThreadPage {

    const SEPERATOR = ',';

    /**
     * ids of the users whose posts should be shown
     * @var int[]
     */
    public $userIDs = [];

    public function getUsersParam(): string {
        return 'users=' . implode(self::SEPERATOR, $this->userIDs);
    }

    /**
     * @inheritDoc
     */
    public function readParameters() {
        if (isset($_REQUEST['users'])) {
            $users = explode(',', $_REQUEST['users']);
            $ids = [];
            foreach($users as $user) {
                $id = intval($user);
                if ($id !== 0) {
                    $ids[] = $id;
                }
            }
            $this->userIDs = array_unique($ids);
            sort($this->userIDs);
            UserProfileRuntimeCache::getInstance()->cacheObjectIDs($this->userIDs);
        }

        // this must come last because setCanonicalURL is called in ancestor AbstractThreadPage::readParameters
        parent::readParameters();
        
        $this->thread = new ViewableUserPostsThread($this->thread->getDecoratedObject());
    }

    /**
     * @inheritDoc
     */
    public function assignVariables() {
        parent::assignVariables();

        $userList = UserProfileRuntimeCache::getInstance()->getObjects($this->userIDs);
        $userList = array_filter($userList, function($el) { return !is_null($el); });

        WCF::getTPL()->assign([
            'whoWroteShownUsers' => $userList,
            'whoWroteUsersParam' => $this->getUsersParam(),
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function initObjectList() {
        $this->objectList = new ThreadPostList($this->thread->getDecoratedObject());
        if ($this->sortOrder == 'DESC') {
            $this->objectList->sqlOrderBy = 'post.time DESC, post.postID DESC';
        }
        if (count($this->userIDs) === 0) {
            $this->objectList->getConditionBuilder()->add('1=0');
        } else {
            $this->objectList->getConditionBuilder()->add('userID IN (?)', [$this->userIDs]);
        }
    }

    /**
     * @inheritDoc
     */
    protected function setCanonicalURL() {
        $url = $this->getUsersParam();
        if ($this->pageNo > 1) {
            $url .= '&pageNo=' . $this->pageNo;
        }
        $this->canonicalURL = LinkHandler::getInstance()->getLink('ThreadUserPosts', [
            'application' => 'wbb',
            'object' => $this->thread,
        ], $url);
    }

    /**
     * @inheritDoc
     */
    protected function updateThreadVisit() {
        // do nothing, do not mark anything as done if viewing only the post of certain users, do not collect $200
    }
}
