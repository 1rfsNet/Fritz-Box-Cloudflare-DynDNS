<?php
/**
Author: 1rfsNet
Version: 1.0
Created: 26.04.2020
License: GNU General Public License v3.0

Description:
This simple PHP script replaces a DynDNS provider and passes your current IP address to Cloudflare.

Required parameters: 
- Cloudflare api key (as password) - required
- domain(s) (as domain) -> use multiple domains by separating by semicolon (;) - required
- log (true/false) - optional (default: false)
- proxy (true/false) - optional (default: false)
Fritz!Box update URL: https://example.com/fritz_dyndns.php?cf_key=<pass>&domain=<domain>&log=  <true/false>&proxy=<true/false>
*/

wlog("INFO","===== Starting Script =====");
if(!isset($_GET["cf_key"]) || empty($_GET["cf_key"]) || !isset($_GET["domain"]) || empty($_GET["domain"])) { wlog("ERROR","Parameter(s) missing or invalid"); wlog("INFO","Script aborted"); die; }

if(isset($_GET["proxy"]) && $_GET["proxy"]) $proxy = TRUE;
else $proxy = FALSE;

$header = getallheaders();
if(array_key_exists("CF-Connecting-IP", $header)) { 
    wlog("INFO", "Server is proxied by Cloudflare (IP: ".$_SERVER['REMOTE_ADDR']."). Adapting ip to ".$header["CF-Connecting-IP"].".");
    $ip = $header["CF-Connecting-IP"];
} else {
    wlog("INFO", "Server is not proxied by Cloudflare. Taking direct ip: ".$_SERVER['REMOTE_ADDR'].".");
    $ip = $_SERVER['REMOTE_ADDR'];   
}

$auth = cf_curl("zones");
if(!$auth["success"]) { wlog("ERROR","Authentication failed"); wlog("INFO","Script aborted"); die; } else wlog("INFO","Authentication successful");

wlog("INFO","Found records to set: ".$_GET["domain"]);
$domains = explode(";", $_GET["domain"]);

foreach($domains as $domain) {
    wlog("INFO","Find zone for record '".$domain."'");
    $domain_explode = explode(".", $domain);
    $domain_explode = array_reverse($domain_explode);
    $zone = $domain_explode[1].".".$domain_explode[0];
    $response = cf_curl("zones?name=".$zone."&status=active");
    if(!$response["success"]) { wlog("ERROR","Could not set record '".$domain."', because the script could not determine the zone id (check api permissions and resources). Continue with next domain, if available."); continue; } else wlog("INFO","Found zone id (".$response["result"][0]["id"].") for '".$domain."'.");
    $zone = $response["result"][0]["id"];
    $response = cf_curl("zones/".$zone."/dns_records?name=".$domain);
    $response = $response["result"];
    if(!$response) {
        wlog("INFO","Could not find record, creating new record.");
        if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) == true) {
            $response = cf_curl("zones/".$zone."/dns_records", array("type" => "A", "name" => $domain, "content" => $ip, "ttl" => 120, "proxied" => $proxy ));
            if(!$response["success"]) {
                wlog("ERROR","Could not create record for '".$domain."'. Continue with next record, if available.");
                continue;
            } else wlog("INFO","Created new A-Record for '".$domain."' with ip '".$ip."' successfully.");
        } else if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) == true) {
            $response = cf_curl("zones/".$zone."/dns_records", array("type" => "AAAA", "name" => $domain, "content" => $ip, "ttl" => 120, "proxied" => $proxy ));
            if(!$response["success"]) {
                wlog("ERROR","Could not create record for '".$domain."'. Continue with next record, if available.");
                continue;
            } else wlog("INFO","Created new AAAA-Record for '".$domain."' with ip '".$ip."' successfully.");
        } else { wlog("ERROR","Could not determine the ip address of the Fritzbox! Please contact developer: https://github.com/1rfsNet/Fritz-Box-Cloudflare-DynDNS/issues/new"); wlog("INFO","Script aborted"); die; }
    } else {
        foreach($response as $record) {
            wlog("INFO","Found record for '".$domain."': Type: ".$record["type"]." | IP: ".$record["content"]." | Last modified: ".$record["modified_on"]);
            if($ip == $record["content"]) { wlog("INFO","Skipped record, because ip is already up-to-date."); continue; }
            if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) == true) {
                if($record["type"] == "AAAA") continue;
                $response = cf_curl("zones/".$zone."/dns_records/".$record["id"], array("type" => "A", "name" => $domain, "content" => $ip, "ttl" => 120, "proxied" => $proxy ), true);
                if(!$response["success"]) { wlog("ERROR","Could not update record for '".$domain."'. Continue with next record, if available."); continue; } else wlog("INFO","Updated A-Record with ip '".$ip."' successfully.");
            } else if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) == true) { //IPv6
                $response = cf_curl("zones/".$zone."/dns_records/".$record["id"], array("type" => "AAAA", "name" => $domain, "content" => $ip, "ttl" => 120, "proxied" => $proxy ), true);
                if(!$response["success"]) { wlog("ERROR","Could not update record for '".$domain."'. Continue with next record, if available."); continue; } else wlog("INFO","Updated AAAA-Record with ip '".$ip."' successfully.");
            } else { wlog("ERROR","Could not determine the ip address of the Fritzbox! Please contact developer: https://github.com/1rfsNet/Fritz-Box-Cloudflare-DynDNS/issues/new"); wlog("INFO","Script aborted"); die; }
        }
    }
    
}

wlog("INFO","Script completed");

function cf_curl($url, $post = false, $put = false) {
    $cf_key = $_GET["cf_key"];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.cloudflare.com/client/v4/".$url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
    if($put) curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$cf_key, 'Content-Type: application/json'));
    if($post) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $response;
}

function wlog($error, $msg) {
    if(!isset($_GET["log"]) || !$_GET["log"]) return;
    $log = fopen("log.txt", "a");
    fwrite($log, date("Y-m-d H:i:s")." - ".$error." - ".$msg."\n");
    fclose($log);
}

?>