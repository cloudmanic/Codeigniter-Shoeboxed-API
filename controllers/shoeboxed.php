<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Shoeboxed extends CI_Controller
{
	function __construct() 
	{
		parent::__construct();
		$this->load->library('Shoeboxedlib');
		$this->load->library('session');
	}
	
	
	//
	// Login to Shoeboxed
	//
	function login()
	{
		if($this->session->userdata('shoeboxed_tkn') && $this->session->userdata('shoeboxed_uname')) {
			echo "You have already have your access token is stored in sessions. Click " . anchor('/shoeboxed/demo', 'Here') . 
						"for a demo. To reset these tokens click the link below. <br />";
		}
	
		if($url = $this->shoeboxedlib->getLoginUrl())
			echo '<a href="' . $url . '">Login to ShoeBoxed</a>';
	}
	
	//
	// Callback function after user completes the Auth with Shoeboxed.
	//
	function callback()
	{
		if(isset($_REQUEST['tkn']) && isset($_REQUEST['uname'])) {
			$this->session->set_userdata('shoeboxed_tkn', $_REQUEST['tkn']);
			$this->session->set_userdata('shoeboxed_uname', $_REQUEST['uname']);
			redirect('/shoeboxed/demo');
		}
	}
	
	
	//
	// This function will give the user a demo if grabing different forms of data.
	//
	function demo()
	{
		if($this->session->userdata('shoeboxed_tkn') && $this->session->userdata('shoeboxed_uname')) {
			$this->shoeboxedlib->set_usertoken($this->session->userdata('shoeboxed_tkn'));
			
			// Get all categories.
			$categories = $this->shoeboxedlib->getCategories();
			echo '<pre>' . print_r($categories, TRUE) . '</pre>';
			
			// Set some limits and get some receipts
			$this->shoeboxedlib->setCount(50);
			$this->shoeboxedlib->setPage(1);
			//$this->shoeboxedlib->setCategoryId("XXXXXXXXX");
			$receipts = $this->shoeboxedlib->getReceipts();
			echo '<pre>' . print_r($receipts, TRUE) . '</pre>';
			
		} else {
			redirect('/shoeboxed/login');
		}
	}
}
?>