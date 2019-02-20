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
		protected $no_pages = array();
		protected $connector_name = array();
		protected $script_lines = 1;
		/*
		This function is used to create the first page, I have the title of the pdf document and the name of the channel searched also the page number that have been stored for the second page.
		*/
		function Create_Page_one($filesnames,$current_channel,$data){
			global $first_table;
			global $no_pages;
			global $script_lines;
			global $connector_name;
			$first_table = 0;
			unset($no_pages);
			$no_pages = array();
			unset($connector_name);
			$connector_name = array();
			$script_lines = 1;
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
			foreach ($no_pages as $no) {
				if(!empty($no_pages)){
					$this->Cell(0,10,$channel_name.' '.$no,0,2,'L',true);
				}
			}
			$this->Cell(0,10,$channel_name,0,2,'L',true);
			$this->Image('ethilog_logo.png',$width,$height,80,40);
		}
		/*
		This function is used to create the second page, the channel contents will be resume in this
		and push it in the pdf document, for each data I search the node reference in XML File, it match with tag, type and finally the current channel when I got a channel group. Also I have all the page number for each chapter.
		*/
		function Create_Page_two($current_channel,$data){
			global $connector_name;
			$count = 1;
			$no_connector = 0;
			$open_connector = false;
			$ignore = false;
			$margin = 20;
			$margin_sub = 25;
			$this->SetMargins($margin,30,$margin);
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
			$this->SetX($margin_sub);
			$this->SetFont('Times','',12);
			//To get name connector.
			foreach ($data as $lines) {
				if($data[array_search($lines, $data)]['tag'] == "CHANNELGROUPS" && $data[array_search($lines, $data)]['type']== "open"){
					$ignore = true;
				}else if ($data[array_search($lines, $data)]['tag'] == "CHANNELGROUPS" && $data[array_search($lines, $data)]['type']== "close") {
					$ignore = false;
				}
				if($data[array_search($lines, $data)]['tag'] == "CHANNEL" && $data[array_search($lines, $data)]['type']== "close" && !$ignore){
					$count++;
				}
				if($data[array_search($lines, $data)]['tag'] == "CONNECTOR" && $data[array_search($lines, $data)]['type']== "open"){
					if ($count == $current_channel) {
						$open_connector = true;
					}
				}
				if($data[array_search($lines, $data)]['tag'] == "NAME" && $open_connector){
					$no_connector++;
					$connector_name[$no_connector-1] = "3.".$no_connector." ".$data[array_search($lines, $data)]['value'];
					$this->Cell(0,6,$connector_name[$no_connector-1],0,2,'L');
					$open_connector = false;
				}
			}
			$this->SetFont('Arial','',26);
			//Orange.
			$this->SetTextColor(40,174,163);
			$this->Ln(5);
			$this->SetX($margin);
			$this->Cell(0,15,"4.0 Channel Scripts",0,2,'L');
			//Black.
			$this->SetTextColor(0,0,0);
			$this->SetFont('Times','',12);
			$this->SetX($margin_sub);
			$this->Cell(0,6,"4.1 Deploy Script",0,2,'L');
			$this->Cell(0,6,"4.2 Preprocessor Script",0,2,'L');
			$this->Cell(0,6,"4.3 Postprocessor Script",0,2,'L');
			$this->Cell(0,6,"4.4 Shutdown Script",0,2,'L');
			$this->AliasNbPages();
		}
		/*
		This function is used to change the summary at page two and each header defined by 'Summary', because the only way to know where are the information is to complete the report and as you go collect page numbers. Furthermore to change a previous page the only way is what do we want to change and replace it
		*/
		function Set_Page_Numbers_And_Titles($current_channel,$data){
			global $no_pages;
			global $connector_name;
			$open_source = false;
			$open_connector = false;
			$get_name = false;
			$ignore = false;
			$count = 0;
			$count_source = 1;
			$count_connector = 1;
			$page = 2;
			$res = "";
			$i = 0;
			$titles = array("1.0 Channel Summary","2.0 Source Connector","3.0 Destination Connectors","4.0 Channel Scripts");
			$scripts = array("4.1 Deploy Script", "4.2 Preprocessor Script", "4.3 Postprocessor Script", "4.4 Shutdown Script");
			if(!empty($no_pages)){ 
				//Channel Summary
				while($page < $no_pages[0]){
					$this->pages[$page] = str_replace($titles[0],$this->AlignNo($titles[0],$no_pages[0]),$this->pages[$page],$count);
					if($count == 0){
						$page++;
					}else{
						$page = $no_pages[0];
					}
				}
				$page = 2;
				$count = 0;
				//Destination connectors
				while ($page < $no_pages[0]) {
					$this->pages[$page]= str_replace($titles[2],$this->AlignNo($titles[2],$no_pages[2]),$this->pages[$page],$count);
					if($count == 0){
						$page++;
					}else{
						$page = $no_pages[0];
					}
				}
				$page = 2;
				$count = 0;
				foreach ($data as $lines) {
					if($data[array_search($lines, $data)]['tag'] == "CHANNELGROUPS" && $data[array_search($lines, $data)]['type']== "open"){
						$ignore = true;
					}else if ($data[array_search($lines, $data)]['tag'] == "CHANNELGROUPS" && $data[array_search($lines, $data)]['type']== "close") {
						$ignore = false;
					}
					if(!$ignore){
						if($data[array_search($lines, $data)]['tag'] == "CHANNEL" && $data[array_search($lines, $data)]['type']== "close"){
							$count_source++;
							$count_connector++;
							$i = 0;
						}
						//Test when I have a node with type open and tag SourceConnector so I test if this channel is the channel needed.
						if($data[array_search($lines, $data)]['tag'] == "SOURCECONNECTOR" && $data[array_search($lines, $data)]['type']== "open"){
							if ($count_source == $current_channel){
								$open_source = true;
							}
						}
						//Source Connector : If the source connector is open I could get the value of it.
						if($data[array_search($lines, $data)]['tag'] == "TRANSPORTNAME" && $open_source){
							$res = $titles[1]." (".$data[array_search($lines, $data)]['value'].")";
							while ($page < $no_pages[0]) {
								$this->pages[$page]= str_replace($titles[1],$this->AlignNo($res ,$no_pages[1]),$this->pages[$page], $count);
								if($count == 0){
									$page++;
								}else{
									$page = $no_pages[0];
								}
							}
							$page = 2;
							$count = 0;
							$open_source = false;
						}
						//Test when I have a node with type open and tag Connector so I test if this channel is the channel needed.
						if($data[array_search($lines, $data)]['tag'] == "CONNECTOR" && $data[array_search($lines, $data)]['type']== "open"){
							if ($count_connector == $current_channel){
								$open_connector = true;
							}
						}
						//Connector : So when I get the name connector, I can have the type of this one and put each connector in the summary page two.
						if($data[array_search($lines, $data)]['tag'] == "TRANSPORTNAME" && $open_connector){
							$res = $connector_name[$i]." (".$data[array_search($lines, $data)]['value'].")";
							while ($page < $no_pages[0]) {
								$this->pages[$page]= str_replace($connector_name[$i],$this->AlignNo($res, $no_pages[$i+3]),$this->pages[$page], $count);
								if($count == 0){
									$page++;
								}else{
									$page = $no_pages[0];
								}
							}
							$page = 2;
							$count = 0;							
							$i++;
							$open_connector = false;					
						}
					}
				}
				//Channel Scripts
				while ($page < $no_pages[0]) {
					$this->pages[$page]= str_replace($titles[3],$this->AlignNo($titles[3],$no_pages[sizeof($no_pages)-5]),$this->pages[$page]);
					if($count == 0){
						$page++;
					}else{
						$page = $no_pages[0];
					}
				}
				$page = 2;
				$count = 0;
				//Deploy Script
				while ($page < $no_pages[0]) {
					$this->pages[$page]= str_replace($scripts[0],$this->AlignNo($scripts[0],$no_pages[sizeof($no_pages)-3]),$this->pages[$page]);
					if($count == 0){
						$page++;
					}else{
						$page = $no_pages[0];
					}
				}
				$page = 2;
				$count = 0;
				//Preprocessor Script
				while ($page < $no_pages[0]) {
					$this->pages[$page]= str_replace($scripts[1],$this->AlignNo($scripts[1],$no_pages[sizeof($no_pages)-5]),$this->pages[$page]);
					if($count == 0){
						$page++;
					}else{
						$page = $no_pages[0];
					}
				}
				$page = 2;
				$count = 0;
				//Postprocessor Script
				while ($page < $no_pages[0]) {
					$this->pages[$page]= str_replace($scripts[2],$this->AlignNo($scripts[2],$no_pages[sizeof($no_pages)-4]),$this->pages[$page]);
					if($count == 0){
						$page++;
					}else{
						$page = $no_pages[0];
					}
				}
				$page = 2;
				$count = 0;
				//Shutdown Script
				while ($page < $no_pages[0]) {
					$this->pages[$page]= str_replace($scripts[3],$this->AlignNo($scripts[3],$no_pages[sizeof($no_pages)-2]),$this->pages[$page]);
					if($count == 0){
						$page++;
					}else{
						$page = $no_pages[0];
					}
				}
				$page = 2;
				$count = 0;
			}
			//This function has some bugs so if you want to try it and correct it, you can, just uncomment the function Header.
			//$this->Replace_Each_Header($connector_name, $titles);
		}
		/*	Other pages - The first loop through a first array where all nodes have been store.
						  The second loop through a second array when i get a node then i get the data about it,
						  i store the name of the node into $tag because i want to know 
						  the type of the node so it's not redundant.
						  The third loop is there to retrieve the first attribute and value.
		*/
		function Create_Page_Others($current_channel,$data){
			global $no_pages;
			$open = false;
			$ignore = false;
			$count = 1;
			$first_attr = true;
			$first_table = true;
			$first_connector = true;
			$needed = true;
			$break_page = false;
			$channel_script = true;
			$iteration = 0;
			$i_no_pages = 0;
			$tag ="";
			$header = "";
			$col_one = "";
			$col_two = "";
			$attributes = array();
			$data_table = array(array());
			$geshi = new GeSHi();
			$pages_destination = array("Sourceconnector", "Destinationconnectors", "Connector");
			$pages_scripts = array("Deployscript", "Preprocessingscript", "Postprocessingscript", "Undeployscript");
			$filter = array("empty","Inactivedays","Channeltags","Channeltag","Channelids","Backgroundcolor");
			$this->SetMargins(20,10,20);
			$this->AddPage();
			foreach ($data as $lines) { 
				foreach ($lines as $node => $node_data) {
					if($node == "type" && $node_data == "open" && $data[array_search($lines, $data)]['tag'] == "CHANNELGROUPS"){
						$ignore = true;
					}else if ($node == "type" && $node_data == "close" && $data[array_search($lines, $data)]['tag'] == "CHANNELGROUPS") {
						$ignore = false;
					}
					//Test when I have a node with type open and tag Channel so I test if this channel is the channel needed.
					if($node == "type" && $node_data == "open" && $data[array_search($lines, $data)]['tag'] == "CHANNEL" && !$ignore){
						if($count == $current_channel){
							$open = true;
							$no_pages[$i_no_pages] = $this->PageNo();
							$i_no_pages++;
						} 	
						else $count++;
					}
					//Collecting the node tag.
					if($node == "tag") $tag = ucfirst(strtolower($node_data));
					//If I'm in an open channel node I collect the data needed.
					if($open){
						if(in_array($tag, $pages_destination)){
							$no_pages[$i_no_pages] = $this->PageNo();
						}
						//Creating each table when node is open and collect the new $tag of open node.
						if ($node == "type" && $node_data == "open" && $header !== $tag && !in_array($tag, $filter)) {
							if(in_array($tag, $pages_destination)){
								if($tag == "Connector" && $first_connector){
									$break_page = false;
								}else $break_page = true; 
							}
							if(!$first_table){
								$this->Create_Table($current_channel, $header, $data_table);
								//To break page I add a new page and I get the current page number
								if($break_page){
									$this->AddPage();
									$no_pages[$i_no_pages] += 1;
									$i_no_pages++;
									$break_page = false;
								//For the first connector, I increment the index of page number, because I don't want to break a page just after destination connector, I want to get their page numbers.
								}else if($tag == "Connector" && $first_connector){
									$i_no_pages++;
									$first_connector = false;
								}
								//Reset the $data_table
								unset($data_table);
								$data_table = array(array());
							}
							$first_table = false;
							$needed = true;
							//Storage next header.
							$header = $tag;
						}else if(in_array($tag, $filter)){
							$needed = false;
						}
						
						//Setting the last table when the first node has been closed.
						if($node == "type" && $node_data == "close" && $data[array_search($lines, $data)]['tag'] == "CHANNEL"){
							$this->Create_Table($current_channel, $header, $data_table);
							unset($data_table);
							$data_table = array(array());
							$open = false;
							$no_pages[] = $this->PageNo();
							$count++;
						}
						//Collecting data when node type is complete.
						if($node == "type" && $node_data == "complete"){	
							if(in_array($tag, $pages_scripts)){
								if($channel_script){
									$this->AddPage();
									$channel_script = false;
								}
								$no_pages[] = $this->PageNo();
							}				
							$col_one = $tag;
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
					}
				}
				//Adding data in array when the column one is not empty.
				if(!empty($col_one) && !in_array($col_two, $filter) && $needed){
					if($col_one == "Time"){
						$col_two = date('d/m/Y H:i:s', substr($col_two, 0, 10));
					}
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
		    //print_r($data_table);
		    if(sizeof($data_table)>1){
		    	//Header.
		    	$this->SetFont('Times','B',12);
		    	//Gray color.
		    	$this->SetDrawColor(215,215,215);
		    	$this->SetFillColor(215,215,215);
		    	//Black color.
		    	$this->SetTextColor(0,0,0);
				$this->Cell($col_width+$col_width,7,$header,1,0,'',true);
				if($first_table == 0){
					$this->Ln();
					$first_table++;
				}
		    	$this->SetFont('Times','',12);
		    	//White color.
		    	$this->SetFillColor(255,255,255);
		    	foreach($data_table as $lines) {
		    		foreach ($lines as $node => $node_data) {
		    			//If the data is narrower than the column, just print it, else check if it's HTML, else multi-line split. 
		    			if($this->GetStringWidth($node_data)<$col_width){
		    				$this->SetFontSize(12);
		    				$this->Cell($col_width,6,$node_data,1,0,'');
						}else{
							//On line 693,694,707 and 736 in 'fpdf.php' I limit at 100 line break.
							$this->Ln();
							$this->SetFontSize(10);
							if($node_data[0]=='<') $this->WriteHTML($node_data);
							else $this->MultiCell($col_width*2,6,$node_data,1,"L");
		    			}
		    		}
		    		$this->Ln();
		    	}
			}else{
				$this->SetFont('Arial','',18);
		    	$this->SetDrawColor(255,255,255);
		    	//Cyan.
		    	$this->SetTextColor(40,174,163);
				$this->Cell($col_width+$col_width,10,$header,1,0,'',true);
			}
		    $this->Ln();
		}
		/*Header
		function Header(){
    		//Character Font Arial.
		    $this->SetFont('Arial','',16);
		    //Title
		    $this->SetTextColor(255,100,0);
		    $this->Cell(170,10,'Summary',0,0,'L');
		    $this->Ln(10);
		}*/
		//Replace Header : This function has some bugs so if you want to try it and correct it, you can, just uncomment the funcion Header.
		function Replace_Each_Header($connector_name, $titles){
			global $no_pages;
			$index = 1;
			$name = 0;
			$title = 0;
			$skip_destination = true;
			if(!empty($no_pages)){
				for ($page = $no_pages[0]; $page <= $no_pages[sizeof($no_pages)-1]; $page++) { 
					//If the current page is greater than the title page number, change title if we have something else the destination connector, change title page number.
					if(!($no_pages[$index] > $page)){
						//If it's the destination connector just change the connector name.
						if($titles[$title] == "3.0 Destination Connectors" && sizeof($connector_name)-1>$name){
							$name++;
						}else if($title != sizeof($titles)-1){
							$title++;
						}				
						$index++;
					}
					if($titles[$title] == "3.0 Destination Connectors"){
						if($name == 0 && $skip_destination){
							$index+=1;
							$skip_destination = false;
						}
						if(sizeof($connector_name)>$name){
							$this->pages[$page] = str_replace('Summary', $titles[$title]." : ".$connector_name[$name], $this->pages[$page]);
						}
					}else{
						$this->pages[$page] = str_replace('Summary', $titles[$title], $this->pages[$page]);
					}
				}
			}
		}
		//Footer - to manage the auto page breaker go to line 207 in 'fpdf.php' and change integer value.
		function Footer()
		{
    		//Positioning 1,5 cm of bottom.
    		$this->SetY(-15);
    		//Character Font Arial.
    		$this->SetFont('Arial','',8);
    		//Gray color.
    		$this->SetDrawColor(215,215,215);
    		//Black color.
    		$this->SetTextColor(0,0,0);
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
		        	$this->Write(6,$text);
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
					if($script_lines<10) $space = '      ';
					else if($script_lines<100) $space = '     ';
					else if($script_lines<1000) $space = '   ';
					else if($script_lines<10000) $space = '  ';
					else if($script_lines<100000) $space = ' ';
					$this->Write(6,$script_lines.'.'.$space);
					$script_lines++;
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
			$r = hexdec($r); 
			$g = hexdec($g); 
			$b = hexdec($b);
			return array($r, $g, $b);
		}
		//Align page numbers.
		function AlignNo($title, $no_page){
			$space = " ";
			//Set font size to adjust the alignment.
			if($title[2] == 0) $this->SetFontSize(37);
			else $this->SetFontSize(16);
			//Define a limit for each title with page width minus size of title and page number 
			$limit = $this->GetPageWidth()-$this->GetStringWidth($title.$no_page);
			//While we are below the limit add space, if we have a subtitle add point.
			while($this->GetStringWidth($space) < $limit){
				$space .= ' ';
				if($title[2] !== '0') $space .= '.';	
			}
			return $title.$space.$no_page;
		}
	}
?>