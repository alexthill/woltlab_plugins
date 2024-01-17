<?php

namespace wbb\data\election;

use wcf\data\DatabaseObject;

/**
 * Represents an election.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 *
 * @property-read   int         $electionID         unique id of the election
 * @property-read   int         $threadID           id of the thread this election is associated with
 * @property-read   string      $name               user given name of the election
 * @property-read   int         $deadline           time when the election ends
 * @property-read   int         $extension          time the election should be extended when a new election starts
 * @property-read   string      $phase              the how many election this is 
 * @property-read   bool        $isActive           if currently an election is active
 * @property        DateTime    $deadlineObj        contains either the deadline or the next deadline as a DateTime object
 */
class Election extends DatabaseObject {

    public $deadlineObj;

    /**
     * initializes the $deadlineObj property
     */
    public function setDeadlineObj() {
        if ($this->isActive) {
            $this->deadlineObj = (new \DateTime())->setTimestamp($this->deadline);
        } else {
            $this->deadlineObj = (new \DateTime())->setTimestamp($this->deadline + $this->extension);
        }
    }
}
