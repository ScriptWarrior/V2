<?php
class account extends vengine_mod
{
  private function register_form()
  {
    $this->display_interface('register_form');
  }
  private function register()	// this method is a great example of using create() with some additional conditions
  {
       if($this->REQ->post('password')!=$this->REQ->post('user_pass'))
      {
			$this->msg('PASSWORDS_MATCH_ERR');
			return;
      }
      if($this->DB->query('SELECT id FROM user where email_addr=:email_addr',array(':email_addr'=>$this->REQ->post('email_addr'))))
      {
      	if($this->CNF['REGISTER_EMAIL_CONFIRM']=='YES') 
      	{
      		sleep(rand(2,4));
		      $this->msg('REGISTRATION_SUCCESFUL_MAIL_SENT'); // no information disclosure ;D
		    }
		    else
		    $this->msg('REGISTER_SUCCESFUL');
	      return;
      }
		$sql_data=$this->create(self::DB_OBJECT);
		if(!$sql_data) return;
		$sql_data['params'][':user_pass']=$this->USR->pass_hash($sql_data['params'][':user_pass']);
      if(!$this->DB->query($sql_data['sql'],$sql_data['params']))
      {
			$this->msg('REGISTER_FAILED');
			return 0;
      }
		$user_id=$this->DB->query('SELECT id FROM user where email_addr=:email_addr',array(':email_addr'=>$this->REQ->post('email_addr')));
		$user_id=$user_id[0]['id'];      
      $this->DB->query('UPDATE user SET user.user_id=:user_id WHERE id=:id',array(':user_id'=>$user_id,':id'=>$user_id)); // not very elegant, but trigger didn't want to do the trick
      if($this->CNF['REGISTER_EMAIL_CONFIRM']=='YES')
      {
      	require('./lib/uni_mailer.class.php');
			$mail=new uni_mailer($this->REQ->post('email_addr'),$this->REQ->post('username'),$this->MSG['REGISTER_MAIL_TOPIC']['content'].' '.$this->CNF['SITE_NAME'],'',$this->CNF['SITE_EMAIL']);
			do
			{
			 	$token=$this->AUTH->get_rand_string(10,20);
			 } while($this->DB->query('SELECT id from account_activation_token WHERE token=:token',array(':token'=>$token)));
			$mail->fromTemplate($this->TPL,'_register_activate_mail',array('SITE_EMAIL'=>$this->CNF['SITE_EMAIL'],'UNAME'=>$this->REQ->post('username'),'LINK'=>$this->REQ->link(array('module'=>'account','action'=>'mail_activate','subaction'=>$token,'full'=>1),$this->MSG['THIS_LINK']['content'])));
			$this->DB->query('INSERT INTO account_activation_token (token,user_id) VALUES (:token,:user_id)',array(':token'=>$token,':user_id'=>$user_id));
			if(!$mail->mail->send())
			{
				$this->msg_raw($mail->$mail->ErrorInfo,'error');
				return;
			}
			$this->msg('REGISTRATION_SUCCESFUL_MAIL_SENT');
			return;
		}
      $this->msg('REGISTER_SUCCESFUL');
      return;
  }
  // this method allows us only to upgrade pass and avatar, that's it, no more options available now, this is just for basic usage, written with the old style from rc1, will be rewritten to compatibile with forms and listers
  private function update_account()
  {
		$this->update();
  }
  private function show_usr()
  { 
  	  $user=$this->show(self::FETCH_DATA);	// retrieve user data
	  if(!$this->REQ->get('ajax')) 
	  {
	  	$this->TPL->assign('usr_data',$user);
	  	$this->display_interface('account_show_profile');
	  }
	  else
	  		$this->output.=$this->array2xmltag('user',$user);
  }
  private function mail_activate()
  {
  		$token=$this->REQ->get('subaction');
  		$id=$this->DB->query('SELECT user_id from account_activation_token WHERE token=:token',array(':token'=>$token));
  		if(!$id)
  		{
	  		$this->msg('TOKEN_ACTIVATION_FAILED');
	  		return;
  		}
  		$this->DB->query('UPDATE user SET active=1 WHERE id=:id',array(':id'=>$id[0]['user_id']));
  		$this->DB->query('DELETE FROM account_activation_token WHERE token=:token',array(':token'=>$token));
  		$this->msg('TOKEN_ACTIVATION_SUCCESFUL');
  		return;
  }
  public function engine()
  {
  	$this->CNF['avatar_dir']='img/avatars/';
	switch($this->REQ->get('action'))
	{
		case 'log_in' :
		    $status=$this->USR->log_in();
		    if($status==2) $this->msg('ALREADY_LOGGED');
		    if($status!=1) $this->msg('VE_ACCESS_DENIED');
		    break;
		case 'log_out' :
		    $this->USR->logout();
		    break;
		case 'register' :
		    $this->register();
		    break;
		case 'register_form' :
		    $this->register_form();
		    break;
		case 'show' : 
		{		
			 $this->show_usr();
			 break;
		}
		case 'mail_activate' :
		{
			$this->mail_activate(); //
			$this->output.=$this->TPL->fetch('_result.tpl');
			break;
		}
		case 'list' :
		{
			$usrlist=$this->search(self::FETCH_DATA);
			if(!$this->REQ->get('ajax'))
			{
				$this->TPL->assign('userlist',$usrlist);
				$this->TPL->assign('pager',$this->get_subpages());
				$this->output.=$this->TPL->fetch('account_list_mod.tpl');
			}
			else
			$this->output.=$this->array2xmltag('user',$usrlist);
			break;	 
		}
		case 'show_me'	: // shows current logged user profile 
		{
			$this->REQ->set('id',$this->USR->get_uid());
			$this->show_usr();
			break;
		}
		case 'update' :
		    $this->update_account();	// READY
		    break;
		default :  
		{ 
			$this->display_interface('account'); 
		}
	}
	return $this->output;
  }
}
?>