
document.addEventListener("DOMContentLoaded", () => {
  const msgEl = document.getElementById("userMsg");
  if (!msgEl) return; 

  const messages = [
    "Welcome to HATRO:X",
    "Luxury that loves you back",
    "you're cute 😘",
    "You deserve something special ✨",
    "You + HATRO:X = perfect combo 💖",
    "Jewels that match your glow ✨",
    "Handpicked, just for you 💎",
    "Shine with every detail",
    "Subtle flex. Massive impact.",
    "Your vibe says ‘premium only’ 😉",
    "Classic today, iconic forever.",
  ];

  let msgIndex = 0;
  let charIndex = 0;

  const TYPE_SPEED = 130;        // ms per character 
  const WAIT_AFTER_TYPE = 3000;  // pause after full word
  const FADE_DURATION = 0.6;     // seconds 

  function typeNextChar() {
    const current = messages[msgIndex];

    if (charIndex === 0) {
      msgEl.textContent = "";
      gsap.set(msgEl, { opacity: 0, y: 2 });
      gsap.to(msgEl, {
        opacity: 1,
        y: 0,
        duration: 0.3,
        ease: "power1.out"
      });
    }

    if (charIndex <= current.length) {
      msgEl.textContent = current.substring(0, charIndex);
      charIndex++;
      setTimeout(typeNextChar, TYPE_SPEED);
    } else {
      setTimeout(() => {
        gsap.to(msgEl, {
          opacity: 0,
          y: -3,
          duration: FADE_DURATION,
          ease: "power2.inOut",
          onComplete: () => {
            msgIndex = (msgIndex + 1) % messages.length;
            charIndex = 0;
            typeNextChar();
          }
        });
      }, WAIT_AFTER_TYPE);
    }
  }

  gsap.set(msgEl, { opacity: 1 });
  typeNextChar();
});
