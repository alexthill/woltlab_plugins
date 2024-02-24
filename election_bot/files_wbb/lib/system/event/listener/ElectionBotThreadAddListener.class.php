<?php

namespace wbb\system\event\listener;

use wbb\data\election\Election;
use wbb\data\election\ElectionAction;
use wbb\data\election\ParticipantList;
use wbb\data\post\PostAction;
use wbb\form\ThreadAddForm;
use wcf\data\user\User;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\exception\UserInputException;
use wcf\system\form\builder\IFormDocument;
use wcf\system\WCF;

/**
 * Listener for adding an election in ThreadAddForm.
 *
 * @author  Xaver
 * @license MIT License <https://mit-license.org/>
 * @package com.xaver.election_bot
 */
class ElectionBotThreadAddListener implements IParameterizedEventListener {

    protected ?IFormDocument $form = null;

    protected string $electionParticipants = '';

    protected bool $electionParticipantsStrict = true;

    protected bool $electionParticipantsPost = true;

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
        $this->electionParticipantsStrict = !empty($_POST['electionParticipantsStrict']);
        $this->electionParticipantsPost = !empty($_POST['electionParticipantsPost']);
    }

    protected function assignVariables() {
        WCF::getTPL()->assign([
            'electionBotCreateForm' => $this->form ?? ElectionAction::getCreateForm(),
            'electionParticipants' => $this->electionParticipants,
            'electionParticipantsStrict' => $this->electionParticipantsStrict,
            'electionParticipantsPost' => $this->electionParticipantsPost,
            'maxLength' => Election::MAX_VOTER_LENGTH,
            'maxCount' => ParticipantList::MAX_PARTICIPANTS,
        ]);
    }

    protected function save(ThreadAddForm $eventObj) {
        if ($this->form !== null) {
            $data = $this->form->getData();
            $data['msgs'] = [WCF::getLanguage()->getDynamicVariable(
                'wbb.electionbot.message.create', $data
            )];
            $doc = $eventObj->htmlInputProcessor->getHtmlInputNodeProcessor()->getDocument();
            $doc->getElementsByTagName('body')->item(0)
                ->appendChild(Election::processMessages($doc, $data));
        }
    }

    protected function saved(ThreadAddForm $eventObj) {
        $thread = $eventObj->objectAction->getReturnValues()['returnValues'];
        $threadID = $thread->threadID;

        if ($this->form !== null) {
            $data = ElectionAction::extractFormData($this->form, $threadID);
            $electionAction = new ElectionAction([], 'create', $data);
            $electionAction->executeAction();
        }

        if (count($this->participantList)) {
            $this->participantList->save($threadID);
        }

        if ($this->electionParticipantsPost) {
            $user = new User(WBB_ELECTION_BOT_USER_ID);
            // if WBB_ELECTION_BOT_USER_ID is not a valid userID $user->username will be null
            // in this case we set userID to null and username to 'guest'
            $action = new PostAction([], 'create', ['data' => [
                'message' => ParticipantList::forThread($threadID)->generateHtmlList(),
                'threadID' => $threadID,
                'time' => TIME_NOW,
                'userID' => $user->userID ?: null,
                'username' => $user->username ?? WCF::getLanguage()->get('wcf.user.guest'),
            ]]);
            $action->executeAction();
        }
    }

    protected function validate() {
        $this->form = ElectionAction::validateCreateForm($_POST);
        if ($this->form !== null && $this->form->hasValidationErrors()) {
            throw new UserInputException('election', 'invalid');
        }

        $this->participantList = ParticipantList::fromNsvInput($this->electionParticipants);
        if (count($this->participantList)) {
            $error = $this->participantList->validate($this->electionParticipantsStrict);
            $this->electionParticipants = $this->participantList->getValidatedInput();
            if ($error !== '') {
                throw new UserInputException('electionParticipants', $error);
            }
        }
    }
}


