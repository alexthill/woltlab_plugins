{if $electionBotCreateForm|isset}
{@$electionBotCreateForm->getHtml()}
<section class="section">
    <h2 class="sectionTitle">{* this header is empty on purpose *}</h2>
    <dl{if $errorField === 'electionParticipants'} class="formError"{/if}>
        <dt><label for="electionParticipants">{lang}wbb.electionbot.form.participants{/lang}</label></dt>
        <dd>
            <textarea id="electionParticipants" name="electionParticipants" rows="10">{$electionParticipants}</textarea>
            {if $errorField == 'electionParticipants'}
                <small class="innerError">
                    {lang}wbb.electionbot.form.participants.error.{@$errorType}{/lang}
                </small>
            {/if}
            <small>{lang}wbb.electionbot.form.participants.description{/lang}</small>
            <label><input type="checkbox" name="electionParticipantsStrict"{if $electionParticipantsStrict} checked{/if}>{lang}wbb.electionbot.form.participants.strict{/lang}</label>
            <label><input type="checkbox" name="electionParticipantsPost"{if $electionParticipantsPost} checked{/if}>{lang}wbb.electionbot.form.participants.post{/lang}</label>
        </dd>
    </dl>
</section>
{/if}
