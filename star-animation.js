/*
 * Haus of Lyra - Star Field Animation Script
 * Creates a beautiful starfield with constellation stars and shooting stars
 */

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
    
    // Random position
    const x = Math.random() * 100;
    const y = Math.random() * 100;
    
    star.style.left = `${x}%`;
    star.style.top = `${y}%`;
    
    // Randomize animation delay for natural twinkling
    const delay = Math.random() * 8;
    star.style.animationDelay = `${delay}s`;
    
    this.container.appendChild(star);
    this.stars.push(star);
  }

  generateConstellationStars() {
    // 5 constellation stars representing the 5 brand pillars
    // Positioned to loosely resemble Lyra constellation
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
    
    // Random starting position (usually from top-left quadrant)
    const startX = Math.random() * 30; // Start from left 30%
    const startY = Math.random() * 30; // Start from top 30%
    
    shootingStar.style.left = `${startX}%`;
    shootingStar.style.top = `${startY}%`;
    
    // Animation duration between 2-4 seconds
    const duration = 2 + Math.random() * 2;
    shootingStar.style.animation = `shooting-star-move ${duration}s ease-out forwards`;
    
    this.container.appendChild(shootingStar);
    
    // Remove after animation completes
    setTimeout(() => {
      if (shootingStar.parentNode) {
        shootingStar.parentNode.removeChild(shootingStar);
      }
    }, duration * 1000);
  }

  startShootingStars() {
    // Create a shooting star every 8-15 seconds
    const scheduleNext = () => {
      const delay = 8000 + Math.random() * 7000; // 8-15 seconds
      setTimeout(() => {
        this.createShootingStar();
        scheduleNext();
      }, delay);
    };
    
    // First shooting star after 5 seconds
    setTimeout(() => {
      this.createShootingStar();
      scheduleNext();
    }, 5000);
  }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  // Check for reduced motion preference
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  
  if (!prefersReducedMotion) {
    new StarField();
  } else {
    // Create static stars for accessibility
    const container = document.createElement('div');
    container.className = 'star-field';
    document.body.appendChild(container);
    
    // Just a few static constellation stars
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