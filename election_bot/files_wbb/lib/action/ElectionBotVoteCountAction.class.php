<?php

namespace wbb\action;

use wbb\data\election\ElectionList;
use wbb\data\election\ParticipantList;
use wbb\data\election\VoteList;
use wcf\system\exception\IllegalLinkException;
use wcf\system\form\builder\Psr15DialogForm;
use wcf\system\form\builder\field\CheckboxFormField;
use wcf\system\form\builder\field\IntegerFormField;
use wcf\system\form\builder\field\SingleSelectionFormField;
use wcf\system\WCF;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Handles the form for displaying vote counts
 *
 * @author  Xaver
 * @license MIT License <https://mit-license.org/>
 * @package com.xaver.election_bot
 */
class ElectionBotVoteCountAction implements RequestHandlerInterface {

    const FORM_ID = 'electionBotVoteCount';

    public function handle(ServerRequestInterface $request): ResponseInterface {
        $params = $request->getQueryParams();
        if (!isset($params['threadID'])) {
            throw new IllegalLinkException();
        }
        $threadID = $params['threadID'];
        $elections = ElectionList::getThreadElections($threadID);
        if (count($elections) === 0) {
            return new JsonResponse([
                'message' => WCF::getLanguage()->getDynamicVariable(
                    'wcf.ajax.error.invalidParameter',
                    ['fieldName' => 'threadID'],
                ),
            ], 400);
        }

        // TODO: check permissions here

        $history = !empty($params['history']);
        $formTitle = $history ? 'wbb.electionbot.votehistory.insert' : 'wbb.electionbot.votecount.insert';
        $dialogForm = $this->getForm($formTitle, $elections);

        if ($request->getMethod() === 'GET') {
            return $dialogForm->toResponse();
        }
        if ($request->getMethod() === 'POST') {
            $response = $dialogForm->validateRequest($request);
            if ($response !== null) {
                return $response;
            }

            $data = $dialogForm->getData()['data'];
            if (isset($data['electionID'])) {
                $electionID = intval($data['electionID']);
                $election = $elections[$electionID];
            } else {
                $election = array_values($elections)[0];
                $electionID = $election->electionID;
            }
            if ($data['phase'] > $election->phase) {
                return new JsonResponse([
                    'message' => WCF::getLanguage()->getDynamicVariable(
                        'wbb.electionbot.votecount.error.phase',
                        ['currentPhase' => $election->phase],
                    ),
                ], 400);
            }

            $html = '';
            if ($history) {
                $voteHistory = VoteList::getElectionVotes($electionID, $data['phase'], !$data['all'])
                    ->generateMultiHistoryHtml($threadID);
                ksort($voteHistory);
                foreach ($voteHistory as $phase => $voteHistory) {
                    $title = WCF::getLanguage()->getDynamicVariable(
                        'wbb.electionbot.votehistory.title',
                        ['election' => $election, 'phase' => $phase],
                    );
                    $html .= "<p><u>$title</u><br/>{$voteHistory}</p>";
                }
            } else {
                $participants = null;
                if ($data['color']) {
                    $participants = ParticipantList::forThread($threadID);
                }
                if ($data['all']) {
                    $voteCounts = VoteList::getAllVoteCounts($electionID, $data['phase']);
                    ksort($voteCounts);
                } else {
                    $voteCount = VoteList::getLastElectionVotes($electionID, $data['phase'])
                        ->getVoteCount();
                    $voteCounts = [$data['phase'] => $voteCount];
                }
                foreach ($voteCounts as $phase => $voteCount) {
                    $voteCount->participants($participants);
                    $title = WCF::getLanguage()->getDynamicVariable(
                        'wbb.electionbot.votecount.title',
                        ['election' => $election, 'phase' => $phase],
                    );
                    $html .= "<p><u>$title</u><br/>{$voteCount->generateHtml()}</p>";
                }
            }

            return new JsonResponse([
                'result' => ['html' => $html],
            ]);
        }
        throw new \LogicException('Unreachable');
    }

    protected function getForm(string $title, array $elections): Psr15DialogForm {
        $form = new Psr15DialogForm(static::FORM_ID, WCF::getLanguage()->get($title));

        $minPhase = 0;
        $maxPhase = 0;
        foreach ($elections as $election) {
            $minPhase = min($minPhase, $election->phase);
            $maxPhase = max($maxPhase, $election->phase);
        }

        if (count($elections) > 1) {
            $form->appendChild(
                SingleSelectionFormField::create('electionID')
                    ->label('wbb.electionbot.votecount.insert.election')
                    ->options(array_map(fn($election) => $election->getFullTitle(), $elections))
            );
        }
        $form->appendChildren([
            IntegerFormField::create('phase')
                ->label('wbb.electionbot.votecount.insert.phase')
                ->value($maxPhase)
                ->minimum($minPhase)
                ->maximum($maxPhase)
                ->required(),
            CheckboxFormField::create('color')
                ->label('wbb.electionbot.votecount.insert.color'),
            CheckboxFormField::create('all')
                ->label('wbb.electionbot.votecount.insert.all'),
        ]);
        $form->markRequiredFields(false);
        return $form->build();
    }
}

