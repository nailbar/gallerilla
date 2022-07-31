<?php
header("Content-Type: application/json");
manageCors();
require_once "config.php";
list($a, $b, $c) = parseGet();
$authenticatedUser = authenticate();

switch($a) {
  case "test": makeResp("Test OK");
  case "meta":
    if($_SERVER["REQUEST_METHOD"] === "POST") {
      validateUser(); // Valid user require for write access
      makeResp(saveMeta($b, json_decode(file_get_contents("php://input"), true)));
    } else {
      makeResp(getMeta($b));
    }
  case "gallery":
    if($b) {
      getPhoto($b, $c);
    } else {
      validateUser(); // Listing photos requires valid user
      makeResp(getPhotos());
    }
  case "me":
    validateUser(); // Requires valid user
    makeResp(getUser());
  default: makeError("Unknown action", empty($a) ? "NO ACTION PROVIDED" : $a);
}

function saveMeta($id, $data) {
  global $config, $authenticatedUser;
  $metaPath = "{$config["metaDir"]}{$id}.json";
  if(file_exists($metaPath)) {
    $meta = json_decode(file_get_contents($metaPath), true);
  } else {
    $meta = [];
  }
  $meta[] = [ "created" => date("c"), "createdBy" => $authenticatedUser["id"], "meta" => $data["meta"]];
  $json = json_encode($meta);
  if($json) {
    file_put_contents($metaPath, $json);
    makeResp("Meta entry saved");
  } else {
    makeError("Could not generate JSON for meta entry", $meta);
  }
}

function getMeta($id) {
  global $config;
  $metaPath = "{$config["metaDir"]}{$id}.json";

  foreach(getPhotos() as $photo) {
    if($photo["id"] === $id) {
      return $photo;
    }
  }

  makeError("getMeta: Invalid ID", $id);
}

function manageCors() {
  header("Access-Control-Allow-Origin: *");
  header("Access-Control-Allow-Headers: *");

  // Access-Control headers are received during OPTIONS requests
  if($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    if(isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_METHOD"])) {
      header("Access-Control-Allow-Methods: GET, POST, OPTIONS");         
    }
    if(isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_HEADERS"])) {
      header("Access-Control-Allow-Headers: {$_SERVER["HTTP_ACCESS_CONTROL_REQUEST_HEADERS"]}");
    }
    die();
  }
}

function authenticate() {
  global $config;
  $token = getAuthorizationHeader();
  foreach(json_decode(file_get_contents($config["userFile"]), true) as $user) {
    if("Bearer {$user["token"]}" === $token) {
      return $user;
    }
  }
  return [
    "id" => 0,
    "name" => "Guest",
    "token" => ""
  ];
}

function validateUser() {
  global $authenticatedUser;
  if(empty($authenticatedUser["id"])) {
    makeError("Access denied");
  }
}

function getUser() {
  global $authenticatedUser;
  return $authenticatedUser["name"];
}

function getPhoto($id, $size) {
  global $config;
  foreach(getPhotos(true) as $photo) {
    if($photo["id"] === $id) {
      
      header("Content-Disposition: inline; filename={$photo["filename"]}");

      // Attempt to generate a thumbnail
      if($size) {
        getThumbnail($id, $size, $photo["path"], $photo["type"]);
      }

      // Fallback to just the raw image
      $modified = filemtime($photo["path"]);
      header("Last-Modified: " . gmdate("D, d M Y H:i:s", $modified) . " GMT");
      header("Content-Type: {$photo["type"]}");
      handleCached($modified);
      echo file_get_contents($photo["path"]);
      die();
    }
  }
  makeError("Unknown photo", $id);
}

function getThumbnail($id, $size, $path, $type) {
  global $config;
  $thumbPath = "{$config["metaDir"]}$id.thumb.$size";
  if(!file_exists($thumbPath)) {
    switch($type) {
      case "image/jpeg": $img = imagecreatefromjpeg($path); break;
      case "image/png": $img = imagecreatefrompng($path); break;
      case "image/gif": $img = imagecreatefromgif($path); break;
      default: return;
    }

    $width = imagesx($img);
    if($width <= $size) {
      return;
    }
    $height = imagesy($img);
    $thumbHeight = ceil($height / $width * $size);

    $thumb = imagecreatetruecolor($size, $thumbHeight);
    imagecopyresampled($thumb, $img, 0, 0, 0, 0, $size, $thumbHeight, $width, $height);
    imagejpeg($thumb, $thumbPath);
  }

  $modified = filemtime($thumbPath);
  header("Last-Modified: " . gmdate("D, d M Y H:i:s", $modified) . " GMT");
  header("Content-Type: image/jpeg");
  handleCached($modified);
  die(file_get_contents($thumbPath));
}

function handleCached($lastModified) {
  if(!empty($_SERVER["HTTP_IF_MODIFIED_SINCE"])) {
    if(strtotime($_SERVER["HTTP_IF_MODIFIED_SINCE"]) >= $lastModified) {
      header("HTTP/1.0 304 Not Modified");
      die();
    }
  }
}

function parseGet() {
  $get = explode("/", empty($_GET["a"]) ? "" : $_GET["a"]);
  return [
    $get[0],
    empty($get[1]) ? "" : $get[1],
    empty($get[2]) ? "" : $get[2]
  ];
}

function getPhotos($fullPath = false) {
  global $config;
  $data = [];
  foreach(glob($config["photoDir"] . "*") as $path) {
    if($photo = parsePhoto($path, $fullPath)) {
      $data[] = $photo;
    }
  }
  return $data;
}

function parsePhoto($path, $fullPath = false) {
  global $config;
  $filename = basename($path);
  $nameParts = explode(".", $filename);
  if(count($nameParts) <= 1) {
    return false;
  }
  switch(strtolower($nameParts[count($nameParts) - 1])) {
    case "gif": $fileType = "image/gif"; break;
    case "png": $fileType = "image/png"; break;
    case "jpg": $fileType = "image/jpeg"; break;
    case "jpeg": $fileType = "image/jpeg"; break;
    default: return false;
  }
  unset($nameParts[count($nameParts) - 1]);
  $name = ucfirst(implode(".", $nameParts));
  $return = [
    "id" => md5($filename . "skalbaggssekret"),
    "filename" => $filename,
    "name" => $name,
    "meta" => "",
    "type" => $fileType
  ];
  if($fullPath) {
    $return["path"] = $path;
  }
  $metaPath = "{$config["metaDir"]}{$return["id"]}.json";
  if(file_exists($metaPath)) {
    $meta = json_decode(file_get_contents($metaPath), true);
    $return["meta"] = $meta[count($meta) - 1]["meta"];
  }
  return $return;
}

function makeResp($data) {
  die(json_encode([
    "status" => "OK",
    "data" => $data
  ]));
}

function makeError($desc = "Unknown error", $data = []) {
  die(json_encode([
    "status" => "ERROR",
    "description" => $desc,
    "data" => $data
  ]));
}

/** 
 * Get header Authorization
 * 
 * From https://stackoverflow.com/questions/40582161/how-to-properly-use-bearer-tokens
 * 
 * @return string
 */
function getAuthorizationHeader() {
  $headers = null;
  if(isset($_SERVER["Authorization"])) {
    $headers = trim($_SERVER["Authorization"]);
  } elseif(isset($_SERVER["HTTP_AUTHORIZATION"])) {
    $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
  } elseif(function_exists("apache_request_headers")) {
    $requestHeaders = apache_request_headers();
    $requestHeaders = array_combine(array_map("ucwords", array_keys($requestHeaders)), array_values($requestHeaders));
    if(isset($requestHeaders["Authorization"])) {
      $headers = trim($requestHeaders["Authorization"]);
    }
  }
  return $headers;
}

/**
 * Get access token from header
 * 
 * From https://stackoverflow.com/questions/40582161/how-to-properly-use-bearer-tokens
 * 
 * @return string
 */
function getBearerToken() {
  $headers = getAuthorizationHeader();
  if (!empty($headers)) {
    if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
      return $matches[1];
    }
  }
  return null;
}