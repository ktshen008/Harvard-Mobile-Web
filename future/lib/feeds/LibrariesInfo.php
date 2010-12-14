<?php

//require_once 'lib_constants.inc';
require_once realpath(LIB_DIR.'/feeds/html2text.php');
require_once realpath(LIB_DIR.'/DiskCache.php');

class Libraries{
  private static $cache = null;

  private static function getLibraryCache() {
    if (!isset(self::$cache)) {
      self::$cache = new DiskCache(
        $GLOBALS['siteConfig']->getVar('LIB_CACHE_DIR'), 
        $GLOBALS['siteConfig']->getVar('LIB_DIR_CACHE_TIMEOUT'), TRUE);
      self::$cache->preserveFormat();
      self::$cache->setSuffix('.xml');
    }
    
    return self::$cache;
  }

  private static function query($cacheName, $url) {
    $cache = self::getLibraryCache();
    $xml = null;
    
    if ($cache->isFresh($cacheName)) {
      $xml = simplexml_load_string($cache->read($cacheName));
      
    } else {
      $contents = file_get_contents($url);
      if ($contents == "") {
        error_log("Failed to read contents from $url, reading expired cache");
        $xml = simplexml_load_string($cache->read($cacheName));
        
      } else {
        $xml = simplexml_load_string($contents);
        if (!$xml) {
          error_log("Failed to parse contents from $url, reading expired cache");
          $xml = simplexml_load_string($cache->read($cacheName));
        
        } else {
          $cache->write($contents, $cacheName);
        }
      }
    }
    return $xml;
  }

    public static function getAllLibraries() {

      $xmlURLPath = $GLOBALS['siteConfig']->getVar('URL_LIBRARIES_INFO');
      error_log("LIBRARIES DEBUG: " . $xmlURLPath);
        
      $xml_obj = self::query('librariesInfo', $xmlURLPath);
        
      return self::getAllLibrariesOrArchives('library', $xml_obj);
    }


    public static function getAllArchives() {

      $xmlURLPath = $GLOBALS['siteConfig']->getVar('URL_LIBRARIES_INFO');
      error_log("LIBRARIES DEBUG: " . $xmlURLPath);
        
      $xml_obj = self::query('librariesInfo', $xmlURLPath);
      
      return self::getAllLibrariesOrArchives('archive', $xml_obj);
    }


    public static function getAllLibrariesOrArchives($librariesOrArchives, $xml_obj) {

        $institutes = array();
       
        $count = 0;
         foreach ($xml_obj->institution as $institution) {

             $timeOpen = $institution->hoursofoperation[0]->dailyhours->hours[0];

             if (strlen($timeOpen) <= 0) {
                 $timeOpen = $institution->hoursofoperation->hoursofoperation->dailyhours->hours[0];
             }

             if (strlen($timeOpen) <= 0) {
                 $timeOpen = "N/A";
             }

             $isOpen = self::isOpenNow($timeOpen);
             /*print($institution->id[0]);
             print("     ");
             print($timeOpenToSend);
             print("<br>");*/
            
             $type = explode(":",$institution->type[0]);
             $type = $type[0];
             
             if ($type == $librariesOrArchives){
                 $institute = array();

                 $name = explode(":", $institution->name[0]);
                 $id = explode(":", $institution->id[0]);
                 $address = explode(":", $institution->location->address[0]);
                 $longitude = explode(":",$institution->location->longitude);
                 $latitude = explode(":", $institution->location->latitude);
                 $hrsOpenToday = explode(":", $timeOpen);
                 
                 $institute['name'] = $name[0];
                 $institute['primaryName'] = $name[0];
                 $institute['id'] = $id[0];
                 $institute['type'] = $type;
                 $institute['address'] = HTML2TEXT($address[0]);
                 $institute['latitude'] = $latitude[0];
                 $institute['longitude'] = $longitude[0];
                 $institute['hrsOpenToday'] = $hrsOpenToday[0];
                 $institute['isOpenNow'] = $isOpen;

                 $institutes[] = $institute;
             }

        }
           return $institutes;
       }


       public static function extractSpecificRepoDetails($idTag, $typeString) {

         $xmlURLPath = $GLOBALS['siteConfig']->getVar('URL_LIBRARIES_INFO');
         error_log("LIBRARIES DEBUG: " . $xmlURLPath);
        
         $xml_obj = self::query('librariesInfo', $xmlURLPath);
        
         $institute = array();
         foreach ($xml_obj->institution as $institution) {

             $found = "NO";
             
             $type = explode(":",$institution->type[0]);
             $type = $type[0];
             $id = explode(":", $institution->id[0]);

             if (($type == $typeString) && ($id[0] == $idTag)){

                 $name = explode(":", $institution->name[0]);
                 
                 $addressArray = explode(":", $institution->location->address[0]);
                 $address = HTML2TEXT($addressArray[0]);
                 for($y=1; $y < count($addressArray); $y++)
                    $address = $address . ":" . HTML2TEXT($address[$y]);

                 $longitude = explode(":",$institution->location->longitude);
                 $latitude = explode(":", $institution->location->latitude);

                 $institute['name'] = $name[0];
                 $institute['primaryName'] = $name[0];
                 $institute['id'] = $id[0];
                 $institute['type'] = $type;
                 $institute['address'] = $address; //HTML2TEXT($address[0]);
                 $institute['latitude'] = $latitude[0];
                 $institute['longitude'] = $longitude[0];

                 $found = "YES";
             }

             if ($found == "YES")
                 break;
        }

        return $institute;
    }



       public static function getOpenNow() {

        $xmlURLPath = $GLOBALS['siteConfig']->getVar('URL_LIBRARIES_INFO');
        error_log("LIBRARIES DEBUG: " . $xmlURLPath);
        
        $xml_obj = self::query('librariesInfo', $xmlURLPath);

        $institutes = array();

         foreach ($xml_obj->institution as $institution) {

             $timeOpen = $institution->hoursofoperation[0]->dailyhours->hours[0];

             if (strlen($timeOpen) <= 0) {
                 $timeOpen = $institution->hoursofoperation->hoursofoperation->dailyhours->hours[0];
             }

             if (strlen($timeOpen) <= 0) {
                 $timeOpen = "N/A";
             }

             $isOpen = self::isOpenNow($timeOpen);

                 $institute = array();

                 $name = explode(":", $institution->name[0]);
                 $id = explode(":", $institution->id[0]);
                 $type = explode(":",$institution->type[0]);

                 $institute['name'] = $name[0];
                 $institute['id'] = $id[0];
                 $institute['type'] = $type[0];
                 $institute['isOpenNow'] = $isOpen;

                 $institutes[] = $institute;
             }
           return $institutes;     
       }



       public static function getLibraryDetails($libId, $libName){

         $xmlURLPath = $GLOBALS['siteConfig']->getVar('URL_LIB_DETAIL_BASE').$libId;
         error_log("LIBRARIES DEBUG: " . $xmlURLPath);
        
         $xml_obj = self::query("lib-{$libId}{$libName}", $xmlURLPath);

           return self::getLibOrArchiveDetails($xml_obj, $libName);
       }



       public static function getArchiveDetails($archiveId, $archiveName){

       $xmlURLPath = $GLOBALS['siteConfig']->getVar('URL_ARCHIVE_DETAIL_BASE').$archiveId;
       error_log("LIBRARIES DEBUG: " . $xmlURLPath);
        
       $xml_obj = self::query("lib-{$archiveId}{$archiveName}", $xmlURLPath);

           return self::getLibOrArchiveDetails($xml_obj, $archiveName);
       }

       

       public static function getLibOrArchiveDetails($xml_obj, $name){

           $details = array();

           $institution = $xml_obj;

                $primaryName = explode(":",$institution->names->primaryname[0]);
                $primaryName = $primaryName[0];

                $nameToReturn = $name;
                
                if ($primaryName == $name)
                    $nameToReturn;

                else{

                    foreach($institution->names->alternatename as $possibleName){

                        $possibleName = explode(":", $possibleName[0]);
                        $possibleName = $possibleName[0];

                        if ($possibleName == $name){
                            $nameToReturn = $possibleName;
                            break;
                        }
                    }
                }

                 $type = explode(":",$institution->type[0]);
                 $type = $type[0];
                 $id = explode(":", $institution->id[0]);
                 $id = $id[0];

                 $directionArray = explode(":",$institution->location->directions[0]);
                 $direction = $directionArray[0];
                 for ($j=1; $j < count($directionArray); $j++){
                    $direction .= ":" .$directionArray[$j];
                 }  
                 $direction = HTML2TEXT($direction);
                  
                 $addressArray = explode(":",$institution->location->address[0]);
                 $address = $addressArray[0];
                 for ($j=1; $j < count($addressArray); $j++){
                    $address .= ":" .$addressArray[$j];
                 }
                 $address = HTML2TEXT($address);

                 $latitude = explode(":",$institution->location->latitude[0]);
                 $latitude = $latitude[0];
                 $longitude = explode(":",$institution->location->longitude[0]);
                 $longitude = $longitude[0];

                 $address = explode(":",$institution->location->address[0]);
                 $address = HTML2TEXT($address[0]);
                    
                 $ur = explode(":", $institution->url);
                  if (count($ur) > 1)
                    $url = $ur[0].':'.$ur[1];
                  else
                      $url = $ur[0];


                  $email = explode(":", $institution->emailaddresses[0]->email->emailaddress[0]);
                  $email = $email[0];

                  $phoneNumberArray = array();

                  foreach ($institution->phonenumbers->phonenumber as $phone) {
                      $phoneNumberEntry = array();

                      $phoneNumber = explode(":",$phone->number[0]);
                      $phoneNumber = $phoneNumber[0];

                      $description = explode(":",$phone->description);
                      $description = $description[0];

                      if (strlen($description) == 0)
                          $description = '';

                      $phoneNumberEntry['description'] = HTML2TEXT($description);
                      $phoneNumberEntry['number'] = $phoneNumber;

                      $shouldDisplay = explode(":",$phone['mobiledisplay']);
                      $shouldDisplay = $shouldDisplay[0];

                      if ($shouldDisplay != "false")
                       $phoneNumberArray[] = $phoneNumberEntry;
                  }


                  $weeklyHours = array();

                  $hrsOpenToday = "closed";
                  $today = date('l');
                    foreach ($institution->dailyhours as $hours) {
                        $openHours = array();

                        $date = explode(":",$hours->date[0]);
                        $date = $date[0];

                        $day = date("l", mktime(0, 0, 0, substr($date, 4, 2), substr($date, 6, 2), substr($date, 0, 4)));

                        $dayHours = "";
                        $dayHrs = explode(":",$hours->hours[0]);
                        $dayHours = $dayHrs[0];
                            for ($i=1; $i < count($dayHrs); $i++)
                                $dayHours = $dayHours . ":" .$dayHrs[$i];
                        //$dayHours = $dayHours[0];

                        $openHours['date'] = $date;
                        $openHours['day'] = $day;
                        $openHours['hours'] = $dayHours;

                        $weeklyHours[] = $openHours;

                        if ($day == $today)
                            $hrsOpenToday = $dayHours;
                  }


                  $details['name'] = $nameToReturn;
                  $details['primaryname'] = $primaryName;
                  $details['id'] = $id;
                  $details['type'] = $type;
                  $details['address'] = $address;
                  $details['directions'] = $direction;
                  $details['longitude'] = $longitude;
                  $details['latitude'] = $latitude;
                  //$details['address'] = $address;
                  $details['website'] = $url;
                  $details['email'] = $email;
                  $details['phone'] = $phoneNumberArray;
                  $details['weeklyHours'] = $weeklyHours;
                  $details['hrsOpenToday'] = $hrsOpenToday;

            return $details;
           
       }

       public static function isOpenNow($timeString) {

           if ($timeString == 'closed')
               return "NO";

           else{

               $timeArray= explode('-', $timeString);
               $startString = $timeArray[0];
               $endString = $timeArray[1];

               $isStartAM = self::isAM($startString);
               $isStartPM = self::isPM($startString);

               $isEndAM = self::isAM($endString);
               $isEndPM = self::isPM($endString);

               $startTime = $startString;
               $endTime = $endString;

               if ($isStartAM){
                   $sPos = strpos($startString, 'am');
                   $startTime = substr($startString, 0, $sPos);

               }
               else if ($isStartPM){
                   $sPos = strpos($startString, 'pm');
                   $startTime = substr($startString, 0, $sPos);

               }

               if ($isEndAM){
                   $ePos = strpos($endString, 'am');
                   $endTime = substr($endString, 0, $sPos);

               }
               else if ($isEndPM){
                   $ePos = strpos($endString, 'pm');
                   $endTime = substr($endString, 0, $ePos);
               }


               if ((!isStartAM) && (!isStartPM) && (($isEndAM) || ($isEndPM))){
                   if ($isEndAM)
                       $isStartAM = true;
                   else
                       $isStartPM = true;
               }

               else  if ((!$isEndAM) && (!$isEndPM) && (($isStartAM) || ($isStartPM))){
                   if ($isStartAM)
                       $isEndAM = true;
                   else
                       $isEndPM = true;
               }


               $startTimeArray = explode(':',$startTime);
               $startHrs = (int)$startTimeArray[0];
               $startMins = 0;
               if(count($startTimeArray) > 1){
                   $startMins = (int)$startTimeArray[1];
               }

               $endTimeArray = explode(':',$endTime);
               $endHrs = (int)$endTimeArray[0];
               $endMins = 0;
               if(count($endTimeArray) > 1){
                   $endMins = (int)$endTimeArray[1];
               }

               if (($isStartPM) && ($startHrs != 12))
                   $startHrs += 12;

               if (($isEndPM) && ($startHrs != 12))
                   $endHrs += 12;


               $posNOON_start = strpos($startString, "noon");

               if ($posNOON_start > 0)
                   $startHrs = 12;

               $posMIDNIGHT_start = strpos($startString, "midnight");

               if ($posMIDNIGHT_start > 0)
                   $startHrs = 0;

               $posNOON_end = strpos($endString, "noon");

               if ($posNOON_end > 0)
                   $endHrs = 12;

               $posMIDNIGHT_end= strpos($endString, "midnight");

               if ($posMIDNIGHT_start > 0)
                   $endHrs = 24;


               $start = 60*((int)$startHrs) +((int)$startMins);
               $end = 60*((int)$endHrs) + ((int)$endMins);

               $nowHrs = date('G');
               $nowMins = date('i');

               $now = 60*$nowHrs + $nowMins;

               /*print("startTime = ");
               print($startTime);
               print('<br>');               
               print("startTimeArray = ");
               print_r($startTimeArray);
               print('<br>');

               print("endTime = ");
               print($endTime);
               print('<br>');
               print("endTimeArray = ");
               print_r($endTimeArray);
               print('<br>');

               print("satrtHrs = ");
               print($startHrs);
               print("startMins = ");
               print($startMins);
               print('<br>');
               print("endHrs = ");
               print($endHrs);
               print("endMins = ");
               print($endMins);
               print('<br>');
               print("nowHrs = ");
               print($nowHrs);
               print("nowMins = ");
               print($nowMins);
               print('<br>');

               if ($isStartPM)
               print("isStartPM = YES");
               
               print('<br>');
               */

               if (($now > $start) && ($now < $end)){
                   return "YES";
               }
               else
                   return "NO";
           }       
       }


       public static function searchItems($queryTerms) {

        $xmlURLPath = $GLOBALS['siteConfig']->getVar('URL_LIBRARIES_SEARCH_BASE').http_build_query(array(
          'q' => $queryTerms,
        ));
        error_log("LIBRARIES DEBUG: " . $xmlURLPath);
        
        $xml_obj = self::query("search-{$queryTerms}", $xmlURLPath);
        //print_r($xml_obj);

        $totalResults = explode(":", $xml_obj->totalResults[0]);
        $totalResults = $totalResults[0];

        $searchResults = array();


        foreach ($xml_obj->resultSet->item as $result) {

            $item = $result;
            $itemIndex = explode(":",$item['position']);
            $itemIndex = $itemIndex[0];
            $itemId = explode(":",$item['id']);
            $itemId = $itemId[0];

            $editionArray = explode(":", $item->edition);
            $edition = $editionArray[0];
            for ($j = 1; $j < count($editionArray); $j++) {
                $edition = $edition . ":" . $editionArray[$j];
            }

            $namespaces = $result->getNameSpaces(true);
            $dc = $item->children($namespaces['dc']);


            $creatorArray = explode(":", $dc->creator);
            $creator = $creatorArray[0];
            for ($i = 1; $i < count($creatorArray); $i++) {
                $creator = $creator . ":" . $creatorArray[$i];
            }

            $titleArray = explode(":", $dc->title);
            $title = $titleArray[0];
            for ($j = 1; $j < count($titleArray); $j++) {
                $title = $title . ":" . $titleArray[$j];
            }

            $dateArray = explode(":", $dc->date);
            $date = $dateArray[0];
            for ($k = 1; $k < count($dateArray); $k++) {
                $date = $date . ":" . $dateArray[$k];
            }


            $format = array();

            foreach ($dc->format as $formats) {
                $type = "";
                $val = "";
                if ($formats->attributes()) {

                    foreach ($formats->attributes() as $tp => $value) {
                        $type = $tp;
                        $val = explode(":", $value);
                        $val = $val[0];
                    }
                }

                $formatArray = explode(":", $formats[0]);
                $fm = $formatArray[0];
                for ($k = 1; $k < count($formatArray); $k++) {
                    $fm = $fm . ":" . $formatArray[$k];
                }

                if (strlen($val) > 0) {
                    $format['type'] = $val;
                    $format['typeDetail'] = $fm;
                }
                else
                    $format['formatDetail'] = $fm;
            }

            $isOnline = "NO";
            $isFigure = "NO";
            $onlineAvailabilityArray = array();
            foreach($dc->identifier as $identifier){

                $idenType = "";
                $idenValue = "";
                $idenLink = "";
                foreach ($identifier->attributes() as $idenTp => $idenVal){
                        $idenType = $idenTp;
                        $valA = explode(":", $idenVal);
                        $idenValue = $valA[0];

                        $idenArray = explode(":", $identifier[0]);
                        $idenLink = $idenArray[0];
                        for ($k = 1; $k < count($idenArray); $k++) {
                            $idenLink = $idenLink . ":" . $idenArray[$k];
                        }

                        for($r=1; $r<count($valA); $r++){
                            $idenValue = $idenValue . ":" .$valA[$r];
                        }

                        if ($idenValue == "NET")
                            $isOnline = "YES";

                        else if ($idenValue == "FIG")
                            $isFigure = "YES";

                        
                }

                $onlineavail = array();
                $onlineavail['type'] = $idenValue;
                $onlineavail['link'] = $idenLink;

                $onlineAvailabilityArray[] = $onlineavail;
            }


            $resultArray = array();
            $resultArray['index'] = $itemIndex;
            $resultArray['totalResults'] = $totalResults;
            $resultArray['itemId'] = $itemId;
            $resultArray['creator'] = $creator;
            $resultArray['title'] = $title;
            $resultArray['date'] = $date;
            $resultArray['edition'] = $edition;
            $resultArray['format'] = $format;
            $resultArray['isFigure'] = $isFigure;
            $resultArray['isOnline'] = $isOnline;
            $resultArray['otherAvailability'] = $onlineAvailabilityArray;


            $searchResults[] = $resultArray;

           /* print($itemIndex);
            print("<br>");
            print($itemId);
            print("<br>");
            print($edition);
            print("<br>");
            print($creator);
            print("<br>");
            print($date);
            print("<br>");
            print($title);
            print("<br>");
            print_r($format);
            print("<br>");
            //print($type);
            print("<br>");
           // print($val);
            print("<br>");*/
        }

        return $searchResults;
    }



    public static function getFullAvailability($itemId) {
         $xmlURLPath = $GLOBALS['siteConfig']->getVar('URL_LIBRARIES_AVAILABILITY_BASE').$itemId;
         error_log("LIBRARIES DEBUG: " . $xmlURLPath);
        
         $xml_obj = self::query("fullAvailability-{$itemId}", $xmlURLPath);

          $librariesToReturn = array();
        foreach ($xml_obj->branch as $branch) {

            $libName = explode(":", $branch->repository->name[0]);
            $libId = explode(":", $branch->repository->id[0]);
            $libType = explode(":", $branch->repository->type[0]);

            $repoDetails = array();
            $repoDetails = self::extractSpecificRepoDetails($libId[0], $libType[0]);

            $itemsToReturn = array();
            $statsList = array();

            $collectionItemOnly = array();
            foreach($branch->collection as $collection) {
            $parentCallNumber = "";
            $parentCallNumberArray = explode(":",$collection->callnumber);
            $parentCallNumber = $parentCallNumberArray[0];

                for($z=1; $z < count($callNumberArray); $z++)
                    $parentCallNumber = $parentCallNumber . ":" . $parentCallNumberArray[$z];
            
            $itemCount = 0;
            foreach($collection->items->itemrecord as $item){

                $isAvailable = "N";
                $callNumber = "";
                $stat = "";
                $reqUrl = "";
                
                $itemArray = array();
                $isAvailable = explode(":", $item->isavail);
                $callNumber = "";
                $callNumberArray = explode(":", $item->call);
                $callNumber = $callNumberArray[0];
                
                for($q=1; $q < count($callNumberArray); $q++)
                    $callNumber = $callNumber . ":" . $callNumberArray[$q];
                
                $stat = explode(":", $item->stat);
                $req = explode(":", $item->req->url);
                $reqUrl = $req[0];

                for($i=1; $i < count($req); $i++)
                    $reqUrl = $reqUrl . $req[$i];

                if ($isAvailable[0] == "Y")
                    $itemArray['available'] = "YES";
                else
                     $itemArray['available'] = "NO";

                
                $itemArray['callNumber'] = $callNumber;

               // else
                 //   $itemArray['callNumber'] = $parentCallNumber[0];

                
                $stA = explode(" | ", $stat[0]);

                $seachStringForCheckedOut = $stA[1];
                for ($r=2; $r < count($stA); $r++)
                    $seachStringForCheckedOut = $seachStringForCheckedOut . " " . $stA[$r];

                $seachStringForCheckedOut = strtolower($seachStringForCheckedOut);

                $itemArray['statMain'] = strtolower($stA[0]);
                $itemArray['statSecondary'] = $seachStringForCheckedOut;
                $itemArray['requestUrl'] = $reqUrl;

                $pos = strpos($seachStringForCheckedOut, 'checked out');
                $pos2 = strpos($seachStringForCheckedOut, 'not checked out');

                $itemArray['checkedOutItem'] = "NO";
                if (($pos >= 0) && ($pos2 === FALSE)){
                    $itemArray['checkedOutItem'] = "YES";
                }

                   

                if (($itemArray['available'] == "NO") && (strlen($itemArray['requestUrl']) == 0))
                    $itemArray['unavailable'] = "YES";
                else
                    $itemArray['unavailable'] = "NO";

                if ((strlen($itemArray['requestUrl']) > 0))
                    $itemArray['canRequest'] = "YES";
                else
                     $itemArray['canRequest'] = "NO";

                $itemsToReturn[] = $itemArray;

                if (!in_array(strtolower($stA[0]), $statsList)){
                    $statsList[] = strtolower($stA[0]);
                }
                $itemCount++;
            }

                if ($itemCount == 0){
                    $collectionNameArray = explode(":",$collection->collectionname[0]);
                    $collectionName = $collectionNameArray[0];

                    for($e=1; $e < count($collectionNameArray); $e++)
                        $collectionName = $collectionName . $collectionNameArray[$e];


                    $collectionCallNumber = $parentCallNumber;

                    $collectionAvailVals = array();

                    foreach($collection->holdtag as $holdtag) {
                        $collectionAvailValArray = explode(":",$holdtag->availval[0]);
                    
                        $collectionAvailVal = $collectionAvailValArray[0];
                        for($w=1; $w < count($collectionAvailValArray); $w++)
                            $collectionAvailVal = $collectionAvailVal . $collectionAvailValArray[$w];

                        $collectionAvailVals[] = $collectionAvailVal;
                    }

                    if (strlen($collectionName) == 0)
                        $collectionName = $collectionCallNumber;

                    $noItemMessage = "";
                    $noItemMessageArray = $branch->noitems[0];
                    $noItemMessage = $noItemMessage . $noItemMessageArray[0];

                    for($t=1; $t < count($noItemMessageArray); $t++)
                        $noItemMessage = $noItemMessage . $noItemMessageArray[$t];
                    
                    $collectionItem = array();
                    $collectionItem['collectionName'] = $collectionName;
                    $collectionItem['collectionCallNumber'] = $collectionCallNumber;
                    $collectionItem['collectionAvailVal'] = $collectionAvailVals;
                    $collectionItem['noItemMessage'] = $noItemMessage;


                    if (((strlen($collectionName) > 0) || (count($collectionAvailVals) > 0)) || (strlen($noItemMessage) > 0))
                        $collectionItemOnly[] = $collectionItem;

                }

            }

            $statsToReturn = array();

            for($j=0; $j < count($statsList); $j++){
            $statArr = array();
            $statArr['availableItems'] = array();
            $statArr['checkedOutItems'] = array();
            $statArr['unavailableItems'] = array();
            $statArr['collectionOnlyItems'] = array();
            $availCount = 0;
            $requestCount = 0;
            $unavailCount = 0;
            $checkedOutCount = 0;
            $callNo = "";
             $statArr['statMain'] = $statsList[$j];
                foreach($itemsToReturn as $itm){
                    if ($itm['statMain'] == $statsList[$j]){

                        if ($itm['available'] == 'YES')
                            $availCount++;

                        if (strlen($itm['requestUrl']) > 0)
                            $requestCount++;

                        if ($itm['unavailable'] == 'YES')
                            $unavailCount++;


                        if ($itm['checkedOutItem'] == 'YES')
                            $checkedOutCount++;

                        if (strlen($parentCallNumber[0]) > 0)
                             $callNo = $parentCallNumber;

                        else
                            $callNo = $itm['callNumber'];
                        //$statArr['items'][] = $itm;

                        if ($itm['available'] == 'YES') {
                            $statArr['availableItems'][] = $itm;
                        }
                        
                        else if (($itm['checkedOutItem'] == "YES") || ($item['canRequest'] == "YES"))
                            $statArr['checkedOutItems'][] = $itm;

                        else
                            $statArr['unavailableItems'][] = $itm;

                    }
                }

                if (($availCount == 0) && ($unavailCount == 0) && ($checkedOutCount == 0) && ($requestCount > 0))
                    $checkedOutCount = $requestCount;
                
                $statArr['availCount'] = $availCount;
                $statArr['unavailCount'] = $unavailCount;
                $statArr['requestCount'] = $requestCount;
                $statArr['checkedOutCount'] = $checkedOutCount;
                $statArr['collectionOnlyCount'] = 0;// $collectionOnlyCount;
                $statArr['callNumber'] = $callNo;

                $statsToReturn[] = $statArr;
            }

            if (count($collectionItemOnly) > 0) {
                $collectionOnlyStat = array();

                $collectionOnlyStat['availableItems'] = array();
                $collectionOnlyStat['checkedOutItems'] = array();
                $collectionOnlyStat['unavailableItems'] = array();
                $collectionOnlyStat['collectionOnlyItems'] = $collectionItemOnly;

                $collectionOnlyStat['availCount'] = 0;
                $collectionOnlyStat['unavailCount'] = 0;
                $collectionOnlyStat['requestCount'] = 0;
                $collectionOnlyStat['checkedOutCount'] = 0;
                $collectionOnlyStat['collectionOnlyCount'] = count($collectionItemOnly);
                $collectionOnlyStat['callNumber'] = "";

                $statsToReturn[] = $collectionOnlyStat;
                
            }
            
            $lib = array();
            $lib['name'] = $libName[0];
            $lib['id'] = $libId[0];
            $lib['type'] = $libType[0];
            $lib['details'] = $repoDetails;
            //$lib['items'] = $itemsToReturn;
            $lib['itemsByStat'] = $statsToReturn;

            if (count($repoDetails) > 0)
                $librariesToReturn[] = $lib;
        }

        return $librariesToReturn;
    }



    public static function getItemRecord($itemId){


        $xmlURLPath = $GLOBALS['siteConfig']->getVar('URL_LIBRARIES_ITEM_RECORD_BASE').$itemId;
        error_log("LIBRARIES DEBUG: " . $xmlURLPath);

        $xml_obj = self::query("item-{$itemId}", $xmlURLPath);

        $item = $xml_obj;

            $editionArray = explode(":", $item->edition);
            $edition = $editionArray[0];
            for ($j = 1; $j < count($editionArray); $j++) {
                $edition = $edition . ":" . $editionArray[$j];
            }

            $namespaces = $item->getNameSpaces(true);
            $dc = $item->children($namespaces['dc']);

            $creatorArray = explode(":", $dc->creator);
            $creator = $creatorArray[0];
            for ($i = 1; $i < count($creatorArray); $i++) {
                $creator = $creator . ":" . $creatorArray[$i];
            }

            $titleArray = explode(":", $dc->title);
            $title = $titleArray[0];
            for ($j = 1; $j < count($titleArray); $j++) {
                $title = $title . ":" . $titleArray[$j];
            }

            $dateArray = explode(":", $dc->date);
            $date = $dateArray[0];
            for ($k = 1; $k < count($dateArray); $k++) {
                $date = $date . ":" . $dateArray[$k];
            }

            $pubArray = explode(":", $dc->publisher);
            $publisher = $pubArray[0];
            for ($k = 1; $k < count($dateArray); $k++) {
                $publisher = $publisher . ":" . $pubArray[$k];
            }


            $format = array();

            foreach ($dc->format as $formats) {
                $type = "";
                $val = "";
                if ($formats->attributes()) {

                    foreach ($formats->attributes() as $tp => $value) {
                        $type = $tp;
                        $val = explode(":", $value);
                        $val = $val[0];
                    }
                }

                $formatArray = explode(":", $formats[0]);
                $fm = $formatArray[0];
                for ($k = 1; $k < count($formatArray); $k++) {
                    $fm = $fm . ":" . $formatArray[$k];
                }

                if (strlen($val) > 0) {
                    $format['type'] = $val;
                    $format['typeDetail'] = $fm;
                }
                else
                    $format['formatDetail'] = $fm;
            }


            $identifierArray = array();
            foreach ($dc->identifier as $identifier) {
                $identifierToAdd = array();
                $type = "";
                $val = "";
                if ($identifier->attributes()) {

                    foreach ($identifier->attributes() as $tp => $value) {
                        $type = $tp;
                        $val = explode(":", $value);
                        $val = $val[0];
                    }
                }

                $idenArray = explode(":", $identifier[0]);
                $iden = $idenArray[0];
                for ($k = 1; $k < count($idenArray); $k++) {
                    $iden = $iden . ":" . $idenArray[$k];
                }

                $identifierToAdd['type'] = $val;
                $identifierToAdd['typeDetail'] = $iden;

                $identifierArray[] = $identifierToAdd;
            }


            $itemDetails = array();

            $itemDetails['title'] = $title;
            $itemDetails['creator'] = $creator;
            $itemDetails['date'] = $date;
            $itemDetails['publisher'] = $publisher;
            $itemDetails['edition'] = $edition;
            $itemDetails['format'] = $format;
            $itemDetails['identifier'] = $identifierArray;

            return $itemDetails;
    }



    public static function isPM ($timeString) {

        $posPM = strpos($timeString,'pm');

                if($posPM === false) {
                    return false;
                }
                else {
                    return true;
                }
       }

       public static function isAM ($timeString) {

        $posAM = strpos($timeString,'am');

                if($posAM === false) {
                    return false;
                }
                else {
                    return true;
                }
       }


}

