<?php

namespace wbb\data\election;

use wcf\data\AbstractDatabaseObjectAction;

/**
 * Executes vote-related actions.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
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
