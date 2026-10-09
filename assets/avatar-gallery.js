(() => {
    const rarityFilter = document.querySelector('[data-avatar-rarity]');
    const statusFilter = document.querySelector('[data-avatar-status]');
    const cards = Array.from(document.querySelectorAll('[data-avatar-card]'));
    const countLabel = document.querySelector('[data-avatar-count]');

    if (!rarityFilter || !statusFilter || !countLabel || cards.length === 0) {
        return;
    }

    function applyFilters() {
        const rarity = rarityFilter.value;
        const status = statusFilter.value;
        let visibleCount = 0;

        cards.forEach((card) => {
            const visible = (rarity === 'all' || card.dataset.rarity === rarity)
                && (status === 'all' || card.dataset.status === status);
            card.hidden = !visible;
            visibleCount += visible ? 1 : 0;
        });

        countLabel.textContent = visibleCount === 1
            ? '1 personagem na galeria'
            : `${visibleCount} personagens na galeria`;
    }

    rarityFilter.addEventListener('change', applyFilters);
    statusFilter.addEventListener('change', applyFilters);
    applyFilters();
})();
