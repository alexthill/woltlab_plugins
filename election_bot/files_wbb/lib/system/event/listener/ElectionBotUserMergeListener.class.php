<?php

namespace wcf\system\event\listener;

/**
 * Updates election bot information during user merging.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 * @package com.alexthill.election_bot
 */
final class ElectionBotUserMergeListener extends AbstractUserMergeListener {
    /**
     * @inheritDoc
     */
    protected $databaseTables = [
        ['name' => 'wbb{WCF_N}_election_vote', 'username' => null],
    ];
}
