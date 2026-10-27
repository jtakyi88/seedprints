(() => {
  'use strict';
  const config = window.seedprintsCommerce;
  if (!config) return;
  const bag = document.getElementById('spfix-bag');
  const live = document.getElementById('spfix-feedback');
  const openers = new WeakMap();
  const header = document.querySelector('.spfix-header');
  const compactHeader = () => header?.classList.toggle('spfix-compact', window.scrollY > 80);
  window.addEventListener('scroll', compactHeader, {passive:true});
  compactHeader();
  function openDialog(dialog, opener) {
    if (!dialog || dialog.open || typeof dialog.showModal !== 'function') return;
    const origin = opener || document.activeElement;
    const parent = origin?.closest?.('dialog[open]');
    openers.set(dialog, parent ? (openers.get(parent) || origin) : origin);
    dialog.showModal();
    (dialog.querySelector('input[type="search"]') || dialog.querySelector('.spfix-close'))?.focus();
  }
  document.addEventListener('click', event => {
    const opener = event.target.closest('[data-spfix-open]');
    if (!opener) return;
    const dialog = document.getElementById(opener.dataset.spfixOpen);
    if (dialog && typeof dialog.showModal === 'function') {
      event.preventDefault();
      openDialog(dialog, opener);
      if (dialog === bag) refreshBag();
    }
  });
  document.querySelectorAll('.spfix-dialog').forEach(dialog => {
    dialog.addEventListener('close', () => {
      const opener = openers.get(dialog);
      if (opener?.isConnected) opener.focus();
    });
    dialog.addEventListener('click', event => {
      if (event.target !== dialog) return;
      const rect = dialog.getBoundingClientRect();
      if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) dialog.close();
    });
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') document.querySelectorAll('.spfix-mobile-menu[open]').forEach(menu => {
      menu.open = false;
      menu.querySelector('summary')?.focus();
    });
  });
  function fragments(data) {
    if (!data?.fragments) return;
    Object.entries(data.fragments).forEach(([selector, html]) => {
      document.querySelectorAll(selector).forEach(node => { node.outerHTML = html; });
    });
    updateBagLabel();
  }
  function updateBagLabel() {
    const count = Number(document.querySelector('.spfix-bag-count')?.textContent || 0);
    document.querySelector('.spfix-bag-trigger')?.setAttribute('aria-label', `Bag ${count} ${count === 1 ? 'item' : 'items'}. Open your bag.`);
  }
  updateBagLabel();
  function added(opener) {
    if (live) live.textContent = 'Added to your bag.';
    const status = bag?.querySelector('.spfix-bag-status');
    if (status) status.textContent = 'Added to your bag.';
    openDialog(bag, opener);
  }
  async function refreshBag() {
    const status = bag?.querySelector('.spfix-bag-status');
    if (status) status.textContent = 'Updating your bag…';
    try {
      const response = await fetch(config.refreshUrl, {method:'POST',credentials:'same-origin'});
      if (!response.ok) throw new Error('request');
      const result = await response.json();
      if (!result.fragments) throw new Error('response');
      fragments(result);
      if (status) status.textContent = '';
    } catch {
      if (status) status.textContent = 'Your bag could not be refreshed. Open View bag to check it.';
    }
  }
  if (window.jQuery) {
    window.jQuery(document.body).on('wc_fragments_loaded.spfix wc_fragments_refreshed.spfix', updateBagLabel);
    window.jQuery(document.body).on('added_to_cart.spfix', (event, items, hash, button) => {
      fragments({fragments:items});
      added(button?.[0]);
    });
    window.jQuery(document.body).on('found_variation.spfix', 'form.variations_form', function (event, variation) {
      this.dataset.spfixAvailable = variation.is_in_stock && variation.is_purchasable && variation.variation_is_active ? 'yes' : 'no';
      this.dataset.spfixVariation = String(variation.variation_id || '');
      this.dataset.spfixPrice = String(variation.display_price || 0);
      this.dataset.spfixSelection = selectionSignature(this);
      const id = this.querySelector('input[name="variation_id"]');
      if (id) id.value = variation.variation_id || '0';
      const parent = this.querySelector('input[name="add-to-cart"]');
      if (parent && this.dataset.product_id) parent.value = this.dataset.product_id;
      const feedback = this.querySelector('.spfix-options-feedback');
      if (feedback && !missingOptions(this).length) feedback.textContent = '';
      syncOptions(this);
    });
    window.jQuery(document.body).on('reset_data.spfix hide_variation.spfix', 'form.variations_form', function () {
      delete this.dataset.spfixAvailable;
      delete this.dataset.spfixVariation;
      delete this.dataset.spfixSelection;
      syncOptions(this);
    });
  }
  function missingOptions(form) {
    return [...form.querySelectorAll('select[name^="attribute_"]')].filter(select => !select.value);
  }
  function selectionSignature(form) {
    return JSON.stringify([...form.querySelectorAll('select[name^="attribute_"]')].map(select => [select.name,select.value]));
  }
  function syncOptions(form) {
    const button = form.querySelector('.single_add_to_cart_button');
    if (!button || form.dataset.spfixBusy === 'yes') return;
    const missing = missingOptions(form);
    if (missing.length) {
      button.disabled = false;
      button.classList.remove('disabled', 'wc-variation-selection-needed', 'wc-variation-is-unavailable');
    } else if (form.dataset.spfixAvailable === 'no') {
      button.disabled = true;
    }
    form.querySelectorAll('.variations .st-swatch-preview').forEach(group => {
      group.setAttribute('role','group');
      group.setAttribute('aria-label', `Choose ${(group.dataset.attribute || 'an option').replace(/^pa_/, '').replaceAll('_',' ')}`);
    });
    form.querySelectorAll('.variations li').forEach(item => {
      const swatch = item.querySelector('[data-name]');
      if (!swatch) return;
      item.tabIndex = item.classList.contains('sten-li-disabled') || item.classList.contains('out-of-stock') ? -1 : 0;
      item.setAttribute('role', 'button');
      item.setAttribute('aria-label', swatch.dataset.name);
      item.setAttribute('aria-pressed', String(item.classList.contains('selected')));
    });
  }
  document.querySelectorAll('form.variations_form').forEach(form => {
    syncOptions(form);
    form.addEventListener('change', () => setTimeout(() => syncOptions(form), 0));
    form.addEventListener('keydown', event => {
      const swatch = event.target.closest('.variations li[role="button"]');
      if (swatch && (event.key === 'Enter' || event.key === ' ')) {
        event.preventDefault();
        swatch.querySelector('.st-custom-attribute')?.click();
      }
    });
  });
  document.querySelectorAll('.woocommerce-tabs [role="tablist"]').forEach(tablist => {
    const tabs = [...tablist.querySelectorAll('a[role="tab"]')];
    function activate(tab) {
      tabs.forEach(item => {
        const active = item === tab;
        item.setAttribute('aria-selected', String(active));
        item.tabIndex = active ? 0 : -1;
      });
    }
    const initial = tabs.find(tab => tab.parentElement.classList.contains('active')) || tabs[0];
    if (initial) activate(initial);
    tabs.forEach(tab => {
      tab.addEventListener('click', () => activate(tab));
      tab.addEventListener('keydown', event => {
        let index = tabs.indexOf(tab);
        if (event.key === 'ArrowRight') index = (index+1)%tabs.length;
        else if (event.key === 'ArrowLeft') index = (index+tabs.length-1)%tabs.length;
        else if (event.key === 'Home') index = 0;
        else if (event.key === 'End') index = tabs.length-1;
        else return;
        event.preventDefault();
        tabs[index].focus();
        tabs[index].click();
      });
    });
  });
  document.addEventListener('click', event => {
    const button = event.target.closest('form.variations_form .single_add_to_cart_button');
    if (!button) return;
    const form = button.closest('form');
    const missing = missingOptions(form);
    if (!missing.length) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    const labels = missing.map(select => form.querySelector(`label[for="${CSS.escape(select.id)}"]`)?.textContent.trim() || select.name.replace('attribute_pa_', '').replace('attribute_', ''));
    const feedback = form.querySelector('.spfix-options-feedback');
    if (feedback) feedback.textContent = `Choose ${labels.join(' and ')} to add this item.`;
    const choice = missing[0].closest('tr')?.querySelector('li[tabindex="0"], [data-sp-option]:not(:disabled)');
    (choice || missing[0]).focus();
  }, true);
  function initCart(form) {
    if (form.querySelector('[data-sp-native-configuration], [name="prad_selection"]')) return;
    if (form.dataset.spfixBound) return;
    form.dataset.spfixBound = 'yes';
    form.addEventListener('submit', async event => {
      const button = event.submitter || form.querySelector('.single_add_to_cart_button');
      if (!button?.classList.contains('single_add_to_cart_button')) return;
      if (form.dataset.spfixBusy === 'yes') { event.preventDefault(); return; }
      if (missingOptions(form).length || button.disabled) { event.preventDefault(); return; }
      const data = new FormData(form);
      const variable = form.classList.contains('variations_form');
      if (variable && (!form.dataset.spfixVariation || form.dataset.spfixAvailable !== 'yes' || form.dataset.spfixSelection !== selectionSignature(form))) {
        event.preventDefault();
        const message = form.querySelector('.spfix-options-feedback');
        if (message) message.textContent = 'Checking this option’s availability. Please try again in a moment.';
        return;
      }
      const variation = variable ? form.dataset.spfixVariation : data.get('variation_id');
      const product = variation && variation !== '0' ? variation : data.get('add-to-cart') || button.value;
      if (!product) return;
      event.preventDefault();
      // Only the Woo AJAX endpoint may perform this add. The classic
      // add-to-cart field would also trigger WC_Form_Handler on wp_loaded.
      data.delete('add-to-cart');
      data.set('product_id', product);
      data.set('quantity', data.get('quantity') || '1');
      form.dataset.spfixBusy = 'yes';
      button.disabled = true;
      button.setAttribute('aria-busy', 'true');
      const label = button.querySelector('.button-text') || button;
      const original = label.textContent;
      label.textContent = 'Adding…';
      const feedback = form.querySelector('.spfix-options-feedback');
      try {
        const response = await fetch(config.addUrl, {method:'POST',credentials:'same-origin',body:data});
        if (!response.ok) throw new Error('request');
        const result = await response.json();
        if (result.error || !result.fragments) throw new Error('validation');
        fragments(result);
        if (feedback) feedback.textContent = '';
        if (window.jQuery) window.jQuery(document.body).trigger('added_to_cart', [result.fragments, result.cart_hash, window.jQuery(button)]);
        else added(button);
      } catch {
        if (feedback) feedback.textContent = 'This item could not be added. Check your options and try again.';
        else if (live) live.textContent = 'This item could not be added. Please try again.';
      } finally {
        label.textContent = original;
        delete form.dataset.spfixBusy;
        button.disabled = false;
        button.removeAttribute('aria-busy');
        if (form.classList.contains('variations_form')) syncOptions(form);
      }
    });
  }
  window.seedprintsDialogs = {open:openDialog, initCart, refreshBag};
  document.querySelectorAll('form.cart:not(.grouped_form)').forEach(initCart);

  bag?.addEventListener('click', async event => {
    const link = event.target.closest('.spfix-remove');
    if (!link || link.getAttribute('aria-busy') === 'true') return;
    event.preventDefault();
    link.setAttribute('aria-busy', 'true');
    const originalLabel = link.textContent;
    link.textContent = 'Removing…';
    bag.querySelector('.spfix-bag-status').textContent = 'Removing item…';
    const data = new FormData();
    data.set('cart_item_key', link.dataset.cartKey);
    try {
      const response = await fetch(config.removeUrl, {method:'POST',credentials:'same-origin',body:data});
      if (!response.ok) throw new Error('request');
      const result = await response.json();
      if (!result.fragments) throw new Error('response');
      fragments(result);
      bag.querySelector('.spfix-bag-status').textContent = 'Item removed from your bag.';
      bag.querySelector('.spfix-close')?.focus();
      if (window.jQuery) {
        window.jQuery(document.body).trigger('removed_from_cart', [result.fragments, result.cart_hash, window.jQuery(link)]);
        if (document.body.classList.contains('woocommerce-cart')) window.jQuery(document.body).trigger('wc_update_cart');
      }
    } catch {
      bag.querySelector('.spfix-bag-status').textContent = 'The item could not be removed. Please try again.';
      link.removeAttribute('aria-busy');
      link.textContent = originalLabel;
    }
  });
  if (bag?.hasAttribute('data-spfix-auto-open')) openDialog(bag);
})();
