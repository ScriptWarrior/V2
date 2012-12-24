<?php
// authorization system
class Vauth
{
	private $REQ;
	private $DB;
	private $action_log_status=array('OK'=>1,'DENIED'=>2,'NOT_FOUND'=>3,'CSRF_ATTACK'=>4);
	public function __construct($REQ,$DB,$CNF)
	{
		$this->REQ=$REQ;
		$this->DB=$DB;
		$this->CNF=$CNF;
		if(!isset($_SESSION['VENGINE']['CSRF_CODE'])) $_SESSION['VENGINE']['CSRF_CODE']='';
	}
	public function get_rand_string($min=10,$max=40)
	{
		$arr='AaB0bCcD1dEeF2fGgHh3IiJjK5kLlMm9NnOo4PpQqR6rSsTtUu8VvWw7XxYyZ9z';
		$min=mt_rand($min,2*$min);
		$max=mt_rand($max,2*$max);
		if($min>$max) 
		{
			$tmp=$min;
			$min=$max;
			$max=$tmp;
		}
		if($max-$min<5) $max+=10;
		$ret='';
		for($i=$min;$i<$max;$i++) $ret.=$arr[mt_rand(0,strlen($arr)-1)];
		return $ret;
	}
	public function generate_csrf_code()
	{
		$_SESSION['VENGINE']['CSRF_CODE']=$this->get_rand_string();
		$this->REQ->set('csrf_code',$_SESSION['VENGINE']['CSRF_CODE']); // to get it sent with cookie to JS
	}
	public function check_csrf_code($mod,$action) // Cross Site Request Forgery protection
	{
		if($this->CNF['CSRF_PROTECT']=='no'||!$this->REQ->is_csrf_protected($mod,$action)) return 1;
		$ref=isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'';
		if(($ref===''||preg_match('#http(s?)://'.preg_quote($_SERVER['SERVER_NAME']).'/#',$ref))&&$this->REQ->get('csrf_code')===$_SESSION['VENGINE']['CSRF_CODE']) return 1;
		#echo "KURWA ".$this->REQ->get('csrf_code')."!==".$_SESSION['VENGINE']['CSRF_CODE'].", albo referer jest iwul: $ref<hr />";
		return 0;
	}
	private function log_action($mod,$action,$res_id,$uid,$status,$do_log=1)
	{
		if($do_log)	$this->DB->query("INSERT INTO auth_log (module_name,action,res_id,usr_id,operation_status) VALUES (:mod,:action,:res_id,:uid,:status)",array(':mod'=>$mod,':action'=>$action,':res_id'=>'',':uid'=>$uid,':status'=>$status));
		if($status!=$this->action_log_status['OK']) return 0;
		return $status;
	}
	public function permit($mod,$action,$curr_gids,$curr_uid,$dummy=0)
	{
		$id=$this->REQ->get('id'); // this is ID param, it can be empty, we'll see
		if(!$id) $id=0;
		if(!$dummy&&!$this->check_csrf_code($mod,$action)) return $this->log_action($mod,$action,$id,$curr_uid,$this->action_log_status['CSRF_ATTACK']);
		// one statement pulls out all ACL records, amongst which there has to be sufficient permission for current action defined, if it's not found, then the permission is denied
		// permission granting is based either on the per user/per group permission (acl_cuid,acl_gid) OR on the fact that current user is the resource owner, which is estimated by dynamically generating additional statement looking for the corresponding resource record with pk_name=ID uid_col_name=CURR_UID in the schema table to check if the current user is its owner
		$acl_map=$this->DB->query('SELECT aid,action,schema_name,logging_system,acl_res_id,acl_uid,acl_gid FROM acl WHERE mod_name=:mod_name AND action=:action AND (acl_res_id=:id OR acl_res_id=0)',array(':mod_name'=>$mod,':action'=>$action,':id'=>$id));
		if(!$acl_map||!is_array($acl_map)) return $this->log_action($mod,$action,$id,$curr_uid,$this->action_log_status['NOT_FOUND']);
		foreach($acl_map as $aclr)
		{
			// dla kazdego zasobu moze  byc wiecej rekordow (np. kilka dodatkowych przydzialow uprawnien dla kilku grup na jednym zasobie)
			// musza byc niepuste, inaczej dojdzie do zaburzenia funkcjonalnosci (np. wpis z uid==1337 i gid ==0 udzieli praw komus o uid 0 i gid 0, tak byc nie powinno
			foreach($curr_gids as $curr_gid)
			{
				if($curr_uid&&$curr_gid)
				{
					if($curr_uid==$aclr['acl_uid']) return $this->log_action($mod,$action,$id,$curr_uid,$this->action_log_status['OK'],$aclr['logging_system']);
					if($curr_gid==$aclr['acl_gid']) return $this->log_action($mod,$action,$id,$curr_uid,$this->action_log_status['OK'],$aclr['logging_system']);
				}
			}
			// rekord z id, gid i uid == 0 oznacza wjazd dla wszystkich, pod warunkiem, ze nie okreslono pk_name, schema_name oraz uid_col_name
			if($aclr['schema_name']&&$id)
			{
				// jest okreslony ID, co oznacza, ze nalezy zasiegnac info do tabeli docelowej (w tym wypadku metadane o kolumnach nie moga byc puste)
				$q='SELECT id FROM '.$aclr['schema_name'].' WHERE user_id=:uid  AND id=:pk';
				$p=array(':uid'=>$curr_uid,':pk'=>$id);
				if($this->DB->query($q,$p)) return $this->log_action($mod,$action,$id,$curr_uid,$this->action_log_status['OK'],$aclr['logging_system']);
			}
			else
			{ ## schemata metadata is empty, so if uid and gid are empty, it doesn't matter what ID is - access is granted to all
			  if($aclr['acl_uid']==0&&$aclr['acl_gid']==0) return $this->log_action($mod,$action,$id,$curr_uid,$this->action_log_status['OK'],$aclr['logging_system']);
			}
		}
		return $this->log_action($mod,$action,$id,$curr_uid,$this->action_log_status['DENIED']);
	}
}
?>