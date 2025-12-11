function initCollectionPage() {
  // Check if we're on the collection page
  const collectionSections = document.querySelectorAll('[id^="collectionCardContainer-"]');
  if (collectionSections.length === 0) return;
  
  if (window.collectionPageInitialized) return;
  window.collectionPageInitialized = true;

  // Get sections dynamically
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

      // Compute scroll distance
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
  const gradeSelect = document.getElementById('collectionModalGrade');

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
      
      // Reset form fields
      if (gradeSelect) gradeSelect.value = '';
      if (priceInput) {
        priceInput.value = '';
      }

      // Update UI based on listing status
      if (sellButton) {
        sellButton.textContent = isListed ? 'Take Out of Sale' : 'Put Up for Sale';
        
        // Get listing add path from data attribute or use default
        const listingAddPath = card.dataset.listingAddPath || 'my-collection/listing/add';
        sellButton.form.action = isListed
          ? `my-collection/listing/delete/${card.dataset.listingId || ''}`
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
        editButton.href = `/my-collection/edit/${card.dataset.id || ''}`;
      }

      // Set up tokens for delete
      if (modalToken) {
        modalToken.value = isListed 
          ? (card.dataset.deleteListingToken || '')
          : (card.dataset.deleteCollectibleToken || '');
      }

      // Show/hide delete button
      if (deleteButton) {
        deleteButton.classList.toggle('collection-hidden', isListed);
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
      if (priceInput) priceInput.value = '';
      if (gradeSelect) gradeSelect.value = '';
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

  // Handle delete button
  if (deleteButton) {
    deleteButton.addEventListener('click', () => {
      const collectibleId = modalCollectibleIdInput?.value;
      const token = modalToken?.value;
      
      if (!collectibleId || !token) {
        alert("Missing data for deletion");
        return;
      }
      
      if (confirm("Are you sure you want to delete this collectible?")) {
        // Create form submission
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/my-collection/delete/${collectibleId}`;
        
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = token;
        
        form.appendChild(csrfInput);
        document.body.appendChild(form);
        form.submit();
      }
    });
  }

  // Handle sell form submission
  const sellForm = document.getElementById('collectionSellForm');
  if (sellForm && sellButton) {
    sellForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const collectibleId = modalCollectibleIdInput?.value || '';
      const token = modalToken?.value || '';
      const grade = gradeSelect?.value || '';
      const price = priceInput?.value || '';
      const actionUrl = sellButton.form.action;
      const isDeleteAction = actionUrl.includes('/delete/');
      
      if (!collectibleId || !token) {
        alert("Missing required data");
        return;
      }
      
      // For delete action (take out of sale), just submit the form
      if (isDeleteAction) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = actionUrl;
        
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = token;
        
        form.appendChild(csrfInput);
        document.body.appendChild(form);
        form.submit();
        return;
      }
      
      // For add action (put up for sale), validate and submit
      if (!price || parseFloat(price) < 1) {
        alert("Please enter a valid price (minimum ₱1.00)");
        if (priceInput) priceInput.focus();
        return;
      }
      
      // Create form for listing creation
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = actionUrl;
      
      const collectibleIdInput = document.createElement('input');
      collectibleIdInput.type = 'hidden';
      collectibleIdInput.name = 'collectible_id';
      collectibleIdInput.value = collectibleId;
      
      const gradeInput = document.createElement('input');
      gradeInput.type = 'hidden';
      gradeInput.name = 'grade';
      gradeInput.value = grade;
      
      const priceFormInput = document.createElement('input');
      priceFormInput.type = 'hidden';
      priceFormInput.name = 'price';
      priceFormInput.value = price;
      
      const csrfInput = document.createElement('input');
      csrfInput.type = 'hidden';
      csrfInput.name = '_token';
      csrfInput.value = token;
      
      form.appendChild(collectibleIdInput);
      form.appendChild(gradeInput);
      form.appendChild(priceFormInput);
      form.appendChild(csrfInput);
      document.body.appendChild(form);
      form.submit();
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