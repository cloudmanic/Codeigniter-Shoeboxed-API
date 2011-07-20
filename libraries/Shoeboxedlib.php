<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
//
// By: Cloudmanic Labs, LLC (http://www.cloudmanic.com)
// Date: 4/1/2010
// Contact: admin@cloudmanic.com
//
class Shoeboxedlib 
{
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
	function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->config->load('shoeboxed');
		
		$this->url = 'https://app.shoeboxed.com/ws/api.htm';
		$this->apikey = $this->CI->config->item('shoeboxed_key');
		$this->appname = $this->CI->config->item('shoeboxed_appname');
		$this->callback = $this->CI->config->item('shoeboxed_callback'); 
		$this->datestart = date('Y-m-d', strtotime("-3 Year"))  . 'T00:00:10';
		$this->dateend = date('Y-m-d', strtotime("+1 Day"))  . 'T00:00:10';
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
		{
			return $this->url . '?appname=' . $this->appname . '&appurl=' . $this->callback . '&apparams=done&SignIn=1';
		} else
		{ 
			return 0;
		}
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
		
		if($this->request())
		{
			return $this->_categoryProcess(); 
		}
		
		return 0;
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
		// Clear variables
		$this->errormsgs = array();
		$this->results = array();
		$this->parsedresponse = array();
		$this->response = array();	
		
		$xml = '<Request xmlns="urn:sbx:apis:SbxBaseComponents">
							<RequesterCredentials>
      					<ApiUserToken>' . $this->apikey . '</ApiUserToken>          
      					<SbxUserToken>' . $this->usertoken . '</SbxUserToken>
							</RequesterCredentials>';
		
		$xml .= '<' . $this->action . '>';
		
		// We only did this xml for certain requests.
		if($this->action == 'GetReceiptCall')
		{
			$xml .=	'<ReceiptFilter>';
	    $xml .= "<Results>" . $this->count . "</Results>";
			$xml .= '<PageNo>' . $this->page . '</PageNo>';
			
			if(! empty($this->datestart))
			{
	    	$xml .= '<DateStart>' . $this->datestart . '</DateStart>';
	    }
	    
	    $xml .= '<DateEnd>' . $this->dateend . '</DateEnd>';
			
			if(isset($this->useselldate))
			{
				$xml .= "<UseSellDate>$this->useselldate</UseSellDate>";		
			} else
			{ 
				$xml .= '<UseSellDate>false</UseSellDate>'; 
			}
			
			if(isset($this->catid))
			{
				$xml .= '<Category>' . $this->catid . '</Category>';
			}
			
	 		$xml .=	'</ReceiptFilter>';
		}
	
		$xml .= '</' . $this->action . '>';
		$xml .=	'</Request>';

		// Send request and process it.
		$this->response = $this->http_post($this->requesturl,  array('xml' => $xml));
		
		if((! empty($this->response['content'])) && 
				(isset($this->response['headers'][0])) &&
				(strtoupper($this->response['headers'][0]) == 'HTTP/1.1 200 OK'))
		{
			return $this->_parse_response();
		} else
		{
			log_message('level', 'Shoeboxed Lib: error on shoeboxed response.');
			return 0;
		}
	}
	
	//
	// Process returns from a receipt request.
	//
	private function _receiptsProcess()
	{
		$data = array();
			
		if(isset($this->parsedresponse['Receipts']['Receipt'])) 
		{
			// What is the count of the returned data.
			if(isset($this->parsedresponse['Receipts']['@attributes']['count']))
			{
				$data['count'] = $this->parsedresponse['Receipts']['@attributes']['count']; 
			} else 
			{
				$data['count'] = '';
			}

			// Loop through the data and translate.			
			foreach($this->parsedresponse['Receipts']['Receipt'] AS $key => $row)
			{
				$rec = array();
				
				// Get receipt info.
				foreach($row['@attributes'] AS $key2 => $row2)
				{
					$rec[$key2] = trim($row2);
				}
					
				// Get category info.
				$rec['categories'] = array();
				if(isset($row['Categories']['Category']))
				{
					if(isset($row['Categories']['Category']['@attributes']['name']))
					{
						$rec['categories'][] = array('name' => $row['Categories']['Category']['@attributes']['name'], 
																					'id' => $row['Categories']['Category']['@attributes']['id']);	
					} else 
					{
						foreach($row['Categories']['Category'] AS $key2 => $row2)
						{
							$rec['categories'][] = array('name' => $row2['@attributes']['name'], 
																						'id' => $row2['@attributes']['id']);
						}
					}
				}

				// Get images
				$rec['images'] = array();
				if(isset($row['Images']['Image']))
				{
					if(isset($row['Images']['Image']['@attributes']['imgurl']))
					{
						$rec['images'][] = array('imgurl' => $row['Images']['Image']['@attributes']['imgurl'], 
																			'index' => $row['Images']['Image']['@attributes']['index']);
					} else 
					{
						foreach($row['Images']['Image'] AS $key2 => $row2)
						{
							$rec['images'][] = array('imgurl' => $row2['@attributes']['imgurl'], 
																					'index' => $row2['@attributes']['index']);
						}
					}
				}
					
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
		
		if(isset($this->parsedresponse['Categories']['Category'])) 
		{
			foreach($this->parsedresponse['Categories']['Category'] AS $key => $row)
			{
				$data[] = array('id' => $row['@attributes']['id'], 'name' => $row['@attributes']['name']);
			}
		}

		return $data;
	}
	
	//
	// Parse the returned xml and put it into an array we can work with.
	//
	private function _parse_response()
	{		
		$this->parsedresponse = json_decode(json_encode((array) simplexml_load_string($this->response['content'])),1);

		// See if the shoeboxed return an error.
		if(isset($this->parsedresponse['@attributes']['code']) && 
				($this->parsedresponse['@attributes']['code'] != 0))
		{
			$this->errormsgs = array('code' => $this->parsedresponse['@attributes']['code'], 
															'msg' => $this->parsedresponse['@attributes']['description']);
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
	
	//
	// Make a http post request to the shoeboxed.com servers.
	//
	function http_post($url, $data)
	{
		$data_url = http_build_query($data);
		$data_len = strlen($data_url);

		return array('content' => file_get_contents($url, false, stream_context_create(array('http'=>array('method' => 'POST', 
															'header' => "Connection: close\r\nContent-Length: $data_len\r\n" . "Content-type: application/x-www-form-urlencoded\r\n", 
															'content' => $data_url)))), 'headers' => $http_response_header);
	}
}

/* End File */