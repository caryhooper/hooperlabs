<html>

<?php include '../header.php';?>

<body>

<?php include '../navbar.php';?>

    <div class="body-content">
        <h2>
            OWASP Uncrackable 1
        </h2>
        <h3 class="subtitle">
            or Android Hacking with frida for dummies
        </h3>

        <img class="headerpic" src="/img/pwn.png" alt="Hack the Planet" height="50px" width="50px">
        <p class="date">2020-04-22</p>
        <p class="text">
            The other day, I started to get serious about using frida to hack mobile applications.  There is a ton to learn and, at times, mobile hacking can be a bit daunting even when you have experience pentesting web applications.  Mobile is a different beast altogether.  I'm talking about a unique threat model, many more skills/techniques to learn, and on top of it all still web application hacking techniques which may be relevant. A friend of mine, <a href="https://twitter.com/gcovs">gcovs</a>, challenged me to up my mobile game and motivated me to finally dive into frida. 
        </p>
        <p class="text">
            Gcovs recommended the OWASP "Crack-Me" Android applications (found <a href="https://github.com/OWASP/owasp-mstg/tree/master/Crackmes">here</a>).  I'm looking forward to solving all three, but this writeup will outline the methods and analysis for solving just the first one.  I found two solutions to the first uncrackable application (Uncrackable 1): one through static analysis and one through dynamic analysis (with frida).  I'll explain them both below as best as I understand.  
        </p>
        <h4>
            Setup
        </h4>
        <p class="text">
            I'll outline my own setup here for the reader's awareness and learning, but it should be understood that there are many ways of doing these things.  I decided to use an Android emulator.  My understanding is that emulators are fairly reasonable substitutes for a bare metal android device while using the adb interface (<a href="https://developer.android.com/studio/command-line/adb">Android Debugging Bridge</a>).  I've used Android Studio's emulator in the past, but over the past week, I installed Genymotion and am thrilled at how much easier it is to use.  Genymotion for personal use is free and can be downloaded <a href="https://www.genymotion.com/download/">here</a>.  Once installed, I opened up the application and clicked the large pink/white plus sign in the top right corner to add a device.  I added a "Samsung Galaxy S10" running Android 9.0 - API 28 and allocated 2GB of RAM for good measure.  By default, it came with 4GB but I don't have that kind of RAM to spare.  I configured it to "bridged" networking mode so it would pick up an IP address from my DHCP server.
        </p>
        <p class="text">
            Once the emulated android device was turned on, I checked to see if adb recognized the newly-connected device.  I already had adb installed (** and included within the Windows PATH environment variable).  The installation of adb is beyond the scope of this writeup.  We can view all of the devices connected to the android debugging bridge by issuing the following command.
        </p>
        <pre>
    PS C:\Users\Cary\.android\frida-examples\owasp.mstg.uncrackable1> adb devices                                                    
    List of devices attached
    192.168.0.165:5555      device
    192.168.7.101:5555      device
        </pre>
        <p class="text">
            This is quite important because since two devices are recognized, we'll have to specify which of the two we are interfacing with.  Quickly, we can test this out by dropping a shell on the device.  Interestingly, we may see that we're already root.  One of the benefits of running emulated Android operating systems is that we don't have to root these devices manually.  I may cover rooting devices in another post.
        </p>
        <pre>
    PS C:\Users\Cary\.android\frida-examples\owasp.mstg.uncrackable1> adb -s 192.168.0.165:5555 shell                                
    vbox86p:/ # whoami
    root
    vbox86p:/ # id
    uid=0(root) gid=0(root) groups=0(root),1004(input),1007(log),1011(adb),1015(sdcard_rw),1028(sdcard_r),3001(net_bt_admin),3002(net_bt),3003(inet),3006(net_bw_stats),3009(readproc),3011(uhid) context=u:r:su:s0
        </pre>
        <p class="text">
            Great!  Next, we'll have to run frida-server on the target android device in order to dynamically interface with it at runtime.  frida-server can be downloaded from the frida <a href="https://github.com/frida/frida/releases">GitHub releases</a> page.  It's quite important to match the target architecture.  In our case, "uname -m" reveals a i686 processor, which is x86 or 32bit.  Thus, I downloaded "frida-server-12.8.20-android-x86.xz", used 7zip to unpack the XZ archive, and renamed the binary to frida-server.  Then, I uploaded the file to the android device and ran it as a background process. 
        </p>
        <pre>
    PS C:\Users\Cary\.android\frida-server-12.8.20-android-x86> adb -s 192.168.0.165:5555 push .\frida-server-12.8.20-android-x86 /data/local/tmp/frida-server                                                                                                        
    .\frida-server-12.8.20-android-x86: 1 file pushed. 48.8 MB/s (26114852 bytes in 0.511s)
    PS C:\Users\Cary\.android\frida-server-12.8.20-android-x86> adb -s 192.168.0.165:5555 shell "chmod +x /data/local/tmp/frida-server"    
    PS C:\Users\Cary\.android\frida-server-12.8.20-android-x86> adb -s 192.168.0.165:5555 shell "/data/local/tmp/frida-server &"
    **Note: this command hung and I needed to press "CTL+C", but the command still ran in the background.
    PS C:\Users\Cary\.android\frida-server-12.8.20-android-x86> frida-ps -D 192.168.0.165:5555  |sls frida                           
    2346  frida-server
        </pre>
        <p class="text">
            The last command, "frida-ps", is a command-line tool for listing processes.  In order for this to work, you'll need the python frida-tools package installed on your system.  You can install it with the command: "python -m pip install frida-tools".  
        </p>
        <p class="text">
            That's it for the setup!  If you've made it this far, we're ready to start reverse engineering/hacking the app. In conclusion, we installed Genymotion emulator, downloaded/booted an Android system image, interfaced with that emulated device with adb, and ran frida-server on the target device for use in dynamic analysis.
        </p>
        <h4>
            Static Analysis of Uncrackable 1
        </h4>
        <p class="text">
            First, I did some static analysis of the Uncrackable 1 APK to try and get an idea of the application flow and patterns.  There are many other ways to do this, but I loaded the APK directly into <a href="https://www.bytecodeviewer.com/">ByteCode Viewer</a>.  An APK is basically a ZIP file containing all of the code and files necessary to make the android application run.  ByteCode Viewer unpacks and decompiles these source code files, allowing us to view them in their hierarchy.  To run ByteCode Viewer, I just downloaded the JAR file, then ran it on a device with Java installed (in my case Kali Linux) with "java -jar ByteCodeViewer.jar".
        </p>
        <p class="text">
            The application was a bit confusing at first because I'm unfamiliar with how Android apps handle "views".  As a result, I searched through the package for code pertaining to the functionality.  What do we look at first?  A decent strategy that I've tried is to open the application and try to search for strings surrounding interesting functionality.  In this case, within the Android emulator, I opened the application and was presented with the error message: "Root detected! This is unacceptable.  The app is now going to exit."  Obviously, we'll have to bypass this root detection function(s).  Looking in the code to find these strings, we find them in the "sg.vantagepoint.uncrackable1.MainActivity.class".  Note that these are the class/folder structure packaged within the application.  
        </p>
        <p class="text">
            On line 25, within the "onCreate" function, three functions are called (c.a, c.b, c.c).  Because of the "||" (OR) statements, if any one of them returns "true", then the root detection is failed.  That is, the code passes "Root detected!" into the MainActivity.a function, which essentially pops an alert box.
        </p>
        <pre>
    protected void onCreate(Bundle var1) {
        if (c.a() || c.b() || c.c()) {
            this.a("Root detected!");
        }
        </pre>
        <p class="text">
            Next, I took a look at each of these functions.  To find them, I looked in the import statements at the top of the code and found "import sg.vantagepoint.a.c;", which imports functionality from another part of the code.  Within sg.vantagepoint.a.c.class, I found these three root-detection functions.  
        </p>
        <pre>
    package sg.vantagepoint.a;

    import android.os.Build;
    import java.io.File;

    public class c {
       public static boolean a() {
          String[] var0 = System.getenv("PATH").split(":");
          int var1 = var0.length;

          for(int var2 = 0; var2 < var1; ++var2) {
             if ((new File(var0[var2], "su")).exists()) {
                return true;
             }
          }

          return false;
       }

       public static boolean b() {
          String var0 = Build.TAGS;
          return var0 != null && var0.contains("test-keys");
       }

       public static boolean c() {
          String[] var0 = new String[]{"/system/app/Superuser.apk", "/system/xbin/daemonsu", "/system/etc/init.d/99SuperSUDaemon", "/system/bin/.ext/.su", "/system/etc/.has_su_daemon", "/system/etc/.installed_su_daemon", "/dev/com.koushikdutta.superuser.daemon/"};
          int var1 = var0.length;

          for(int var2 = 0; var2 < var1; ++var2) {
             if ((new File(var0[var2])).exists()) {
                return true;
             }
          }

          return false;
       }
    }
        </pre>
        <p class="text">
            Two of the three functions were pretty clear in what checks they were performing.  Function c.a calls "System.getenv('PATH')", which presumably reads the PATH environment variable.  It splits that variable on ":" and for each element, checks to see if the "su" binary exists at that path.  Function c.c, on the other hand contains an array of strings with common artifacts of rooted systems.  Then, one by one, checks to see if any of those files exist on the system.  I was a little confused by the c.b function and needed to do a little bit of research.  Googling "Build.TAGS root detection", I landed at a <a href="https://stackoverflow.com/questions/18808705/android-root-detection-using-build-tags">StackOverflow</a> page, which revealed that test-keys and release-keys have to do with how the kernel is signed when it is compiled.  A kernel signed with "test-keys" means it was signed with a custom key generated by a third-party developer.  A kernel signed with Release-Keys is generally a sign that the kernel is more secure.  
        </p>
        <p class="text">
            Awesome!  We've found the root detection methods and learned some things about ways to check if a device is rooted or not.  Now that we know which functions to manipulate, we will do so through dynamic manipulation.  It is quite possible to change the source code to remove these functions or have them always return the boolean "false".  We would then have to recompile or re-sign the application, and then reinstall, but it may be easier to use frida to manipulate the return value of these functions on-the-fly.
        </p>
        <h4>
        Dynamic Analysis (with frida)
        </h4>
        <p class="text">
            In order to change these applications functions' input and output, we will use the frida API.  There are other ways of integrating with the frida API running on the server (emulated android device), but for the moment, I prefer the Python integration with the python-frida package.  Creating this Python script is also out of scope for this write-up, but you can find it <a href="https://github.com/caryhooper/frida-examples/blob/master/infosecadventures.fridademo/fridademo_frida.py">on GitHub</a>.  
        </p>
        <p class="text">
            If you're up-to-date with Python3, you shouldn't have any trouble running the script.  All the script really does is find/attach to the Android device, determine if the process is running, attach to the process, then load the JavaScript to interface with frida.  I hope to continually add/update this script to make it easier to invoke/change.  I think of it as a wrapper to the frida API.  Eventually, I hope it will help keep track of more complicated apps.  Let's take a look at the JavaScript file.  In the following script, we attach to (use) a Java class and hook a function.  Since we're attempting to bypass the root detection, we'll start with one of the three functions located in the "sg.vantagepoint.a.c" class.
        </p>
        <pre>
    Java.perform(function () {
        console.log("[ * ] Starting implementation override...");
        
        //obtain reference of the activity currently running
        var rootDetection = Java.use("sg.vantagepoint.a.c");
        
        //replace the original implementation of the function with ours.
        rootDetection.a.implementation = function(){
            console.log("[ + ] Root detection #1 was hooked!");                
        }
    });
        </pre>
        <p class="text">
            The code above performs an action on the current session via the frida API.  Those of you familiar with JavaScript will recognize "console.log" as a substitute for Python's "print" or bash's "echo".  This will just echo a value to the screen.  Next, we define the class "c.class" by passing the full class reference to "Java.use", then saving it into a variable.  Last, we call the "implementation" method on the function "a", which will execute a defined function when the original function is called at runtime.  Running this Python frida script, we receive the following output. 
        </p>
        <pre>
    PS C:\Users\Cary\.android\frida-examples\owasp.mstg.uncrackable1> python .\uncrackable1_frida.py C:\Temp\test.js                 
    [ * ] Attaching to current process.
    [ * ] No process detected.  Spawning process.
    [ * ] Running frida Demo App
    [ * ] Starting implementation override...
    [ + ] Root detection #1 was hooked!
    Message: {'type': 'error', 'description': 'Error: Implementation for a expected return value compatible with boolean', 'stack': 'Error: Implementation for a expected return value compatible with boolean\n    at we (frida/node_modules/frida-java-bridge/lib/class-factory.js:599)\n    at frida/node_modules/frida-java-bridge/lib/class-factory.js:581', 'fileName': 'frida/node_modules/frida-java-bridge/lib/class-factory.js', 'lineNumber': 599, 'columnNumber': 1}
    Payload: None
        </pre>
        <p class="text">
            This is great!  We have output that is expected, which was the message that "Root detection #1 was hooked".  This is important because it demonstrates that our JavaScript (console.log) was executed when the function was called.  The "Message" and "Payload" were generated when the program crashed.  This is because I defined "my_message_handler" within the Python program ensuring that all error messages were output to the terminal.  But why did we receive an error?  This is because we hijacked the flow of the program by hooking the function but didn't return anything.  Within a JavaScript function, we can include the "return" keyword to return a value.  To find what the data structure the original function expected to be returned, look at the word to the left of the function name.  In the case of the "a" function within "c.class" ( public static boolean a()), we see that the function returns a boolean value, which is either true or false.  If we add another line to return a variable, the program will run without error.  
        </p>
        <pre>
    Java.perform(function () {
        console.log("[ * ] Starting implementation override...");
        
        //obtain reference of the activity currently running
        var rootDetection = Java.use("sg.vantagepoint.a.c");
        
        //replace the original implementation of the function with ours.
        rootDetection.a.implementation = function(){
            console.log("[ + ] Root detection #1 was hooked!");
            return false;
        }
    });
        </pre>
        <p class="text">
            Though we've successfully hooked one root detection function, two others still need to be hooked/manipulated.  In the same manner as the first, we may modify the JavaScript file to bypass all three root detection functions.   
        </p>
        <pre>
    Java.perform(function () {
    console.log("[ * ] Starting implementation override...");
    //obtain reference of the activity currently running
    var rootDetection = Java.use("sg.vantagepoint.a.c");
    //replace the original implementation of the function with ours.
    rootDetection.a.implementation = function(){
        console.log("[ + ] Root detection #1 ($PATH check) successfully bypassed!");
        return false;
    }
    rootDetection.b.implementation = function(){
        console.log("[ + ] Root detection #2 (unknown check) successfully bypassed!");
        return false;
    }
    rootDetection.c.implementation = function(){
        console.log("[ + ] Root detection #3 (File check) successfully bypassed!");
        return false;
    }
        </pre>
        <p class="text">
            Now, when we run the program again (python uncrackable1-frida.py uncrackable1-rootBypass.js), the "Root Detected!" popup no longer appears and we gained access to additional functionality within the application.  Additionally, three console.log statements printed to the terminal confirmed that all three root detection functions were hooked and bypassed.  Awesome!  Give yourself a pat on the back.  Do a dance.  Have a drink.  Then let's prepare to go deeper. 
        </p>
        <h4>
            Finding the Secret Key
        </h4>
        <p class="text">
            This portion of the application appeared to prompt the user to input a "secret string", and then provide a "Verify" button to check the secret.  I looked into the static source of the application for the functionality and found that this value was handled in "sg.vantagepoint.uncrackable1.a.class". 
        </p>
        <pre>
    package sg.vantagepoint.uncrackable1;

    import android.util.Base64;
    import android.util.Log;

    public class a {
       public static boolean a(String var0) {
          byte[] var1 = Base64.decode("5UJiFctbmgbDoLXmpL12mkno8HT4Lv8dlat8FxR2GOc=", 0);

          try {
             var1 = sg.vantagepoint.a.a.a(b("8d127684cbc37c17616d806cf50473cc"), var1);
          } catch (Exception var3) {
             StringBuilder var2 = new StringBuilder();
             var2.append("AES error:");
             var2.append(var3.getMessage());
             Log.d("CodeCheck", var2.toString());
             var1 = new byte[0];
          }

          return var0.equals(new String(var1));
       }

       public static byte[] b(String var0) {
          int var1 = var0.length();
          byte[] var2 = new byte[var1 / 2];

          for(int var3 = 0; var3 < var1; var3 += 2) {
             var2[var3 / 2] = (byte)((byte)((Character.digit(var0.charAt(var3), 16) << 4) + Character.digit(var0.charAt(var3 + 1), 16)));
          }

          return var2;
       }
    }
        </pre>
        <p class="text">
            I didn't understand 100% of the code within this class, but I was able to deduce the following:
            <ul>
                <li>Function a.a appears to contain ciphertext and an encryption key.</li>
                <li>This was confirmed by looking at sg.vantagepoint.a.a.class, which explicitly created objects related to AES ECB Encryption.</li>
                <li>AES Decryption generally requires a key and ciphertext.</li>
                <li>Function a.b appears to take a 32-character string and convert it to a 16-byte byte array (necessary for encryption/decryption).  It appeared to "prepare" the key.</li>
            </ul>
        </p>
        <p class="text">
            Not only do we have what appears to be ciphertext, but we also have the encryption method and key.  At this point, I can write a quick decryption routine in Python to reveal the plaintext.  This decryption program is located <a href="https://github.com/caryhooper/scripts/blob/master/aesdecrypt.py">on GitHub, here</a>.  While this certainly works, its definitely not the frida way of doing things.  I looked closely at the return value for function a.a, which checked to see if var0 (the user input) was equal to var1.  Earlier in the function, var1 was populated in the "try" statement.  In order to perform the decryption function, we'd need to invoke a.b to prepare the key and sg.vantagepoint.a.a.a to perform the actual decrypt function.  Luckily, we can do this in frida!
        </p>
        <pre>
    Java.perform(function () {
        console.log("[ * ] Starting implementation override...");
        //obtain reference of the activity currently running
        var rootDetection = Java.use("sg.vantagepoint.a.c");
        //replace the original implementation of the function with ours.
        rootDetection.a.implementation = function(){
            console.log("[ + ] Root detection #1 ($PATH check) successfully bypassed!");
            return false;
        }
        rootDetection.b.implementation = function(){
            console.log("[ + ] Root detection #2 (unknown check) successfully bypassed!");
            return false;
        }
        rootDetection.c.implementation = function(){
            console.log("[ + ] Root detection #3 (File check) successfully bypassed!");
            return false;
        }

        //By reverse engineering the sg.vantagepoint.a class, we see that our user input is being
        //compared to "var1".  If they match, it returns true.  If not, false.  Obviously we can
        //override this function, but what we really want is to find the secretWord.

        //var1 = sg.vantagepoint.a.a.a(input1,input2)
        //input1 is b("8d127684cbc37c17616d806cf50473cc")
        var compareWord = Java.use("sg.vantagepoint.uncrackable1.a");
        var input1 = compareWord.b("8d127684cbc37c17616d806cf50473cc");

        //input2 is Base64.decode("5UJiFctbmgbDoLXmpL12mkno8HT4Lv8dlat8FxR2GOc=", 0);
        //to include this in our JS, we need to import the Java Base64 class.
        var Base64 = Java.use("android.util.Base64");
        var input2 = Base64.decode("5UJiFctbmgbDoLXmpL12mkno8HT4Lv8dlat8FxR2GOc=", 0);

        //Now, we can call sg.vantagepoint.a.a.a and include both inputs.
        var doEncrypt = Java.use("sg.vantagepoint.a.a");
        var secretObj = doEncrypt.a(input1,input2);
        //This returns an "object".  Within the code, this is converted to a String before comparison.

        //Thus, we need to import Java's string type in order to create a new String from the object.
        var string_class = Java.use("java.lang.String");
        var secret = string_class.$new(secretObj);
        console.log("Secret: " + secret);

    });
        </pre>
        <p class="text">
            Using frida, I prepared the two inputs into the decrypt function as input1 and input2.  One thing that surprised me is that I couldn't just use JavaScript's atob() or btoa() base64-handling functions.  Using those resulted in an error. Instead, for each function I needed to import a class with "Java.use".  This way, I used the Android Base64 utility within the frida script. Later, I needed to import Java's string class "java.lang.String" in order to convert the decrypted byte array to an actual string.  After running the program, the secret word was displayed to the terminal.  
        </p>
        <pre>
    PS C:\Users\Cary\.android\frida-examples\owasp.mstg.uncrackable1> python .\uncrackable1-frida.py .\uncrackable1-decryptSecret.js [ * ] Attaching to current process.
    [ * ] No process detected.  Spawning process.
    [ * ] Running frida Demo App
    [ * ] Starting implementation override...
    Secret: I want to believe
    [ + ] Root detection #1 ($PATH check) successfully bypassed!
    [ + ] Root detection #2 (unknown check) successfully bypassed!
    [ + ] Root detection #3 (File check) successfully bypassed!
        </pre>
        <p class="text">
            I find it interesting that the "secret" was displayed before the root detection was bypassed.  This is because we weren't overriding the implementation of any application functions at all. We weren't waiting for these functions to be called at runtime.  Instead, we were calling these functions directly.  Therefore, as soon as the application was loaded, the functions ran.  Though kind of a pain, I found this incredibly powerful and can't wait to explore this frida API more.  
        </p>
        <p class="text">
            In conclusion, we combined both static and dynamic analysis of the Android Uncrackable 1 application to bypass security function and discover secrets.  First, we prepared the environment including an emulated Android device running frida-server.  Next, we decompiled and analyzed the APK source code (Java).  Last, we interfaced with the frida API using Python and JavaScript to hook/bypass security functions and invoke arbitrary functions within the application at runtime.  I'm looking forward to writing more about android hacking with frida!
        </p>
        <br>
    </div>
</body>

<?php include '../footer.php';?>

</html>
