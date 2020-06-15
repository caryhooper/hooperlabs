<?php echo '

<div class="title_pic">
    <img src="/img/logo.jpg" width="150px">
</div>
<div class="title_box">
    <div class="title_text">
        <h1>
        Hooper Labs
        </h1>
    </div>

    <div class="navbar">
        <a href="/index.php">Home</a>

        <div class="dropdown">
            <button onclick="showOptions()" class="dropbtn">
                Boot to Root
            </button>
            <div id="menuDropdown" class="dropdown-content">
                <a href="/b2r/nightfall.php">Nightfall</a>
                <a href="/b2r/weakness.php">Weakness</a>
                <a href="/b2r/stapler1.php">Stapler 1</a>
                <a href="/b2r/mytomcathost.php">My Tomcat Host</a>
                <a href="/b2r/inclusiveness.php">Inclusiveness</a>
            </div>
        </div>
        <a href="/cheatsheets.php">Cheat Sheets</a>
        <div class="dropdown">
            <button onclick="showOptions2()" class="dropbtn2">
                Mobile
            </button>
            <div id="menuDropdown2" class="dropdown-content">
                <a href="/mobile/uncrackable1.php">OWASP Uncrackable 1</a>
            </div>
        </div>
        <a href="/about.php">About</a>
        <a href="/links.php">Links</a>
    </div>
</div>
';
?>
