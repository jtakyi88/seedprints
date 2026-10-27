const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(process.argv[2], 'utf8');
let checks = 0;
function test(name, fn) { fn(); checks++; console.log('PASS '+name); }
function boot(options={}) {
  const handlers=[], calls=[], store=options.store||new Map();
  const document={body:{},querySelectorAll:()=>options.lists||[],getElementById:id=>{
    if(id==='sp-ecommerce-events')return {textContent:JSON.stringify(options.events||[])};
    if(id==='sp-custom-result'&&options.custom)return {textContent:JSON.stringify(options.custom)};
    return null;
  }};
  const window={seedprintsSite:{production:true,analyticsAllowed:true,currency:'USD',ga4MeasurementId:'G-TEST123',...options.cfg},dataLayer:[],gtag:(...args)=>calls.push(args),jQuery:()=>({on:(events,...args)=>{for(const name of events.split(' '))handlers.push({name:name.split('.')[0],fn:args.at(-1)});}})};
  const sessionStorage={getItem:k=>{if(options.blockStorage)throw Error('blocked');return store.get(k);},setItem:(k,v)=>{if(options.blockStorage)throw Error('blocked');store.set(k,v);},removeItem:k=>store.delete(k)};
  vm.runInNewContext(source,{window,document,sessionStorage});
  return {window,calls,store,emit:(name,context,...args)=>handlers.filter(h=>h.name===name).forEach(h=>h.fn.call(context,{},...args))};
}
const product={item_id:'613',item_name:'Scripture tee',price:15};
const form=(data,quantity=1)=>({querySelector:s=>s==='.sp-product-analytics'?{textContent:JSON.stringify(data)}:s==='[name="quantity"]'?{value:quantity}:null});
const button=(f,card=null)=>[{closest:s=>s==='form'?f:card,dataset:{}}];
test('simple PDP uses authoritative ID, price and quantity',()=>{const b=boot();b.emit('added_to_cart',null,null,null,button(form(product,3)));const p=b.calls[0][2];assert.equal(p.value,45);assert.equal(p.items[0].item_id,'613');assert.equal(p.send_to,'G-TEST123');assert.equal(b.calls.length,1);});
test('selected variation owns price and variant ID',()=>{const b=boot(),f=form(product,2);b.emit('found_variation',f,{seedprints_analytics_item:{...product,item_variant:'700',price:30}});b.emit('added_to_cart',null,null,null,button(f));assert.equal(b.calls[0][2].value,60);assert.equal(b.calls[0][2].items[0].item_variant,'700');});
test('simple card add retains public catalog metadata',()=>{const b=boot();b.emit('added_to_cart',null,null,null,button(null,{dataset:{productId:'613',productName:'Tee',productPrice:'15'}}));assert.equal(b.calls[0][2].items[0].price,15);});
test('malformed price is suppressed instead of reporting zero',()=>{const b=boot();b.emit('added_to_cart',null,null,null,button(form({...product,price:''})));assert.equal(b.calls.length,0);});
test('missing product ID is suppressed',()=>{const b=boot();b.emit('added_to_cart',null,null,null,button(form({price:15})));assert.equal(b.calls.length,0);});
test('server receipt sends one native add event',()=>{const b=boot({events:[{event:'add_to_cart',ecommerce:{currency:'USD',value:30,items:[{...product,quantity:2}]}}]});assert.equal(b.calls.length,1);assert.equal(b.calls[0][2].value,30);});
test('paid purchase receipt deduplicates in the same browser session',()=>{const events=[{event:'purchase',ecommerce:{transaction_id:'synthetic-test',value:15,items:[product]}}],b=boot({events});assert.equal(b.calls.length,1);assert.equal(boot({events,store:b.store}).calls.length,0);});
test('blocked session storage still permits valid receipt',()=>{assert.equal(boot({blockStorage:true,events:[{event:'purchase',ecommerce:{transaction_id:'synthetic-test',value:15}}]}).calls.length,1);});
test('custom item includes existing customization charge and variant',()=>{const b=boot({custom:{type:'cart',product_id:12100,item_variant:'12101',item_name:'Custom tumbler',quantity:2,unit_price:32.5}});assert.deepEqual(b.calls.map(c=>c[1]),['custom_order_submit','add_to_cart']);assert.equal(b.calls[1][2].value,65);assert.equal(b.calls[1][2].items[0].item_variant,'12101');});
test('excluded users and staging emit no analytics',()=>{for(const cfg of [{analyticsAllowed:false},{analyticsAllowed:''},{production:false},{production:''}]){const b=boot({cfg});b.window.seedprintsTrack('custom_order_start');b.emit('added_to_cart',null,null,null,button(form(product)));assert.equal(b.calls.length,0);assert.equal(b.window.dataLayer.length,0);}});
test('WordPress localized string flags enable only allowed production events',()=>{const b=boot({cfg:{production:'1',analyticsAllowed:'1'}});b.window.seedprintsTrack('custom_order_start');assert.equal(b.calls.length,1);});
test('unverified destination never calls gtag',()=>{const b=boot({cfg:{ga4MeasurementId:''}});b.window.seedprintsTrack('custom_order_start');assert.equal(b.calls.length,0);});
test('list impressions include currency and deduplicate unchanged lists',()=>{const list={querySelectorAll:()=>[{dataset:{productId:'613',productName:'Tee',productPrice:'15'}}],getAttribute:()=> 'Bestsellers'},b=boot({lists:[list]});b.window.seedprintsAnalytics.trackList(list);assert.equal(b.calls.length,1);assert.equal(b.calls[0][2].currency,'USD');assert.equal(b.calls[0][2].item_list_name,'Bestsellers');assert.equal(b.calls[0][2].items[0].index,0);});
test('ecommerce state clears before every dataLayer payload',()=>{const b=boot();b.emit('added_to_cart',null,null,null,button(form(product)));assert.equal(b.window.dataLayer[0].ecommerce,null);assert.equal(b.window.dataLayer[1].event,'add_to_cart');});
console.log(JSON.stringify({passed:checks,transport:'isolated gtag spy; no network, orders or personal data'}));
