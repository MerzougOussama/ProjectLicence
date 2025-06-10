// Script JavaScript unifié pour le site de bibliothèque

document.addEventListener("DOMContentLoaded", () => {
  // Navigation mobile
  initMobileNavigation()

  // Confirmation des actions
  initConfirmations()

  // Filtres de produits
  initProductFilters()

  // Validation des formulaires
  initFormValidation()

  // Gestion des étoiles pour les avis
  initStarRating()

  // Messages d'alerte auto-dismiss
  initAlertDismiss()
})

// Navigation mobile
function initMobileNavigation() {
  const navbar = document.querySelector(".navbar")
  const navMenu = document.querySelector(".nav-menu")

  // Créer un bouton hamburger pour mobile
  const hamburger = document.createElement("button")
  hamburger.className = "hamburger"
  hamburger.innerHTML = "☰"
  hamburger.style.display = "none"
  hamburger.style.background = "none"
  hamburger.style.border = "none"
  hamburger.style.color = "white"
  hamburger.style.fontSize = "1.5rem"
  hamburger.style.cursor = "pointer"

  const navContainer = document.querySelector(".nav-container")
  navContainer.appendChild(hamburger)

  // Toggle menu mobile
  hamburger.addEventListener("click", () => {
    navMenu.classList.toggle("mobile-active")
  })

  // Responsive check
  function checkMobile() {
    if (window.innerWidth <= 768) {
      hamburger.style.display = "block"
      navMenu.style.display = navMenu.classList.contains("mobile-active") ? "flex" : "none"
    } else {
      hamburger.style.display = "none"
      navMenu.style.display = "flex"
      navMenu.classList.remove("mobile-active")
    }
  }

  window.addEventListener("resize", checkMobile)
  checkMobile()
}

// Confirmations des actions
function initConfirmations() {
  // Confirmation d'achat
  const buyButtons = document.querySelectorAll('button[name="buy_product"]')
  buyButtons.forEach((button) => {
    button.addEventListener("click", (e) => {
      if (!confirm("Êtes-vous sûr de vouloir acheter ce produit ?")) {
        e.preventDefault()
      }
    })
  })

  // Confirmation de suppression
  const deleteButtons = document.querySelectorAll(".btn-delete")
  deleteButtons.forEach((button) => {
    button.addEventListener("click", (e) => {
      if (!confirm("Êtes-vous sûr de vouloir supprimer cet élément ?")) {
        e.preventDefault()
      }
    })
  })
}

// Filtres de produits
function initProductFilters() {
  const filterForm = document.querySelector(".filter-form")
  if (!filterForm) return

  const searchInput = filterForm.querySelector('input[name="search"]')
  const categorySelect = filterForm.querySelector('select[name="category"]')

  // Auto-submit sur changement de catégorie
  if (categorySelect) {
    categorySelect.addEventListener("change", () => {
      filterForm.submit()
    })
  }

  // Recherche en temps réel (avec délai)
  if (searchInput) {
    let searchTimeout
    searchInput.addEventListener("input", function () {
      clearTimeout(searchTimeout)
      searchTimeout = setTimeout(() => {
        if (this.value.length >= 3 || this.value.length === 0) {
          filterForm.submit()
        }
      }, 500)
    })
  }
}

// Validation des formulaires
function initFormValidation() {
  // Validation du formulaire d'inscription
  const signupForm = document.querySelector('form[action*="signup"]')
  if (signupForm) {
    signupForm.addEventListener("submit", function (e) {
      const password = this.querySelector('input[name="password"]').value
      const confirmPassword = this.querySelector('input[name="confirm_password"]').value

      if (password !== confirmPassword) {
        e.preventDefault()
        showAlert("Les mots de passe ne correspondent pas.", "error")
        return false
      }

      if (password.length < 6) {
        e.preventDefault()
        showAlert("Le mot de passe doit contenir au moins 6 caractères.", "error")
        return false
      }
    })
  }

  // Validation du formulaire d'ajout de produit
  const productForm = document.querySelector('form[action*="add_product"]')
  if (productForm) {
    productForm.addEventListener("submit", function (e) {
      const title = this.querySelector('input[name="title"]').value.trim()
      const price = this.querySelector('input[name="price"]').value
      const category = this.querySelector('select[name="category_id"]').value

      if (!title || !price || !category) {
        e.preventDefault()
        showAlert("Veuillez remplir tous les champs obligatoires.", "error")
        return false
      }

      if (Number.parseFloat(price) <= 0) {
        e.preventDefault()
        showAlert("Le prix doit être supérieur à 0.", "error")
        return false
      }
    })
  }
}

// Gestion des étoiles pour les avis
function initStarRating() {
  const ratingInputs = document.querySelectorAll(".star-rating")

  ratingInputs.forEach((ratingContainer) => {
    const stars = ratingContainer.querySelectorAll(".star-input")
    const hiddenInput = ratingContainer.querySelector('input[type="hidden"]')

    stars.forEach((star, index) => {
      star.addEventListener("click", () => {
        const rating = index + 1
        hiddenInput.value = rating

        // Mettre à jour l'affichage des étoiles
        stars.forEach((s, i) => {
          if (i < rating) {
            s.classList.add("filled")
            s.textContent = "⭐"
          } else {
            s.classList.remove("filled")
            s.textContent = "☆"
          }
        })
      })

      star.addEventListener("mouseover", () => {
        const rating = index + 1

        stars.forEach((s, i) => {
          if (i < rating) {
            s.style.color = "#f39c12"
          } else {
            s.style.color = "#ddd"
          }
        })
      })
    })

    ratingContainer.addEventListener("mouseleave", () => {
      const currentRating = Number.parseInt(hiddenInput.value) || 0

      stars.forEach((s, i) => {
        if (i < currentRating) {
          s.style.color = "#f39c12"
        } else {
          s.style.color = "#ddd"
        }
      })
    })
  })
}

// Messages d'alerte auto-dismiss
function initAlertDismiss() {
  const alerts = document.querySelectorAll(".alert")

  alerts.forEach((alert) => {
    // Ajouter un bouton de fermeture
    const closeBtn = document.createElement("button")
    closeBtn.innerHTML = "×"
    closeBtn.style.float = "right"
    closeBtn.style.background = "none"
    closeBtn.style.border = "none"
    closeBtn.style.fontSize = "1.2rem"
    closeBtn.style.cursor = "pointer"
    closeBtn.style.marginLeft = "10px"

    closeBtn.addEventListener("click", () => {
      alert.style.display = "none"
    })

    alert.appendChild(closeBtn)

    // Auto-dismiss après 5 secondes pour les messages de succès
    if (alert.classList.contains("alert-success")) {
      setTimeout(() => {
        alert.style.opacity = "0"
        setTimeout(() => {
          alert.style.display = "none"
        }, 300)
      }, 5000)
    }
  })
}

// Fonction utilitaire pour afficher des alertes
function showAlert(message, type = "info") {
  const alertDiv = document.createElement("div")
  alertDiv.className = `alert alert-${type}`
  alertDiv.textContent = message

  // Insérer l'alerte en haut de la page
  const main = document.querySelector("main")
  if (main) {
    main.insertBefore(alertDiv, main.firstChild)

    // Auto-dismiss
    setTimeout(() => {
      alertDiv.style.opacity = "0"
      setTimeout(() => {
        alertDiv.remove()
      }, 300)
    }, 3000)
  }
}

// Fonction pour formater les prix
function formatPrice(price) {
  return new Intl.NumberFormat("fr-FR", {
    style: "currency",
    currency: "EUR",
  }).format(price)
}

// Fonction pour valider les emails
function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

// Fonction pour gérer le loading des boutons
function setButtonLoading(button, loading = true) {
  if (loading) {
    button.disabled = true
    button.dataset.originalText = button.textContent
    button.textContent = "Chargement..."
  } else {
    button.disabled = false
    button.textContent = button.dataset.originalText || button.textContent
  }
}

// Fonction pour smooth scroll
function smoothScrollTo(element) {
  element.scrollIntoView({
    behavior: "smooth",
    block: "start",
  })
}

// Gestion des images lazy loading
function initLazyLoading() {
  const images = document.querySelectorAll("img[data-src]")

  const imageObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const img = entry.target
        img.src = img.dataset.src
        img.classList.remove("lazy")
        imageObserver.unobserve(img)
      }
    })
  })

  images.forEach((img) => imageObserver.observe(img))
}

// Initialiser le lazy loading si supporté
if ("IntersectionObserver" in window) {
  initLazyLoading()
}

// Gestion du mode sombre (optionnel)
function initDarkMode() {
  const darkModeToggle = document.querySelector(".dark-mode-toggle")
  if (!darkModeToggle) return

  const currentTheme = localStorage.getItem("theme")
  if (currentTheme === "dark") {
    document.body.classList.add("dark-mode")
  }

  darkModeToggle.addEventListener("click", () => {
    document.body.classList.toggle("dark-mode")
    const theme = document.body.classList.contains("dark-mode") ? "dark" : "light"
    localStorage.setItem("theme", theme)
  })
}

// Fonction pour gérer les notifications push (si implémentées)
function initNotifications() {
  if ("Notification" in window && "serviceWorker" in navigator) {
    // Demander la permission pour les notifications
    if (Notification.permission === "default") {
      Notification.requestPermission()
    }
  }
}

// Fonction pour sauvegarder les données du formulaire
function initFormAutoSave() {
  const forms = document.querySelectorAll("form[data-autosave]")

  forms.forEach((form) => {
    const formId = form.dataset.autosave

    // Charger les données sauvegardées
    const savedData = localStorage.getItem(`form_${formId}`)
    if (savedData) {
      const data = JSON.parse(savedData)
      Object.keys(data).forEach((key) => {
        const input = form.querySelector(`[name="${key}"]`)
        if (input && input.type !== "password") {
          input.value = data[key]
        }
      })
    }

    // Sauvegarder lors de la saisie
    form.addEventListener("input", () => {
      const formData = new FormData(form)
      const data = {}
      for (const [key, value] of formData.entries()) {
        if (key !== "password" && key !== "confirm_password") {
          data[key] = value
        }
      }
      localStorage.setItem(`form_${formId}`, JSON.stringify(data))
    })

    // Nettoyer lors de la soumission
    form.addEventListener("submit", () => {
      localStorage.removeItem(`form_${formId}`)
    })
  })
}

// Initialiser l'auto-save
initFormAutoSave()
