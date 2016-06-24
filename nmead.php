<?php
define("SENTENCESTART","$");
define("SENTENCEEND","\r");
define("SAMPLE_RATE_LIMIT", 0);
define("BUFFER_READ_LENGTH",128);
$com = "COM1:";
$connected = 0;
$runfile = "nmealogger".time().".dat";
//$log = "nmea".time().".dat";
function open_com($comm_file) {
	// mode setup 
	exec("mode $comm_file baud=4800 data=8 stop=1 parity=n xon=on",$out); // xon=off to=on');
	print_r($out);
	//exit;
	//$fd = dio_open('$comm_file', O_RDWR); //O_RDONLY | O_NONBLOCK, 0644);
	//var_dump($fd);
	$fd = fopen($comm_file, "r+");
	fputs($fd, "\r\n\r\n");
	return $fd;
	
}

function read_com($handle) {
	//$data = dio_read($fd, 256);
	$data = fread($handle,BUFFER_READ_LENGTH);
	echo $data; 	
	return $data;
}

function process_nmea_buffer(&$inbuffer) {
	global $runfile;
	static $last_position = null;
	static $last_depth = null;
	static $last_temperature = null;
	static $start_time = null;
	static $sample_count = 0;
	$rate = 0;
	if ($start_time == null) {
		$start_time = time();
		
	} else {
		$runtime = time() - $start_time;
		$rate = ($sample_count/$runtime);
	}
	
	//$lh = fopen($log,"a+");
	
	
	
	
	
	//$first_ds = strpos($inbuffer,"$");
	// loose trailing sentences
	//$inbuffer = substr($inbuffer,$first_ds);
	//echo "buffer in: ". $inbuffer ."\n\r";
	$insentence=0;
	$endsentence = 0;
	$current_sentence = "";
	$sentences = array();
	$last_sentenceend_char_pos = 0;
	for ($charpos = 0; $charpos < strlen($inbuffer);$charpos++) {
		//echo $charpos ."\r";
		if ($inbuffer[$charpos] == SENTENCESTART) {
			$insentence = 1;
			$endsentence = 0;	
		}
		if ($inbuffer[$charpos] == SENTENCEEND) {
			//$insentence = 1;
			$endsentence = 1;	
		}
		if ($insentence) {
			$current_sentence .= $inbuffer[$charpos];	
		}
		if ($insentence && $endsentence) {
			// end of sentence detected.
			
			$asterisk = strpos($current_sentence, "*");
			$checksum = 0;
			$sum =0;
			if ($asterisk !== FALSE) {
				for ($i = 1; $i < $asterisk;$i++) {
					//echo $sentence[$i] . " $i\n";
					$sum = $sum ^ ord($current_sentence[$i]);
				}
				//echo $sum;
				//echo "sent sum is: " .hexdec(substr($current_sentence,$asterisk + 1)) . " calculated was " .($sum) ."\n";
				if ($sum == hexdec(substr($current_sentence,$asterisk + 1))) {
					$checksum = 1;
					$current_sentence = substr($current_sentence,0,$asterisk);
				}	
			}
			
			if ($checksum) {
				$sentences[] =   	$current_sentence;
			} else {
				//echo "checksum failed";	
			}
			
			
			
			
			//echo $current_sentence;
			$current_sentence = "";
			$last_sentenceend_char_pos = $charpos;
		} elseif($endsentence) {
			// end sentence while not in a sentence?   fucked. reset and try the next one.
			$current_sentence = "";	
		
		}
		
	}
	//resize the buffer
	$inbuffer = substr($inbuffer,$last_sentenceend_char_pos);
	if (count($sentences)) {
		
		
		
		//sleep(2);
	}
	//echo "remains buffer: $inbuffer\n\r";
	
	
	
	foreach ($sentences as $sentence) {
		//echo "ere";
		//fputs($lh,$sentence."\r");
		$words = explode(",",$sentence);
		
		switch ($words[0]) {
			case "\$SDDBT":
				//echo "DEPTH: ". $sentence ."\n\r";
				//exit;
				$last_depth = $words;
				
				
				break;
			case "\$GPGGA":
				//echo "POSITION: ". $sentence ."\n\r";
				//exit;
				$last_position= $words;
				
				break;
			case "\$SDMTW":
				//echo "WATER TEMP: ". $sentence ."\n\r";
				//exit;
				$last_temperature = $words;
				
				break;
			default:
				//echo $words[0] ."\n";
			//	sleep(1);
		}
		//sleep(1);
		$sample_data = array();
		if (isset($last_position)) {
			$sample_data[] = $last_position[1];
			$sample_data[] = $last_position[2];
			$sample_data[] = $last_position[3];
			$sample_data[] = $last_position[4];
			$sample_data[] = $last_position[5];
			$sample_data[] = $last_position[9];
			if (isset($last_depth) && $last_depth[3] != "") {
				
				$sample_data[] = $last_depth[3];
				if (isset($last_temperature)) {
					$sample_data[] = $last_temperature[1];
					// write full samples.
					if (!SAMPLE_RATE_LIMIT || ($rate < SAMPLE_RATE_LIMIT)) {
						$sl = fopen($runfile, "a+");
						fputs($sl,implode(",",$sample_data) ."\n");
						fclose($sl);
						echo implode(",",$sample_data) . "  (".$rate."s/sec)\n\r";
						$sample_count++;
					} else {
						//echo "Rate Limiting ($rate)... \n\r";
					}
				}
				
			}
			
		} 
	}
	
	//echo "sampling at " . $rate. "s/sec\n\r";
	//fclose($lh);
	//$inbuffer ="";
}

$handle = open_com($com);

if ($handle) {
echo "connected\r\n";
	$connected = 1;
} else {
	die("Couldn't Open port # $com");
	
}


// main loop
$inbuffer = "";
$strikes = 0;
while (TRUE) {
	$buffer_size = strlen($inbuffer);
	$inbuffer .= read_com($handle);
	echo $inbuffer;
	if (strlen($inbuffer) == $buffer_size) {
		// wait 1 for the buffer.
		sleep(1);
		if ($strikes == 2) {
			die ("Waited on buffers. Got nothing.  Quitting.");
			
		} 
		$strikes++;
	}
	if ($inbuffer) {
		process_nmea_buffer($inbuffer);
	} else {
		die ("buffer done");
		
	}	
	
	
	

}

?>