window.addEventListener('scroll', function() {
  const scrolled = window.pageYOffset;
  const parallax = document.querySelector('.parallax-img');
  
  // ajusta o valor para controlar a velocidade do efeito
  parallax.style.transform = 'translateY(' + (scrolled * 0.5) + 'px)';
});
