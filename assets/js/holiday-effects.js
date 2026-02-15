/**
 * Holiday Effects Engine
 * Handles special animations for holiday themes.
 */
document.addEventListener('DOMContentLoaded', () => {
    const config = window.themeEffectConfig;
    if (!config || !config.type) return;

    const container = document.getElementById('themeEffectLayer');
    if (!container) return;

    console.log('✨ Initializing Holiday Effect:', config.type);

    switch (config.type) {
        case 'fireworks':
            initFireworks(container);
            break;
        case 'confetti':
            initConfetti(container, config.activeKey === 'kemerdekaan' || config.activeKey === '17agustus' ? ['#d62828', '#ffffff'] : ['#facc15', '#ef4444', '#ffffff']);
            break;
        case 'lanterns':
            initLanterns(container);
            break;
        case 'flowers':
            initFallingItems(container, '🌸');
            break;
        case 'twinkle':
            initTwinkle(container);
            break;
        case 'snow':
            initSnow(container);
            break;
    }
});

function initFireworks(container) {
    const canvas = document.createElement('canvas');
    canvas.style.width = '100%';
    canvas.style.height = '100%';
    container.appendChild(canvas);
    const ctx = canvas.getContext('2d');

    let particles = [];

    function resize() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }
    window.addEventListener('resize', resize);
    resize();

    class Particle {
        constructor(x, y, color) {
            this.x = x;
            this.y = y;
            this.color = color;
            this.velocity = {
                x: (Math.random() - 0.5) * 5,
                y: (Math.random() - 0.5) * 5
            };
            this.alpha = 1;
            this.size = 2 + Math.random() * 3;
        }
        draw() {
            ctx.save();
            ctx.globalAlpha = this.alpha;
            ctx.shadowBlur = 10;
            ctx.shadowColor = this.color;
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fillStyle = this.color;
            ctx.fill();
            ctx.restore();
        }
        update() {
            this.x += this.velocity.x;
            this.y += this.velocity.y;
            this.velocity.y += 0.05; // Soft gravity
            this.alpha -= 0.005;
        }
    }

    function spawnFirework() {
        const x = Math.random() * canvas.width;
        const y = Math.random() * (canvas.height * 0.4);
        const colors = ['#facc15', '#fbbf24', '#ffffff'];
        const color = colors[Math.floor(Math.random() * colors.length)];
        for (let i = 0; i < 25; i++) {
            particles.push(new Particle(x, y, color));
        }
    }

    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        particles = particles.filter(p => p.alpha > 0);
        particles.forEach(p => {
            p.update();
            p.draw();
        });

        if (Math.random() < 0.01) spawnFirework(); // Reduced frequency
        requestAnimationFrame(animate);
    }
    animate();
}

function initConfetti(container, colors) {
    for (let i = 0; i < 20; i++) { // Reduced count
        const confetti = document.createElement('div');
        confetti.className = 'confetti-piece';
        confetti.style.cssText = `
            position: absolute;
            width: 8px;
            height: 8px;
            background: ${colors[Math.floor(Math.random() * colors.length)]};
            top: -20px;
            left: ${Math.random() * 100}%;
            opacity: ${0.3 + Math.random() * 0.4};
            transform: rotate(${Math.random() * 360}deg);
            animation: fall ${6 + Math.random() * 8}s linear infinite; // Slower speed
            animation-delay: ${Math.random() * 10}s;
        `;
        container.appendChild(confetti);
    }

    const style = document.createElement('style');
    style.textContent = `
        @keyframes fall {
            to {
                transform: translateY(${window.innerHeight + 20}px) rotate(${Math.random() * 720}deg);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
}

function initLanterns(container) {
    const canvas = document.createElement('canvas');
    canvas.style.width = '100%';
    canvas.style.height = '100%';
    container.appendChild(canvas);
    const ctx = canvas.getContext('2d');

    let lanterns = [];

    function resize() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }
    window.addEventListener('resize', resize);
    resize();

    class Lantern {
        constructor() {
            this.x = Math.random() * canvas.width;
            this.y = canvas.height + 100;
            this.size = 20 + Math.random() * 20;
            this.speed = 0.5 + Math.random() * 1;
            this.wobble = Math.random() * Math.PI * 2;
            this.wobbleSpeed = 0.02 + Math.random() * 0.03;
            this.opacity = 0.6 + Math.random() * 0.4;
        }
        update() {
            this.y -= this.speed;
            this.wobble += this.wobbleSpeed;
            this.x += Math.sin(this.wobble) * 0.5;
            if (this.y < -100) {
                this.y = canvas.height + 100;
                this.x = Math.random() * canvas.width;
            }
        }
        draw() {
            ctx.save();
            ctx.globalAlpha = this.opacity;
            ctx.translate(this.x, this.y);

            // Outer Glow
            ctx.shadowBlur = 20;
            ctx.shadowColor = '#fbbf24';

            // Lantern Body
            ctx.beginPath();
            ctx.roundRect(-this.size / 2, -this.size / 2, this.size, this.size * 1.2, 8);
            ctx.fillStyle = '#991b1b';
            ctx.fill();

            // Inner Core Glow
            ctx.beginPath();
            ctx.roundRect(-this.size / 4, -this.size / 4, this.size / 2, this.size * 0.8, 4);
            ctx.fillStyle = '#fbbf24';
            ctx.fill();

            // Tassels
            ctx.beginPath();
            ctx.moveTo(0, this.size * 0.7);
            ctx.lineTo(-5, this.size * 1.1);
            ctx.lineTo(5, this.size * 1.1);
            ctx.closePath();
            ctx.fillStyle = '#d97706';
            ctx.fill();

            ctx.restore();
        }
    }

    function init() {
        for (let i = 0; i < 12; i++) {
            lanterns.push(new Lantern());
        }
    }

    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        lanterns.forEach(l => {
            l.update();
            l.draw();
        });
        requestAnimationFrame(animate);
    }

    init();
    animate();
}

function initFallingItems(container, char) {
    for (let i = 0; i < 20; i++) {
        const item = document.createElement('div');
        item.textContent = char;
        item.style.cssText = `
            position: absolute;
            font-size: ${15 + Math.random() * 15}px;
            top: -50px;
            left: ${Math.random() * 100}%;
            opacity: 0.5;
            animation: fallSway ${5 + Math.random() * 10}s linear infinite;
            animation-delay: ${Math.random() * 5}s;
        `;
        container.appendChild(item);
    }

    const style = document.createElement('style');
    style.textContent = `
        @keyframes fallSway {
            from { transform: translateY(0) rotate(0); }
            to { transform: translateY(${window.innerHeight + 100}px) rotate(360deg) translateX(${Math.random() * 40 - 20}px); }
        }
    `;
    document.head.appendChild(style);
}

function initTwinkle(container) {
    for (let i = 0; i < 40; i++) {
        const star = document.createElement('div');
        star.style.cssText = `
            position: absolute;
            width: 2px;
            height: 2px;
            background: white;
            top: ${Math.random() * 100}%;
            left: ${Math.random() * 100}%;
            border-radius: 50%;
            box-shadow: 0 0 5px white;
            animation: twinkle ${1 + Math.random() * 2}s ease-in-out infinite alternate;
            animation-delay: ${Math.random() * 2}s;
        `;
        container.appendChild(star);
    }

    const style = document.createElement('style');
    style.textContent = `
        @keyframes twinkle {
            from { opacity: 0.2; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1.2); }
        }
    `;
    document.head.appendChild(style);
}

function initSnow(container) {
    const canvas = document.createElement('canvas');
    canvas.style.width = '100%';
    canvas.style.height = '100%';
    container.appendChild(canvas);
    const ctx = canvas.getContext('2d');

    let particles = [];

    function resize() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }
    window.addEventListener('resize', resize);
    resize();

    class Snowflake {
        constructor() {
            this.x = Math.random() * canvas.width;
            this.y = Math.random() * -canvas.height;
            this.size = 2 + Math.random() * 4;
            this.speed = 1 + Math.random() * 2;
            this.velX = (Math.random() - 0.5) * 1;
            this.opacity = 0.5 + Math.random() * 0.5;
        }
        update() {
            this.y += this.speed;
            this.x += this.velX;
            if (this.y > canvas.height) {
                this.y = -10;
                this.x = Math.random() * canvas.width;
            }
        }
        draw() {
            ctx.save();
            ctx.globalAlpha = this.opacity;
            ctx.shadowBlur = 5;
            ctx.shadowColor = '#ffffff';
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fillStyle = '#ffffff';
            ctx.fill();
            ctx.restore();
        }
    }

    function init() {
        for (let i = 0; i < 50; i++) {
            particles.push(new Snowflake());
        }
    }

    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        particles.forEach(p => {
            p.update();
            p.draw();
        });
        requestAnimationFrame(animate);
    }

    init();
    animate();
}
