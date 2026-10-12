/**
 * ShopSmart India - Frontend Interactive Engine
 * BCA Semester V Field Project
 */

// Global App Configuration
const ShopSmart = {
  baseUrl: window.location.origin + (window.location.pathname.includes('/ShopSmart') ? '/ShopSmart' : ''),
  csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
  
  // Initialize all client components
  init() {
    this.initSplash();
    this.initSearchSuggestions();
    this.initVoiceSearch();
    this.initImageSearchModal();
    this.initPinCodeSelector();
    this.initCartListeners();
    this.initWishlistListeners();
  },

  // Splash Screen Handler
  initSplash() {
    const splash = document.getElementById('shopsmart-splash');
    if (!splash) return;

    if (sessionStorage.getItem('ss_splash_shown')) {
      splash.style.display = 'none';
      return;
    }

    setTimeout(() => {
      splash.classList.add('fade-out');
      sessionStorage.setItem('ss_splash_shown', 'true');
      setTimeout(() => {
        splash.style.display = 'none';
      }, 500);
    }, 1100);
  },

  // Toast Notification System
  toast(message, type = 'info') {
    let container = document.getElementById('shopsmart-toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'shopsmart-toast-container';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `ss-toast ${type}`;
    
    let icon = 'ℹ️';
    if (type === 'success') icon = '✓';
    if (type === 'error') icon = '✕';

    toast.innerHTML = `<span style="font-weight:700;">${icon}</span> <span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(10px)';
      toast.style.transition = 'all 0.3s ease';
      setTimeout(() => toast.remove(), 300);
    }, 3200);
  },

  // Format INR Currency in JavaScript
  formatINR(amount) {
    const num = Math.round(Number(amount) || 0);
    const str = String(num);
    if (str.length <= 3) return '₹' + str;
    const lastThree = str.substring(str.length - 3);
    const rest = str.substring(0, str.length - 3);
    return '₹' + rest.replace(/\B(?=(\d{2})+(?!\d))/g, ',') + ',' + lastThree;
  },

  // Add product to cart via Fetch API
  async addToCart(productId, quantity = 1, buttonEl = null) {
    if (buttonEl) {
      buttonEl.disabled = true;
      buttonEl.dataset.originalText = buttonEl.innerHTML;
      buttonEl.innerHTML = '<span>Adding...</span>';
    }

    try {
      const response = await fetch(`${this.baseUrl}/api/cart.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': this.csrfToken
        },
        body: JSON.stringify({
          action: 'add',
          product_id: productId,
          quantity: quantity
        })
      });

      const data = await response.json();

      if (data.success) {
        this.toast(data.message || 'Added to Cart successfully!', 'success');
        this.updateCartCountBadge(data.cart_count);
        if (buttonEl) {
          buttonEl.innerHTML = '<span>✓ Added</span>';
          setTimeout(() => {
            buttonEl.innerHTML = buttonEl.dataset.originalText;
            buttonEl.disabled = false;
          }, 1500);
        }
      } else {
        this.toast(data.message || 'Could not add item to cart.', 'error');
        if (buttonEl) {
          buttonEl.innerHTML = buttonEl.dataset.originalText;
          buttonEl.disabled = false;
        }
      }
    } catch (err) {
      console.error('Cart Error:', err);
      this.toast('Connection error. Please try again.', 'error');
      if (buttonEl) {
        buttonEl.innerHTML = buttonEl.dataset.originalText;
        buttonEl.disabled = false;
      }
    }
  },

  // Update header cart count badge
  updateCartCountBadge(count) {
    const badge = document.getElementById('header-cart-count');
    if (badge) {
      badge.textContent = count;
      badge.style.display = count > 0 ? 'inline-block' : 'none';
      badge.classList.add('pulse');
      setTimeout(() => badge.classList.remove('pulse'), 400);
    }
  },

  // Toggle Wishlist via Fetch API
  async toggleWishlist(productId, btnEl) {
    try {
      const response = await fetch(`${this.baseUrl}/api/wishlist.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': this.csrfToken
        },
        body: JSON.stringify({
          product_id: productId
        })
      });

      const data = await response.json();

      if (data.success) {
        if (data.action === 'added') {
          btnEl.classList.add('active');
          btnEl.innerHTML = '❤️';
          this.toast('Added to Wishlist!', 'success');
        } else {
          btnEl.classList.remove('active');
          btnEl.innerHTML = '🤍';
          this.toast('Removed from Wishlist', 'info');
        }
        const badge = document.getElementById('header-wishlist-count');
        if (badge && data.wishlist_count !== undefined) {
          badge.textContent = data.wishlist_count;
        }
      } else {
        this.toast(data.message || 'Please log in to manage your wishlist.', 'error');
      }
    } catch (err) {
      this.toast('Unable to update wishlist.', 'error');
    }
  },

  // Search Auto-Suggestions
  initSearchSuggestions() {
    const input = document.getElementById('header-search-input');
    const dropdown = document.getElementById('header-search-suggestions');
    if (!input || !dropdown) return;

    let debounceTimer;

    input.addEventListener('input', () => {
      const query = input.value.trim();
      clearTimeout(debounceTimer);

      if (query.length < 2) {
        dropdown.classList.remove('active');
        dropdown.innerHTML = '';
        return;
      }

      debounceTimer = setTimeout(async () => {
        try {
          const res = await fetch(`${this.baseUrl}/api/search_suggestions.php?q=${encodeURIComponent(query)}`);
          const items = await res.json();

          if (items && items.length > 0) {
            dropdown.innerHTML = items.map(item => `
              <a href="${this.baseUrl}/product.php?slug=${item.slug}" class="suggestion-item">
                <div>
                  <span style="font-weight:600;">${item.name}</span>
                  <div style="font-size:11px;color:#64748b;">${item.category_name}</div>
                </div>
                <div style="font-weight:700;color:#0f233a;">${this.formatINR(item.price)}</div>
              </a>
            `).join('');
            dropdown.classList.add('active');
          } else {
            dropdown.innerHTML = '<div style="padding:12px;font-size:13px;color:#64748b;">No matching products found</div>';
            dropdown.classList.add('active');
          }
        } catch (err) {
          dropdown.classList.remove('active');
        }
      }, 250);
    });

    document.addEventListener('click', (e) => {
      if (!input.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.classList.remove('active');
      }
    });
  },

  // Voice Search Integration (Web Speech API)
  initVoiceSearch() {
    const micBtn = document.getElementById('btn-voice-search');
    const input = document.getElementById('header-search-input');
    if (!micBtn || !input) return;

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    if (!SpeechRecognition) {
      micBtn.title = 'Voice search is not supported in this browser. Please type your search.';
      micBtn.addEventListener('click', () => {
        this.toast('Voice search is supported on Chrome, Edge & Safari. Please type your query.', 'info');
      });
      return;
    }

    const recognition = new SpeechRecognition();
    recognition.lang = 'en-IN';
    recognition.interimResults = false;

    micBtn.addEventListener('click', () => {
      try {
        recognition.start();
        this.toast('Listening... Speak your product search in Hindi or English', 'info');
        micBtn.style.color = '#ef4444';
      } catch (e) {
        recognition.stop();
        micBtn.style.color = '';
      }
    });

    recognition.onresult = (event) => {
      const speechToText = event.results[0][0].transcript;
      input.value = speechToText;
      micBtn.style.color = '';
      this.toast(`Searching for: "${speechToText}"`, 'success');
      document.getElementById('header-search-form')?.submit();
    };

    recognition.onerror = () => {
      micBtn.style.color = '';
      this.toast('Could not detect voice. Please try typing.', 'info');
    };

    recognition.onend = () => {
      micBtn.style.color = '';
    };
  },

  // Image-Based Product Search Demo
  initImageSearchModal() {
    const camBtn = document.getElementById('btn-image-search');
    const modal = document.getElementById('image-search-modal');
    if (!camBtn || !modal) return;

    camBtn.addEventListener('click', () => {
      modal.style.display = 'flex';
    });

    const closeBtn = modal.querySelector('.modal-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', () => modal.style.display = 'none');
    }

    modal.addEventListener('click', (e) => {
      if (e.target === modal) modal.style.display = 'none';
    });
  },

  // PIN Code Selector & Estimator
  initPinCodeSelector() {
    const savedPin = localStorage.getItem('ss_pincode') || '411004';
    const pinDisplay = document.getElementById('header-delivery-pin');
    if (pinDisplay) pinDisplay.textContent = savedPin;

    const btn = document.getElementById('btn-change-pin');
    if (btn) {
      btn.addEventListener('click', () => {
        const pin = prompt('Enter 6-digit Indian PIN Code for delivery estimate:', savedPin);
        if (pin && /^\d{6}$/.test(pin)) {
          localStorage.setItem('ss_pincode', pin);
          if (pinDisplay) pinDisplay.textContent = pin;
          this.toast(`Delivery PIN updated to ${pin}. Showing updated delivery dates!`, 'success');
          // Reload if on product page to recompute estimates
          if (window.location.pathname.includes('product.php')) {
            window.location.reload();
          }
        } else if (pin) {
          alert('Please enter a valid 6-digit Indian PIN code (e.g. 411004, 400001, 110001)');
        }
      });
    }
  },

  // Global listeners for Add to Cart & Wishlist buttons
  initCartListeners() {
    document.addEventListener('click', (e) => {
      const btn = e.target.closest('.js-add-to-cart');
      if (btn) {
        e.preventDefault();
        const productId = btn.dataset.productId;
        const qty = parseInt(btn.dataset.quantity || '1', 10);
        if (productId) this.addToCart(productId, qty, btn);
      }
    });
  },

  initWishlistListeners() {
    document.addEventListener('click', (e) => {
      const btn = e.target.closest('.js-toggle-wishlist');
      if (btn) {
        e.preventDefault();
        const productId = btn.dataset.productId;
        if (productId) this.toggleWishlist(productId, btn);
      }
    });
  }
};

document.addEventListener('DOMContentLoaded', () => {
  ShopSmart.init();
});
