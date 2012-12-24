<!-- this is a bit an example of how we actually WON'T write V2 modules - it's just for the contrast with content, at least client side- compare their codes -->
<!-- this is just a demo for pseudo smarty, generally, Smarty-enabled get_entity_form() magic method should be used and all the work is done already for you -->
<h1>{$ACCOUNT_MOD}</h1>
<a class="v_logout" id="konto_update_account_logout" href="">{$VE_LOGOUT}</a>
<hr />
{foreach from=$usr_data item=usr}
Login: {$usr.email_addr}<br />
{$SIGNATURE}
<textarea id="user_signature" rows="10" cols="30" class="user_edit">
	{$usr.user_info}	
</textarea><u id="user_info_save">{$VE_SAVE}</u>
<br />
{$USER_NAME}
<input type="text" id="user_username" value="{$usr.username}" /><u id="user_username_save">{$VE_SAVE}</u><br />
<img src="/img/avatars/{$usr.user_avatar}" id="avatar" /><br />
{$AVATAR_ENTICE_INFO}:
<form method="post" action="" enctype="multipart/form-data" id="avatar_upload_form">
	<input type="file" id="account_avatar_file" name="account_avatar_file" /><br />
	<input type="submit" name="avatar_submit" value="Upload!" id="avatar_submit_button" />
</form>
{/foreach}
<iframe height="100" width="100" id="avatar_upload_iframe">
</iframe>
<script>
function avatar_upload_trigger(e)
{
	$("#avatar_upload_form").attr('action',get_link({'action':'update','subaction':'avatar'})).attr('target','avatar_upload_iframe');
	alert('[DEBUG]Set form action to : '+$("#avatar_upload_form").attr('action')+' and target to '+$("#avatar_upload_form").attr('target'));
}
$(".v_logout").click(account__log_out);
$("#avatar_submit_button").click(avatar_upload_trigger);
$("#user_info_save").click(function(){account__update('user_info',$("#user_signature").val())});
$("#user_username_save").click(function(){account__update('username',$("#user_username").val())});
</script>