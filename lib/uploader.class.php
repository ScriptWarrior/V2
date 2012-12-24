<?php
// this is general file uploading V2 class (now it only supports files)
### THIS SHIT IS NOT WORKING NOW - STILL IN DEVELOPMENT, STATUS - UNKNOWN
### 
class uploader extends vengine_mod
{
	private $sess_files;
	private $last_uploaded_status; // this contains link to last uploaded file
	private $file_dir;	// CONF, do not set this manually here, see manual (UPLOADER-README)
	private $thumb_dir;
	// saves information about uploaded files in session
	public function session_load_files($files) 
	{
		$this->flush_files();
		$free_index='file_';
		$i=0;
		foreach($files as $file) 
		{
			$file['file_path']=preg_replace('#'.$this->file_dir.'#','',$file['file_path']); // assembling paths (full + thumb) 
			// sciezke i nazwe pliku osobno, aby obslugiwac miniatury bez dodatkowych metadanych (nazwa ta sama, sciezka inna)
			$this->sess_files[$free_index.$i]=array('id'=>$file['id'],'path'=>$file['file_path']);
			if(isset($file['file_width'])) $this->sess_files[$free_index.$i]['width']=$file['file_width'];
			if(isset($file['file_height'])) $this->sess_files[$free_index.$i]['height']=$file['file_height'];
			if(isset($file['file_name'])) $this->sess_files[$free_index.$i]['name']=$file['file_name'];
			$i++;
		}
	}
	public function get_picture_id($sess_id)	// returns id parameter, whatever it is
	{
		$sess_id='file_'.$sess_id;
		if(!isset($this->sess_files[$sess_id]))
		{
			$this->msg('INVALID_REFERENCE');
			return;
		}
		if(isset($this->sess_files[$sess_id]['id'])) return $this->sess_files[$sess_id]['id'];
		return 0;
	}
	public function delete_picture($sess_id)
	{
		$sess_id='file_'.$sess_id;
		if(!isset($this->sess_files[$sess_id]))
		{
			$this->msg('INVALID_REFERENCE');
			return;
		}
		$file=$this->sess_files[$sess_id];
		unset($this->sess_files[$sess_id]);
		if($file['path']) 
		{
			if(file_exists($this->file_dir.$file['path'])&&!unlink($this->file_dir.$file['path'])) 
			{
				$this->msg('DELETE_FAILED');
				return;
			}
			// fuck, miniatury, nalezy jednak trzymac sciezki oddzielnie
			if(file_exists($this->thumb_dir.$file['path'])&&!unlink($this->thumb_dir.$file['path'])) 
			{
				$this->msg('DELETE_FAILED');
				return;
			}
		}
		// if($file['id']) return $file['id']; // kwestie bazy danych pozostawiamy modulowi
		return 1;
	}
	public function upload_file()
	{
		if(count($this->sess_files)==$this->CNF['max_files'])
		{
			$this->last_uploaded_status='MAX_fileS_REACHED';
			return false;
		}
		if(!isset($_FILES[$this->CNF['file_filename']])||!is_uploaded_file($_FILES[$this->CNF['file_filename']]['tmp_name']))
		{	
			#$this->msg('NO_PICTURE');
			print_r($_FILES);
			$this->last_uploaded_status='NO_PICTURE';
			return false;
		}
		// sprawdzenie z pomoca gd, pobranie rozmiaru, dodanie tmp name do sesji, aby pozniej wykonac move_uploaded_file przy probie zapisu
		// pierwsza linia walidacji, druga jest w save_files (przy robieniu miniatur)
		$img_size=getimagesize($_FILES[$this->CNF['file_filename']]['tmp_name']);
		if(!$img_size)
		{
			$this->last_uploaded_status='UNSUPPORTED_FILE_FORMAT';
			return false;
		}
		$file=array('height'=>$img_size[1],'width'=>$img_size[0],'path'=>'','name'=>$_FILES[$this->CNF['file_filename']]['name'],'tmp_name'=>$_FILES[$this->CNF['file_filename']]['tmp_name'],'id'=>0);
		if(preg_match('/\.php/i',$file['name']))
		{
			$this->last_uploaded_status='UNSUPPORTED_FILE_FORMAT';
			return false;
		}
		$image_create_func='';
		$image_func='';
		$ext=strtolower(end(explode('.',$file['name']))); // wyciagniecie rozszerzenia
		switch($ext)
		{
			case 'png' :  
			$image_create_func='imagecreatefrompng';
			$image_func='imagepng';
			if(!(imagetypes() & IMG_PNG)) 
			{
				$this->last_uploaded_status='UNSUPPORTED_FILE_FORMAT';
				return false;
			}												
			break;
			case 'jpg' : 
				$image_create_func='imagecreatefromjpeg';
				$image_func='imagejpeg';
				if(!(imagetypes() & IMG_JPG)) 
				{
					$this->last_uploaded_status='UNSUPPORTED_FILE_FORMAT';
					return false;
				}						
			break;
			case 'jpeg' : 
			$image_create_func='imagecreatefromjpeg';
			$image_func='imagejpeg';
			if(!(imagetypes() & IMG_JPG)) 
			{
				$this->last_uploaded_status='UNSUPPORTED_FILE_FORMAT';
				return false;
			}						
			break;
			case 'gif' : 
				$image_create_func='imagecreatefromgif';
				$image_func='imagegif';
				if(!(imagetypes() & IMG_GIF)) 
				{	
					$this->last_uploaded_status='UNSUPPORTED_FILE_FORMAT';
					return false;
				}
			break;
			default : 
			{ 	
				$this->last_uploaded_status='UNSUPPORTED_FILE_EXTENSION';
				return false;
			}
		}
		$thumb=$file;
		// collision avoidance
		$seed=0;
		while(file_exists($this->file_dir.($file['path']=$thumb['path']=md5($file['name'].($seed++)).".$ext")));
		if($this->thumb_dir) // jesli w ogole mamy robic miniature, to ja robimy
		{
			$ratioh = $this->CNF['thumb_height']/$file['height'];
			$ratiow = $this->CNF['thumb_width']/$file['width'];
			$ratio = min($ratioh, $ratiow);
			// Nowe rozmiary, klonujemy file i zmieniamy go w miniature
			$thumb['width'] = intval($ratio*$file['width']);
			$thumb['height'] = intval($ratio*$file['height']);
			// ustalenie nazwy do zapisu
			$src = $image_create_func($file['tmp_name']);
			$dst = ImageCreateTrueColor($thumb['width'],$thumb['height']);
			ImageCopyResized($dst, $src, 0, 0, 0, 0, $thumb['width'], $thumb['height'], $file['width'], $file['height']);
			$image_func($dst,$this->thumb_dir.$thumb['path']);
			ImageDestroy($dst);
			ImageDestroy($src);
		}
		// jeszcze jeden wazny detal - move_uploaded_file
		if(!move_uploaded_file($file['tmp_name'],$this->file_dir.$file['path']))
		{
		 	$this->last_uploaded_status='MOVE_UPLOADED_FILE_FAILED';
		}
		// znalezienie pierwszego wolnego indeksu
		$free_index='file_';
		$i=0;
		for(;$i<$this->CNF['max_files'];$i++)	if(!array_key_exists($free_index.$i,$this->sess_files)) break;
		$this->sess_files[$free_index.$i]=$file;
		return $this->last_uploaded_status=$this->file_dir.$file['path'];
	}
	public function get_last_uploaded_status()
	{
		return $this->last_uploaded_status;
	}
	public function get_sess_files()
	{
		return $this->sess_files;
	}
	public function get_files()
	{
		$ret=array();
		if(count($this->sess_files)==0)  return $ret;
		foreach($this->sess_files as $file) $ret[]=$this->file_dir.$file['path'];
		return $ret;
	}
	public function get_thumbs()
	{
		if(!$this->thumb_dir||count($this->sess_files)==0)  return array();
		$ret=array();
		foreach($this->sess_files as $file) $ret[]=$this->thumb_dir.$file['path'];
		return $ret;
	}
	public function flush_files()
	{
		$this->sess_files=array();
	}
	public function __construct($args)
	{
			$this->init($args);
			//print_r($this->MSG);
			if(!isset($this->CNF['session_files_key'])||!isset($this->CNF['file_filename'])||!isset($this->CNF['file_dir'])||!isset($this->CNF['session_last_uploaded_key'])||!isset($this->CNF['max_files'])) 
			{  
				$this->msg('INVALID_file_UPLOADER_CONFIGURATION'); // this doesn't reach anywhere
				return; 
			}
			if(!isset($_SESSION[$this->CNF['session_files_key']])) $_SESSION[$this->CNF['session_files_key']]=array();
			if(!isset($_SESSION[$this->CNF['session_last_uploaded_key']])) $_SESSION[$this->CNF['session_last_uploaded_key']]='';
			$this->last_uploaded_status=&$_SESSION[$this->CNF['session_last_uploaded_key']];
			$this->sess_files=&$_SESSION[$this->CNF['session_files_key']];
			$this->file_dir=$this->CNF['file_dir'];
			$this->thumb_dir=$this->CNF['thumb_dir'];
	}
}
?>