<?php

namespace wbb\data\election;

use wcf\data\DatabaseObject;

/**
 * Represents a vote in an election.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 * @package com.alexthill.election_bot
 *
 * @property-read   int         $voteID         unique id of the vote
 * @property-read   int         $electionID     id of the election
 * @property-read   int|null    $userID         id of the user who created this vote
 * @property-read   int|null    $postID         id of the post
 * @property-read   string      $voter          name of the voter
 * @property-read   string      $voted          name of whatever is voted or empty string if nothing is voted
 * @property-read   int         $time           time when the vote was given
 * @property-read   int         $phase          with the how many election this vote is associated
 * @property-read   int         $count          how much this vote counts
 */
class Vote extends DatabaseObject {
    /**
     * maximum value a vote is allowed to have
     * @var int
     */
    const MAX_COUNT = 1000000;

    /**
     * minimum value a vote is allowed to have
     * @var int
     */
    const MIN_COUNT = -1000000;

    /**
     * database table for this object
     * @var string
     */
    protected static $databaseTableName = 'election_vote';
}
