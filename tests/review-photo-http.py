import subprocess,tempfile,os,re,json,base64
from html.parser import HTMLParser
base='https://try.seedprints.com'
html=subprocess.check_output(['curl','--fail','-sS',base+'/product/jesus-died-once/']).decode()
class Form(HTMLParser):
 def __init__(self):super().__init__();self.inside=False;self.fields={}
 def handle_starttag(self,t,a):
  d=dict(a)
  if t=='form' and d.get('id')=='commentform':self.inside=True
  if t=='input' and self.inside and d.get('type')=='hidden':self.fields[d.get('name','')]=d.get('value','')
 def handle_endtag(self,t):
  if t=='form':self.inside=False
f=Form();f.feed(html);assert f.fields.get('sp_review_photo_nonce'),'Photo field missing from native review form'
path='/tmp/seedprints-review-fixture.png'
# A new disposable one-pixel PNG fixture, containing no customer information.
import struct,zlib
def chunk(t,d):return struct.pack('!I',len(d))+t+d+struct.pack('!I',zlib.crc32(t+d)&0xffffffff)
png=b'\x89PNG\r\n\x1a\n'+chunk(b'IHDR',struct.pack('!IIBBBBB',32,32,8,2,0,0,0))+chunk(b'IDAT',zlib.compress((b'\0'+bytes([91,107,58])*32)*32))+chunk(b'IEND',b'')
open(path,'wb').write(png)
fields={**f.fields,'author':'Seed Prints staging QA','email':'seedprints-qa@example.invalid','comment':'STAGING QA PHOTO TEST — pending moderation; not a customer endorsement.','rating':'5','sp_photo_consent':'1'}
args=['curl','--fail-with-body','--silent','--show-error','--location','--max-time','40']
for key,value in fields.items():args+=['--form-string',key+'='+value]
args+=['--form','sp_review_photo=@'+path+';type=image/png',base+'/wp-comments-post.php']
result=subprocess.run(args,stdout=subprocess.PIPE,stderr=subprocess.PIPE);os.unlink(path)
if result.returncode:
 body=result.stdout.decode(errors='replace');print(re.sub('<[^>]+>',' ',body)[-2200:]);raise SystemExit(1)
print(json.dumps({'native_review_photo_submission':True,'fixture_contains_customer_data':False,'publication_requested':False}))
