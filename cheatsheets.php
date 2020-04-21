<html>

<?php include 'header.php';?>

<body>

<?php include 'navbar.php';?>

    <div class="body-content">


<?php 
//Development for presentation of cheat sheets.
//1. Locate each file in cheatsheet directory.
//2. Check each line of each .txt file.
//3. parse string by "----"
//4. Echo each to the page.

// for i in $(ls); do word=$(echo -n $i | cut -d '.' -f1); echo -n \"$word\",; done
$whitelist = array("ad","android","asm","bash","bpf","compilingC","csharp","ctf","gdb","hashes","icacls","kalitools","linux-enum","mitm","msf","networking","osint","portscan","powershell","reg","smb","smtp","snmp","sockets","tmux","web","windows-enum");

function printList(){
	//Look at each file within directory.  Returns an array
	print("<h2>List of Cheat Sheets</h2>");
	$files = scandir("cheatsheets");
	print("<ul>");
	foreach ($file as &$file){
		print($file."\n");
	}
	//Loop through each file in array & look for ".txt"
	foreach ($files as &$file){
		//Find all files with ".txt" in them.  
		if (strpos($file,".txt")){
			print("<li>");
			print("<a class='cheatsheet' href=\"/");
			$topic = explode(".",$file)[0];
			print("cheatsheets.php?topic=");
			print($topic);
			print("\">");
			$firstline = fgets(fopen("cheatsheets/".$file,'r'));
			$firstline_ex = explode("----",$firstline);
			$title = $firstline_ex[0];
			print($title);
			print("</a>");
			print("</li>");
		}
		print("\n");
	}
	print("</ul>");
}

function printNotes($topic){
	print("<h2>".$topic."</h2><br /><br />\n");
	$f = fopen("cheatsheets/".$topic . ".txt",'r');
	while(! feof($f) ){
		//ToDo... include handling for multiline.

		$nextline = fgets($f);
		$nextline_ex = explode("----",$nextline);
		print("<p class=\"heading\">".$nextline_ex[0]);
		$linelen = strlen($nextline_ex[1]);
		//print("Line Length: ".$linelen);
		if ($linelen > 2){
			if (strpos("\"\"\"",$nextline_ex[1])){
				print("Multiline detected.");
			}
			else{
				print("<pre>\t".$nextline_ex[1]."</pre></p>\n");
			}
		}
	}
	fclose($f);
}

//Main program logic.  Checks to see if we should return the table of contents or the cheat sheet.
//TODO - put reference at bottom, Switch First line + Title thing.  
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
		//printList();
		print("<h3><b>Error! Cheat sheet does not exist!</b></h3>");

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