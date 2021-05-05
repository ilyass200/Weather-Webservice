<?php

require_once realpath(__DIR__."/vendor/autoload.php");

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Load variables from the .env file
$weatherApiKey = $_ENV['WEATHER_API_KEY'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASSWORD'];
$port = $_ENV['DB_PORT'];

$zip_code = $_GET['zipcode'];

// Connect to DB
$db = new \PDO('mysql::host=localhost;port='.$port.';dbname=weather',$user,$pass,array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));

// GET THE LAST WEATHER SAVED TODAY FROM THE ZIP CODE 
$req = $db->prepare('SELECT * FROM weather WHERE zipcode = ? AND DATE(date_added) = CURDATE() ORDER BY ID DESC LIMIT 1');
$req->execute(array($zip_code));

$result = $req->fetch();
$count_result = $req->rowCount();
$dbtime = $result['date_added'];
$dbtimestamp = strtotime($dbtime);

// CHECK IF A REQUEST WAS MADE WITH THE ZIP CODE MORE THAN 15 MINUTES AGO
if (((time() - $dbtimestamp)) > 900) {

    // CALL API 
    $url = "http://api.openweathermap.org/data/2.5/weather?zip=".$zip_code.",fr&appid=".$weatherApiKey;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $resp = curl_exec($ch);

    // CHECK IF THERE IS A PROBLEM IN THE URL
    if($e = curl_error($ch))
    {
        echo $e;
        return;
    }
    else
    {
        $resp = json_decode($resp);
        $temp = $resp->main;
        $temp_celsius =  number_format($temp->temp - 273.15, 2, '.', '');
        $temp_min_celsius = number_format($temp->temp_min - 273.15, 2, '.', '');
        $temp_max_celsius = number_format($temp->temp_max - 273.15, 2, '.', '');
        $temp_weather = $resp->weather[0];


        // CHECK STATUS CODE
        if($resp->cod !== 200)
        {
            $resp;
            print_r($resp);
            return;
        }
        else
        {
            $resp = json_encode(array("current_temperature"=> $temp_celsius,"minimum_temperature" => $temp_min_celsius,"maximum_temperature" => $temp_max_celsius, 'weather' => $temp_weather->description));
            $data = array($zip_code,$temp_celsius,$temp_min_celsius,$temp_max_celsius,$temp_weather->description);

            // INSERT DATA TO DATABASE
            try 
            {
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
                $req = $db->prepare("INSERT INTO weather(zipcode,curr_temp,temp_min,temp_max,weather,date_added) VALUES(?,?,?,?,?,CURRENT_TIMESTAMP)");
                $req->execute(array($zip_code,$temp_celsius,$temp_min_celsius,$temp_max_celsius,$temp_weather->description));
        
        } catch (PDOException $e) {
                throw $e;
        }

        print_r($resp);
        return;
        }

    }

}

$resp = json_encode(array("current_temperature"=> $result['curr_temp'],"minimum_temperature" => $temp_min_celsius,"maximum_temperature" => $result['temp_max'], 'weather' => $result['weather']));
print_r($resp);

?>