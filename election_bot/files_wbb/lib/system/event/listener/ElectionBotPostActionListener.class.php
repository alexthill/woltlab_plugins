<?php

namespace wbb\system\event\listener;

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

    protected $elections;

    protected $election_data = [];

    protected $errors = [];
    
    protected $votes = [];

    public function execute($eventObj, $className, $eventName, array &$parameters) {
        if ($eventObj->getActionName() === 'quickReply') {
            $this->$eventName($eventObj);
        }
    }

    protected function validateAction(PostAction $eventObj) {
        if (isset($_POST['parameters'])
            && isset($_POST['parameters']['data'])
            && isset($_POST['parameters']['data']['electionBot'])
            && is_array($_POST['parameters']['data']['electionBot'])
        ) {
            if (!$eventObj->thread->board->getPermission('canStartElection')) throw new PermissionDeniedException();
            $this->processElectionBotForm($eventObj);
        }
        
        if (!$eventObj->thread->board->getPermission('canUseElection') || WCF::getUser() === null) return;
        
        $elections = $this->getElections($eventObj);
        $defaultElectionID = 0;
        foreach ($elections as $electionID => $election) {
            if ($election->isActive && $election->deadline > TIME_NOW) {
                $defaultElectionID = $electionID;
            }
        }
        if ($defaultElectionID === 0) return;
        
        $doc = $eventObj->getHtmlInputProcessor()->getHtmlInputNodeProcessor()->getDocument();
        foreach ($doc->getElementsByTagName('woltlab-metacode') as $el) {
            $type = $el->getAttribute('data-name');
            if ($type !== 'v' || DOMUtil::hasParent($el, 'woltlab-quote') || DOMUtil::hasParent($el, 'woltlab-spoiler')) {
                continue;
            }
            
            $content = StringUtil::trim($el->textContent);
            if (strlen($content) > 1 && $content[0] === '!') {
                $content = substr($content, 1);
            }
            $el->textContent = $content;
            $el->setAttribute('data-attributes', base64_encode('[1]'));
            $this->votes[$defaultElectionID] = $content;
        }
        
        if (count($this->votes) === 0) return;
        
        $body = $doc->getElementsByTagName('body')->item(0);
        $divider = $doc->createElement('p');
        $divider->textContent = '--------';
        $body->appendChild($divider);
            
        $voter = WCF::getUser()->username;
        foreach ($this->votes as $electionID => $voted) {
            $election = $elections[$electionID];
            $voteCountHtml = VoteList::getLastElectionVotes($electionID, $election->phase, $voter)
                ->getVoteCount()
                ->generateHtmlWithNewVote($voter, $voted, 1);
            $fragment = $doc->createDocumentFragment();
            $fragment->appendXML($voteCountHtml);
            $p = $doc->createElement('p');
            $p->appendChild($fragment);
            $spoiler = $doc->createElement('woltlab-spoiler');
            $spoiler->appendChild($p);
            $label = WCF::getLanguage()->getDynamicVariable('wbb.electionbot.votecount.title', ['election' => $election]);
            $spoiler->setAttribute('data-label', $label);
            $body->appendChild($spoiler);
            
            $diff = $election->deadline - TIME_NOW;
            $s = $diff % 60;
            if ($s < 10) $s = '0' . $s;
            $m = intdiv($diff, 60) % 60;
            if ($m < 10) $m = '0' . $m;
            $h = intdiv($diff, 3600);
            if ($h < 10) $h = '0' . $h;
            $timeLeft = WCF::getLanguage()->getDynamicVariable('wbb.electionbot.votecount.timeLeft', ['h' => $h, 'm' => $m, 's' => $s]);
            $body->appendChild($doc->createTextNode($timeLeft));
        }
    }

    protected function finalizeAction(PostAction $eventObj) {
        foreach ($this->votes as $electionID => $voted) {
            $voteAction = new VoteAction([], 'create', ['data' => [
                'electionId' => $electionID,
                'userID' => WCF::getUser()->userID,
                'postID' => $eventObj->getReturnValues()['returnValues']['objectID'],
                'voter' => WCF::getUser()->username,
                'voted' => $voted,
                'time' => TIME_NOW,
                'phase' => $this->getElections($eventObj)[$electionID]->phase,
                'count' => 1,
            ]]);
            $vote = $voteAction->executeAction();
        }
        
        foreach ($this->election_data as $electionID => $data) {
            $electionAction = new ElectionAction([$electionID], 'update', ['data' => $data]);
            $electionAction->executeAction();
        }
    }
    
    protected function getElections(PostAction $eventObj) {
        if ($this->elections === null) {
            $this->elections = ElectionList::getThreadElections($eventObj->thread->threadID);
        }
        return $this->elections;
    }

    protected function processElectionBotForm(PostAction $eventObj) {
        $parameters = $_POST['parameters']['data']['electionBot'];
        $electionMsgs = [];
        foreach ($this->getElections($eventObj) as $election) {
            $id = $election->electionID;
            if (!isset($parameters[$id]) || !is_array($parameters[$id])) continue;
            
            $options = ElectionOptions::fromParameters($parameters[$id]);
            if ($options->deadline === null) {
                if ($options->changeDeadline || $options->start) {
                    $msg = WCF::getLanguage()->get('wcf.global.form.error.empty');
                    $this->addError($id, 'electionDeadline', $msg);
                }
            } else if ($options->deadline === false || $options->deadline ->getTimestamp() < TIME_NOW) {
                $msg = WCF::getLanguage()->getDynamicVariable('wbb.electionbot.add.deadline.error.invalid');
                $this->addError($id, 'electionDeadline', $msg);
            }
            
            if (count($this->errors) === 0) {
                $electionMsgs[$election->electionID] = $this->processOptions($election, $options);
            }
        }
        
        if (count($this->errors)) {
            throw new UserInputException('electionBot', json_encode($this->errors));
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
                $container->appendChild($doc->createElement('br'));
                $el = $doc->createElement('span');
                $el->textContent = $msg;
                $container->appendChild($el);
            }
            $body->appendChild($container);
        }
    }

    protected function processOptions($election, $options) {
        $data = [];
        $msgs = [];
        if (!$election->isActive && $options->start) {
            $data['phase'] = $election->phase + 1;
            $data['isActive'] = 1;
            $data['deadline'] = $options->deadline->getTimestamp();  
            $msgs[] = WCF::getLanguage()->getDynamicVariable('wbb.electionbot.message.start', ['time' => $options->deadline]);
        }
        if ($election->isActive && $options->end) {
            $data['isActive'] = 0;
            $msgs[] = WCF::getLanguage()->get('wbb.electionbot.message.end');
        }
        if ($options->changeDeadline && $options->deadline !== null) {
            $data['deadline'] = $options->deadline->getTimestamp();
            $msgs[] = WCF::getLanguage()->getDynamicVariable('wbb.electionbot.message.deadline', ['time' => $options->deadline]);
        }
        
        if (count($data)) {
            $this->election_data[$election->electionID] = $data;
        }
        return $msgs;
    }

    protected function addError($id, $field, $msg) {
        $this->errors[] = [
            'id' => $id,
            'field' => $field,
            'msg' => $msg,
        ];
    }
}
