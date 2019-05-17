<?php
include_once("config.php");
include_once("dbconnection.php");
include_once("./models/teams.php");
include_once("./models/calendar.php");

header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, PATCH, OPTIONS');
header('Access-Control-Max-Age: 1000');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

$db = (new DBConnection($db_config))->getConnection();
$path = array();
preg_match_all('/\/([a-z]|[0-9]|[A-Z])+/', $_SERVER["REQUEST_URI"], $path);

if (sizeof($path) < 1) {
  $err = array(
    "error" => array(
      "status" => "404",
      "message" => "Bad URL"
    )
  );

  echo json_encode($err);
  exit();
}

$teams = new Teams($db);
$calendar = new Calendar($teams->getNames());
$method = $_SERVER["REQUEST_METHOD"];
$uri = $path[0];
$resource = $uri[0];

if ($resource == '/teams') {
  if ($method == 'GET') {
    $teams_data = $teams->getAll();
    echo json_encode($teams_data);
  }
  else if ($method == 'POST') {
    $json = file_get_contents('php://input'); // Returns data from the request body
    $team_data = json_decode($json, true);
    
    try {
      $team_names = $teams->getNames();
      if (in_array($team_data["name"], $team_names)) {
        $err = array(
          "error" => array(
            "status" => "500",
            "message" => "A team already exists with that name."
          )
        );
      
        echo json_encode($err);
        exit();
      }
      else {
        $qr = $teams->create($team_data["name"], $team_data["logo"]);
        echo '{"success":"Team ' . $team_data["name"] . ' created successfully!"}';
      }
    }
    catch(Exception $e) {
      $err = array(
        "error" => array(
          "status" => "500",
          "message" => "There was a problem reading the database."
        )
      );
    
      echo json_encode($err);
      exit();
    }
  }
  else if ($method == 'DELETE') {
    $json = file_get_contents('php://input'); // Returns data from the request body
    $team_data = json_decode($json, true);

    $qr = $teams->delete($team_data["id"]);
    if ($qr) {
      echo '{"success":"Deleted team ' . $team_data["id"] . ' successfully."}';
    }
    else {
      $err = array(
        "error" => array(
          "status" => "404",
          "message" => "Team with id " . $team_data["id"] . " not found."
        )
      );
  
      echo json_encode($err);
      exit();
    }
  }
  else if ($method == 'PATCH') {
    $json = file_get_contents('php://input'); // Returns data from the request body
    $team_data = json_decode($json, true);

    $qr = $teams->edit($team_data["id"], $team_data["name"], $team_data["logo"]);
    if ($qr) {
      echo '{"success":"Updated team successfully."}';
    }
    else {
      $err = array(
        "error" => array(
          "status" => "404",
          "message" => "Team with id " . $team_data["id"] . " not found."
        )
      );
  
      echo json_encode($err);
      exit();
    }
  }
  else {
    $err = array(
      "error" => array(
        "status" => "404",
        "message" => "Bad URL. Wrong method for resource '" . $resource . "'."
      )
    );
  
    echo json_encode($err);
    exit();
  }
}
else if ($resource == '/calendar') {
  if ($method == 'GET') {
    echo json_encode($calendar->getCalendar());
  }
  else if ($method == 'POST') {
    $json = file_get_contents('php://input'); // Returns data from the request body
    $team_data = json_decode($json, true);

    // TODO: test this endpoint
    $calendar->setTeams($team_data);
  }
  else {
    $err = array(
      "error" => array(
        "status" => "404",
        "message" => "Bad URL. Wrong method for resource '" . $resource . "'."
      )
    );

    echo json_encode($err);
    exit();
  }
}
else {
  $err = array(
    "error" => array(
      "status" => "404",
      "message" => "Bad URL. Resource '" . $resource . "' not found."
    )
  );

  echo json_encode($err);
  exit();
}

?>

