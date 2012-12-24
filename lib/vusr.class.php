<?php
// to jest klasa sluzaca do uwierzytelniania i wspierania autoryzacji (okreslanie tozsamosci uzera i obsluga zdarzen uwierzytelniania)
class VUSR
{	
	private $UID=0;
	private $GID=array(0);
	private $DB;
	private $logo='';
	private $auth_hash='';
	private $user_key='user_id';
	private $gid_key='user_gid';
	public function get_key()
	{
	  return $this->user_key;
	}
	public function get_gid_key()
	{
	  return $this->gid_key;
	}
	public function get_uid()
	{
		return $this->UID;
	}
	public function get_gid()
	{
		return $this->GID;
	}
	public function get_auth()
	{
		return $this->UID;
	}
	public function get_logo_path()	// avatars
	{
		return $this->logo;
	}
	public function get_mail()
	{
		$mail=$this->DB->query('SELECT email_addr FROM user WHERE user_id=:id',array(':id'=>$this->UID));
		return $mail[0]['email_addr'];
	}
	public function is_active($uid)
	{
		$active=$this->DB->query('SELECT active FROM user WHERE id=:id',array(':id'=>$uid));
		if(isset($active[0]['active'])) return $active[0]['active'];
		return 0;
	}
	public function user_exists($uid)
	{
		return $this->DB->query('SELECT id FROM user WHERE id=:id',array(':id'=>$uid));
	}
	public function pass_hash($pass)
	{
		return sha1($pass);
	}
	// this method is used to authentication purposes, doesn't write anything in session
	// this is left for log_in method, so we can have this flexibility
	public function check_credentials($login,$password)
	{
		$udata=$this->DB->query('SELECT id,user_pass FROM user WHERE email_addr=:email_addr',array(':email_addr'=>$login));
		if(!$udata||!is_array($udata)||count($udata)==0) return 0;
		if($this->pass_hash($password)==$udata[0]['user_pass']) 
		{
			$groups_list=$this->DB->query('SELECT group_id FROM user_groups_membership WHERE user_id=:id',array(':id'=>$udata[0]['id']));
			$usr_groups=array(0);
			foreach($groups_list as $group) $usr_groups[]=$group['group_id'];
			return array('id'=>$udata[0]['id'],'hash'=>$udata[0]['user_pass'],'gid'=>$usr_groups);
		}
		return 0;
	}
	public function set_cookies()
	{
		  setcookie('user_logged',$this->UID);
		  setcookie('user_gid',implode(';',$this->GID));
		  setcookie('site_name',$this->CNF['SITE_NAME']);
	}
	public function log_in()
	{
	    if($this->UID) return 2;
	    if(isset($_POST['email'],$_POST['password']))
	    {
				if(is_array($usr_data=$this->check_credentials($_POST['email'],$_POST['password'])))
				{
		  			if(!$this->is_active($usr_data['id'])) return; // not active
		  			$this->UID=$_SESSION[$this->user_key]=$usr_data['id'];
				  	$this->GID=$_SESSION[$this->gid_key]=$usr_data['gid'];
		  			$this->set_cookies();
		  			return 1;
				}
	  	}
   	return;
	}
	public function logout()
	{
		session_destroy();
		$this->UID=0;
		$this->GID=array(0);
		$this->set_cookies();
		setcookie(session_name(),0,time()-1); // delete cookie
	}
	public function __construct($db,$cnf)
	{
		$this->DB=$db;
		$this->CNF=$cnf;
		if(isset($_SESSION[$this->user_key])&&$_SESSION[$this->user_key])
		{
			// authentication resistant from session poisoning (additional securying of uid ang gid variables)
		    $udata=$this->DB->query('SELECT user_pass FROM user WHERE id=:id',array(':id'=>$_SESSION[$this->user_key]));
		    if(!$udata) return $_SESSION[$this->user_key]=$_SESSION[$this->gid_key]=0; // goodbye
		    $this->UID=$_SESSION[$this->user_key];
		    $this->GID=$_SESSION[$this->gid_key];
		}
		$this->set_cookies();
	}	
}
?>