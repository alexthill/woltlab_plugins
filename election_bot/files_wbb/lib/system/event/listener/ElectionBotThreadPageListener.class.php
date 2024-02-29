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
 * @author  Xaver
 * @license MIT License <https://mit-license.org/>
 * @package com.xaver.election_bot
 */
class ElectionBotThreadPageListener implements IParameterizedEventListener {

    public function execute($eventObj, $className, $eventName, array &$parameters) {
        if (!$eventObj->thread->canReply()) return;

        $canStartElection = $eventObj->board->getPermission('canStartElection') ;
        if ($canStartElection || $eventObj->board->getPermission('canUseElection')) {
            $threadID = $eventObj->thread->threadID;
            $elections = ElectionList::getThreadElections($threadID);
            WCF::getTPL()->assign(['electionBotElections' => $elections]);

            if ($canStartElection) {
                $maxPhase = 0;
                foreach ($elections as $election) {
                    $maxPhase = max($maxPhase, $election->phase);
                }
                WCF::getTPL()->assign([
                    'electionBotCreateForm' => ElectionAction::getCreateForm(false, $maxPhase),
                    'electionBotParticipants' => ParticipantList::forThread($threadID),
                ]);
            }
        }
    }
}

