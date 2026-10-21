(() => {
    const root = document.documentElement;
    const themeStorageKey = 'lunaris-theme';
    const accessibilityStorageKey = 'lunaris-accessibility';
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
        'color_palette_purple_yellow',
        'readable_text',
        'accessible_font'
    ]);
    const isAuthenticated = Boolean(root.dataset.accessibilityCsrf);

    function readStorage(key) {
        try {
            return localStorage.getItem(key);
        } catch (error) {
            console.error(`Não foi possível ler a configuração "${key}".`, error);
            return null;
        }
    }

    function writeStorage(key, value) {
        try {
            localStorage.setItem(key, value);
            return true;
        } catch (error) {
            console.error(`Não foi possível salvar a configuração "${key}".`, error);
            return false;
        }
    }

    const savedTheme = readStorage(themeStorageKey);
    const systemPrefersDark = window.matchMedia
        ? window.matchMedia('(prefers-color-scheme: dark)').matches
        : false;
    let activeTheme = savedTheme === 'light' || savedTheme === 'dark'
        ? savedTheme
        : (systemPrefersDark ? 'dark' : 'light');

    function applyTheme(theme) {
        activeTheme = theme;
        root.dataset.theme = theme;
        writeStorage(themeStorageKey, theme);
        document.querySelector('meta[name="theme-color"]')?.setAttribute(
            'content',
            theme === 'light' ? '#F5F7FF' : '#050817'
        );
    }

    applyTheme(activeTheme);

    let accessibilityPreferences = [];
    try {
        const storedPreferences = isAuthenticated
            ? root.dataset.accessibilityPreferences
            : readStorage(accessibilityStorageKey);
        const parsedPreferences = JSON.parse(storedPreferences || '[]');
        accessibilityPreferences = Array.isArray(parsedPreferences)
            ? [...new Set(parsedPreferences.filter((preference) => supportedPreferences.has(preference)))]
            : [];
    } catch (error) {
        console.error('Não foi possível carregar as preferências de acessibilidade.', error);
    }

    function applyAccessibilityPreferences() {
        const textScale = accessibilityPreferences.includes('largest_text')
            ? 'large'
            : (accessibilityPreferences.includes('larger_text') ? 'medium' : 'normal');
        root.dataset.textScale = textScale;
        root.dataset.contrast = String(accessibilityPreferences.includes('high_contrast'));
        root.dataset.reducedMotion = String(accessibilityPreferences.includes('reduced_motion'));
        root.dataset.accessibilityPreferences = JSON.stringify(accessibilityPreferences);
        root.dataset.colorVision = String(accessibilityPreferences.includes('color_vision'));
        root.dataset.accessibleFont = String(accessibilityPreferences.includes('accessible_font'));
        root.dataset.readableText = String(accessibilityPreferences.includes('readable_text'));
        root.dataset.colorPalette = accessibilityPreferences.includes('color_palette_blue_orange')
            ? 'blue-orange'
            : (accessibilityPreferences.includes('color_palette_purple_yellow') ? 'purple-yellow' : 'default');
    }

    applyAccessibilityPreferences();

    function addSettingsPanel() {
        const interfaceLanguage = document.documentElement.lang;
        const translations = {
            'pt-BR': {
                settings: 'Configurações de aparência e acessibilidade',
                open: 'Abrir configurações de aparência e acessibilidade',
                close: 'Fechar configurações',
                appearance: 'Aparência',
                theme: 'Tema',
                light: 'Claro',
                dark: 'Escuro',
                accessibility: 'Acessibilidade',
                textSize: 'Tamanho do texto',
                increase: 'Aumentar',
                decrease: 'Diminuir',
                resetText: 'Padrão',
                readableText: 'Melhorar legibilidade dos textos',
                accessibleFont: 'Usar fonte mais acessível',
                contrast: 'Alto contraste',
                motion: 'Reduzir animações e efeitos',
                colorVision: 'Ajudar a distinguir as cores',
                palette: 'Combinação de cores',
                defaultPalette: 'Padrão',
                blueOrange: 'Azul e laranja',
                purpleYellow: 'Roxo e amarelo',
                reset: 'Restaurar configurações padrão',
                saved: 'As preferências de acessibilidade são salvas no seu perfil quando você entra.',
                savedConfirmation: 'Preferências de acessibilidade salvas.',
                saveError: 'Não foi possível salvar as preferências. Tente novamente.',
                closeLabel: 'Fechar'
            },
            en: {
                settings: 'Appearance and accessibility settings',
                open: 'Open appearance and accessibility settings',
                close: 'Close settings',
                appearance: 'Appearance',
                theme: 'Theme',
                light: 'Light',
                dark: 'Dark',
                accessibility: 'Accessibility',
                textSize: 'Text size',
                increase: 'Increase',
                decrease: 'Decrease',
                resetText: 'Default',
                readableText: 'Improve text readability',
                accessibleFont: 'Use a more accessible font',
                contrast: 'High contrast',
                motion: 'Reduce animations and effects',
                colorVision: 'Help distinguish colors',
                palette: 'Color combination',
                defaultPalette: 'Default',
                blueOrange: 'Blue and orange',
                purpleYellow: 'Purple and yellow',
                reset: 'Restore default settings',
                saved: 'Accessibility preferences are saved to your profile when signed in.',
                savedConfirmation: 'Accessibility preferences saved.',
                saveError: 'Could not save preferences. Try again.',
                closeLabel: 'Close'
            },
            es: {
                settings: 'Configuración de apariencia y accesibilidad',
                open: 'Abrir configuración de apariencia y accesibilidad',
                close: 'Cerrar configuración',
                appearance: 'Apariencia',
                theme: 'Tema',
                light: 'Claro',
                dark: 'Oscuro',
                accessibility: 'Accesibilidad',
                textSize: 'Tamaño del texto',
                increase: 'Aumentar',
                decrease: 'Reducir',
                resetText: 'Predeterminado',
                readableText: 'Mejorar la legibilidad del texto',
                accessibleFont: 'Usar una fuente más accesible',
                contrast: 'Alto contraste',
                motion: 'Reducir animaciones y efectos',
                colorVision: 'Ayudar a distinguir los colores',
                palette: 'Combinación de colores',
                defaultPalette: 'Predeterminada',
                blueOrange: 'Azul y naranja',
                purpleYellow: 'Morado y amarillo',
                reset: 'Restablecer la configuración',
                saved: 'Las preferencias de accesibilidad se guardan en tu perfil al iniciar sesión.',
                savedConfirmation: 'Preferencias de accesibilidad guardadas.',
                saveError: 'No se pudieron guardar las preferencias. Inténtalo de nuevo.',
                closeLabel: 'Cerrar'
            }
        };
        const labels = translations[interfaceLanguage] || translations['pt-BR'];
        const widget = document.createElement('div');
        widget.className = 'lunaris-settings';
        widget.innerHTML = `
            <button class="lunaris-settings-trigger" type="button"
                aria-label="${labels.open}" aria-expanded="false" aria-controls="lunarisSettingsPanel">
                <span aria-hidden="true">♿</span>
            </button>
            <section class="lunaris-settings-panel" id="lunarisSettingsPanel"
                aria-label="${labels.settings}" hidden>
                <div class="lunaris-settings-heading">
                    <h2>${labels.settings}</h2>
                    <button class="lunaris-settings-close" type="button"
                        aria-label="${labels.closeLabel}">&times;</button>
                </div>
                <fieldset class="lunaris-settings-group lunaris-appearance-settings">
                    <legend>${labels.appearance}</legend>
                    <span class="lunaris-settings-label">${labels.theme}</span>
                    <div class="lunaris-theme-options" role="group" aria-label="${labels.theme}">
                        <button type="button" data-theme-choice="light">${labels.light}</button>
                        <button type="button" data-theme-choice="dark">${labels.dark}</button>
                    </div>
                </fieldset>
                <fieldset class="lunaris-settings-group lunaris-accessibility-settings">
                    <legend>${labels.accessibility}</legend>
                    <span class="lunaris-settings-label">${labels.textSize}</span>
                    <div class="lunaris-text-size-controls">
                        <button type="button" data-accessibility-action="decrease">${labels.decrease}</button>
                        <button type="button" data-accessibility-action="increase">${labels.increase}</button>
                        <button type="button" data-accessibility-action="resetText">${labels.resetText}</button>
                    </div>
                    <button class="lunaris-preference-toggle" type="button"
                        data-accessibility-action="readability" aria-pressed="false">${labels.readableText}</button>
                    <button class="lunaris-preference-toggle" type="button"
                        data-accessibility-action="font" aria-pressed="false">${labels.accessibleFont}</button>
                    <button class="lunaris-preference-toggle" type="button"
                        data-accessibility-action="contrast" aria-pressed="false">${labels.contrast}</button>
                    <button class="lunaris-preference-toggle" type="button"
                        data-accessibility-action="motion" aria-pressed="false">${labels.motion}</button>
                    <button class="lunaris-preference-toggle" type="button"
                        data-accessibility-action="colorVision" aria-pressed="false">${labels.colorVision}</button>
                    <label class="lunaris-settings-label" for="lunarisColorPalette">${labels.palette}</label>
                    <select id="lunarisColorPalette" data-accessibility-palette>
                        <option value="default">${labels.defaultPalette}</option>
                        <option value="blue-orange">${labels.blueOrange}</option>
                        <option value="purple-yellow">${labels.purpleYellow}</option>
                    </select>
                    <button class="lunaris-reset-settings" type="button"
                        data-accessibility-action="reset">${labels.reset}</button>
                    <p class="lunaris-settings-hint">${labels.saved}</p>
                    <p class="lunaris-settings-status" role="status" aria-live="polite"></p>
                </fieldset>
            </section>
        `;
        document.body.append(widget);

        const trigger = widget.querySelector('.lunaris-settings-trigger');
        const panel = widget.querySelector('.lunaris-settings-panel');
        const status = widget.querySelector('.lunaris-settings-status');

        function updateControls() {
            widget.querySelectorAll('[data-theme-choice]').forEach((button) => {
                button.setAttribute('aria-pressed', String(button.dataset.themeChoice === activeTheme));
            });
            widget.querySelector('[data-accessibility-action="decrease"]').disabled =
                !accessibilityPreferences.includes('largest_text') && !accessibilityPreferences.includes('larger_text');
            widget.querySelector('[data-accessibility-action="increase"]').disabled =
                accessibilityPreferences.includes('largest_text');
            widget.querySelector('[data-accessibility-action="readability"]').setAttribute(
                'aria-pressed',
                String(accessibilityPreferences.includes('readable_text'))
            );
            widget.querySelector('[data-accessibility-action="font"]').setAttribute(
                'aria-pressed',
                String(accessibilityPreferences.includes('accessible_font'))
            );
            widget.querySelector('[data-accessibility-action="contrast"]').setAttribute(
                'aria-pressed',
                String(accessibilityPreferences.includes('high_contrast'))
            );
            widget.querySelector('[data-accessibility-action="motion"]').setAttribute(
                'aria-pressed',
                String(accessibilityPreferences.includes('reduced_motion'))
            );
            widget.querySelector('[data-accessibility-action="colorVision"]').setAttribute(
                'aria-pressed',
                String(accessibilityPreferences.includes('color_vision'))
            );
            widget.querySelector('[data-accessibility-palette]').value =
                accessibilityPreferences.includes('color_palette_blue_orange')
                    ? 'blue-orange'
                    : (accessibilityPreferences.includes('color_palette_purple_yellow') ? 'purple-yellow' : 'default');
        }

        function closePanel(restoreFocus = false) {
            panel.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
            trigger.setAttribute('aria-label', labels.open);
            if (restoreFocus) {
                trigger.focus();
            }
        }

        trigger.addEventListener('click', () => {
            const opening = panel.hidden;
            panel.hidden = !opening;
            trigger.setAttribute('aria-expanded', String(opening));
            trigger.setAttribute('aria-label', opening ? labels.close : labels.open);
            if (opening) {
                panel.querySelector('.lunaris-settings-close').focus();
            }
        });

        widget.querySelector('.lunaris-settings-close').addEventListener('click', () => closePanel(true));

        document.addEventListener('click', (event) => {
            if (!panel.hidden && !widget.contains(event.target)) {
                closePanel();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !panel.hidden) {
                closePanel(true);
            }
        });

        function setPreference(preference, enabled) {
            accessibilityPreferences = accessibilityPreferences.filter((item) => item !== preference);
            if (enabled) {
                accessibilityPreferences.push(preference);
            }
        }

        async function saveAccessibilityPreferences(previousPreferences) {
            applyAccessibilityPreferences();
            updateControls();

            if (!isAuthenticated) {
                writeStorage(accessibilityStorageKey, JSON.stringify(accessibilityPreferences));
                status.textContent = '';
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
                updateControls();
                status.textContent = labels.savedConfirmation;
            } catch (error) {
                accessibilityPreferences = previousPreferences;
                applyAccessibilityPreferences();
                updateControls();
                status.textContent = labels.saveError;
                console.error('Não foi possível salvar as preferências de acessibilidade.', error);
            }
        }

        widget.addEventListener('click', async (event) => {
            const themeControl = event.target.closest('[data-theme-choice]');
            if (themeControl) {
                applyTheme(themeControl.dataset.themeChoice);
                updateControls();
                return;
            }

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
                setPreference('largest_text', false);
                setPreference('larger_text', false);
            } else if (action === 'resetText') {
                setPreference('larger_text', false);
                setPreference('largest_text', false);
            } else if (action === 'readability') {
                setPreference('readable_text', !accessibilityPreferences.includes('readable_text'));
            } else if (action === 'font') {
                setPreference('accessible_font', !accessibilityPreferences.includes('accessible_font'));
            } else if (action === 'contrast') {
                setPreference('high_contrast', !accessibilityPreferences.includes('high_contrast'));
            } else if (action === 'motion') {
                setPreference('reduced_motion', !accessibilityPreferences.includes('reduced_motion'));
            } else if (action === 'colorVision') {
                setPreference('color_vision', !accessibilityPreferences.includes('color_vision'));
            } else if (action === 'reset') {
                accessibilityPreferences = accessibilityPreferences.filter((preference) =>
                    ['screen_reader', 'keyboard', 'captions_visual', 'simple_instructions', 'other'].includes(preference)
                );
            }

            await saveAccessibilityPreferences(previousPreferences);
        });

        widget.querySelector('[data-accessibility-palette]').addEventListener('change', async (event) => {
            const previousPreferences = [...accessibilityPreferences];
            const palette = event.target.value;
            setPreference('color_palette_blue_orange', palette === 'blue-orange');
            setPreference('color_palette_purple_yellow', palette === 'purple-yellow');
            setPreference('color_vision', palette !== 'default');
            await saveAccessibilityPreferences(previousPreferences);
        });

        updateControls();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', addSettingsPanel, { once: true });
    } else {
        addSettingsPanel();
    }
})();
