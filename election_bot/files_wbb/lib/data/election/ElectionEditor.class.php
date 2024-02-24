<?php

namespace wbb\data\election;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit an election.
 *
 * @author  Xaver
 * @license MIT License <https://mit-license.org/>
 * @package com.xaver.election_bot
 *
 * @method static   Election    create(array $parameters = [])
 * @method          Election    getDecoratedObject()
 * @mixin           Election
 */
class ElectionEditor extends DatabaseObjectEditor {
    /**
     * @inheritDoc
     */
    protected static $baseClass = Election::class;
}
