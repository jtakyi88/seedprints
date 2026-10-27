(() => {
  const back = document.querySelector('.back-top');
  const footer = document.querySelector('.elementor-location-footer, .footer, footer');
  if (back && footer && 'IntersectionObserver' in window) {
    new IntersectionObserver(([entry]) => {
      back.classList.toggle('spfix-footer-visible', entry.isIntersecting);
      back.inert = entry.isIntersecting;
    }).observe(footer);
  }
})();
