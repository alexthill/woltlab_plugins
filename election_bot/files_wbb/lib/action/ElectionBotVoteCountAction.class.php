<?php

namespace wbb\action;

use wbb\data\election\ElectionList;
use wbb\data\election\VoteList;
use wcf\system\exception\IllegalLinkException;
use wcf\system\form\builder\data\processor\CustomFormDataProcessor;
use wcf\system\form\builder\IFormDocument;
use wcf\system\form\builder\Psr15DialogForm;
use wcf\system\form\builder\field\CheckboxFormField;
use wcf\system\form\builder\field\HiddenFormField;
use wcf\system\form\builder\field\IntegerFormField;
use wcf\system\form\builder\field\IFormField;
use wcf\system\form\builder\field\validation\FormFieldValidationError;
use wcf\system\form\builder\field\validation\FormFieldValidator;
use wcf\system\WCF;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Handles the form for displaying vote counts
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 */
class ElectionBotVoteCountAction implements RequestHandlerInterface {

    const FORM_ID = 'electionBotVoteCount';

    public function handle(ServerRequestInterface $request): ResponseInterface {
        $params = $request->getQueryParams();
        if (!isset($params['threadID'])) {
            throw new IllegalLinkException();
        }
        $elections = ElectionList::getThreadElections($params['threadID']);
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
        } elseif ($request->getMethod() === 'POST') {
            $response = $dialogForm->validateRequest($request);
            if ($response !== null) {
                return $response;
            }

            $data = $dialogForm->getData()['data'];
            $electionID = $data['electionID'];
            $election = $elections[$electionID];
            $html = '';
            if ($history) {
                $voteHistory = VoteList::getElectionVotes($electionID, $data['phase'], !$data['all'])
                    ->generateMultiHistoryHtml($params['threadID']);
                ksort($voteHistory);
                foreach ($voteHistory as $phase => $voteHistory) {
                    $title = WCF::getLanguage()->getDynamicVariable(
                        'wbb.electionbot.votehistory.title',
                        ['election' => $election, 'phase' => $phase],
                    );
                    $html .= "<p><u>$title</u><br/>{$voteHistory}";
                }
            } else {
                if ($data['all']) {
                    $voteCounts = VoteList::getAllVoteCounts($electionID, $data['phase']);
                    ksort($voteCounts);
                } else {
                    $voteCount = VoteList::getLastElectionVotes($electionID, $data['phase'])->getVoteCount();
                    $voteCounts = [$data['phase'] => $voteCount];
                }
                foreach ($voteCounts as $phase => $voteCount) {
                    $title = WCF::getLanguage()->getDynamicVariable(
                        'wbb.electionbot.votecount.title',
                        ['election' => $election, 'phase' => $phase],
                    );
                    $html .= "<p><u>$title</u><br/>{$voteCount->generateHtml()}";
                }
            }

            return new JsonResponse([
                'result' => ['html' => $html],
            ]);
        } else {
            throw new \LogicException('Unreachable');
        }
    }

    protected function getForm(string $title, array $elections): Psr15DialogForm {
        $form = new Psr15DialogForm(static::FORM_ID, WCF::getLanguage()->get($title));
        
        $maxPhase = 0;
        foreach ($elections as $election) {
            $maxPhase = max($maxPhase, $election->phase);
        }
        
        $electionIdFormField = HiddenFormField::create('electionID')
            ->value(array_values($elections)[0]->electionID)
            ->required();
        $electionIdFormField->addValidator(new FormFieldValidator('electionID', function (IFormField $formField) use ($elections) {
            if (!array_key_exists($formField->getValue(), $elections)) {
                $formField->addValidationError(
                    new FormFieldValidationError('electionID', 'wbb.electionbot.votecount.insert.election.error.doesNotExists')
                );
            }
        }));
        $form->appendChildren([
            $electionIdFormField,
            IntegerFormField::create('phase')
                ->label('wbb.electionbot.votecount.insert.phase')
                ->value($maxPhase)
                ->minimum(0)
                ->maximum($maxPhase)
                ->required(),
            CheckboxFormField::create('all')
                ->label('wbb.electionbot.votecount.insert.all'),
        ]);
        $form->getDataHandler()->addProcessor(
            new CustomFormDataProcessor('electionID',
                static function (IFormDocument $document, array $parameters) {
                    $parameters['data']['electionID'] = intval($parameters['data']['electionID']);
                    return $parameters;
                }
            )
        );
        $form->markRequiredFields(false);
        return $form->build();
    }
}
