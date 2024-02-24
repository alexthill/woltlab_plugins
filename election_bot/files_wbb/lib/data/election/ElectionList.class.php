<?php

namespace wbb\data\election;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of elections.
 *
 * @author  Xaver
 * @license MIT License <https://mit-license.org/>
 * @package com.xaver.election_bot
 *
 * @method      Election        current()
 * @method      Election[]      getObjects()
 * @method      Election|null   search($objectID)
 * @property    Election[]      $objects
 */
class ElectionList extends DatabaseObjectList {

    public $className = Election::class;

    public static function getThreadElections(int $threadID) {
        $list = new ElectionList();
        $list->getConditionBuilder()->add('threadID = ?', [$threadID]);
        $list->readObjects();
        return $list->getObjects();
    }
}
