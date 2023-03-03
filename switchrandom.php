<?php
    use Chassit\Repeat\History;

    require_once "init.php";

   if($_SERVER["REQUEST_METHOD"] == "GET"){
    $video = History::getRandomVideo($_GET['nsfc'] == true);
    header("Content-Type: application/json", true);
    echo json_encode($video);
   }
   else {
    http_response_code(400);
    echo "Unsupported";
}

?>
