<?php

namespace wbb\system\cronjob;

use wbb\data\election\Election;
use wbb\data\election\ElectionList;
use wbb\data\election\ParticipantList;
use wbb\data\election\VoteList;
use wbb\data\post\PostAction;
use wcf\data\cronjob\Cronjob;
use wcf\data\user\User;
use wcf\system\cronjob\AbstractCronjob;
use wcf\system\html\input\HtmlInputProcessor;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Updates elections where the deadline is over and posts in the thread.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 * @package com.alexthill.election_bot
 */
final class ElectionBotCronjob extends AbstractCronjob {
    private ?User $botUser = null;

    /**
     * @inheritDoc
     */
    public function execute(Cronjob $cronjob) {
        parent::execute($cronjob);

        if ($cronjob->failCount > 10) {
            return;
        }

        if (date('m-d') === '04-01') {
            $this->botUser = new User(WBB_ELECTION_BOT_USER_ID_TEST);
        } else {
            $this->botUser = new User(WBB_ELECTION_BOT_USER_ID);
        }

        $this->execute_reminders();
        $this->execute_deadlines();
    }

    private function execute_reminders(): void {
        $list = new ElectionList();
        $list->getConditionBuilder()->add('isActive AND NOT isReminded AND deadline - reminder <= ? AND deadline > ?', [TIME_NOW, TIME_NOW]);
        $list->readObjects();
        if (count($list) === 0) return;

        $questionMarks = str_repeat(', ?', count($list) - 1);
        $sql = "UPDATE {$list->getDatabaseTableName()}
                SET isReminded = true
                WHERE electionID IN (?$questionMarks)";
        $statement = WCF::getDB()->prepareUnmanaged($sql);
        $statement->execute($list->getObjectIDs());

        $title = WCF::getLanguage()->get('wbb.electionbot.deadlineReminderPost.title');
        $this->post($list, $title);
    }

    private function execute_deadlines(): void {
        $list = new ElectionList();
        $list->getConditionBuilder()->add('isActive = 1 AND deadline <= ?', [TIME_NOW]);
        $list->readObjects();
        if (count($list) === 0) return;

        $questionMarks = str_repeat(', ?', count($list) - 1);
        $sql = "UPDATE {$list->getDatabaseTableName()}
                SET isActive = false
                WHERE electionID IN (?$questionMarks)";
        $statement = WCF::getDB()->prepareUnmanaged($sql);
        $statement->execute($list->getObjectIDs());

        $title = WCF::getLanguage()->get('wbb.electionbot.deadlineOverPost.title');
        $this->post($list, $title);
    }

    private function post(ElectionList $list, string $title): void {
        $electionsByThread = [];
        foreach ($list as $election) {
            if (array_key_exists($election->threadID, $electionsByThread)) {
                $electionsByThread[$election->threadID][] = $election;
            } else {
                $electionsByThread[$election->threadID] = [$election];
            }
        }

        foreach ($electionsByThread as $threadID => $elections) {
            $html = $title;
            try {
                foreach ($elections as $election) {
                    $html .= $this->generateHtmlForElection($threadID, $election);
                }
            } catch (\Exception $exception) {
                $html .= '<h2>' . WCF::getLanguage()->get('wcf.global.exception.title') . '</h2>'
                    . str_replace("\n", '<br/>', StringUtil::encodeHTML($exception));
            }

            $htmlInputProcessor = new HtmlInputProcessor();
            $htmlInputProcessor->process($html, 'com.woltlab.wbb.post');
            $postAction = new PostAction([], 'create', [
                'data' => [
                    'threadID' => $threadID,
                    'subject' => '',
                    'time' => TIME_NOW,
                    'userID' => $this->botUser->userID ?: null,
                    'username' => $this->botUser->username ?? WCF::getLanguage()->get('wcf.user.guest'),
                ],
                'htmlInputProcessor' => $htmlInputProcessor,
            ]);
            $postAction->executeAction();
        }
    }

    private function generateHtmlForElection(int $threadID, Election $election): string {
        $voteList = VoteList::getElectionVotes($election->electionID, $election->phase);
        $label1 = WCF::getLanguage()->getDynamicVariable(
            'wbb.electionbot.votecount.title',
            ['election' => $election],
        );
        $label2 = WCF::getLanguage()->getDynamicVariable(
            'wbb.electionbot.votehistory.title',
            ['election' => $election],
        );

        if (count($voteList) === 0) {
            $voteCountHtml = WCF::getLanguage()->get('wbb.electionbot.votecount.empty');
            $voteHistoryHtml = $voteCountHtml;
        } else {
            $voteCountHtml = $voteList->getVoteCount()->generateHtml();
            $voteHistoryHtml = $voteList->generateHistoryHtml($election->threadID);
        }

        $noVoters = ParticipantList::nonVotersForElectionPhase($threadID, $election->electionID, $election->phase);
        $noVotersHtml = '';
        if (count($noVoters->objects) !== 0) {
            $noVoters = WCF::getLanguage()->getDynamicVariable(
                'wbb.electionbot.votecount.novote',
                ['array' => $noVoters->objects],
            );
            $noVotersHtml = "<p>$noVoters</p>";
        }

        $timeLeft = "";
        if ($election->deadline > TIME_NOW) {
            $timeLeft = "<p>{$election->formatTimeLeft()}</p>";
        }

        return "<h3>$label1</h3>"
            . "<p>$voteCountHtml</p>"
            . "<p>$noVotersHtml</p>"
            . "<woltlab-spoiler data-label=\"$label2\">$voteHistoryHtml</woltlab-spoiler>"
            . "$timeLeft";
    }
}
