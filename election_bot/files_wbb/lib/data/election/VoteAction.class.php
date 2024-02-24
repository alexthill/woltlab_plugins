<?php

namespace wbb\data\election;

use wcf\data\AbstractDatabaseObjectAction;

/**
 * Executes vote-related actions.
 *
 * @author  Xaver
 * @license MIT License <https://mit-license.org/>
 * @package com.xaver.election_bot
 *
 * @method  Vote            create()
 * @method  VoteAction[]    getObjects()
 * @method  VoteAction      getSingleObject()
 */
class VoteAction extends AbstractDatabaseObjectAction {
    /**
     * @inheritDoc
     */
    public $className = VoteEditor::class;
}
