['DOMContentLoaded', 'turbo:load'].forEach(evt => {
  document.addEventListener(evt, () => {
    const form = document.querySelector('#collectible-form');
    if (!form) return;

    const fetchBtn = document.querySelector('#fetch-card-btn');
    const cardIdInput = document.querySelector('#card-id');
    const fetchedImageInput = document.querySelector('#fetched_image');
    const previewImg = document.querySelector('#preview-img');
    const imagePreviewContainer = document.querySelector('#image-preview-container');
    const imageInput = document.querySelector('#collectible_image');
    const imageError = document.querySelector('#image-error');

    // Handle localStorage prefilling
    const pokemonIDs = localStorage.getItem("copiedPokemonIDs");
    if (pokemonIDs && cardIdInput) {
      cardIdInput.value = pokemonIDs.split(", ")[0];
      localStorage.removeItem("copiedPokemonIDs");
    }

    // === Image Upload Validation and Preview ===
    if (imageInput) {
      imageInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
          const validTypes = ["image/jpeg", "image/jpg", "image/png", "image/gif"];
          if (validTypes.includes(file.type)) {
            const reader = new FileReader();
            reader.onload = function(ev) {
              previewImg.src = ev.target.result;
              imagePreviewContainer.classList.remove("hidden");
            };
            reader.readAsDataURL(file);
            imageError.classList.add("hidden");
            imageInput.classList.remove("border-red-500");
          } else {
            imageError.classList.remove("hidden");
            imageInput.classList.add("border-red-500");
            imagePreviewContainer.classList.add("hidden");
          }
        } else {
          imagePreviewContainer.classList.add("hidden");
        }
      });
    }

    // === Form Validation ===
    if (form) {
      form.addEventListener('submit', (e) => {
        let isValid = true;

        // Validate image if uploaded
        if (imageInput && imageInput.files[0]) {
          const file = imageInput.files[0];
          const validTypes = ["image/jpeg", "image/jpg", "image/png", "image/gif"];
          if (!validTypes.includes(file.type)) {
            imageError.classList.remove("hidden");
            imageInput.classList.add("border-red-500");
            isValid = false;
          }
        }

        if (!isValid) {
          e.preventDefault();
        }
      });
    }

    // === Fetch Card Data ===
    if (fetchBtn) {
      fetchBtn.addEventListener('click', async () => {
        const id = cardIdInput.value.trim();
        if (!id) {
          alert('Please enter a Card ID.');
          return;
        }

        imagePreviewContainer.classList.add('hidden');
        fetchBtn.disabled = true;
        fetchBtn.textContent = "Fetching...";

        try {
          const tcgdexRes = await fetch(`https://api.tcgdex.net/v2/en/cards/${id}`);
          if (!tcgdexRes.ok) throw new Error('Card not found on TCGdex.');
          const tcgdexData = await tcgdexRes.json();

          // DEBUG: Log what we received
          console.log('=== API RESPONSE ===');
          console.log('Card Name:', tcgdexData.name);
          console.log('Card Image:', tcgdexData.image);
          console.log('Card Set:', tcgdexData.set);

          // ============ FIXED FORM FILLING ============
          
          // 1. Fill Name field - USING CORRECT PREFIX "add_collectible"
          const nameField = document.querySelector('[name="add_collectible[name]"]');
          if (nameField && tcgdexData.name) {
            nameField.value = tcgdexData.name;
            console.log('✓ Name field filled:', tcgdexData.name);
          } else {
            console.log('✗ Name field not found');
          }
          
          // 2. Fill Description field - USING CORRECT PREFIX "add_collectible"
          const descriptionField = document.querySelector('[name="add_collectible[description]"]');
          if (descriptionField) {
            let descriptionText = 'No description available';
            if (tcgdexData.set && tcgdexData.set.name) {
              descriptionText = `${tcgdexData.set.name} Card Pack`;
            } else if (tcgdexData.effect) {
              descriptionText = tcgdexData.effect;
            } else if (tcgdexData.description) {
              descriptionText = tcgdexData.description;
            }
            descriptionField.value = descriptionText;
            console.log('✓ Description field filled:', descriptionText);
          } else {
            console.log('✗ Description field not found');
          }

          // 3. Fill Franchise field - USING CORRECT PREFIX "add_collectible"
          const franchiseField = document.querySelector('[name="add_collectible[franchise]"]');
          if (franchiseField) {
            franchiseField.value = 'Pokemon';
            console.log('✓ Franchise field filled: Pokemon');
          } else {
            console.log('✗ Franchise field not found');
          }

          // 4. Fill Category field - USING CORRECT PREFIX "add_collectible"
          const categoryField = document.querySelector('[name="add_collectible[category]"]');
          if (categoryField) {
            categoryField.value = "cards";
            console.log('✓ Category field filled: cards');
          } else {
            console.log('✗ Category field not found');
          }

          // 5. Handle image (fetched_image is an ID, not a name)
          if (tcgdexData.image && fetchedImageInput && previewImg) {
            // Use the high-quality image if available
            let imageUrl = tcgdexData.image;
            if (imageUrl.includes('/high')) {
              imageUrl = imageUrl.replace('/high', '/low');
            } else if (!imageUrl.endsWith('.jpg') && !imageUrl.endsWith('.png')) {
              imageUrl = imageUrl + '/low.jpg';
            }
            
            fetchedImageInput.value = imageUrl;
            previewImg.src = imageUrl;
            imagePreviewContainer.classList.remove('hidden');
            console.log('✓ Image set:', imageUrl);
          } else {
            console.log('✗ Image not available or elements not found');
          }

          alert('Card data fetched successfully!');

        } catch (err) {
          console.error('Fetch error:', err);
          alert('Failed to fetch card details. Please check the ID or try again later.');
        } finally {
          fetchBtn.disabled = false;
          fetchBtn.textContent = "Fetch Card Data";
        }
      });
    }
  });
});