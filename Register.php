<?php

class Register extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->data['page_title'] = 'Register';
        $this->load->helper("user_db_ops");
        $this->load->model('model_users');
        // $this->load->helper('array');
        $this->load->helper('register_main');
    }

    function resume_upload()
    {
        $config['upload_path']          = "./resume/";
        $config['allowed_types']        = 'doc|pdf|txt|docx';
        $config['max_size']             = 1000;
        $config['max_width']            = 1024;
        $config['max_height']           = 768;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('resume')) {
            $error = array('error' => $this->upload->display_errors("<p>", "</p>"));
            // print_r ($error);
            $this->form_validation->set_message('resume_upload', strip_tags($error['error'] . ': ' . $this->upload->data('client_name')));
            return false;
        } else {
            $this->data['resume'] =  $this->upload->data("file_name");
            return true;
        }
    }

    public function index()
    {
        $this->register();
    }
    public function register($group_name = 'resource')
    {
        $this->logged_in();

        /* if (get_session("group_id") <> "3") {
            $this->session->set_flashdata('error', 'Alert Forgery!');
			redirect('messages/?alert=forgery', 'refresh');
        }    */
        $this->data["user_data"]['group_name'] = "Resource";
        $group_id = '3';
        $this->data["user_data"]['group'] = $group_id;
        $this->data["user_data"]['group_id'] = $group_id;

        $this->data['heading1'] = 'How IT works';
        $this->data['heading2'] = 'We are Hiring';
        $this->data['heading_detail'] = 'Tation argumentum et usu, dicit viderer evertitur te has. Eu dictas concludaturque usu, facete detracto patrioque an per, lucilius pertinacia eu vel.';
        $this->data['subheading1'] = "Register - " . ucfirst($group_name);
        $this->data['subheading2'] = "Personal Info";

        register_validation('add', 'register');
        // $this->load->model('model_groups');
        // $group_data = $this->model_groups->getGroupData();
        // $this->data['group_data'] = $group_data;

        $this->data["user_data"]['email']  = $this->input->get_post('email');
        $this->data["user_data"]['firstname'] = $this->input->get_post('firstname');
        $this->data["user_data"]['lastname'] = $this->input->get_post('lastname');
        $this->data["user_data"]['phone'] = $this->input->get_post('phone');
        if ($this->form_validation->run() == TRUE) { // and $chk_resume) {
            check_spam();
            $user_id = db_operation('add', 'register');
            if (
                ($user_id <> "") &&
                ($user_id <> false)
            ) {
                $this->session->set_flashdata('success', 'Successfully created');
                if ($this->session->userdata("group_id") == "1") {
                    redirect('/register/setpass/' . $this->atri->en($user_id), 'refresh');
                } else {
                    $user_id = $this->atri->de($user_id);
                    // print "user_id @ register: $user_id";
                    $this->confirm_email($user_id, 'register');
                    redirect('messages/?alert=registered&token=' . get_session("token") . '&diff=' . get_session("diff"), 'refresh');
                }
            } else {
                // $this->data = $this->add_fields($group);
                $this->session->set_flashdata('error', 'Error occurred!!');
                $this->render_template('/users/register', $this->data);
            }
        } else {
            // false case
            // $this->data = $this->add_fields($group);
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $this->session->set_flashdata('error', 'Error occurred!!');
            }
            $this->render_template('/users/register', $this->data);
        }
    }
    public function setpass($id, $is_forgot_recovery = '')
    {
        $user_id = $this->atri->de($id);
        $fullname = $this->auto_model->get_db_value('users', "CONCAT_WS(' ', firstname, lastname)", $user_id);
        $this->load_user_inputs("load_only", $user_id);
        $trade_basic = $this->auto_model->get_db_value("users", "trade_basic", $user_id);
        load_user_extended_info($user_id, $trade_basic);

        // $this->user_detail($user_id, 'setpass');

        // $this->data['heading1'] = 'How IT works';
        $this->data['heading2'] = 'Password Setting';
        $this->data['heading_detail'] = 'Please enter atleast a capital letter and one number when creating the password.';
        if ($is_forgot_recovery == '') {
            $title = "Welcome $fullname,";
        } else {
            $title = "$fullname Recover Password";
        }
        $this->data['subheading1'] = $title;

        $this->data['subheading2'] = "Please assign your account password:";
        $this->data["is_forgot_recovery"] = $is_forgot_recovery;
        if ($user_id == "") {
            $this->session->set_flashdata('error', 'Alert Forgery!');
            redirect('messages/?alert=forgery', 'refresh');
        }
        if ($is_forgot_recovery <> 'forgot_recovery') {
            $is_set_pass = $this->auto_model->get_db_value('users', 'password', $user_id);
            if ($is_set_pass <> "") {
                $this->session->set_flashdata('success', 'Password Already Set. Please login...');
                redirect('/rlogin', 'refresh');
            }
        }
        register_validation('add', 'setpass');
        if ($id) {
            $this->data["user_data"]['email'] = $this->auto_model->get_db_value('users', 'email', $user_id);
            if ($this->form_validation->run() == TRUE) {
                $update = db_operation('edit', 'setpass', $user_id);
                if ($update == true) {
                    if ($is_forgot_recovery == '') {
                        $status = '-2';
                        $upgrade = $this->model_users->update_status($status, $user_id);
                        // print $this->db->last_query();
                        // exit;
                    } else {
                        $upgrade = true;
                    }
                    if ($upgrade == true) {
                        $this->session->set_flashdata('success', 'Password Successfully Set!');
                        if ($this->session->userdata('logged_in') == TRUE) {
                            redirect('/register/register_work/' . $id, 'refresh');
                        } else {
                            redirect('/rlogin', 'refresh');
                        }
                    }
                } else {
                    $this->session->set_flashdata('error', 'Error occurred!!');
                    redirect('/register/setpass/' . $id, 'refresh');
                }
            } else {
                if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                    $this->session->set_flashdata('error', 'Error occurred!! ');
                }
                $this->data["user_data"]['id'] = $id;
                $this->render_template('/users/setpass', $this->data);
            }
        }
    }
    function register_work($id)
    {
        $this->not_logged_in(3, $id);
        $user_id = $this->atri->de($id);

        $trades = $this->input->get_post("trades");
        // print_r( $t);

        $this->load_user_inputs("load_only", $user_id);
        // print_r( $t);

        $trade_basic = $this->auto_model->get_db_value("users", "trade_basic", $user_id);
        load_user_extended_info($user_id, $trade_basic);
        // print_r( $t);

        // $this->user_detail($user_id, 'register_work');

        $this->data['heading1'] = 'Tell Us About Yourself';
        $this->data['heading2'] = 'We are Hiring';
        $this->data['heading_detail'] = 'Tation argumentum et usu, dicit viderer evertitur te has. Eu dictas concludaturque usu, facete detracto patrioque an per, lucilius pertinacia eu vel.';
        $this->data['subheading1'] = "Welcome " . $this->data["user_data"]['fullname'] . ",";
        $this->data['subheading2'] = "Work Location";

        register_validation('add', 'register_work', $user_id);

        $this->data["user_data"]['id'] = $id;
        /* $this->data['user_data']['state_code'] = $this->input->get_post('state');
        $this->data['user_data']['state'] = $this->input->get_post('state');
        $this->data['user_data']['city'] = $this->input->get_post('city');
        $this->data['user_data']['zip'] = $this->input->get_post('zip'); */
        $this->data['trade_basic_list'] = get_trade_list();

        // $this->data["user_data"]['trade_basic'] = $this->input->get_post('trade_basic');
        // $this->data["user_data"]['trades'] = $this->input->get_post('trades');
        // $this->data['fullname'] = $this->auto_model->get_db_value('users', 'CONCAT_WS(" ", firstname, lastname)', $user_id);
        if ($this->form_validation->run() == TRUE) {
            // $t = $this->input->get_post("trades");
            // print_r( $t);
            $update = db_operation('add', 'register_work', $user_id);
            /*************** Update Trades *************/
            // var_export($trades);
            // print "trades: $trades";
            $trades = implode(',', $trades);
            if (is_array($trades)) {
                $trades = implode(',', $trades);
            }
            $this->db->where('user_id', $user_id);
            $update = $this->db->update('user_work', array(
                'trades' => $trades
            ));
            /*************** End of Trades *************/
            $this->session->set_userdata("trade_basic", $this->input->get_post("trade_basic"));
            $this->session->set_userdata("trade_basic_text", $this->input->get_post("trade_basic"));
            if ($update == true) {
                if (($this->input->get_post('trade_basic') == "1") or
                    $this->input->get_post('trade_basic') == "3"
                ) {
                    $status = "-3";
                    $redirect = '/register/register_experience/' . $id;
                } else {
                    $status = "-7";
                    $redirect = '/register/register_questions/' . $id;
                }
                $update = $this->model_users->update_status($status, $user_id);
                if ($update == true) {
                    redirect($redirect, "refresh");
                }
            } else {
                $this->data["user_data"]['user_trades'] = $this->input->get_post('user_trades');
                $this->data["user_data"]['other'] = $this->input->get_post('other');
                // $this->data = $this->add_fields($group);
                $this->session->set_flashdata('error', 'Error occurred!!');
                // print 'test 1';
                redirect('/register/register_work', 'refresh');
            }
        } else {
            // false case
            /* $this->data['location'] = $this->db->query("SELECT code, region FROM us_states ORDER BY region;")->result();
            sort($this->data['location']);
            $this->data['trade_basic_list'] = explode(",", "1,Epic Certified,2,Credentialed,3,Go-Live Support,4,Other");
            $this->data['trade_list'] = $this->auto_model->get_db_list("trade_list"); */
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $this->session->set_flashdata('error', 'Error occurred!!');
            } else {
                $this->session->set_flashdata('success', "Please complete your Profile Work Experience...");
            }
            // print $this->data['location'];
            $this->render_template('/users/register_work', $this->data);
        }
    }
    function register_experience($id)
    {
        $this->not_logged_in(3, $id);
        $user_id = $this->atri->de($id);
        $this->load_user_inputs("load_only", $user_id);
        $trade_basic = $this->auto_model->get_db_value("users", "trade_basic", $user_id);
        load_user_extended_info($user_id, $trade_basic);

        // $this->user_detail($user_id, 'register_experience');
        $this->data['heading1'] = 'Your Experience';
        $this->data['heading2'] = 'Select Your Application(s)';
        $this->data['heading_detail'] = 'Tation argumentum et usu, dicit viderer evertitur te has. Eu dictas concludaturque usu, facete detracto patrioque an per, lucilius pertinacia eu vel.';
        $this->data['subheading1'] = "Welcome " . $this->data['fullname'] . ",";
        $this->data['subheading2'] = "Work Experience";
        register_validation('add', 'register_experience');
        $year_of_experience = $this->input->get_post("year_of_experience");
        $preferred_app = $this->input->get_post("preferred_app");
        $this->data['year_of_experience'] = $year_of_experience;
        $this->data['preferred_app'] = $preferred_app;
        if ($this->form_validation->run() == TRUE) {
            $update = db_operation('add', 'register_experience', $user_id);
            if ($update == true) {
                if (get_trade_basic($user_id) == "1") {
                    $status = '-4';
                    $redirect = '/register/register_epic_certified_upload/' . $id;
                } else {
                    $status = '-7';
                    $redirect = '/register/register_questions/' . $id;
                }
                $update = $this->model_users->update_status($status, $user_id);
                if ($update == true) {
                    $this->session->set_flashdata('success', 'Information has been successfully saved!');
                    redirect($redirect, 'refresh');
                }
            } else {
                $this->session->set_flashdata('error', 'Error occurred!!');
                redirect('/register/register_experience/' . $id, 'refresh');
            }
        } else {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                // print 'if';
                $this->session->set_flashdata('error', 'Error occurred!!');
            } else {
                // print 'else';
                $this->session->set_flashdata('success', "Please complete your Profile first...");
            }
            $this->data["user_data"]['id'] = $id;
            $this->render_template('/users/register_experience', $this->data);
        }
    }
    function register_epic_certified_upload($id)
    {
        $this->not_logged_in(3, $id);
        $this->data['heading1'] = 'Proof of Certification';
        $this->data['heading2'] = 'Upload Images';
        $this->data['heading_detail'] = 'Tation argumentum et usu, dicit viderer evertitur te has. Eu dictas concludaturque usu, facete detracto patrioque an per, lucilius pertinacia eu vel.';
        $this->data['subheading1'] = "Welcome " . $this->data['fullname'] . ",";
        $this->data['subheading2'] = "Epic Certification Upload";
        $user_id = $this->atri->de($id);
        $this->load_user_inputs("load_only", $user_id);
        $trade_basic = $this->auto_model->get_db_value("users", "trade_basic", $user_id);
        load_user_extended_info($user_id, $trade_basic);

        // $this->user_detail($user_id, 'register_epic_certified_upload');
        if ($this->data["user_data"]["trade_basic"] <> "1") {
            redirect('/messages/?alert=mishap', 'refresh');
        }
        // $fcount = count(explode(',', $this->data["user_data"]["trades"]));
        $config = array(
            'allowed_types' => 'pdf|jpg|jpeg|gif|png',
            'upload_path' => './resume/cert_files/',
            'max_size' => 1500000,
            'remove_spaces' => TRUE,
            'multi' => 'all'
        );

        if (empty($_FILES['files']['name'])) {
            $this->form_validation->set_rules('files[]', ' File', 'required');
        }

        $this->form_validation->set_rules('files[]', ' File', 'callback_fileupload_check');

        if ($this->form_validation->run() == TRUE) {
            db_operation('add', 'register_epic_certified_upload', $user_id);
            $status = "-5";
            $update = $this->model_users->update_status($status, $user_id);
            if ($update == true) {
                redirect('/register/register_epic_project_detail/' . $id, 'refresh');
            }
            // }
        }
        // }
        // }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // $this->data["user_data"]['trade_basic'] = $this->input->get_post("trade_basic");
            // $this->data["user_data"]['trades'] = $this->input->get_post("trades");
            $this->session->set_flashdata('error', 'Error occurred!!');
        } else {
            $this->session->set_flashdata('success', "Please complete your Profile first...");
        }
        $this->data["user_data"]['id'] = $id;
        $this->render_template('/users/register_epic_certified_upload', $this->data);
    }
    function register_epic_project_detail($id)
    {
        $this->not_logged_in(3, $id);
        $user_id = $this->atri->de($id);
        $this->load_user_inputs("load_only", $user_id);
        $trade_basic = $this->auto_model->get_db_value("users", "trade_basic", $user_id);
        load_user_extended_info($user_id, $trade_basic);

        // $this->user_detail($user_id, 'register_epic_project_detail');
        if ($this->data["user_data"]["trade_basic"] <> "1") {
            redirect('/messages/?alert=mishap', 'refresh');
        }
        $this->data['heading1'] = 'Your Experience';
        $this->data['heading2'] = 'Project Detail';
        $this->data['heading_detail'] = 'Tation argumentum et usu, dicit viderer evertitur te has. Eu dictas concludaturque usu, facete detracto patrioque an per, lucilius pertinacia eu vel.';
        $this->data['subheading1'] = "Welcome " . $this->data["user_data"]['fullname'] . ",";
        $this->data['subheading2'] = "Epic Project Detail";
        register_validation('add', 'register_epic_project_detail', $user_id);
        // $full_cycle = $this->input->get_post("full_cycle");
        // $this->data["user_data"]['full_cycle'] = $full_cycle;
        if ($this->form_validation->run() == TRUE) {
            $create = db_operation('add', 'register_epic_project_detail', $user_id);
            if ($create == true) {
                $status = "-6";
                $update = $this->model_users->update_status($status, $user_id);
                if ($update == true) {
                    $this->session->set_flashdata('success', 'Information has been successfully saved!');
                    if ($this->session->userdata("trade_basic") == "1") {
                        redirect('/register/register_epic_questions/' . $id, 'refresh');
                    } else {
                        redirect('/register/register_questions/' . $id, 'refresh');
                    }
                }
            } else {
                $this->session->set_flashdata('error', 'Error occurred!!');
                redirect('/register/register_epic_project_detail/' . $id, 'refresh');
            }
        } else {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $full_cycle = $this->input->get_post("full_cycle");
                $this->data["user_data"]['full_cycle'] = $full_cycle;
                // print 'if';
                $this->session->set_flashdata('error', 'Error occurred!!');
            } /* else {
                $this->session->set_flashdata('success', "Please complete your Profile first...");
            } */
            $this->data["user_data"]['id'] = $id;
            // print $this->data['location'];
            $this->render_template('/users/register_epic_project_detail', $this->data);
        }
    }
    function register_epic_questions($id)
    {
        $this->not_logged_in(3, $id);
        $user_id = $this->auto_model->get_db_value('users', 'id', $this->atri->de($id));
        $this->load_user_inputs("load_only", $user_id);
        $trade_basic = $this->auto_model->get_db_value("users", "trade_basic", $user_id);
        load_user_extended_info($user_id, $trade_basic);

        // $this->user_detail($user_id, 'register_epic_questions');
        if ($this->data["user_data"]["trade_basic"] <> "1") {
            redirect('/messages/?alert=mishap', 'refresh');
        }
        $this->data['heading1'] = 'Your Experience';
        $this->data['heading2'] = 'Epic Questions';
        $this->data['heading_detail'] = 'Tation argumentum et usu, dicit viderer evertitur te has. Eu dictas concludaturque usu, facete detracto patrioque an per, lucilius pertinacia eu vel.';
        $this->data['subheading1'] = "Welcome " . $this->data["user_data"]['fullname'] . " - " . $this->data["user_data"]["trade_basic_text"];
        $this->data['subheading2'] = "Epic Questionnaire";

        register_validation('add', 'register_epic_questions', $user_id);

        if ($this->form_validation->run() == TRUE) {
            $create = db_operation('add', 'register_epic_questions', $user_id);
            if ($create) {
                $status = "-7";
                $update = $this->model_users->update_status($status, $user_id);
                if ($update == true) {
                    $this->session->set_flashdata('success', 'Information has been successfully saved!');
                    redirect('/register/register_questions/' . $id, 'refresh');
                }
            }
        } else {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $this->data["user_data"]['opt_6'] = $this->input->get_post("opt_6");
                $this->data["user_data"]['work_remotely'] = $this->input->get_post("opt_6");
                $this->data["user_data"]['work_remotely_projects'] = $this->input->get_post("work_remotely_projects");
                $this->data["user_data"]['travel'] = $this->input->get_post("travel");
                print 'error -- ';

                $this->session->set_flashdata('error', 'Error occurred!!');
            } /* else {
                $this->session->set_flashdata('success', "Please complete your Profile first...");
            } */
            $this->data["user_data"]['id'] = $id;
            // print_r ($this->data['user_data']);
            $this->render_template('/users/register_epic_questions', $this->data);
        }
    }
    function register_questions($id)
    {
        $this->not_logged_in(3, $id);
        $user_id = $this->atri->de($id);
        $this->load_user_inputs("load_only", $user_id);
        $trade_basic = $this->auto_model->get_db_value("users", "trade_basic", $user_id);
        load_user_extended_info($user_id, $trade_basic);

        // $this->user_detail($user_id, 'register_questions');
        $this->data['heading1'] = 'Your Experience';
        $this->data['heading2'] = 'General Questions';
        $this->data['heading_detail'] = 'Tation argumentum et usu, dicit viderer evertitur te has. Eu dictas concludaturque usu, facete detracto patrioque an per, lucilius pertinacia eu vel.';
        $this->data['subheading1'] = "Welcome " . $this->data["user_data"]['fullname'] . " - " . $this->data["user_data"]["trade_basic_text"];
        $this->data['subheading2'] = "General Questions";
        $this->data["user_data"]['value_projects'] = $this->input->get_post("value_projects");
        $this->data["user_data"]['good_teammate'] = $this->input->get_post("good_teammate");
        register_validation('add', 'register_questions', $user_id);
        if ($this->form_validation->run() == TRUE) {
            $create = db_operation('add', 'register_questions', $user_id);
            if ($create) {
                $status = "-8";
                $update = $this->model_users->update_status($status, $user_id);
                if ($update == true) {
                    $this->db->where('id', $user_id);
                    $create = $this->db->update('users', array('completed' => 1));
                    // $from_email = get_session("email");
                    // $to_email = get_config_var("admin_email");
                    // $this->email_application($user_id, $to_email, $from_email);
                    $status = $this->model_users->get_status($user_id, "value|$status");
                    $resource = $this->auto_model->get_db_value('users', 'CONCAT_WS(" ", firstname, lastname)', $user_id);
                    $this->app_email["body"] = "Resource: $resource has completed the Profile. The status of this Resource is set as $status";

                    /*  print "admin_email_process(
                        $user_id,
                        'Khick IT - Resource: $resource has completed the Profile',
                        'users',
                        'reg_completed'
                    )"; */
                    $this->admin_email_process(
                        $user_id,
                        "Khick IT - Resource: $resource has completed the Profile",
                        'users',
                        'reg_completed'
                    );
                    // exit;
                    // $group_id = $this->auto_model->get_db_value('users', "group_id", $user_id);
                    // if ($group_id == "3") {
                    $days = get_config_var("app_in_review_days_to_schedule_phone_call");
                    // $this->app_email["body"] = " User: " . $from_email . " your registration process has been completed.";                        
                    $this->app_email["body"] = "Dear $resource,<br><br>Your Profile has been completed. The status of this Resource is set as $status.<br><br>
                    You will be contacted in $days number of days to schedule a phone call to go over all of their details. In the meantime, you are able to log in to the dashboard.<br><br>
                    Site Administrator will be updated by email or phone call for arrangement of an interview with you shortly.";
                    $from_email = get_config_var("admin_email");
                    $to_email =  $this->auto_model->get_db_value('users', 'email', $user_id); // get_session("email");
                    $status = "reg_complete";
                    $subject = "Registration Complete";
                    // print "email_application1: $user_id, $to_email, $from_email, $status)";
                    $this->email_application($user_id, $to_email, $from_email, $status, $subject);
                    // }
                    redirect('/messages/?alert=registration_review', 'refresh');
                    exit();
                }
            }
        } else {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $this->session->set_flashdata('error', 'Error occurred!!');
            } else {
                $this->session->set_flashdata('success', "Please complete your Profile first...");
            }
            $this->data["user_data"]['id'] = $id;
            // print $this->data['location'];
            $this->render_template('/users/register_questions', $this->data);
        }
    }

    function logout()
    {
        $user_data = $this->session->all_userdata();
        foreach ($user_data as $key => $value) {
            if ($key != 'session_id' && $key != 'ip_address' && $key != 'user_agent' && $key != 'last_activity') {
                $this->session->unset_userdata($key);
            }
        }
        $this->session->sess_destroy();
        redirect('/');
    }
}
