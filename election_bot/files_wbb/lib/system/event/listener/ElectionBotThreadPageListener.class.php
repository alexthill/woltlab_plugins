<?php

namespace wbb\system\event\listener;

use wbb\data\election\ElectionList;
use wbb\data\election\ElectionOptions;
use wbb\page\ThreadPage;
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
        if ($eventObj->thread->canReply() && ($eventObj->board->getPermission('canStartElection') || $eventObj->board->getPermission('canUseElection'))) {
            $elections = ElectionList::getThreadElections($eventObj->thread->threadID);
            foreach ($elections as $election) {
                $election->setDeadlineObj();
            }
            WCF::getTPL()->assign(['electionBotElections' => $elections]);
        }
    }
}
