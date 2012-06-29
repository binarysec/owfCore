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
	private $boundary_normal = "--------------000502060407050709070002";
	private $boundary_attachment = "--------------060302000505040406030709";
	
	private $template_normal = 'core/mail';
	private $template_attachment = 'core/mail-attachment';
	private $render = null;
	
	public function __construct($wf, $subject, $content, $rcpt_to=null, $mail_from=null) {
		$this->wf = $wf;
		$this->core_smtp = $this->wf->core_smtp();
		$this->core_lang = $this->wf->core_lang();
		$this->core_mime = $this->wf->core_mime();
		$core_pref = $this->wf->core_pref()->register_group("session",  "Session");
		$this->tpl = new core_tpl($this->wf);
		
		if($mail_from == null)
			$this->mail_from = $core_pref->get_value("sender");
		else
			$this->mail_from = $mail_from;
		
		if($rcpt_to == null)
			$this->rcpt_to = array($core_pref->get_value("sender"));
		else if(is_array($rcpt_to))
			$this->rcpt_to = $rcpt_to;
		else
			$this->rcpt_to = array($rcpt_to);
		
		$this->subject = $subject;
		$this->body = $content;
		
	}
	
	public function set_header($header_name, $data) {
		$this->headers[$header_name] = $data;
	}
	
	public function attach($filepath, $name) {
		$item = array();
		
		$data = file_get_contents($filepath);
		if($data == null)
			return false;
		
		$item['data'] = $data;
		$item['name'] = $name;
		$item['mime'] = $this->core_mime->get_mime($filepath);
		
		$this->attachments[] = $item;
		
		return true;
	}

	public function render() {
		$attach_count = count($this->attachments);
		
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
		if($attach_count == 0) {
			$this->render .= "Content-Type: multipart/alternative;\r\n";
			$boundary = $this->boundary_normal;
		}
		else {
			$this->render .= "Content-Type: multipart/mixed;\r\n";
			$boundary = $this->boundary_attachment;
		}
		
		//Filter html tags to have a raw text
		$non_html = $this->html2text($this->body);
		
		//Render body
		$this->tpl->set('body_text', $non_html);
		$this->tpl->set('body_html', $this->body);
		if($attach_count == 0)
			$this->render .= $this->tpl->fetch($this->template_normal);
		else
			$this->render .= $this->tpl->fetch($this->template_attachment);
		
		//Render attachments
		if(count($this->attachments) == 0) {
			$this->render .= "\r\n".$boundary."--\r\n";
		}
		else {
			foreach($this->attachments as $v) {
				$this->render .= "\r\n".$boundary."\r\n";
				$this->render .=
					'Content-Type: '.$v['mime'].";\r\n".
					' name="'.$v['name']."\"\r\n".
					"Content-Transfer-Encoding: base64\r\n".
					"Content-Disposition: attachment;\r\n".
					' filename="'.$v['name']."\"\r\n\r\n".
					base64_encode($v['data']);
			}
			$this->render .= "\r\n".$boundary."--\r\n";
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
