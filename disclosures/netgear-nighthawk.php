<html>

<?php include '../header.php';?>

<body>

<?php include '../navbar.php';?>

<div class="body-content">
    <h2>
        Stored XSS in NETGEAR Nighthawk C7000v2 Administration Interface (V1.02.09)
    </h2>
    <div class="center">
        <img class="headerpic" src="https://www.netgear.com/images/HomePage/RBU/home-rbu-my-netgear-medium.png" alt="Hack the Planet" align="middle">
    </div>
    <p class="date">2020-02-09</p>
    <h3>Background</h3>
    <p class="text">
        NETGEAR Nighthawk AC1900 is a SOHO router.  It features a web (HTTP) administration panel accessible from the LAN to setup Wifi Networks, perform security functions, and unlock additional features.  
        It appeared that firmware updates were controlled by the ISP and each ISP has their own flavor of firmware, presumably so they can interface with their respective device "backdoor".
    </p>
    <p class="text">
        Purchase Link: <a href="https://www.netgear.com/home/products/networking/wifi-routers/R7000.aspx">AC1900 Nighthawk WiFi Router</a>
    </p>

        <h4>Vulnerability - Stored Cross-Site Scripting (XSS)</h4>
        <p class="text">
            A Stored XSS vulnerability was identified in the most recent NETGEAR web administration console within the "diagnostics" functionality, allowing an attacker to send a request to the application, 
            which was later returned as JavaScript to any victim browsing the page.  The application failed to sufficiently sanitize the TracerouteHost parameter within the "/goform/Diagnostics" endpoint, 
            placing it permanently into the page's response for all users.  Unsuccessful exploitation attempts may break the functionality of the endpoint.
        </p>
        <h4>Steps:</h4>
        <ol>
            <li>Authenticate to the router's web administration interface.</li>
            <li>Click on "ADVANCED".  Then, under the "Administration" menu, click on "Diagnostics".</li>
            <li>Within the "Utility" dropdown, select "Traceroute".  Then, input either of the following payloads: 
                <ul>
                    <li>127.0.0.1';alert("XSS in Traceroute Target");//</li>
                    <li>127.0.0.1;&lt;/script&gt;&lt;script&gt;alert("XSS in Traceroute Target")&lt;/script&gt;&lt;script&gt;</li>
                </ul>
            </li>
            <li>When the page reloads, observe that JavaScript executes on the page.</li>
            <li>Optionally, use a different browser (with a cleared cache) to navigate to the "Diagnostics" page.  When the page loads, JavaScript will also execute, demonstrating that the XSS is stored within the page.</li>
        </ol>
        <img class="body-img" src="/img/netgear1.PNG" width="50%">
        <h4>Versions:</h4>
        <ul>
            <li>Firmware - V1.02.09</li>
            <li>Router Model - C7000v2</li>
        </ul>

    </div>

</body>

<?php include '../footer.php';?>

</html>
