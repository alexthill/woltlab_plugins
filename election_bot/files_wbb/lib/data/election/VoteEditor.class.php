<?php

namespace wbb\data\election;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit a vote.
 *
 * @author  Xaver
 * @license MIT License <https://mit-license.org/>
 * @package com.xaver.election_bot
 *
 * @method static   Vote    create(array $parameters = [])
 * @method          Vote    getDecoratedObject()
 * @mixin           Vote
 */
class VoteEditor extends DatabaseObjectEditor {
    /**
     * @inheritDoc
     */
    protected static $baseClass = Vote::class;
}
