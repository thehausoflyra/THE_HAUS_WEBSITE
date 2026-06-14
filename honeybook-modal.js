/**
 * Haus of Lyra - HoneyBook Inquiry Modal Manager
 */
document.addEventListener('DOMContentLoaded', function() {
  // Find all buttons and links that point to #inquire
  var inquireElements = document.querySelectorAll('a[href*="#inquire"], .nav-btn, [data-action="inquire"]');
  
  inquireElements.forEach(function(el) {
    el.addEventListener('click', function(e) {
      e.preventDefault();
      openInquiryModal();
    });
  });

  function openInquiryModal() {
    var modal = document.getElementById('hbModal');
    
    if (!modal) {
      // Build modal container dynamically
      modal = document.createElement('div');
      modal.className = 'hb-modal-overlay';
      modal.id = 'hbModal';

      // Check if we are on the senior page
      var isSenior = (window.location.pathname.indexOf('senior-portraits') !== -1 || window.location.href.indexOf('senior-portraits') !== -1);
      var placementClass = isSenior ? 'hb-p-69362501a7b735000728c367-6' : 'hb-p-69362501a7b735000728c367-5';

      modal.innerHTML = 
        '<div class="hb-modal-container" style="padding: 2.5rem 1rem 1rem;">' +
          '<button class="hb-modal-close" id="hbClose" aria-label="Close inquiry form">&times;</button>' +
          '<div class="hb-modal-content-wrapper" style="width:100%; height:100%; overflow-y:auto;">' +
            '<div class="' + placementClass + '"></div>' +
            '<img height="1" width="1" style="display:none" src="https://www.honeybook.com/p.png?pid=69362501a7b735000728c367">' +
          '</div>' +
        '</div>';

      // Load the HoneyBook controller script dynamically
      (function(h,b,s,n,i,p,e,t) {
        h._HB_ = h._HB_ || {};h._HB_.pid = i;;;;
        t=b.createElement(s);t.type="text/javascript";t.async=!0;t.src=n;
        e=b.getElementsByTagName(s)[0];e.parentNode.insertBefore(t,e);
      })(window,document,"script","https://widget.honeybook.com/assets_users_production/websiteplacements/placement-controller.min.js","69362501a7b735000728c367");

      document.body.appendChild(modal);

      // Hook up close events
      var closeBtn = modal.querySelector('#hbClose');
      closeBtn.addEventListener('click', closeInquiryModal);
      
      modal.addEventListener('click', function(e) {
        if (e.target === modal) {
          closeInquiryModal();
        }
      });

      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          closeInquiryModal();
        }
      });
    }

    // Show modal
    modal.classList.add('active');
    document.body.classList.add('modal-open');
  }

  function closeInquiryModal() {
    var modal = document.getElementById('hbModal');
    if (modal) {
      modal.classList.remove('active');
      document.body.classList.remove('modal-open');
    }
  }

  // Initialize StarField animation
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!prefersReducedMotion) {
    new StarField();
  } else {
    // Create static stars for accessibility
    const container = document.createElement('div');
    container.className = 'star-field';
    document.body.appendChild(container);
    
    const positions = [
      { x: 25, y: 20 }, { x: 35, y: 35 }, { x: 45, y: 25 }, 
      { x: 55, y: 40 }, { x: 30, y: 45 }
    ];
    
    positions.forEach(pos => {
      const star = document.createElement('div');
      star.className = 'star constellation';
      star.style.left = `${pos.x}%`;
      star.style.top = `${pos.y}%`;
      star.style.opacity = '0.6';
      container.appendChild(star);
    });
  }
});

/* --- Star Field Animation Class --- */
class StarField {
  constructor() {
    this.container = null;
    this.stars = [];
    this.shootingStars = [];
    this.init();
  }

  init() {
    this.createContainer();
    this.generateStars();
    this.generateConstellationStars();
    this.startShootingStars();
  }

  createContainer() {
    this.container = document.createElement('div');
    this.container.className = 'star-field';
    document.body.appendChild(this.container);
  }

  generateStars() {
    const starCounts = {
      small: 150,   // Lots of tiny twinkling stars
      medium: 75,   // Medium brightness stars
      bright: 25    // Fewer bright stars with glow
    };

    Object.entries(starCounts).forEach(([type, count]) => {
      for (let i = 0; i < count; i++) {
        this.createStar(type);
      }
    });
  }

  createStar(type) {
    const star = document.createElement('div');
    star.className = `star ${type}`;
    
    const x = Math.random() * 100;
    const y = Math.random() * 100;
    
    star.style.left = `${x}%`;
    star.style.top = `${y}%`;
    
    const delay = Math.random() * 8;
    star.style.animationDelay = `${delay}s`;
    
    this.container.appendChild(star);
    this.stars.push(star);
  }

  generateConstellationStars() {
    const constellationPositions = [
      { x: 25, y: 20, name: 'Intentionality' },    // Vega (brightest)
      { x: 35, y: 35, name: 'Creativity' },        // Sheliak
      { x: 45, y: 25, name: 'Connection' },        // Sulafat
      { x: 55, y: 40, name: 'Artistry' },         // Delta Lyrae
      { x: 30, y: 45, name: 'Storytelling' }      // Zeta Lyrae
    ];

    constellationPositions.forEach((pos, index) => {
      const star = document.createElement('div');
      star.className = 'star constellation';
      star.style.left = `${pos.x}%`;
      star.style.top = `${pos.y}%`;
      star.style.animationDelay = `${index * 1.6}s`;
      star.title = pos.name; // Tooltip with brand pillar name
      
      this.container.appendChild(star);
    });
  }

  createShootingStar() {
    const shootingStar = document.createElement('div');
    shootingStar.className = 'shooting-star';
    
    const startX = Math.random() * 30;
    const startY = Math.random() * 30;
    
    shootingStar.style.left = `${startX}%`;
    shootingStar.style.top = `${startY}%`;
    
    const duration = 2 + Math.random() * 2;
    shootingStar.style.animation = `shooting-star-move ${duration}s ease-out forwards`;
    
    this.container.appendChild(shootingStar);
    
    setTimeout(() => {
      if (shootingStar.parentNode) {
        shootingStar.parentNode.removeChild(shootingStar);
      }
    }, duration * 1000);
  }

  startShootingStars() {
    const scheduleNext = () => {
      const delay = 8000 + Math.random() * 7000;
      setTimeout(() => {
        this.createShootingStar();
        scheduleNext();
      }, delay);
    };
    
    setTimeout(() => {
      this.createShootingStar();
      scheduleNext();
    }, 5000);
  }
}
