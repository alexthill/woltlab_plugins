{capture assign='pageTitle'}{$thread->topic} {if $pageNo > 1}- {lang}wcf.page.pageNo{/lang} {/if}- {$board->getTitle()}{/capture}

{capture assign='__mainItemScope'}
	itemprop="mainEntity" itemscope itemtype="http://schema.org/DiscussionForumPosting"
	itemid="{$thread->getLink()}#post{$thread->firstPostID}"
{/capture}

{capture assign='contentHeader'}
	<header class="contentHeader messageGroupContentHeader wbbThread{if $thread->isDisabled} messageDisabled{/if}{if $thread->isDeleted} messageDeleted{/if}" data-thread-id="{$thread->threadID}" data-is-closed="{$thread->isClosed}" data-is-deleted="{$thread->isDeleted}" data-is-disabled="{$thread->isDisabled}" data-is-sticky="{$thread->isSticky}" data-is-announcement="{$thread->isAnnouncement}"{if WBB_MODULE_THREAD_MARKING_AS_DONE && $board->enableMarkingAsDone} data-is-done="{$thread->isDone}" data-can-mark-as-done="{if $thread->canMarkAsDone()}1{else}0{/if}"{/if} data-is-link="{if $thread->movedThreadID}1{else}0{/if}">
		<div class="contentHeaderIcon">
			{unsafe:$thread->getUserProfile()->getAvatar()->getImageTag(64)}

			{if $thread->isAnnouncement}
				<span class="jsTooltip wbbAnnouncementIcon" title="{lang}wbb.thread.announcement{/lang}">
					{icon name='bullhorn'}
				</span>
			{/if}
			{if $thread->isSticky}
				<span class="jsTooltip jsIconSticky wbbStickyIcon" title="{lang}wbb.thread.sticky{/lang}">
					{icon name='thumbtack'}
				</span>
			{/if}
			{if $thread->isDeleted}
				<span class="jsTooltip wbbDeletedIcon" title="{lang}wcf.message.status.deleted{/lang}">
					{icon name='trash-can'}
				</span>
			{/if}
			{if $thread->isDisabled}
				<span class="jsTooltip wbbDisabledIcon" title="{lang}wcf.message.status.disabled{/lang}">
					{icon name='eye-slash'}
				</span>
			{/if}
			{event name='icons'}
		</div>

		<div class="contentHeaderTitle">
			<h1 class="contentTitle" itemprop="name headline">{$thread->topic}</h1>
			<ul class="inlineList contentHeaderMetaData">
				{event name='beforeMetaData'}

				{hascontent}
					<li>
						{icon name='tags'}
						<ul class="labelList">
							{content}
								{event name='beforeLabels'}
								{foreach from=$thread->getLabels() item=label}
									<li>{unsafe:$label->render()}</li>
								{/foreach}
								{event name='afterLabels'}
							{/content}
						</ul>
					</li>
				{/hascontent}

				{if $pageNo == 1}
					<li>
						{icon name='user'}
						{user object=$thread->getUserProfile()}
					</li>
				{else}
					<li itemprop="author" itemscope itemtype="http://schema.org/Person">
						{icon name='user'}
						{if $thread->getUserProfile()->userID}
							<a href="{$thread->getUserProfile()->getLink()}" class="userLink" data-object-id="{$thread->getUserProfile()->userID}" itemprop="url">
								<span itemprop="name">{unsafe:$thread->getUserProfile()->getFormattedUsername()}</span>
							</a>
						{else}
							<span itemprop="name">{$thread->getUserProfile()->username}</span>
						{/if}
					</li>
				{/if}

				<li>
					{icon name='clock'}
					<a href="{link application='wbb' controller='Thread' object=$thread}{/link}">{time time=$thread->time}</a>
				</li>

				{if $thread->isClosed}
					<li>
						<span class="jsIconClosed">
							{icon name='lock'}
						</span>
						{lang}wbb.thread.closed{/lang}
					</li>
				{/if}

				{event name='afterMetaData'}
			</ul>
		</div>
	</header>
{/capture}

{capture assign='headContent'}
	{if $pageNo < $pages}
		<link rel="next" href="{link application='wbb' controller='ThreadUserPosts' object=$thread pageNo=$pageNo+1}{unsafe:$whoWroteUsersParam}{/link}">
	{/if}
	{if $pageNo > 1}
		<link rel="prev" href="{link application='wbb' controller='ThreadUserPosts' object=$thread}{unsafe:$whoWroteUsersParam}{if $pageNo > 2}&pageNo={$pageNo-1}{/if}{/link}">
	{/if}
{/capture}

{capture assign='__pageDataAttributes'}data-board-id="{$thread->boardID}" data-thread-id="{$thread->threadID}"{/capture}

{capture assign='contentInteractionPagination'}
	{if $pages > 1}
		<woltlab-core-pagination
			page="{$pageNo}"
			count="{$pages}"
			url="{link application='wbb' controller='ThreadUserPosts' object=$thread}{unsafe:$whoWroteUsersParam}{/link}"
		></woltlab-core-pagination>
	{/if}
{/capture}

{capture assign='contentInteractionButtons'}
	{if $__wcf->user->userID && !$thread->getBoard()->isIgnored()}
		<div class="dropdown contentInteractionButton">
			<button type="button" class="jsTooltip button small dropdownToggle jsSubscribeButton subscribeThreadStatusDropdownToggle{if $thread->isSubscribed() || $thread->isIgnored()} active{/if}" data-thread-id="{$thread->threadID}" data-is-subscribed="{if $thread->isSubscribed()}1{else}0{/if}">
				{if $thread->isSubscribed()}
					{icon name='bookmark' type='solid'}
				{else if $thread->isIgnored()}
					{icon name='ban'}
				{else}
					{icon name='bookmark'}
				{/if}
				<span>{lang}{if $thread->isSubscribed()}wbb.thread.status.watching{elseif $thread->isIgnored()}wbb.thread.status.ignoring{else}wcf.user.objectWatch.button.subscribe{/if}{/lang}</span>
			</button>
			<ul class="dropdownMenu subscribeThreadStatusDropdown" data-thread-id="{$thread->threadID}">
				<li class="userObjectWatchSelect subscribeThreadSelect{if !$thread->isSubscribed() && !$thread->isIgnored()} active{/if}" data-status="normal">
					<span class="userObjectWatchSelectHeader">{lang}wbb.thread.status.normal{/lang}</span>
					<span class="userObjectWatchSelectDescription">{lang}wbb.thread.status.normal.description{/lang}</span>
				</li>
				<li class="userObjectWatchSelect subscribeThreadSelect{if $thread->isSubscribed()} active{/if}" data-status="watching">
					<span class="userObjectWatchSelectHeader">{lang}wbb.thread.status.watching{/lang}</span>
					<span class="userObjectWatchSelectDescription">{lang}wbb.thread.status.watching.description{/lang}</span>
				</li>
				{if $thread->canBeIgnored()}
					<li class="userObjectWatchSelect subscribeThreadSelect{if $thread->isIgnored()} active{/if}" data-status="ignoring">
						<span class="userObjectWatchSelectHeader">{lang}wbb.thread.status.ignoring{/lang}</span>
						<span class="userObjectWatchSelectDescription">{lang}wbb.thread.status.ignoring.description{/lang}</span>
					</li>
				{/if}
			</ul>
		</div>
	{/if}
	{if $officialPostMapping|isset && $pageNo == 1 && $officialPostMapping[null][next]}
		<a href="{$officialPostMapping[null][next]->getLink()}" class="contentInteractionButton button small">{icon name='arrow-right'} <span>{lang}wbb.post.official.first{/lang}</span></a>
	{/if}
{/capture}

{capture assign='contentInteractionShareButton'}
	<button type="button" class="button small wsShareButton jsTooltip" title="{lang}wcf.message.share{/lang}" data-link="{$thread->getLink()}" data-link-title="{$thread->getTitle()}" data-bbcode="[thread]{$thread->getObjectID()}[/thread]">
		{icon name='share-nodes'}
	</button>
{/capture}

{include file='header'}

{if $items > 0}
	<div class="section">
		<ul
			class="wbbThreadPostList messageList jsClipboardContainer"
			data-is-last-page="{if $isLastPage}true{else}false{/if}"
			data-last-post-time="{$thread->lastPostTime|max:$objects->getMaxPostTime()}"
			data-page-no="{$pageNo}"
			data-type="com.woltlab.wbb.post"
		>
			{include file='threadPostList' application='wbb'}
			{if $__wcf->user->userID && $thread->isClosed && !$thread->canReply() && !($hideClosedForNewRepliesNotice|isset && $hideClosedForNewRepliesNotice)}
				<li class="messageListNotice">
					<woltlab-core-notice type="info">{lang}wbb.thread.closedForNewReplies{/lang}</woltlab-core-notice>
				</li>
			{/if}
			{if $numberOfHiddenPosts > 0}
				<li class="messageListNotice">
					<woltlab-core-notice type="info">{lang}wbb.post.hiddenForGuests{/lang}</woltlab-core-notice>
				</li>
			{/if}
			{if $pages > 1}
				<li class="messageListPagination">
					<woltlab-core-pagination
						page="{$pageNo}"
						count="{$pages}"
						url="{link application='wbb' controller='ThreadUserPosts' object=$thread}{unsafe:$whoWroteUsersParam}{/link}"
					></woltlab-core-pagination>
				</li>
			{/if}
			{if $thread->canReply()}{include file='threadQuickReply' application='wbb'}{/if}
		</ul>
	</div>
{else}
	<woltlab-core-notice type="info">{lang}wcf.global.noItems{/lang}</woltlab-core-notice>
{/if}

{capture assign='footerBoxes'}
	{if $tags|count}
		<section class="box">
			<h2 class="boxTitle">{lang}wcf.tagging.tags{/lang}</h2>

			<div class="boxContent">
				<ul class="tagList">
					{foreach from=$tags item=tag}
						<li><a href="{link controller='Tagged' object=$tag}objectType=com.woltlab.wbb.thread{/link}" class="tag jsTooltip" title="{lang}wcf.tagging.taggedObjects.com.woltlab.wbb.thread{/lang}">{$tag->name}</a></li>
					{/foreach}
				</ul>
			</div>
		</section>
	{/if}

	{event name='infoBoxes'}
{/capture}

<script data-relocate="true">
	$(function() {
		WCF.Language.addObject({
			'wbb.post.closed': '{jslang}wbb.post.closed{/jslang}',
			'wbb.post.copy.title': '{jslang}wbb.post.copy.title{/jslang}',
			'wbb.post.edit': '{jslang}wbb.post.edit{/jslang}',
			'wbb.post.edit.close': '{jslang}wbb.post.edit.close{/jslang}',
			'wbb.post.edit.delete': '{jslang}wbb.post.edit.trash{/jslang}',
			'wbb.post.edit.delete.confirmMessage': '{jslang}wbb.post.edit.delete.confirmMessage{/jslang}',
			'wbb.post.edit.deleteCompletely': '{jslang}wbb.post.edit.delete{/jslang}',
			'wbb.post.edit.enable': '{jslang}wbb.post.edit.enable{/jslang}',
			'wbb.post.edit.disable': '{jslang}wbb.post.edit.disable{/jslang}',
			'wbb.post.edit.merge': '{jslang}wbb.post.edit.merge{/jslang}',
			'wbb.post.edit.merge.success': '{jslang}wbb.post.edit.merge.success{/jslang}',
			'wbb.post.edit.open': '{jslang}wbb.post.edit.open{/jslang}',
			'wbb.post.edit.restore': '{jslang}wbb.post.edit.restore{/jslang}',
			'wbb.post.edit.trash.confirmMessage': '{jslang}wbb.post.edit.trash.confirmMessage{/jslang}',
			'wbb.post.edit.trash.reason': '{jslang}wbb.post.edit.trash.reason{/jslang}',
			'wbb.post.ipAddress.title': '{jslang}wbb.post.ipAddress.title{/jslang}',
			'wbb.post.moderation.redirect': '{jslang}wbb.post.moderation.redirect{/jslang}',
			'wbb.post.moveToNewThread': '{jslang}wbb.post.moveToNewThread{/jslang}',
			'wbb.post.official': '{jslang}wbb.post.official{/jslang}',
			'wbb.thread.closed': '{jslang}wbb.thread.closed{/jslang}',
			'wbb.thread.confirmDelete': '{jslang}wbb.thread.confirmDelete{/jslang}',
			'wbb.thread.confirmTrash': '{jslang}wbb.thread.confirmTrash{/jslang}',
			'wbb.thread.confirmTrash.reason': '{jslang}wbb.thread.confirmTrash.reason{/jslang}',
			'wbb.thread.edit.advanced': '{jslang}wbb.thread.edit.advanced{/jslang}',
			'wbb.thread.edit.close': '{jslang}wbb.thread.edit.close{/jslang}',
			'wbb.thread.edit.delete': '{jslang}wbb.thread.edit.delete{/jslang}',
			'wbb.thread.edit.done': '{jslang}wbb.thread.edit.done{/jslang}',
			'wbb.thread.edit.enable': '{jslang}wbb.thread.edit.enable{/jslang}',
			'wbb.thread.edit.disable': '{jslang}wbb.thread.edit.disable{/jslang}',
			'wbb.thread.edit.markPosts': '{jslang}wbb.thread.edit.markPosts{/jslang}',
			'wbb.thread.edit.markThread': '{jslang}wbb.thread.edit.markThread{/jslang}',
			'wbb.thread.edit.move': '{jslang}wbb.thread.edit.move{/jslang}',
			'wbb.thread.edit.moveDestination.error.equalsOrigin': '{jslang}wbb.thread.edit.moveDestination.error.equalsOrigin{/jslang}',
			'wbb.thread.edit.open': '{jslang}wbb.thread.edit.open{/jslang}',
			'wbb.thread.edit.removeLink': '{jslang}wbb.thread.edit.removeLink{/jslang}',
			'wbb.thread.edit.restore': '{jslang}wbb.thread.edit.restore{/jslang}',
			'wbb.thread.edit.scrape': '{jslang}wbb.thread.edit.scrape{/jslang}',
			'wbb.thread.edit.sticky': '{jslang}wbb.thread.edit.sticky{/jslang}',
			'wbb.thread.edit.trash': '{jslang}wbb.thread.edit.trash{/jslang}',
			'wbb.thread.edit.undone': '{jslang}wbb.thread.edit.undone{/jslang}',
			'wbb.thread.edit.unmarkThread': '{jslang}wbb.thread.edit.unmarkThread{/jslang}',
			'wbb.thread.moved': '{jslang}wbb.thread.moved{/jslang}',
			'wbb.thread.newPosts': '{jslang}wbb.thread.newPosts{/jslang}',
			'wbb.thread.sticky': '{jslang}wbb.thread.sticky{/jslang}',
			'wcf.global.worker.completed': '{jslang}wcf.global.worker.completed{/jslang}',
			'wcf.user.objectWatch.manageSubscription': '{jslang}wcf.user.objectWatch.manageSubscription{/jslang}',
			'wcf.message.bbcode.code.copy': '{jslang}wcf.message.bbcode.code.copy{/jslang}',
			'wcf.message.error.editorAlreadyInUse': '{jslang}wcf.message.error.editorAlreadyInUse{/jslang}',
			'wcf.message.status.deleted': '{jslang}wcf.message.status.deleted{/jslang}',
			'wcf.message.status.disabled': '{jslang}wcf.message.status.disabled{/jslang}',
			'wbb.thread.edit.moveThreads': '{jslang}wbb.thread.edit.moveThreads{/jslang}',
			'wbb.thread.edit': '{jslang}wbb.thread.edit{/jslang}',
			'wcf.label.none': '{jslang}wcf.label.none{/jslang}',
			'wbb.thread.done': '{jslang}wbb.thread.done{/jslang}',
			'wbb.thread.undone': '{jslang}wbb.thread.undone{/jslang}',
			'wbb.thread.modification.log.thread.hide.confirmMessage': '{jslang}wbb.thread.modification.log.thread.hide.confirmMessage{/jslang}'
		});

		{assign var='canEditPostInline' value=false}
		{if $__wcf->user->userID}
			{if $board->canEditThreads() || $thread->getBoard()->getModeratorPermission('canClosePost') || $thread->getBoard()->getModeratorPermission('canDeletePost') || $thread->getBoard()->getModeratorPermission('canDeletePostCompletely') || $thread->getBoard()->getModeratorPermission('canEnablePost') || $thread->getBoard()->getModeratorPermission('canRestorePost')}
				{assign var='canEditPostInline' value=true}
			{/if}
		{/if}

		require(['WoltLabSuite/Forum/Controller/Thread', 'WoltLabSuite/Core/Component/Quote/Message', 'WoltLabSuite/Forum/Handler/Thread/ThreadUpdateHandler'], (ControllerThread, { registerContainer }, { ThreadUpdateHandler }) => {
			registerContainer(".message", ".messageBody", "com.woltlab.wbb.post");

			ControllerThread.init({$thread->threadID}, {
				clipboard: true,
				postInlineEditor: {if $__wcf->user->userID}true{else}false{/if},
				postLoader: {if $board->getModeratorPermission('canReadDeletedPost')}true{else}false{/if},
				postManager: {if $__wcf->user->userID}true{else}false{/if},
				postLikeHandler: {if MODULE_LIKE}true{else}false{/if}
			}, {
				clipboard: {
					hasMarkedItems: {if $hasMarkedItems}true{else}false{/if}
				},
				postInlineEditor: {
					canEditInline: {if $canEditPostInline}true{else}false{/if},
				}
			});
		});

		{if $thread->canMarkBestAnswer()}
			require(['WoltLabSuite/Forum/Ui/Thread/BestAnswer', 'Language'], function(BestAnswer, Language) {
				Language.addObject({
					'wbb.thread.markAsBestAnswer.confirmMessage': '{jslang}wbb.thread.markAsBestAnswer.confirmMessage{/jslang}'
				});

				BestAnswer.init({$thread->threadID});
			});
		{/if}

		require(['WoltLabSuite/Forum/Ui/Thread/LastPageHandler'], (LastPageHandler) => {
			LastPageHandler.setup();
		});

		{if LOG_IP_ADDRESS && $__wcf->session->getPermission('admin.user.canViewIpAddress')}
			require(['WoltLabSuite/Forum/Ui/Post/IpAddressHandler'], ({ IpAddressHandler }) => {
				new IpAddressHandler();
			});
		{/if}

		{if $thread->getBoard()->getModeratorPermission('canEditPost')}
			require([ 'WoltLabSuite/Forum/Ui/Thread/Modification/Log/Hide'], function (ThreadLogHideHandler) {
				ThreadLogHideHandler.init();
			});
		{/if}

		{if $__wcf->user->userID && !$thread->getBoard()->isIgnored()}
			require([ 'WoltLabSuite/Forum/Controller/Thread/SubscriptionStatusHandler', 'Language'], function ({ SubscriptionStatusHandler }, Language) {
				Language.addObject({
					'wbb.thread.status.watching': '{jslang}wbb.thread.status.watching{/jslang}',
					'wbb.thread.status.ignoring': '{jslang}wbb.thread.status.ignoring{/jslang}',
					'wbb.thread.status.normal': '{jslang}wbb.thread.status.normal{/jslang}',
					'wcf.user.objectWatch.button.subscribe': '{jslang}wcf.user.objectWatch.button.subscribe{/jslang}',
				})

				new SubscriptionStatusHandler();
			});
		{/if}
	});
</script>

{if $board->canEditThreads()}
	<div class="jsClipboardContainer" data-type="com.woltlab.wbb.thread" style="display: none;">
		<div class="jsClipboardObject">
			<input id="wbbThreadClipboardMark" type="checkbox" class="jsClipboardItem" data-object-id="{$thread->threadID}">
		</div>
	</div>
{/if}

{include file='footer'}
