<?php
	/**
	* This program pick a XML file from mirth (a single channel or a channel group) and 'convert'/'translate' it in pdf.
	* It composed of index.html/file.php/pdf.php/fpdf.php/geshi.php.
	* @author Tristan Chaput <tristacha59@gmail.com>.
	* If you have any question ask me at my email address above.
	*/
	require('pdf.php');
	set_time_limit(0);
	$files = array();
	$pdf = array();
//FILE MANAGEMENT.
	//Upload file processing.
	if(isset($_FILES['upload'])){
		$errors = array();
		$file_name = $_FILES['upload']['name'];
		$file_tmp = $_FILES['upload']['tmp_name'];
		$file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
		$file_destination = "pdf_channels/";
		$ext = "xml";
		//Catch exception from the uploaded file.
		try{
			switch ($_FILES['upload']['error']) {
        	case UPLOAD_ERR_OK:
            	break;
        	case UPLOAD_ERR_NO_FILE:
            	throw new RuntimeException('No file found.');
        	default:
            	throw new RuntimeException('Unknown errors.');
    		}
    		if($file_ext != $ext){
				throw new RuntimeException("Extension not allowed, please choose XML file");
			}
    	}catch(RuntimeException $e){
    		echo "Error : ",$e->getMessage(),"\n";
    		exit();
    	}
    	move_uploaded_file($file_tmp, $file_destination.$file_name);
	}
	$type = true;
	//Event on button Convert to PDF and Download.
	if(isset($_POST['convert'])){
		$type = false;
		xml_File_Processing($type,$file_name,$file_destination);
		create_Zip_And_Download($file_name,$file_destination);
		delete_Temp_Files($file_destination);
	}

	//XML File processing.
	//Insert the XML document data into the PDF document for the different parts, Page 1, Page 2 and others. 
	function xml_File_Processing($type,$file_name,$file_destination){
		global $pdf, $files;
		$open  = false;
		$filenames = array();
		//Initialize the XML parser.
		$parser = xml_parser_create();
		//Link of uploaded file.
		$uploaded_file = $file_destination.$file_name;
		//Open XML file.
		$xml = fopen($uploaded_file, "r");
		//Read XML.
		$xml_data = fread($xml, filesize($uploaded_file));
		//Parse it in array.
		xml_parse_into_struct($parser, $xml_data, $data);
		$channels = 0;
		//Here I need to know how many channels I have and their names.
		foreach ($data as $lines) {
			if($data[array_search($lines, $data)]['tag'] == "CHANNEL" && $data[array_search($lines, $data)]['type'] == "open"){
				$channels++;
				$open = true;
			}
			if($data[array_search($lines, $data)]['tag'] == "NAME" && $open){
				array_push($filenames, $data[array_search($lines, $data)]['value']);
				$open = false;
			}
		}
    	date_default_timezone_set("Europe/Paris");
		for($current_channel = 1; $current_channel <= $channels; $current_channel++){
			$pdf[] = new PDF();
			//For the page 1 and 2 I changed the code in 'fpdf.php' at line 304 to 314. The footer appears at page 3.
			$pdf[$current_channel-1]->Create_Page_one($filenames,$current_channel,$data);
			$pdf[$current_channel-1]->Create_Page_two($current_channel,$data);
			$pdf[$current_channel-1]->Create_Page_others($current_channel,$data);
			$pdf[$current_channel-1]->Replace_Page_Two($current_channel,$data);
			//I save all pdf files and their names to output them one by one.
			$pdf[$current_channel-1]->Output('f', $file_destination.$filenames[$current_channel-1].'_'.date("d-m-Y").'_'.date("h-i-s").date("A").'.pdf');
			array_push($files, $filenames[$current_channel-1].'_'.date("d-m-Y").'_'.date("h-i-s").date("A").'.pdf');
		}
		//Release the memory allocated.
		xml_parser_free($parser);
	}
	//I create a zip file with pdf files created.
	function create_Zip_And_Download($file_name,$file_destination){
		date_default_timezone_set("Europe/Paris");
		//The zip file named with channel name or channel group name, date like day/month/year dd/mm/yyyy, hour in 12 hours hours/minutes/seconds hh-mm-ss and AM or PM. Example :  mirth_channel_01-01-2019_03-15-12PM.
		$zip_name = pathinfo($file_destination.$file_name,PATHINFO_FILENAME).'_'.date("d-m-Y").'_'.date("h-i-s").date("A").'.zip';
		header('Content-type: application/zip');
    	header('Content-Disposition: attachment; filename='.$zip_name);
   		header('Expires: 0');
	    $zip = new ZipArchive;
	    if ($zip->open($zip_name, ZipArchive::CREATE) === TRUE) {
	        foreach (glob("pdf_channels/*.pdf") as $file) $zip->addFile($file);
	        $zip->close();
	        readfile($zip_name);
	        unlink($zip_name);
	    	}
		}
	/*
		Delete uploaded .xml file and delete pdf file created in $file_destination,
		delete .zip file created in server directory.
	*/
	function delete_Temp_Files($file_destination){
 		$directory = opendir($file_destination);
	 	while (false !== ($file = readdir($directory))) {
			$path = $file_destination.$file;
			if ($file != ".." && $file != "." && !is_dir($file)) unlink($path);
		}
		closedir($directory);
	}
	/*Array ( 
			[0] => Array ( 
				[tag] => CHANNEL 
				[type] => open 
				[level] => 1 
				[attributes] => Array ( 
							[VERSION] => 3.6.1 ) 
				[value] => ) 
			[1] => Array ( 
				[tag] => ID 
				[type] => complete 
				[level] => 2 
				[value] => 0c081662-850c-4662-80d9-db0b5cc204d3 ) 
			[2] => Array ( 
				[tag] => CHANNEL 
				[value] => 
				[type] => cdata 
				[level] => 1 ) 
			[3] => Array ( 
				[tag] => NEXTMETADATAID 
				[type] => complete 
				[level] => 2 
				[value] => 2 ) 
			[4] => Array ( 
				[tag] => CHANNEL
				[value] => 
				[type] => cdata 
				[level] => 1 ) 
			[5] => Array ( 
				[tag] => NAME 
				[type] => complete 
				[level] => 2 
				[value] => csv-to-pre-xml-ethilog ) 
			[6] => Array ( 
				[tag] => CHANNEL 
				[value] => 
				[type] => cdata 
				[level] => 1 ) 
			[7] => Array ( 
				[tag] => DESCRIPTION 
				[type] => complete 
				[level] => 2 )
		*/

		/*Array ( 
			[0] => Array ( 
				[0] => Version 
				[1] => 3.6.1 )) 
		Array ( 
			[0] => Array ()
			[1] => Array ( 
				[0] => Id
				[1] => 0c081662-850c-4662-80d9-db0b5cc204d3 )) 
		Array ( [0] => Array () 
				[3] => Array ( 
					[0] => Nextmetadataid 
					[1] => 2 )) 
		Array ( [0] => Array ( ) 
				[5] => Array ( 
					[0] => Name 
					[1] => csv-to-pre-xml-ethilog)) 
		Array ( [0] => Array () 
				[7] => Array ( 
					[0] => Description 
					[1] => empty )) 
		Array ( [0] => Array ( ) 
				[9] => Array ( 
					[0] => Revision 
					[1] => 1 ) ) 
		Array ( [0] => Array ( ) 
				[12] => Array ( 
					[0] => Metadataid 
					[1] => 0 ) 
				[14] => Array ( 
					[0] => Name 
					[1] => sourceConnector) 
				[17] => Array ( 
					[0] => Pluginproperties 
					[1] => empty ) 
				[20] => Array ( 
					[0] => Pollingtype 
					[1] => INTERVAL ) 
				[22] => Array ( 
					[0] => Pollonstart
					[1] => false ) 
				[24] => Array ( 
					[0] => Pollingfrequency 
					[1] => 5000 ) 
		*/
?>