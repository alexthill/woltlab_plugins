<?php

namespace wbb\system\event\listener;

use wbb\data\election\ElectionAction;
use wbb\form\ThreadAddForm;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\WCF;

/**
 * Listener for adding an election in ThreadAddForm.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 */
class ElectionBotThreadAddListener implements IParameterizedEventListener {
    
    protected $enable = true;
    
    protected $electionName = '';
    
    protected $electionDeadline;
    
    protected $electionExtension = 0;

    public function execute($eventObj, $className, $eventName, array &$parameters) {
        $this->$eventName($eventObj);
    }

    protected function assignVariables() {
        WCF::getTPL()->assign([
            'electionEnable' => $this->enable,
            'electionName' => $this->electionName,
            'electionDeadline' => $this->electionDeadline?->format(\DateTimeInterface::ATOM),
            'electionExtension' => $this->electionExtension,
        ]);
    }

    protected function readFormParameters() {
        $this->enable = !empty($_POST['enableElectionBot']);
        if (!$this->enable) return;
        
        if (isset($_POST['electionName'])) {
            $this->electionName = $_POST['electionName'];
        }
        if (!empty($_POST['electionDeadline'])) {
            $this->electionDeadline = \DateTime::createFromFormat(\DateTimeInterface::ATOM, $_POST['electionDeadline']);
        }
        if (isset($_POST['electionExtension'])) {
            $this->electionExtension = intval($_POST['electionExtension']);
        }
    }

    protected function saved(ThreadAddForm $eventObj) {
        if (!$this->enable) return;
        
        $thread = $eventObj->objectAction->getReturnValues()['returnValues'];
        $electionAction = new ElectionAction([], 'create', ['data' => [
            'threadID' => $thread->threadID,
            'name' => $this->electionName,
            'deadline' => $this->electionDeadline->getTimestamp(),
            'extension' => $this->electionExtension * 60,
            'phase' => 0,
            'isActive' => 1,
        ]]);
        $electionAction->executeAction();
        
        $this->enable = true;
        $this->electionName = '';
        $this->electionDeadline = null;
        $this->electionExtension = 0;
    }

    protected function validate(ThreadAddForm $eventObj) {
        if (!$this->enable) return;
        
        if (!$eventObj->board->getPermission('canStartElection')) {
            throw new PermissionDeniedException();
        }
        
        if ($this->electionName === '' || strlen($this->electionName) > 255) {
            throw new UserInputException('electionName', 'invalid');
        }
        if ($this->electionDeadline === null) {
            throw new UserInputException('electionDeadline', 'empty');
        }
        if ($this->electionDeadline === false || $this->electionDeadline->getTimestamp() < TIME_NOW) {
            throw new UserInputException('electionDeadline', 'invalid');
        }
        if ($this->electionExtension < 0 || $this->electionExtension > 60 * 24 * 366) {
            throw new UserInputException('electionExtension', 'invalid');
        }
    }
}
