<?php

namespace wbb\system\event\listener;

use wbb\data\election\Election;
use wbb\data\election\ElectionAction;
use wbb\data\election\ElectionList;
use wbb\data\election\ElectionOptions;
use wbb\data\election\VoteAction;
use wbb\data\election\VoteList;
use wbb\data\post\PostAction;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\WCF;
use wcf\util\DOMUtil;
use wcf\util\StringUtil;

/**
 * Listener for adding election options to the quick reply on a thread page.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 */
class ElectionBotPostActionListener implements IParameterizedEventListener {

    protected array $electionData = [];
    
    protected array $votes = [];
    
    protected array $voteValues = [];

    private $elections;

    public function execute($eventObj, $className, $eventName, array &$parameters) {
        if ($eventObj->getActionName() === 'quickReply') {
            $this->$eventName($eventObj);
        }
    }

    protected function validateAction(PostAction $eventObj): void {
        if (isset($_POST['parameters'])
            && isset($_POST['parameters']['data'])
            && isset($_POST['parameters']['data']['electionBot'])
            && is_array($_POST['parameters']['data']['electionBot'])
        ) {
            if (!$eventObj->thread->board->getPermission('canStartElection')) {
                throw new PermissionDeniedException();
            }
            $this->processElectionBotForm($eventObj);
        }
        
        if (!$eventObj->thread->board->getPermission('canUseElection') || WCF::getUser() === null) {
            return;
        }
        
        $elections = $this->getElections($eventObj);
        $defaultElectionID = 0;
        foreach ($elections as $electionID => $election) {
            if ($election->isActive && $election->deadline > TIME_NOW) {
                $defaultElectionID = $electionID;
            }
        }
        if ($defaultElectionID === 0) return;
        
        $doc = $eventObj->getHtmlInputProcessor()->getHtmlInputNodeProcessor()->getDocument();
        $els = $doc->getElementsByTagName('woltlab-metacode');
        // we need to iterate backwards over the elements because we remove them
        for ($i = $els->length; --$i >= 0; ) {
            $el = $els->item($i);
            if ($el->getAttribute('data-name') !== 'v') continue;
            
            $content = StringUtil::trim($el->textContent);
            $valid = true;
            if (DOMUtil::hasParent($el, 'woltlab-quote') || DOMUtil::hasParent($el, 'woltlab-spoiler')) {
                $el->textContent = WCF::getLanguage()->getDynamicVariable(
                    'wbb.electionbot.vote.invalid',
                    ['vote' => $content],
                );
                $valid = false;
            } else if (strlen($content)) {
                if (strlen($content) > 1 && $content[0] === '!') {
                    $content = substr($content, 1);
                }
                $el->textContent = WCF::getLanguage()->getDynamicVariable(
                    'wbb.electionbot.vote',
                    ['vote' => $content],
                );
            } else {
                $el->textContent = WCF::getLanguage()->get('wbb.electionbot.vote.unvote');
            }
            DOMUtil::replaceElement($el, $doc->createElement('u'));
            if ($valid && !isset($this->votes[$defaultElectionID])) {
                $this->votes[$defaultElectionID] = $content;
            }
        }
        
        if (count($this->votes) === 0) return;
        
        $body = $doc->getElementsByTagName('body')->item(0);
        $divider = $doc->createElement('p');
        $divider->textContent = '--------';
        $body->appendChild($divider);
        
        $voter = WCF::getUser()->username;
        foreach ($this->votes as $electionID => $voted) {
            $sql = "SELECT count FROM wbb1_election_voter WHERE electionID = ? AND voter = ?";
            $statement = WCF::getDB()->prepare($sql, 1);
            $statement->execute([$electionID, WCF::getUser()->username]);
            $count = $statement->fetchSingleColumn();
            $count = $count === false ? 1 : $count;
            $this->voteValues[$electionID] = $count;
            
            $election = $elections[$electionID];
            $voteCountHtml = VoteList::getLastElectionVotes($electionID, $election->phase, $voter)
                ->getVoteCount()
                ->generateHtmlWithNewVote($voter, $voted, $count);
            $fragment = $doc->createDocumentFragment();
            $fragment->appendXML($voteCountHtml);
            $p = $doc->createElement('p');
            $p->appendChild($fragment);
            $spoiler = $doc->createElement('woltlab-spoiler');
            $spoiler->appendChild($p);
            $label = WCF::getLanguage()->getDynamicVariable(
                'wbb.electionbot.votecount.title',
                ['election' => $election],
            );
            $spoiler->setAttribute('data-label', $label);
            $body->appendChild($spoiler);
            
            $diff = $election->deadline - TIME_NOW;
            $s = $diff % 60;
            if ($s < 10) $s = '0' . $s;
            $m = intdiv($diff, 60) % 60;
            if ($m < 10) $m = '0' . $m;
            $h = intdiv($diff, 3600);
            if ($h < 10) $h = '0' . $h;
            $timeLeft = WCF::getLanguage()->getDynamicVariable(
                'wbb.electionbot.votecount.timeLeft',
                ['h' => $h, 'm' => $m, 's' => $s],
            );
            $body->appendChild($doc->createTextNode($timeLeft));
        }
    }

    protected function finalizeAction(PostAction $eventObj): void {
        $postID = $eventObj->getReturnValues()['returnValues']['objectID'];
        $elections = $this->getElections($eventObj);
        
        WCF::getDB()->beginTransaction();
        try {
            foreach ($this->votes as $electionID => $voted) {
                $voteAction = new VoteAction([], 'create', ['data' => [
                    'electionId' => $electionID,
                    'userID' => WCF::getUser()->userID,
                    'postID' => $postID,
                    'voter' => WCF::getUser()->username,
                    'voted' => $voted,
                    'time' => TIME_NOW,
                    'phase' => $elections[$electionID]->phase,
                    'count' => $this->voteValues[$electionID],
                ]]);
                $vote = $voteAction->executeAction();
            }
            
            foreach ($this->electionData as $electionID => $data) {
                if (count($data['data'])) {
                    $electionAction = new ElectionAction([$electionID], 'update', ['data' => $data['data']]);
                    $electionAction->executeAction();
                }
                foreach ($data['addVotes'] as $vote) {
                    $voteAction = new VoteAction([], 'create', ['data' => [
                        'electionId' => $electionID,
                        'userID' => WCF::getUser()->userID,
                        'postID' => $postID,
                        'voter' => $vote->voter,
                        'voted' => $vote->voted,
                        'time' => TIME_NOW,
                        'phase' => $data['data']['phase'] ?? $elections[$electionID]->phase,
                        'count' => $vote->count,
                    ]]);
                    $vote = $voteAction->executeAction();
                }
                foreach ($data['addVoteValues'] as $vote) {
                    $sql = "INSERT INTO wbb1_election_voter (electionID, voter, count)
                            VALUES (?, ?, ?)
                            ON DUPLICATE KEY UPDATE count = VALUES(count)";
                    $statement = WCF::getDB()->prepare($sql);
                    $statement->execute([$electionID, $vote->voter, $vote->count]);
                }
            }
            WCF::getDB()->commitTransaction();
        } catch (\Exception $exception) {
            WCF::getDB()->rollBackTransaction();
            throw $exception;
        }
    }
    
    protected function getElections(PostAction $eventObj): array {
        if ($this->elections === null) {
            $this->elections = ElectionList::getThreadElections($eventObj->thread->threadID);
        }
        return $this->elections;
    }

    protected function processElectionBotForm(PostAction $eventObj): void {
        $parameters = $_POST['parameters']['data']['electionBot'];
        $electionMsgs = [];
        $errors = [];
        foreach ($this->getElections($eventObj) as $election) {
            $id = $election->electionID;
            if (!isset($parameters[$id]) || !is_array($parameters[$id])) continue;
            
            $options = ElectionOptions::fromParameters($parameters[$id]);
            $options->validate($election, $errors);
            
            if (count($errors) === 0) {
                $electionMsgs[$election->electionID] = $this->processOptions($election, $options);
            }
        }
        
        if (count($errors)) {
            throw new UserInputException('electionBot', json_encode($errors));
        }
        
        $doc = $eventObj->getHtmlInputProcessor()->getHtmlInputNodeProcessor()->getDocument();
        $body = $doc->getElementsByTagName('body')->item(0);
        foreach ($electionMsgs as $electionID => $msgs) {
            if (count($msgs) === 0) continue;
            
            $container = $doc->createElement('p');
            $el = $doc->createElement('span');
            $el->textContent = '---- ' . $this->elections[$electionID]->name . ' ----';
            $container->appendChild($el);
            foreach ($msgs as $msg) {
                $fragment = $doc->createDocumentFragment();
                $fragment->appendXML($msg);
                $container->appendChild($doc->createElement('br'));
                $container->appendChild($fragment);
            }
            $body->appendChild($container);
        }
    }

    protected function processOptions(Election $election, ElectionOptions $options): array {
        $data = [];
        $msgs = [];
        if (!$election->isActive && $options->start) {
            $data['phase'] = $election->phase + 1;
            $data['isActive'] = 1;
            $data['deadline'] = $options->deadline->getTimestamp();  
            $msgs[] = WCF::getLanguage()->getDynamicVariable(
                'wbb.electionbot.message.start',
                ['time' => $options->deadline],
            );
        }
        if ($election->isActive && $options->end) {
            $data['isActive'] = 0;
            $msgs[] = WCF::getLanguage()->get('wbb.electionbot.message.end');
        }
        if ($options->changeDeadline && $options->deadline !== null) {
            $data['deadline'] = $options->deadline->getTimestamp();
            $msgs[] = WCF::getLanguage()->getDynamicVariable(
                'wbb.electionbot.message.deadline',
                ['time' => $options->deadline],
            );
        }
        foreach ($options->addVotes as $vote) {
            $msgs[] = WCF::getLanguage()->getDynamicVariable(
                'wbb.electionbot.message.addVote',
                ['vote' => $vote],
            );
        }
        foreach ($options->addVoteValues as $voteValue) {
            $msgs[] = WCF::getLanguage()->getDynamicVariable(
                'wbb.electionbot.message.addVoteValue',
                ['vote' => $voteValue],
            );
        }
        
        $this->electionData[$election->electionID] = [
            'data' => $data,
            'addVotes' => $options->addVotes,
            'addVoteValues' => $options->addVoteValues,
        ];
        
        return $msgs;
    }
}
