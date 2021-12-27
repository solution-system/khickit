<?php

class Dashboard extends Admin_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->not_logged_in();
		$this->data['page_title'] = 'Dashboard';
		$this->load->model('model_human_resource');
		$this->load->model('model_timesheet');
	}
	public function filter_list($list = null)
	{
		$result = array('data' => array());
		$this->load->model('model_filter');
		$data = $this->model_filter->getFilterData();
		foreach ($data as $key => $value) {
			$filter_id = $this->atri->en($value['id']);
			// button
			$buttons = '';
			// if (get_session("group_id") == "1") {
			/* $buttons = str_replace(array("\r", "\n", "\t"), "", '
                            <a href="#" onclick="delete_filter(' . $value['id'] . "," . $filter_id . ');">
                                <button type="button" class="btn btn-default" data-toggle="modal" data-target="#removeModal"><i style="color: red" class="fa fa-trash"></i></button>
                            </a>'); */
			// }
			$buttons = '<a href="#"><button type="button" class="btn btn-default del_filter" 
			ref="' . $filter_id . '" data-toggle="modal" data-target="#removeModal"><i style="color: red" class="fa fa-trash"></i></button></a>';
			$result['data'][$key] = array(
				'<a href="' . base_url("dashboard/resource_search/?") . $value['url'] . '">' . $value['title'] . '</a>',
				$buttons
			);
		} // /foreach
		if (!$list) {
			$result = str_replace(array("\r", "\n", "\t"), "", json_encode($result));
			echo $result;
		} else {
			$result = str_replace(array("\r", "\n", "\t"), "", $result);
			echo var_dump($result);
		}
		// var_dump( json_last_error());
	}
	public function index()
	{
		if (get_session('group_id') == "3") {
			redirect(base_url('dashboard/profile'), 'refresh');
		}
		$css = ' id="status" class="form-control" ';
		$group_id = get_session("group_id");
		$is_admin = ($group_id == 1) ? true : false;
		$this->data['is_admin'] = $is_admin;
		$status = get_session('status');
		if (($is_admin)
			or ($group_id == '2')
		) {

			// $this->render_template('dashboard/profile_client', $this->data);
			$this->initial_detail();
			$this->data["id"] = $this->atri->en(get_session("id"));
			$this->data['action'] = 'update';
			$limit = get_config_var("limit_resources_on_dashboard");
			$arr_res = $this->resource_available($limit);
			// print $this->db->last_query();
			// $arr_res = null;
			$this->data['resources'] = $arr_res;
			$this->data['timesheet'] = timesheet_list();
			// print $this->db->last_query();
			$opt = get_client_status_kv();
			$this->data['status_dd'] = form_dropdown('status', $opt, $status, $css);
			// print "<br>2 in status: " . $this->data["status"];

			$profile = $this->load->view('client/upgrade', $this->data, TRUE);
			$this->data['profile'] = $profile;
			// var_export($this->data);
			$this->render_template('dashboard/profile_client', $this->data);
			/* if ($is_admin) {
				$this->render_template('dashboard/index', $this->data);
			} */
		} /* else {
			redirect(base_url() . 'dashboard/profile');
		} */
	}
	function set_interview()
	{
		$this->not_logged_in();
		$user_id = get_session("id");
		$this->form_validation->set_rules('call_option', 'Suitable Interview Timing', 'trim|required');
		if ($this->form_validation->run() == TRUE) {
			$mysqltime = date("Y-m-d H:i:s", strtotime($this->input->get_post("call_option")));
			$this->auto_model->update_field('users', 'interview_date_selected', $mysqltime, $user_id);
			$interview_date_selected = $this->auto_model->get_db_value('users', "DATE_FORMAT(interview_date_selected, '%d %b, %Y %l:%s %p')", $user_id);

			$this->session->set_flashdata('success', "Your Interview Date has been set to $interview_date_selected.");
			$status = $this->auto_model->get_db_value("users", "status", $user_id);
			$status = $this->model_users->get_status($user_id, "value|$status");
			// $from_email = $this->auto_model->get_db_value("users", "email", $user_id);
			$resource = $this->auto_model->get_db_value('users', 'CONCAT_WS(" ", firstname, lastname)', $user_id);
			$id = $this->atri->en($user_id);
			$url = urlencode("users/upgrade/update/$id");
			// $to_email = get_config_var("admin_email");
			// $this->email_application($user_id, $to_email, $from_email, $status, 'Khick IT - Interview Scheduled Set');
			$this->app_email["body"] = "Resource: $resource scheduled for the interview date & Time at: $interview_date_selected.
			<br><br>$resource status is set as <b>$status</b>.
			<br><br>Now clear him from the interview and approve him in Profile for the client access.
			<br><br>
			<a href='" . base_url("process_to_login") . "/admin/" . $url . "'><button type='button'>Take $resource Interview at $interview_date_selected</button></a>";

			$this->admin_email_process(
				$user_id,
				"Khick IT - Interview Scheduled Set by Resource: $resource"
			);
		} else {
			$this->session->set_flashdata('error', 'Error occurred!!');
		}
		redirect('dashboard/profile', 'refresh');
	}
	public function profile()
	{
		$this->not_logged_in();
		if (
			(get_session('group_id') == "1") or
			(get_session('group_id') == "2")
		) {
			redirect('dashboard', 'refresh');
		}
		$trades = $this->input->get_post("trades");
		$user_id = get_session("id");
		$hired_id = timesheet_detail($user_id);
		$timesheet = timesheet_list($hired_id);
		$this->data['timesheet'] = $timesheet;
		$id = $this->atri->en($user_id);
		$this->data["id"] = $id;
		$this->data['form_action'] = '/dashboard/profile';
		$action = 'update';
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$this->load->helper("register_main");
			register_validation($action, 'all', $user_id);
		}
		if ($this->input->get_post("trade_basic") == "1") {
			if ($this->input->get_post("upgrade") <> "") {
				if ($_FILES["files"]["error"] <> 4) {
					$this->form_validation->set_rules('files[]', ' File', 'callback_fileupload_check');
				}
			}
		}
		// print 'form_error: ' . form_error();
		if ($this->form_validation->run() == TRUE) {
			// print 'inside form_valid';
			$this->load->helper("user_db_ops");
			$ret = db_operation($action, 'all', $user_id);
			if ($ret) {
				/*************** Update Trades *************/
				$trades = implode(',', $trades);
				if (is_array($trades)) {
					$trades = implode(',', $trades);
				}
				$this->db->where('user_id', $user_id);
				$update = $this->db->update('user_work', array(
					'trades' => $trades
				));
				// print $this->db->last_query();
				/*************** End of Trades *************/
				$status = $this->auto_model->get_db_value("users", "status", $user_id);
				$status = $this->model_users->get_status($user_id, "value|$status");
				// $from_email = $this->auto_model->get_db_value("users", "email", $user_id);
				$resource = $this->auto_model->get_db_value('users', 'CONCAT_WS(" ", firstname, lastname)', $user_id);
				$this->app_email["body"] = "Resource: $resource has updated the Profile. The status of this Resource is set as $status";
				$this->admin_email_process(
					$user_id,
					"Khick IT - Resource: $resource has updated the Profile"
				);
				// $this->email_application($user_id, $to_email, $from_email, $status);
				// $this->load_user_inputs("update", $user_id);
				$this->session->set_flashdata('success', 'Your Account Information has been successfully saved!');
				redirect('dashboard/profile', $this->data);
			} else {
				print "error: " . (!empty($this->data['error'])) ? $this->data['error'] : "@Dashboad->Profile - Error Found!!";
				print "ret: $ret";
			}
		} else {
			if ($_SERVER['REQUEST_METHOD'] == 'POST') {
				$this->load_user_inputs("load_only", $user_id);
			} else {
				$this->load_user_inputs("update", $user_id);
			}
			$status = $this->auto_model->get_db_value('users', 'status', $user_id);
			$interview_date = $this->auto_model->get_db_value('users', 'interview_date', $user_id);
			$this->data['interview_date'] = $interview_date;
			$this->data['status'] = $status;
			if (
				($status == "1") or
				($status == "5")
			) {
				// $add_info = $this->auto_model->get_db_value("user_add_info", "add_info", $user_id, 'user_id');
				// $this->data["user_data"]['add_info'] = $add_info;

				$available_timing = $this->auto_model->get_db_value("user_add_info", "available_timing", $user_id, 'user_id');
				// print 'status ' . $available_timing;
				$this->data["user_data"]['available_timing'] = $available_timing;

				$profile = $this->load->view('users/upgrade', $this->data, TRUE);
			} else {
				$trade_basic = $this->auto_model->get_db_value("users", "trade_basic", $user_id);
				load_user_extended_info($user_id, $trade_basic);
				$profile = $this->load->view('users/user_profile_logged_in', $this->data, TRUE);
			}
			$this->data['profile'] = $profile;
			$url = 'dashboard/profile';
			if ($_SERVER['REQUEST_METHOD'] == 'POST') {
				$this->session->set_flashdata('error', 'Error occurred!!');
				// $url .= '#edit';
			}
			$this->render_template($url, $this->data);
		}
	}
	function picture_upload()
	{
		$config['upload_path']          = "./resume/picture";
		$config['allowed_types']        = 'jpg|jpeg|png|gif';
		/* $config['max_size']             = 1000;
        $config['max_width']            = 1024;
        $config['max_height']           = 768; */

		$this->load->library('upload', $config);

		if (!$this->upload->do_upload('picture')) {
			$error = array('error' => $this->upload->display_errors("<p>", "</p>"));
			// print_r ($error);
			$this->form_validation->set_message('picture_upload', strip_tags($error['error'] . ': ' . $this->upload->data('client_name')));
			return false;
		} else {
			return true;
		}
	}
	public function ajax_upload($id = null)
	{
		$user_id = ($id <> null) ? $this->atri->de($id) : get_session("id");

		$this->form_validation->set_rules(
			'picture',
			'picture',
			'required',
			array(
				'required'      => 'You have not provided %s.',
			)
		);
		$this->form_validation->set_rules(
			'picture',
			'picture',
			'callback_picture_upload'
		);
		if ($this->form_validation->run() == TRUE) { // and $chk_resume) {

			$pic = $this->auto_model->get_db_value('users', 'picture', $user_id);
			if ($pic <> "") {
				$path = APPPATH . "../resume/picture/$pic";
				// print $path;
				unlink($path);
			}
			$this->auto_model->update_field('users', 'show_picture', $this->input->get_post("show_picture"), $user_id);

			$picture =  $this->upload->data("file_name");
			$this->auto_model->update_field('users', 'picture', $picture, $user_id);
			$this->data['picture'] = $picture;
			print "1,$picture"; // it is for pic update via ajax
		} else {
			if ($_SERVER['REQUEST_METHOD'] == 'POST') {
				print "0," . validation_errors();
			}
		}
	}
	public function resource_available()
	{
		if ($this->input->get_post("FrmSubmit") == "SubmitButton") {
			$keyword = $this->input->get_post("keyword");
			$state = $this->input->get_post("state");
			$zip = $this->input->get_post("zip");
			$trade_basic = $this->input->get_post("trade_basic");
			$trades = $this->input->get_post("trades");
			$preferred_app = $this->input->get_post("preferred_app");
			$year_of_experience = $this->input->get_post("year_of_experience");
			$travel = $this->input->get_post("travel");
			$work_remotely = $this->input->get_post("work_remotely");
			$full_cycle = $this->input->get_post("full_cycle");
			$dropdown1 = $this->input->get_post("dropdown1");
			$dropdown2 = $this->input->get_post("dropdown2");
			$dropdown3 = $this->input->get_post("dropdown3");
			$dropdown4 = $this->input->get_post("dropdown4");
			$dropdown5 = $this->input->get_post("dropdown5");
			$page = $this->input->get_post("page");
			$from_page = $this->input->get_post("from_page");
		} else {
			// $page = "1";
			$keyword = "";
			$state = "";
			$zip = "";
			$trade_basic = "";
			$trades = "";
			$preferred_app = "";
			$year_of_experience = "";
			$travel = "";
			$work_remotely = "";
			$full_cycle = "";
			$dropdown1 = "";
			$dropdown2 = "";
			$dropdown3 = "";
			$dropdown4 = "";
			$dropdown5 = "";
			$from_page = current_url();
		}
		if (strstr($from_page, 'profile')) {
			$limit = get_config_var("limit_resources_on_dashboard");
		} else {
			$limit = get_config_var("show_resources_in_list");
		}
		$sql = "
		SELECT DISTINCTROW 	u.id,
						CONCAT_WS(' ', firstname, lastname) as fullname, 
						CONCAT_WS(' ',  (SELECT region 
										FROM 	us_states
										WHERE 	code = state), city, zip) as work_availability,						
						IF (u.show_picture=1, u.picture, '') as picture,
						trade_basic,						
						uq.value_projects,
						uq.good_teammate,
						uw.trades,
						uw.year_of_experience,
						DATE_FORMAT(uq.date_of_update, '%l:%s %p %d %b %Y') as date_of_update,
						COUNT(rh.id) as hired,
						u.group_id ";
		$sFrom = "		FROM 	users u
						LEFT JOIN user_epic ue 
							ON 	u.id=ue.user_id
						LEFT JOIN user_work uw 
							ON 	u.id=uw.user_id
						LEFT JOIN user_questions uq 
							ON 	u.id=uq.user_id	
						LEFT JOIN  user_add_info uai
							ON uai.user_id = u.id
						LEFT JOIN interview i
							ON i.resource_id = u.id 	
							AND 	i.int_status = 1
						LEFT JOIN  resource_hired rh
							ON rh.interview_id = i.id
							AND rh.hired_status = 5
						LEFT JOIN  user_epic_client uec
							ON uec.user_id = u.id	
						LEFT JOIN  user_epic_questions ueq
							ON ueq.user_id = u.id		";
		$sWhere = "	WHERE 							
							u.status = 1 
						AND u.available = 1 
						";
		if ($keyword <> "") {
			$sWhere .= " AND (
							CONCAT_WS(' ', firstname, lastname) LIKE '%" . $keyword . "%' or
							# firstname LIKE '%" . $keyword . "%' or
							# lastname LIKE '%" . $keyword . "%' or
							uw.city LIKE '%" . $keyword . "%' or
							uq.value_projects LIKE '%" . $keyword . "%' or
							uq.good_teammate LIKE '%" . $keyword . "%' or
							uai.add_info LIKE '%" . $keyword . "%'
						  ) ";
		}
		if ($state <> "") {
			$sWhere .= " AND uw.state='" . $state . "' ";
		}
		if ($zip <> "") {
			$sWhere .= " AND uw.zip='" . $zip . "' ";
		}
		if ($trade_basic <> "") {
			$sWhere .= " AND u.trade_basic='" . $trade_basic . "' ";
		}
		if ($trades <> "") {
			// print "trades: $trades";
			// $trades = implode(",", $trades);
			$sWhere .= " AND FIND_IN_SET('" . $trades . "', uw.trades) ";
		}
		if ($preferred_app <> "") {
			$sWhere .= " AND uw.preferred_app='" . $preferred_app . "' ";
		}
		if ($year_of_experience <> "") {
			$sWhere .= " AND uw.year_of_experience='" . $year_of_experience . "' ";
		}
		if ($travel <> "") {
			$sWhere .= " AND ueq.travel='" . $travel . "' ";
		}
		if ($work_remotely <> "") {
			$sWhere .= " AND ueq.work_remotely='" . $work_remotely . "' ";
		}
		if (
			($full_cycle <> "") and
			($full_cycle <> "0")
		) {
			$sWhere .= " AND full_cycle='" . $full_cycle . "' ";
		}

		$dd = $this->derive_dropdown(
			$dropdown1,
			$dropdown2,
			$dropdown3,
			$dropdown4,
			$dropdown5
		);
		if ($dd) {
			$sWhere .= $dd;
		}
		$sOrder = " 			
		GROUP BY 	u.id,
					state,
					city, 
					uq.value_projects,
					uq.good_teammate,
					uw.year_of_experience,
					uw.trades,
					uq.date_of_update,
					zip ";
		$having = '';
		if ($dd) {
			$having = count(explode(',', $dd));
			$having = " HAVING COUNT(*) = $having ";
		}
		$query = $this->db->query($sql . $sFrom . $sWhere . $sOrder . $having);
		$total_resources = $query->num_rows();
		$this->data["total_resources"] = $total_resources;
		if ($limit == null) {
			$limit = get_config_var("limit_resources_on_dashboard");
		}
		$page = (isset($page) ? $page : "0") * $limit;
		($limit <> "") ? $sLimit = " LIMIT $page, $limit " : "";
		$sql_sel = "SELECT distinct u.id as ids $sFrom  $sWhere $sLimit";
		// print "$sql_sel<hr>";
		$query_ids = $this->db->query($sql_sel); //->row()->ids;
		// print "ids: $res_ids";
		if ($query_ids->num_rows() > 0) {
			foreach ($query_ids->result() as $row) {
				$id = $row->ids;
				$sql_update = "UPDATE users uu SET uu.searched=(uu.searched+1) 
						  Where uu.id = $id ";
				// print $sql_update;
				$update = $this->db->query($sql_update);
			}
		}
		$query = $this->db->query($sql . $sFrom . $sWhere . $sOrder . $having . $sLimit);
		/* if ($this->input->get_post("FrmSubmit") == "SubmitButton") {
			print "<pre>" . date('Y-m-d H:m:s') . "
		" . $this->db->last_query() . "</pre>";
		} */
		// $this->data["page_num"] = $page;
		// $this->data["limit"] = $limit;

		if ($query->num_rows() < $limit) {
			$msg = 'done';
		} else {
			$msg = 'success';
		}
		$this->data["msg"] = $msg;
		return $query->result_array();
	}
	public function resource_search($view = false)
	{
		$this->not_logged_in(2);
		$this->data['section_heading'] = 'Resource Search Result';
		$this->data['first_level_page'] = 'Dashboard';
		$this->data['second_level_page'] = 'Resource Result';
		$this->data['page_title'] = 'Resource Profile';
		$arr_res = $this->resource_available();
		$this->data['resources'] = $arr_res;
		// print "view: $view";
		if ($view) {
			$from_page = $this->input->get_post("from_page");
			if (strstr($from_page, 'dashboard')) {
				$data = $this->load->view('dashboard/resources_on_dashboard_result', $this->data, true);
			} else {
				$data = $this->load->view('dashboard/resource_search_result', $this->data, true);
			}
			// print $data;
			print
				$data
				. '<div style="display: none" id="total_resources">' . $this->data["total_resources"] . '</div>
				<div style="display: none" id="msg">' . $this->data["msg"] . '</div>';
		} else {
			$this->render_template('dashboard/resource_search', $this->data);
		}
	}
	public function proceed($resource_id)
	{
		$this->not_logged_in(2);
		$res_id = $this->atri->de($resource_id);
		if (resource_hired($res_id)) {
			redirect("/messages/?alert=hired&res=$resource_id", 'refresh');
		}
		$client_id = $this->input->get_post("client_id");
		if (get_session("group_id") == "1") {
			if ($client_id == "") {
				redirect('/messages/?alert=mishap', 'refresh');
			}
		} else {
			$client_id = get_session("id");
		}
		$sel_time = $this->input->get_post("available_timing_selected");
		if ($sel_time == "") {
			$sel_time = $this->input->get_post("available_timing_selected_other");
		}
		$this->load->helper('date');
		$date = $this->input->get_post("interview_date");
		$now   = new DateTime($date);
		$date = $now->format('Y-m-d');
		$sel_time = date('h:i:s A', strtotime($sel_time));
		if (get_session("group_id") == "1") {
			$int_status = $this->input->get_post("int_status");
		} else {
			$int_status = "-1";
		}
		$table = 'interview';
		$data = [
			'client_id'    => $client_id,
			'resource_id'    => $res_id,
			'interview_date' => $date,
			'interview_time' => $sel_time,
			'int_status' => $int_status,
			'job_desc' => $this->input->get_post("job_desc"),
		];
		// var_export($data);
		$chk_interview = interview_live($client_id, $res_id);
		if ($chk_interview) {
			redirect("/messages/?alert=interview_live&res=$resource_id", 'refresh');
		} else {
			/* $array = array('client_id' => $client_id, 'resource_id' => $res_id);
			$this->db->where($array); */
			$upgrade = $this->db->insert($table, $data);			
		}
		if (!$upgrade) {
			$this->session->set_flashdata('error', 'Error occurred!!');
			redirect("/users/user_profile/$resource_id", 'refresh');
		} else {
			$sql = "SELECT MAX(id) as id FROM $table";
			$int_id = $this->db->query($sql)->row()->id;
			/********* Send email for client & resource */
			$resource = $this->auto_model->get_db_value("users", 'CONCAT_WS(" ", firstname, lastname)', $res_id);
			$res_email = $this->auto_model->get_db_value("users", 'email', $res_id);

			$client = $this->auto_model->get_db_value("client", 'CONCAT_WS(" ", firstname, lastname)', $client_id);
			$client_email = $this->auto_model->get_db_value("client", 'email', $client_id);

			$admin_email = get_config_var("admin_email");
			$status = "Interview Scheduled";
			$id = $this->atri->en($int_id);
			$url = urlencode("interview/setup/$id");

			/***** Client Email */
			$this->app_email["body"] = "Resource: $resource interview scheduled for your at: $date $sel_time .			
			<br><br>Now clear him from the interview and approve him in Profile for the client access.
			<br><br>
			<a href='" . base_url("process_to_login") . "/client/" . $url . "'>
				<button type='button'>Take $resource Interview at $date $sel_time</button>
			</a>";
			$subject = "Interview has been scheduled for you for the Resource: $resource";
			$this->client_email($client_id, $client_email, $admin_email, $subject, 'users');

			/***** Resource Email */
			$this->app_email["body"] = "Client: $client interview has been scheduled for your at: $date $sel_time .			
			<br><br>Please get ready by proper communication method you prefer.
			<br><br>
			<a href='" . base_url("process_to_login") . "/resource/" . $url . "'>
				<button type='button'>Take $resource Interview at $date $sel_time</button>
			</a>";
			$subject = "Interview has been scheduled for you by the client: $client";
			$this->email_application($res_id, $res_email, $admin_email, $status, $subject);

			/***** Admin Email */
			$this->app_email["body"] = "Interview has been scheduled for the client: $client to Resource: $resource at: $date $sel_time .			
			<br><br>
			<a href='" . base_url("process_to_login") . "/admin/" . $url . "'>
				<button type='button'>Interview scheduled Client: $client Resource: $resource at: $date $sel_time</button>
			</a>";
			$subject = "Interview has been scheduled for the Client: $client to Resource: $resource";
			$this->admin_email_process($client_id, $subject, 'client');


			/***************** end of emails  **********/
			$this->session->set_flashdata('success', "Interview has been scheduled for the Resource: $resource");
			redirect("/dashboard", 'refresh');
		}
	}
	public function derive_dropdown(
		$dropdown1,
		$dropdown2,
		$dropdown3,
		$dropdown4,
		$dropdown5
	) {
		$sWhere = '';
		if (
			($dropdown1 <> "") or ($dropdown2 <> "") or ($dropdown3 <> "") or ($dropdown4 <> "") or ($dropdown5 <> "")
		) {
			$sWhere .= 'AND uec.dropdown IN (';
			if ($dropdown1 <> "") {
				$sWhere .= "1,";
			}
			if ($dropdown2 <> "") {
				$sWhere .= "2,";
			}
			if ($dropdown3 <> "") {
				$sWhere .= "3,";
			}
			if ($dropdown4 <> "") {
				$sWhere .= "4,";
			}
			if ($dropdown5 <> "") {
				$sWhere .= "5,";
			}
			$sWhere = rtrim($sWhere, ",");
			$sWhere .= ') ';
		}
		return $sWhere;
	}
}
