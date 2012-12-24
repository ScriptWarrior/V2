<?php
### what's the status of this shit?
###
require('./config/mailing_conf.php');
require('./lib/phpmailer/class.phpmailer.php');

### Do tego od razu dorzuci sie zestaw szablonow i bedzie majoowo
### uzywanie: 
### require('lib/uni_mailer.php');
### $mailing=new uni_mailer('jan@maciejewski.pl','Maciejewski','Lalala','HEJ HEJ HEJ','jestes@chujem.pl');
### $mailing->mail->send();
###
### Bardziej rozbudowany przypadek:
### $mailing=new uni_mailer('jan@maciejewski.pl','Maciejewski','Ponaglenie','');
### $mailing->mail->AddReplyTo('klient@o2.pl','klient nazwisko');
### $mailing->addAttachment('sciezka','TMP nejm');
### $mailing->addAttachment('sciezka 2','TMP nejm drugi');
### $mailing->fromTemplate(clone $this->TPL,'ponaglenie',array('kwota'=>909));
### $mailing->mail->send();

###
### Szablony
### CREATE TABLE mail_templates (
###  tpl_id INT PRIMARY KEY AUTO_INCREMENT,
###  subject VARCHAR(100),
###  template_name VARCHAR(50),
###  ln	CHAR(2) DEFAULT 'PL',
###  html TINYINT DEFAULT 1,
###	 from VARCHAR(100),
###  from_name VARCHAR(100),
###  reply_to VARCHAR(100) DEFAULT '',
###  reply_to_label VARCHAR(100) DEFAULT '',
###	 user_id INT DEAFULT 0, -- id wlasciciela, tworcy szablonu
###  body TEXT
### );
### Szablony mejli powinnismy trzymac w bazie, tj. nazwa, topic, nazwa pliku z tplem, czy moze caly tpl?
class uni_mailer 
{
	public $mail;
	public function __construct($destination_addr,$destination_label,$topic,$body,$from=SMTP_FROM) 
	{
		$this->mail=new PHPMailer();
		$this->mail->IsSMTP();
		$this->mail->SMTPAuth=SMTP_AUTH;
		$this->mail->SMTPSecure=SMTP_SECURE;
		$this->mail->Host=SMTP_HOST;
		$this->mail->Port=SMTP_PORT;
		$this->mail->Username=SMTP_USERNAME;
		$this->mail->Password=SMTP_PASSWORD;
		$this->mail->CharSet=SMTP_CHARSET;
		$this->mail->WordWrap=SMTP_WORD_WRAP;
		$this->mail->From=$from;
		$this->mail->FromName=$from;
		$this->mail->AddAddress($destination_addr,$destination_label);
		$this->mail->AddReplyTo($from,SMTP_FROM_NAME);
		$this->mail->Subject=$topic;
		$this->mail->MsgHTML($body);		
	}	
	public function addAttachment($tmp_name,$attachment_name)
	{
		$this->mail->AddAttachment($tmp_name,$attachment_name,'base64','application/octet-stream');
	}
	public function fromTemplate($TPL_obj,$tpl_name,$tpl_content)
	{
		foreach($tpl_content as $key=>$val)
			$TPL_obj->assign($key,$val);
		$this->mail->MsgHTML($TPL_obj->fetch($tpl_name.'.tpl'));
	}
	// send i addAttachment beda uzywane bezposrednio
}
?>