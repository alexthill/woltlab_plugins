<?php

namespace wbb\system\event\listener;

use wbb\data\election\ElectionAction;
use wbb\form\ThreadAddForm;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\exception\PermissionDeniedException;
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

    public function execute($eventObj, $className, $eventName, array &$parameters) {
        if ($eventObj->board->getPermission('canStartElection')) {
            $this->$eventName($eventObj);
        }
    }

    protected function assignVariables() {
        WCF::getTPL()->assign([
            'electionBotCreateForm' => $this->form ?? ElectionAction::getCreateForm(),
        ]);
    }

    protected function saved(ThreadAddForm $eventObj) {
        if ($this->form !== null) {
            $thread = $eventObj->objectAction->getReturnValues()['returnValues'];
            $data = ElectionAction::extractFormData($this->form, $thread->threadID);
            $electionAction = new ElectionAction([], 'create', $data);
            $electionAction->executeAction();
        }
    }

    protected function validate(ThreadAddForm $eventObj) {
        $this->form = ElectionAction::validateCreateForm($_POST);
        if ($this->form !== null && $this->form->hasValidationErrors()) {
            throw new UserInputException('election', 'invalid');
        }
    }
}
