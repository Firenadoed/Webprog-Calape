function initCollectionPage() {
  // Check if we're on the collection page
  const collectionSections = document.querySelectorAll('[id^="collectionCardContainer-"]');
  if (collectionSections.length === 0) return;
  
  if (window.collectionPageInitialized) return;
  window.collectionPageInitialized = true;

  // Get sections dynamically instead of relying on window.collectionData
  const sections = [];
  document.querySelectorAll('[id^="collectionCardContainer-"]').forEach(container => {
    const match = container.id.match(/collectionCardContainer-(.+)/);
    if (match) sections.push(match[1]);
  });

  // Initialize carousels for each section
  sections.forEach(key => {
    const container = document.getElementById(`collectionCardContainer-${key}`);
    if (!container) return;
    
    const cards = Array.from(container.querySelectorAll('.collection-collectible-card'));
    const prevBtn = document.getElementById(`collectionPrevBtn-${key}`);
    const nextBtn = document.getElementById(`collectionNextBtn-${key}`);
    const cardWidth = 220;
    let index = 0;

    const updateCards = () => {
      const total = cards.length;
      if (total === 0) return;

      // Wrap index for looping
      if (index < 0) index = total - 1;
      if (index >= total) index = 0;

      // Compute scroll distance but make sure it loops correctly
      const translateX = (index % total) * cardWidth;
      container.style.transition = "transform 0.5s ease-in-out";
      container.style.transform = `translateX(-${translateX}px)`;

      // Highlight active card
      cards.forEach(card => {
        card.classList.remove('collection-scale-105', 'collection-brightness-150', 'collection-scale-90', 'collection-brightness-50');
        card.classList.add('collection-scale-90', 'collection-brightness-50');
      });
      
      if (cards[index]) {
        cards[index].classList.remove('collection-scale-90', 'collection-brightness-50');
        cards[index].classList.add('collection-scale-105', 'collection-brightness-150');
      }
    };

    // Button actions
    if (nextBtn) {
      nextBtn.addEventListener('click', () => {
        index++;
        updateCards();
      });
    }

    if (prevBtn) {
      prevBtn.addEventListener('click', () => {
        index--;
        updateCards();
      });
    }

    // Card click = focus highlight
    cards.forEach((card, i) => {
      card.addEventListener('click', () => {
        index = i;
        updateCards();
      });
    });

    updateCards();
    window.addEventListener('resize', updateCards);
  });

  // Initialize modal
  initCollectionModal();
}

function initCollectionModal() {
  const modal = document.getElementById('collectionDetailModal');
  if (!modal) return;
  
  const modalContainer = modal.querySelector('.collection-modal-container');
  const modalImage = document.getElementById('collectionModalImage');
  const modalName = document.getElementById('collectionModalName');
  const modalDescription = document.getElementById('collectionModalDescription');
  const modalPrice = document.querySelector('.collection-modal-price-value');
  const modalCollectibleIdInput = document.getElementById('collectionModalCollectibleId');
  const sellButton = document.getElementById('collectionSellButton');
  const modalToken = document.getElementById('collectionModalToken');
  const editButton = document.getElementById('collectionEditButton');
  const closeBtn = document.getElementById('collectionModalClose');
  const deleteButton = document.getElementById('collectionDeleteButton');
  const gradeWrapper = document.getElementById('collectionGradeWrapper');
  const priceWrapper = document.getElementById('collectionPriceWrapper');
  const priceInput = document.getElementById('collectionModalPriceInput');

  // Add double-click event to cards
  document.querySelectorAll('.collection-collectible-card').forEach(card => {
    card.addEventListener('dblclick', () => {
      const isListed = card.dataset.listed === '1';
      modal.style.display = 'flex';
      setTimeout(() => modalContainer.classList.add('collection-scale-100'), 10);

      // Set modal content
      modalImage.src = card.dataset.image || '';
      modalName.textContent = card.dataset.name || '';
      modalDescription.textContent = card.dataset.description || '';
      
      // Format price
      if (modalPrice && card.dataset.price) {
        const price = parseFloat(card.dataset.price);
        modalPrice.textContent = `₱${!isNaN(price) ? price.toFixed(2) : '0.00'}`;
      }
      
      if (modalCollectibleIdInput) modalCollectibleIdInput.value = card.dataset.id || '';
      
      // Reset price input
      if (priceInput) {
        priceInput.value = '';
      }

      // Update UI based on listing status
      if (sellButton) {
        sellButton.textContent = isListed ? 'Take Out of Sale' : 'Put Up for Sale';
        
        // Get listing add path from data attribute or use default
        const listingAddPath = card.dataset.listingAddPath || '/listing/add';
        sellButton.form.action = isListed
          ? `/listing/delete/${card.dataset.listingId || ''}`
          : listingAddPath;
      }
      
      if (gradeWrapper) {
        gradeWrapper.style.display = isListed ? 'none' : 'flex';
      }
      
      // Show/hide price input based on listing status
      if (priceWrapper) {
        priceWrapper.style.display = isListed ? 'none' : 'flex';
      }
      
      if (editButton) {
        editButton.classList.toggle('collection-hidden', isListed);
        editButton.href = `/collection/edit/${card.dataset.id || ''}`;
      }

      if (!isListed) {
        if (deleteButton) {
          deleteButton.classList.remove('collection-hidden');
          if (modalToken) modalToken.value = card.dataset.deleteCollectibleToken || '';
        }
      } else {
        if (deleteButton) deleteButton.classList.add('collection-hidden');
        if (modalToken) modalToken.value = card.dataset.deleteListingToken || '';
      }

      // Setup delete button handler
      if (deleteButton) {
        deleteButton.onclick = () => {
          const collectibleId = card.dataset.id;
          const token = card.dataset.deleteCollectibleToken;
          
          if (!collectibleId || !token) {
            alert("Missing data for deletion");
            return;
          }
          
          if (confirm("Are you sure you want to delete this collectible?")) {
            fetch(`/collection/delete/${collectibleId}`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: new URLSearchParams({ _token: token })
            })
            .then(r => r.json())
            .then(data => {
              if (data.status === 'success') {
                card.remove();
                closeCollectionModal();
                
                // Show success message
                const flashContainer = document.getElementById('collectionFlashContainer');
                if (flashContainer) {
                  const flash = document.createElement('div');
                  flash.className = 'p-3 rounded-md mb-2 text-white bg-green-600';
                  flash.textContent = data.message || 'Collectible deleted successfully';
                  flashContainer.prepend(flash);
                  setTimeout(() => flash.remove(), 4000);
                }
              } else {
                alert(data.message || 'Error deleting collectible');
              }
            })
            .catch(error => {
              console.error('Delete error:', error);
              alert('Network error. Please try again.');
            });
          }
        };
      }
    });
  });

  // Close modal function
  function closeCollectionModal() {
    modalContainer.classList.remove('collection-scale-100');
    setTimeout(() => {
      modal.style.display = 'none';
      // Reset modal content
      if (modalImage) modalImage.src = '';
      if (modalName) modalName.textContent = '';
      if (modalDescription) modalDescription.textContent = '';
      if (modalPrice) modalPrice.textContent = '₱0.00';
      if (modalCollectibleIdInput) modalCollectibleIdInput.value = '';
      if (modalToken) modalToken.value = '';
      if (priceInput) priceInput.value = ''; // Reset price input
    }, 200);
  }

  // Close modal events
  if (closeBtn) closeBtn.addEventListener('click', closeCollectionModal);
  modal.addEventListener('click', e => {
    if (e.target === modal) closeCollectionModal();
  });
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeCollectionModal();
  });

  // Handle sell form submission
  const sellForm = document.getElementById('collectionSellForm');
  if (sellForm && sellButton) {
    sellForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const collectibleId = modalCollectibleIdInput?.value || '';
      const token = modalToken?.value || '';
      const grade = document.getElementById('collectionModalGrade')?.value || '';
      const price = priceInput?.value || ''; // Get price value
      const actionUrl = sellButton.form.action;
      const isDeleteAction = actionUrl.includes('/delete/');
      
      if (!collectibleId || !token) {
        alert("Missing required data");
        return;
      }
      
      // Prepare request body based on action type
      let requestBody;
      if (isDeleteAction) {
        // For delete action, only send _token
        requestBody = new URLSearchParams({
          _token: token
        });
      } else {
        // For add action, validate price and send all fields
        if (!price || parseFloat(price) < 1) {
          alert("Please enter a valid price (minimum ₱1.00)");
          if (priceInput) priceInput.focus();
          return;
        }
        
        requestBody = new URLSearchParams({
          collectible_id: collectibleId,
          grade: grade,
          price: price, // Include price
          _token: token
        });
      }

      fetch(actionUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: requestBody
      })
      .then(res => {
        if (!res.ok) {
          throw new Error(`HTTP error! Status: ${res.status}`);
        }
        return res.json();
      })
      .then(data => {
        const flashContainer = document.getElementById('collectionFlashContainer');
        if (flashContainer) {
          const flash = document.createElement('div');
          flash.className = `p-3 rounded-md mb-2 text-white ${
            data.status === 'success' ? 'bg-green-600' :
            data.status === 'error' ? 'bg-red-600' : 'bg-yellow-500 text-black'
          }`;
          flash.textContent = data.message || (data.status === 'success' ? 'Operation successful' : 'Operation failed');
          flashContainer.prepend(flash);
          setTimeout(() => flash.remove(), 4000);
        }

        closeCollectionModal();

        // Update card data attributes
        const card = document.querySelector(`.collection-collectible-card[data-id='${collectibleId}']`);
        if (card) {
          if (!isDeleteAction && data.listingId) {
            // Item was just listed
            card.dataset.listed = '1';
            card.dataset.listingId = data.listingId;
            card.dataset.deleteListingToken = data.deleteToken || '';
          } else if (isDeleteAction) {
            // Listing was removed
            card.dataset.listed = '0';
            card.dataset.listingId = '';
            card.dataset.deleteListingToken = '';
          }
        }
      })
      .catch(error => {
        console.error('Error:', error);
        const flashContainer = document.getElementById('collectionFlashContainer');
        if (flashContainer) {
          const flash = document.createElement('div');
          flash.className = 'p-3 rounded-md mb-2 text-white bg-red-600';
          flash.textContent = `Network error: ${error.message}`;
          flashContainer.prepend(flash);
          setTimeout(() => flash.remove(), 4000);
        }
      });
    });
  }
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initCollectionPage);
} else {
  initCollectionPage();
}

// Reinitialize on Turbo render (if using Hotwire/Turbo)
if (typeof Turbo !== 'undefined') {
  document.addEventListener('turbo:render', () => {
    window.collectionPageInitialized = false;
    initCollectionPage();
  });
} else {
  // Fallback for non-Turbo pages
  document.addEventListener('turbo:load', () => {
    window.collectionPageInitialized = false;
    initCollectionPage();
  });
}

// Flash message helper function
function showFlashMessage(message, type = 'success') {
  const container = document.getElementById('collectionFlashContainer');
  if (!container) return;
  
  const messageDiv = document.createElement('div');
  messageDiv.className = `p-3 rounded-md mb-2 text-white ${
    type === 'success' ? 'bg-green-600' :
    type === 'error' ? 'bg-red-600' : 'bg-yellow-500'
  }`;
  messageDiv.textContent = message;
  
  container.prepend(messageDiv);
  
  setTimeout(() => {
    messageDiv.style.opacity = '0';
    setTimeout(() => {
      if (messageDiv.parentNode === container) {
        container.removeChild(messageDiv);
      }
    }, 300);
  }, 5000);
}