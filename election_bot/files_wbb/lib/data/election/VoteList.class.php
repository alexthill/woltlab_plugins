<?php

namespace wbb\data\election;

use wcf\data\DatabaseObjectList;
use wcf\system\WCF;

/**
 * Represents a list of votes.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 *
 * @method      Vote        current()
 * @method      Vote[]      getObjects()
 * @method      Vote|null   search($objectID)
 * @property    Vote[]      $objects
 */
class VoteList extends DatabaseObjectList {

    public $className = Vote::class;

    public static function getLastElectionVotes(int $electionID, int $phase, string $exceptVoter = ''): VoteList {
        $list = new VoteList();
        $sql = "SELECT voteID, postID, voter, voted, time, count FROM {$list->getDatabaseTableName()}
                JOIN (
                    SELECT MAX(voteID) as maxVoteID FROM {$list->getDatabaseTableName()}
                    WHERE electionID = $electionID AND phase = $phase AND voter != ?
                    GROUP BY voter
                ) t2 ON voteID = t2.maxVoteID";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$exceptVoter]);
        $list->objects = $statement->fetchObjects(($list->objectClassName ?: $list->className));

        // use table index as array index
        $objects = $list->indexToObject = [];
        foreach ($list->objects as $object) {
            $objectID = $object->getObjectID();
            $objects[$objectID] = $object;

            $list->indexToObject[] = $objectID;
        }
        $list->objectIDs = $list->indexToObject;
        $list->objects = $objects;
        
        return $list;
    }

    public function getVoteCount(): VoteCount {
        return new VoteCount($this);
    }
}
