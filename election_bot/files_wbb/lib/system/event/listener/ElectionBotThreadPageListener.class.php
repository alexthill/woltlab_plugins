<?php

namespace wbb\system\event\listener;

use wbb\data\election\ElectionAction;
use wbb\data\election\ElectionList;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\WCF;

/**
 * Listener for adding election options to the quick reply on a thread page and for voting.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 */
class ElectionBotThreadPageListener implements IParameterizedEventListener {

    public function execute($eventObj, $className, $eventName, array &$parameters) {
        if (!$eventObj->thread->canReply()) return;

        $canStartElection = $eventObj->board->getPermission('canStartElection') ;
        if ($canStartElection || $eventObj->board->getPermission('canUseElection')) {
            $elections = ElectionList::getThreadElections($eventObj->thread->threadID);
            $createForm = $canStartElection ? ElectionAction::getCreateForm() : null;
            WCF::getTPL()->assign([
                'electionBotElections' => $elections,
                'electionBotCreateForm' => $createForm,
            ]);
        }
    }
}
