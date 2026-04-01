// Header scroll effect
const header = document.querySelector(".header")
let lastScroll = 0

window.addEventListener("scroll", () => {
  const currentScroll = window.pageYOffset

  if (currentScroll > 50) {
    header.classList.add("scrolled")
  } else {
    header.classList.remove("scrolled")
  }

  lastScroll = currentScroll
})

// Mobile menu toggle
const menuToggle = document.querySelector(".menu-toggle")
const nav = document.querySelector(".nav")

menuToggle.addEventListener("click", () => {
  nav.style.display = nav.style.display === "flex" ? "none" : "flex"

  // Animate hamburger menu
  const spans = menuToggle.querySelectorAll("span")
  if (nav.style.display === "flex") {
    spans[0].style.transform = "rotate(45deg) translateY(8px)"
    spans[1].style.opacity = "0"
    spans[2].style.transform = "rotate(-45deg) translateY(-8px)"
  } else {
    spans[0].style.transform = "none"
    spans[1].style.opacity = "1"
    spans[2].style.transform = "none"
  }
})

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener("click", function (e) {
    e.preventDefault()
    const target = document.querySelector(this.getAttribute("href"))

    if (target) {
      target.scrollIntoView({
        behavior: "smooth",
        block: "start",
      })

      // Close mobile menu if open
      if (window.innerWidth <= 968) {
        nav.style.display = "none"
        const spans = menuToggle.querySelectorAll("span")
        spans[0].style.transform = "none"
        spans[1].style.opacity = "1"
        spans[2].style.transform = "none"
      }
    }
  })
})

// Intersection Observer for scroll animations
const observerOptions = {
  threshold: 0.1,
  rootMargin: "0px 0px -50px 0px",
}

const observer = new IntersectionObserver((entries) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) {
      entry.target.style.opacity = "1"
      entry.target.style.transform = "translateY(0)"
    }
  })
}, observerOptions)

// Observe all cards for animation
const cards = document.querySelectorAll(".category-card, .product-card, .benefit-card")
cards.forEach((card) => {
  card.style.opacity = "0"
  card.style.transform = "translateY(30px)"
  card.style.transition = "opacity 0.6s ease, transform 0.6s ease"
  observer.observe(card)
})

// Product card hover effect with 3D tilt
const productCards = document.querySelectorAll(".product-card")

productCards.forEach((card) => {
  card.addEventListener("mousemove", (e) => {
    const rect = card.getBoundingClientRect()
    const x = e.clientX - rect.left
    const y = e.clientY - rect.top

    const centerX = rect.width / 2
    const centerY = rect.height / 2

    const rotateX = (y - centerY) / 20
    const rotateY = (centerX - x) / 20

    card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.02)`
  })

  card.addEventListener("mouseleave", () => {
    card.style.transform = "perspective(1000px) rotateX(0) rotateY(0) scale(1)"
  })
})

// Category card click animation
const categoryCards = document.querySelectorAll(".category-card")

categoryCards.forEach((card) => {
  card.addEventListener("click", () => {
    card.style.transform = "scale(0.95)"
    setTimeout(() => {
      card.style.transform = "scale(1)"
    }, 150)
  })
})

// Parallax effect for hero image
const heroImage = document.querySelector(".hero-image")

window.addEventListener("scroll", () => {
  if (heroImage && window.innerWidth > 968) {
    const scrolled = window.pageYOffset
    const rate = scrolled * 0.3
    heroImage.style.transform = `translateY(${rate}px)`
  }
})

// Button ripple effect
const buttons = document.querySelectorAll(".btn")

buttons.forEach((button) => {
  button.addEventListener("click", function (e) {
    const ripple = document.createElement("span")
    const rect = this.getBoundingClientRect()
    const size = Math.max(rect.width, rect.height)
    const x = e.clientX - rect.left - size / 2
    const y = e.clientY - rect.top - size / 2

    ripple.style.width = ripple.style.height = size + "px"
    ripple.style.left = x + "px"
    ripple.style.top = y + "px"
    ripple.style.position = "absolute"
    ripple.style.borderRadius = "50%"
    ripple.style.background = "rgba(255, 255, 255, 0.5)"
    ripple.style.transform = "scale(0)"
    ripple.style.animation = "ripple 0.6s ease-out"
    ripple.style.pointerEvents = "none"

    this.style.position = "relative"
    this.style.overflow = "hidden"
    this.appendChild(ripple)

    setTimeout(() => ripple.remove(), 600)
  })
})

// Add ripple animation to CSS dynamically
const style = document.createElement("style")
style.textContent = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`
document.head.appendChild(style)

// Counter animation for numbers (if you want to add statistics)
function animateCounter(element, target, duration = 2000) {
  let start = 0
  const increment = target / (duration / 16)

  const timer = setInterval(() => {
    start += increment
    if (start >= target) {
      element.textContent = target
      clearInterval(timer)
    } else {
      element.textContent = Math.floor(start)
    }
  }, 16)
}

// Lazy loading images
const images = document.querySelectorAll("img")

const imageObserver = new IntersectionObserver((entries) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) {
      const img = entry.target
      img.style.opacity = "0"
      img.style.transition = "opacity 0.5s ease"

      img.onload = () => {
        img.style.opacity = "1"
      }

      imageObserver.unobserve(img)
    }
  })
})

images.forEach((img) => imageObserver.observe(img))

console.log("Ofiequipo website loaded successfully! 🎉")
