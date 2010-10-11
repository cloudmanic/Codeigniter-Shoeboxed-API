<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
//
// By: Cloudmanic Labs, LLC (http://www.cloudmanic.com)
// Date: 4/1/2010
// Contact: Spicer Matthews <spicer@cloudmanic.com>
//
class Shoeboxedlib {
	public $appname;
	public $apikey;
	public $usertoken;
	public $username;
	public $url;
	public $errormsgs = array();
	public $results = array();
	public $response;
	public $parsedresponse;
	public $action;
	public $count = "50";
	public $page = "1";
	public $datestart = '';
	public $dateend =  '';
	public $categoriesname = array();
	public $categoriesid = array();
	private $requesturl = 'https://app.shoeboxed.com/ws/api.htm';
	
	// 
	// Make sure we have a token and set the vars we need.
	//
	function Shoeboxedlib()
	{
		$this->CI =& get_instance();
		$this->CI->config->load('shoeboxed');
		
		$this->url = 'https://app.shoeboxed.com/ws/api.htm';
		$this->apikey = $this->CI->config->item('shoeboxed_key');
		$this->appname = $this->CI->config->item('shoeboxed_appname');
		$this->callback = $this->CI->config->item('shoeboxed_callback'); 
		$this->datestart = date('Y-n-d', strtotime("-3 Year")) . 'T00:00:10';
		$this->dateend = date('Y-n-d', strtotime("+1 Day")) . 'T00:00:10';
	}

	//
	// This function will set the usertoken of the user.
	//
	function set_usertoken($token)
	{
		$this->usertoken = $token;
	}

	//
	// Turn the LoginUrl for Shoeboxed.
	//
	function getLoginUrl()
	{
		if(isset($this->apikey))
			return $this->url . '?appname=' . $this->appname . '&appurl=' . $this->callback . '&apparams=done&SignIn=1';
		else 
			return 0;
	}
	
	//
	// Get transactions.
	//
	function getReceipts()
	{
		$this->action = 'GetReceiptCall';
		$this->request();
		return $this->_receiptsProcess();
	}

	//
	// Get Categories.
	//
	function getCategories()
	{
		$this->action = 'GetCategoryCall';
		$this->request();
		return $this->_categoryProcess(); 
	}
	
	//
	// Set Count.
	//
	function setCount($count = "50")
	{
		$this->count = $count;
		return 1;
	}
	
	//
	// Set Page.
	//
	function setPage($page = "1")
	{
		$this->page = $page;
		return 1;
	}
	
	//
	// Set Date Start.
	//
	function setDateStart($start)
	{
		$this->datestart = $start;
		return 1;
	}
	
	//
	// Set Date End.
	//
	function setDateEnd($end)
	{
		$this->dateend = $end;
		return 1;
	}
	
	//
	// Set UseSellDate (default is false).
	//
	function setUseSellDate()
	{
		$this->useselldate = 'true';
		return 1;
	}
	
	//
	// Set Category Id.
	//
	function setCategoryId($id)
	{
		$this->catid = $id;
		return 1;
	}
	
	// ----------------- Private Functions --------------- //
	
	//
	// Make request to shoeboxed
	//
	private function request()
	{	
		$this->results = array();	
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
						<Request xmlns="urn:sbx:apis:SbxBaseComponents">
							<RequesterCredentials>
      					<ApiUserToken>' . $this->apikey . '</ApiUserToken>          
      					<SbxUserToken>' . $this->usertoken . '</SbxUserToken>
							</RequesterCredentials>';
		$xml .= '<' . $this->action . '>';
		$xml .=	'<ReceiptFilter>';
    $xml .= "<Results>" . $this->count . "</Results>";
		$xml .= '<PageNo>' . $this->page . '</PageNo>';
		
		if(! empty($this->datestart))
    	$xml .= '<DateStart>' . $this->datestart . '</DateStart>';
    
    $xml .= '<DateEnd>' . $this->dateend . '</DateEnd>';
		
		if(isset($this->useselldate))
			$xml .= "<UseSellDate>$this->useselldate</UseSellDate>";		
		else 
			$xml .= '<UseSellDate>false</UseSellDate>'; 
		
		if(isset($this->catid))
			$xml .= '<Category>' . $this->catid . '</Category>';
		
 		$xml .=	'</ReceiptFilter>';
		$xml .= '</' . $this->action . '>';
		$xml .=	'</Request>';
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->requesturl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array('xml' => $xml));
		$this->response = curl_exec($ch);
		curl_close($ch);
		
		if(! $this->_parse_response())
			return 0;
		else
			return $this->response;
	}
	
	//
	// Process returns from a receipt request.
	//
	private function _receiptsProcess()
	{
		$data = array();
		
		if(isset($this->parsedresponse->Receipts->Receipt)) {
			$data['count'] = current($this->parsedresponse->Receipts->attributes()->count); 
			
			foreach($this->parsedresponse->Receipts->Receipt AS $key => $row)
			{
				$rec = array();
				
				// Get receipt info.
				foreach($row->attributes() AS $key2 => $row2)
					$rec[$key2] = current($row2);
					
				// Get category info.
				$rec['categories'] = array();
				foreach($row->Categories->Category AS $key2 => $row2)
					$rec['categories'][] = array('name' => current($row2->attributes()->name), 
													'id' => current($row2->attributes()->id));
					
				$data['receipts'][] = $rec;
			}
		}
		
		return $data;
	} 

	//
	// Process returns from a category request.
	//
	private function _categoryProcess()
	{
		$data = array();
		if(isset($this->parsedresponse->Categories->Category)) {
			foreach($this->parsedresponse->Categories->Category AS $key => $row)
				$data[] = array('id' => current($row->attributes()->id), 'name' => current($row->attributes()->name));
		}
		return $data;
	}
	
	//
	// Function to parse the api response
	// The code uses SimpleXML. http://us.php.net/manual/en/book.simplexml.php 
	// There are also other ways to parse xml in PHP depending on the version and what is installed.
	//
	private function _parse_response()
	{
		$this->parsedresponse = simplexml_load_string($this->response, "SimpleXMLElement", LIBXML_NOWARNING);
		
		if($this->parsedresponse->attributes()->code != 0) { 
			$this->errormsgs = array('code' => htmlspecialchars($this->parsedresponse->attributes()->code), 
														'msg' => htmlspecialchars($this->parsedresponse->attributes()->description));
			return 0;
		}
		return 1;
	}
	
	//
	// Return the error.
	//
	function getError()
	{
		return $this->errormsgs;
	}
}
?>