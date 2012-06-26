<?php

class core_mail {
	private $wf;
	private $core_smtp;
	private $core_lang;
	private $core_mime;
	private $tpl;
	
	//Special headers
	private $rcpt_to;
	private $mail_from;
	private $subject = "";
	
	private $headers = array();
	private $charset;
	private $body;
	
	private $attachments = array();
	//A '--' is added at the beginning of each boundary, and should be here too.
	private $boundary = "--------------050202070803070706060506";
	
	private $template = 'core/mail';
	private $render = null;
	
	public function __construct($wf, $mail_from, $rcpt_to, $subject, $content) {
		$this->wf = $wf;
		$this->core_smtp = $this->wf->core_smtp();
		$this->core_lang = $this->wf->core_lang();
		$this->core_mime = $this->wf->core_mime();
		$this->tpl = new core_tpl($this->wf);
		
		$this->mail_from = $mail_from;
		if(is_array($rcpt_to))
			$this->rcpt_to = $rcpt_to;
		else
			$this->rcpt_to = array($rcpt_to);
		$this->subject = $subject;
		$this->body = $content;
		
	}
	
	public function set_header($header_name, $data) {
		$this->headers[$header_name] = $data;
	}
	
	public function attach($file, $name) {
		$item = array();
		
		$item['data'] = $file;
		$item['name'] = $name;
		$item['mime'] = $this->core_mime->get_mime($name);
		
		$this->attachments[] = $item;
	}

	public function render() {
		//Set some headers
		$this->set_header("User-Agent", "OWF/".WF_VERSION);
		$this->set_header("Date", date("r"));
		$this->set_header("Subject", $this->subject);
		$this->set_header("From", $this->mail_from);
		$this->set_header("To", implode(",\r\n ", $this->rcpt_to));
		
		$this->render = '';
		//Render headers
		foreach($this->headers as $k => $v) {
			$this->render .= $k.": ".$v."\r\n";
		}
		$this->render .= "Content-Type: multipart/alternative;\r\n";
		
		//Filter html tags to have a raw text
		$non_html = $this->html2text($this->body);
		
		//Render body
		$this->tpl->set('body_text', $non_html);
		$this->tpl->set('body_html', $this->body);
		$this->render .= $this->tpl->fetch($this->template);
		
		//Render attachments
		if(count($this->attachments) == 0) {
			$this->render .= "\r\n".$this->boundary."--\r\n";
		}
		else {
			foreach($this->attachments as $v) {
				$this->render .= "\r\n".$this->boundary."\r\n";
				$this->render .=
					'Content-Type: '.$v['mime'].";\r\n".
					' name="'.$v['name']."\"\r\n".
					"Content-Transfer-Encoding: base64\r\n".
					"Content-Disposition: attachment;\r\n".
					' filename="'.$v['name']."\"\r\n\r\n".
					base64_encode($v['data']).
					"\r\n";
			}
			$this->render .= "\r\n".$this->boundary."--\r\n";
		}
		
		return $this->render;
	}

	public function send() {
		if($this->render == null)
			return false;
		
		$queueid = $this->core_smtp->sendmail($this->mail_from, $this->rcpt_to, $this->render);
		
		return $queueid;
	}
	
	private function html2text($html)
	{
		$tags = array (
		'~<h[123][^>]+>~si',
		'~<h[456][^>]+>~si',
		'~<table[^>]+>~si',
		'~<tr[^>]+>~si',
		'~</li>~si',
		'~<br[^>]*>~si',
		'~<p[^>]*>~si',
		'~</p>~si',
		'~<div[^>]+>~si',
		);
		// remove newlines
		$html = preg_replace('~(\r?\n)+~s',"",$html);
		
		// insert newlines
		$html = preg_replace($tags,"\r\n",$html);
		
		// handle arrays & lists
		$html = preg_replace('~<li[^>]*>~','* ',$html);
		$html = preg_replace('~</t(d|h)>\s*<t(d|h)[^>]+>~si',' - ',$html);
		
		// clean all tags
		$html = preg_replace('~<[^>]+>~s','',$html);
		
		// reduce spaces
		$html = preg_replace('~ +~s',' ',$html);
		$html = preg_replace('~^[ \f\t]+~m','',$html);
		$html = preg_replace('~[ \f\t]+$~m','',$html);
		
		
		return $html;
	}
}
