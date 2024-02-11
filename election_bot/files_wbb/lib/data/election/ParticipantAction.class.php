<?php

namespace wbb\data\election;

use wcf\data\AbstractDatabaseObjectAction;

/**
 * Executes participant-related actions.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 *
 * @method  Participant             create()
 * @method  ParticipantEditor[]     getObjects()
 * @method  ParticipantEditor       getSingleObject()
 */
class ParticipantAction extends AbstractDatabaseObjectAction {
    /**
     * @inheritDoc
     */
    public $className = ParticipantEditor::class;
}
