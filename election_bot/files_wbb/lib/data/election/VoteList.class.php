<?php

namespace wbb\data\election;

use wbb\data\election\ParticipantList;
use wcf\data\DatabaseObjectList;
use wcf\system\WCF;
use wcf\util\StringUtil;

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

    public array $participants = [];

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
        $statement = WCF::getDB()->prepareUnmanaged($sql);
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

    public function participants(?ParticipantList $participants): static {
        $this->participants = [];
        if ($participants !== null) {
            foreach ($participants as $participant) {
                $this->participants[$participant->name] = $participant;
            }
        }
        return $this;
    }

    public static function getAllVoteCounts(int $electionID, int $upToPhase): array {
        $list = new VoteList();
        $sql = "SELECT voteID, postID, voter, voted, time, count, phase FROM {$list->getDatabaseTableName()}
            JOIN (
                SELECT MAX(voteID) as maxVoteID FROM {$list->getDatabaseTableName()}
                WHERE electionID = $electionID AND phase <= $upToPhase
                GROUP BY voter, phase
            ) t2 ON voteID = t2.maxVoteID ORDER BY voteID";
        $statement = WCF::getDB()->prepareUnmanaged($sql);
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

    public function generateMultiHistoryHtml(int $threadID): array {
        $linesByPhase = [];
        $prevVotesByPhase = [];
        foreach ($this as $vote) {
            if (!isset($linesByPhase[$vote->phase])) {
                $linesByPhase[$vote->phase] = [];
                $prevVotesByPhase[$vote->phase] = [];
            }

            $voted = $this->decorateName($vote->voted);
            $linesByPhase[$vote->phase][] = WCF::getLanguage()->getDynamicVariable(
                'wbb.electionbot.votehistory.line', [
                    'prevVoted' => $prevVotesByPhase[$vote->phase][$vote->voter] ?? null,
                    'postID' => $vote->postID,
                    'time' => $vote->time,
                    'threadID' => $threadID,
                    'voted' => $voted,
                    'voter' => $this->decorateName($vote->voter),
                ],
            );
            $prevVotesByPhase[$vote->phase][$vote->voter] = $voted;
        }
        return array_map(fn(array $lines): string => implode('<br/>', $lines), $linesByPhase);
    }

    protected function decorateName(string $name): string {
        if (array_key_exists($name, $this->participants)) {
            return $this->participants[$name]->decorateName();
        }
        return StringUtil::encodeHTML($name);
    }
}
