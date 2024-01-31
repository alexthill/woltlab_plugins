<?php

namespace wbb\data\election;

use wcf\data\DatabaseObject;

/**
 * Represents an election.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 *
 * @property-read   int         $electionID     unique id of the election
 * @property-read   int         $threadID       id of the thread this election is associated with
 * @property-read   string      $name           user given name of the election
 * @property-read   int         $deadline       time when the election ends
 * @property-read   int         $extension      time the election should be extended when a new election starts
 * @property-read   string      $phase          the how many election this is 
 * @property-read   bool        $isActive       if currently an election is active
 */
class Election extends DatabaseObject {

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
}
