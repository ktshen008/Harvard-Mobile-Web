<?php

/* this class can be expanded eventually to do custom tile image
 * manipulation for institutions that have an ArcGIS tile server
 * but not a WMS server
 */

class ArcGISTileServer {

  public static function getCapabilities() {
    $data = NULL;

    if (file_exists(ARCGIS_SERVICE_JSON_FILE)) {
      $contents = file_get_contents(ARCGIS_SERVICE_JSON_FILE);
      $data = unserialize($contents);
    }
    if (!$data) {
      $contents = file_get_contents(ARCGIS_SERVER_URL . '?f=json');

      // make sure this is legitimate JSON so we don't cache garbage
      if ($data = json_decode($contents)) {
        $fh = fopen(ARCGIS_SERVICE_JSON_FILE, 'w');
        fwrite($fh, serialize($data));
        fclose($fh);
      }
    }

    return $data;
  }

  public static function getWkidProperties($wkid) {
    $data_src = ARCGIS_CACHE . '/' . $wkid;
    $fh = NULL;
    if (!file_exists($data_src)) {
      // this is weird, but i'm counting on unified caching being implemented soon
      $fh = fopen($data_src, 'w');
      $data_src = "http://spatialreference.org/ref/epsg/$wkid/proj4/";
    }
    $data = file_get_contents($data_src);
    if ($fh) {
      fwrite($fh, $data);
      fclose($fh);
    }
    return array('properties' => $data);
  }

  

}

?>