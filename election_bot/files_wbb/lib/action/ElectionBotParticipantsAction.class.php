<?php

namespace wbb\action;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use wbb\data\election\Election;
use wbb\data\election\Participant;
use wbb\data\election\ParticipantAction;
use wbb\data\election\ParticipantList;
use wbb\data\post\PostAction;
use wbb\data\post\PostList;
use wbb\data\thread\Thread;
use wbb\system\thread\ThreadHandler;
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

/**
 * Handles the form to add, delete or modify an election participant.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 * @package com.alexthill.election_bot
 */
final class ElectionBotParticipantsAction implements RequestHandlerInterface {

    const FORM_ID = 'electionBotParticipants';

    protected ?ParticipantList $participantList = null;

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
            try {
                WCF::getDB()->beginTransaction();
                $result = $this->updateParticipant($data, $participant, $thread->threadID);
                $this->updateParticipantPost($thread->threadID);
                $this->updateThreadTitle($thread);
                WCF::getDB()->commitTransaction();
            } catch (\Exception $exception) {
                WCF::getDB()->rollBackTransaction();
                throw $exception;
            }

            return new JsonResponse(['result' => $result]);
        } else {
            throw new \LogicException('Unreachable');
        }
    }

    private function updateParticipant($data, ?Participant $participant, int $threadID): array {

        if ($participant == null) {
            $aliases = $data['aliases'];
            unset($data['delete']);
            unset($data['aliases']);
            $data['threadID'] = $threadID;
            $action = new ParticipantAction([], 'create', ['data' => $data]);
            $participant = $action->executeAction()['returnValues'];
            $participant->saveAliases($aliases);
            $data['objectId'] = $participant->participantID;
            $data['color'] = Participant::colorToMarkerClass($data['color']);
            $data['aliases'] = implode('/', $participant->getAliases());
            $result = ['action' => 'add', 'data' => $data];
        } else if ($data['delete']) {
            $participant->deleteAliases();
            $action = new ParticipantAction([$participant->participantID], 'delete', []);
            $action->executeAction();
            $result = ['action' => 'delete'];
        } else {
            $aliases = $data['aliases'];
            unset($data['delete']);
            unset($data['aliases']);
            $action = new ParticipantAction([$participant->participantID], 'update', ['data' => $data]);
            $action->executeAction();
            $participant->updateAliases($aliases);
            $data['color'] = Participant::colorToMarkerClass($data['color']);
            $data['aliases'] = implode('/', $participant->getAliases());
            $result = ['action' => 'update', 'data' => $data];
        }
        return $result;
    }

    private function getParticipantList(int $threadID): ParticipantList {
        if ($this->participantList === null) {
            $this->participantList = ParticipantList::forThread($threadID);
        }
        return $this->participantList;
    }

    private function updateThreadTitle(Thread $thread) {
        $title = $thread->getTitle();
        if (!preg_match('% [0-9]+/[0-9]+$%', $title, $matches)) {
            return;
        }
        $participantList = $this->getParticipantList($thread->threadID);
        $activeCount = $participantList->countActive();
        $allCount = count($participantList);
        $title = substr($title, 0, -strlen($matches[0])) . " $activeCount/$allCount";
        ThreadHandler::getInstance()->saveEdit(
            $thread->threadID,
            ['default' => ['topic' => $title]],
        );
    }

    private function updateParticipantPost(int $threadID): void {
        $postList = new PostList();
        $postList->sqlOffset = 1;
        $postList->sqlLimit = 1;
        $postList->getConditionBuilder()->add('threadID = ?', [$threadID]);
        $postList->readObjects();
        $post = $postList->getSingleObject();
        if ($post !== null && $post->userID === WBB_ELECTION_BOT_USER_ID) {
            $postAction = new PostAction([$post], 'update', [
                'isEdit' => true,
                'showEditNote' => true,
                'data' => [
                    'message' => $this->getParticipantList($threadID)->generateHtmlListWithAliases(),
                    'editReason' => WCF::getLanguage()->get('wbb.electionbot.participantListPost.update'),
                    'editCount' => $post->editCount + 1,
                    'editor' => WCF::getUser()->username,
                    'editorID' => WCF::getUser()->userID,
                    'lastEditTime' => TIME_NOW,
                ]
            ]);
            $postAction->executeAction();
        }
    }

    private function getForm(?Participant $participant): Psr15DialogForm {
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
                ->autoFocus()
                ->required(),
            TextFormField::create('aliases')
                ->label('wbb.electionbot.form.participant.aliases')
                ->value($participant?->getAliasList() ?? '')
                ->placeholder($participant?->getAliasList() ?? '')
                ->minimumLength(1)
                ->maximumLength(Election::MAX_VOTER_LENGTH),
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

