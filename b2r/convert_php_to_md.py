import bs4
import os

def decode_html_entities(message):
	entities = {
		'&gt;':'>',
		'&lt;':'<',
		'&apos;':'\'',
		'&nbsp;':' ',
	}
	our_message = message.text
	children = message.findChildren('a')
	for child in children:
		href = child["href"]
		link = child.text.strip()
		our_message.replace(child.text,f"[{link}]({href})")
	for ent,decoded in entities.items():
		our_message.replace(ent,decoded)
	return our_message.strip()


files = os.listdir('C:\\Users\\Cary\\Documents\\Professional\\hooperlabs\\b2r\\')
print(files)
php_files = [f for f in files if '.php' in f]

first_file = php_files[0]

file = open(first_file,'r')
soup = bs4.BeautifulSoup(file.read(),'html.parser')

title = soup.find_all('h2')[0].text.strip()
img = soup.find_all('img')[0]
date =  soup.find_all('p',{'class':'date'})[0].text.strip()

print(f'#{title}')
print(f'![]({img["src"]})')

print(f'#####{date}')

elements = soup.find_all(['p','pre'])
for elem in elements:
	if elem.name == 'p':
		print("- ",end='')
		print(decode_html_entities(elem))
	else:
		print('```')
		print(decode_html_entities(elem))
		print('```')
