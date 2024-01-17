<?php

namespace wbb\data\election;

use wcf\data\DatabaseObject;

/**
 * Represents a vote in an election.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 *
 * @property-read   int         $electionID     unique id of the election
 * @property-read   string      $voter          name of the voter
 * @property-read   string|null $voted          name of whatever is voted or null
 * @property-read   int         $time           time when the vote was given
 * @property-read   int         $phase          with the how many election this vote is associated
 * @property-read   int         $count          how much this vote counts
 */
class Vote extends DatabaseObject {
    /**
     * database table for this object
     * @var string
     */
    protected static $databaseTableName = 'election_vote';
}
