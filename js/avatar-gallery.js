(() => {
    const detailDialog = document.querySelector('.avatar-detail-dialog');
    const detailTitle = document.querySelector('#avatarDetailTitle');
    const detailImage = document.querySelector('.avatar-detail-image');
    let detailTrigger = null;

    if (detailDialog && detailTitle && detailImage) {
        document.querySelectorAll('[data-avatar-details]').forEach((button) => {
            button.addEventListener('click', () => {
                detailTrigger = button;
                detailTitle.textContent = button.dataset.avatarName;
                detailImage.src = button.dataset.avatarFullImage;
                detailImage.alt = `Imagem completa do personagem ${button.dataset.avatarName}`;
                detailDialog.showModal();
            });
        });

        detailDialog.querySelector('.avatar-detail-close').addEventListener('click', () => {
            detailDialog.close();
        });

        detailDialog.addEventListener('click', (event) => {
            if (event.target === detailDialog) {
                detailDialog.close();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && detailDialog.open) {
                detailDialog.close();
            }
        });

        detailDialog.addEventListener('close', () => {
            detailImage.removeAttribute('src');
            detailTrigger?.focus();
            detailTrigger = null;
        });
    }

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
