<?php
class Submit_Question extends Controller
{
    public function index()
    {
        $this->check_login();
        $this->view('home/chapters_submit',['error_msg' => $error_msg]);
    }
    private function reload($data=''){
        $_SESSION["error_msg"]=$data;
        $new_url="../chapters";
        header('Location: '.$new_url);
        die;
    }
    public function process(){
       
    }
}