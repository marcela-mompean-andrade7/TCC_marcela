(() => {
    const storageKey = 'lunaris-theme';
    const savedTheme = localStorage.getItem(storageKey);
    const initialTheme = savedTheme === 'light' ? 'light' : 'dark';

    document.documentElement.dataset.theme = initialTheme;

    function addThemeToggle() {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'theme-toggle';
        button.setAttribute('aria-pressed', String(initialTheme === 'light'));
        button.addEventListener('click', () => {
            const nextTheme = document.documentElement.dataset.theme === 'light' ? 'dark' : 'light';
            document.documentElement.dataset.theme = nextTheme;
            localStorage.setItem(storageKey, nextTheme);
            button.textContent = nextTheme === 'light' ? '🌙 Tema escuro' : '☀️ Tema claro';
            button.setAttribute('aria-label', `Ativar tema ${nextTheme === 'light' ? 'escuro' : 'claro'}`);
            button.setAttribute('aria-pressed', String(nextTheme === 'light'));
        });
        button.textContent = initialTheme === 'light' ? '🌙 Tema escuro' : '☀️ Tema claro';
        button.setAttribute('aria-label', `Ativar tema ${initialTheme === 'light' ? 'escuro' : 'claro'}`);
        document.body.append(button);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', addThemeToggle, { once: true });
    } else {
        addThemeToggle();
    }
})();
