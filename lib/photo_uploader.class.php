<?php
// FUCKING FILES UPLOADER CLASS
class photo_uploader extends vengine_mod
{
	private $session_files;
	private $last_uploaded_status; // variable with last uploaded file status, used for AJAX interface
	public function session_load_files($files) 
	{
		$this->flush_files();
		$free_index='file_';
		$i=0;
		foreach($files as $file) 
		{
			$file['file_path']=preg_replace('#'.$this->CNF['files_dir'].'#','',$photo['file_path']); // assembling paths (full + thumb) 
			// sciezke i nazwe pliku osobno, aby obslugiwac miniatury bez dodatkowych metadanych (nazwa ta sama, sciezka inna)
			$this->sess_photos[$free_index.$i]=array('id'=>$photo['id'],'path'=>$photo['photo_path']);
			if(isset($photo['photo_width'])) $this->sess_photos[$free_index.$i]['width']=$photo['photo_width'];
			if(isset($photo['photo_height'])) $this->sess_photos[$free_index.$i]['height']=$photo['photo_height'];
			if(isset($photo['photo_name'])) $this->sess_photos[$free_index.$i]['name']=$photo['photo_name'];
			$i++;
		}
	}
	public function get_file_id($sess_id)	// returns id parameter, whatever it is
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
	public function session_delete_file($sess_id)
	{
		$sess_id='file_'.$sess_id;
		if(!isset($this->sess_files[$sess_id]))
		{
			$this->msg('INVALID_REFERENCE');
			return;
		}
		$file=$this->sess_files[$sess_id];
		unset($this->sess_files[$sess_id]);
		if($photo['path']) 
		{
			if(file_exists($this->files_dir.$photo['path'])&&!unlink($this->photo_dir.$photo['path'])) 
			{
				$this->msg('DELETE_FAILED');
				return;
			}
			// fuck, miniatury, nalezy jednak trzymac sciezki oddzielnie
			if(file_exists($this->thumb_dir.$photo['path'])&&!unlink($this->thumb_dir.$photo['path'])) 
			{
				$this->msg('DELETE_FAILED');
				return;
			}
		}
		// if($photo['id']) return $photo['id']; // kwestie bazy danych pozostawiamy modulowi
		return 1;
	}
	public function upload_photo()
	{
		if(count($this->sess_photos)==$this->CNF['max_pictures'])
		{
			$this->last_uploaded_status='MAX_PHOTOS_REACHED';
			return false;
		}
		if(!isset($_FILES[$this->CNF['photo_filename']])||!is_uploaded_file($_FILES[$this->CNF['photo_filename']]['tmp_name']))
		{	
			#$this->msg('NO_PICTURE');
			print_r($_FILES);
			$this->last_uploaded_status='NO_PICTURE';
			return false;
		}
		// sprawdzenie z pomoca gd, pobranie rozmiaru, dodanie tmp name do sesji, aby pozniej wykonac move_uploaded_file przy probie zapisu
		// pierwsza linia walidacji, druga jest w save_pictures (przy robieniu miniatur)
		$img_size=getimagesize($_FILES[$this->CNF['photo_filename']]['tmp_name']);
		if(!$img_size)
		{
			$this->last_uploaded_status='UNSUPPORTED_FILE_FORMAT';
			return false;
		}
		$photo=array('height'=>$img_size[1],'width'=>$img_size[0],'path'=>'','name'=>$_FILES[$this->CNF['photo_filename']]['name'],'tmp_name'=>$_FILES[$this->CNF['photo_filename']]['tmp_name'],'id'=>0);
		if(preg_match('/\.php/i',$photo['name']))
		{
			$this->last_uploaded_status='UNSUPPORTED_FILE_FORMAT';
			return false;
		}
		$image_create_func='';
		$image_func='';
		$ext=strtolower(end(explode('.',$photo['name']))); // wyciagniecie rozszerzenia
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
		$thumb=$photo;
		// collision avoidance
		$seed=0;
		while(file_exists($this->photo_dir.($photo['path']=$thumb['path']=md5($photo['name'].($seed++)).".$ext")));
		if($this->thumb_dir) // jesli w ogole mamy robic miniature, to ja robimy
		{
			$ratioh = $this->CNF['thumb_height']/$photo['height'];
			$ratiow = $this->CNF['thumb_width']/$photo['width'];
			$ratio = min($ratioh, $ratiow);
			// Nowe rozmiary, klonujemy photo i zmieniamy go w miniature
			$thumb['width'] = intval($ratio*$photo['width']);
			$thumb['height'] = intval($ratio*$photo['height']);
			// ustalenie nazwy do zapisu
			$src = $image_create_func($photo['tmp_name']);
			$dst = ImageCreateTrueColor($thumb['width'],$thumb['height']);
			ImageCopyResized($dst, $src, 0, 0, 0, 0, $thumb['width'], $thumb['height'], $photo['width'], $photo['height']);
			$image_func($dst,$this->thumb_dir.$thumb['path']);
			ImageDestroy($dst);
			ImageDestroy($src);
		}
		// jeszcze jeden wazny detal - move_uploaded_file
		if(!move_uploaded_file($photo['tmp_name'],$this->photo_dir.$photo['path']))
		{
		 	$this->last_uploaded_status='MOVE_UPLOADED_FILE_FAILED';
		}
		// znalezienie pierwszego wolnego indeksu
		$free_index='photo_';
		$i=0;
		for(;$i<$this->CNF['max_pictures'];$i++)	if(!array_key_exists($free_index.$i,$this->sess_photos)) break;
		$this->sess_photos[$free_index.$i]=$photo;
		return $this->last_uploaded_status=$this->photo_dir.$photo['path'];
	}
	public function get_last_uploaded_status()
	{
		return $this->last_uploaded_status;
	}
	public function get_sess_photos()
	{
		return $this->sess_photos;
	}
	public function get_pictures()
	{
		$ret=array();
		if(count($this->sess_photos)==0)  return $ret;
		foreach($this->sess_photos as $photo) $ret[]=$this->photo_dir.$photo['path'];
		return $ret;
	}
	public function get_thumbs()
	{
		if(!$this->thumb_dir||count($this->sess_photos)==0)  return array();
		$ret=array();
		foreach($this->sess_photos as $photo) $ret[]=$this->thumb_dir.$photo['path'];
		return $ret;
	}
	public function flush_pictures()
	{
		$this->sess_photos=array();
	}
	public function __construct($args)
	{
			$this->init($args);
			if(!isset($this->CNF['max_pictures'])) 
			{  
				$this->msg('INVALID_PHOTO_UPLOADER_CONFIGURATION'); // this doesn't reach anywhere
				return; 
			}
			if(!isset($_SESSION[$this->CNF['session_pictures_key']])) $_SESSION[$this->CNF['session_pictures_key']]=array();
			if(!isset($_SESSION[$this->CNF['session_last_uploaded_key']])) $_SESSION[$this->CNF['session_last_uploaded_key']]='';
			$this->last_uploaded_status=&$_SESSION[$this->CNF['session_last_uploaded_key']];
			$this->sess_photos=&$_SESSION[$this->CNF['session_pictures_key']];
			$this->photo_dir=$this->CNF['photo_dir'];
			$this->thumb_dir=$this->CNF['thumb_dir'];
	}
}
?>