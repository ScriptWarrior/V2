<?php
class VDHttpRequest 
{
	  private $_varseparator=',';
	  private $_request_suffix='';
	  private $_request_prefix='';
	  private $MSG;
	  private $CNF;
	  private $DB;
	  private $_get_params=array();
	  private $csrf_map=array();
	  private $strategy=array();
	  public function __construct($separator,$strategy,$suffix,$DB,$CNF)
	  {
	  		$this->DB=$DB;
	  		$this->CNF=$CNF;
	  		$this->_request_suffix=$suffix;
		  	$new_strategy=array();
		  	$strategy=explode(',',$strategy);
		  	foreach($strategy as $strat) 
		  	{
			  	$strat=explode('=',$strat);
				$new_strategy[$strat[0]]=isset($strat[1])?$strat[1]:'';
		  	}
	  		$this->strategy=$new_strategy;
	  		#print_r($this->strategy);
	  		$this->_varseparator=$separator;
	  		if($separator=='&=')
	  		{
				$this->_get_params=$_GET;		  			
	  		}
	  		else
	  		{	
	  			$this->_get_params=explode($separator,preg_replace('#'.preg_quote($this->_request_suffix).'$#','',$_SERVER['QUERY_STRING']));
	  		}
	  		## there's one more thing we have to do - load CSRF protection map
	  		$map=$this->DB->query('SELECT mod_name,action FROM acl WHERE csrf_protect=1',array(),1);
	  		if($map) foreach($map as $m) $this->csrf_map[$m['mod_name']][$m['action']]=1;
	  }
	  public function send_strategy()
	  {
	  		$cookie_strategy=array();
	  		foreach($this->strategy as $strategy_element_key=>$strategy_element) 
	  		{
		  		if($strategy_element_key=='csrf_code') $strategy_element=$_SESSION['VENGINE']['CSRF_CODE'];
	  			$cookie_strategy[]="$strategy_element_key=$strategy_element";
			}
	  		setcookie('http_req_strategy',implode(',',$cookie_strategy)); // this has to stay
	  }
	  public function get($param_name)
	  {
	  		$param_num=array_search($param_name,array_keys($this->strategy),TRUE); // przemapowanie nazwy na numer
	  		if($param_num===FALSE)
	  		{
	  			# $this->msg('VE_UNKNOWN_PARAMETER');
	  			echo "UNKNOWN PARAMETER :$param_name!"; // this is fucked ;]
	  		}
			if(!empty($this->_get_params[$param_num])) return $this->_get_params[$param_num];
			return ''; 
	  }
		// this is just for usage of not nice urls if needed, for example with ajax search queries 
	  public function get_raw($param_num)
	  {
			if(!isset($_GET[$param_num])) $_GET[$param_num]='';
			return $_GET[$param_num]; 
	  }
	  public function set($param_name,$value)
	  {
	  		$param_num=array_search($param_name,array_keys($this->strategy),TRUE); // przemapowanie nazwy na numer
	  		if($param_num===FALSE)
	  		{
	  			$this->msg('VE_UNKNOWN_PARAMETER');
	  		}
			return $this->_get_params[$param_num]=$value;
	  }
	  public function post($param_name)
	  {
	  		return isset($_POST[$param_name])?$_POST[$param_name]:false; 
	  }
	  // used to link, if parameter is not specified it's set to its default value ('')
	  private function _link($params,$label='',$target='_self',$fill_empty_with='strategy') ## or self
	  {
	  		$uri_params=array();
	  		$mod='';
	  		$action='';
	  		foreach(array_keys($this->strategy) as $uri_part) 
	  		{
	  			 if(isset($params[$uri_part])) $uri_params[]=$params[$uri_part]; else $uri_params[]=($fill_empty_with=='strategy')?$this->strategy[$uri_part]:$this->get($uri_part);
	  		}
			if($this->is_csrf_protected($uri_params[array_search('module',array_keys($this->strategy))],$uri_params[array_search('action',array_keys($this->strategy))])) 
				$uri_params[array_search('csrf_code',array_keys($this->strategy))]=$_SESSION['VENGINE']['CSRF_CODE'];
			else
				$uri_params[array_search('csrf_code',array_keys($this->strategy))]='';
		   $href=$this->_request_prefix.implode($this->_varseparator,$uri_params).$this->_request_suffix;
		   $label=($label==''?$href:$label);
		   $prefix='';
		   if(isset($params['full'])&&$params['full']) $prefix='http://'.$this->CNF['SITE_NAME'].'/';
		  	return '<a href="'.$prefix.$href.'" target="'.$target.'">'.$label.'</a>';
	  }
	  public function is_csrf_protected($mod,$action)
	  {	
		  return isset($this->csrf_map[$mod][$action]);
	  }
	  public function link($params,$label='',$target='_self')
	  {	
			return $this->_link($params,$label,$target,'strategy');
	  }
	  // used to 'selflink', all default (not specified in $params parameters are copied from the current link instead from the strategy array
  	  public function link_self($params,$label='',$target='_self')
	  {	
			return $this->_link($params,$label,$target,'self');
	  }
}
?>