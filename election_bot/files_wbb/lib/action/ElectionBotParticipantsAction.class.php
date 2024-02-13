<?php

namespace wbb\action;

use wbb\data\election\Election;
use wbb\data\election\Participant;
use wbb\data\election\ParticipantAction;
use wbb\data\election\ParticipantList;
use wbb\data\post\PostAction;
use wbb\data\post\PostList;
use wbb\data\thread\Thread;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\form\builder\IFormDocument;
use wcf\system\form\builder\Psr15DialogForm;
use wcf\system\form\builder\data\processor\CustomFormDataProcessor;
use wcf\system\form\builder\field\BooleanFormField;
use wcf\system\form\builder\field\CheckboxFormField;
use wcf\system\form\builder\field\SelectFormField;
use wcf\system\form\builder\field\TextFormField;
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
class ElectionBotParticipantsAction implements RequestHandlerInterface {

    const FORM_ID = 'electionBotParticipants';

    public function handle(ServerRequestInterface $request): ResponseInterface {
        $params = $request->getQueryParams();
        if (!isset($params['threadID'])) {
            throw new IllegalLinkException();
        }
        $thread = new Thread($params['threadID']);
        if ($thread->threadID === 0) {
            throw new IllegalLinkException();
        }
        if (!$thread->getBoard()->getPermission('canStartElection')) {
            throw new PermissionDeniedException();
        }

        $objectId = intval($params['objectId'] ?? 0);
        $participant = $objectId > 0 ? new Participant($objectId) : null;
        if ($participant !== null && $participant->participantID === 0) {
            throw new IllegalLinkException();
        }
        $dialogForm = $this->getForm($participant);

        if ($request->getMethod() === 'GET') {
            return $dialogForm->toResponse();
        } else if ($request->getMethod() === 'POST') {
            $response = $dialogForm->validateRequest($request);
            if ($response !== null) {
                return $response;
            }

            $data = $dialogForm->getData()['data'];

            if ($objectId === 0) {
                $data['threadID'] = $thread->threadID;
                $action = new ParticipantAction([], 'create', ['data' => $data]);
                $returnValues = $action->executeAction();
                $data['objectId'] = $returnValues['returnValues']->participantID;
                $data['color'] = Participant::colorToMarkerClass($data['color']);
                $result = ['action' => 'add', 'data' => $data];
            } else if ($data['delete']) {
                $actionName = 'delete';
                $action = new ParticipantAction([$objectId], 'delete', []);
                $action->executeAction();
                $result = ['action' => 'delete'];
            } else {
                $actionName = 'update';
                unset($data['delete']);
                $action = new ParticipantAction([$objectId], 'update', ['data' => $data]);
                $action->executeAction();
                $data['color'] = Participant::colorToMarkerClass($data['color']);
                $result = ['action' => 'update', 'data' => $data];
            }

            $this->updateParticipantPost($thread->threadID);
            return new JsonResponse(['result' => $result]);
        } else {
            throw new \LogicException('Unreachable');
        }
    }

    protected function updateParticipantPost(int $threadID): void {
        $postList = new PostList();
        $postList->sqlOffset = 1;
        $postList->sqlLimit = 1;
        $postList->getConditionBuilder()->add('threadID = ?', [$threadID]);
        $postList->readObjects();
        $post = $postList->getSingleObject();
        if ($post !== null && $post->userID === WBB_ELECTION_BOT_USER_ID) {
            $participants = ParticipantList::forThread($threadID);
            $postAction = new PostAction([$post], 'update', ['data' => [
                'message' => $participants->generateHtmlList(),
            ]]);
            $postAction->executeAction();
        }
    }

    protected function getForm(?Participant $participant): Psr15DialogForm {
        $form = new Psr15DialogForm(
            static::FORM_ID,
            WCF::getLanguage()->get('wbb.electionbot.form.participant.' . (is_null($participant) ? 'add' : 'edit')),
        );
        $form->appendChildren([
            TextFormField::create('name')
                ->label('wcf.global.name')
                ->value($participant?->name ?? '')
                ->placeholder($participant?->name ?? '')
                ->minimumLength(1)
                ->maximumLength(Election::MAX_VOTER_LENGTH)
                ->required(),
            SelectFormField::create('color')
                ->label('wbb.electionbot.form.participant.marker')
                ->options(Participant::COLOR_OPTIONS)
                // set value to null ("no selection") if color is the default value 0
                ->value($participant?->color ?: null),
            TextFormField::create('extra')
                ->label('wbb.electionbot.form.participant.extra')
                ->value($participant?->extra ?? '')
                ->maximumLength(255),
            CheckboxFormField::create('active')
                ->label('wbb.electionbot.form.participant.active')
                ->value($participant?->active ?? true),
        ]);
        if (!is_null($participant)) {
            $form->appendChild(
                BooleanFormField::create('delete')
                    ->label('wcf.global.button.delete')
                    ->description('wcf.dialog.confirmation.cannotBeUndone')
            );
        }
        $form->markRequiredFields(false);
        $form->build();
        $form->getDataHandler()->addProcessor(new CustomFormDataProcessor(
            'color',
            static function (IFormDocument $document, array $parameters) {
                $parameters['data']['color'] = intval($parameters['data']['color'] ?? 0);
                return $parameters;
            }
        ));
        return $form;
    }
}

