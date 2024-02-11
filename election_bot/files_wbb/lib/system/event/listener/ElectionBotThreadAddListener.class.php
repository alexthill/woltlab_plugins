<?php

namespace wbb\system\event\listener;

use wbb\data\election\Election;
use wbb\data\election\ElectionAction;
use wbb\data\election\ParticipantList;
use wbb\form\ThreadAddForm;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\exception\UserInputException;
use wcf\system\form\builder\IFormDocument;
use wcf\system\WCF;

/**
 * Listener for adding an election in ThreadAddForm.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 */
class ElectionBotThreadAddListener implements IParameterizedEventListener {
    
    protected ?IFormDocument $form = null;

    protected string $electionParticipants = '';

    protected bool $electionParticipantsStrict = true;
    
    protected ?ParticipantList $participantList = null;

    public function execute($eventObj, $className, $eventName, array &$parameters) {
        if ($eventObj->board === null || $eventObj->board->getPermission('canStartElection')) {
            $this->$eventName($eventObj);
        }
    }

    protected function readFormParameters() {
        if (isset($_POST['electionParticipants'])) {
            $this->electionParticipants = $_POST['electionParticipants'];
        }
        if (empty($_POST['electionParticipantsStrict'])) {
            $this->electionParticipantsStrict = false;
        }
    }

    protected function assignVariables() {
        WCF::getTPL()->assign([
            'electionBotCreateForm' => $this->form ?? ElectionAction::getCreateForm(),
            'electionParticipants' => $this->electionParticipants,
            'electionParticipantsStrict' => $this->electionParticipantsStrict,
            'maxLength' => Election::MAX_VOTER_LENGTH,
            'maxCount' => ParticipantList::MAX_PARTICIPANTS,
        ]);
    }

    protected function saved(ThreadAddForm $eventObj) {
        $thread = $eventObj->objectAction->getReturnValues()['returnValues'];
        
        if ($this->form !== null) {
            $data = ElectionAction::extractFormData($this->form, $thread->threadID);
            $electionAction = new ElectionAction([], 'create', $data);
            $electionAction->executeAction();
        }
        
        if ($this->participantList !== null) {
            $this->participantList->save($thread->threadID);
        }
    }

    protected function validate(ThreadAddForm $eventObj) {
        $this->form = ElectionAction::validateCreateForm($_POST);
        if ($this->form !== null && $this->form->hasValidationErrors()) {
            throw new UserInputException('election', 'invalid');
        }
        
        $this->participantList = ParticipantList::fromNsvInput($this->electionParticipants);
        $error = $this->participantList->validate($this->electionParticipantsStrict);
        $this->electionParticipants = $this->participantList->getValidatedInput();
        if ($error !== '') {
            throw new UserInputException('electionParticipants', $error);
        }
    }
}
