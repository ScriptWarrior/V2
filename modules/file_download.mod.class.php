<?php
## File download V2 special module (do not touch this if you're not sure what you're doing)
class file_download extends vengine_mod
{
  public function engine()
  {
		$id=$this->REQ->get('id');
		if(!$id||!$f=$this->DB->query('SELECT user_id,filename,mime_type,height,width FROM file_published WHERE id=:id',array(':id'=>$id)))
		{
			$this->msg('VE_INVALID_ID');
			return $this->output;
		}
		header('Content-Type: '.$f['mime_type']);
		echo file_get_contents($this->CNF['UPLOAD_DIR'].'/'.$f['user_id'].'/'.$f['filename']);
		return '';
  }
}
?>