{if $whoWroteShownUsers|isset}
    <li id="whoWroteShownUsers">
        {if $whoWroteShownUsers|count}{icon name='user-pen'} {lang}wbb.alexthill.who_wrote.showingUsers{/lang}
        {else}{icon name='user-xmark'} {lang}wbb.alexthill.who_wrote.showingAll{/lang}{/if}
        {lang}wbb.alexthill.who_wrote.backToThread{/lang}
    </li>
{/if}