(() => {
  'use strict';
  const cfg = window.seedprintsSite;
  if (!cfg) return;
  const $ = window.jQuery;
  const selections = new WeakMap(), listed = new WeakMap();
  const names = new Set(['view_item_list','view_item','add_to_cart','begin_checkout','purchase','custom_order_start','custom_order_submit']);
  function track(name, data = {}) {
    if (!cfg.production || !cfg.analyticsAllowed || !names.has(name)) return;
    window.dataLayer = window.dataLayer || [];
    if (data.ecommerce) window.dataLayer.push({ecommerce:null});
    window.dataLayer.push({event:name,...data});
    if (cfg.ga4MeasurementId && typeof window.gtag === 'function') {
      window.gtag('event',name,{...(data.ecommerce || data),send_to:cfg.ga4MeasurementId});
    }
  }
  function item(source, quantity = 1) {
    const price = Number(source?.price), count = Number(quantity);
    if (!source?.item_id || source.price === '' || source.price == null || !Number.isFinite(price) || price < 0 || !Number.isFinite(count) || count <= 0) return null;
    return {item_id:String(source.item_id),item_name:String(source.item_name || ''),item_variant:String(source.item_variant || ''),price,quantity:count};
  }
  function formItem(form) {
    if (!form) return null;
    if (selections.has(form)) return selections.get(form);
    try { return JSON.parse(form.querySelector('.sp-product-analytics')?.textContent || 'null'); } catch { return null; }
  }
  $(document.body).on('found_variation.spAnalytics','form.variations_form',function(event,variation){selections.set(this,variation.seedprints_analytics_item || null);});
  $(document.body).on('reset_data.spAnalytics hide_variation.spAnalytics','form.variations_form',function(){selections.delete(this);});
  $(document.body).on('added_to_cart.spAnalytics',function(event,fragments,hash,button){
    const node=button?.[0], form=node?.closest('form'), card=node?.closest('.sp-product-card');
    const source=formItem(form) || (card?{item_id:card.dataset.productId,item_name:card.dataset.productName,price:card.dataset.productPrice}:null);
    const product=item(source,form?.querySelector('[name="quantity"]')?.value || node?.dataset.quantity || 1);
    if(product)track('add_to_cart',{ecommerce:{currency:cfg.currency,value:Math.round(product.price*product.quantity*1e6)/1e6,items:[product]}});
  });
  function trackList(list) {
    const products=[...list.querySelectorAll('.sp-product-card')].slice(0,50).map((card,index)=>{
      const product=item({item_id:card.dataset.productId,item_name:card.dataset.productName,price:card.dataset.productPrice});
      return product?{...product,index}:null;
    }).filter(Boolean);
    const signature=JSON.stringify(products);
    if(!products.length || listed.get(list)===signature)return;
    listed.set(list,signature);
    track('view_item_list',{ecommerce:{currency:cfg.currency,item_list_name:list.getAttribute('aria-label') || 'Products',items:products}});
  }
  document.querySelectorAll('.sp-product-grid,.sp-rail').forEach(trackList);
  function purchaseSeen(id) {try {const key='sp-purchase-'+id;if(sessionStorage.getItem(key))return true;sessionStorage.setItem(key,'1');}catch{}return false;}
  try {JSON.parse(document.getElementById('sp-ecommerce-events')?.textContent || '[]').forEach(data=>{
    if(data.event==='purchase' && purchaseSeen(data.ecommerce.transaction_id))return;
    track(data.event,{ecommerce:data.ecommerce});
  });}catch{}
  try {const result=document.getElementById('sp-custom-result');if(result){const data=JSON.parse(result.textContent);
    track('custom_order_submit',{method:data.type,product_id:data.product_id});
    const product=item({item_id:data.product_id,item_variant:data.item_variant,item_name:data.item_name,price:data.unit_price},data.quantity);
    if(data.type==='cart'&&product)track('add_to_cart',{ecommerce:{currency:cfg.currency,value:Math.round(product.price*product.quantity*1e6)/1e6,items:[product]}});
    try{sessionStorage.removeItem('seedprints-custom-draft');}catch{}
  }}catch{}
  window.seedprintsTrack=track;
  window.seedprintsAnalytics={trackList};
})();
