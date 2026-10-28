const fs=require('node:fs'),assert=require('node:assert/strict');
const {Builder,By,Key,until}=require('selenium-webdriver'),chrome=require('selenium-webdriver/chrome');
const base=process.env.SEEDPRINTS_QA_BASE||'https://try.seedprints.com';
assert(['https://try.seedprints.com','https://seedprints.com'].includes(base));
const out=process.env.SEEDPRINTS_QA_OUT||'/tmp/seedprints-mobile-navigation';fs.mkdirSync(out,{recursive:true});
const axe=fs.readFileSync(require.resolve('axe-core/axe.min.js'),'utf8');
const make=async(noJS=false)=>{
 const options=new chrome.Options().setPageLoadStrategy('eager').setChromeBinaryPath('/usr/bin/google-chrome').addArguments('--headless=new','--no-sandbox','--disable-dev-shm-usage','--window-size=375,900');
 if(noJS)options.setUserPreferences({'profile.managed_default_content_settings.javascript':2});
 const driver=await new Builder().forBrowser('chrome').setChromeOptions(options).setChromeService(new chrome.ServiceBuilder(require('chromedriver').path)).build();
 await driver.manage().setTimeouts({pageLoad:45000,script:30000});return driver;
};
(async()=>{
 const d=await make(),report={base,widths:[],routes:[],axe:[]};
 try{
  await d.get(base+'/');await d.wait(until.elementLocated(By.css('.sp-mobile-toggle')),15000);
  for(const width of [375,768,1280,1440]){
   await d.manage().window().setRect({width,height:900});
   const state=await d.executeScript(`const b=document.querySelector('.sp-mobile-toggle');return {width:innerWidth,overflow:document.documentElement.scrollWidth-document.documentElement.clientWidth,triggerVisible:getComputedStyle(b).display!=='none',desktopVisible:getComputedStyle(document.querySelector('.spfix-desktop-nav')).display!=='none',h1:document.querySelectorAll('h1').length,drawerCount:document.querySelectorAll('#sp-mobile-navigation').length};`);
   assert.equal(state.overflow,0);assert.equal(state.h1,1);assert.equal(state.drawerCount,1);assert.equal(state.triggerVisible,width<=1100);assert.equal(state.desktopVisible,width>1100);
   if(width<=1100){
    await d.findElement(By.css('.sp-mobile-toggle')).click();await d.wait(until.elementLocated(By.css('#sp-mobile-navigation[open]')),5000);
    const modal=await d.executeScript(`const x=document.querySelector('#sp-mobile-navigation');return {overflow:x.scrollWidth-x.clientWidth,categories:[...x.querySelectorAll('.sp-mobile-category-grid>a')].map(a=>a.textContent.replace('↗','').trim()),locked:getComputedStyle(document.documentElement).overflowY,focusInside:x.contains(document.activeElement),lazy:[...x.querySelectorAll('img')].every(i=>i.loading==='lazy')};`);
    assert.equal(modal.overflow,0);assert.equal(modal.categories.length,6);assert.equal(modal.locked,'hidden');assert(modal.focusInside);assert(modal.lazy);state.modal=modal;
    await d.executeScript(axe);
    const scan=await d.executeAsyncScript(`const done=arguments[arguments.length-1];axe.run(document,{runOnly:{type:'tag',values:['wcag2a','wcag2aa','wcag21a','wcag21aa','wcag22aa']}}).then(r=>done({violations:r.violations,passes:r.passes.length})).catch(e=>done({error:String(e)}));`);
    assert(!scan.error);assert.equal(scan.violations.length,0);report.axe.push({width,...scan});
    if(width===375){
     await d.findElement(By.css('#sp-mobile-navigation .spfix-close')).sendKeys(Key.chord(Key.SHIFT,Key.TAB));
     // A scrollable native dialog can itself be a tab stop.
     assert.equal(await d.executeScript(`return document.querySelector('#sp-mobile-navigation').contains(document.activeElement)`),true);
     await d.switchTo().activeElement().sendKeys(Key.TAB);
     assert.equal(await d.executeScript(`return document.activeElement===document.querySelector('#sp-mobile-navigation .spfix-close')`),true);
     report.keyboardFocusTrapped=true;
     fs.writeFileSync(out+'/drawer-375.png',await d.takeScreenshot(),'base64');
    }
    await d.switchTo().activeElement().sendKeys(Key.ESCAPE);
    await d.wait(async()=>!await d.findElement(By.id('sp-mobile-navigation')).getAttribute('open'),5000);
    assert.equal(await d.executeScript(`return document.activeElement===document.querySelector('.sp-mobile-toggle')`),true);report.escapeReturnsFocus=true;
   }
   report.widths.push(state);
  }
  await d.manage().window().setRect({width:375,height:900});await d.findElement(By.css('.sp-mobile-toggle')).click();
  await d.manage().window().setRect({width:1280,height:900});
  await d.wait(async()=>!await d.findElement(By.id('sp-mobile-navigation')).getAttribute('open'),5000);
  assert.equal(await d.executeScript(`return document.activeElement===document.querySelector('.spfix-logo')`),true);report.desktopResizeClosesDrawer=true;
  await d.manage().window().setRect({width:768,height:900});await d.findElement(By.css('.sp-mobile-toggle')).click();
  await d.actions().move({x:700,y:180,origin:'viewport'}).click().perform();
  await d.wait(async()=>!await d.findElement(By.id('sp-mobile-navigation')).getAttribute('open'),5000);report.backdropCloses=true;
  await d.findElement(By.css('.sp-mobile-toggle')).click();await d.findElement(By.css('#sp-mobile-navigation .spfix-close')).click();
  await d.wait(async()=>!await d.findElement(By.id('sp-mobile-navigation')).getAttribute('open'),5000);report.closeButtonWorks=true;
  await d.findElement(By.css('.sp-mobile-toggle')).click();await d.findElement(By.css('#sp-mobile-navigation a[href*="/product-category/women/"]')).click();
  await d.wait(until.urlContains('/product-category/women/'),15000);report.categoryNavigationWorks=true;
  for(const path of ['/shop/','/product/jesus-died-once/','/custom/','/about-us/']){
   await d.get(base+path);await d.wait(until.elementLocated(By.css('.sp-mobile-toggle')),15000);
   assert.equal((await d.findElements(By.id('sp-mobile-navigation'))).length,1);report.routes.push({path,globalDrawer:true});
  }
 }finally{await d.quit();}
 const fallback=await make(true);
 try{
  await fallback.manage().window().setRect({width:375,height:900});await fallback.get(base+'/');
  assert.equal((await fallback.findElements(By.css('.sp-mobile-toggle'))).length,0);
  const menu=await fallback.findElement(By.css('.spfix-mobile-menu'));await fallback.findElement(By.css('.spfix-mobile-menu>summary')).click();
  assert(await menu.getAttribute('open'));assert(await fallback.findElement(By.css('.spfix-mobile-menu nav a[href*="/custom/"]')).isDisplayed());report.noJavaScriptMenuWorks=true;
 }finally{await fallback.quit();}
 fs.writeFileSync(out+'/verification.json',JSON.stringify(report,null,2));
 console.log(JSON.stringify({widths:report.widths.length,routes:report.routes.length,axeViolations:report.axe.reduce((n,r)=>n+r.violations.length,0),keyboardFocusTrapped:report.keyboardFocusTrapped,escapeReturnsFocus:report.escapeReturnsFocus,desktopResizeClosesDrawer:report.desktopResizeClosesDrawer,backdropCloses:report.backdropCloses,categoryNavigationWorks:report.categoryNavigationWorks,noJavaScriptMenuWorks:report.noJavaScriptMenuWorks}));
})().catch(e=>{console.error(e);process.exitCode=1;});
