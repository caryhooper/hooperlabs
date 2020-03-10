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
            </div>
        </div>

        <div class="dropdown">
            <button onclick="showOptions2()" class="dropbtn2">
                Active Directory Attacks
            </button>
            <div id="menuDropdown2" class="dropdown-content">
                <a href="/ad/kerberoast.php">Kerberoast (under construction)</a>
                <a href="#">DC Sync (todo)</a>
                <a href="#">NTDS.dit Attacks (todo)</a>
                <a href="#">Unconstrained Delegation (todo)</a>
            </div>
        </div>

        <a href="/about.php">About</a>
        <a href="/links.php">Links</a>
    </div>
</div>
';
?>
