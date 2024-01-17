<?php

namespace wbb\data\election;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of elections.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
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
