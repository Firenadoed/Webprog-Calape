document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('pokemon-form');
    const input = document.getElementById('pokemon-name');
    const results = document.getElementById('results');
    let allSetsCache = null;

    // Get the URL from the data attribute
    const container = document.getElementById('pokemon-container');
    const collectionAddUrl = container ? container.dataset.collectionAddUrl : '/collection/add';

    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const query = input.value.trim();
        if (!query) {
            results.innerHTML = '';
            return;
        }

        results.innerHTML = '<p class="text-gray-300 col-span-2">Searching...</p>';

        try {
            // 1️⃣ Fetch cards
            const respCards = await fetch(`https://api.tcgdex.net/v2/en/cards?name=${encodeURIComponent(query)}`);
            const allCards = await respCards.json();

            // 2️⃣ STRICT name match
            const cards = allCards.filter(card => card.name.toLowerCase() === query.toLowerCase());

            if (!cards || cards.length === 0) {
                results.innerHTML = '<p class="text-gray-300 col-span-2">No cards found for this Pokémon.</p>';
                return;
            }

            // 3️⃣ Map sets
            const setsMap = {};
            cards.forEach(card => {
                const setCode = card.id.split('-')[0];
                if (!setsMap[setCode]) setsMap[setCode] = [];
                setsMap[setCode].push(card.id);
            });

            // 4️⃣ Fetch all sets for logos once
            if (!allSetsCache) {
                const respSets = await fetch('https://api.tcgdex.net/v2/en/sets');
                allSetsCache = await respSets.json();
            }

            results.innerHTML = '';

            for (const setCode in setsMap) {
                const setInfo = allSetsCache.find(s => s.id.toLowerCase() === setCode.toLowerCase());

                const div = document.createElement('div');
                div.className = 'bg-[#232327] p-3 rounded flex items-center gap-3 cursor-pointer hover:bg-gray-700';

                // Add .webp to logo URL
                let logo = setInfo?.logo || setInfo?.images?.logo || setInfo?.images?.icon;
                if (logo && !logo.endsWith('.webp')) logo += '.webp';
                if (logo) {
                    const img = document.createElement('img');
                    img.src = logo;
                    img.alt = setInfo.name || setCode.toUpperCase();
                    img.className = 'w-12 h-12 object-contain';
                    div.appendChild(img);
                }

                const nameSpan = document.createElement('span');
                nameSpan.textContent = setInfo?.name || setCode.toUpperCase();
                nameSpan.className = 'text-[#d4af37] font-medium';
                div.appendChild(nameSpan);

                // 🟡 When clicked: copy IDs, save to localStorage, then redirect
                div.addEventListener('click', () => {
                    const ids = setsMap[setCode].join(', ');
                    navigator.clipboard.writeText(ids);

                    // Save to localStorage
                    localStorage.setItem('copiedPokemonIDs', ids);

                    // Redirect to add_collectible route using the URL from data attribute
                    window.location.href = collectionAddUrl;
                });

                results.appendChild(div);
            }

        } catch (err) {
            console.error(err);
            results.innerHTML = '<p class="text-red-500 col-span-2">Error fetching data.</p>';
        }
    });
});