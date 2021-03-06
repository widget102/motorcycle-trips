<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * auth
 * 
 * Where all the authentication methods live
 * 
 * @license		http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @author		Mike Funk
 * @link		http://mikefunk.com
 * @email		mike@mikefunk.com
 * 
 * @file		auth.php
 * @version		1.3.4
 * @date		03/20/2012
 */

// --------------------------------------------------------------------------

/**
 * auth class.
 * 
 * @extends CI_Controller
 */
class auth extends CI_Controller
{
	// --------------------------------------------------------------------------
	
	/**
	 * __construct function.
	 * 
	 * @access public
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}
	
	// --------------------------------------------------------------------------
	
	/**
	 * login function.
	 *
	 * shows login form, handles validation.
	 * 
	 * @access public
	 * @return void
	 */
	public function login()
	{
		// load resources
		$this->load->helper(array('cookie', 'url'));
		$this->load->library('form_validation');
		$this->ci_authentication->remember_me();
		
		// form validation
		$this->form_validation->set_rules('email_address', 'Email Address', 'trim|required|valid_email|callback__email_address_check');
		$this->form_validation->set_rules('password', 'Password', 'required|callback__password_check');
		if ($this->form_validation->run() == FALSE)
		{
			// load view
			$this->load->view('auth/login_view', $data);
		}
		else
		{
			// redirect to configured home page
			$this->ci_authentication->do_login();
		}
	}
	
	// --------------------------------------------------------------------------
	
	/**
	 * login_new_password function.
	 *
	 * shows login_new_password form, handles validation.
	 * 
	 * @access public
	 * @return void
	 */
	public function login_new_password()
	{
		// load resources
		$this->load->helper(array('cookie', 'url'));
		$this->load->library('form_validation');
		$this->ci_authentication->remember_me();
		
		// form validation
		$this->form_validation->set_rules('email_address', 'Email Address', 'trim|required|valid_email|callback__email_address_check');
		$this->form_validation->set_rules('temp_password', 'Temporary Password', 'required|callback__password_check');
		$this->form_validation->set_rules('password', 'Password', 'trim|required');
		$this->form_validation->set_rules('confirm_password', 'Confirm Password', 'trim|required|matches[password]');
		if ($this->form_validation->run() == FALSE)
		{
			// load view
			$this->load->view('auth/login_new_password_view', $data);
		}
		else
		{
			// redirect to configured home page
			$this->ci_authentication->do_login();
		}
	}
	
	// --------------------------------------------------------------------------
	
	/**
	 * _email_address_check function.
	 *
	 * checks for an email in the db and checks to make sure registration link
	 * has been clicked.
	 * 
	 * @access public
	 * @param string $email_address
	 * @return bool
	 */
	public function _email_address_check($email_address)
	{
		if (!$this->ci_authentication_model->username_check($email_address))
		{
			$this->form_validation->set_message('_email_address_check', 'Email address not found. <a href="' . base_url() . 'home/register">Want to Register?</a>');
			return false;
		}
		else
		{
			// if there's a confirm string, fail
			$q = $this->ci_authentication_model->get_user_by_username($email_address);
			$r = $q->row();
			// if (!$this->ci_authentication_model->confirm_string_check($email_address))
			if ($r->confirm_string != '')
			{
				$this->form_validation->set_message('_email_address_check', 'Please click the registration link sent to your email. <a href="'.base_url().'home/resend_register_email/'.$r->confirm_string.'">Or resend it</a>.');
				return false;
			}
			else
			{
				return true;
			}
		}
	}
	
	// --------------------------------------------------------------------------
	
	/**
	 * _password_check function.
	 *
	 * checks to ensure password matches username in db.
	 * 
	 * @access public
	 * @param string $password
	 * @return bool
	 */
	public function _password_check($password)
	{
		$chk = $this->ci_authentication_model->password_check($this->input->post('email_address'), $password);
		if (!$chk)
		{
			$this->form_validation->set_message('_password_check', 'Incorrect password. <a href="'.base_url().'auth/request_reset_password/?email_address='.$this->input->post('email_address').'">Forgot your password?</a>');
			return false;
		}
		else
		{
			return true;
		}
	}
	
	// --------------------------------------------------------------------------
	
	/**
	 * register function.
	 *
	 * displays register form, handles validation, runs ci_authentication library 
	 * method on success.
	 * 
	 * @access public
	 * @return void
	 */
	public function register()
	{
        if(is_logged_in()) redirect();

		$this->load->helper(array('cookie', 'url'));
		$this->load->library('form_validation');
		
		// form validation
		$this->form_validation->set_rules(config_item('username_field'), 'Email Address', 'trim|required|valid_email|is_unique[' . config_item('users_table') . '.' . config_item('username_field') . ']');
		$this->form_validation->set_rules(config_item('password_field'), 'Password', 'trim|required');
		$this->form_validation->set_rules('confirm_password', 'Confirm Password', 'trim|required|matches[' . config_item('password_field') . ']');
		if ($this->form_validation->run() == FALSE)
		{
			// load view
			$this->load->view('auth/register_view', $data);
		}
		else
		{
			// redirect to configured home page
			$this->ci_authentication->do_register();
		}
	}
	
	// --------------------------------------------------------------------------
	
	/**
	 * my_profile function.
	 *
	 * Displays user profile form for logged in user, edits user and redirects
	 * on successful submit.
	 * 
	 * @access public
	 * @return void
	 */
	public function my_profile()
	{
		$this->ci_authentication->restrict_access();
		
		$this->load->helper(array('cookie', 'url'));
		
		$this->form_validation->set_rules('user_name', 'Username', 'trim|required');
		$this->form_validation->set_rules('motorcycle_name', 'Motorcycle', 'trim|required');
		$this->form_validation->set_rules('city_name', 'City', 'trim|required');
		$this->form_validation->set_rules('state_name', 'State', 'trim|required');
		$this->form_validation->set_rules('birth_date', 'Birthday', 'trim|required');
		$this->form_validation->set_rules('phone', 'Phone', 'trim|required');
		$this->form_validation->set_rules('user_description', 'Description', 'trim');

		if ($this->form_validation->run() == FALSE)
		{
			$data['item_query'] = $this->user_model->get_by_id(auth_id());
            $data['states'][0] = "All";
            $states = $this->state_model->get_all();
            foreach($states as $row)
            {
                $data['states'][$row['state_id']] = $row['state_name'];
            }

			$this->load->view('auth/my_profile_view', $data);
		}
		else
		{
			$post = $this->input->post();

            $user['user_name'] = $post['user_name'];
            $user['user_description'] = $post['user_description'];
            $user['phone'] = $post['phone'];
            $user['birth_date'] = date('Y-m-d', strtotime($post['birth_date']));
            $user['motorcycle_id'] = $this->motorcycle_model->add($post['motorcycle_name']);
            $user['city_id'] = $this->city_model->add($post['city_name']);
            $user['state_id'] = $post['state_name'];

            $this->user_model->update(auth_id(), $user);

			$this->ci_alerts->set('success', 'Profile updated.');
			redirect('auth/my_profile');
		}
	}
	
	// --------------------------------------------------------------------------
	
	/**
	 * resend_register_email function.
	 *
	 * resends register email based on confirm_string, redirects to configured page.
	 * 
	 * @access public
	 * @param string $confirm_string
	 * @return void
	 */
	public function resend_register_email($confirm_string)
	{
		$this->ci_authentication->resend_register_email($confirm_string);
	}
	
	// --------------------------------------------------------------------------
	
	/**
	 * confirm_register function.
	 *
	 * verifies confirm link, clears confirm_string column for that user, sets
	 *  flashdata for success notice, redirects to login page.
	 * 
	 * @access public
	 * @param string $confirm_string
	 * @return void
	 */
	public function confirm_register($confirm_string)
	{
		$this->ci_authentication->do_confirm_register($confirm_string);
	}
	
	// --------------------------------------------------------------------------
	
	/**
	 * request_reset_password function.
	 *
	 * send email confirmation to user, redirects to configured page.
	 * 
	 * @access public
	 * @return void
	 */
	public function request_reset_password()
	{
        $this->load->helper(array('cookie', 'url'));
        $this->load->library('form_validation');
        $this->ci_authentication->remember_me();

        // form validation
        $this->form_validation->set_rules('email_address', 'Email Address', 'trim|required|valid_email|callback__email_address_check');
        if ($this->form_validation->run() == FALSE)
        {
            // load view
            $this->load->view('auth/forgotten_password', $data);
        }
        else
        {
            $email_address = $this->input->post('email_address');
            $this->ci_authentication->do_request_reset_password($email_address);
        }
	}
	
	// --------------------------------------------------------------------------
	
	/**
	 * confirm_reset_password function.
	 *
	 * validates whether encryption of passed email and encrypted string match,
	 * emails temp password and redirects to configured page (login new password)
	 * 
	 * @access public
	 * @return void
	 */
	public function confirm_reset_password()
	{
		$this->ci_authentication->do_confirm_reset_password();
	}
	
	// --------------------------------------------------------------------------
	
	/**
	 * logout function.
	 *
	 * destroys the session, unsets userdata, sets flashdata, redirects to 
	 * configured page (login page).
	 * 
	 * @access public
	 * @return void
	 */
	public function logout()
	{
		$this->ci_authentication->do_logout();
	}
	
	// --------------------------------------------------------------------------
	
	/**
	 * alert function.
	 * 
	 * @access public
	 * @return void
	 */
	public function alert()
	{
		// load resources
		$this->load->helper('url');
		
		// load content and view
		$data['content'] = $this->load->view('auth/alert_view', $data);
	}
	
	// --------------------------------------------------------------------------
}
/* End of file auth.php */
/* Location: ./ci_authentication/examples/controllers/auth.php */