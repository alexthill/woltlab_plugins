<?php

namespace wbb\data\election;

use wcf\data\AbstractDatabaseObjectAction;

/**
 * Executes election-related actions.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 *
 * @method  Election            create()
 * @method  ElectionAction[]    getObjects()
 * @method  ElectionAction      getSingleObject()
 */
class ElectionAction extends AbstractDatabaseObjectAction {
    /**
     * @inheritDoc
     */
    public $className = ElectionEditor::class;
}
