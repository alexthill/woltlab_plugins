<?php

namespace wbb\system\cronjob;

use wbb\data\election\ElectionList;
use wbb\data\election\VoteList;
use wbb\data\post\PostAction;
use wcf\data\cronjob\Cronjob;
use wcf\data\user\User;
use wcf\system\cronjob\AbstractCronjob;
use wcf\system\html\input\HtmlInputProcessor;
use wcf\system\WCF;

/**
 * Updates elections where the deadline is over and posts in the thread.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 */
class ElectionBotCronjob extends AbstractCronjob {
    /**
     * @inheritDoc
     */
    public function execute(Cronjob $cronjob) {
        parent::execute($cronjob);

        $list = new ElectionList();
        $list->getConditionBuilder()->add('isActive = 1 AND deadline <= ?', [TIME_NOW]);
        $list->readObjects();
        
        if (count($list) === 0) return;
        
        $questionMarks = str_repeat(', ?', count($list) - 1);
        $sql = "UPDATE {$list->getDatabaseTableName()}
                SET isActive = 0
                WHERE electionID IN (?$questionMarks)";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($list->getObjectIDs());
        
        $botUser = new User(WBB_ELECTION_BOT_USER_ID);
        if ($botUser === null) return;
        
        $electionsByThread = [];
        foreach ($list as $election) {
            if (array_key_exists($election->electionID, $electionsByThread)) {
                $electionsByThread[$election->threadID][] = $election;
            } else {
                $electionsByThread[$election->threadID] = [$election];
            }
        }
        foreach ($electionsByThread as $threadID => $elections) {
            $html = WCF::getLanguage()->get('wbb.electionbot.deadlineOverPost.title');
            foreach ($elections as $election) {
                $voteList = VoteList::getElectionVotes($election->electionID, $election->phase);
                $label1 = WCF::getLanguage()->getDynamicVariable('wbb.electionbot.votecount.title', ['election' => $election]);
                $label2= WCF::getLanguage()->getDynamicVariable('wbb.electionbot.votehistory.title', ['election' => $election]);
                if (count($voteList) === 0) {
                    $voteCountHtml = WCF::getLanguage()->get('wbb.electionbot.votecount.empty');
                    $voteHistoryHtml = $voteCountHtml;
                } else {
                    $voteCountHtml = $voteList->getVoteCount()->generateHtml();
                    $voteHistoryHtml = $voteList->generateHistoryHtml($election->threadID);
                }
                $html .= "<h3>$label1</h3><p>$voteCountHtml</p><h3>$label2</h3><p>$voteHistoryHtml</p>";
            }
            
            $htmlInputProcessor = new HtmlInputProcessor();
            $htmlInputProcessor->process($html, 'com.woltlab.wbb.post');
            $postAction = new PostAction([], 'create', [
                'data' => [
                    'threadID' => $threadID,
                    'subject' => '',
                    'time' => TIME_NOW,
                    'userID' => $botUser->userID,
                    'username' => $botUser->username,
                ],
                'htmlInputProcessor' => $htmlInputProcessor,
            ]);
            $resultValues = $postAction->executeAction();
        }
    }
}
