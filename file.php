<?php
	/**
	* This program pick a XML file from mirth (a single channel or a channel group) and 'convert'/'translate' it in pdf.
	* It composed of index.html/file.php/pdf.php/fpdf.php/geshi.php.
	* @author Tristan Chaput <tristacha59@gmail.com>.
	* If you have any question ask me at my email address above.
	*
	* If you have some files with a size greater than 2M you need to set the value of 
	* upload_max_filesize and post_max_size in your php.ini :
	*
	* ; Maximum allowed size for uploaded files.
	* upload_max_filesize = 100M
	*
	* ; Must be greater than or equal to upload_max_filesize.
	* post_max_size = 100M
	*/
	require('pdf.php');
	set_time_limit(0);
	$files = array();
	$pdf = array();
	date_default_timezone_set("Europe/Paris");
    $date = date("d-m-Y").'_'.date("h-i-s").date("A");
//FILE MANAGEMENT.
	//Upload file processing.
	if(isset($_FILES['upload'])){
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
				throw new RuntimeException("Extension not allowed, please choose an XML file");
			}
			if(file_exists($file_destination)){
				delete_Temp_Files($file_destination);
			}
			if(!move_uploaded_file($file_tmp, $file_destination.$file_name)){
    			throw new Exception("Can't move file, file exceeds the maximum size");
    		}
    	}catch(RuntimeException $e){
    		echo "Error : ",$e->getMessage(),"\n";
    		exit();
    	}	
	}
	//Event on button Convert to PDF and Download.
	if(isset($_POST['convert'])){
		xml_File_Processing($file_name,$file_destination,$date);
		create_Zip_And_Download($file_name,$file_destination,$date);
		delete_Temp_Files($file_destination);
	}

	//XML File processing.
	//Insert the XML document data into the PDF document for the different parts, Page 1, Page 2 and others. 
	function xml_File_Processing($file_name,$file_destination,$date){
		global $pdf, $files;
		$open  = false;
		$ignore = false;
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
			if($data[array_search($lines, $data)]['tag'] == "CHANNELGROUPS" && $data[array_search($lines, $data)]['type'] == "open"){
						$ignore = true;
					}else if ($data[array_search($lines, $data)]['tag'] == "CHANNELGROUPS" && $data[array_search($lines, $data)]['type'] == "close") {
						$ignore = false;
					}
			if($data[array_search($lines, $data)]['tag'] == "CHANNEL" && $data[array_search($lines, $data)]['type'] == "open" && !$ignore){
				$channels++;
				$open = true;
			}
			if($data[array_search($lines, $data)]['tag'] == "NAME" && $open){
				array_push($filenames, $data[array_search($lines, $data)]['value']);
				$open = false;
			}
		}
    	
		for($current_channel = 1; $current_channel <= $channels; $current_channel++){
			$pdf = new PDF();
			//The header appears at page 3. I changed the code in 'fpdf.php' at line 334 to 339. 
			//For the page 1 and 2 I changed the code in 'fpdf.php' at line 304 to 314. The footer appears at page 3.
			$pdf->Create_Page_one($filenames,$current_channel,$data);
			$pdf->Create_Page_two($current_channel,$data);
			$pdf->Create_Page_others($current_channel,$data);
			$pdf->Set_Page_Numbers_And_Titles($current_channel,$data);
			//I save all pdf files and their names to output them one by one.
			$pdf->Output('f', $file_destination.$filenames[$current_channel-1].'_'.$date.'.pdf');
			array_push($files, $filenames[$current_channel-1].'_'.$date.'.pdf');
			unset($pdf);
		}
		//Release the memory allocated.
		xml_parser_free($parser);
	}
	//I create a zip file with pdf files created.
	function create_Zip_And_Download($file_name,$file_destination,$date){
		date_default_timezone_set("Europe/Paris");
		//The zip file named with channel name or channel group name, date like day/month/year dd/mm/yyyy, hour in 12 hours hours/minutes/seconds hh-mm-ss and AM or PM. Example :  mirth_channel_01-01-2019_03-15-12PM.
		$zip_name = pathinfo($file_destination.str_replace(' ', '_', $file_name),PATHINFO_FILENAME).'_'.$date.'.zip';
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
?>