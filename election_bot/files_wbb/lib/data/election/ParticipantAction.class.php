<?php

namespace wbb\data\election;

use wbb\action\ElectionBotSuggestionsAction;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\ISearchAction;

/**
 * Executes participant-related actions.
 *
 * @author  Xaver
 * @license MIT License <https://mit-license.org/>
 * @package com.xaver.election_bot
 *
 * @method  Participant             create()
 * @method  ParticipantEditor[]     getObjects()
 * @method  ParticipantEditor       getSingleObject()
 */
class ParticipantAction extends AbstractDatabaseObjectAction implements ISearchAction {
    /**
     * @inheritDoc
     */
    public $className = ParticipantEditor::class;

    public function validateGetSearchResultList() {
        $this->readString('searchString', false, 'data');
        $this->readInteger('threadID', false, 'data');
    }

    public function getSearchResultList() {
        $query = $this->parameters['data']['searchString'];
        $threadID = $this->parameters['data']['threadID'];
        return ElectionBotSuggestionsAction::getMatches($query, $threadID);
    }
}

