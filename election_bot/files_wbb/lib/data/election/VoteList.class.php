<?php

namespace wbb\data\election;

use wcf\data\DatabaseObjectList;
use wcf\system\WCF;

/**
 * Represents a list of votes.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 * @package com.alexthill.election_bot
 *
 * @method      Vote        current()
 * @method      Vote[]      getObjects()
 * @method      Vote|null   search($objectID)
 * @property    Vote[]      $objects
 */
class VoteList extends DatabaseObjectList {

    public $className = Vote::class;

    private bool $singlePhase = true;

    public static function getElectionVotes(int $electionID, int $phase, bool $single = true): static {
        $list = new VoteList();
        if ($single) {
            $list->getConditionBuilder()->add('electionID = ? AND phase = ?', [$electionID, $phase]);
        } else {
            $list->singlePhase = false;
            $list->getConditionBuilder()->add('electionID = ? AND phase <= ?', [$electionID, $phase]);
        }
        $list->readObjects();
        return $list;
    }

    public static function getLastElectionVotes(int $electionID, int $phase, string $exceptVoter = ''): static {
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

    public static function getAllVoteCounts(int $electionID, int $phase): array {
        $list = new VoteList();
        $sql = "SELECT voteID, postID, voter, voted, time, count, phase FROM {$list->getDatabaseTableName()}
            JOIN (
                SELECT MAX(voteID) as maxVoteID FROM {$list->getDatabaseTableName()}
                WHERE electionID = $electionID AND phase <= $phase
                GROUP BY voter, phase
            ) t2 ON voteID = t2.maxVoteID";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute();
        $votes = $statement->fetchObjects(($list->objectClassName ?: $list->className));

        $votesByPhase = [];
        foreach ($votes as $vote) {
            if (!isset($votesByPhase[$vote->phase])) {
                $votesByPhase[$vote->phase] = [];
            }
            $votesByPhase[$vote->phase][] = $vote;
        }
        $voteCounts = [];
        foreach ($votesByPhase as $phase => $votes) {
            $voteCounts[$phase] = VoteCount::fromUniqueVotes($votes);
        }
        return $voteCounts;
    }

    public function singlePhase() {
        return $this->singlePhase;
    }

    public function getVoteCount(): VoteCount {
        return VoteCount::fromVoteList($this);
    }

    public function generateHistoryHtml(int $threadID): string {
        $lines = [];
        foreach ($this as $vote) {
            $lines[] = WCF::getLanguage()->getDynamicVariable(
                'wbb.electionbot.votehistory.line',
                ['vote' => $vote, 'threadID' => $threadID],
            );
        }
        return implode('<br/>', $lines);
    }

    public function generateMultiHistoryHtml(int $threadID): array {
        $linesByPhase = [];
        foreach ($this as $vote) {
            if (!isset($linesByPhase[$vote->phase])) {
                $linesByPhase[$vote->phase] = [];
            }
            $linesByPhase[$vote->phase][] = WCF::getLanguage()->getDynamicVariable(
                'wbb.electionbot.votehistory.line',
                ['vote' => $vote, 'threadID' => $threadID],
            );
        }
        return array_map(fn(array $lines): string => implode('<br/>', $lines), $linesByPhase);
    }
}

