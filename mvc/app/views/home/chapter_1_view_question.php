﻿<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Chapter: Commands</title>
    <link rel="stylesheet" href="resources/stylesheets/header.css" type="text/css" />
    <link rel="stylesheet" href="resources/stylesheets/chapter_1_view_question.css" type="text/css" />
</head>
<body>
    <?php
        include "header.php"
    ?>
    <div class='questionBox' >
        <p class='questionText'><?php echo  $data["question_text"] ;?> </p>
        <div class='questionDetailsBox'>
            <p class='questionDetails'><?php echo  "Times answered: " . $data["right_answers"] . " / " . $data["all_answers"] ;?> </p>
            <p class='questionDetails'><?php echo "Validation: " . $data["validation"]; ?> </p>
            <p class='questionDetails'><?php echo "Date submitted: " . $data["date_submitted"]; ?> </p>
        </div>
    
        <form class='deleteQuestion' action="chapter_1_view_question/delete_question">
            <?php 
                if($data['can_delete']==false){
                    echo "<input class='btnDeleteGray' type='submit' value='Delete' disabled/><br><p class='cannotDeleteMessage'>Answer " . $data['answers_left'] . " more questions!<p>";        
                }else{
                    echo "<input class='btnDelete' type='submit' value='Delete'/>";        
                }
            ?>
            
        </form>
        <h2>Reports</h2>
        <div class='reportsBox'>
            <p class='reportText'><?php echo  $data["question_text"] ;?> </p>
            <p class='reportDetails'><?php echo  "Date submitted: " . $data["right_answers"] . " / " . $data["all_answers"] ;?> </p>
        </div>
    </div>
</body>
</html>

