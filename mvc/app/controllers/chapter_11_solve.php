<?php
//Chapter Commands
class Chapter_11_Solve extends Controller
{
    private $question_text;
    private $output_file;
    const CHAPTER_ID=11;
    const TEXT_MAX_LEN=500;
    const CODE_MAX_LEN=150;
    const INPUT_MAX_LEN=500;
    public function index()
    {   
        $this->check_login();
        $this->check_chapter_posted(self::CHAPTER_ID);
        $this->get_question();
        $error_msg=$this->session_extract("error_msg",true);
        $exec_msg=$this->session_extract("exec_msg",true);
        $code_field=$this->session_extract("code_field");
        $input_field=$this->session_extract("input_field");
        $chapter_name=$this->get_chapter_name(self::CHAPTER_ID);
        $this->question_text=$this->replace_html_special_characters($this->question_text);
        $this->view('home/chapter_' . (string)self::CHAPTER_ID . '_solve',['chapter_id' => (string)self::CHAPTER_ID,'chapter_name'=>$chapter_name,
                                                                           'question_text' => $this->question_text, 'code_field' =>$code_field,'input_field' =>$input_field, 'code_field_max_len' =>self::CODE_MAX_LEN,
                                                                           'input_field_max_len' =>self::INPUT_MAX_LEN,'error_msg' => $error_msg, 'exec_msg' => $exec_msg]);
    }
    private function reload($data=''){
        $_SESSION["error_msg"]=$data;
        $new_url="../chapter_" . (string)self::CHAPTER_ID . "_solve";
        header('Location: '.$new_url);
        $this->my_sem_release();
        die;
    }
    private function next_question(){
        $chapter_id=self::CHAPTER_ID;
        $config=$this->model('JSONConfig');
        $db_host=$config->get('db','host');
        $db_user=$config->get('db','user');
        $db_pass=$config->get('db','pass');
        $db_name=$config->get('db','name');
        /*check if user is in the chapter_1 users list*/
        $db_connection=$this->model('DBConnection');
        $link=$db_connection->connect($db_host,$db_user,$db_pass,$db_name);
        $sql=$link->prepare('SELECT last_question_id FROM chapter_' . (string)$chapter_id . ' WHERE `user_id`=?');
        $sql->bind_param('i', $this->session_user_id);
        $sql->execute();
        $sql->bind_result($last_question_id);
        $status=$sql->fetch();
        $sql->close();
        $sql=$link->prepare('SELECT COUNT(id) FROM questions WHERE chapter_id=? AND `status`="posted" AND `validation`!="invalid" AND id != ? AND `user_id`!=?');
        $sql->bind_param('iii',$chapter_id,$last_question_id,$this->session_user_id);
        $sql->execute();
        $sql->bind_result($questions_nr);
        $sql->fetch();
        $sql->close();

        if($questions_nr<1){
            die("Could not find a suitable question!");
        }
        
        $sql=$link->prepare('SELECT id FROM questions WHERE chapter_id=? AND `status`="posted" AND `validation`!="invalid" AND id != ? AND `user_id`!=?');
        $sql->bind_param('iii',$chapter_id,$last_question_id,$this->session_user_id);
        $sql->execute();
        $sql->bind_result($question_id);    
        for($i=1;$i<=rand(1,$questions_nr);$i++){
            $sql->fetch();
        }
        $sql->close();
        
        $sql=$link->prepare('UPDATE chapter_' . (string)$chapter_id . ' SET last_question_id=? WHERE `user_id`=?');        
        $sql->bind_param('ii',$question_id,$this->session_user_id);
        $sql->execute();
        $sql->close();
        $db_connection->close();
    }
    
    private function get_question(){
        $chapter_id=self::CHAPTER_ID;
        $config=$this->model('JSONConfig');
        $db_host=$config->get('db','host');
        $db_user=$config->get('db','user');
        $db_pass=$config->get('db','pass');
        $db_name=$config->get('db','name');
        /*check if user is in the chapter_1 users list*/
        $db_connection=$this->model('DBConnection');
        $link=$db_connection->connect($db_host,$db_user,$db_pass,$db_name);
        $sql=$link->prepare('SELECT last_question_id FROM chapter_'. (string)$chapter_id .' WHERE `user_id`=?');
        $sql->bind_param('i', $this->session_user_id);
        $sql->execute();
        $sql->bind_result($last_question_id);
        $status=$sql->fetch();
        $sql->close();
        
        if(!$status){/*insert user into chapter_1 table*/
            $sql=$link->prepare('SELECT id FROM questions WHERE chapter_id=? AND `status`="posted" AND `validation`!="invalid"');
            $sql->bind_param('i',$chapter_id);
            $sql->execute();
            $sql->bind_result($last_question_id);
            $sql->fetch();
            $sql->close();
            $sql=$link->prepare('INSERT INTO chapter_' . (string)$chapter_id . ' (`user_id`,right_answers,last_question_id) VALUES (?,?,?)');
            $right_answers=0;
            $sql->bind_param('sii', $this->session_user_id,$right_answers,$last_question_id);
            $sql->execute();
            $sql->close();
        }/*increment right_answers for user*/
        
        /*check if question is still available*/
        $sql=$link->prepare('SELECT user_id FROM questions WHERE chapter_id=? AND `status`="posted" AND id=? AND `validation`!="invalid"');
        $sql->bind_param('ii',$chapter_id, $last_question_id);
        $sql->execute();
        $sql->bind_result($aux_res);
        if(!$sql->fetch()){/*in case the question is not available*/
            $this->next_question();
            $sql_1=$link->prepare('SELECT id FROM questions WHERE chapter_id=? AND `status`="posted" AND `validation`!="invalid"');
            $sql->bind_param('i',$chapter_id);
            $sql_1->execute();
            $sql_1->bind_result($last_question_id);
            $sql_1->fetch();
            $sql_1->close();
        }
        $sql->close();
        $db_connection->close();
        $config=$this->model('JSONConfig');
        $app_local_path=$config->get('app','local_path');
        $text_file=fopen($app_local_path . '/mvc/app/questions/' . $last_question_id . '.text','r');
        $this->question_text=fread($text_file,self::TEXT_MAX_LEN);
        fclose($text_file);
    }
    private function correct_answer(){ /*add question_id*/
        $chapter_id=self::CHAPTER_ID;
        $config=$this->model('JSONConfig');
        $db_host=$config->get('db','host');
        $db_user=$config->get('db','user');
        $db_pass=$config->get('db','pass');
        $db_name=$config->get('db','name');
        
        $db_connection=$this->model('DBConnection');
        $link=$db_connection->connect($db_host,$db_user,$db_pass,$db_name);
        $sql=$link->prepare('SELECT right_answers FROM chapter_' . (string)$chapter_id . ' WHERE `user_id`=?');
        $sql->bind_param('i', $this->session_user_id);
        $sql->execute();
        $sql->bind_result($right_answers);
        $sql->fetch();
        $sql->close();
        /*increment right_answers for user*/
        $sql=$link->prepare('UPDATE chapter_' . (string)$chapter_id . ' SET right_answers=? WHERE `user_id`=?');        
        $right_answers=$right_answers+1;
        $sql->bind_param('ii',$right_answers,$this->session_user_id);
        $sql->execute();
        $sql->close();
        $db_connection->close();
    }
    private function execute($code,$input="",$combine_outputs=false){//the $combine_outputs argument adds output file contents in the exec_msg
        $config=$this->model('JSONConfig');
        $ssh_host=$config->get('ssh','host');
        $ssh_port=$config->get('ssh','port');
        $ssh_user=$config->get('ssh','user');
        $ssh_pass=$config->get('ssh','pass');
        $ssh_timeout_seconds=$config->get('ssh','timeout_seconds');
        $ssh_connection=$this->model('SSHConnection');
        $ssh_connection->configure($ssh_host,$ssh_port);
        try{
            if(!$ssh_connection->connect($ssh_user,$ssh_pass)){
                $ssh_connection->close();
                $this->reload("Could not access Linux machine!");
            }
        }catch(Exception $e){
            $this->reload($e->getMessage());
        }
        $app_local_path=$config->get('app','local_path');
        $code_file=fopen($app_local_path . '/mvc/app/scp_cache/' . $this->session_user . '.code','w');
        fwrite($code_file,$code);
        fclose($code_file);
        $run_file=fopen($app_local_path . '/mvc/app/scp_cache/' . $this->session_user . '.run','w');
        fwrite($run_file,"chmod +x code.sh && ./code.sh ");
        fclose($run_file); 
        $input_file=fopen($app_local_path . '/mvc/app/scp_cache/' . $this->session_user . '.input','w');
        fwrite($input_file,$input);
        fclose($input_file);
        try{
            $ssh_connection->send_code_file($app_local_path . '/mvc/app/scp_cache/' . $this->session_user . '.code', $this->session_user . '.sh');
            $ssh_connection->send_code_file($app_local_path . '/mvc/app/scp_cache/' . $this->session_user . '.run', $this->session_user . '.run');
            $ssh_connection->send_code_file($app_local_path . '/mvc/app/scp_cache/' . $this->session_user . '.input', $this->session_user . '.input');
            $docker_command="docker run --name " . $this->session_user . " -v $(pwd)/" . $this->session_user . ".sh:/code.sh -v $(pwd)/" . 
                                                    $this->session_user . ".input:/code.input -v $(pwd)/" . 
                                                   $this->session_user . ".output:/code.output -v $(pwd)/" . $this->session_user . ".run:/code.run:ro --rm my_ubuntu bash ./code.run";
            /*creating the output file which will be mounted in the container*/
            $ssh_connection->execute("echo>" . $this->session_user . ".output",true);
            $_SESSION["output_file"]=0;
            $_SESSION["exec_msg"]=$ssh_connection->execute("timeout --signal=SIGKILL " . $ssh_timeout_seconds . " " . $docker_command);
        }catch(Exception $e){
            $ssh_connection->close();
            $this->reload($e->getMessage());
        }
        if(empty($_SESSION["exec_msg"])==true){
            $_SESSION["output_file"]=$ssh_connection->read_file($this->session_user . ".output");//only if the standard output is empty should we read the output file
            if(empty($_SESSION["output_file"])==true || ord($_SESSION["exec_msg"][0])==10){
                $ssh_connection->close();
                $this->reload("Output cannot be empty!");
            }else{
                if($combine_outputs==true){
                    $_SESSION["exec_msg"]=$_SESSION["output_file"];
                }
            }
        }
        $_SESSION["output_file"]=$ssh_connection->read_file($this->session_user . ".output");//only if the standard output is empty should we read the output file
        $ssh_connection->close();
    }
    private function submit($code,$skip=false){
        $chapter_id=self::CHAPTER_ID;
        $config=$this->model('JSONConfig');
        $db_host=$config->get('db','host');
        $db_user=$config->get('db','user');
        $db_pass=$config->get('db','pass');
        $db_name=$config->get('db','name');
        /*check if user is in the chapter_1 users list*/
        $db_connection=$this->model('DBConnection');
        $link=$db_connection->connect($db_host,$db_user,$db_pass,$db_name);
        $sql=$link->prepare('SELECT last_question_id FROM chapter_' . (string)$chapter_id . ' WHERE `user_id`=?');
        $sql->bind_param('i', $this->session_user_id);
        $sql->execute();
        $sql->bind_result($last_question_id);
        $status=$sql->fetch();
        $sql->close();
        $sql=$link->prepare('SELECT all_answers,right_answers FROM questions WHERE `id`=?');
        $sql->bind_param('i', $last_question_id);
        $sql->execute();
        $sql->bind_result($all_answers,$right_answers);
        $sql->fetch();
        $sql->close();
        if($skip==false){
            $config=$this->model('JSONConfig');
            $app_local_path=$config->get('app','local_path');
            $author_code=null;
            
            $code_file=fopen($app_local_path . '/mvc/app/questions/' . $last_question_id . '.code','r');
            $author_code=fread($code_file,self::CODE_MAX_LEN);
            fclose($code_file);
            $input_file=fopen($app_local_path . '/mvc/app/questions/' . $last_question_id . '.input','r');
            $author_input=fread($input_file,self::INPUT_MAX_LEN);
            fclose($input_file);
        
            $this->execute($author_code,$author_input);
            $aux_output=$_SESSION["exec_msg"];
            $aux_output_file=$_SESSION["output_file"];
            $_SESSION["exec_msg"]=$_SESSION["output_file"]="";
            
            $this->execute($code,$author_input);           
            if(empty($aux_output)==false){
                if(strcmp($aux_output,$_SESSION["exec_msg"])==0){//|| strcmp($author_code,$code)==0
                    $this->correct_answer();
                    $right_answers=$right_answers+1;
                    $_SESSION['result_correct']="You answerd correctly!";
                }else{
                    $_SESSION['result_incorrect']="You answerd incorrectly!";
                }    
            }else{
                if(strcmp($aux_output_file,$_SESSION["output_file"])==0){// || strcmp($author_code,$code)==0
                    $this->correct_answer();
                    $right_answers=$right_answers+1;
                    $_SESSION['result_correct']="You answerd correctly!";
                }else{
                    $_SESSION['result_incorrect']="You answerd incorrectly!";
                }
            }
        }
        /*increment answers for question*/
        $sql=$link->prepare('UPDATE questions SET all_answers=?,right_answers=? WHERE `id`=?');        
        $all_answers=$all_answers+1;
        $sql->bind_param('iii',$all_answers,$right_answers,$last_question_id);
        $sql->execute();
        $sql->close();
        $db_connection->close();
        
        if($skip==false){/*prepare info for result*/
            $this->get_question();
            $_SESSION['question_id']=$last_question_id;
            $_SESSION['question_text']=$this->question_text;
            $_SESSION['question_input']=$author_input;
            $_SESSION['user_code']=$code;
            $_SESSION['author_code']=$author_code;
            if(empty($aux_output)==false){
                $_SESSION['author_output']=$aux_output;
                $_SESSION['user_output']=$_SESSION["exec_msg"];
            }else{
                $_SESSION['author_output']=$aux_output_file;
                $_SESSION['user_output']=$_SESSION["output_file"];
            }
        }
        $this->next_question();  
    }
    public function process(){
        $chapter_id=self::CHAPTER_ID;
        $this->check_login();
        $this->check_chapter_posted(self::CHAPTER_ID);
        $this->my_sem_acquire($this->session_user_id);
        if(strlen($_POST["code_field"])>self::CODE_MAX_LEN){
            $this->reload("Characters limit exceeded for code!");
        }
        if(strlen($_POST["input_field"])>self::INPUT_MAX_LEN){
            $this->reload("Characters limit exceeded for input file!");
        }
        if($_POST["action"]!="Skip" && empty($_POST["code_field"])==true){
            $this->reload("You did not enter any code!");
        }
        $code=$_SESSION["code_field"]=$_POST["code_field"];
        $input=$_SESSION["input_field"]=$_POST["input_field"];
        $code=str_replace("\r","",$code);//Converting DOS line end to Linux version
        $input=str_replace("\r","",$input);//Converting DOS line end to Linux version
        if(strstr($_POST["code_field"],"\n")==true){
            $this->reload("New line not permitted!");
        }
        if($_POST["action"]=="Execute"){
            $this->execute($code,$input,true);
            header('Location: ../chapter_' . (string)$chapter_id . '_solve'); 
        }else if($_POST["action"]=="Submit"){
            $this->submit($code);
            $this->session_extract("code_field",true);
            $this->session_extract("input_field",true);
            $this->session_extract("text_field",true);
            $this->session_extract("error_msg",true);
            $this->session_extract("exec_msg",true);
            header('Location: ../chapter_' . (string)$chapter_id . '_result');       
        }else{/*skip*/
            sleep(4);//delaying the skipping process
            $this->submit("",true);
            $this->session_extract("code_field",true);
            $this->session_extract("input_field",true);
            $this->session_extract("text_field",true);
            $this->session_extract("error_msg",true);
            $this->session_extract("exec_msg",true);
            header('Location: ../chapter_' . (string)$chapter_id . '_solve');  
        }
        $this->my_sem_release();
    }
}