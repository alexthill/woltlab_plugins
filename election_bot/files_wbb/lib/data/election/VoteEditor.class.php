<?php

namespace wbb\data\election;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit a vote.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
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
