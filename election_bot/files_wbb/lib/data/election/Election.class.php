<?php

namespace wbb\data\election;

use wcf\data\DatabaseObject;
use wcf\util\StringUtil;

/**
 * Represents an election.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 *
 * @property-read   int         $electionID     unique id of the election
 * @property-read   int         $threadID       id of the thread this election is associated with
 * @property-read   string      $name           user given name of the election
 * @property-read   string      $name0          user given name for pahse 0 of the election
 * @property-read   int         $deadline       time when the election ends
 * @property-read   int         $extension      extension of the deadline in seconds when a new phase starts
 * @property-read   string      $phase          the how many election this is 
 * @property-read   bool        $isActive       if currently an election is active
 * @property-read   bool        $silenceBetweenPhases   if users are forbidden to post between phases
 */
class Election extends DatabaseObject {

    const MAX_VOTER_LENGTH = 100;

    const MAX_VOTED_LENGTH = 100;

    private ?\DateTimeImmutable $nextDeadline = null;

    /**
     * gets the deadline or extended deadline if inactive as \DateTimeImmutable object
     */
    public function getNextDeadline(): \DateTimeImmutable {
        if ($this->nextDeadline === null) {
            $time = $this->isActive ? $this->deadline : $this->deadline + $this->extension;
            $this->nextDeadline = new \DateTimeImmutable('@' . $time);
        }
        return $this->nextDeadline;
    }

    /**
     * returns whether this election is active and the deadline has not been passed
     */
    public function canVote(): bool {
        return $this->isActive && $this->deadline > TIME_NOW;
    }

    /**
     * name of the election for a given phase
     * the name for phase 0 can be different than for the other phases
     * @param   int     $phase  the phase or the current phase if -1 or null
     * @param   bool    $encode if true the return value is html encoded
     * @return  string
     */
    public function getTitle(int $phase = -1, bool $encode = true): string {
        if ($phase < 0) {
            $phase = $this->phase;
        }
        $title = $phase === 0 && $this->name0 !== '' ? $this->name0 : $this->name;
        return $encode ? StringUtil::encodeHTML($title) : $title;
    }

    /**
     * full title including the name for phase 0 if set
     * @return  string
     */
    public function getFullTitle(): string {
        $title = $this->name;
        if ($this->name0 !== '') {
            $title = "$this->name0/$title";
        }
        return StringUtil::encodeHTML($title);
    }
}

