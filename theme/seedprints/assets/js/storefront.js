(() => {
  'use strict';
  const cfg = window.seedprintsSite;
  if (!cfg) return;
  const $ = window.jQuery;
  const say = text => { const node=document.getElementById('spfix-feedback'); if(node) node.textContent=text; };
  const api = async (path, options={}) => {
    const response=await fetch(cfg.api+path,{credentials:'same-origin',...options,headers:{...(cfg.nonce?{'X-WP-Nonce':cfg.nonce}:{}),...options.headers}});
    const data=await response.json(); if(!response.ok) throw new Error(data.message || 'Please try again.'); return data;
  };
  window.seedprintsApi=api;
  window.addEventListener('pageshow',event=>{if(event.persisted&&!window.wc_cart_fragments_params)window.seedprintsDialogs?.refreshBag();});
  let saved=[];
  try { saved=JSON.parse(localStorage.getItem('seedprints-wishlist') || '[]').filter(Number.isInteger).slice(0,50); } catch {}
  function markSaved(){ document.querySelectorAll('[data-sp-wish]').forEach(button=>{const active=saved.includes(Number(button.dataset.spWish)); button.setAttribute('aria-pressed',String(active)); button.classList.toggle('is-saved',active);}); }
  async function showSaved(){
    const target=document.getElementById('sp-wishlist'); if(!target) return;
    if(!saved.length){target.innerHTML='<p>No saved pieces yet. Tap the heart on a piece you love.</p>';return;}
    try { const products=await api('products?include='+saved.join(',')); target.innerHTML=products.map(p=>p.html).join(''); markSaved(); }
    catch {target.innerHTML='<p>Your saved pieces could not load. Refresh the page to try again.</p>';}
  }
  async function persistSaved(){try{localStorage.setItem('seedprints-wishlist',JSON.stringify(saved));}catch{say('Your browser could not save this list on this device.');} if(cfg.loggedIn)try{await api('wishlist',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({ids:saved})});}catch{say('Saved on this device. Account sync is temporarily unavailable.');}}
  markSaved(); showSaved();
  document.addEventListener('keydown',event=>{if(event.key==='Escape')document.querySelectorAll('.sp-mega[open]').forEach(menu=>{menu.open=false;menu.querySelector('summary').focus();});});
  document.querySelectorAll('.sp-mega').forEach(menu=>menu.addEventListener('toggle',()=>{if(menu.open)document.querySelectorAll('.sp-mega[open]').forEach(other=>{if(other!==menu)other.open=false;});}));
  if(cfg.loggedIn) api('wishlist').then(ids=>{saved=[...new Set([...saved,...ids])].slice(0,50); markSaved();showSaved();persistSaved();}).catch(()=>{});
  document.addEventListener('click', async event=>{
    const thumb=event.target.closest('[data-sp-gallery-slide]');
    if(thumb){const gallery=thumb.closest('.woocommerce-product-gallery');if($(gallery).data('flexslider')){$(gallery).flexslider(Number(thumb.dataset.spGallerySlide));gallery.querySelectorAll('[data-sp-gallery-slide]').forEach(button=>button.setAttribute('aria-pressed',String(button===thumb)));}else say('The gallery is loading. Please try again in a moment.');}
    const wish=event.target.closest('[data-sp-wish]');
    if(wish){const id=Number(wish.dataset.spWish); if(!saved.includes(id)&&saved.length>=50){say('Save up to 50 pieces. Remove one to make room.');return;} saved=saved.includes(id)?saved.filter(n=>n!==id):[...saved,id]; markSaved();persistSaved();showSaved();say(saved.includes(id)?'Saved to your pieces.':'Removed from your saved pieces.');}
    const quick=event.target.closest('[data-sp-quick]');
    if(quick){
      const dialog=document.getElementById('sp-quick-add'),content=dialog?.querySelector('.sp-quick-content'); if(!content)return;
      content.innerHTML='<p class="sp-loading" role="status">Loading product options…</p>'; window.seedprintsDialogs.open(dialog,quick);
      try {const data=await api('quick-add/'+Number(quick.dataset.spQuick)); document.getElementById('sp-quick-title').textContent=data.name;content.innerHTML=data.html;
        const form=content.querySelector('form');if(!form)throw new Error();
        form.querySelectorAll('[id]').forEach(node=>{const old=node.id;node.id='quick-'+old; form.querySelectorAll('label').forEach(label=>{if(label.htmlFor===old)label.htmlFor=node.id;});});
        window.seedprintsDialogs.initCart(form); $(form).wc_variation_form(); $(form).trigger('check_variations'); enhanceOptions(form);
      }catch{content.innerHTML='<p>These options could not load. Open the product page to choose your piece.</p>';}
    }
    const option=event.target.closest('[data-sp-option]');
    if(option){const select=document.getElementById(option.dataset.spSelect);if(select){select.value=option.dataset.spOption;$(select).trigger('change');syncButtons(select);}}
    if(event.target.closest('[data-sp-buy]')){ const target=document.querySelector('.summary .single_add_to_cart_button,.sp-personalize a');target?.scrollIntoView({behavior:matchMedia('(prefers-reduced-motion:reduce)').matches?'auto':'smooth',block:'center'});target?.focus({preventScroll:true});if(target&&(!target.matches('.single_add_to_cart_button')||!document.querySelector('.summary form.variations_form')||document.querySelector('.summary form.variations_form')?.dataset.spfixAvailable==='yes'))target.click(); }
  });
  function syncButtons(select){document.querySelectorAll('[data-sp-select]').forEach(button=>{if(button.dataset.spSelect!==select.id)return;button.setAttribute('aria-pressed',String(button.dataset.spOption===select.value));const option=[...select.options].find(o=>o.value===button.dataset.spOption);button.disabled=Boolean(option?.disabled);});}
  function enhanceOptions(root){root.querySelectorAll('.variations select').forEach(select=>{
    if(select.dataset.spEnhanced)return;select.dataset.spEnhanced='yes';if(!select.id)select.id='sp-choice-'+Math.random().toString(36).slice(2);
    const group=document.createElement('div');group.className='sp-option-group';group.setAttribute('role','group');group.setAttribute('aria-label',root.querySelector(`label[for="${CSS.escape(select.id)}"]`)?.textContent || 'Product options');
    [...select.options].filter(o=>o.value).forEach(o=>{const button=document.createElement('button');button.type='button';button.textContent=o.textContent;button.dataset.spOption=o.value;button.dataset.spSelect=select.id;group.append(button);});select.after(group);select.classList.add('sp-upgraded-select');syncButtons(select);select.addEventListener('change',()=>syncButtons(select));
  });}
  function syncSticky(){const button=document.querySelector('[data-sp-buy]');if(!button)return;const custom=document.querySelector('.sp-personalize a'),form=document.querySelector('.summary form.variations_form');button.disabled=form?.dataset.spfixAvailable==='no';button.textContent=custom?'Start your design':button.disabled?'Sold out':form&&!form.dataset.spfixVariation?'Choose options':'Add to bag';}
  $(document.body).on('found_variation.spThumb','form.variations_form',(event,variation)=>{const img=document.querySelector('.sp-pdp [data-sp-gallery-slide="0"] img');if(!img||!variation.image)return;if(!img.dataset.spOriginalSrc){img.dataset.spOriginalSrc=img.getAttribute('src')||'';img.dataset.spOriginalSrcset=img.getAttribute('srcset')||'';}img.src=variation.image.gallery_thumbnail_src||variation.image.thumb_src;img.removeAttribute('srcset');document.querySelectorAll('.sp-pdp [data-sp-gallery-slide]').forEach((button,index)=>button.setAttribute('aria-pressed',String(index===0)));});
  $(document.body).on('reset_data.spThumb','form.variations_form',()=>{const img=document.querySelector('.sp-pdp [data-sp-gallery-slide="0"] img');if(img?.dataset.spOriginalSrc){img.src=img.dataset.spOriginalSrc;img.setAttribute('srcset',img.dataset.spOriginalSrcset);}});
  $(document.body).on('found_variation.spSticky reset_data.spSticky hide_variation.spSticky','form.variations_form',()=>setTimeout(syncSticky,0));syncSticky();
  const accessibleGalleries=new WeakSet();
  function prepareGallery(gallery){if(accessibleGalleries.has(gallery))return;accessibleGalleries.add(gallery);const sync=()=>{const slides=[...gallery.querySelectorAll('.woocommerce-product-gallery__image')];const selected=Math.max(0,slides.findIndex(slide=>slide.classList.contains('flex-active-slide')));slides.forEach((slide,index)=>{slide.inert=index!==selected;slide.querySelectorAll('a').forEach(link=>link.tabIndex=index===selected?0:-1);});gallery.querySelectorAll('[data-sp-gallery-slide]').forEach((button,index)=>button.setAttribute('aria-pressed',String(index===selected)));};new MutationObserver(sync).observe(gallery,{subtree:true,attributes:true,attributeFilter:['class']});sync();}
  document.querySelectorAll('.woocommerce-product-gallery').forEach(prepareGallery);
  $(document.body).on('wc-product-gallery-after-init.spAccessibility','.woocommerce-product-gallery',function(){prepareGallery(this);});
  enhanceOptions(document);
  document.querySelectorAll('.sp-filter-form').forEach(form=>form.addEventListener('submit',()=>{[...form.elements].forEach(input=>{if(input.name&&!input.value)input.disabled=true;});}));
  document.querySelectorAll('.showcoupon').forEach(link=>{link.textContent='Add a promo code';link.setAttribute('aria-label','Add a promo code');});
  document.querySelectorAll('.prad-block-radio[data-required="yes"]').forEach(group=>{group.setAttribute('role','radiogroup');group.setAttribute('aria-label',group.dataset.label||'Product options');const input=group.querySelector('input[type=radio]');if(input)input.required=true;});
  document.querySelectorAll('.woocommerce-cart .coupon').forEach(coupon=>{const details=document.createElement('details'),summary=document.createElement('summary');details.className='sp-coupon';summary.textContent='Have a promo code?';coupon.before(details);details.append(summary,coupon);});
  document.querySelectorAll('#commentform:has([name=sp_review_photo])').forEach(form=>{form.enctype='multipart/form-data';});
  $(document.body).on('woocommerce_update_variation_values', 'form.variations_form',function(){this.querySelectorAll('select').forEach(syncButtons);});
  setTimeout(()=>{$('form.variations_form').trigger('check_variations');},0);
  // Ecommerce payloads are consumed by the owner's existing consent-aware analytics setup.
  const track=(name,data={})=>{if(!cfg.production)return;window.dataLayer=window.dataLayer||[];window.dataLayer.push({event:name,...data});};
  const cards=[...document.querySelectorAll('.sp-product-card')];if(cards.length)track('view_item_list',{ecommerce:{items:cards.slice(0,24).map((card,index)=>({item_id:card.dataset.productId,item_name:card.dataset.productName,price:Number(card.dataset.productPrice),index}))}});
  $(document.body).on('added_to_cart.spAnalytics',(event,fragments,hash,button)=>{const form=button?.[0]?.closest('form');const id=button?.[0]?.dataset.product_id||form?.dataset.product_id;const card=button?.[0]?.closest('.sp-product-card'),quantity=Number(form?.querySelector('[name="quantity"]')?.value||1),price=Number(form?.dataset.spfixPrice||card?.dataset.productPrice||0);track('add_to_cart',{ecommerce:{currency:cfg.currency,value:price*quantity,items:[{item_id:String(id||''),price,quantity}]}});document.getElementById('sp-quick-add')?.close();});
  const commerceEvents=document.getElementById('sp-ecommerce-events');
  if(commerceEvents){try{JSON.parse(commerceEvents.textContent).forEach(data=>{if(data.event==='purchase'){const key='sp-purchase-'+data.ecommerce.transaction_id;if(sessionStorage.getItem(key))return;sessionStorage.setItem(key,'1');}track(data.event,{ecommerce:data.ecommerce});});}catch{}}
  const result=document.getElementById('sp-custom-result');if(result){try{const data=JSON.parse(result.textContent);track('custom_order_submit',{method:data.type,product_id:data.product_id});if(data.type==='cart')track('add_to_cart',{ecommerce:{currency:cfg.currency,value:Number(data.unit_price)*data.quantity,items:[{item_id:String(data.product_id),price:Number(data.unit_price),quantity:data.quantity}]}});sessionStorage.removeItem('seedprints-custom-draft');}catch{}}
  window.seedprintsTrack=track;
})();
