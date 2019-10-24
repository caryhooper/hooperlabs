<?php echo '<h1>
        Welcome to Hooper Labs
    </h1>
    <br />
    <div class="navbar">
        <a href="/index.php">Home</a>
        <div class="dropdown">
            <button onclick="showOptions()" class="dropbtn">
                Boot to Root
            </button>
            <div id="menuDropdown" class="dropdown-content">
                <a href="/b2r/nightfall.php">Boot 2 Root - Nightfall</a>
                <a href="/b2r/weakness.php">Boot 2 Root - Weakness</a>
            </div>
        </div>
        <div class="dropdown">
            <button onclick="showOptions2()" class="dropbtn2">
                Active Directory Attacks
            </button>
            <div id="menuDropdown2" class="dropdown-content2">
                <a href="/ad/kerberoast.php">Kerberoast</a>
                <a href="#">DC Sync</a>
                <a href="#">NTDS.dit Attacks</a>
                <a href="#">Unconstrained Delegation</a>
            </div>
        </div>
        <a href="/about-me.php">About Me</a>
        <a href="/links.php">Links</a>
    </div>';
?>