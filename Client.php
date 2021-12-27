<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Client extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        /* if ($this->session->userdata("group_id") <> "1") {
            redirect('dashboard', 'refresh');
        } */
        $this->data['page_title'] = 'Clients';
        // $this->load->model('auto_model');
        $this->load->model('model_client');
        $this->load->helper("client_db_ops");
        // $this->load->model('model_users');
        $this->load->helper('client_main');
    }
    public function client_list()
    {
        $result = array('data' => array());
        $this->load->model('model_human_resource');
        // var_export($opt);
        $data = $this->model_client->getClientData();
        $opt = get_client_status_kv();
        foreach ($data as $key => $value) {
            $client_id = $this->atri->en($value["id"]);
            $task_count = $this->model_human_resource->tasks_exist(null, null, $value["id"], 'task_count');
            if ($this->session->userdata("group_id") == "1") {
                $buttons = str_replace(array("\r", "\n", "\t"), "", '<!--
							<a href="' . base_url('messages/msg_list/') . $client_id . '">
								<button type="button" class="btn btn-default" data-toggle="modal" data-target="#editModal">
								<i class="text-primary fa fa-envelope" aria-hidden="true"></i>
								</button>
							</a> -->
							<a href="' . base_url('client/upgrade/update/') . $client_id . '">
								<button type="button" class="btn btn-default" data-toggle="modal" data-target="#editModal">
									<i class="text-primary fa fa-edit"></i>
								</button>
							</a>
							<a href="' . base_url('client/delete/') . $client_id . '">
								<button type="button" class="btn btn-default" data-toggle="modal" data-target="#removeModal">
									<i style="color: red" class="fa fa-trash"></i>
								</button>
							</a>');
            }
            $onchange_availability = " onchange=\"update_field('client', '" . $client_id . "', this.value, 'status', 'id');\" ";
            $js = ' id="status" ' . $onchange_availability . ' class="form-control" style="float-right;" ';
            // $status = "value|" . $value['status'];
            $status = $value['status'];
            $result['data'][$key] = array(
                $value['title'],
                $value['email'],
                $value['fullname'],
                $value['confirmed'],
                $value['phone'],
                $value['status_dd'] = form_dropdown('status', $opt, $status, $js),
                $value['resources'],
                $task_count,
                $buttons
            );
        } // /foreach
        // var_export( $result);
        echo json_encode($result);
    }
    public function index()
    {
        redirect('client/manage', 'refresh');
    }
    public function manage()
    {
        $this->not_logged_in(1);
        $this->data['section_heading'] = 'Manage Client';
        $this->data['first_level_page'] = 'Clients List';
        $this->render_template('client/index', $this->data);
    }
    public function delete($id)
    {
        $this->not_logged_in(1);
        $client_id = $this->atri->de($id);
        $this->load->model('model_human_resource');
        $task_exist = $this->model_human_resource->tasks_exist(null, null, $client_id);
        // print $this->db->last_query();
        if ($task_exist) {
            $client = $this->auto_model->get_db_value('client', "CONCAT_WS(' ', firstname, lastname)", $client_id);
            $this->session->set_flashdata('error', "Client: <strong>$client</strong> already have running tenure Resource(s)!");
            $this->manage();
            // $this->data['id'] = $id;
            // $this->render_template('users/index', $this->data);
        } else if ($this->input->get_post("FormAction") == "delete") {
            $sql = 'DELETE	
                    FROM	resource_tasks 
                    WHERE 	resource_tasks.hired_id IN (
                                            SELECT 	resource_hired.id
                                            FROM	resource_hired 
                                            WHERE	resource_hired.interview_id in (
                                                                    SELECT interview.id 
                                                                    FROM 	interview 
                                                                    WHERE 	interview.client_id=?
                                                                    )
                                                                ) ';
            $int_del = $this->db->query($sql, array($client_id));
            if ($int_del) {
                $sql = 'DELETE
								FROM	resource_hired 
								WHERE	resource_hired.interview_id in (
														SELECT  interview.id 
														FROM 	interview 
														WHERE 	interview.client_id=?
														) ';
                $delete = $this->db->query($sql, array($client_id));
            }
            if ($delete == true) {
                $sql = 'DELETE FROM interview WHERE client_id=?';
                $delete = $this->db->query($sql, array($client_id));
            }
            if ($delete == true) {
                $this->db->where('id', $client_id);
                $delete = $this->db->delete('client');
            }
            if ($delete == true) {
                $this->session->set_flashdata('success', 'Successfully removed');
                redirect('client/', 'refresh');
            } else {
                $this->session->set_flashdata('error', 'Error occurred!!');
                redirect('client/delete/' . $id, 'refresh');
            }
        } else {
            $data = $this->model_client->getClientData($client_id);
            // print "fn: " . $data['fullname'];            
            $this->data['fullname'] = $data['fullname'];
            $this->data['id'] = $id;
            // var_export($this->data);
            $this->render_template('client/delete', $this->data);
        }
    }

    public function upgrade($action = 'add', $id = null)
    {
        $client_id = null;
        if ($action == 'update') {
            $this->not_logged_in(2, $id);
            $client_id = $this->atri->de($id);
        }
        $css = ' id="status" class="form-control" ';
        client_validation($action);
        $this->data['id']  = $id;
        $this->data['action']  = $action;
        $title = $this->input->get_post('title');
        $this->data['title'] = $title;
        $email = $this->input->get_post('email');
        $this->data['email'] = $email;
        $firstname = $this->input->get_post('firstname');
        $this->data['firstname'] = $firstname;
        $lastname = $this->input->get_post('lastname');
        $this->data['lastname'] = $lastname;
        $phone = $this->input->get_post('phone');
        $this->data['phone'] = $phone;
        $opt = get_client_status_kv();
        $this->data['status_dd'] = form_dropdown('status', $opt, $this->input->get_post("status"), $css);
        if ($this->form_validation->run() == TRUE) { // and $chk_resume) {
            check_spam();
            $old_status = null;
            if ($action == 'update') {
                $old_status = $this->auto_model->get_db_value("client", "status", $client_id);
            }
            $upgrade = client_operation($action, $client_id);
            // print "upg client: $upgrade<br>";
            if ($upgrade) {
                if ($action == 'add') {
                    $this->db->select_max('id', 'max');
                    $client_id = $this->db->get('client')->row()->max;
                    $this->confirm_email($client_id, 'client');
                }
                // print "upg client_id: $client_id<br>";

                if (get_session("group_id") == "1") {
                    // if ($action == 'update') {
                    if ($old_status <> $this->input->get_post("status")) {
                        $this->confirm_email($client_id, 'client');
                    }
                    // }
                    $this->session->set_flashdata('success', 'Client successfully created and email sent for confirmation.');
                    redirect(base_url() . 'client/manage');
                } else {
                    if ($action == 'add') {
                        redirect('messages/?alert=client_registered', 'refresh');
                    }
                    $fullname = $this->input->get_post('firstname') . ' ' . $this->input->get_post('lastname');
                    $email = $this->input->get_post('email');
                    $logged_in_sess = array(
                        'title' => $title,
                        'fullname'  => $fullname,
                        'email'     => $email,
                        'group_name' =>  'Client',
                        'logged_in' => TRUE,
                    );
                    // var_export($logged_in_sess);
                    // exit;                    
                    $this->session->set_userdata($logged_in_sess);
                    $this->session->set_flashdata('success', 'Successfully ' . ucfirst($action));
                    redirect(base_url() . 'dashboard');
                }
            } else {
                $this->session->set_flashdata('error', 'Error occurred!!');
                $this->render_template('/client/upgrade', $this->data);
            }
        } else {
            // false case
            // $this->data = $this->add_fields($group);
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                // print 'else ';
                $this->session->set_flashdata('error', 'Error occurred!!');
            } else if (isset($client_id)) {
                $data = $this->model_client->getClientData($client_id);
                // array_push($data, $this->data);
                $this->data['fullname']  = $data['fullname'];
                $this->data['firstname']  = $data['firstname'];
                $this->data['lastname']  = $data['lastname'];
                $this->data['email']     = $data['email'];
                // print "status: " . $data["status"];
                $this->data['status_dd'] = form_dropdown('status', $opt, intval($data["status"]), $css);
                $this->data['title'] = $data["title"];
                $this->data['phone'] = $data["phone"];
            }
            // var_export($this->data);
            $this->render_template('/client/upgrade', $this->data);
        }
    }
    function setpass($id) {
		redirect(base_url("/confirm/setpass") . "/$id/client", 'refresh');        
    }
}
