<?php

namespace wbb\data\election;

use wcf\data\AbstractDatabaseObjectAction;
use wcf\system\form\builder\FormDocument;
use wcf\system\form\builder\IFormDocument;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\field\BooleanFormField;
use wcf\system\form\builder\field\CheckboxFormField;
use wcf\system\form\builder\field\DateFormField;
use wcf\system\form\builder\field\IntegerFormField;
use wcf\system\form\builder\field\TextFormField;
use wcf\system\form\builder\field\dependency\ValueFormFieldDependency;
use wcf\util\StringUtil;

/**
 * Executes election-related actions.
 *
 * @author  Xaver
 * @license MIT License <https://mit-license.org/>
 * @package com.xaver.election_bot
 *
 * @method  Election            create()
 * @method  ElectionAction[]    getObjects()
 * @method  ElectionAction      getSingleObject()
 */
class ElectionAction extends AbstractDatabaseObjectAction {
    /**
     * @inheritDoc
     */
    public $className = ElectionEditor::class;

    public static function getCreateForm(int $defaultPhase = 0): IFormDocument {
        $form = FormDocument::create('electionCreateForm')
            ->prefix('election')
            ->ajax();
        $form->appendChild(
            FormContainer::create('create')
                ->label('wbb.electionbot.form.create')
                ->addClass('electionBotSection')
                ->attribute('data-id', '0')
                ->appendChildren([
                    TextFormField::create('name')
                        ->label('wbb.electionbot.form.name')
                        ->description('wbb.electionbot.form.name.description')
                        ->minimumLength(1)
                        ->maximumLength(255),
                    IntegerFormField::create('phase')
                        ->label('wbb.electionbot.form.phase')
                        ->required()
                        ->value($defaultPhase)
                        ->minimum(0)
                        ->maximum(10000), // arbitrary limit
                    TextFormField::create('name0')
                        ->label('wbb.electionbot.form.name0')
                        ->minimumLength(1)
                        ->maximumLength(255)
                        ->addDependency(ValueFormFieldDependency::create('phase')
                            ->fieldId('phase')
                            ->values(['0'])
                        ),
                    DateFormField::create('deadline')
                        ->label('wbb.electionbot.form.deadline')
                        ->required()
                        ->supportTime()
                        ->value(TIME_NOW)
                        ->earliestDate(TIME_NOW),
                    IntegerFormField::create('extension')
                        ->label('wbb.electionbot.form.extension')
                        ->description('wbb.electionbot.form.extension.description')
                        ->required()
                        ->value(1440) // minutes in day
                        ->minimum(0)
                        ->maximum(1440 * 365), // minutes in year
                    BooleanFormField::create('silenceBetweenPhases')
                        ->label('wbb.electionbot.form.silence'),
                ])
        );
        $form->markRequiredFields(false);
        $form->addDefaultButton(false);
        $form->showErrorMessage(false);
        return $form->build();
    }

    public static function validateCreateForm(array $parameters): ?IFormDocument {
        if (!isset($parameters['election_name'])) {
            return null;
        }
        $parameters['election_name'] = StringUtil::trim($parameters['election_name']);
        if ($parameters['election_name'] === '') {
            return null;
        }
        $form = static::getCreateForm();
        $form->requestData($parameters);
        $form->readValues();
        $form->validate();
        return $form;
    }

    public static function extractFormData(IFormDocument $form, int $threadID): array {
        if (!$form->didReadValues() || $form->hasValidationErrors()) {
            throw new \BadMethodCallException('Form has not been validated successfully');
        }
        $data = $form->getData();
        $data['data']['threadID'] = $threadID;
        $data['data']['isActive'] = 1;
        $data['data']['extension'] = $data['data']['extension'] * 60;
        if (!isset($data['data']['name0'])) {
            $data['data']['name0'] = '';
        }
        return $data;
    }
}
