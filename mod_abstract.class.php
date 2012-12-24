<?php
# abstract classes shared between V2 engine and modules
# "(...) I'm breaking, I'm falling apart, all for the sake of my ART! (...)"
abstract class VBase
{
	protected $CNF=array();
	protected $DB;
	protected $TPL;
	protected $REQ;
	protected $USR;
	protected $AUTH;
	protected $MSG=array();
	protected $output='';
	protected $found_rows=0;
	const FETCH_DATA=0;
	const DB_OBJECT=1;
	const EMAIL_PREG='(\\w+(-|\\.)?)*\\w+@(((\\w+-?)*\\w+\\.)+\\w{2,4})';
	const DOMAIN_PREG='(((\\w+-?)*\\w+\\.)+\\w{2,4})';
# define('LOGIN_PREG','/^\\w+(-\\w+)*$/');
# define('LOGIN_UNALLOWED_PREG','/[\'"!@#\\$%\\^&\\*\\(\\)\\+=`~,<>;\\.\\/\\?\\:\\|\\]\\{\\}]/');
# define('PHONE_PREG','/\+?\s*(\d(\s*-?\s*)){7,}/');
	protected function set_main_template($name)
	{
		$this->CNF['MAIN_TEMPLATE']=$name;
	}
	protected function disable_tags()
	{
		$this->CNF['TAGS_SYSTEM']='no';
	}
	protected function load_config($xmlfile)
	{
		if(!preg_match('#^\w+$#',$xmlfile)||!file_exists('etc/'.$xmlfile.'.xml')) return;
		$xml = new SimpleXMLElement(file_get_contents('etc/'.$xmlfile.'.xml'));
		$cnf=array();
		for($i=0;$i<$xml->count();$i++) $cnf[(string)$xml->option[$i]['id']]=(string)$xml->option[$i];
	   $this->CNF=array_merge($this->CNF,$cnf);
	}
	protected function load_language_messages($mod_name)
	{
		if($mod_name=='error'||!preg_match('#^\w+$#',$mod_name)) return;
		$trans_path='./translations/'.$mod_name.'_'.strtolower($this->CNF['ln']).'.xml'; ## read in the engine messages
		$msgs=array();
		if(file_exists($trans_path))
		{
			$xml = new SimpleXMLElement(file_get_contents($trans_path));
	      for($i=0;$i<$xml->count();$i++) $msgs[(string)$xml->msg[$i]['id']]=array('content'=>(string)$xml->msg[$i],'type'=>(string)$xml->msg[$i]['type']);
	      $this->MSG=array_merge($this->MSG,$msgs);
		}	
		else
			$this->msg_raw('The language you choosed seems to be not installed.','error');
	}	
	protected function send_language_messages()
	{
		// because of MSG structure we can't use array2xmltag, oh well
		 $msg_tags='';
		 foreach($this->MSG as $msg_key=>$msg) $msg_tags.='<msg type="'.$msg['type'].'" id="'.$msg_key.'">'.$msg['content']."</msg>\n";
		 return '<messages ln="'.$this->CNF['ln'].'">'.$msg_tags.'</messages>';
	}
	protected function msg($id,$holder='') // [ READY ] // $this->msg('OK');
	{
		if($this->REQ->get('ajax'))
		$this->output.='<'.$this->MSG[$id]['type'].'>'.$this->MSG[$id]['content'].'</'.$this->MSG[$id]['type'].'>';
		else
		{
			$this->TPL->assign($id,$this->MSG[$id]['content']);
			$this->TPL->assign($this->MSG[$id]['type'],$this->MSG[$id]['content']);
			if($holder) $this->TPL->assign($holder,$this->MSG[$id]['content']); 
		}
	}
	protected function msg_raw($msg,$type='info') // [ READY ] // $this->msg('OK');
	{
		if($this->REQ->get('ajax'))
		$this->output.='<'.$type.'>'.$msg.'</'.$type.'>';
		else
		$this->TPL->assign($type,$msg);
	}
	protected function link_hyperlinks($content) ## instead of bbcode, now it's only this
	{
		return preg_replace('#\s*(http(s?)://'.self::DOMAIN_PREG.'/\S*)#','<a href="\1" target="_blank">\1</a>',$content);
	}
	protected function get_args()
	{
		return array('CNF'=>$this->CNF,'MSG'=>&$this->MSG,'TPL'=>$this->TPL,'USR'=>$this->USR,'DB'=>$this->DB,'REQ'=>$this->REQ,'AUTH'=>$this->AUTH);
	}
}
### abstract class for all modules and libraries
abstract class vengine_mod  extends VBase
{
	protected $attributes;
	private $obligatory_index;
	protected function validate_data($rules,&$data_arr)
	{
			if(!$rules) 
			{
				$this->msg('VE_NO_VALUES');
				return;
			}
			$status=1;
			foreach($rules as $rule)
			{
				if((!isset($rule[$this->obligatory_index])||$rule[$this->obligatory_index]!='yes')&&!isset($data_arr[$rule['attribute_id']])) continue;
				if($rule['preg']&&(!isset($data_arr[$rule['attribute_id']])||!preg_match('#^'.$rule['preg'].'$#s',$data_arr[$rule['attribute_id']]))) 
				{
						$this->msg($rule['error']);
						$status=0;
				}						
			}
			return $status;
	}
	protected function get_subpages()
	{
		$sublinks=array();
		for($i=1;$i<=ceil($this->found_rows/$this->CNF['PER_PAGE']);$i++) $sublinks[]=$this->REQ->link_self(array('action'=>'list','page'=>$i),$i);
		if(count($sublinks)==1) return array();
		return $sublinks;
	}
	protected function generate_hash($long)
   {
        return substr($this->AUTH->get_rand_string($long,$long+5),0,$long);
   }
	protected function display_interface($mod_name,$template_name='') // [READY]
	{
		// tutaj dopiszemy sciaganie info o tytule
		$title=$this->DB->query('SELECT mod_label FROM module_labels WHERE mod_name=:mod_name AND mod_ln=:ln',array(':mod_name'=>$mod_name,':ln'=>$_SESSION['ln']));
		if($title)
		{
			$this->TPL->assign('title','<script>document.title="'.$title[0]['mod_label'].'"</script>');
		}
		if(!$template_name) $template_name=$mod_name.'_mod.tpl';
		$this->output=$this->TPL->fetch($template_name);
		// to tez mogloby byc dziedziczone
	}
	protected function array2xmltag($tagname,$arr,$content='')
	{
		#print_r($arr);
		if(isset($arr[0])&&is_array($arr[0]))
		{
			#echo "arr2xml debug: ".print_r($arr[0],1)."<br />";
			$tags='';
			foreach($arr as $arr_row)
			{
				$impl='';
				foreach($arr_row as $key=>$val)
				{
					$impl.=' '.$key.'="'.$val.'"';	
				}
				$tags.="<$tagname$impl />";
			}
			return $tags;
		}
		else 
		{
			$impl='';
			foreach($arr as $key=>$val) 
			{
					$impl.=' '.$key.'="'.$val.'"';
			}
			if($content) return "<$tagname $impl>$content< /$tagname>";
			return "<$tagname$impl />";
		}
	}
	private function embed_language_to_tpl($smarty_obj) 
	{ 
		foreach(array_keys($this->MSG) as $id) $smarty_obj->assign($id,$this->MSG[$id]['content']);
	}
	public function init($args)
	{
		$this->CNF=$args['CNF'];
		$this->DB=$args['DB'];
		$this->TPL=$args['TPL'];
		$this->REQ=$args['REQ'];
		$this->USR=$args['USR'];
		$this->AUTH=$args['AUTH'];
		$this->MSG=&$args['MSG'];
		$this->load_config(get_called_class());
		if(isset($this->CNF['CURR_LIB']))	$this->load_language_messages($this->CNF['CURR_LIB']);
		else 
		$this->load_language_messages($this->CNF['CURR_MOD']);
		$this->embed_language_to_tpl($this->TPL);
	}
	## prefedefined use cases working by the rules found in the definitons file (./entities/modname_entity.xml)
	protected function get_entity_rules()	// READY
	{
		$this->attributes=array('attribute_id'=>-1,'obligatory_create'=>'no','obligatory_update'=>'no','preg'=>'','class_input'=>'','class_displayer'=>'','class_saver'=>'','default'=>'','search'=>'yes','edit'=>'yes','insert'=>'yes','display'=>'yes', 'info'=>'','error'=>'VE_VALIDATION_ERR','order_by'=>'no','private'=>'no','type'=>'','label'=>'','display_label'=>'no');
		// schema + module
		$rules=array();
		$path='./entities/'.$this->CNF['CURR_MOD'].'_'.$this->REQ->get('action').'.xml';
		if(!file_exists($path)) $path='./entities/'.$this->CNF['CURR_MOD'].'.xml';
		if(!file_exists($path))
		{
			$this->msg('VE_ENTITY_DEFINITION_NOT_FOUND');
			return;
		}
		$rules_xml=new SimpleXMLElement(file_get_contents($path));
		$rules['module']=(string)$rules_xml['module'];
		$rules['schema']=(string)$rules_xml['schema'];
		$rules['multiple_savers']=(string)$rules_xml['multiple_savers'];
		if(!$rules['multiple_savers']) $rules['multiple_savers']='no';
		if($rules['module']!=$this->CNF['CURR_MOD']) 
		{
			$this->msg('VE_MOD_ENTITY_MISMATCH');
			return;
		}
		for($i=0;$i<$rules_xml->count();$i++) 
		{
			$new_attr=array();
			foreach($this->attributes as $name=>$default)
			{
			   if((string)$rules_xml->attr[$i][$name]) 
			   {
			   	if($name=='attribute_id'&&in_array((string)$rules_xml->attr[$i][$name],array_keys($this->attributes)))
			   	{
			   		$this->msg('VE_ENTITY_DB_XML_CONFLICT');
			   		return;
			   	}
			   	$new_attr[$name]=(string)$rules_xml->attr[$i][$name];
			   }
				else
				{
					if($name=='attribute_id') 
					{
						$this->msg('VE_ENTITY_NO_ID');
						return;
					}
					$new_attr[$name]=$default;
				}
			}
			# if($new_attr['type']=='file')
			if($new_attr['type']=='list') 
			{
				preg_match_all('#\{\$(\w+)\}#',$new_attr['default'],$out);
				if(count($out[0])>1) 
				{
					for($j=0;$j<count($out[0]);$j++) $new_attr['default']=str_replace('{$'.$out[1][$j].'}',$this->MSG[$out[1][$j]]['content'],$new_attr['default']); 
				}
				$options=explode(',',$new_attr['default']);
				$new_attr['default']=array();
				foreach($options as $option)
				{
					$opt=explode(':',$option);
					$new_attr['default'][$opt[0]]=$opt[1];
				}
			}
			$rules['entities'][]=$new_attr;
		}
		return $rules;
	} 
	// validate data is just simple $rules['entities'] arr :D
	protected function update($no_db_write=0,$no_msg=0)	// READY
	{
		$this->obligatory_index='obligatory_update';
		$id=$this->REQ->get('id');
		if(!$id)
		{
			if(!$no_msg) $this->msg('VE_NO_ID_FOR_UPDATE');
			return;
		}
		$rules=$this->get_entity_rules();
		$sql='UPDATE '.$rules['schema'].' SET ';
		$fields=array();
		$values=array();
		$validate=array();
		foreach($rules['entities'] as $rule)
		{
			if((isset($rule['edit'])&&$rule['edit']=='no')||$this->REQ->post($rule['attribute_id'])===false) continue;
			$fields[]=$rule['attribute_id'].'=:'.$rule['attribute_id'];
			$validate[]=$rule;
			$values[':'.$rule['attribute_id']]=$this->REQ->post($rule['attribute_id']);
		}
		if(!$this->validate_data($validate,$_POST)) return;
		$values[':id']=$id;
		$sql.=implode(',',$fields).' WHERE id=:id';
		#print_r($sql);
		if($no_db_write) return array('sql'=>$sql,'params'=>$values);
		if(!$this->DB->query($sql,$values))
		{
			if(!$no_msg) $this->msg('VE_DB_WRITE_FAILED');
			return;
		}
		if(!$no_msg) $this->msg('VE_OK');
		return 1;
	}
	protected function create($no_db_write=0,$no_msg=0)
	{
		$this->obligatory_index='obligatory_create';
		$rules=$this->get_entity_rules();
		$sql='INSERT INTO '.$rules['schema'].' SET user_id=:user_id,';
		$fields=array();
		$values=array();
		$validate=array();
		foreach($rules['entities'] as $rule)
		{
			if((isset($rule['insert'])&&$rule['insert']=='no')||$this->REQ->post($rule['attribute_id'])===false) continue;
			$fields[]=$rule['attribute_id'].'=:'.$rule['attribute_id'];
			$values[':'.$rule['attribute_id']]=$this->REQ->post($rule['attribute_id']);
			$validate[]=$rule;
		}
		if(!$this->validate_data($validate,$_POST)) return;
		$values[':user_id']=$this->USR->get_uid();
		$sql.=implode(',',$fields);
		if($no_db_write) return array('sql'=>$sql,'params'=>$values);
		if(!$this->DB->query($sql,$values))
		{
			if(!$no_msg) $this->msg('VE_DB_WRITE_FAILED');
			return;
		}
		if(!$no_msg) $this->msg('VE_OK');
		return 1;
	}
	protected function delete($no_db_write=0,$no_msg=0)
	{
		$id=$this->REQ->get('id');
		if(!$id)
		{
			if(!$no_msg) $this->msg('VE_NO_ID_FOR_DELETE');
			return;
		}
		$rules=$this->get_entity_rules();
		$sql='DELETE FROM '.$rules['schema'].' WHERE id=:id';
		$values=array(':id'=>$id);
		if($no_db_write) return array('sql'=>$sql,'params'=>$values);
		if(!$this->DB->query($sql,$values))
		{			
			if(!$no_msg) $this->msg('VE_DB_WRITE_FAILED');
			return;
		}
		if(!$no_msg) $this->msg('VE_OK');
		return 1;
	}
	protected function search($no_db_query=0,$filter=array())
	{	
		$rules=$this->get_entity_rules();
		$criteria=$selects=array();
		$sql='SELECT SQL_CALC_FOUND_ROWS DISTINCT '.$rules['schema'].'.id,';
		$params=array();
		$order_by=array();
		$order_by_direction='desc';
		foreach($rules['entities'] as $entity)
		{
			if($entity['search']&&isset($filter[$entity['attribute_id']])&&$filter[$entity['attribute_id']]&&(!isset($entity['private'])||$entity['private']=='no'))
			{
				 	$criteria[]=$entity['attribute_id'].'=:'.$entity['attribute_id'];
				 	$params[':'.$entity['attribute_id']]=$filter[$entity['attribute_id']];
			}
			if(isset($filter['substr'])&&isset($filter['substr'][$entity['attribute_id']])&&is_numeric($filter['substr'][$entity['attribute_id']])&&$filter['substr'][$entity['attribute_id']]>0)
			$curr_inset='SUBSTR('.$rules['schema'].'.'.$entity['attribute_id'].',1,'.$filter['substr'][$entity['attribute_id']].') '.$entity['attribute_id'];
			else
			$curr_inset=$rules['schema'].'.'.$entity['attribute_id'];				
			if(isset($entity['private'])&&$entity['private']=='yes')
			$selects[]='IF(id='.$this->USR->get_uid().','.$curr_inset.',0) AS '.$entity['attribute_id'];
			else
			$selects[]=$curr_inset;					
			if(isset($filter['order_by'])&&is_array($filter['order_by'])&&count($filter['order_by'])>0)
			{
				foreach($filter['order_by'] as $filter_order_by)
				{
					if($filter_order_by==$entity['attribute_id']&&isset($entity['order_by'])&&$entity['order_by']!='no')
					{
						$order_by[]=$entity['attribute_id'];
						break;
					}
				}
			}
			else
			if(isset($entity['order_by'])&&$entity['order_by']=='yes') $order_by[]=$entity['attribute_id'];
		}
		if(isset($filter['order_by_direction'])&&in_array($filter['order_by_direction'],array('asc','desc'))) $order_by_direction=$filter['order_by_direction'];
		$group_by='';
		$sql.=implode(',',$selects).' FROM  '.$rules['schema'];
		## ACL integration
		$sql.=" INNER JOIN acl ON (acl.mod_name=:module_name AND acl.action='show' AND ((acl.schema_name='{$rules['schema']}' AND {$rules['schema']}.user_id='1')	OR 	((acl.acl_res_id={$rules['schema']}.id OR acl.acl_res_id=0) AND (acl.acl_uid=:user_id OR acl.acl_uid=0) AND acl.acl_gid IN (".implode(',',$this->USR->get_gid())."))))";
		## someone's either an owner or permitted user
		$params[':module_name']=$this->CNF['CURR_MOD'];
		$params[':user_id']=$this->USR->get_uid();
		
		if($this->REQ->get('subaction')) ## category specified
		{
		 	## test this one and then implement hierarchy (IN (GET_SUBCATEGORIES(parent_id)) instead of =)
			$sql.=' INNER JOIN categories_assignment ON '.$rules['schema'].'.id=categories_assignment.res_id';
			$criteria[]='categories_assignment.cat_id=:vcategory';
			$criteria[]='categories.schema_name=:vcatschema';
			$params[':vcategory']=$this->REQ->get('subaction');
			$params[':vcatschema']=$rules['schema'];
		}
		$offset=$this->CNF['PER_PAGE'];
		$page=(int)$this->REQ->get('page');
		if(!$page||$page<0) $page=1;
		$page=($page-1)*$offset;
		if(count($criteria)) $sql.=' WHERE '.implode(' AND ',$criteria).' GROUP BY '.$rules['schema'].'.id';
		if(count($order_by)>0) $sql.=' ORDER BY '.implode(',',$order_by).' '.$order_by_direction;	
		$sql.= " LIMIT $page,$offset";
		// + order by 
		if($no_db_query) return array('sql'=>$sql,'params'=>$criteria);
		$res=$this->DB->query($sql,$params,1);
		$found_rows=0;
		if(!$no_db_query)
		{
			 $found_rows=$this->DB->query('SELECT FOUND_ROWS() found_rows',array(),1);
			 $this->found_rows=$found_rows[0]['found_rows'];
		}
		#echo "[DEBUG: $sql]\n";
		return $res;		
	}
	protected function show($no_db_query=0)
	{
		$id=$this->REQ->get('id');
		if(!$id)
		{
			$this->msg('VE_INVALID_ID');
			return;
		}
		$rules=$this->get_entity_rules();
		if(!is_array($rules['entities']))
		{
			$this->msg('VE_ENTITY_CORRUPTED');
			return;
		}
		$selects=array();
		$sql='SELECT id,';
		foreach($rules['entities'] as $entity)
		{
				if($entity['private']=='yes')
				{
					if($entity['display']=='no'&&$entity['edit']=='no') continue;
					$selects[]='IF(id='.$this->USR->get_uid().','.$entity['attribute_id'].',0) AS '.$entity['attribute_id'];
				}
				else
				$selects[]=$entity['attribute_id'];
		}
		$sql.=implode(',',$selects).' FROM  '.$rules['schema'].' WHERE id=:id';
		$params[':id']=$id;
		if($no_db_query) return array('sql'=>$sql,'params'=>$params);
		$result=$this->DB->query($sql,$params);
		return $result;
	}
	protected function merge_rules_with_contents($rules,$contents) // this merges entity rules set with record's data from the DB, so it can be easily used by the standard displayer-form generator
	{
		$new_rules=array();
		foreach($rules['entities'] as $rule)
		{
			$rule['info']=isset($this->MSG[$rule['info']]['content'])?$this->MSG[$rule['info']]['content']:'';
			$rule['error']=isset($this->MSG[$rule['error']]['content'])?$this->MSG[$rule['error']]['content']:'';
			$rule['label']=isset($this->MSG[$rule['label']]['content'])?$this->MSG[$rule['label']]['content']:'';
			foreach($contents as $key=>$content)
			{
				if($key=='id') $rule['id']=$content; ## add primary key to the results
				if($key==$rule['attribute_id']) 
				{
					$rule['content']=$content;
					if($rule['type']=='list')
					{
						$new_list=array();
						$list_start=array();
						if(is_array($rule['default']))
						foreach($rule['default'] as $key=>$val)
						{
							if($val==$content)
							$list_start[$key]=$val;
							else
							$new_list[$key]=$val;
						}
						$rule['default']=array_merge($list_start,$new_list); ## change the sequence to keep content as default value
				 	}
					$new_rules[]=$rule;
					break;
				}
			}
		}
		return $new_rules;
	}
	protected function get_entity_form($contents,$autoform='') // parametrized in the case you'd like to make your own custom forms
	{
		$this->TPL->assign('rules',$this->merge_rules_with_contents($this->get_entity_rules(),$contents));
		if(!$autoform) $autoform='_standard_autoform.tpl';
		foreach($this->MSG as  $msgkey=>$msg) $this->TPL->assign($msgkey,$msg['content']);
		$this->TPL->assign('CURR_MOD',$this->CNF['CURR_MOD']);
		if($this->AUTH->permit($this->CNF['CURR_MOD'],'update',$this->USR->get_gid(),$this->USR->get_uid(),1)) $this->TPL->assign('VCAN_EDIT',1);
		if($this->AUTH->permit($this->CNF['CURR_MOD'],'create',$this->USR->get_gid(),$this->USR->get_uid(),1)) $this->TPL->assign('VCAN_CREATE',1);
		if($this->AUTH->permit($this->CNF['CURR_MOD'],'delete',$this->USR->get_gid(),$this->USR->get_uid(),1)) $this->TPL->assign('VCAN_DELETE',1);
		return $this->TPL->fetch('_standard_autoform.tpl');
	}
}
?>