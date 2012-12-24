<?php
// V2 DB
class PDODB_wrapper
{
	private $conn;
	private $last_statement;
	private $query_cache;
	private $error;
	private $debug;
	private $REQ;
	public function __construct($host,$port,$usrname,$pass,$dbname,$debug=0)
	{
		$this->debug=$debug;
		try
		{
			$this->conn=new PDO("mysql:host=$host;dbname=$dbname;port=$port",$usrname,$pass,array(PDO::ATTR_PERSISTENT=>true));
			$this->conn->exec('SET CHARACTER SET utf8');
			$this->conn->exec("SET collation_connection = 'utf8_unicode_ci'");
			$this->conn->exec("SET NAMES 'utf8'"); 
			$this->conn->beginTransaction(); 
		}
		catch(PDOException $e)
		{
			$this->error=$e->getMessage();
			if($this->debug) echo $this->error;
		}
	}
	public function __destruct()
	{
		$this->commit();
	}
	public function convey_req_object($REQ)
	{
		$this->REQ=$REQ;
	}
	public function get_err()
	{
		echo $this->query_cache;
		return $this->error;
	}
	public function commit()
	{
		$this->conn->commit();
	}
	public function get_found_rows()
	{
		$found_rows=$this->query('SELECT FOUND_ROWS() f');
		return $found_rows[0]['f'];
	}
	public function rollback()
	{
		//$this->conn->rollBack();
	}
	public function last_insert_id() 
	{ 
		return $this->conn->lastInsertId();
	}
	public function query($query,$params=0,$im_not_that_stupid=0)
	{ 
		$this->query_cache.="\n".$query; //
		$matches=array();
		try
		{
			if(strpos($query,':')==FALSE&&!$im_not_that_stupid) throw new Exception("Unsafe use of query method (possible SQL injection, read the manual, you fucking idiot).: $query");
			$this->last_statement=$this->conn->prepare($query);
			if(!is_object($this->last_statement))
			{
				$this->error=$query;
				print_r($query);
				return;
			}
			$res;
			if(is_array($params))
				$res=$this->last_statement->execute($params);
			else 
				$res=$this->last_statement->execute();
			if(preg_match('/^SELECT/i',$query))
			{
				if($res)
				{
					$res=array();
					$keys=0;
					$ajax=0;
					if(is_object($this->REQ)&&$this->REQ->get('ajax')) $ajax=1;
					while($row=$this->last_statement->fetch(PDO::FETCH_ASSOC))
					{
						if(!$keys) $keys=array_keys($row);
						$new_row=array();
						foreach($keys as $k) 
						{
							$new_row[$k]=preg_replace("#\'#","'",$row[$k]); # instead of stripslashes, cause they were faking other cases of \ appearance
							#if($ajax) $new_row[$k]=preg_replace('#&|"|<|>#','<![CDATA[$0]]>',$new_row[$k]);
						}
						$res[]=$new_row;
					}
					return $res;
				}
				return 0;
			}
			if(!$res&&$this->last_statement->errorInfo())
			{
				$this->error.=$this->last_statement->errorInfo();
				print_r($this->last_statement->errorInfo()); // tymczasowo
				$this->rollBack();
			}
			return $res; // jesli jestesmy tu, to znaczy, ze query nie jest selektem i ze wykonalo sie bez problemow
		}
		catch(PDOException $e)
		{
			$this->error=$e->getMessage();
			$this->conn->rollBack();
			return 0;
		}
	} 
}
?>