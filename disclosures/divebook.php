<html>

<?php include '../header.php';?>

<body>

<?php include '../navbar.php';?>

<div class="body-content">
    <h2>
        Multiple Vulnerabilities in DiveBook WordPress Plugin (v.1.1.4)
    </h2>
    <div class="center">
        <img class="headerpic" src="http://pluginsroom.com/assets/img/wordpress-plugins.png" alt="Hack the Planet" height="100px" width="100px" align="center">
    </div>
    
    <p class="date">2020-09-15</p>
    <h3>Background</h3>
    <p class="text">
        The DiveBook plugin for WordPress was prone to a multiple vulnerabilities including SQL injection, cross-site scripting, and improper authorization (CVE-2020-14205, CVE-2020-14206, CVE-2020-14207). 
        An attacker could leverage these issues to dump the database including administrative user credentials, to steal cookie-based authentication credentials, or launch other attacks.  Other versions are likely affected, though they were not tested. 
    </p>
    <p class="text">
        Download Link: <a href="https://downloads.wordpress.org/plugin/divebook.1.1.4.zip">DiveBook</a>
    </p>
    <p class="text">
        Vulnerable version: v.1.1.4.  
    </p>
        <h4>Vulnerability - CVE-2020-14207</h4>
        <p class="text">
            A SQL injection vulnerability exists within the DiveBook log's filter functionality. Though the plugin escapes some user input (quotes), complete compromise of the application is possible by injecting the "filter_diver" GET parameter. 
        </p>
        <h4>Steps:</h4>
        <ol>
            <li>Identify a WordPress application with the DiveBook (v.1.1.4) plugin installed (hxxps://example.wordpress.com).</li>
            <li>Locate a page with the DiveBook content included.  Note: within the WordPress page editor interface, "[divebook]divebook_display();[/divebook]" will be included.</li>
            <li>Browse to a URL triggering the "filter" functionality and injecting into the back-end SQL statement: hxxps://example.wordpress.com/?divelog_page=1&scrolled=456&filter_diver=0%20UNION%20SELECT%201,2,version(),4,5,6,7,8,9,10,11,12,13;%20--</li>
            <li>Observe that the MySQL version is returned within the page.</li>
        </ol>
        <img class="body-img" src="/img/divebook_SQLi.png" width="60%">
        <p class="text">
            The screenshot above demonstrates the attack and MySQL version returned in the page.
        </p>

        <h4>Vulnerability - CVE-2020-14206</h4>
        <p class="text">
            A reflected cross-site scripting vulnerability exists within the DiveBook log's filter functionality.  Arbitrary URL parameters are reflected into the application's response, rendered by the browser as HTML or JavaScript.  An attacker may abuse this functionality by sending a victim a crafted link containing JavaScript, which will execute within the context of the victim's browser.  The "scrolled" parameter is also vulnerable. 
        </p>
        <h4>Steps:</h4>
        <ol>
            <li>Identify a WordPress application with the DiveBook (v.1.1.4) plugin installed (hxxps://example.wordpress.com).</li>
            <li>Locate a page with the DiveBook content included.  Note: within the WordPress page editor interface, "[divebook]divebook_display();[/divebook]" will be included.</li>
            <li>Browse to a URL triggering the "filter" functionality and include an arbitrary parameter containing HTML.  Example: hxxps://example.wordpress.com/index.php/2020/06/15/hello-world/?filter_diver=0&filter_divesite=site&divelog_page=1&scrolled=798&foobar"&lt;&gt;script&gt;alert`XSS+in+arbitrary+URL+parameter`&lt;/script&gt;"&lt;</li>
            <li>Observe that JavaScript executes within the web page (an alert box appears).</li>
        </ol>
        <img class="body-img" src="/img/divebook_xss.png" width="60%">
        <p class="text">
            The screenshot above demonstrates the attack (request, response, and result).  Observe that a JavaScript "alert" box appears with arbitrary content. This payload may be modified to execute arbitrary JavaScript in the victim's browser.
        </p>

        <h4>Vulnerability - CVE-2020-14205</h4>
        <p class="text">
            An authorization issue is present in the DiveBook "Add New Dive" feature.  An anonymous user may create a crafted HTTP POST request to create a new dive.  
        </p>
        <h4>Steps:</h4>
        <ol>
            <li>Identify a WordPress application with the DiveBook (v.1.1.4) plugin installed (hxxps://example.wordpress.com).</li>
            <li>Locate a page with the DiveBook content included.  Note: within the WordPress page editor interface, "[divebook]divebook_display();[/divebook]" will be included.</li>
            <li>Submit a crafted POST request containing the required parameters (see HTTP request below).</li>
            <li>Observe that a new dive has been logged within the database, which is visible on the page.</li>
        </ol>
        <img class="body-img" src="/img/divebook_auth.png" width="60%">
        <p class="text">
            The screenshot above contains the necessary parameters to create a new dive entry without supplying any cookies.
        </p>
        <h4>Testing Conditions:</h4>
        <ul>
            <li>Firefox v68.9.0esr</li>
            <li>WordPress 5.4.2</li>
        </ul>
    </div>
     <?php include 'policy.php';?>
</body>

<?php include '../footer.php';?>

</html>
