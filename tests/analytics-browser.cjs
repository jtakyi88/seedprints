const fs=require('fs'),assert=require('node:assert/strict');
const {Builder,By,until}=require(require.resolve('selenium-webdriver',{paths:[process.cwd(),'/tmp/seedprints-qa']})),chrome=require(require.resolve('selenium-webdriver/chrome',{paths:[process.cwd(),'/tmp/seedprints-qa']}));
const options=new chrome.Options().setPageLoadStrategy('eager').setChromeBinaryPath('/usr/bin/google-chrome').addArguments('--headless=new','--no-sandbox','--disable-dev-shm-usage','--window-size=1280,1000');
(async()=>{
 const driver=await new Builder().forBrowser('chrome').setChromeOptions(options).setChromeService(new chrome.ServiceBuilder(require(require.resolve('chromedriver',{paths:[process.cwd(),'/tmp/seedprints-qa']})).path)).build(),report={};
 try{
  await driver.manage().setTimeouts({pageLoad:45000,script:30000});
  await driver.sendDevToolsCommand('Page.addScriptToEvaluateOnNewDocument',{source:`window.__spAnalytics=[];window.gtag=(...args)=>window.__spAnalytics.push(args);Object.defineProperty(window,'seedprintsSite',{configurable:true,set(value){Object.defineProperty(window,'seedprintsSite',{value:{...value,production:true,analyticsAllowed:true,ga4MeasurementId:'G-ISOLATEDTEST'},writable:true,configurable:true});}});`});
  await driver.get('https://try.seedprints.com/product/the-genealogy-of-jesus-placemat/');
  await driver.wait(()=>driver.executeScript('return !!window.seedprintsAnalytics'),15000);
  const quantity=await driver.findElement(By.css('form.cart [name=quantity]'));await quantity.clear();await quantity.sendKeys('2');
  await driver.findElement(By.css('form.cart .single_add_to_cart_button')).click();
  await driver.wait(until.elementLocated(By.css('#spfix-bag[open]')),15000);
  const simple=await driver.executeScript('return window.__spAnalytics.filter(e=>e[1]==="add_to_cart")');
  assert.equal(simple.length,1);assert.equal(simple[0][2].items[0].item_id,'613');assert.equal(simple[0][2].value,30);assert.equal(simple[0][2].items[0].quantity,2);report.simple={events:1,item_id:'613',quantity:2,value:30};
  await driver.get('https://try.seedprints.com/product/jesus-died-once/');
  await driver.wait(()=>driver.executeScript('return !!window.seedprintsAnalytics'),15000);
  await driver.findElement(By.css('[data-sp-select="pa_color"][data-sp-option="black"]')).click();
  await driver.findElement(By.css('[data-sp-select="pa_size"][data-sp-option="m"]')).click();
  await driver.wait(async()=>await driver.findElement(By.css('form.variations_form')).getAttribute('data-spfix-available')==='yes',10000);
  const expected=await driver.executeScript('const form=document.querySelector("form.variations_form");return jQuery(form).data("product_variations").find(v=>String(v.variation_id)===form.querySelector("[name=variation_id]").value).seedprints_analytics_item;');
  await driver.findElement(By.css('form.cart .single_add_to_cart_button')).click();
  await driver.wait(until.elementLocated(By.css('#spfix-bag[open]')),15000);
  const variable=await driver.executeScript('return window.__spAnalytics.filter(e=>e[1]==="add_to_cart")');
  assert.equal(variable.length,1);assert.equal(variable[0][2].items[0].item_id,'657');assert.equal(variable[0][2].items[0].item_variant,expected.item_variant);assert.equal(variable[0][2].value,expected.price);report.variation={events:1,item_id:'657',variant:expected.item_variant,value:expected.price};
  report.transport='isolated browser gtag spy on protected staging; no analytics requests';report.orders_created=0;report.payments_attempted=0;report.emails_sent=0;
  fs.writeFileSync('/tmp/seedprints-analytics-staging-reports/browser-events.json',JSON.stringify(report,null,2));console.log(JSON.stringify(report));
 }finally{
  try{await driver.get('https://try.seedprints.com/cart/');for(let n=0;n<3;n++){const remove=await driver.findElements(By.css('.woocommerce-cart-form a.remove'));if(!remove.length)break;await remove[0].click();await driver.wait(async()=>!(await driver.findElements(By.css('.woocommerce-cart-form .blockUI'))).length,10000);}}catch{}
  await driver.quit();
 }
})().catch(e=>{console.error(e);process.exitCode=1;});
