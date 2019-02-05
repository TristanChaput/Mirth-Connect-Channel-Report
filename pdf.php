<?php
	/**
	* This program pick a XML file from mirth (a single channel or a channel group) and 'convert'/'translate' it in pdf.
	* It composed of index.html/file.php/pdf.php/fpdf.php/geshi.php
	* @author Tristan Chaput <tristacha59@gmail.com>
	* If you have any question ask me at my email address above.
	*/
	require('fpdf.php');
	include_once('geshi.php');
	class PDF extends FPDF
	{
		protected $first_table = 0;
		protected $no_page = array();
		protected $script_lines = 1;
		/*
		This function is used to create the first page, I have the title of the pdf document and the name of the channel searched also the page number that have been stored for the second page.
		*/
		function Create_Page_one($filesnames,$current_channel,$data){
			$this->SetMargins(20,50,20);
			$this->AddPage();
			$this->SetFont('Arial','',32);
			$height = $this->GetPageHeight()-110;
			$width = $this->GetPageWidth()-150;
			$this->SetFillColor(40,174,163);
			$this->SetTextColor(255,255,255);
			$this->Cell(0,20,"Mirth Connect Channel Report",0,2,'C',true);
			$this->Ln(2);
			$this->SetFillColor(255,100,0);
			$channel_name = $filesnames[$current_channel-1]." Channel";
			$this->SetRightMargin(10);
			$this->SetLeftMargin($this->GetPageWidth()-$this->GetStringWidth($channel_name));
			$this->SetFontSize(16);
			$this->Cell(0,10,$channel_name,0,2,'L',true);
			$this->Image('ethilog_logo.png',$width,$height,80,40);
		}
		/*
		This function is used to structure the second page before adding page number and connector properties.
		*/
		function Create_Page_two($current_channel,$data){
			$count = 1;
			$no_connector = 0;
			$open_connector = false;
			$this->SetMargins(20,30,20);
			$this->AddPage();
			$this->SetFontSize(72);
			//Cyan.
			$this->SetTextColor(255,100,0);
			$this->Cell(0,40,"Contents",0,2,'L');
			$this->SetFontSize(26);
			//Orange.
			$this->SetTextColor(40,174,163);
			$this->Cell(0,15,"1.0 Channel Summary",0,2,'L');
			$this->Ln(5);
			$this->Cell(0,15,"2.0 Source Connector",0,2,'L');
			$this->Ln(5);
			$this->Cell(0,15,"3.0 Destination Connectors",0,2,'L');
			//Black.
			$this->SetTextColor(0,0,0);
			$this->SetFont('Times','',12);
			//It allows structuring the summary for the connectors.
			foreach ($data as $lines) {
				if($data[array_search($lines, $data)]['tag'] == "CONNECTOR" && $data[array_search($lines, $data)]['type']== "open"){
					if ($count == $current_channel) {
						$open_connector = true;
					}else{
						$count++;
					}
				}
				if($data[array_search($lines, $data)]['tag'] == "NAME" && $open_connector){
					$no_connector++;
					$this->Cell(0,6,"3.".$no_connector.' '.$data[array_search($lines, $data)]['value'],0,2,'L');
					$open_connector = false;
				}
			}
			$this->SetFont('Arial','',26);
			//Orange.
			$this->SetTextColor(40,174,163);
			$this->Ln(5);
			$this->Cell(0,15,"4.0 Channel Scripts",0,2,'L');
			//Black.
			$this->SetTextColor(0,0,0);
			$this->SetFont('Times','',12);
			$this->Cell(0,6,"4.1 Deploy Script",0,2,'L');
			$this->Cell(0,6,"4.2 Preprocessor Script",0,2,'L');
			$this->Cell(0,6,"4.3 Postprocessor Script",0,2,'L');
			$this->Cell(0,6,"4.4 Shutdown Script",0,2,'L');
			$this->AliasNbPages();
		}
		/*
		This function put page numbers in summary at page 2 and the type of source connector, connector, etc...
		The channel contents will be resume in this and push it in the pdf document.
		*/		
		function Replace_Page_Two($current_channel,$data){
			global $no_page;
			$open_source = false;
			$open_connector = false;
			$get_name = false;
			$connector_name = array();
			$count = 1;
			if(!empty($no_page)){

				$this->pages[2]=str_replace("1.0 Channel Summary","1.0 Channel Summary p".$no_page[0],$this->pages[2]);
				$this->pages[2]=str_replace("3.0 Destination Connectors","3.0 Destination Connectors p".$no_page[2],$this->pages[2]);
				foreach ($data as $lines) {
					//Test when I have a node with type open and tag SourceConnector so I test if this channel is the channel needed.;
					if($data[array_search($lines, $data)]['tag'] == "SOURCECONNECTOR" && $data[array_search($lines, $data)]['type']== "open"){
						if ($count == $current_channel) {
							$open_source = true;
						}else{
							$count++;
						}
					}
					//If the source connector is open I could get the value of it.
					if($data[array_search($lines, $data)]['tag'] == "TRANSPORTNAME" && $open_source){
						$this->pages[2]=str_replace("2.0 Source Connector","2.0 Source Connector (".$data[array_search($lines, $data)]['value'].") p".$no_page[1],$this->pages[2]);
						$open_source = false;
					} 
					if($data[array_search($lines, $data)]['tag'] == "CONNECTOR" && $data[array_search($lines, $data)]['type']== "open"){
						if ($count == $current_channel) {
							$open_connector = true;
						}else{
							$count++;
						}
					}
					//When connector is open I get his name. 
					if($data[array_search($lines, $data)]['tag'] == "NAME" && $open_connector){
						$connector_name[] = $data[array_search($lines, $data)]['value'];
						$open_connector = false;
						$get_name = true;
					}
					//When I got his name I replace in page 2 his name, put the type of this connector and page number.
					if($data[array_search($lines, $data)]['tag'] == "TRANSPORTNAME" && $get_name){
						for($i=0;$i<sizeof($connector_name);$i++){
							$this->pages[2]=str_replace("3.".strval($i+1)." ".$connector_name[$i],"3.".strval($i+1)." ".$connector_name[$i]." (".$data[array_search($lines, $data)]['value'].") p".$no_page[$i+3],$this->pages[2]);
						}
						$get_name = false;
					}
				}
				$this->pages[2]=str_replace("4.0 Channel Scripts","4.0 Channel Scripts p".$no_page[sizeof($no_page)-4],$this->pages[2]);
				$this->pages[2]=str_replace("4.1 Deploy Script","4.1 Deploy Script p".$no_page[sizeof($no_page)-2],$this->pages[2]);
				$this->pages[2]=str_replace("4.2 Preprocessor Script","4.2 Preprocessor Script p".$no_page[sizeof($no_page)-4],$this->pages[2]);
				$this->pages[2]=str_replace("4.3 Postprocessor Script","4.3 Postprocessor Script p".$no_page[sizeof($no_page)-3],$this->pages[2]);
				$this->pages[2]=str_replace("4.4 Shutdown Script","4.4 Shutdown Script p".$no_page[sizeof($no_page)-1],$this->pages[2]);
			}
		}
		/*	Other pages - This function will put all data from XML Files in the pdf document and structure it into table.
						  The first loop through a first array where all nodes have been store.
						  The second loop through a second array when i get a node then i get the data about it,
						  i store the name of the node into $tag because i want to know 
						  the type of the node so it's not redundant.
						  The third loop is there to retrieve the first attribute and value.
		*/
		function Create_Page_Others($current_channel,$data){
			global $no_page;
			$open = false;
			$count = 1;
			$first_attr = true;
			$first_table = true;
			$iteration = 0;
			$tag ="";
			$header = "";
			$col_one = "";
			$col_two = "";
			$attributes = array();
			$data_table = array(array());
			$geshi = new GeSHi();
			$pages_destination = array("Sourceconnector", "Destinationconnector", "Connector");
			$pages_scripts = array("Deployscript", "Preprocessingscript", "Postprocessingscript", "Undeployscript");
			$this->SetMargins(20,10,20);
			$this->AddPage();
			foreach ($data as $lines) { 
				foreach ($lines as $node => $node_data) {
					//Test when I have a node with type open and tag Channel so I test if this channel is the channel needed.
					if($node == "type" && $node_data == "open" && $data[array_search($lines, $data)]['tag'] == "CHANNEL"){
						if($count == $current_channel){
							$open = true;
							$no_page[] = $this->PageNo();
						} 	
						else $count++;
					}
					//Collecting the node tag.
					if($node == "tag") $tag = ucfirst(strtolower($node_data));
					//If I'm in an open channel node I collect the data needed.
					if($open){
						//Creating each table when node is open and collect the new $tag of open node.
						if ($node == "type" && $node_data == "open" && $header !== $tag) {
							//To get page number.
							if(in_array($tag, $pages_destination)){
								$no_page[] = $this->PageNo();
							}
							if(!$first_table){
								$this->Create_Table($current_channel, $header, $data_table);
								//Reset the $data_table.
								unset($data_table);
								$data_table = array(array());
							}
							$first_table = false;
							//Storage next header.
							$header = $tag;
						}
						
						//Setting the last table when the first node has been closed.
						if($node == "type" && $node_data == "close" && $data[array_search($lines, $data)]['tag'] == "CHANNEL"){
							$this->Create_Table($current_channel, $header, $data_table);
							unset($data_table);
							$data_table = array(array());
							$open = false;
						}
						//Collecting data when node type is complete.
						if($node == "type" && $node_data == "complete"){
							//To get page number.	
							if(in_array($tag, $pages_scripts)){
								$no_page[] = $this->PageNo();
							}				
							$col_one = ucfirst(strtolower($tag));
							//Check if the node has a value.
							if(array_key_exists('value',$lines)){
								//Check if the node has attributes.
								if(array_key_exists('attributes',$lines)) {
									//If attribute is Encoding and his value is base64.
									if($data[array_search($lines, $data)]['attributes']['ENCODING'] == "base64"){
										//Decode base64.
										$col_two = base64_decode($data[array_search($lines, $data)]['value']);
									}
								//When I have a script (here javascript) I syntax the code with 'geshi.php', but it return HTML code, so I parse the code with the function WriteHTML.
								}elseif(stristr(strtolower($tag), "script")){
									$geshi->set_language('javascript');
									$geshi->set_source($data[array_search($lines, $data)]['value']);
									$geshi->set_language_path('geshi/');
									$geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
									$col_two = $geshi->parse_code();
								}else $col_two = $data[array_search($lines, $data)]['value'];
							}else $col_two = "empty";
						}
						//Management of attributes.
						if ($node == "attributes" && $first_attr) {
							foreach ($node_data as $attribute => $value_attr) {
								$col_one = ucfirst(strtolower($attribute));
								$col_two = $value_attr;
							}
							$first_attr = false;	
						}
						$count++;
					}
				}
				//Adding data in array when the column one is not empty.
				if(!empty($col_one)){
					$data_table[$iteration][] = $col_one;
					$data_table[$iteration][] = $col_two;
					$iteration++;
				}
				$col_one = "";
				$col_two = "";
			}
		}
		/*
			This function manage each table, it put the $data_table in table 
			and it breaks the line of data when it too width also it parse HTML 
			from 'geshi.php' when I have script.
		*/
		function Create_Table($current_channel, $header, $data_table) {	
			global $first_table;
		    // Column width.
		    $col_width = 85;
		    //Data table.
		    if(!empty($data_table)){
		    	//Header.
		    	$this->SetFont('Times','B',12);
		    	$this->SetDrawColor(235,235,235);
		    	$this->SetFillColor(235,235,235);
		    	$this->SetTextColor(0,0,0);
				$this->Cell($col_width+$col_width,7,$header,1,0,'',true);
				if($first_table == 0){
					$this->Ln();
					$first_table++;
				}
				if($first_table == 1 && $current_channel>1){
					$this->Ln();
					$first_table++;
				}
		    	$this->SetFont('Times','',12);
		    	$this->SetFillColor(255,255,255);
		    	foreach($data_table as $lines) {
		    		foreach ($lines as $node => $node_data) {
		    			//If the data is narrower than the column, just print it, else check if it's HTML, else multi-line split. 
		    			if($this->GetStringWidth($node_data)<$col_width){
		    				$this->SetFontSize(12);
		    				$this->Cell($col_width,6,$node_data,1,0,'');
						}else{
							//On line 691,692,705 and 734 in 'fpdf.php' I limit at 100 line break.
							$this->Ln();
							$this->SetFontSize(10);
							if($node_data[0]=='<') $this->WriteHTML($node_data);
							else $this->MultiCell($col_width*2,6,$node_data,1,"L");
		    			}
		    		}
		    		$this->Ln();
		    	}
			}
		    $this->Ln();
		}
		//Footer - to manage the auto page breaker go to line 207 in 'fpdf.php' and change integer value.
		function Footer()
		{
    		//Positioning 1,5 cm of bottom.
    		$this->SetY(-15);
    		//Character Font Arial.
    		$this->SetFont('Arial','',8);
    		//Gray color.
    		$this->SetDrawColor(235,235,235);
    		//Footer line.
    		$this->Line(20,$this->GetPageHeight()-27,$this->GetPageWidth()-20,$this->GetPageHeight()-27);
    		//Ethilog logo.
    		$this->Image('ethilog_logo.png',20,$this->GetPageHeight()-25,30,15);
    		//Page number.
    		$this->Cell(0,0,'Page '.$this->PageNo().' of {nb}',0,0,'R');
    		$this->Ln();
    		//To define timezone.
    		date_default_timezone_set("Europe/Paris");
    		//Report date.
    		$this->Cell(0,0,"Report date: ".date("d-m-Y")." at ".date("h:i:s")." ".date("A"),0,0,'C');
		}
		//This function parse HTML code given by 'geshi.php' and put it in pdf with correct syntax.
		function WriteHTML($html)
		{
		    //HTML parser.
			$html = str_replace('&nbsp;', '', $html);
			$html = html_entity_decode($html,ENT_QUOTES);
		    $html_node = preg_split('/<(.*)>/U',$html,-1,PREG_SPLIT_DELIM_CAPTURE);
		    global $script_lines;
		    foreach($html_node as $key => $text)
		    {
		        if($key%2==0){
		        	//Limitation to the first 100 lines.
		        	if($script_lines<100) $this->Write(6,$text);
		        }
		        else
		        {
		            //Node.
		            if($text[0]=='/') $this->CloseTag(strtoupper(substr($text,1)));
		            else
		            {
		                //Attributes extraction.
		                $attr = array();
		                $all_attr = explode(' ',$text);
		                $tag = strtoupper(array_shift($all_attr));
                    	$all_attr = explode('"',$text);
                   		if(!empty($all_attr[1])) $attr = explode(';',$all_attr[1]);
		                $this->OpenTag($tag,$attr);
		            }
		        }
		    }
		    $script_lines = 1;
		}
		function OpenTag($tag, $attr)
		{
			global $script_lines;
			switch ($tag) {
				case 'PRE':
					$script_lines = 1;
					break;
				case 'DIV':
					if($script_lines<100){
						if($script_lines<10) $space = '     ';
						else $space = '    ';						
						$this->Write(6,$script_lines.'.'.$space);
						$script_lines++;
					}
					break;
				case 'BR':
					$this->Ln(5);	
					break;
				case 'SPAN':
					foreach ($attr as $key => $attribute) {
						$attrpara = explode(':',$attribute);
						if(!empty($attrpara[0])) $type = str_replace(' ', '', $attrpara[0]);
						else $type = '';
						if(!empty($attrpara[1])) $value = str_replace(' ', '', $attrpara[1]);
						else $value = '';
              			switch($type){
                			case 'color':
			                    $rgb = $this->HtmlToRgb($value);
			                    $this->SetTextColor($rgb[0],$rgb[1],$rgb[2]);
			                  break;
			                case 'font-weight':
			                  	switch ($value) {
				                  	case 'normal':
				                  		$this->SetFont('Times');
				                  		break;
				                  	case 'bold':
				                  		$this->SetFont('Times','B');
				                  		break;
			                  	}
			                break;
			                case 'font-style':
			                	if($value == 'italic') $this->SetFont('Times','I');
			                	break;
			            }
			            unset($attrpara);
						$attrpara = array();
					}
					break;
			}
		}
		function CloseTag($tag)
		{
			if($tag == 'DIV') $this->Ln(5);
		    if($tag == 'SPAN'){
		        $this->SetFont('Times','');
		    	$this->SetTextColor(0,0,0);
		    }
		}
		function HtmlToRgb($color)
		{
			if ($color[0] == '#') $color = substr($color, 1);
			if (strlen($color) == 6) list($r, $g, $b) = array($color[0].$color[1], $color[2].$color[3], $color[4].$color[5]);
			elseif (strlen($color) == 3) list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
			else return false;
			$r = hexdec($r); $g = hexdec($g); $b = hexdec($b);
			return array($r, $g, $b);
		}
	}
?>