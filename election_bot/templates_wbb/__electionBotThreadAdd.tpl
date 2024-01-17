{if $board->getPermission('canStartElection')}
<section class="section">
    <h2 class="sectionTitle">{lang}wbb.electionbot.form.title2{/lang}</h2>
    
    <dl>
        <dt></dt>
        <dd>
            <label><input type="checkbox" id="enableElectionBot" name="enableElectionBot"{if $electionEnable} checked{/if}> {lang}wbb.electionbot.form.enable{/lang}</label>
        </dd>
    </dl>
    <div id="electionAddFormContainer"{if !$electionEnable} style="display:none;"{/if}>
        <span>{* this empty span is just here so that the first `dl` is not the first child and gets a `margin-top` *}</span>
        <dl{if $errorField == 'electionName'} class="formError"{/if}>
            <dt><label for="electionName">{lang}wbb.electionbot.form.name{/lang}</label> <span class="formFieldRequired">*</span></dt>
            <dd>
                <input type="text" id="electionName" name="electionName" value="{$electionName}" maxlength="255" class="long"{if $electionEnable} required{/if}>
                {if $errorField == 'electionName'}
                    <small class="innerError">
                        {if $errorType == 'empty'}
                            {lang}wcf.global.form.error.empty{/lang}
                        {else}
                            {lang}wbb.electionbot.form.name.error.{@$errorType}{/lang}
                        {/if}
                    </small>
                {/if}
            </dd>
        </dl>
        <dl{if $errorField == 'electionDeadline'} class="formError"{/if}>
            <dt><label for="electionDeadline">{lang}wbb.electionbot.form.deadline{/lang}</label> <span class="formFieldRequired">*</span></dt>
            <dd>
                <input type="datetime" id="electionDeadline" name="electionDeadline" value="{$electionDeadline}" class="medium">
                {if $errorField == 'electionDeadline'}
                    <small class="innerError">
                        {if $errorType == 'empty'}
                            {lang}wcf.global.form.error.empty{/lang}
                        {else}
                            {lang}wbb.electionbot.form.deadline.error.{@$errorType}{/lang}
                        {/if}
                    </small>
                {/if}
                <small>{lang}wbb.electionbot.form.deadline.description{/lang}</small>
            </dd>
        </dl>
        <dl{if $errorField == 'electionExtension'} class="formError"{/if}>
            <dt><label for="electionExtension">{lang}wbb.electionbot.form.extension{/lang}</label></dt>
            <dd>
                <input type="number" id="electionExtension" name="electionExtension" value="{$electionExtension}" class="medium">
                {if $errorField == 'electionExtension'}
                    <small class="innerError">{lang}wbb.electionbot.form.extension.error.{@$errorType}{/lang}</small>
                {/if}
                <small>{lang}wbb.electionbot.form.extension.description{/lang}</small>
            </dd>
        </dl>
    </div>
</section>

<script data-relocate="true">
    (function() {
        const enableEl = document.getElementById('enableElectionBot');
        const containerEl = document.getElementById('electionAddFormContainer');
        const nameEl = document.getElementById('electionName');
        enableEl.addEventListener('click', () => {
            if (enableEl.checked) {
                containerEl.style.display = '';
                nameEl.required = true;
            } else {
                containerEl.style.display = 'none';
                nameEl.required = false;
            }
        });
    })();
</script>
{/if}