(() => {
    const storageKey = 'lunaris-theme';
    const savedTheme = localStorage.getItem(storageKey);
    const systemPrefersDark = window.matchMedia
        ? window.matchMedia('(prefers-color-scheme: dark)').matches
        : false;
    const initialTheme = savedTheme === 'light' || savedTheme === 'dark'
        ? savedTheme
        : (systemPrefersDark ? 'dark' : 'light');

    document.documentElement.dataset.theme = initialTheme;
    document.querySelector('meta[name="theme-color"]')?.setAttribute(
        'content',
        initialTheme === 'light' ? '#F5F7FF' : '#050817'
    );

    const accessibilityStorageKey = 'lunaris-accessibility';
    const root = document.documentElement;
    const isAuthenticated = Boolean(root.dataset.accessibilityCsrf);
    const serverPreferences = root.dataset.accessibilityPreferences;
    let accessibilityPreferences = [];

    try {
        const savedPreferences = isAuthenticated
            ? serverPreferences
            : localStorage.getItem(accessibilityStorageKey);
        accessibilityPreferences = JSON.parse(savedPreferences || '[]');
    } catch (error) {
        console.error('Não foi possível carregar as preferências de acessibilidade.', error);
    }

    const supportedPreferences = new Set([
        'larger_text',
        'largest_text',
        'high_contrast',
        'reduced_motion',
        'screen_reader',
        'keyboard',
        'captions_visual',
        'simple_instructions',
        'other',
        'color_vision',
        'color_palette_blue_orange',
        'color_palette_purple_yellow'
    ]);
    accessibilityPreferences = Array.isArray(accessibilityPreferences)
        ? [...new Set(accessibilityPreferences.filter((preference) => supportedPreferences.has(preference)))]
        : [];

    function applyAccessibilityPreferences() {
        const scale = accessibilityPreferences.includes('largest_text')
            ? 'large'
            : (accessibilityPreferences.includes('larger_text') ? 'medium' : 'normal');
        root.dataset.textScale = scale;
        root.dataset.contrast = String(accessibilityPreferences.includes('high_contrast'));
        root.dataset.reducedMotion = String(accessibilityPreferences.includes('reduced_motion'));
        root.dataset.accessibilityPreferences = JSON.stringify(accessibilityPreferences);
        root.dataset.colorVision = String(accessibilityPreferences.includes('color_vision'));
        root.dataset.colorPalette = accessibilityPreferences.includes('color_palette_blue_orange')
            ? 'blue-orange'
            : (accessibilityPreferences.includes('color_palette_purple_yellow') ? 'purple-yellow' : 'default');
    }

    applyAccessibilityPreferences();

    function addThemeToggle() {
        const navigation = document.querySelector('.nav nav, .navbar .nav-links');
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'theme-toggle';
        button.classList.toggle('nav-theme-toggle', Boolean(navigation));
        button.classList.toggle('theme-toggle-floating', !navigation);
        const interfaceLanguage = navigation?.getAttribute('lang') || document.documentElement.lang;
        const labels = {
            'pt-BR': {
                light: '☀️ Tema claro', dark: '🌙 Tema escuro', ariaLight: 'Ativar tema claro', ariaDark: 'Ativar tema escuro',
                accessibility: '♿ Acessibilidade', textSize: 'Tamanho do texto', increase: 'Aumentar', decrease: 'Diminuir', reset: 'Restaurar',
                contrast: 'Alto contraste', motion: 'Reduzir animações', saved: 'As alterações são salvas no seu perfil.',
                savedConfirmation: 'Preferências salvas.', saveError: 'Não foi possível salvar. Tente novamente.',
                colorVision: 'Melhorar distinção das cores', palette: 'Paleta alternativa', defaultPalette: 'Padrão (cores originais)',
                blueOrange: 'Azul e laranja', purpleYellow: 'Roxo e amarelo', preview: 'Prévia das cores e dos estados',
                previewSuccess: '✓ Concluído', previewError: '⚠ Atenção', previewInfo: 'i Informação',
                resetColors: 'Restaurar cores padrão'
            },
            en: {
                light: '☀️ Light theme', dark: '🌙 Dark theme', ariaLight: 'Enable light theme', ariaDark: 'Enable dark theme',
                accessibility: '♿ Accessibility', textSize: 'Text size', increase: 'Increase', decrease: 'Decrease', reset: 'Reset',
                contrast: 'High contrast', motion: 'Reduce motion', saved: 'Changes are saved to your profile.',
                savedConfirmation: 'Preferences saved.', saveError: 'Could not save preferences. Try again.',
                colorVision: 'Improve color distinction', palette: 'Alternative palette', defaultPalette: 'Default (original colors)',
                blueOrange: 'Blue and orange', purpleYellow: 'Purple and yellow', preview: 'Color and status preview',
                previewSuccess: '✓ Complete', previewError: '⚠ Attention', previewInfo: 'i Information',
                resetColors: 'Restore default colors'
            },
            es: {
                light: '☀️ Tema claro', dark: '🌙 Tema oscuro', ariaLight: 'Activar tema claro', ariaDark: 'Activar tema oscuro',
                accessibility: '♿ Accesibilidad', textSize: 'Tamaño del texto', increase: 'Aumentar', decrease: 'Reducir', reset: 'Restablecer',
                contrast: 'Alto contraste', motion: 'Reducir animaciones', saved: 'Los cambios se guardan en tu perfil.',
                savedConfirmation: 'Preferencias guardadas.', saveError: 'No se pudieron guardar. Inténtalo de nuevo.',
                colorVision: 'Mejorar la distinción de colores', palette: 'Paleta alternativa', defaultPalette: 'Predeterminada (colores originales)',
                blueOrange: 'Azul y naranja', purpleYellow: 'Morado y amarillo', preview: 'Vista previa de colores y estados',
                previewSuccess: '✓ Completado', previewError: '⚠ Atención', previewInfo: 'i Información',
                resetColors: 'Restaurar colores predeterminados'
            }
        }[interfaceLanguage] || {
            light: '☀️ Tema claro',
            dark: '🌙 Tema escuro',
            ariaLight: 'Ativar tema claro',
            ariaDark: 'Ativar tema escuro',
            accessibility: '♿ Acessibilidade',
            textSize: 'Tamanho do texto',
            increase: 'Aumentar',
            decrease: 'Diminuir',
            reset: 'Restaurar',
            contrast: 'Alto contraste',
            motion: 'Reduzir animações',
            saved: 'As alterações são salvas no seu perfil.',
            savedConfirmation: 'Preferências salvas.',
            saveError: 'Não foi possível salvar. Tente novamente.',
            colorVision: 'Melhorar distinção das cores',
            palette: 'Paleta alternativa',
            defaultPalette: 'Padrão (cores originais)',
            blueOrange: 'Azul e laranja',
            purpleYellow: 'Roxo e amarelo',
            preview: 'Prévia das cores e dos estados',
            previewSuccess: '✓ Concluído',
            previewError: '⚠ Atenção',
            previewInfo: 'i Informação',
            resetColors: 'Restaurar cores padrão'
        };

        function updateButton(theme) {
            const nextTheme = theme === 'light' ? 'dark' : 'light';
            button.textContent = nextTheme === 'light' ? labels.light : labels.dark;
            button.setAttribute('aria-label', nextTheme === 'light' ? labels.ariaLight : labels.ariaDark);
            button.setAttribute('aria-pressed', String(theme === 'dark'));
        }

        updateButton(initialTheme);
        button.addEventListener('click', () => {
            const nextTheme = document.documentElement.dataset.theme === 'light' ? 'dark' : 'light';
            document.documentElement.dataset.theme = nextTheme;
            localStorage.setItem(storageKey, nextTheme);
            updateButton(nextTheme);
            document.querySelector('meta[name="theme-color"]')?.setAttribute(
                'content',
                nextTheme === 'light' ? '#F5F7FF' : '#050817'
            );
        });
        (navigation || document.body).append(button);

        const accessibilityPanel = document.createElement('details');
        accessibilityPanel.className = 'accessibility-panel';
        accessibilityPanel.innerHTML = `
            <summary>${labels.accessibility}</summary>
            <div class="accessibility-panel-content">
                <p>${labels.textSize}</p>
                <div class="accessibility-button-row">
                    <button type="button" data-accessibility-action="decrease">${labels.decrease}</button>
                    <button type="button" data-accessibility-action="increase">${labels.increase}</button>
                    <button type="button" data-accessibility-action="reset">${labels.reset}</button>
                </div>
                <button type="button" data-accessibility-action="contrast" aria-pressed="false">${labels.contrast}</button>
                <button type="button" data-accessibility-action="motion" aria-pressed="false">${labels.motion}</button>
                <fieldset class="color-accessibility-controls">
                    <legend>${labels.colorVision}</legend>
                    <button type="button" data-accessibility-action="colorVision" aria-pressed="false">${labels.colorVision}</button>
                    <label for="accessibilityColorPalette">${labels.palette}</label>
                    <select id="accessibilityColorPalette" data-accessibility-palette>
                        <option value="default">${labels.defaultPalette}</option>
                        <option value="blue-orange">${labels.blueOrange}</option>
                        <option value="purple-yellow">${labels.purpleYellow}</option>
                    </select>
                    <p class="color-preview-label">${labels.preview}</p>
                    <div class="color-accessibility-preview" data-color-preview="default" aria-label="${labels.preview}">
                        <span class="color-preview-state is-success">${labels.previewSuccess}</span>
                        <span class="color-preview-state is-error">${labels.previewError}</span>
                        <span class="color-preview-state is-info">${labels.previewInfo}</span>
                    </div>
                    <button type="button" data-accessibility-action="resetColors">${labels.resetColors}</button>
                </fieldset>
                <p class="accessibility-panel-hint">${labels.saved}</p>
                <p class="accessibility-panel-status" role="status" aria-live="polite"></p>
            </div>
        `;
        (navigation || document.body).append(accessibilityPanel);

        function keepAccessibilityPanelInViewport() {
            const panelContent = accessibilityPanel.querySelector('.accessibility-panel-content');
            if (!accessibilityPanel.open) {
                delete accessibilityPanel.dataset.panelPlacement;
                return;
            }

            const bounds = panelContent.getBoundingClientRect();
            accessibilityPanel.dataset.panelPlacement = bounds.bottom > window.innerHeight - 12
                ? 'viewport'
                : 'inline';
        }

        accessibilityPanel.addEventListener('toggle', () => {
            window.requestAnimationFrame(keepAccessibilityPanelInViewport);
        });
        window.addEventListener('resize', keepAccessibilityPanelInViewport);

        function updateAccessibilityControls() {
            accessibilityPanel.querySelector('[data-accessibility-action="decrease"]').disabled =
                !accessibilityPreferences.includes('largest_text') && !accessibilityPreferences.includes('larger_text');
            accessibilityPanel.querySelector('[data-accessibility-action="increase"]').disabled =
                accessibilityPreferences.includes('largest_text');
            accessibilityPanel.querySelector('[data-accessibility-action="contrast"]').setAttribute(
                'aria-pressed',
                String(accessibilityPreferences.includes('high_contrast'))
            );
            accessibilityPanel.querySelector('[data-accessibility-action="motion"]').setAttribute(
                'aria-pressed',
                String(accessibilityPreferences.includes('reduced_motion'))
            );
            accessibilityPanel.querySelector('[data-accessibility-action="colorVision"]').setAttribute(
                'aria-pressed',
                String(accessibilityPreferences.includes('color_vision'))
            );
            const selectedPalette = accessibilityPreferences.includes('color_palette_blue_orange')
                ? 'blue-orange'
                : (accessibilityPreferences.includes('color_palette_purple_yellow') ? 'purple-yellow' : 'default');
            accessibilityPanel.querySelector('[data-accessibility-palette]').value = selectedPalette;
            accessibilityPanel.querySelector('[data-color-preview]').dataset.colorPreview = selectedPalette;
        }

        function setPreference(preference, enabled) {
            accessibilityPreferences = accessibilityPreferences.filter((item) => item !== preference);
            if (enabled) {
                accessibilityPreferences.push(preference);
            }
        }

        async function saveAccessibilityPreferences(previousPreferences) {
            applyAccessibilityPreferences();
            updateAccessibilityControls();

            if (!isAuthenticated) {
                localStorage.setItem(accessibilityStorageKey, JSON.stringify(accessibilityPreferences));
                return;
            }

            const body = new URLSearchParams({ csrf: root.dataset.accessibilityCsrf });
            accessibilityPreferences.forEach((preference) => body.append('accessibility_needs[]', preference));

            try {
                const response = await fetch(root.dataset.accessibilityEndpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                    body
                });
                const result = await response.json();
                if (!response.ok) {
                    throw new Error(result.error || `${labels.saveError} (${response.status})`);
                }
                accessibilityPreferences = result.preferences;
                applyAccessibilityPreferences();
                updateAccessibilityControls();
                accessibilityPanel.querySelector('.accessibility-panel-status').textContent = labels.savedConfirmation;
            } catch (error) {
                accessibilityPreferences = previousPreferences;
                applyAccessibilityPreferences();
                updateAccessibilityControls();
                accessibilityPanel.querySelector('.accessibility-panel-status').textContent = labels.saveError;
                console.error('Não foi possível salvar as preferências de acessibilidade.', error);
            }
        }

        accessibilityPanel.addEventListener('click', async (event) => {
            const control = event.target.closest('[data-accessibility-action]');
            if (!control || control.disabled) {
                return;
            }

            const previousPreferences = [...accessibilityPreferences];
            const action = control.dataset.accessibilityAction;
            if (action === 'increase') {
                if (accessibilityPreferences.includes('larger_text')) {
                    setPreference('larger_text', false);
                    setPreference('largest_text', true);
                } else {
                    setPreference('larger_text', true);
                }
            } else if (action === 'decrease') {
                if (accessibilityPreferences.includes('largest_text')) {
                    setPreference('largest_text', false);
                    setPreference('larger_text', true);
                } else {
                    setPreference('larger_text', false);
                }
            } else if (action === 'reset') {
                setPreference('larger_text', false);
                setPreference('largest_text', false);
            } else if (action === 'contrast') {
                setPreference('high_contrast', !accessibilityPreferences.includes('high_contrast'));
            } else if (action === 'motion') {
                setPreference('reduced_motion', !accessibilityPreferences.includes('reduced_motion'));
            } else if (action === 'colorVision') {
                setPreference('color_vision', !accessibilityPreferences.includes('color_vision'));
            } else if (action === 'resetColors') {
                setPreference('color_vision', false);
                setPreference('color_palette_blue_orange', false);
                setPreference('color_palette_purple_yellow', false);
            }

            await saveAccessibilityPreferences(previousPreferences);
        });

        accessibilityPanel.querySelector('[data-accessibility-palette]').addEventListener('change', async (event) => {
            const previousPreferences = [...accessibilityPreferences];
            setPreference('color_palette_blue_orange', event.target.value === 'blue-orange');
            setPreference('color_palette_purple_yellow', event.target.value === 'purple-yellow');
            await saveAccessibilityPreferences(previousPreferences);
        });

        updateAccessibilityControls();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', addThemeToggle, { once: true });
    } else {
        addThemeToggle();
    }
})();
