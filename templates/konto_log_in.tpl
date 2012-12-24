<a class="v_account_register">Nie masz konta? Zarejestruj się!</a><br />
adres e-mail: <input type="text" id="register_email" value="" /><br />
Twoje hasło: <input type="password" id="register_password" value="" /><br />
powtórz hasło: <input type="password" id="register_password2" value="" /><br />
<input type="button" value="Rejestruj!" id="register_submit" class="account_register_submit" /><hr />
<script>
  $('.account_register_submit').click(account__register);
  $("#login_submit").click(account__log_in);
</script>

<br /><br /><br /><br />
<div id="v_registerform">
	Adres e-mail: <input type="text" id="email_addr" value="" /><br />
	Twoje hasło: <input type="password" id="password" value="" /><br />
	<input type="button" value="Zaloguj!" id="login_submit" /><hr />
</div>