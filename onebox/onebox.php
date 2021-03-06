<?php
    header('Content-type: text/html; charset=UTF-8');
    class Onebox
    {
        private $apiMap = array(
            "mal" => array(
                "name" => "MyAnimelist.net",
                "url" => "",
                "logo_url" => "http://www.userlogos.org/files/logos/Lozzy1/MyAnimeList.png"
            ),
            "ann" => array(
                "name" => "AnimeNewsNetwork",
                "url" => "",
                "logo_url" => "https://pbs.twimg.com/profile_images/199100222/ANN_Logo_dots.png"
            )
        );

        private $errorMsg = "<p>Please get back to kokoro-ko.de for help.</p>";

        public function __construct() {
        }

        public function printPage(){
            echo '
            <html>
                <head>
                    <link rel="stylesheet" href="./style.css">
                </head>
                <body>';

            if(isset($_GET["api"])){
                $oneBoxType = $_GET["api"];
                if($oneBoxType == "mal" || $oneBoxType == "ann"){
                    isset($_GET["type"]) ? $apiType = $_GET["type"] : $apiType = null;
                    isset($_GET["id"]) ? $apiId = $_GET["id"] : $apiId = null;
                    $this->retrieveData($oneBoxType,$apiType,$apiId);
                }else{
                    $this->printErrorMsg("Wrong Onebox-Type given!");
                    return;
                }
            }else{
                $this->printErrorMsg("No Onebox-Type given!");
                return;
            }

        }

        private function printOneBox($dataAr){
            $englishName = "";
            if($dataAr["name"] != $dataAr["en_name"] && $dataAr["en_name"] != ""){
                $englishName = '<em> ('.$dataAr["en_name"].')</em>';
            }

            if(strpos($dataAr["img_url"], "https") === false){
				$dataAr["img_url"] = str_replace("http","https",$dataAr["img_url"]);
            }

            if(strpos($dataAr["url"], "https") === false){
				$dataAr["url"] = str_replace("http","https",$dataAr["url"]);
            }

            if(strpos($dataAr["logo_url"], "https") === false){
				$dataAr["logo_url"] = str_replace("http","https",$dataAr["logo_url"]);
            }

            echo '<div id="widget">
                <div id="header" class="header_container">
                    <img src="'.$dataAr["logo_url"].'" class="api_logo">
                    <h1 class="main_text" style="max-width: 1773px;">
                        <a target="_blank" href="'.$dataAr["url"].'">'.$dataAr["name"].$englishName.'</a>
                    </h1>
                </div><div style="clear: both;"></div><div class="desc">';

            if($dataAr["img_url"] != ""){
                echo '<a target="_blank" href="'.$dataAr["url"].'"><div class="capsule" style="background-image: url('.$dataAr["img_url"].');"></div></a>';
            }   

            echo '<div class="text_desc">'.preg_replace('#\[[^\]]+\]#', '', str_replace("<br />","",(str_replace("[Written by MAL Rewrite]","",$dataAr["desc"])))).'</div></div><div style="clear: both;"></div><div class="lower_button">';


            if($dataAr["type"] == "anime"){
                $this->drawInfoButton('<b>Episodes:</b> '.$dataAr["episodes"]);
                $this->drawInfoButton('<b>Status:</b> '.$dataAr["status"]);
                $this->drawInfoButton('<b>Rating:</b> '.$dataAr["score"]);
                $dates = $dataAr["start"];
                if(isset($dataAr["end"]) && $dataAr["end"] !== null){
                    $dates .= " - " . $dataAr["end"];
                }
                $this->drawInfoButton('<b>Release:</b> '.$dates);
            }else{
                $this->drawInfoButton('<b>Volumes:</b> '.$dataAr["volumes"]);
                $this->drawInfoButton('<b>Chapter:</b> '.$dataAr["chapter"]);
                $this->drawInfoButton('<b>Status:</b> '.$dataAr["status"]);
                $this->drawInfoButton('<b>Rating:</b> '.$dataAr["score"]);
                $dates = $dataAr["start"];
                if(isset($dataAr["end"])){
                    $dates .= " - " . $dataAr["end"];
                }
                $this->drawInfoButton('<b>Release:</b> '.$dates);
            }
            

            echo '</div></div></div></body></html>';
        }

        private function drawInfoButton($content){
            echo '<div class="lower_button_bg"><div class="lower_button_content">'.$content.'</div></div>';
        }

        private function retrieveData($api,$_apiType,$_id){
            if(!($_apiType !== null && $_id !== null)){
                $this->printErrorMsg("Missing parameter!");
                return;
            }

            if($_apiType != "anime" && $_apiType != "manga"){
                $this->printErrorMsg("No valid Api-Type given.");
                return;
            }

            $resultData = array(
                "url" => &$this->apiMap[$api]["url"],
                "logo_url" => $this->apiMap[$api]["logo_url"],
                "img_url" => "",
                "api_name" => $this->apiMap[$api]["name"],
                "type" => $_apiType
            );
            $foundSomething = false;
            $funcName = $api."RetrieveData";
            $foundSomething = $this->$funcName($_apiType,$_id, $resultData);
            
            if($foundSomething){
                $this->printOneBox($resultData);
            }else{
                $this->printErrorMsg("Couldn't find the given " . $_apiType);
            }
            
        }
        
        private function malRetrieveData($apiType,$id, &$resultData){
            
            $host = "https://api.jikan.moe/v3/".$apiType."/".$id;
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => TRUE,
                CURLOPT_URL => $host,
                CURLOPT_HEADER => FALSE
            ));
            $resp = curl_exec($curl);

            if(curl_errno($curl)){
                echo 'Curl error: ' . curl_error($curl);
            }
            curl_close($curl);
            $singleObj = json_decode($resp);

            if($singleObj != null){
                if(!isset($singleObj->error)){
                    if($singleObj->mal_id == $id){
                        if($apiType == "anime"){
                            $resultData["name"] = $singleObj->title_japanese;
                            $resultData["en_name"] = $singleObj->title_english;
                            $resultData["desc"] = $singleObj->synopsis;
                            $resultData["start"] = date("d.m.Y",strtotime($singleObj->aired->from));
                            $resultData["end"] = ($singleObj->aired->to != "") ? date("d.m.Y",strtotime($singleObj->aired->to)) : null;
                            $resultData["status"] = $singleObj->status;
                            $resultData["score"] = $singleObj->score;
                            $resultData["episodes"] = $singleObj->episodes;
                            $resultData["img_url"] = $singleObj->image_url;
                        }else{
                            $resultData["chapter"] = $singleObj->chapters;
                            $resultData["volumes"] = $singleObj->volumes;
                            $resultData["name"] = $singleObj->title;
                            $resultData["en_name"] = $singleObj->title_english;
                            $resultData["desc"] = $singleObj->synopsis;
                            $resultData["start"] = date("d.m.Y",strtotime($singleObj->published->from));
                            $resultData["end"] = ($singleObj->published->to != "") ? date("d.m.Y",strtotime($singleObj->published->to)) : null;
                            $resultData["status"] = $singleObj->status;
                            $resultData["score"] = $singleObj->score;
                            $resultData["img_url"] = $singleObj->image_url;
                        }
                        $this->apiMap["mal"]["url"] = $singleObj->url;
                        return true;
                    }
                }
            }

            return false;
        }

        private function annRetrieveData($apiType,$id, &$resultData){
            $this->apiMap["ann"]["url"] = "http://www.animenewsnetwork.com/encyclopedia/".$apiType.".php?id=".$id;
            $host = "https://cdn.animenewsnetwork.com/encyclopedia/api.xml?".$apiType."=".$id;

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $host,
                CURLOPT_FOLLOWLOCATION => TRUE,
                CURLOPT_SSL_VERIFYPEER => false
            ));
            $resp = curl_exec($curl);

            if(curl_errno($curl)){
                echo 'Curl error: ' . curl_error($curl);
            }
            curl_close($curl);

            $parsedXmlObject = new SimpleXMLElement($resp);

            if($parsedXmlObject->warning){
                return;
            }

            if($apiType == "manga"){
                foreach ($parsedXmlObject->manga as $value) {
                    foreach ($value->info as $info) {
                        switch ($info["type"][0]) {
                            case 'Picture':
                                $resultData["img_url"] = $info["src"];
                            break;
                            case 'Main title':
                                $resultData["en_name"] = $info;
                            break;
                            case 'Alternative title' && $info["lang"][0] == "JA":
                                $resultData["name"] = $info;
                            break;
                            case 'Plot Summary':
                                $resultData["desc"] = $info;
                            break;
                            case 'Number of tankoubon':
                                $resultData["volumes"] = $info;
                            break;
                            case 'Number of pages':
                                $resultData["chapter"] = "---";
                            break;
                            case 'Vintage':
                                if(!isset($resultData["start"])){
                                    $dates = explode(" to ",preg_replace("/\([^)]+\)/","",$info));
                                    $start = str_replace(" ","",$dates[0]);
                                    if(strlen($start) == 4){
                                        $start = DateTime::createFromFormat("d.m.Y", "01.01." . $start)->format("d.m.Y");
                                    }
                                    isset($dates[1]) ? $end = str_replace(" ","",$dates[1]) : $end = "";
                                    if(strlen($end) == 4){
                                        $end = DateTime::createFromFormat("d.m.Y", "01.01." . $end)->format("d.m.Y");
                                    }
                                    $resultData["start"] = date("d.m.Y",strtotime($start));
                                    if($end != "") { $resultData["end"] = date("d.m.Y",strtotime($end));}
                                    if(isset($resultData["end"]) && strtotime($resultData["end"]) < time()){
                                        $resultData["status"] = "Finished Airing";
                                    }else{
                                        $resultData["status"] = "Ongoing/Not Started";
                                    }
                                }
                            break;
                            default:
                                break;
                        }
                        if(!isset($resultData["volumes"])){
                            $resultData["volumes"] = "---";
                        }
                    }                    
                    $resultData["score"] = $value->ratings["weighted_score"];
                }
            }else{
                foreach ($parsedXmlObject->anime as $value) {
                    foreach ($value->info as $info) {
                        switch ($info["type"][0]) {
                            case 'Picture':
                                $resultData["img_url"] = $info["src"];
                            break;
                            case 'Main title':
                                $resultData["en_name"] = $info;
                            break;
                            case 'Alternative title' && $info["lang"][0] == "JA":
                                $resultData["name"] = $info;
                            break;
                            case 'Plot Summary':
                                $resultData["desc"] = $info;
                            break;
                            case 'Number of episodes':
                                $resultData["episodes"] = $info;
                                if(count($value->episode) >= $info){
                                    $resultData["status"] = "Finished Airing";
                                }else{
                                    $resultData["status"] = "Ongoing/Not Started";
                                }
                            break;
                            case 'Vintage':
                                $dates = explode(" to ",$info);
                                $resultData["start"] = date("d.m.Y",strtotime($dates[0]));
                                isset($dates[1]) ? $resultData["end"] = date("d.m.Y",strtotime($dates[1])) : $resultData["end"] = null;
                                if($value->attributes()->type == "movie"){
                                    if(strtotime($resultData["start"]) < time()){
                                        $resultData["status"] = "Finished Airing";
                                    }else{
                                        $resultData["status"] = "Ongoing/Not Started";
                                    }
                                }
                            break;
                            default:
                                break;
                        }
                    }
                    if($value->attributes()->type == "movie"){
                        $resultData["episodes"] = 1;
                    }
                    $resultData["score"] = $value->ratings["weighted_score"];
                }
            }
            
            return true;
            
        }

        private function printErrorMsg($msg){
            echo "<div class='error_msg'><h1>".$msg."</h1>".$this->errorMsg."</div>";
        }
    }

    $ob = new Onebox();
    $ob->printPage();
    
?>