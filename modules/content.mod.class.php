<?php
## Universal vildev module for displaying content (news/articles/agreements/anything - just to show, edit, list)
class content extends vengine_mod
{
  public function engine()
  {
	switch($this->REQ->get('action'))
	{
		case 'update' : 
		    $this->update(); // AJAX
		    break;
		case 'create' :
			$this->create();	// AJAX
			 break;
		case 'delete' : 
			$this->delete();	// AJAX
			break;
		case 'show' :
			$news=$this->show();
			$news=$news[0];
			if($this->REQ->get('ajax'))
				$this->output.=$this->array2xmltag('news',$news);	
			else
				$this->output.=$this->get_entity_form($news);
			break;
		default :  // list 'alias' ;D
		{
			$news=$this->search(self::FETCH_DATA,array('substr'=>array('content'=>$this->CNF['SHORT_CONTENT_LENGTH']),'include_in_list'=>'1')); // Smarty (filter temporary not implemented)
			$news_parsed=array();
			if(!$news)
			$this->msg('VE_NO_RESULTS');
			else
			{
				if(!$this->REQ->get('ajax'))
				{
					foreach($news as $n)
					{
						$n['content'].=' ...';
						$short=strtolower(preg_replace('#\W+#','',preg_replace('#\s+#','_',$n['title'])));
						$n['link']=$this->REQ->link(array('module'=>'content','action'=>'show','id'=>$n['id'],'ajax'=>0,'subaction'=>$short),$n['title']);
						$news_parsed[]=$n;
					}
					$this->TPL->assign('news',$news_parsed);
					$this->TPL->assign('pager',$this->get_subpages());
				}
				else
				$this->output.=$this->array2xmltag('news',$news);	
			}
			if(!$this->REQ->get('ajax')) $this->output.=$this->TPL->fetch('news_list_mod.tpl');
		}
	}
	return $this->output;
  }
}
?>