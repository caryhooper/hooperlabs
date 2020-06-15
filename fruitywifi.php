<html>

<?php include 'header.php';?>

<body>

<?php include 'navbar.php';?>

<div class="body-content">
    <h2>
        Raspberry Pi WiFi Pineapple (with FruityWiFi)
    </h2>
    <h3>(and the MitM Bootstrap Vulnerability)</h3>
    <div class="center">
        <img class="headerpic" src="https://www.raspberrypi.org/app/uploads/2011/10/Raspi-PGB001.png" alt="Hack the Planet" height="50px" width="50px" align="middle">
    </div>
    <p class="date">2020-01-20</p>
    <h3>Equipment</h3>
    <p class="text">
    For an upcoming Man-in-the-Middle (MitM) demonstration, I needed to create a portable device.  This device needed to be capable of hosting a WiFi network while simluataneously being connected to one.
    After some research, I concluded that my Raspberry PI 2 with the FruityWiFi framework would be enough to PWN devices.
    </p>
    <p class="text">
        Hardware:
    </p>
    <ul>
    <li>1x Raspberry Pi 2 (Model B / v1.1)</li>
    <li>2x Panda PAU06 Wifi Adapter (one standard, one extended range)</li>
    <li>1x 128GB MicroSD Card</li>
    <li>1x MicroUSB Power adapter (5V 2A)</li>
    </ul>
    <h3>First Steps</h3>
        <p class="text">
            My limited knowledge of Raspberry Pi caused this project to be a bit tricky.  First, we had to select an operating system. 
            There were many to choose from including Raspbian, Ubuntu MATE, Kali, NOOBS, and many more.  After trial and error, I landed on a Raspbian distribution.
            I was most comfortable with a Debian-variant OS anyway, especially when it came to wireless networking. 
        </p>
        <p class="text">
            I downloaded the operating system from raspberrypi.org <a href="https://www.raspberrypi.org/downloads/raspbian/">(link)</a>.
            I prefer downloading it via Torrent if possible as I believe its faster and much more reliable, but you should do what you want.  After all, you're an adult probably.
        </p>
        <p class="text">
            In my opinion, the toughest part is connecting the Pi to WiFi.  In the past, I have installed NOOBS, connected the Pi to a monitor via HDMI, connected a wirelesss mouse/keyboard,
            and troubleshot the WiFi from there.  One time I even connected an Ethernet cable, which worked but was cumbersome.  WiFi is SO much <i>cleaner</i>.
            After more research, I found that others had the same issues and that it may be possible (using builtin Raspberry Pi functionality) install the OS and connect to Wifi simulataneously.
            Ideally, I wanted to flash an SD card, write a bootable OS to it, have it automatically install the OS, recognize the WiFi adapter, connect to a router, and then open an SSH service.
            This was more difficult than I anticipated but the following steps eventually worked for me.
        </p>
        <p class="text">
            From 0 to SSH:
        </p>
        <ol>
            <li>Format the MicroSD card.  I did this in Windows (right click --> Format --> Quick Format)</li>
            <li>Use a program such as Rufus or Etcher to write the Rasbian OS to the MicroSD card.</li>
            <li>Once written, a few changes need to be made to connect to WiFi and open the SSH service.  Create an empty file named "ssh" on the SD card. (Write-Output "" > D:\ssh OR touch /media/microsd/ssh)</li>
            <li>Scruss wrote about this on <a href="https://raspberrypi.stackexchange.com/questions/10251/prepare-sd-card-for-wifi-on-headless-pi">stackexchange.com</a>.  Create a wpa_supplicant.conf file in the /boot directory. </li>
        </ol>


    </div>

</body>

<?php include 'footer.php';?>

</html>
