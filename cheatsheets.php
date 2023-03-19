<html>

<?php include 'header.php';?>

<body>

<?php include 'navbar.php';?>

    <div class="body-content">
<?php 
    include 'lib/Parsedown.php';  //https://raw.githubusercontent.com/erusev/parsedown/master/Parsedown.php
    include 'lib/ParsedownExtra.php'; //https://raw.githubusercontent.com/erusev/parsedown-extra/master/ParsedownExtra.php
    include 'lib/ParsedownExtraPlugin.php';  //https://raw.githubusercontent.com/taufik-nurrohman/parsedown-extra-plugin/main/ParsedownExtraPlugin.php
?> 
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

	//Loop through each file in array & look for ".md"
	foreach ($files as &$file){
		//Find all files with ".md" in them.  
        $file = escape_evil($file);
		if (strpos($file,".md")){
            if (strpos($file,"ormat") == false){
                //print($file." doesn't contain format.");
                $recipe = explode(".",$file)[0];
                print("<li>"."<a class='recipes' href='cheatsheets.php?topic=".$recipe."'>");
                $handle = fopen("cheatsheets/".$file,'r');
                $firstline = fgets($handle);
                $title = str_replace("#","",$firstline);
                print($title."</a></li>");
                fclose($handle);
            }
		}
	}
	print("</ul>");
}


//Uses Parsedown
function printNotes($topic){
	$Parsedown = new ParsedownExtraPlugin();
    $Parsedown->setMarkupEscaped(true);
    $Parsedown->setSafeMode(true);

    $file = file_get_contents("cheatsheets/".$topic.".md");
    echo $Parsedown->text($file);

    echo "</br>";
}

//Main program logic.  Checks to see if we should return the table of contents or the cheat sheet.
//TODO - put references at bottom
//Clickable list of all keys?
//Searchable?

if(isset($_GET['topic']) && !empty($_GET['topic']))
{
	//security whitelist check
	if (in_array($_GET['topic'],$whitelist)){
		printNotes($_GET['topic']);
	}
	else{
		//Silent Error
		printList();
		//print("<h3><b>Error! Cheat sheet does not exist!</b></h3>");

	}
}
else {
	printList();
}
?>

    </div>
</body>

<?php include 'footer.php';?>
</html>
