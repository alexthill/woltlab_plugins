{if !$__wbbThreadQuickReply|empty && $electionBotCreateForm|isset}
<div class="messageTabMenuContent" id="electionBot_{if $wysiwygSelector|isset}{$wysiwygSelector}{else}text{/if}">
    {foreach from=$electionBotElections item=$election}
        {assign var=id value=$election->electionID}
        <section class="section electionBotSection" data-id="{$id}">
            <h2 class="sectionTitle">{@$election->getFullTitle()}</h2>
            <dl>
                <dt><button class="electionAddBtn button small" data-add-name="Vote" data-election-id="{@$id}">{lang}wbb.electionbot.form.addVote{/lang}</button></dt>
                <dd><ul id="electionAddContainerVote{@$id}"></ul></dd>
            </dl>
            <dl>
                <dt><button class="electionAddBtn button small" data-add-name="VoteValue" data-election-id="{@$id}">{lang}wbb.electionbot.form.addVoteValue{/lang}</button></dt>
                <dd><ul id="electionAddContainerVoteValue{@$id}"></ul></dd>
            </dl>
            {if $election->isActive}
            <dl>
                <dt>{lang}wbb.electionbot.form.deadlineDisplay{/lang} {time time=$election->deadline type='plainTime'}</dt>
                <dd>
                    <label><input type="checkbox" name="electionEnd"> {lang}wbb.electionbot.form.end{/lang}</label>
                </dd>
            </dl>
            <dl>
                <dt><label><input type="checkbox" name="electionChangeDeadline" id="showDeadlineChanger{@$id}"> {lang}wbb.electionbot.form.deadlineChange{/lang}</label></dt>
            {else}
            <dl>
                <dt></dt>
                <dd>
                    <label><input type="checkbox" name="electionStart" id="showDeadlineChanger{@$id}"> {lang}wbb.electionbot.form.start{/lang}</label>
                </dd>
            {/if}
                <dd>
                    <input type="datetime" id="electionDeadline{@$id}" name="electionDeadline" value="{time time=$election->getNextDeadline() type='machine'}" class="medium" data-enable-if-checked="showDeadlineChanger{@$id}">
                </dd>
            </dl>
        </section>
    {/foreach}
    <div id="electionCreateFormOuter">
        {@$electionBotCreateForm->getHtml()}
    </div>
    <section class="section electionBotSection">
        <h2 class="sectionTitle">{lang}wbb.electionbot.form.participants{/lang}</h2>
        <dl>
            <dt></dt>
            <dd>
                <template id="electionAddTemplateParticipant">
                    <tr>
                        <td class="columnIcon">
                            <a role="button" class="electionParticipantEditBtn" title="{lang}wcf.global.button.edit{/lang}" aria-label="{lang}wcf.global.button.edit{/lang}">
                                <fa-icon name="pencil" size="16" solid="" aria-hidden="true" translate="no"></fa-icon>
                            </a>
                        </td>
                        <td><span class="name"></span></td>
                        <td><span class="extra"></span></td>
                    </tr>
                </template>
                <table id="electionParticipants" class="table">
                {foreach from=$electionBotParticipants key=$objectId item=$participant}
                    <tr data-object-id="{@$objectId}">
                        <td class="columnIcon">
                            <a role="button" class="electionParticipantEditBtn" title="{lang}wcf.global.button.edit{/lang}" aria-label="{lang}wcf.global.button.edit{/lang}">
                                <fa-icon name="pencil" size="16" solid="" aria-hidden="true" translate="no"></fa-icon>
                            </a>
                        </td>
                        <td><span class="name {@$participant->getMarkerClass()}"{if !$participant->active} style="text-decoration-line:line-through;"{/if}>{$participant->name}</span></td>
                        <td><span class="extra">{$participant->extra}</span></td>
                    </tr>
                {foreachelse}
                    <tr>
                        <td colspan="3">{lang}wcf.global.noItems{/lang}</td>
                    </tr>
                {/foreach}
                </table>
            </dd>
        </dl>
        <dl>
            <dt></dt>
            <dd>
                <button id="electionParticipantAddBtn" class="button small">{lang}wbb.electionbot.form.participant.add{/lang}</button>
            </dd>
        </dl>
    </section>
</div>

<template id="electionAddTemplate">
    <li>
        <input type="hidden" name="electionAdd" data-collector="">
        <div>
            <button class="electionAddRemoveBtn button small" title="{lang}wcf.global.button.delete{/lang}" aria-label="{lang}wcf.global.button.delete{/lang}">
                <fa-icon name="xmark" size="16" solid="" aria-hidden="true" translate="no"></fa-icon>
            </button>
        </div>
    </li>
</template>
<template id="electionAddTemplateVote">
    <span>{lang}wbb.electionbot.form.addVote.description{/lang} <input type="text" data-name="voter" maxlength="255"> <input type="text" data-name="voted" maxlength="255"> <input type="number" class="short" value="1" data-name="count"></span>
</template>
<template id="electionAddTemplateVoteValue">
    <span>{lang}wbb.electionbot.form.addVoteValue.description{/lang} <input type="text" data-name="voter" maxlength="255"> <input type="number" class="short" value="1" data-name="count"></span>
</template>

<script data-relocate="true">
require([
    'WoltLabSuite/Core/Ajax/Backend',
    'WoltLabSuite/Core/Component/Dialog',
], function (Backend, Dialog) {
    'use strict';

    function participantEditFn() {
        const url = new URL('{link controller="ElectionBotParticipants" application="wbb" encode=false}{/link}');
        url.searchParams.set('threadID', document.body.dataset.threadId);
        if (this.tagName !== 'BUTTON') {
            url.searchParams.set('objectId', this.closest('tr').dataset.objectId);
        }
        Dialog.dialogFactory().usingFormBuilder().fromEndpoint(url).then(
            ({ ok, result }) => {
                if (!ok) return;
                if (result.action === 'delete') {
                    this.closest('tr').remove();
                    return;
                }
                let node;
                if (result.action === 'update') {
                    node = this.closest('tr');
                } else if (result.action === 'add') {
                    const template = document.getElementById('electionAddTemplateParticipant');
                    node = template.content.cloneNode(true).firstElementChild;
                    node.querySelector('.electionParticipantEditBtn').addEventListener('click', participantEditFn);
                    node.setAttribute('data-object-id', result.data.objectId);
                    document.querySelector('#electionParticipants tbody').appendChild(node);
                }
                const nameNode = node.querySelector('.name');
                nameNode.textContent = result.data.name;
                nameNode.className = 'name ' + result.data.color;
                if (result.data.active) {
                    nameNode.style.textDecorationLine = '';
                } else {
                    nameNode.style.textDecorationLine = 'line-through';
                }
                node.querySelector('.extra').textContent = result.data.extra;
            },
            async ({ response }) => {
                const json = await response.json();
                Dialog.dialogFactory().fromHtml(json.message).asAlert().show('{jslang}wcf.global.error.title{/jslang}');
            }
        );
    }

    for (const btn of document.getElementsByClassName('electionParticipantEditBtn')) {
        btn.addEventListener('click', participantEditFn);
    }
    document.getElementById('electionParticipantAddBtn').addEventListener('click', participantEditFn);
});

require([
    'WoltLabSuite/Core/Event/Handler',
    'WoltLabSuite/Core/Component/Ckeditor/Event',
    'WoltLabSuite/Core/Dom/Util',
], function (EventHandler, CkeditorEvent, DomUtil) {
    'use strict';

    const wysiwygId = '{if $wysiwygSelector|isset}{$wysiwygSelector}{else}text{/if}';
    const ckeditor5 = document.getElementById(wysiwygId);
    const container = document.getElementById('electionBot_' + wysiwygId);
    // sections needs to be a live HTMLCollection
    const sections = container.getElementsByClassName('electionBotSection');
    
    for (const el of container.querySelectorAll('[data-enable-if-checked]')) {
        const checkbox = document.getElementById(el.dataset.enableIfChecked);
        if (el.classList.contains('inputDatePicker')) {
            const el2 = document.getElementById(el.id + 'DatePicker');
            checkbox.addEventListener('change', () => {
                el.disabled = !checkbox.checked;
                el2.disabled = !checkbox.checked;
            });
            el2.disabled = !checkbox.checked;
        } else {
            checkbox.addEventListener('change', () => {
                el.disabled = !checkbox.checked;
            });
        }
        el.disabled = !checkbox.checked;
    }
    
    const addTemplate = document.getElementById('electionAddTemplate');
    for (const addBtn of container.querySelectorAll('.electionAddBtn')) {
        const name = addBtn.dataset.addName;
        const electionId = addBtn.dataset.electionId;
        const addContainer = document.getElementById('electionAddContainer' + name + electionId);
        const subTemplate = document.getElementById('electionAddTemplate' + name);
        addBtn.addEventListener('click', () => {
            const newNode = addTemplate.content.cloneNode(true).firstElementChild;
            const subNode = subTemplate.content.cloneNode(true).firstElementChild;
            addContainer.appendChild(newNode);
            newNode.querySelector('input[name="electionAdd"]').name += name;
            newNode.querySelector('div').prepend(subNode);
            newNode.querySelector('.electionAddRemoveBtn').addEventListener('click', () => {
                addContainer.removeChild(newNode);
            });
        });
    }
    
    function initSectionToggle(section, hidden = true) {
        const title = section.querySelector('.sectionTitle');
        if (!title) return;
        const toggle = document.createElement('span');
        toggle.textContent = '>';
        toggle.className = 'toggle';
        title.prepend(toggle);
        title.addEventListener('click', () => {
            section.classList.toggle('collapsed');
        });
        if (hidden) {
            section.classList.add('collapsed');
        }
    }
    for (const section of sections) {
        initSectionToggle(section);
    }

    let sectionsWithSentData;
    CkeditorEvent.listenToCkeditor(ckeditor5).collectMetaData((payload) => {
        const data = {};
        sectionsWithSentData = [];
        for (const section of sections) {
            if (section.dataset.id === undefined) continue;
            
            const sectionData = {};
            for (const el of section.querySelectorAll('input[name]')) {
                innerError(el, ''); // resets the innerError if it was set previously
                if (el.disabled) continue;
                if (el.type === 'checkbox') {
                    if (el.checked) {
                        sectionData[el.name] = 1;
                    }
                } else {
                    if (el.dataset.hasOwnProperty('collector')) {
                        const value = {};
                        for (const subEl of el.parentElement.querySelectorAll('input[data-name]')) {
                            value[subEl.dataset.name] = subEl.value;
                        }
                        el.value = JSON.stringify(value);
                    }
                    if (!sectionData.hasOwnProperty(el.name)) {
                        sectionData[el.name] = el.value;
                    } else if (Array.isArray(sectionData[el.name])) {
                        sectionData[el.name].push(el.value);
                    } else {
                        sectionData[el.name] = [sectionData[el.name], el.value];
                    }
                }
            }
            if (Object.keys(sectionData).length !== 0) {
                data[section.dataset.id] = sectionData;
                sectionsWithSentData.push(section);
            }
        }
        if (Object.keys(data).length !== 0) {
            payload.metaData.electionBot = data;
        }
    }).reset((payload) => {
        for (const section of sectionsWithSentData) {
            if (section.dataset.id !== '0') {
                const header = section.querySelector('h2').textContent.substring(1);
                section.textContent = header + ': {jslang}wbb.electionbot.form.reloadPage{/jslang}';
            }
        }
    });

    EventHandler.add('com.woltlab.wcf.ckeditor5', 'handleError_' + wysiwygId, (data) => {
        if (data.returnValues.fieldName !== 'electionBot') return;
        
        const errors = JSON.parse(data.returnValues.realErrorMessage);
        data.returnValues.realErrorMessage = '{jslang}wbb.electionbot.form.error{/jslang}';
        for (const error of errors) {
            if (error.id === 0) {
                const outer = document.getElementById('electionCreateFormOuter');
                outer.innerHTML = error.html;
                initSectionToggle(outer.querySelector('.electionBotSection'), false);
            } else {
                const el = container.querySelectorAll('.electionBotSection[data-id="' + error.id + '"] input[name="' + error.field + '"]')[error.n];
                if (el) {
                    innerError(el, error.msg);
                } else {
                    console.error('cannot find input element ' + error.n + ' with name ' + error.field + ' for election ' + error.id);
                }
            }
        }
    });

    function innerError(el, msg) {
        if (el.type === 'hidden') {
            el = el.nextElementSibling ?? el;
        }
        DomUtil.innerError(el, msg);
    }
});
</script>
{/if}

