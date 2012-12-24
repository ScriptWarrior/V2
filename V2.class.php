<?php
// V2 coded by ewilded  & cent
error_reporting(E_ALL); // yeah, why not
require('./lib/http_request.class.php');
require('./lib/vusr.class.php');
require('./config/db.conf.php');
require('./lib/db.class.php');
require('./mod_abstract.class.php');
ini_set('session.use_trans_sid',0);
ini_set('session.use_only_cookies',1);

class VEngine extends VBase
{
	protected $ajax=0;
	protected $pdf=0;
	protected $tags;
	public function __construct()
	{
		$this->load_config('V2');
		$this->DB=new PDODB_wrapper(DB_HOST,3306,DB_USER,DB_PASSWORD,DB_SCHEMA);
		require(SMARTY_DIR.'/Smarty.class.php');
		$this->TPL=new Smarty();
		session_start();
		$this->sanitize_input();
		$this->init_language();
		$this->REQ=new VDHttpRequest($this->CNF['URI_SEPARATOR'],$this->CNF['LINK_STRATEGY'],$this->CNF['LINK_SUFFIX'],$this->DB,$this->CNF);
		$this->DB->convey_req_object($this->REQ); 
		$SSSHA1='';
		$SESSION_ID=session_id();
		if(!preg_match('#^[\w,-]+$#',$SESSION_ID)) $SESSION_ID='';
		$SESS_FILE_PATH=ini_get('session.save_path').'/sess_'.$SESSION_ID;
		if(file_exists($SESS_FILE_PATH)) 
		{
			$SSSHA1=sha1(file_get_contents($SESS_FILE_PATH));
			$result=$this->DB->query('SELECT session_checksum,ip_addr,browser_checksum FROM session_sec where session_id=:SSID',array(':SSID'=>$SESSION_ID));
			if(!$result) 
			{
				$this->DB->query('INSERT INTO session_sec (session_id,session_checksum,session_last_activity,ip_addr,browser_checksum) VALUES (:SSID,:SSUM,'.time().',:ip_addr,:browser_checksum)',array(':SSID'=>$SESSION_ID,':SSUM'=>$SSSHA1,':ip_addr'=>$_SERVER['REMOTE_ADDR'],':browser_checksum'=>sha1($_SERVER['HTTP_USER_AGENT'])));
			}
			else
			{
				if($result[0]['ip_addr']!=$_SERVER['REMOTE_ADDR']||$result[0]['browser_checksum']!=sha1($_SERVER['HTTP_USER_AGENT'])||($result[0]['session_checksum']!=$SSSHA1&&$SSSHA1!='da39a3ee5e6b4b0d3255bfef95601890afd80709'))
				{
					 $this->CNF['CURR_MOD']='error';
					 $this->set_main_template('error');
					 $this->msg('VE_SESSION_SEC_ALERT'); ## hijack detected, no session_destroy here to avoid DoS ;DDD
					 if($result[0]['ip_addr']==$_SERVER['REMOTE_ADDR']&&$result[0]['browser_checksum']==sha1($_SERVER['HTTP_USER_AGENT'])) session_destroy(); 
				}
			}
		}
		else
		{
				$this->CNF['CURR_MOD']='error';
				$this->set_main_template('error');
				$this->msg('VE_SESSION_SEC_ALERT');
				session_destroy();
		}
		// flush
		$lifetime=ini_get('session.cookie_lifetime');
		$this->DB->query("DELETE FROM session_sec WHERE UNIX_TIMESTAMP(CURRENT_TIMESTAMP)-session_last_activity>:lifetime",array(':lifetime'=>$lifetime));
		$this->USR=new VUSR($this->DB,$this->CNF);
		if(!isset($this->CNF['CURR_MOD'])) $this->CNF['CURR_MOD']=$this->REQ->get('module');		
		if($this->CNF['CURR_MOD']!='error'&&!preg_match('/^\w+$/',$this->CNF['CURR_MOD'])) $this->CNF['CURR_MOD']=$this->CNF['DEFAULT_MODULE'];
		require('./lib/auth.class.php');
		$this->AUTH=new Vauth($this->REQ,$this->DB,$this->CNF);
		if(!$this->AUTH->permit($this->CNF['CURR_MOD'],$this->REQ->get('action'),$this->USR->get_gid(),$this->USR->get_uid()))
		{
			$this->CNF['CURR_MOD']='error';
			$this->REQ->set('module','error');
			$this->REQ->set('action','error');
			$this->set_main_template('error');
			$this->msg('VE_ACCESS_DENIED');
		}
		if($this->CNF['CSRF_PROTECT']=='yes') $this->AUTH->generate_csrf_code();
		$this->REQ->send_strategy();
		require('modules/'.$this->CNF['CURR_MOD'].'.mod.class.php');
		$obj=new $this->CNF['CURR_MOD']();
		if($this->REQ->get('ajax')) // jesli request tyczy sie funkcji ajaksowej
		{	
			if($this->REQ->get('no_output')&&$this->CNF['CURR_MOD']!='error')
			{
				$obj->init(array('MSG'=>&$this->MSG,'CNF'=>$this->CNF,'TPL'=>$this->TPL,'DB'=>$this->DB,'REQ'=>$this->REQ,'USR'=>$this->USR,'AUTH'=>$this->AUTH,'AJAX'=>1));
				echo $obj->engine();
			}
			else
			{
				header('Content-Type: text/xml;charset=utf-8');
				$obj->init(array('MSG'=>&$this->MSG,'CNF'=>$this->CNF,'TPL'=>$this->TPL,'DB'=>$this->DB,'REQ'=>$this->REQ,'USR'=>$this->USR,'AUTH'=>$this->AUTH,'AJAX'=>1));
				if($this->REQ->get('subaction')=='load_messages') ## empty action +  ajax=1 + subaction = load_messages -> we send MSG to AJAX call
					$this->output.=$obj->send_language_messages();
				else 
					$this->output.=$obj->engine();
				echo '<?xml version="1.0" encoding="utf-8"?><response>'.$this->output.'</response>';
			}
		}
		else
		{
			if($this->CNF['TAGS_SYSTEM']&&!in_array($this->CNF['CURR_MOD'],array('error','file_download')))
			{
				require('./lib/tags.class.php');
				$this->tags=new tags($this->get_args());
			}
		   $obj->init(array('MSG'=>&$this->MSG,'TPL'=>$this->TPL,'CNF'=>$this->CNF,'DB'=>$this->DB,'REQ'=>$this->REQ,'USR'=>$this->USR,'AUTH'=>$this->AUTH,'AJAX'=>0));
		 	$this->output.=$obj->engine(); ## output filtering
		 	$MAIN_TPL=new Smarty();
		 	if(is_object($this->tags)&&$this->CNF['TAGS_SYSTEM_SHOW'])
		 	{
			 	// this ugly fixed configs will be moved to XML files
			 	$tags_left=new Smarty();
				$tags_left->assign('tags',$this->tags->get(0,floor($this->tags->count()/2)));
			 	$tags_right=new Smarty();
			 	$tags_right->assign('tags',$this->tags->get(ceil($this->tags->count()/2),$this->tags->count()-1));
				$MAIN_TPL->assign('tags1',$tags_left->fetch('tags_system.tpl'));
				$MAIN_TPL->assign('tags2',$tags_right->fetch('tags_system.tpl'));
			}
			## run obscure data here
			$MAIN_TPL->assign('main_content',$this->output);
			$JS_CONFIG=array(); ## generating JS compatible config (it's less elegant, but simpler and IMO better, than sending this shit though cookies or XML)
			foreach($this->CNF as $key=>$val) $JS_CONFIG[]="CNF['$key']='$val'";
			$MAIN_TPL->assign('V2_JS_CONFIG','var CNF=[];'.implode(';',$JS_CONFIG).';');
			$MAIN_TPL->display($this->CNF['MAIN_TEMPLATE'].'.tpl');
		}
		// update session security information
		session_write_close();
		if(file_exists($SESS_FILE_PATH)) $SSSHA1=sha1(file_get_contents($SESS_FILE_PATH));
		if($SSSHA1&&$SSSHA1!='da39a3ee5e6b4b0d3255bfef95601890afd80709') $this->DB->query('UPDATE session_sec SET session_checksum=:SSUM, session_last_activity='.time().' WHERE session_id=:SSID',array(':SSUM'=>$SSSHA1,':SSID'=>$SESSION_ID)); 
	}
	private function init_language()
	{
		if(isset($_COOKIE['ln'])) $_SESSION['ln']=$_COOKIE['ln'];
		if(!isset($_SESSION['ln'])||!in_array($_SESSION['ln'],array('PL','EN'))) $_SESSION['ln']='PL';
		$this->CNF['ln']=$_SESSION['ln'];
		$this->load_language_messages('engine');
	}
	private function sanitize_input()
	{
		  ## session is not filtered from HTML, since we secure it from tampering with another way
        $globals=array(&$_GET,&$_POST,&$_COOKIE,&$_SERVER,&$_REQUEST,&$GLOBALS['HTTP_SERVER_VARS'],&$GLOBALS['HTTP_POST_VARS'],&$GLOBALS['HTTP_GET_VARS'],&$GLOBALS['HTTP_COOKIE_VARS'],&$GLOBALS['HTTP_ENV_VARS'],&$http_response_header,&$_ENV);
        if(isset($HTTP_RAW_POST_DATA)) $globals[]=&$HTTP_RAW_POST_DATA; 
        if(isset($HTTP_SERVER_VARS)) 	$globals=array_merge($globals,array(&$HTTP_SERVER_VARS,&$HTTP_COOKIE_VARS,&$HTTP_POST_VARS,&$HTTP_GET_VARS,&$HTTP_ENV_VARS));
        foreach($globals as &$glbl)
        {
	        	 if($glbl==NULL) continue;
             foreach(array_keys($glbl) as $key)
             {
					  $safe_key=htmlentities($key,ENT_QUOTES,'UTF-8');
                 if(!is_array($glbl[$key]))
      			  { 
                  $glbl[$safe_key]=htmlentities($glbl[$key],ENT_QUOTES,'UTF-8');
                 } 
                 else
                 {
                     foreach($glbl[$key] as $key2=>$val2)
                     {
                     	if(is_array($glbl[$key][$key2])) 
                     	{
                     		unset($glbl[$key][$key2]);
                     		continue;
                     	}
                     	$safe_key2=htmlentities($key2,ENT_QUOTES,'UTF-8');
                     	$glbl[$safe_key][$safe_key2]=htmlentities($val2,ENT_QUOTES,'UTF-8');
                     	if($safe_key2!=$key2) unset($glbl[$key2]);
                     }
                  }
                  if($safe_key!=$key) unset($glbl[$key]);
             	}
         }
	 		foreach(array_keys($_FILES) as $files_key)
	 		{
				if(!preg_match('#^\w+$',$files_key)||is_array($_FILES[$files_key]['name'])||!preg_match('#^[\w-]{3,'.$this->CNF['MAX_FILENAME_LENGTH'].'}(\.\w+){,1}$',$_FILES[$files_key]['name'])||!is_uploaded_file($_FILES[$files_key]['tmp_name'])) ## safety checks
				{
					unset($_FILES[$files_key]);
					if(isset($HTTP_POST_FILES[$files_key])) unset($HTTP_POST_FILES[$files_key]);
				}
	 		}
	}
}
?>