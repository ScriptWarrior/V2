// modul konta
load_status=1
// ACL CALLBACKS
function account__log_in()
{
    function callback_log_in(xml)
    {
			handle_xml_response(xml)
			if(error==0)
			{
	    		init_mod('account','account_log_in')
			}
    }
    $.post(get_link({'module':'account','action':'log_in','ajax':1}),{email:$("#email_addr").val(),password:$("#password").val()},callback_log_in)
}
function account__log_out(e)
{
    e.preventDefault()
    function callback_log_out(xml)
    {
		handle_error_response(xml)
		if(error==0)
		{
		    $.cookie('user_logged',user_id=0)
		    init_mod('account','account_log_in')
		}
    }
   $.ajax({url:get_link({'module':'account','action':'log_out'}),async:true,success:callback_log_out})
}
function account__register()
{
   $.post(get_link({'module':'account','action':'register','ajax':1}),{'email_addr':$("#register_email").val(),'user_pass':$("#register_password").val(),'password':$("#register_password2").val(),'username':$("#register_username").val()},function(XML){handle_xml_response(XML);})
}
function account__update(param,value)
{
	var code='function f(){return {'+param+':unescape("'+escape(value)+'")}}'
	eval(code)
	$.post(get_link({'module':'account','action':'update','id':user_id,'ajax':1}),f())
}
// TEMPLATE INITIATING FUNCTIONS
function account_log_in()
{
	if(user_id!=0)
	{
		// asynchrounous call, we block the destructor so we make our fetch ourselves:
		block_destructor=1
		$.ajax({url:get_link({'module':'account','action':'show_me','ajax':1,'csrf_code':''}),success:function(xml){
			var usr_data=xml_to_array(xml,'user')
			load_template('konto_update_account')
			MAIN_TPL.assign('usr_data',usr_data) // display the profile data
			destruct()
		}})
	}
	else
	{
		load_template('konto_log_in')
	}
}