<html>

<?php include 'header.php';?>

<body>

<?php include 'navbar.php';?>

    <div class="body-content">
        <h3>
            Links for Future Learning
        </h3>
        <p class="text">
            <ul>
            <?php
            #Description --> Link
            $links = [
                "Leveraging INF-SCT Fetch & Execute Techniques For Bypass, Evasion, & Persistence (bohops)" => "https://bohops.com/2018/02/26/leveraging-inf-sct-fetch-execute-techniques-for-bypass-evasion-persistence/",
                "A Case Study in Wagging the Dog: Computer Takeover (@harmj0y)" => "https://www.harmj0y.net/blog/activedirectory/a-case-study-in-wagging-the-dog-computer-takeover/",
                "The NOP Sled (g00se)" => "http://thenopsled.com/",
                "Kellgon: The Hacker Tutorial (@secure_perry)" => "https://kellgon.com/",
                "Rainier Cyber (bsod_steve @diodepack)" => "https://www.rainiercyber.com/",
                "Step by step guide to Linux Kernel Exploitation (@LexfoSecurite)" => "https://blog.lexfo.fr/cve-2017-11176-linux-kernel-exploitation-part1.html",
                "Socket Reconstruction Technique for BOF" => "https://barriersec.com/2019/05/exploiting-vulnserver-trun-command-using-socket-reconstruction-technique/",
                
                "Native VBA Reverse Shell (John Woodman @JohnWoodman15)" => "https://john-woodman.com/research/malicious-vba-macros-trials-tribulation",
                "Reverse Engineering Course (courtesy of @mytechnotalent)" => "https://github.com/mytechnotalent/Reverse-Engineering",
                "Exploiting System Mechanic Driver (Paolo Stagno aka VoidSec)" => "https://voidsec.com/exploiting-system-mechanic-driver/"
            ];
            foreach ($links as $desc => $link) {
                echo "<li><a href=\"{$link}\">{$desc}</a></li>";
            }
            ?>
            </ul>
        </p>
        <br />
    </div>
</body>
<?php include 'footer.php';?>

</html>