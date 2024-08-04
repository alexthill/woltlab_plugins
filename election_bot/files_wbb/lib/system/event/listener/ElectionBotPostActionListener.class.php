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
 * @package com.alexthill.election_bot
 */
class ElectionBotPostActionListener implements IParameterizedEventListener {

    protected ?int $threadID;

    protected array $electionData = [];

    protected array $votes = [];

    protected array $voteValues = [];

    private ?array $elections = null;

    public function execute($eventObj, $className, $eventName, array &$parameters) {
        if ($eventObj->getActionName() === 'quickReply') {
            $this->$eventName($eventObj);
        }
    }

    protected function validateAction(PostAction $eventObj): void {
        $this->threadID = $eventObj->thread->threadID;

        if (isset($_POST['parameters']['data']['electionBot'])
            && is_array($_POST['parameters']['data']['electionBot'])
        ) {
            if (!$eventObj->thread->board->getPermission('canStartElection')) {
                throw new PermissionDeniedException();
            }
            $this->processElectionBotForm($eventObj);
        }

        if (!$eventObj->thread->board->getPermission('canUseElection')) {
            return;
        }

        $elections = $this->getElections();
        $activeElections = [];
        foreach ($elections as $electionID => $election) {
            if ($election->canVote()) {
                $activeElections[] = $election;
            }
        }
        if (count($activeElections) === 0) return;

        $doc = $eventObj->getHtmlInputProcessor()->getHtmlInputNodeProcessor()->getDocument();
        $els = $doc->getElementsByTagName('woltlab-metacode');
        // we need to iterate backwards over the elements because we remove them
        for ($i = $els->length - 1; $i >= 0; $i--) {
            /** @var \DomElement */
            $el = $els->item($i);
            $tag = $el->getAttribute('data-name');
            if ($tag !== 'v' && $tag !== 'v2') continue;

            $electionID = $this->parseVoteBBCodeAttrs($el->getAttribute('data-attributes'));
            $valid = $electionID !== false;
            if ($electionID === 0) {
                $n = $tag === 'v' || count($activeElections) === 1 ? 0 : 1;
                $electionID = $activeElections[$n]->electionID;
            }
            $electionName = null;
            if ($valid && count($activeElections) > 1) {
                $electionName = $elections[$electionID]->getTitle(-1, false);
            }
            $content = StringUtil::trim($el->textContent);

            if (!$valid || !$elections[$electionID]->canVote()
                || DOMUtil::hasParent($el, 'woltlab-quote')
                || DOMUtil::hasParent($el, 'woltlab-spoiler')
            ) {
                $el->textContent = WCF::getLanguage()->getDynamicVariable(
                    'wbb.electionbot.vote.invalid',
                    ['vote' => $content, 'election' => $electionName],
                );
                $valid = false;
            } else if (strlen($content)) {
                if (strlen($content) && ($content[0] === '!' || $content[0] === '?')) {
                    $content = substr($content, 1);
                }
                $el->textContent = WCF::getLanguage()->getDynamicVariable(
                    'wbb.electionbot.vote',
                    ['vote' => $content, 'election' => $electionName],
                );
            } else {
                $el->textContent = WCF::getLanguage()->getDynamicVariable(
                    'wbb.electionbot.vote.unvote',
                    ['vote' => $content, 'election' => $electionName],
                );
            }
            DOMUtil::replaceElement($el, $doc->createElement('u'));
            if ($valid && !isset($this->votes[$electionID])) {
                $this->votes[$electionID] = $content;
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
        if (count($this->votes) === 0 && count($this->electionData) === 0) {
            return;
        }

        $postID = $eventObj->getReturnValues()['returnValues']['objectID'];
        $elections = $this->getElections();

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
                if ($electionID === 0) {
                    $electionAction = new ElectionAction([], 'create', $data);
                    $electionAction->executeAction();
                    continue;
                }
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
                    $voteAction->executeAction();
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

    /**
     * @return Election[]
     */
    protected function getElections(): array {
        if ($this->elections === null) {
            $this->elections = ElectionList::getThreadElections($this->threadID);
        }
        return $this->elections;
    }

    protected function processElectionBotForm(PostAction $eventObj): void {
        $parameters = $_POST['parameters']['data']['electionBot'];
        $elections = $this->getElections();
        $errors = [];
        foreach ($this->getElections() as $id => $election) {
            if (!isset($parameters[$id]) || !is_array($parameters[$id])) {
                continue;
            }
            $options = ElectionOptions::fromParameters($parameters[$id]);
            $options->validate($election, $errors);
            if (count($errors) === 0) {
                $this->processOptions($election, $options);
            }
        }

        if (isset($parameters[0])) {
            $form = ElectionAction::validateCreateForm($parameters[0]);
            if ($form !== null) {
                if ($form->hasValidationErrors()) {
                    $errors[] = ['id' => 0, 'html' => $form->getHtml()];
                } else {
                    $data = ElectionAction::extractFormData($form, $this->threadID);
                    $this->electionData[0] = $data;
                    $this->electionData[0]['msgs'] = [WCF::getLanguage()->getDynamicVariable(
                        'wbb.electionbot.message.create', $data
                    )];
                }
            }
        }

        if (count($errors)) {
            throw new UserInputException('electionBot', json_encode($errors));
        }

        $doc = $eventObj->getHtmlInputProcessor()->getHtmlInputNodeProcessor()->getDocument();
        $body = $doc->getElementsByTagName('body')->item(0);
        foreach ($this->electionData as $electionID => $data) {
            if (count($data['msgs']) === 0) continue;

            $election = $electionID === 0 ? null : $elections[$electionID];
            $container = Election::processMessages($doc, $data, $election);
            $body->appendChild($container);
        }
    }

    protected function processOptions(Election $election, ElectionOptions $options): void {
        $data = [];
        $msgs = [];
        if (!$election->isActive && $options->start) {
            $data['phase'] = $election->phase + 1;
            $data['isActive'] = 1;
            $data['deadline'] = $options->deadline->getTimestamp();
            $msgs[] = WCF::getLanguage()->getDynamicVariable(
                'wbb.electionbot.message.start',
                ['time' => $options->deadline, 'phase' => $data['phase']],
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
            'msgs' => $msgs,
        ];
    }

    /**
     * parses the encoded attributes of a vote bbcode and returns
     * the election id if it is a valid id
     * 0 if there is no first attributes
     * or false if there is an id but it is not a valid one
     */
    protected function parseVoteBBCodeAttrs(string $attrs): int|bool {
        if ($attrs === '') {
            return 0;
        }
        $attrs = json_decode(base64_decode($attrs));
        if (!is_array($attrs) || count($attrs) === 0) {
            return 0;
        }
        $id = intval($attrs[0]);
        if ($id === 0 || isset($this->getElections()[$id])) {
            return $id;
        }
        return false;
    }
}

