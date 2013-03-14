<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Recently_viewed {

	public $return_data;

	private $module_table = 'exp_recently_viewed';
	private $limit 		  = 5;
	private $channel;
	private $entry_id;
	private $distinct;

    public function __construct() {
    	// Make a local reference to the ExpressionEngine super object
        $this->EE =& get_instance();

        // Get tag parameters
        $this->channel = $this->EE->TMPL->fetch_param('channel');
        $this->entry_id = $this->EE->TMPL->fetch_param('entry_id');
        $this->distinct = $this->EE->TMPL->fetch_param('distinct');
    }

    public function add_entry() {
		//get channel_id
    	$this->EE->db->select('channel_id');
    	$query = $this->EE->db->get_where('exp_channels', array(
    		'channel_name' => $this->channel
    		)
    	);
		$channel_id = $query->result[0]['channel_id'];

		//check to see if user has a cookie
		if (isset($_COOKIE['recently_viewed_cookie'])) {
			$session_id = $_COOKIE['recently_viewed_cookie'];
		}
		//set cookie
		else {
			$session_id = md5(microtime());
			$this->set_cookie('recently_viewed_cookie', $session_id, time() + 60*60*24*354, '/', 0, 0);
		}
		
		//add new entry view to db
		$insert_data = array(
			'session_id' => $this->EE->db->escape_str($session_id),
			'channel_id' => $this->EE->db->escape_str($channel_id),
			'entry_id'	 => $this->EE->db->escape_str($entry_id)
		);
		$this->EE->db->insert($this->module_table, $insert_data);
		
		//get any existing entry ids
		$this->EE->db->select('view_id');
		$this->EE->db->order_by('datetime', 'desc');
		$query = $this->EE->db->get_where($this->module_table, array(
			'session_id' => $this->EE->db->escape_str($session_id),
			'channel_id' => $this->EE->db->escape_str($channel_id)
			)
		);

		//return entry ids
		if ($query->num_rows() > 0) {
			$count = 1;
			$result = $query->result();
			foreach ($result as $r) {
				if ($count > $this->limit) {
					$this->EE->db->delete($this->module_table, array('view_id' => $r['view_id']));
				}
				$count++;
			}
		}
		return true;
    }

    public function get_entries() {
		//get parameters
		$channel = $this->EE->TMPL->fetch_param('channel');
		$distinct = $this->EE->TMPL->fetch_param('distinct');

		//get channel_id
		$this->EE->db->select('channel_id');
		$query = $this->EE->db->get_where('exp_channels', array(
			'channel_name' => $channel
			)
		);

		$channel_id = $query->result[0]['channel_id'];

		if (!is_numeric($channel_id) || empty($channel_id) || empty($channel)) {
			$output = '1';
		}

		if (isset($_COOKIE['recently_viewed_cookie'])) {
			$session_id = $_COOKIE['recently_viewed_cookie'];
		}
		else {
			$output = '1';
		}

		//get entry ids
		if ($distinct == 'on') {
			$this->EE->db->distinct();
		}
		$this->EE->db->select('entry_id');
		$this->EE->db->order_by('datetime', 'desc');
		$query = $this->EE->db->get_where($this->module_table, array(
			'session_id' => $this->EE->db->escape_str($session_id),
			'channel_id' => $this->EE->db->escape_str($channel_id)
			)
		);

		//return entry ids
		if ($query->num_rows() > 0) {
			$result = $query->result();
			$q = array();
			foreach ($result as $r) {
				$q[] = $r['entry_id'];
			}
			$output = implode('|', $q);
		}
		else {
			$output = '1';
		}
		return $output;
    }

    public function set_cookie($name, $value = '', $expires=0, $path='', $domain='', $secure=false, $http_only=false) {
		 header('Set-Cookie: ' . rawurlencode($name) . '=' . rawurlencode($value)
	         .(empty($expires) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s \\G\\M\\T', $expires))
	         .(empty($path)    ? '' : '; path=' . $path)
	         .(empty($domain)  ? '' : '; domain=' . $domain)
	         .(!$secure        ? '' : '; secure')
	         .(!$http_only    ? '' : '; HttpOnly'), false);
	}
}

/* End of file mod.recently_viewed.php */
/* Location: ./system/expressionengine/third_party/recently_viewed/mod.recently_viewed.php */
?>
