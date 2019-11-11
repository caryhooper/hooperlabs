<html>

<?php include '../header.php';?>

<body>

<?php include '../navbar.php';?>

    <div class="body-content">
        <h3>
            Kerberoasting (Under Construction)&nbsp;&nbsp;&nbsp;&nbsp;  
        </h3>
        <img src="https://placekitten.com/g/50/50" alt="Hack the Planet" height="50px" width="50px" align="middle" align="left">
        <p class="text">
            <p class="date">
                2019-10-23
            </p>
            <br><h4>The Kerberos Protocol</h4><br>
            This article doesn't cover everything about the kerberos protocol and kerberoasting, but it intends to cover the high points.<br>
            <ul><b>Generally How Kerberos Works</b>
            <li>Kerberos, the network authentication protocol, was developed in by a group at MIT in the last 80s.  It centers around encrypted, mutual, and centralized authentication, based around the concept of authentication tickets.</li></ul>
            <ul><b>Definition of Terms</b>
                <li>KDC - Key Distribution Center - The KDC is a centralized authentication hub proposed within the Kerberos protocol.  It performs authentication and authorization checks and issues "tickets", which may be passed as a security token.  In Microsoft Active Directory, the KDC is the Domain Controller (DC).</li>
                <li>TGT - Ticket-Granting Ticket - A TGT is issued by the KDC in response to an authentication request.  This is generally considered proof that a user is who they are.</li>
                <li>TGS - Ticket-Granting Server - This is the requested service that is being requested.</li>
                <li>AS-REQ - Authentication Server Request - Request from a client to the KDC requesting access to a specific resource.</li>
                <li>AS-REP - Authentication Server Response - Response from the KDC to the client containing a TGT or error message.</li>
                <li>SPN - Service Principal Name - described below.</li>
            </ul>
            &nbsp;&nbsp;&nbsp;&nbsp;Purpose of SPN<br>
            According to Microsoft's <a href="https://docs.microsoft.com/en-us/windows/win32/ad/service-principal-names">documentation</a>, a Service Principal Name "is a unique identifier of a service instance".  SPNs are used in Kerberos to associate a service instance with a service logon account.  This way, a user can request access to a SPN, which acts like an alias for the underlying service account registered.  Generally, they will be in the following format and contain the hostname of the server on which the service resides:
        </p>
        <pre>
            &lt;service-name&gt;/&lt;domain&gt;
            <i>cifs/somecomputer.hoop.local</i>
        </pre>
        <p class="text">
            <h4>Introduction to Kerberoasting</h4>
            &nbsp;&nbsp;&nbsp;&nbsp;Tim Medin<br>
            Tim first introduced the concept of kerberoasting in his 2014 talk, "Attacking Kerberos: Kicking the Guard Dog of Hades".  To understand this attack, more detail must be discussed about how ticket granting services are issued.
            &nbsp;&nbsp;&nbsp;&nbsp;Request SPN TGS<br>
            Users first must request a TGT from the DC.  This can be done through many different methods, but most easily by providing a username and password to the DC.  Once a TGT is issued to the user, this ticket is used to validate identity back to the DC.  Next, a user will present the TGT to request a TGS for the service.  A portion of this TGS is encrypted with the target service's NTLM hash.  Herein lies the vulnerability. 
            &nbsp;&nbsp;&nbsp;&nbsp;Offline cracking<br>
            Given that this portion of the TGS is encrypted with the service's NTLM hash, a routine may be created to brute-force the NTLM password by trying to decrypt the TGS in a brute-force manner.  This may be done more efficiently by generating a hashcat or JohnTheRipper-friendly hash and cracking the hash offline.  
        </p>
        <pre>

        </pre>
        <p class="text">
            Anatomy of an Attack
            &nbsp;&nbsp;&nbsp;&nbsp;Enumerating SPN<br>
            Tim recommends enumerating SPNs with the builtin Windows binary, setspn.exe (which maps AD accounts to SPN):
            <pre>
                PS C:\> setspn -T &lt;domain&gt; -Q */*
                PS C:\> setspn -T hoop.local -Q */* | sls -Context 0,1 Users
                > CN=krbtgt,CN=Users,DC=hoop,DC=local
                    kadmin/changepw
                > CN=msfrizzle,CN=Users,DC=hoop,DC=local
                      msfrizzle/win7sp1x86.hoop.local:80
                > CN=dorothy,CN=Users,DC=hoop,DC=local
                      superoldserviceaccount/solaris5.hoop.local:1337
            </pre>
            &nbsp;&nbsp;&nbsp;&nbsp;Finding Vulnerable SPN<br>
            &nbsp;&nbsp;&nbsp;&nbsp;Capture<br>
            &nbsp;&nbsp;&nbsp;&nbsp;Cracking<br>
        </p>
        <pre>

        </pre>
        <p class="text">
            Mitigations
            &nbsp;&nbsp;&nbsp;&nbsp;Patching<br>
            &nbsp;&nbsp;&nbsp;&nbsp;Detection<br>
            &nbsp;&nbsp;&nbsp;&nbsp;Prevention<br>
        </p>
        <pre>

        </pre>
        <p class=text>
            Additional Resources
            &nbsp;&nbsp;&nbsp;&nbsp;Link to Medin's Original Talk<br>
            &nbsp;&nbsp;&nbsp;&nbsp;Link to @harmj0y things<br>
        </p>

        <br>
    </div>


</body>

<?php include '../footer.php';?>

</html>
