<?php
class Interview extends HR_Controller
{
    var $url;
    var $date;
    var $sel_time;
    var $int_status;
    var $resource;
    var $client;
    var $res_email;
    var $client_email;
    var $admin_email;
    var $resource_id;
    var $client_id;
    public function __construct()
    {
        parent::__construct();
        $this->not_logged_in();
        $this->data['page_title'] = 'Interview';
        $this->load->model('model_human_resource');
        $this->admin_email = get_config_var("admin_email");
    }
    function init()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $gid = get_session("group_id");
            $this->resource_id = $this->input->get_post("resource_id");
            $this->client_id = $this->input->get_post("client_id");
            if ($gid == "2") {
                $this->client_id = get_session("id");
            } else if ($gid == "3") {
                $this->resource_id = get_session("id");
            }
            $this->load->helper('date');
            $this->sel_time = $this->input->get_post("available_timing_selected");
            if ($this->sel_time == "") {
                $this->sel_time = $this->input->get_post("available_timing_selected_other");
            }
            $this->date = $this->input->get_post("interview_date");
            $now   = new DateTime($this->date);
            $this->date = $now->format('Y-m-d');
            $this->sel_time = date('h:i:s A', strtotime($this->sel_time));
            $this->int_status = $this->input->get_post("int_status");
        }
        $this->resource = $this->auto_model->get_db_value("users", 'CONCAT_WS(" ", firstname, lastname)', $this->resource_id);
        $this->res_email = $this->auto_model->get_db_value("users", 'email', $this->resource_id);

        $this->client = $this->auto_model->get_db_value("client", 'CONCAT_WS(" ", firstname, lastname)', $this->client_id);
        $this->client_email = $this->auto_model->get_db_value("client", 'email', $this->client_id);

        $this->data['client_id'] = $this->client_id;
        $this->data['resource_id'] = $this->resource_id;
    }
    public function index()
    {
        $this->not_logged_in(2);
        $this->data['page_title'] = 'Interview';
        $this->data['section_heading'] = 'Interview';
        $this->data['first_level_page'] = 'Interview/Hired';
        $this->render_template('hr/resources_main', $this->data);
    }
    public function interview_list($action = null, $access_page = null)
    {
        $this->not_logged_in(3);
        // $this->load->model('model_human_resource');
        $result = array('data' => array());
        if (get_session("group_id") == "1") {
            $data = $this->model_human_resource->getInterviewData(null, null, null, $access_page);
            // print $this->db->last_query();

        } else if (get_session("group_id") == "2") {
            $data = $this->model_human_resource->getInterviewData(get_session("id"), null, null, $access_page);
            // print $this->db->last_query();
        } else if (get_session("group_id") == "3") {
            $data = $this->model_human_resource->getInterviewData(null, get_session("id"), null, $access_page);
            // print $this->db->last_query();
        }
        // print $this->db->last_query();
        // exit();	
        foreach ($data as $key => $value) {
            // $active = $value['active'];     
            $buttons = '';
            $id = $this->atri->en($value["interview_id"]);
            $buttons = str_replace(array("\r", "\n", "\t"), "", '
				<a href="/interview/setup/' . $id . '">
				<button type="button" class="btn btn-default" data-toggle="modal" data-target="#editModal">
				<i class="text-primary fa fa-edit"></i>
				</button>
				</a>');
            if ($this->session->userdata("group_id") == "1") {
                $buttons .= str_replace(array("\r", "\n", "\t"), "", '
							<a href="' . base_url('interview/int_delete/') . $id . '">
								<button type="button" class="btn btn-default" data-toggle="modal" data-target="#removeModal">
									<i style="color: red" class="fa fa-trash"></i>
								</button>
							</a>');
            }

            // $this->load->model('model_users');
            $this->int_status = get_interview_status($value['int_status'], true);
            if ($this->int_status == "Cleared")
                $this->int_status = "<span class=text-primary>$this->int_status</span>";
            else if ($this->int_status == "Inactive") {
                $this->int_status = "<span class=text-muted>$this->int_status</span>";
            } else if (($this->int_status == "Rejected") or ($this->int_status == "Suspended")) {
                $this->int_status = "<span class=text-danger>$this->int_status</span>";
            } else if ($this->int_status == "Pending") {
                $this->int_status = "<span class=text-warning>$this->int_status</span>";
            } else if ($this->int_status == "Read") {
                $this->int_status = "<span class=text-dark>$this->int_status</span>";
            } else {
                $this->int_status = "<span class=text-success>$this->int_status</span>";
            }
            if ($action == 'list') {
                $array = array(
                    $value['client'],
                    $value['resource'],
                    // . ' --> res id: ' . $value["resource_id"]
                    // . ' --> int id: ' . $value["interview_id"],
                    $value['interview_date'],
                    $value['interview_time'],
                    $this->int_status,
                    $value['date_of_update'],
                    $buttons,
                );
                if (get_session("group_id") == "2") {
                    unset($array[0]);
                } else if (get_session("group_id") == "3") {
                    unset($array[1]);
                }
                $array = array_values($array);
                $result['data'][$key] = $array;
            } else {
                $result['data'][$key] = array(
                    $value['client'],
                    $value['resource'],
                    $value['interviewed_datetime'],
                    $this->int_status,
                    $value['date_of_update'],
                    $value['job_desc'],
                    $value['resource_feedback'],
                    $value['send_admin'],
                    $buttons,
                );
            }
            // var_export($result);            
        } // /foreach
        // var_export($result);
        echo json_encode($result);
        // $this->output->set_content_type('application/json')->set_output(json_encode(array('data' => $result["data"])));
    }
    public function setup($id)
    {
        $this->data['section_heading'] = 'Khick IT - Interview';
        $this->data['first_level_page'] = 'Resource';
        $this->data['second_level_page'] = 'Interview';
        $interview_id = $this->atri->de($id);
        if (get_session("group_id") == "3") {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $this->process_resource($interview_id);
            } else {
                $user_id = get_session("id");
                $this->db->select('resource_id');
                $this->db->where('id', $interview_id);
                $this->resource_id = $this->db->get('interview')->row()->resource_id;
                if ($user_id == $this->resource_id) {
                    $this->db->select('int_status');
                    $this->db->where('id', $interview_id);
                    $this->int_status = $this->db->get('interview')->row()->int_status;
                    if ($this->int_status == "-1") {
                        $arr = array('int_status' => '0');
                        $this->db->where('id', $interview_id);
                        $update = $this->db->update('interview', $arr);
                    }
                } else {
                    // print "$user_id ---- $this->resource_id";
                    $this->session->set_flashdata('error', 'Alert Forgery!');
                    redirect('messages/?alert=forgery&invalid-resource', 'refresh');
                }
            }
        }
        $row = $this->model_human_resource->get_interview($interview_id);
        // $row = $this->model_human_resource->interviewer_hired($interview_id);
        // print $this->db->last_query();
        if (!isset($row)) {
            redirect('messages/?alert=forgery&contract-setup');
        } else {
            // var_export($row);
            $this->client_id = $row->client_id;
            $this->resource_id = $row->resource_id;
            if (resource_hired($this->resource_id)) {
                $res_id = $this->atri->en($this->resource_id);
                $cid = $this->atri->en($this->client_id);
                redirect("/messages/?alert=hired&res=$res_id&cid=$cid", 'refresh');
            }
            $job_desc = $row->job_desc;
            $status = $row->int_status;
            $interview_date = $row->interview_date;
            $interview_time = $row->interview_time;
            $resource_feedback = $row->resource_feedback;
            $send_admin = $row->send_admin;
        }
        $this->init();
        $this->load->model('model_client');
        $this->data['client_list'] = $this->model_client->get_client_dropdownlist('update', 'all');
        // $this->data['client_id'] = $this->client_id;

        $this->load->model('model_users');
        // $this->data['resource_id'] = $this->resource_id;
        $this->data['resource_list'] = $this->model_users->get_resource_dropdownlist($this->resource_id, 'specific-and-available');

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (get_session("group_id") <> "3") {
                $status = $this->input->get_post('int_status');
            }
        } else {
            $resource_feedback = $row->resource_feedback;
            // $send_admin = $row->send_admin;
        }
        $this->resource = $this->auto_model->get_db_value("users", 'CONCAT_WS(" ", firstname, lastname)', $this->resource_id);
        $this->data["interview_data"]["int_status"] = $status;
        $this->data["interview_data"]["resource_id"] = $this->resource_id;
        $this->data["interview_data"]["resource"] = $this->resource;
        $this->data["interview_data"]["id"] = $id;
        $this->data["interview_data"]["interview_date"] = $interview_date;
        $this->data["interview_data"]["send_admin"] = $send_admin == "1" ? "CHECKED" : "";

        $this->data["interview_data"]["interview_time"] = $interview_time;
        $this->data["interview_data"]["job_desc"] = $job_desc;
        $this->data["interview_data"]["resource_feedback"] = $resource_feedback;

        $this->load->helper('interview');

        if ($this->form_validation->run() == TRUE) {
            $this->proceed($interview_id, $this->resource_id, $this->client_id);
        } else {
            $this->bounce_back($interview_id, $interview_time);
        }
    }
    public function bounce_back($interview_id, $interview_time)
    {
        $id = $this->atri->en($interview_id);
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->data["interview_data"]["int_status"] = $this->input->get_post("int_status");
            $this->data["interview_data"]["resource_id"] = $this->input->get_post("resource_id");
            $this->data["interview_data"]["id"] = $id; // $this->input->get_post("id");
            $this->data["interview_data"]["interview_date"] = $this->input->get_post("interview_date");
            $this->data["interview_data"]["send_admin"] = $this->input->get_post('send_admin');

            $this->data["interview_data"]["interview_time"] = $interview_time;
            $this->data["interview_data"]["available_timing_selected_other"] = $this->input->get_post("available_timing_selected_other");
            $this->data["interview_data"]["job_desc"] = $this->input->get_post("job_desc");
            $this->data["interview_data"]["resource_feedback"] = $this->input->get_post("resource_feedback");
            $this->session->set_flashdata('error', 'Error occurred!!');
            // var_export($this->data["interview_data"]);
        }
        if (get_session("group_id") == "1") {
            // print "cid: $this->client_id";
            $this->data['profile'] = $this->x_profile($this->client_id, "view_only");
            $this->data['profile_resource'] = $this->profile($this->resource_id, "resource");
        } else if (get_session("group_id") == "2") {
            $this->data['profile'] = $this->profile($this->resource_id, "resource");
            $this->not_logged_in(2, $this->atri->en($this->client_id));
        } else if (get_session("group_id") == "3") {
            // print "client id: $this->client_id";
            $this->data['profile'] = $this->x_profile($this->client_id);
            $this->not_logged_in(3, $this->atri->en($this->resource_id));
        }
        $this->render_template("interview/setup", $this->data);
    }
    public function proceed($interview_id)
    {
        $this->not_logged_in(2);
        /* if (get_session("group_id") == "1") {
            if ($this->client_id == "") {
                redirect('/messages/?alert=mishap', 'refresh');
            }
        } else {
            $this->client_id = get_session("id");
        } */
        $table = 'interview';
        if (get_session("group_id") <> "3") {
            $data = [
                'client_id'    => $this->client_id,
                'resource_id'    => $this->resource_id,
                'interview_date' => $this->date,
                'interview_time' => $this->sel_time,
                'job_desc' => $this->input->get_post("job_desc"),
                'resource_feedback' => $this->input->get_post("resource_feedback"),
                'int_status' => $this->int_status,
            ];
        } else {
            $data = [
                'client_id'    => $this->client_id,
                'resource_id'    => $this->resource_id,
                'interview_date' => $this->date,
                'interview_time' => $this->sel_time,
                'int_status' => $this->int_status,
                'job_desc' => $this->input->get_post("job_desc"),
            ];
        }
        $array = array('id' => $interview_id);
        $this->db->where($array);
        $upgrade = $this->db->update($table, $data);
        // print $this->db->last_query();
        // exit();
        if (!$upgrade) {
            $interview_time = $this->model_human_resource->getInterviewField($interview_id, "interview_time");
            $this->bounce_back($interview_id, $interview_time);
        } else {
            if ($this->int_status == "1") {
                $this->upgrade_hired_resource($interview_id);
            }
            $this->interview_email_process($this->client_id, $this->resource_id);
            /***************** end of emails  **********/
            $this->session->set_flashdata('success', "Interview has been <b>" . get_interview_status($this->int_status, true) . "</b> for the Resource: $this->resource");
            redirect("/dashboard", 'refresh');
        }
    }
    function upgrade_hired_resource($interview_id)
    {
        $table = 'interview_cleared';
        $data = [
            'interview_id' => $interview_id,
        ];
        $count = $this->auto_model->get_db_value($table, 'COUNT(interview_id)', $interview_id, 'interview_id');
        if ($count == '0') {
            $upgrade = $this->db->insert($table, $data);
        } else {
            $array = array('interview_id' => $interview_id);
            $this->db->where($array);
            $upgrade = $this->db->update($table, $data);
        }

        $table = 'resource_hired';
        $data = [
            'interview_id' => $interview_id,
        ];
        $count = $this->auto_model->get_db_value($table, 'COUNT(interview_id)', $interview_id, 'interview_id');
        if ($count == '0') {
            $upgrade = $this->db->insert($table, $data);
        } else {
            $array = array('interview_id' => $interview_id);
            $this->db->where($array);
            $upgrade = $this->db->update($table, $data);
        }
    }
    function interview_email_process($interview_id)
    {
        $id = $this->atri->en($interview_id);
        /********* Send email for client & resource */

        $status = "Interview Scheduled";
        $this->url = urlencode("interview/setup/$id");

        /***** Client Email */
        $this->app_email["body"] = $this->client_email_content("body");
        $subject = $this->client_email_content("subject");
        $this->client_email($this->client_id, $this->client_email, $this->admin_email, $subject, 'users');

        /***** Resource Email */
        $this->app_email["body"] = $this->resource_email_content("body");
        $subject = $this->resource_email_content("subject");
        $this->email_application($this->resource_id, $this->res_email, $this->admin_email, $status, $subject);

        /***** Admin Email */
        $this->app_email["body"] = $this->admin_email_content("body");
        $subject = $this->admin_email_content("subject");
        $this->admin_email_process($this->client_id, $subject, 'client');
    }
    function admin_email_content($field)
    {
        if (($this->int_status == "-1") or
            ($this->int_status == "0")
        ) {
            if ($field == "body") {
                return "Interview has been scheduled for the client: $this->client to Resource: $this->resource at: $this->date $this->sel_time .			
                        <br><br>
                        <a href='" . base_url("process_to_login") . "/admin/" . $this->url . "'>
                            <button type='button'>Interview scheduled Client: $this->client Resource: $this->resource at: $this->date $this->sel_time</button>
                        </a>";
            } else {
                return "Interview has been scheduled by the client: $this->client for Resource: $this->resource";
            }
        } else if ($this->int_status == "1") {
            if ($field == "body") {
                return "Resource: $this->resource has been cleared in the interview with the Client:
                        $this->client at the time: $this->date $this->sel_time.			
                    <br><br>
                    <a href='" . base_url("process_to_login") . "/admin/" . $this->url . "'>
                        <button type='button'>Interview scheduled Client: $this->client Resource: $this->resource at: $this->date $this->sel_time</button>
                    </a>";
            } else {
                return "Resource: $this->resource interview cleared with Client: $this->client";
            }
        } else {
            if ($field == "body") {
                return "Resource: $this->resource has been " . get_interview_status($this->int_status, true) . " in an interview with Client:
                        $this->client at the time: $this->date $this->sel_time.			
                    <br><br>
                    <a href='" . base_url("process_to_login") . "/admin/" . $this->url . "'>
                        <button type='button'>Interview scheduled Client: $this->client Resource: $this->resource at: $this->date $this->sel_time</button>
                    </a>";
            } else {
                return "Resource: $this->resource interview " . get_interview_status($this->int_status, true) . " with Client: $this->client";
            }
        }
    }
    function client_email_content($field)
    {
        if (($this->int_status == "-1") or
            ($this->int_status == "0")
        ) {
            if ($field == "body") {
                return "Resource: $this->resource interview scheduled for your at: $this->date $this->sel_time .			
			<br><br>Now clear him from the interview and approve him in Profile for the client access.
			<br><br>
			<a href='" . base_url("process_to_login") . "/client/" . $this->url . "'>
				<button type='button'>Take $this->resource Interview at $this->date $this->sel_time</button>
			</a>";
            } else {
                return "Interview has been scheduled you for the Resource: $this->resource";
            }
        } else if ($this->int_status == "1") {
            if ($field == "body") {
                return "Resource: $this->resource has been cleared you at: $this->date $this->sel_time.			
                    <br><br>
                    <a href='" . base_url("process_to_login") . "/client/" . $this->url . "'>
                        <button type='button'>Interview scheduled Client: $this->client Resource: $this->resource at: $this->date $this->sel_time</button>
                    </a>";
            } else {
                return "Resource: $this->resource interview Cleared with you!";
            }
        } else {
            if ($field == "body") {
                return "Resource: $this->resource has been " . get_interview_status($this->int_status, true) . " in an interview with you at time: $this->date $this->sel_time.			
                    <br><br>
                    <a href='" . base_url("process_to_login") . "/client/" . $this->url . "'>
                        <button type='button'>Interview scheduled Client: $this->client Resource: $this->resource at: $this->date $this->sel_time</button>
                    </a>";
            } else {
                return "Resource: $this->resource interview " . get_interview_status($this->int_status, true) . " with Client: $this->client";
            }
        }
    }
    function resource_email_content($field)
    {
        if (($this->int_status == "-1") or
            ($this->int_status == "0")
        ) {
            if ($field == "body") {
                return "Client: $this->client scheduled an interview for your at: $this->date $this->sel_time .			
			<br><br>Please get ready by proper communication method you prefer.
			<br><br>
			<a href='" . base_url("process_to_login") . "/resource/" . $this->url . "'>
				<button type='button'>Take $this->resource Interview at $this->date $this->sel_time</button>
			</a>";
            } else {
                return "Interview has been scheduled for you by the client: $this->client";
            }
        } else if ($this->int_status == "1") {
            if ($field == "body") {
                return "Dear $this->resource,<br>
                        As interview has been cleared with the Client:
                        $this->client at the time: $this->date $this->sel_time.			
                    <br><br>
                    <a href='" . base_url("process_to_login") . "/admin/" . $this->url . "'>
                        <button type='button'>Interview scheduled Client: $this->client Resource: $this->resource at: $this->date $this->sel_time</button>
                    </a>";
            } else {
                return "Congratulation interview cleared with Client: $this->client";
            }
        } else {
            if ($field == "body") {
                return "Dear Resource: $this->resource,<br> 
                Your interview has been " . get_interview_status($this->int_status, true) . " with the Client:
                        $this->client at the time: $this->date $this->sel_time.			
                    <br>
                    Unfortunately, $this->client has decided not to move forward with you at this time.
                    ";
            } else {
                return "$this->client has decided not to move forward with you at this time.";
            }
        }
    }
    public function int_delete($id)
    {
        $this->not_logged_in(1);
        if ($id) {
            $int_id = $this->atri->de($id);
            $this->load->model('model_human_resource');
            $hired_data = $this->model_human_resource->getHiredData(null, $int_id, "delete");
            $hired_count = count($hired_data);
            // print "rec co: $hired_count";
            if ($hired_count > 0) {
                $this->client_id = $this->model_human_resource->getInterviewField($int_id, 'client_id');
                $this->client = $this->auto_model->get_db_value('client', "CONCAT_WS(' ', firstname, lastname)", $this->client_id);

                $this->resource_id = $this->model_human_resource->getInterviewField($int_id, 'resource_id');
                $this->resource = $this->auto_model->get_db_value('users', "CONCAT_WS(' ', firstname, lastname)", $this->resource_id);

                $stat = $this->model_human_resource->getInterviewField($int_id, "int_status");
                $tenure_start = $hired_data["tenure_start"];
                $tenure_end = $hired_data["tenure_end"];
                $paid = $hired_data["paid"];

                $this->session->set_flashdata('error', "Interview - Hiring Detail: This interview cannot be deleted!<br><br>
                Resource: <strong>$this->resource</strong> already running $paid & $stat tenure $tenure_start-$tenure_end by the Client: $this->client!");
                // $this->data['id'] = $id;
                // redirect(base_url('hr'), 'refresh');
            } else {
                // $this->user_detail($int_id);
                if ($this->input->post('confirm')) {
                    $table = "resource_hired";
                    $query = $this->db->query("SELECT id as val FROM $table rh WHERE interview_id = ?", array($int_id));
                    // print $this->db->last_query() . "<hr>";                    
                    if ($query->num_rows() > 0) {
                        $hid = $query->row()->val;
                        $sql = "DELETE FROM $table WHERE id=?";
                        $delete = $this->db->query($sql, array($hid));
                        $table = "resource_tasks";
                        $query = $this->db->query("SELECT id as val FROM $table rh WHERE hired_id = ?", array($hid));
                        if ($query->num_rows() > 0) {
                            $tid = $query->row()->val;
                            $sql = "DELETE FROM $table WHERE id=?";
                            $delete = $this->db->query($sql, array($tid));
                        }
                    }
                    $table = "interview";
                    $sql = "DELETE FROM $table WHERE id=?";
                    $delete = $this->db->query($sql, array($int_id));
                    // $delete = $this->model_users->delete($int_id);
                    if ($delete == true) {
                        $this->session->set_flashdata('success', 'Interview Detail successfully removed');
                        redirect(base_url('hr'), 'refresh');
                    } else {
                        $this->session->set_flashdata('error', 'Error occurred!!');
                        redirect(base_url('dashboard/'), 'refresh');
                    }
                } else {
                    $this->data["interview_data"]["id"] = $id;
                    $is_exist = $this->model_human_resource->getInterviewField($int_id, "count");
                    if (!$is_exist) {
                        redirect('/messages/?alert=mishap&lock=interview', 'refresh');
                    }
                    $job_desc = $this->model_human_resource->getInterviewField($int_id, "job_desc");
                    // $active = $this->model_human_resource->getInterviewField($int_id, "active") == "1" ? "Active" : "Replied";
                    $status = $this->model_human_resource->getInterviewField($int_id, "int_status");
                    // print "status: $status";
                    $interview_date = $this->model_human_resource->getInterviewField($int_id, "interview_date");
                    $interview_time = $this->model_human_resource->getInterviewField($int_id, "interview_time");
                    $resource_feedback = $this->model_human_resource->getInterviewField($int_id, "resource_feedback");

                    $this->data["interview_data"]["int_date"] = $interview_date;
                    // $this->data["interview_data"]["active"] = $active;
                    $this->data["interview_data"]["stat"] = $status;

                    $this->data["interview_data"]["interview_time"] = $interview_time;
                    $this->data["interview_data"]["job_desc"] = $job_desc;
                    // $this->data["interview_data"]["feedback"] = $feedback;
                    $this->data["interview_data"]["resource_feedback"] = $resource_feedback;

                    $this->resource_id = $this->model_human_resource->getInterviewField($int_id, "resource_id");
                    $this->client_id = $this->model_human_resource->getInterviewField($int_id, "client_id");
                    $this->resource = $this->auto_model->get_db_value("users", "CONCAT_WS(' ', firstname, lastname)", $this->resource_id);
                    $this->data["interview_data"]["resource"] = "$this->resource";
                    $this->client = $this->auto_model->get_db_value("client", "CONCAT_WS(' ', firstname, lastname)", $this->client_id);
                    $this->data["interview_data"]["client"] = $this->client;

                    $this->render_template('interview/delete', $this->data);
                }
            }
        }
    }
}
