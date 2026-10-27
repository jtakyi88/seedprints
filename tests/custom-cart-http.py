import urllib.request,urllib.parse,http.cookiejar,re,json,subprocess,tempfile,os
from html.parser import HTMLParser
base='https://try.seedprints.com'
jar_path=tempfile.mktemp(prefix='sp-qa-cookie-',dir='/tmp');open(jar_path,'w').close();os.chmod(jar_path,0o600)
def request(path,data=None):
 args=['curl','--fail','--silent','--show-error','--location','--max-time','40','--cookie',jar_path,'--cookie-jar',jar_path]
 if data is not None:args+=['--data-binary',urllib.parse.urlencode(data)]
 return subprocess.check_output(args+[base+path]).decode()
html=request('/custom/')
class Inputs(HTMLParser):
 def __init__(self):super().__init__();self.inputs={}
 def handle_starttag(self,tag,attrs):
  d=dict(attrs)
  if tag=='input' and d.get('type')=='hidden':self.inputs[d.get('name','')]=d.get('value','')
i=Inputs();i.feed(html)
products=json.loads(re.search(r'<script id="sp-custom-products" type="application/json">(.*?)</script>',html,re.S).group(1));p=next(x for x in products if x['id']==12999);v=next(x for x in p['variations'] if x['available'] and all(x['attributes'].values()))
from datetime import date,timedelta
payload={**i.inputs,**v['attributes'],'product_id':str(p['id']),'variation_id':str(v['id']),'design_text':'STAGING TEST — Thy word is a lamp unto my feet.','verse_reference':'Psalm 119:105','font':'Inter','color':'#1f1b16','quantity':'1','needed_by':str(date.today()+timedelta(days=30)),'design_final':'1','email':'','submit_type':'cart'}
body=request('/wp-admin/admin-post.php',payload)
assert 'STAGING TEST' in body and 'Psalm 119:105' in body, 'Custom metadata absent from cart'
assert '32.50' in body and 'Customization included' in body,'Existing customization charge missing from native cart'
# Verify the same form key cannot create a second design.
repeat_body=request('/wp-admin/admin-post.php',payload)
assert 'already been submitted' in repeat_body, 'Duplicate submission was not rejected'
os.unlink(jar_path)
print(json.dumps({'custom_cart_submission':True,'metadata_preserved':True,'duplicate_submission_rejected':True,'product_id':p['id'],'variation_id':v['id'],'base_unit_price':v['price'],'customization_charge':p['customizationFee'],'inclusive_unit_price':v['price']+p['customizationFee'],'orders_created':0,'payments_attempted':0}))
