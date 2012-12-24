<h1>Profil użyszkodnika</h1>
{foreach from=$usr_data item=usr}
<div style="clear:both">
		<div class="account_avatar">
			<img src="/img/avatars/{$usr.user_avatar}" id="avatar" />
		</div>
	<div class="account_signature">
	{$usr.user_info}
			{if $usr.email_addr!=0}
				<h3>Login: {$usr.email_addr}</h3>
			{/if}
		<h3>Nazwa: {$usr.username}</h3>
	</div>
</div>
{/foreach}