<h2>{$USRLIST}</h2>
<!-- this is normal smarty template; no pseudo smarty used since this should be indexed by the search engines -->
{$no_results}
<table>
<tr>
	<td>{$E_MAIL}</td><td>{$USER_NAME}</td><td>{$SIGNATURE}</td><td>{$VE_CREATED_AT}</td><td>spam</td><td>Party</td>
</tr>
	{foreach from=$userlist item=u}
	<tr>
		<td class="user_mail">{$u.email_addr}</td>
		<td class="user_name">{$u.username}</td>
		<td class="user_info">{$u.user_info}</td>
		<td class="user_created_at">{$u.created_at}</td>
		<td class="user_spam">{if $u.user_spam}{$VE_YES}{else}{$VE_NO}{/if}</td>
		<td class="user_party">{if $u.user_party}{$VE_YES}{else}{$VE_NO}{/if}</td>
	</tr>
{/foreach}
</table>
{if $pager}{$VE_PAGER_LABEL}{/if} 
{foreach from=$pager item=page}
&nbsp;{$page}
{/foreach}