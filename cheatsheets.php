<html>

<?php include 'header.php';?>

<body>

<?php include 'navbar.php';?>

    <div class="body-content">

<?php 
//Development for presentation of cheat sheets.
//1. Locate each file in cheatsheet directory.
//2. Check each line of each .txt file.
//3. Parse file
//4. Print each to the page.

//Populate whitelist in PHP.  This is probably safe.
$files = scandir("cheatsheets");
$whitelist = (array) null;
foreach ($files as &$file){
	$file = explode('.',$file)[0];
	array_push($whitelist,$file);
}

function escape_evil($string){
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

//Prints the list of all notes files within cheatsheets directory.
function printList(){
	//Look at each file within directory.  Returns an array
	print("<h2>List of Cheat Sheets</h2>");
	$files = scandir("cheatsheets");
	print("<ul>");
	foreach ($file as &$file){
		$file = escape_evil($file);
		print($file."\n");
	}
	//Loop through each file in array & look for ".txt"
	foreach ($files as &$file){
		//Find all files with ".txt" in them.  
		if (strpos($file,".txt")){
			$topic = explode(".",$file)[0];
			print("<li>"."<a class='cheatsheet' href='cheatsheets.php?topic=".$topic."'>");
			$firstline = fgets(fopen("cheatsheets/".$file,'r'));
			$title = str_replace("####","",$firstline);
			$title = str_replace("----","",$title);
			$title = escape_evil($title);
			print($title."</a></li>");
		}
	}
	print("</ul>");
}

//Parses through notes file and prints notes in a prettified (subjective) format.
//Features: checks for title (^####), subtitle (^##), note item (----), multiline note item (----""")
//, and sublist (----##foo;bar;foobar)
function printNotes($topic){
	//print("<h2>".$topic."</h2><br /><br />\n");
	$f = fopen("cheatsheets/".$topic . ".txt",'r');
	while(! feof($f) ){

		//grab next line
		$nextline = fgets($f);
		
		//check if line is a title
		if (substr($nextline,0,4)==="####"){
			$title = str_replace("####","",$nextline);
			$title = escape_evil($title);
			print("<h2>".$title."</h2>");
		}
		//Check to see if line is a subtitle
		elseif (substr($nextline,0,2)==='##'){
			$subtitle = str_replace("##","",$nextline);
			$subtitle = escape_evil($subtitle);
			print("<h3>".$subtitle."</h3>");
		}
		else{
			//explode on ---- delimiter
			$nextline_ex = explode("----",$nextline);
			//print the heading
			print("<p class=\"heading\">".$nextline_ex[0]);
			//check for nonzero content right of the delimiter
			$content = $nextline_ex[1];
			if (strlen($content) > 2){
				//check for multiline content denoted by """
				//check if line begins with triple quotes
				if (substr($content,0,3) === "\"\"\""){
					print("<pre>\t");
					do {
						//print the content without """
						$content = str_replace("\"\"\"", "", $content);
						$content = escape_evil($content);
						print(" ".$content." ");
						//look at next line
						$content = fgets($f);
						//loop stops if the next line ends with """, otherwise, go to next line
						//Note: it would be better to strip all white space at end.  I'm sure PHP has a function like this.
					}while (substr($content,-4,-1) != "\"\"\"");
					//remove """
					$content = str_replace("\"\"\"", "", $content);
					$content = escape_evil($content);
					print($content."</pre></p>\n");
				}
				//Check for multiline / sublist (##)
				elseif(substr($content,0,2) === "##"){
					//remove markup
					$content = str_replace("##","",$content);
					//ignore escaped semicolons.
					$content = str_replace("\\;","####",$content);
					//Create an array of items that were separated by semicolon
					$content_ex = explode(";", $content);
					print("<pre><ul>");
					//loop through each subitem and insert into unordered list
					foreach($content_ex as $subitem){
						if (strlen($subitem) > 2){
							//Replace escaped semicolons and print subitem as list item.
							$subitem = str_replace("####",";",$subitem);
							$subitem = escape_evil($subitem);
							print("<li>".$subitem."</li>");
						}
					}
					print("</ul></pre></p>\n");
				}
				else{
					$content = escape_evil($content);
					print("<pre>\t".$content."</pre></p>\n");
				}
			}
		}
	}
	fclose($f);
}

//Main program logic.  Checks to see if we should return the table of contents or the cheat sheet.
//TODO - put references at bottom
//Clickable list of all keys?
//Searchable?
$user_input = $_GET['topic'];
if (isset($user_input) && !empty($user_input)){
	//security whitelist check

	if (in_array($user_input,$whitelist)){
		printNotes($user_input);
	}
	else{
		//Silent Error
		printList();
		//print("<h3><b>Error! Cheat sheet does not exist!</b></h3>");

	}
}
else{
	printList();
}
?>

    </div>
</body>

<?php include 'footer.php';?>
</html>