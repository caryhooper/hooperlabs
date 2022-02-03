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
//whitelist recipe files
$files = scandir("recipes");
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
	print("<h2>List of Recipes</h2>");
	$files = scandir("recipes");
	print("<ul>");
	// foreach ($files as &$file){
		
	// }
	//Loop through each file in array & look for ".md"
	foreach ($files as &$file){
		//Find all files with ".md" in them.  
        $file = escape_evil($file);
		if (strpos($file,".md")){
            if (strpos($file,"emplate") == false){
                //print($file." doesn't contain Template.");
                $recipe = explode(".",$file)[0];
                print("<li>"."<a class='recipes' href='recipes.php?recipe=".$recipe."'>");
                $firstline = fgets(fopen("recipes/".$file,'r'));
                $title = str_replace("#","",$firstline);
                print($title."</a></li>");
            }
		}
	}
	print("</ul>");
}

//Parses through notes file and prints notes in a prettified (subjective) format.
//Uses Parsedown
function printRecipe($recipe){
    $Parsedown = new ParsedownExtraPlugin();
    $Parsedown->setMarkupEscaped(true);
    $Parsedown->setSafeMode(true);
    //{.foo} → <tag class="foo">

	//print("<h2>".$recipe."</h2><br /><br />\n");
    // echo $Parsedown->text('Hello_Parsedown_!');
    $file = file_get_contents("recipes/".$recipe.".md");
    echo $Parsedown->text($file);

    echo "</br>";
}

//Main program logic.  Checks to see if we should return the table of contents or the cheat sheet.
//TODO Clickable list of all keys?
//TODO Searchable?
$user_input = $_GET['recipe'];
if (isset($user_input) && !empty($user_input)){
	//security whitelist check
	if (in_array($user_input,$whitelist)){
		printRecipe($user_input);
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