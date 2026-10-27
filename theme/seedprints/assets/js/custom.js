(() => {
  const form=document.getElementById('sp-custom-form');if(!form)return;
  const products=JSON.parse(document.getElementById('sp-custom-products').textContent), fields=[...form.querySelectorAll('[data-sp-step]')];
  const el=name=>form.elements.namedItem(name), feedback=document.getElementById('sp-custom-feedback');
  let step=0, artworkUrl='',started=false;
  form.noValidate=true;
  const draftNames=['product_id','design_text','verse_reference','font','color','quantity','needed_by'];
  try{const draft=JSON.parse(sessionStorage.getItem('seedprints-custom-draft')||'null');if(draft&&!location.search)draftNames.forEach(name=>{if(typeof draft[name]==='string')el(name).value=draft[name];});}catch{}
  function show(index){step=index;fields.forEach((field,i)=>field.hidden=i!==index);form.querySelector('[data-sp-step-number]').textContent=index+1;form.querySelector('[data-sp-back]').hidden=index===0;form.querySelector('[data-sp-next]').hidden=index===3;feedback.textContent='';}
  function validate(index){const invalid=[...fields[index].querySelectorAll('input,select,textarea')].find(input=>!input.checkValidity());if(invalid){show(index);invalid.focus();invalid.reportValidity();return false;}return true;}
  function selected(){return products.find(p=>String(p.id)===el('product_id').value);}
  function updateVariation(){const product=selected();if(!product)return;let variation=null;
    const previewProduct=document.querySelector('.sp-preview-product');previewProduct.className='sp-preview-product sp-preview-product--'+product.previewType;const color=[...document.querySelectorAll('#sp-custom-attributes select')].find(select=>/color/.test(select.name))?.value;previewProduct.style.setProperty('--sp-preview-surface',color==='black'?'#272522':'#fff');
    if(product.type==='variable'){const choices=[...document.querySelectorAll('#sp-custom-attributes select')];if(choices.every(c=>c.value))variation=product.variations.find(v=>v.available&&choices.every(c=>!v.attributes[c.name]||v.attributes[c.name]===c.value));}
    el('variation_id').value=variation?.id||'';
    const valid=product.available&&(product.type!=='variable'||Boolean(variation));
    el('product_id').setCustomValidity(product.available?'':'This piece is currently unavailable.');
    const first=document.querySelector('#sp-custom-attributes select');first?.setCustomValidity(first.value&&!valid?'Choose an available combination.':'');
    const price=variation?.priceHtml||product.priceHtml;
    document.getElementById('sp-custom-price').innerHTML=price;document.querySelector('[data-sp-preview-price]').innerHTML=price;document.querySelector('[data-sp-preview-name]').textContent=product.name;
  }
  function chooseProduct(){const product=selected(),target=document.getElementById('sp-custom-attributes');target.replaceChildren();if(!product)return;
    product.attributes.forEach(attribute=>{const label=document.createElement('label'),select=document.createElement('select');label.textContent=attribute.label;select.name=attribute.name;select.id='custom-'+attribute.name;select.required=true;label.htmlFor=select.id;select.add(new Option('Choose '+attribute.label.toLowerCase(),''));attribute.options.forEach(option=>select.add(new Option(option.label,option.value)));target.append(label,select);});updateVariation();
  }
  function preview(){const text=el('design_text').value;document.querySelector('[data-sp-preview-text]').textContent=text||'Your words go here';document.querySelector('[data-sp-preview-text]').style.fontFamily=el('font').value;document.querySelector('[data-sp-preview-text]').style.color=el('color').value;form.querySelector('[data-sp-character-count]').textContent=text.length;}
  chooseProduct();preview();show(0);
  form.addEventListener('input',()=>{preview();if(!started){started=true;window.seedprintsTrack?.('custom_order_start');}try{sessionStorage.setItem('seedprints-custom-draft',JSON.stringify(Object.fromEntries(draftNames.map(name=>[name,el(name).value]))));}catch{}});
  el('product_id').addEventListener('change',chooseProduct);document.getElementById('sp-custom-attributes').addEventListener('change',updateVariation);
  form.querySelector('[data-sp-next]').addEventListener('click',()=>{if(validate(step)){show(Math.min(step+1,3));fields[step].querySelector('input:not([type=hidden]),select,textarea')?.focus();}});
  form.querySelector('[data-sp-back]').addEventListener('click',()=>{show(Math.max(step-1,0));fields[step].querySelector('input:not([type=hidden]),select,textarea')?.focus();});
  form.querySelector('[data-sp-lookup]').addEventListener('click',async event=>{const button=event.currentTarget,status=document.getElementById('sp-lookup-feedback');button.disabled=true;status.textContent='Finding your verse…';try{const result=await window.seedprintsApi('verse?reference='+encodeURIComponent(el('verse_reference').value));el('design_text').value=result.text;el('verse_reference').value=result.reference;preview();status.textContent=result.reference+' · '+result.translation;}catch(error){status.textContent=error.message;}finally{button.disabled=false;}});
  el('artwork').addEventListener('change',()=>{const file=el('artwork').files[0],status=form.querySelector('[data-sp-artwork-feedback]'),image=document.querySelector('[data-sp-preview-image]');if(artworkUrl)URL.revokeObjectURL(artworkUrl);image.hidden=true;el('artwork').setCustomValidity('');if(!file){status.textContent='';return;}if(file.size>5*1024*1024||!['image/jpeg','image/png','image/webp'].includes(file.type)){el('artwork').setCustomValidity('Choose a JPG, PNG or WebP smaller than 5 MB.');status.textContent=el('artwork').validationMessage;return;}artworkUrl=URL.createObjectURL(file);image.src=artworkUrl;image.hidden=false;status.textContent='Image ready. The preview is a guide; placement depends on your product.';});
  form.addEventListener('submit',event=>{el('email').required=event.submitter?.value==='request';for(let i=0;i<4;i++)if(!validate(i)){event.preventDefault();return;}show(3);feedback.textContent=event.submitter?.value==='request'?'Saving your request…':'Adding your design…';form.setAttribute('aria-busy','true');});
})();
