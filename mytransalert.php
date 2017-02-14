<?php

error_reporting(E_ALL);
ini_set("display_errors", "1");
$fn = $_POST['function'];
define('API_KEY', 'f0940355-aec5-41e2-a140-2dd9490a05f5');
define('API_BASE_URL', 'http://api.erail.in/');
define('DB_SERVER', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DATABASE', 'irctc');

switch ($fn) {
    case 'stations' :
        stations();
        break;
    case 'route' :
        route();
        break;
    case 'trains' :
        trains();
        break;
    default :
        echo json_encode(array('status' => false, 'msg' => 'invalid request'));
        break;
}

function stations(){
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASS, DATABASE);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
        exit;
    }
    $check = 'SELECT COUNT(id) AS cnt FROM all_stations';
    $result = $conn->query($check);
    if ($result->num_rows > 0) {
        $rr = $result->fetch_assoc();
        if ($rr['cnt'] == 0) {
            $data = file_get_contents(API_BASE_URL . 'stations/?key=' . API_KEY);
            $stations = json_decode($data, true);
            foreach ($stations as $station) {
                $sql[] = '("' . $conn->real_escape_string($station['name']) . '","' . $conn->real_escape_string($station['code']) . '")';
            }
            $conn->query('INSERT INTO all_stations (name,code) VALUES ' . implode(',', $sql));
        }
    }
    $select = 'SELECT * FROM all_stations';
    $return = $conn->query($select);
    foreach ($return as $row1) {
        $data1[] = $row1;
    }
    if ($return->num_rows > 0) {
        echo json_encode(array('status' => true, 'data' => $data1));
    } else {
        echo json_encode(array('status' => false, 'msg' => 'Network Error...!!!'));
    }
    exit;
}

function trains() {
    $conn = new mysqli(DB_SERVER, DB_USER, DB_PASS, DATABASE);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $from = $_POST['from'];
    $to = $_POST['to'];
    $date = $_POST['date'];
    if ($from == '' || $to == '' || $date == '') {
        echo json_encode(array('status' => false, 'msg' => 'All fields are required'));
        exit;
    } else {
        $select = 'SELECT * FROM trains WHERE `from`="' . $from . '" AND `to`="' . $to . '"';
        $return = $conn->query($select);
        if ($return->num_rows > 0) {
            foreach ($return as $row1) {
                $data1[] = $row1;
            }
            echo json_encode(array('status' => true, 'data' => $data1));
            exit;
        } else {
            $response = file_get_contents(API_BASE_URL . 'trains/?key=' . API_KEY . '&stnfrom=' . $from . '&stnto=' . $to . '&date=' . $date);
            $response = json_decode($response, true);
            if ($response['status'] == 'OK') {
                foreach ($response['result'] as $train){
                    $sql[] = '("'.$train['trainno'].'","'.$train['name'].'","'.$train['cls'].'","'.$train['rundays'].'","'.$train['from'].'","'.$train['fromname'].'","'.$train['dep'].'","'.$train['to'].'","'.$train['toname'].'","'.$train['arr'].'","'.$train['pantry'].'","'.$train['type'].'","'.$train['datefrom'].'","'.$train['dateto'].'","'.$train['traveltime'].'")';
                }                
                $insert_sql = 'INSERT INTO `trains`(`trainno`, `name`, `cls`, `rundays`, `from`, `fromname`, `dep`, `to`, `toname`, `arr`, `pantry`, `type`, `datefrom`, `dateto`, `traveltime`) VALUES ' . implode(',', $sql);
                $conn->query($insert_sql);
            }
            
            $select = 'SELECT * FROM trains WHERE `from`="' . $from . '" AND `to`="' . $to . '"';
            $return = $conn->query($select);
            if ($return->num_rows > 0) {
                foreach ($return as $row1) {
                    $data1[] = $row1;
                }
                echo json_encode(array('status' => true, 'data' => $data1));                
            }else{
                echo json_encode(array('status' => false, 'msg' => 'Network Error...!!!'));
            }
            exit;
        }
    }
}

function route() {
    $train_no = $_POST['trainno'];
    if($train_no == ''){
        echo json_encode(array('status' => false, 'msg' => 'Train number is required...!!!'));
        exit;
    }else{        
        //$data = file_get_contents(API_BASE_URL.'fullroute/?key='.API_KEY.'&trainno='.$train_no);        
        $response = file_get_contents(API_BASE_URL.'fullroute/?key='.API_KEY.'&trainno='.$train_no);
        
        $response = str_replace("'",'"',str_replace("'code':",'"code":"',str_replace(",'name",'","name',str_replace("','",'","',str_replace("':'",'":"',$response)))));
        $response = json_decode($response, true);        
        echo json_encode(array('status' => false, 'data' => $response));
        exit;
    }
}

function make_curl_request($url, $post) {
    $data_json = json_encode($post);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data_json)));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function pr($arr) {
    echo '<pre>';
    print_r($arr);
    echo '</pre>';
}



?>