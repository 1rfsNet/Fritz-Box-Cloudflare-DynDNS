<?php
/**
Author: 1rfsNet
Version: 2.0.2
Created: 26.04.2020
Updated: 25.11.2020
License: GNU General Public License v3.0

Description:
This simple PHP script replaces a DynDNS provider and passes your current IP address to Cloudflare.

Parameters: 
- Cloudflare api key (as password) - required
- domain(s) (as domain) -> use multiple domains by separating by semicolon (;) - required
- ipv4 (is automatically provided by the Fritz!Box) - optional (can be removed if you don't want to use ipv4)
- ipv6 (is automatically provided by the Fritz!Box) - optional (can be removed if you don't want to use ipv6)
- log (true/false) - optional (default: false)
- proxy (true/false) - optional (default: false)
Fritz!Box update URL: https://example.com/fritz_dyndns.php?cf_key=<pass>&domain=<domain>&ipv4=<ipaddr>&ipv6=<ip6addr>&log=<true/false>&proxy=<true/false>
*/

wlog("INFO","===== Starting Script =====");
if((!isset($_GET["cf_key"]) && empty($_GET["cf_key"])) || (!isset($_GET["domain"]) && empty($_GET["domain"]))) { wlog("ERROR","Parameter(s) missing or invalid"); wlog("INFO","Script aborted"); die; }
if(isset($_GET["ipv4"]) && !empty($_GET["ipv4"]) && filter_var($_GET["ipv4"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) == true) { wlog("INFO","New IPv4: ".$_GET["ipv4"]); $ipv4 = $_GET["ipv4"]; 
} else { wlog("INFO","IPv4 not available or invalid, ignoring"); $ipv4 = false; }
if(isset($_GET["ipv6"]) && !empty($_GET["ipv6"]) && filter_var($_GET["ipv6"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) == true) { wlog("INFO","New IPv6: ".$_GET["ipv6"]); $ipv6 = $_GET["ipv6"];
} else { wlog("INFO","IPv6 not available or invalid, ignoring"); $ipv6 = false; }

if(!$ipv4 && !$ipv6) { wlog("ERROR","Neither IPv4 nor IPv6 available. Probably the parameters are missing in the update URL. Please note that the update URL has changed with the script version 2.0. You have to change this setting in your Fritz!Box."); wlog("INFO","Script aborted"); die; }

if(isset($_GET["proxy"]) && $_GET["proxy"]) { $proxy = TRUE; wlog("INFO","Record will be proxied by Cloudflare"); }
else { $proxy = FALSE; wlog("INFO","Record will not be proxied by Cloudflare"); }

$auth = cf_curl("zones");
if(!$auth["success"]) { wlog("ERROR","Cloudflare authentication failed: ".$auth["errors"][0]["message"]); wlog("INFO","Script aborted"); die; } else wlog("INFO","Cloudflare authentication successful");

wlog("INFO","Found records to set: ".$_GET["domain"]);
$domains = explode(";", $_GET["domain"]); 

foreach($domains as $domain) {
    wlog("INFO","Find zone for record '".$domain."'");
    $domain_explode = explode(".", $domain);
    $domain_explode = array_reverse($domain_explode);
    $zone = $domain_explode[1].".".$domain_explode[0];
    $response = cf_curl("zones?name=".$zone."&status=active");
    if(!$response["success"]) { 
        wlog("ERROR","Could not set record '".$domain."', because the script could not determine the zone id (check api permissions and resources). Continue with next domain, if available."); 
        continue; 
    } else wlog("INFO","Found zone id (".$response["result"][0]["id"].") for '".$domain."'.");
    $zone = $response["result"][0]["id"];
    $response = cf_curl("zones/".$zone."/dns_records?name=".$domain);
    $response = $response["result"];
    if(!$response) {
        wlog("INFO","Could not find record, creating new record.");
        if($ipv4) {
            $response = cf_curl("zones/".$zone."/dns_records", array("type" => "A", "name" => $domain, "content" => $ipv4, "ttl" => 120, "proxied" => $proxy ));
            if(!$response["success"]) wlog("ERROR","Could not create record for '".$domain."'. Continue with next record, if available."); 
            else wlog("INFO","Created new A-Record for '".$domain."' with ip '".$ipv4."' successfully.");
        }
        if($ipv6) {
            $response = cf_curl("zones/".$zone."/dns_records", array("type" => "AAAA", "name" => $domain, "content" => $ipv6, "ttl" => 120, "proxied" => $proxy ));
            if(!$response["success"]) wlog("ERROR","Could not create record for '".$domain."'. Continue with next record, if available.");
            else wlog("INFO","Created new AAAA-Record for '".$domain."' with ip '".$ipv6."' successfully.");
        }
    } else {
        foreach($response as $record) {
            wlog("INFO","Found record for '".$domain."': Type: ".$record["type"]." | IP: ".$record["content"]." | Last modified: ".$record["modified_on"]);
            if($ipv4 == $record["content"]) { wlog("INFO","Skipped record, because ipv4 is already up-to-date."); continue; }
            else if($record["type"] == "A") {
                $response = cf_curl("zones/".$zone."/dns_records/".$record["id"], array("type" => "A", "name" => $domain, "content" => $ipv4, "ttl" => 120, "proxied" => $proxy ), true);
                if(!$response["success"]) wlog("ERROR","Could not update record for '".$domain."'. Continue with next record, if available.");
                else wlog("INFO","Updated A-Record with ip '".$ipv4."' successfully.");
                continue;
            }
            if($ipv6 == $record["content"]) { wlog("INFO","Skipped record, because ipv6 is already up-to-date."); continue; }
            else if($record["type"] == "AAAA") {
                $response = cf_curl("zones/".$zone."/dns_records/".$record["id"], array("type" => "AAAA", "name" => $domain, "content" => $ipv6, "ttl" => 120, "proxied" => $proxy ), true);
                if(!$response["success"]) wlog("ERROR","Could not update record for '".$domain."'. Continue with next record, if available.");
                else wlog("INFO","Updated AAAA-Record with ip '".$ipv6."' successfully.");
                continue;
            }
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
    $domains = explode(";", $_GET["domain"]);
    $log = fopen("log-".$domains[0].".txt", "a");
    fwrite($log, date("Y-m-d H:i:s")." - ".$error." - ".$msg."\n");
    fclose($log);
}
