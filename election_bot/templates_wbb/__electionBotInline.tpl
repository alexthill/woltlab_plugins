{if !$__wbbThreadQuickReply|empty && $electionBotElections|isset}
<div class="messageTabMenuContent" id="electionBot_{if $wysiwygSelector|isset}{$wysiwygSelector}{else}text{/if}">
    {foreach from=$electionBotElections item=$election}
        {assign var=id value=$election->electionID}
        <section class="section electionBotSection" data-id="{$id}">
            <h2 class="sectionTitle">{$election->name}</h2>
            
            {if $election->isActive}
            <dl>
                <dt>{lang}wbb.electionbot.form.deadlineDisplay{/lang} {time time=$election->deadline type='plainTime'}</dt>
                <dd>
                    <label><input type="checkbox" name="electionEnd"> {lang}wbb.electionbot.form.end{/lang}</label>
                </dd>
            </dl>
            <dl>
                <dt><label><input type="checkbox" name="electionChangeDeadline" id="showDeadlineChanger{$id}"> {lang}wbb.electionbot.form.deadlineChange{/lang}</label></dt>
            {else}
            <dl>
                <dt></dt>
                <dd>
                    <label><input type="checkbox" name="electionStart" id="showDeadlineChanger{$id}"> {lang}wbb.electionbot.form.start{/lang}</label>
            {/if}
                <dd>
                    <input type="datetime" id="electionDeadline{$id}" name="electionDeadline" value="{time time=$election->deadlineObj type='machine'}" class="medium" data-enable-if-checked="showDeadlineChanger{$id}">
                    <small>{lang}wbb.electionbot.form.deadline.description{/lang}</small>
                </dd>
            </dl>
        </section>
    {/foreach}
</div>
<script data-relocate="true">
    require(['WoltLabSuite/Core/Event/Handler', 'WoltLabSuite/Core/Component/Ckeditor/Event', 'WoltLabSuite/Core/Dom/Util'], function (EventHandler, CkeditorEvent, DomUtil) {
        'use strict';

        const wysiwygId = '{if $wysiwygSelector|isset}{$wysiwygSelector}{else}text{/if}';
        const ckeditor5 = document.getElementById(wysiwygId);
        const container = document.getElementById('electionBot_' + wysiwygId);
        let sections = container.querySelectorAll('.electionBotSection');
        
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

        let sendData = false;
        CkeditorEvent.listenToCkeditor(ckeditor5).collectMetaData((payload) => {
            const data = {};
            sendData = false;
            for (const section of sections) {
                const sectionData = {};
                for (const el of section.querySelectorAll('input[name]')) {
                    innerError(el, ''); // resets the innerError if it was set previously
                    if (el.disabled) continue;
                    if (el.type === 'checkbox') {
                        if (el.checked) {
                            sectionData[el.name] = 1;
                            sendData = true;
                        }
                    } else {
                        sectionData[el.name] = el.value;
                        sendData = true;
                    }
                }
                data[section.dataset.id] = sectionData;
            }
            if (sendData) {
                payload.metaData.electionBot = data;
            }
        }).reset((payload) => {
            if (sendData) {
                container.textContent = '{jslang}wbb.electionbot.form.reloadPage{/jslang}';
                sections = [];
            }
        });

        EventHandler.add('com.woltlab.wcf.ckeditor5', 'handleError_' + wysiwygId, (data) => {
            if (data.returnValues.fieldName !== 'electionBot') return;
            
            const errors = JSON.parse(data.returnValues.realErrorMessage);
            data.returnValues.realErrorMessage = '{jslang}wbb.electionbot.form.error{/jslang}';
            for (const error of errors) {
                const el = container.querySelector('.electionBotSection[data-id="' + error.id + '"] input[name="' + error.field + '"]');
                if (el === null) {
                    console.error('cannot find input element with name ' + error.field + ' for election ' + error.id);
                } else {
                    innerError(el, error.msg);
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