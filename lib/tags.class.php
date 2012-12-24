<?php
// google tags
class tags extends vengine_mod
{	 
	private $tags=array();
	private $size_classes=array();
	public function get($start=0,$stop=0)   // READY
	{ 
		if(!$stop) $stop=count($this->tags)-1;
		$result=array();
		for($i=$start;$i<=$stop;$i++) $result[]=$this->tags[$i];
		return $result;
	}
	public function count()	  // READY
	{
		return count($this->tags);
	}
	private function tag_exists($tag)	// READY
	{
		$id=$this->DB->query('SELECT id FROM tags_system WHERE tag_value=:tag AND ln=:ln',array(':ln'=>$this->CNF['ln'],':tag'=>$tag));
		if(isset($id[0]['id'])) return $id[0]['id'];
		return 0;
	}
	private function blacklisted_tag_exists($tag)	// READY
	{
			$id=$this->DB->query("SELECT id FROM tags_system WHERE tag_value=:tag AND blacklisted='1'",array(':tag'=>$tag));
			if(isset($id[0]['id'])) return $id[0]['id'];
			return 0;
	}
	private function add_raw_tag($tag,$rate=1)	// READY
	{
			$tag=strotolower($tag);
			$tag=preg_replace('#\s{2,}#','',$tag);
			if($id=$this->tag_exists($tag)) return $this->DB->query('UPDATE tags_system SET hits=hits+1 WHERE id=:id',array(':id'=>$id));
			return $this->DB->query('INSERT INTO tags_system (tag_value,hits,ln) VALUES (:tag,:hits,:ln)',array(':tag'=>$tag,':hits'=>$rate,':ln'=>$this->CNF['ln']));
	}
	private function add_tags_string($tags_string)
	{
		$tags_string=preg_replace('/\+/',' ',trim($tags_string));
		$tags_string=preg_replace('/\s+/',' ',$tags_string);
		$new_tags=explode(' ',$tags_string);
		foreach($new_tags as $ntag) if(preg_match($this->valid_tag_regex,$ntag)&&$this->blacklisted_tag_exists($ntag)) return;
		$this->add_raw_tag($tags_string); // add to db
		return 1;
	}
	public function scan_for_tags()	// READY
	{
		if(!isset($_SERVER['HTTP_REFERER'])||!$_SERVER['HTTP_REFERER']) return;
		if(!preg_match('/google.\w+\/search\?.*?&?q=(.*?)&/',$_SERVER['HTTP_REFERER'],$matches)) return;
		$this->add_tags_string($match[1]);
	}
	private function load_tags()	// READY
	{
		// a ogole to mozna zatrudnic do tego SQL
		$inset='';
		$params=array();
		if($this->CNF['RESTRICT_LANG']=='yes')
		{
			$inset=' WHERE ln=:ln';
			$params[':ln']=$this->CNF['ln'];	// READY
		}
		$tags=$this->DB->query("SELECT hits rate,tag_value FROM tags_system$inset ORDER BY RAND()",$params,1);
		$arr=explode(';',$this->CNF['SIZE_CLASSES']);
		$ar_parts=array();
		foreach($arr as $arr_single) 
		{
			$arr_parts=explode(',',$arr_single);
			$this->size_classes[$arr_parts[0]]=$arr_parts[1];
		}
		$k=array_reverse(array_keys($this->size_classes));
		if(is_array($tags)&&count($tags)>0)
		foreach($tags as $tag)
		{
			for($i=1;$i<7;$i++)
			{
				if(floor($tag['rate']/$this->size_classes["h$i"]))
				{
					$tag['size_class']=$i;
					break;
				}
			}
			if(!isset($tag['size_class'])) $tag['size_class']=6;;
			$this->tags[]=$tag;
		}
	}
	public function __construct($args)
	{	
			$this->init($args);
			$this->load_tags();
	}
}
?>