(function(){
  // Smooth scroll for anchor CTAs
  document.querySelectorAll('a[href^="#"]').forEach(link => {
    link.addEventListener('click', (e)=>{
      const targetId = link.getAttribute('href').slice(1);
      const target = document.getElementById(targetId);
      if(target){
        e.preventDefault();
        const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if(prefersReduced){
          target.focus();
          window.scrollTo({top: target.offsetTop - 20});
        } else {
          target.scrollIntoView({behavior: 'smooth', block: 'start'});
          setTimeout(()=> target.focus(), 600);
        }
      }
    });
  });

  // Simple focus management for accessibility
  document.querySelectorAll('a, button').forEach(el => {
    el.setAttribute('tabindex', '0');
  });
})();