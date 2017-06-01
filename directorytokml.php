<?php 
/*
This code is based on stuff created by Nick G and Wade S during the GeoHuntsville Hackathon. Joe H ported it over to export as KML instead of GeoJSON in summer '17
Need to still address:
1) web directory to local directory mapping
4) Comments and attribution (esp for GetGPps function and gps2Num function)
5) Recurse subdirectories
*/
// Set this variable to the directory that serves the images
$webdirectory = 'http://rcal.ist.psu.edu/rebersburg/';
function getGps($exifCoord, $hemi) {
    $degrees = count($exifCoord) > 0 ? gps2Num($exifCoord[0]) : 0;
    $minutes = count($exifCoord) > 1 ? gps2Num($exifCoord[1]) : 0;
    $seconds = count($exifCoord) > 2 ? gps2Num($exifCoord[2]) : 0;
    $flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;
    return $flip * ($degrees + $minutes / 60 + $seconds / 3600);
}
function gps2Num($coordPart) {
    $parts = explode('/', $coordPart);
    if (count($parts) <= 0)
        return 0;
    if (count($parts) == 1)
        return $parts[0];
    return floatval($parts[0]) / floatval($parts[1]);
}
function reportGps ($exif) {
    $lon = getGps($exif["GPSLongitude"], $exif['GPSLongitudeRef']);
    $lat = getGps($exif["GPSLatitude"], $exif['GPSLatitudeRef']);
    $datetime = $exif["DateTimeOriginal"];
    return array ($lat,$lon,$datetime);
}
// The header for KML files //
function head()
{
    $heads = <<<EOS
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
    <Document>
EOS;
return $heads;        
}

// The footer for KML files //

function footer()
{
    $foots = <<<foot
    </Document>
    </kml>
foot;
return $foots;
}

// This will define each individual point to be plotted as a placemark //

function Placemark($filename,$thumbnaillink,$link,$lat,$lon,$datetime)
{
    $place = <<<EOS
        <Placemark>
        <name>$filename</name>
        <description> <![CDATA[ <a href = "$link" target="_blank"> <img src = "$thumbnaillink" width =  150 height = 120></a>]]> </description>
        <Point>            
        <coordinates>$lon,$lat,0</coordinates>
        </Point>
        </Placemark>
EOS;
return $place;
}

// This copies and resizes the image into a thumbnail //

function thumbnail($file,$w,$h)
{
list($width, $height) = getimagesize($file);
$src = imagecreatefromjpeg($file);
$dst = imagecreatetruecolor($w,$h);
imagecopyresampled($dst,$src,0,0,0,0,$w,$h,$width,$height);
return dst;
}

// Main //

header('Content-Type: application/kml');
print head();
$directory = './';
$scanned_directory = array_diff(scandir($directory), array('..', '.', '.images.php.swp', 'images.php','.php'));
foreach ($scanned_directory as $filename) {
    
    if ((strpos($filename, '.jpg') !== false ) OR (strpos($filename, '.JPG') !== false )) {
        $exifforfile = exif_read_data($filename);
        list ($lat,$lon,$datetime) = reportGps ($exifforfile);
        $link = $webdirectory.$filename;
        $thumb = thumbnail($link, 150, 120);
        $thumbnaillink = $link."thumbnail";
        imagejpeg($thumb, $thumbnaillink);
        print Placemark($filename,$thumbnaillink,$link,$lat,$lon,$datetime);
        }
}
print footer();
?>
