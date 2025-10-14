['DOMContentLoaded', 'turbo:load'].forEach(evt => {
  document.addEventListener(evt, () => {
    const form = document.querySelector('#collectible-form');
    if (!form) return;

    const fetchBtn = document.querySelector('#fetch-card-btn');
    const cardIdInput = document.querySelector('#card-id');
    const includePriceCheckbox = document.querySelector('#include-price');
    const warningText = document.querySelector('#price-warning');
    const nameField = document.querySelector('#collectible_name');
    const descriptionField = document.querySelector('#collectible_description');
    const franchiseField = document.querySelector('#collectible_franchise');
    const fetchedImageInput = document.querySelector('#fetched_image');
    const priceField = document.querySelector('#collectible_price');
    const previewImg = document.querySelector('#preview-img');
    const imagePreviewContainer = document.querySelector('#image-preview-container');
    const categoryField = document.querySelector('#collectible_category');

    if (!fetchBtn) return;

    fetchBtn.addEventListener('click', async () => {
      const id = cardIdInput.value.trim();
      if (!id) return alert('Please enter a Card ID.');

      warningText.classList.toggle('hidden', !includePriceCheckbox.checked);
      imagePreviewContainer.classList.add('hidden');

      try {
        const tcgdexRes = await fetch(`https://api.tcgdex.net/v2/en/cards/${id}`);
        if (!tcgdexRes.ok) throw new Error('Card not found on TCGdex.');
        const tcgdexData = await tcgdexRes.json();

        // Name
        nameField.value = tcgdexData.name || '';

        // Description → "Set Name Card Pack"
        if (tcgdexData.set && tcgdexData.set.name) {
          descriptionField.value = `${tcgdexData.set.name} Card Pack`;
        }

        // Franchise → Pokémon
        if (franchiseField) franchiseField.value = 'Pokemon';

        // Category → Auto-select "Card" (ID = 1)
        if (categoryField) {
          categoryField.value = "1"; // Card entity ID
          categoryField.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // Image
        if (tcgdexData.image) {
          const highResUrl = tcgdexData.image + '/low.jpg';
          fetchedImageInput.value = highResUrl;

          previewImg.src = highResUrl;
          imagePreviewContainer.classList.remove('hidden');
        }

        // Price (if checkbox checked)
        if (includePriceCheckbox.checked && tcgdexData.pricing?.tcgplayer?.normal?.marketPrice) {
          warningText.classList.remove('hidden');
          const usdPrice = tcgdexData.pricing.tcgplayer.normal.marketPrice;
          const phpPrice = (usdPrice * 55).toFixed(2); // USD → PHP
          priceField.value = phpPrice;
          warningText.classList.add('hidden');
        }

      } catch (err) {
        console.error(err);
        alert('Failed to fetch card details. Please check the ID or try again later.');
      }
    });
  });
});
document.addEventListener("DOMContentLoaded", () => {
  const pokemonIDs = localStorage.getItem("copiedPokemonIDs");

  if (pokemonIDs) {
    const cardIdInput = document.getElementById("card-id");
    if (cardIdInput) {
      cardIdInput.value = pokemonIDs.split(", ")[0]; // Use the first ID only
    }

    // 🧹 Clear localStorage after use
    localStorage.removeItem("copiedPokemonIDs");
  }
});
