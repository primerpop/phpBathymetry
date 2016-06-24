<?php
class ConvolutionFilter {
     public $matrix;
     public $div;
     
     public function computeDiv() {
         $this->div = array_sum ($this->matrix[0]) + array_sum ($this->matrix[1]) + array_sum ($this->matrix[2]);
     }

     function __construct() {
         $matrix = func_get_args();
         $this->matrix = array(    array($matrix[0], $matrix[1], $matrix[2]),
                                 array($matrix[3], $matrix[4], $matrix[5]),
                                 array($matrix[6], $matrix[7], $matrix[8])
                                 );
         $this->computeDiv();
     }
 }

 $identityFilter =    new ConvolutionFilter(    0.0,    0.0,    0.0,
                                              0.0,    1.0,    0.0,
                                              0.0,    0.0,    0.0        );
$sharpenFilter =    new ConvolutionFilter(    0.0,    -1.0,    0.0,
                                              -1.0,    5.0,    -1.0,
                                              0.0,    -1.0,    0.0        );
$edgeFilter =        new ConvolutionFilter(    0.0,    1.0,    0.0,
                                              1.0,    -4.0,    1.0,
                                              0.0,    1.0,    0.0        );
$findEdgesFilter =    new ConvolutionFilter(    -1.0,    -1.0,    -1.0,
                                              -2.0,    8.0,    -1.0,
                                              -1.0,    -1.0,    -1.0        );

 


function convert_lat($lat_value, $direction = "N") {
	$parts = explode(".", $lat_value);
	$min = substr($parts[0], strlen($parts[0]) - 2,2) .".".$parts[1];
	if (strlen($parts[0]) == 4) {
		$deg = substr($parts[0],0,2);
	} elseif (strlen($parts[0]) == 5) {
		$deg = substr($parts[0],0,3);
	}
	//$sec = $parts[1];
	//echo "LAT: $lat_value D: $deg M: $min\r\n";
	$dec = $deg + ($min/60);// + ($sec/3600);
	if ($direction == "S") {
		return $dec * -1;
	}
	return $dec;
}
function convert_long($long_value, $direction = "W") { 
	$parts = explode(".", $long_value);
	
	$min = substr($parts[0], strlen($parts[0]) - 2,2) .".".$parts[1];
	if (strlen($parts[0]) == 4) {
		$deg = substr($parts[0],0,2);
	} elseif (strlen($parts[0]) == 5) {
		$deg = substr($parts[0],0,3);
	}
	//$sec = $parts[1];
	//echo "LONG: $long_value D: $deg M: $min\r\n";
	$dec = $deg + ($min/60);// + ($sec/3600);
	if ($direction == "W") {
		return $dec * -1;
	}
	return $dec;	
}
function getlocationcoords($lat, $lon, $width, $height, $zoom = -10000) {   
    //$x = (($lon + 180) * ($width / $long_view_degrees)); 
    //$y = ((($lat * -1) + 90) * ($height / $lat_view_degrees));

    //$x = $x / $width;
    //$y = $y / $height;
    //$x=$width + (($long*$width)/180);
    //$y=$height - (($lat*$height)/180);
	//$x = (($lon/360)+0.5)*$height;
	//$y = (abs((asinh(tan(deg2rad($lat)))/M_PI/2)-0.5))* $width;
	//$zoom = 12;
	//$x = floor((($lon + 180) / 360) * pow(2, $zoom));
	//$y = floor((1 - log(tan(deg2rad($lat)) + 1 / cos(deg2rad($lat))) / pi()) /2 * pow(2, $zoom));
	
	//$x = lonToX($lon,$zoom) / $width;
	//$y = latToY($lat,$zoom) / $height;
	//X1 = cos(lat1) * cos(lon1)
	$x = cos(deg2rad($lat)) * cos(deg2rad($lon));
	//Y1 = cos(lat1) * sin(lon1)
	$y = cos(deg2rad($lat)) * sin(deg2rad($lon));
	//Z1 = sin(lat1)
    $x = $x * $width;
    $y = $y * $height;
	return array("x"=>($x),"y"=>($y)); 
} 
function lonToX($lon, $zoom) {
    $offset = 256 << ($zoom-1);
    return round($offset + ($offset * $lon / 180));
}
// Returns latitude in pixels at a certain zoom level
function latToY($lat, $zoom) {
    $offset = 256 << ($zoom-1);
    return round($offset - $offset/pi() * log((1 + sin($lat * pi() / 180)) / (1 - sin($lat * pi() / 180))) / 2);
}


$mapWidth = 2000;
$mapHeight = 2000;

$mapLonLeft = -75.4714 + 0.1115;//-75.39;
$mapLonRight = -75.48905 - 0.1115;//-75.54;
//$mapLonLeft = -75.39;
//$mapLonRight = -75.54;
$mapLonDelta = $mapLonRight - $mapLonLeft;
//46.189574, -75.453858
$mapLatBottom = 46.209;
$mapLatBottomDegree = $mapLatBottom * M_PI / 180;

$depthmin = 9999 ;
$depthmax = 0;

$tempmin = 99;
$tempmax = 0;

$latest_sample=null;
$latest_sample_time = null;
$trailsize = 4;
$shuffledraw = 0;


function percent2Color($value,$brightness = 255, $max = 100,$min = 0, $thirdColorHex = '00', $style = 1)
{       
    // Calculate first and second color (Inverse relationship)
    $first = (1-($value/$max))*$brightness;
    $second = ($value/$max)*$brightness;

    // Find the influence of the middle color (yellow if 1st and 2nd are red and green)
    $diff = abs($first-$second);    
    $influence = ($brightness-$diff)/2;     
    $first = intval($first + $influence);
    $second = intval($second + $influence);

    // Convert to HEX, format and return
    $firstHex = str_pad(dechex($first),2,0,STR_PAD_LEFT);     
    $secondHex = str_pad(dechex($second),2,0,STR_PAD_LEFT); 
	
    switch ($style) {
    	case 0:
    		return array($firstHex,$secondHex ,$thirdColorHex) ;
    		break;		
    	case 1:
			return array($thirdColorHex , $firstHex, $secondHex);
			break;
    	case 2:
    		break;
    }
     

    // alternatives:
     
    // return $firstHex . $thirdColorHex . $secondHex;

}

function convertGeoToPixel($lat, $lon)
{
    global $mapWidth, $mapHeight, $mapLonLeft, $mapLonDelta, $mapLatBottom, $mapLatBottomDegree;

    $x = ($lon - $mapLonLeft) * ($mapWidth / $mapLonDelta);

    $lat = $lat * M_PI / 180;
    $worldMapWidth = (($mapWidth / $mapLonDelta) * 360) / (2 * M_PI);
    $mapOffsetY = ($worldMapWidth / 2 * log((1 + sin($mapLatBottomDegree)) / (1 - sin($mapLatBottomDegree))));
    $y = $mapHeight - (($worldMapWidth / 2 * log((1 + sin($lat)) / (1 - sin($lat)))) - $mapOffsetY);

    return array("x"=>round($x), "y"=>round($y));
}

//$position = convertGeoToPixel(53.7, 9.95);
//echo "x: ".$position[0]." / ".$position[1];


$files = glob("nmealogger*.dat");

$min_lat = 180;
$max_lat= -180;

$min_long = 180;
$max_long = -180;

$min_x = $mapWidth;
$min_y =$mapHeight;

$max_x = 0;
$max_y= 0;

$im = imagecreatetruecolor($mapWidth, $mapHeight);
$im_temp = imagecreatetruecolor($mapWidth, $mapHeight);
$im_freq = imagecreatetruecolor($mapWidth, $mapHeight);

imagesetthickness($im_temp,0);
$twhite = imagecolorallocate($im_temp, 255, 255, 255);
$tblack = imagecolorallocate($im_temp, 0, 0, 0);
$tred = imagecolorallocate($im_temp, 255, 0, 0);
imagefill($im_temp, 0, 0, $twhite);

imagesetthickness($im_freq,0);
$fwhite = imagecolorallocate($im_freq, 255, 255, 255);
$fblack = imagecolorallocate($im_freq, 0, 0, 0);
$fred = imagecolorallocate($im_freq, 255, 0, 0);
imagefill($im_freq, 0, 0, $fwhite);



imagesetthickness($im,0);
$white = imagecolorallocate($im, 255, 255, 255);
$black = imagecolorallocate($im, 0, 0, 0);
$red = imagecolorallocate($im, 255, 0, 0);
imagefill($im, 0, 0, $white);
$matrix = array();

$data = array_fill(0, $mapHeight, 0);
$matrix = array_fill(0,$mapWidth,$data);
$samples = array();
$sample_lat_long = array();
$sample_max = 0;
$sample_avg = 0;
$sample_sum = 0;
if ($files) {
	foreach ($files as $file) { 
		$samples = array_merge($samples,file($file));
		
	}
	
	foreach ($samples as $key => $sample) {
		$fields = explode(",", $sample);
		if (isset($fields[6])) {
			$depths[$key] = $fields[6];	
		}
		
		
		
	}
	echo "Read " . count($samples).  " samples.\n\r";
	array_multisort($depths,SORT_DESC);
	
	foreach ($depths as $key => $depth) {
		$t_samples[$key] = $samples[$key];	
	}
	$samples = $t_samples;
	
	foreach ($samples as $key=> $sample) {
		$fields = explode(",", $sample);
		if (isset($fields[1]) && isset($fields[2])) {
			$dec_lat = convert_lat($fields[1],$fields[2]);
			$dec_long = convert_long($fields[3],$fields[4]);
			
			$key = $fields[1].",".$fields[2].",".$fields[3].",".$fields[4];
			
			if (!isset($sample_lat_long[$key])) {
				$sample_lat_long[$key] = 1;
			} else {
				$sample_lat_long[$key]++;
				if ($sample_lat_long[$key] > $sample_max) {
			
						$sample_max = $sample_lat_long[$key];
			
					
				}
			}
			$sample_sum += $sample_lat_long[$key];
			if ($dec_lat < $min_lat) {
				$min_lat = $dec_lat;
				//$mapLatBottom = $min_lat + 0.1115;
	
				//$mapLatBottomDegree = $mapLatBottom * M_PI / 180;
				
			}
			if ($dec_lat > $max_lat) {
				$max_lat = $dec_lat;
				
			}
	
			if ($dec_long < $min_long) {
				$min_long = $dec_long;	
			}
			if ($dec_long > $max_long) {
				$max_long = $dec_long;
			}
			if ($fields[6] > $depthmax) {
				$depthmax = $fields[6] + 3;
			}
			if ($fields[6] < $depthmin) {
				$depthmin = .1;//$fields[6];
			}
			if ($fields[7] > $tempmax) {
				$tempmax = $fields[7];
			}
			if ($fields[7] < $tempmin) {
				$tempmin = $fields[7];
			}
			
			if ($fields[0] > $latest_sample_time) {
				$latest_sample_time = $fields[0];
				$latest_sample = $sample;
			}
		} else {
			unset($samples[$key]);
			
		}
	}
	$latest_sample = $samples[count($samples) - 1];
	if ($shuffledraw) shuffle($samples);
	
	foreach ($samples as $sample) {
	
		$fields = explode(",", $sample);
		$dec_lat = convert_lat($fields[1],$fields[2]);
		$dec_long = convert_long($fields[3],$fields[4]);
		
		
		//echo "Sample " . $fields[0] . ": " . convert_lat($fields[1],$fields[2]) .", ". convert_long($fields[3],$fields[4]) . "\r\n";
		$coords = convertGeoToPixel($dec_lat,$dec_long);//,640,480,50);
		//echo "LAT: $dec_lat = ". $coords["x"] . " LONG: $dec_long" . " " . $coords["y"]  . "\n\r";

		$depth_pct = ($fields[6] / $depthmax) * 100 ;
	
		$color = percent2Color($depth_pct,255,100,$depthmin,'00',1);//,"FF00FF");
		
		
		//print_r($color);	
		$gdcol = imagecolorallocate ($im, hexdec($color[0]),hexdec($color[1]),hexdec($color[2]));
		imagecolortransparent ($im,$gdcol );
		imagefilledellipse ($im, $coords["x"] , $coords["y"]  , $trailsize,$trailsize, $gdcol);
		
		$matrix[$coords["x"]][$coords["y"]] = $fields[6];
		
		$temp_colour = percent2Color($fields[7],255,$tempmax,$tempmin,'00',1);//,"FF00FF");
		$gdcol = imagecolorallocate ($im_temp, hexdec($temp_colour[0]),hexdec($temp_colour[1]),hexdec($temp_colour[2]));
		imagecolortransparent ($im_temp,$gdcol );
		imagefilledellipse ($im_temp, $coords["x"] , $coords["y"]  , $trailsize,$trailsize, $gdcol);
		
		if ((((int)$fields[6] % 1) == 0) || (((int)$fields[6] % 1) == 0)) {
			//imagesetpixel($im, $coords["x"], $coords["y"], $black);	
		}
		if ($coords["x"] < $min_x) {
			$min_x = $coords["x"];
		}
		if ($coords["x"] > $max_x) {
			$max_x = $coords["x"];
		}
		if ($coords["y"] < $min_y) {
			$min_y = $coords["y"];
		}
		if ($coords["y"] > $max_y) {
			$max_y = $coords["y"];
		}
	}
	$sample_avg = $sample_sum / count($sample_lat_long);
	echo $sample_avg;
	//exit;
	array_multisort($sample_lat_long,SORT_ASC);
	foreach ($sample_lat_long as $latlong =>$count) {
		$fields = explode(",", $latlong);
		if ($count > 0) {
			$dec_lat = convert_lat($fields[0],$fields[1]);
			$dec_long = convert_long($fields[2],$fields[3]);
			
			//echo $count / $sample_max . "\n\r";
			
			//echo "Sample " . $fields[0] . ": " . convert_lat($fields[1],$fields[2]) .", ". convert_long($fields[3],$fields[4]) . "\r\n";
			$coords = convertGeoToPixel($dec_lat,$dec_long);//,640,480,50);
			$temp_colour = percent2Color($count,256-64,$sample_avg,1,'CACCCC',0);//,"FF00FF");
			//echp $temp_colour
			$gdcol = imagecolorallocate ($im_freq, hexdec($temp_colour[0]),hexdec($temp_colour[1]),hexdec($temp_colour[2]));
			imagecolortransparent ($im_freq,$gdcol );
			imagefilledellipse ($im_freq, $coords["x"] , $coords["y"]  , $trailsize,$trailsize, $gdcol);
		}
		
	}
	
	echo "min LAT/LONG  = $min_lat,$min_long\r\n";
	echo "max LAT/LONG = $max_lat,$max_long\r\n";
	echo "min depth =  $depthmin"."M\r\n";
	echo "max depth =  $depthmax"."M\r\n";
	//$to_crop_array = array('x' =>$min_x - 50 , 'y' => $min_y - 50, 'width' => $max_x - $min_x + 50, 'height'=> $max_y - $min_y + 50);
	//imagecrop($im,$to_crop_array);
	$fields = explode(",", $latest_sample);
	$dec_lat = convert_lat($fields[1],$fields[2]);
	$dec_long = convert_long($fields[3],$fields[4]);
	$coords = convertGeoToPixel($dec_lat,$dec_long);
	
	imagesetpixel($im, $coords["x"], $coords["y"], $red);
	imageellipse($im, $coords["x"] , $coords["y"]  , 5,5, $red);
	
	$new_im = imagerotate($im, 180,$white,0);
	$new_temp_im = imagerotate($im_temp, 180,$twhite,0);
	$new_freq_im = imagerotate($im_freq, 180, $fwhite,0);

	//imageconvolution($im, $findEdgesFilter->matrix, $findEdgesFilter->div, 0);
	
	
	$imgpng = imagepng($new_im,"image.png");
	$imgpng = imagepng($new_temp_im,"image-temp.png");
	$imgpng = imagepng($new_freq_im,"image-freq.png");
	
	//exit;
//	print_r($matrix); exit;
	require_once ('../jpgraph/jpgraph.php');
	
	include("../jpgraph/jpgraph_contour.php");
	
	$graph = new Graph($mapWidth,$mapHeight);
	$graph->SetScale('intint');//,0,100);
	 
	// Adjust the margins to fit the margin
	//$graph->SetMargin(30,100,40,30);
	 
	// Setup
	$graph->title->Set('Basic contour plot');
	$graph->title->SetFont(FF_ARIAL,FS_BOLD,12);
	 
	// A simple contour plot with default arguments (e.g. 10 isobar lines)
	$cp = new ContourPlot($matrix,20,1);
	 
	// Display the legend
	$cp->ShowLegend();
	 
	$graph->Add($cp);
	@unlink("contour.jpg");
	$graph->Stroke("contour.jpg");
}



?>