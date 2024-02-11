<?php

namespace wbb\system\event\listener;

use wbb\data\election\ElectionAction;
use wbb\data\election\ElectionList;
use wbb\data\election\ParticipantList;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\WCF;

/**
 * Listener for adding election options to the quick reply on a thread page and for voting.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 */
class ElectionBotThreadPageListener implements IParameterizedEventListener {

    private ?array $elections = null;

    public function execute($eventObj, $className, $eventName, array &$parameters) {
        if (!$eventObj->thread->canReply()) return;
        
        $threadID = $eventObj->thread->threadID;
        $canStartElection = $eventObj->board->getPermission('canStartElection') ;
        if ($canStartElection || $eventObj->board->getPermission('canUseElection')) {
            WCF::getTPL()->assign(['electionBotElections' => $this->getElections($threadID)]);
        }
        if ($canStartElection) {
            $maxPhase = 0;
            foreach ($this->getElections($threadID) as $election) {
                $maxPhase = max($maxPhase, $election->phase);
            }
            $createForm = ElectionAction::getCreateForm($maxPhase);
            WCF::getTPL()->assign([
                'electionBotCreateForm' => $createForm,
                'electionBotParticipants' => ParticipantList::getThreadParticipants($threadID),
            ]);
        }
    }

    protected function getElections(int $threadID): array {
        if ($this->elections === null) {
            $this->elections = ElectionList::getThreadElections($threadID);
        }
        return $this->elections;
    }
}
