<?php

namespace wbb\data\election;

use wcf\data\DatabaseObject;
use wcf\system\WCF;
use wcf\util\StringUtil;

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
 * @property-read   float       $count          the value of this vote
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
     * number of decimal digits a vote value is rounded to
     * @var int
     */
    const DECIMAL_DIGITS = 2;

    /**
     * database table for this object
     * @var string
     */
    protected static $databaseTableName = 'election_vote';

    /**
    * checks and parses a string as a vote value
    * @return float|string the parsed value as float or an error message
    */
    public static function checkValue(string $count): float | string {
        $count = str_replace(',', '.', StringUtil::trim($count));
        if (!is_numeric($count)) {
            // this a joke
            return WCF::getLanguage()->get('wcf.map.route.error.not_found');
        }
        $value = round(floatval($count), Vote::DECIMAL_DIGITS);
        if ($value < Vote::MIN_COUNT || $value > Vote::MAX_COUNT) {
            return WCF::getLanguage()->getDynamicVariable(
                'wbb.electionbot.form.addVote.error.countOutsideRange',
                ['min' => Vote::MIN_COUNT, 'max' => Vote::MAX_COUNT],
            );
        }
        return $value;
    }
}
