import { ref } from 'vue';

const THEME_KEY = 'portal-theme';
const DARK_THEME = 'dark';
const LIGHT_THEME = 'light';
const isLightMode = ref(false);
const currentTheme = ref(DARK_THEME);

const setDocumentTheme = (theme) => {
    if (typeof document === 'undefined') return;

    const isLight = theme === LIGHT_THEME;
    document.documentElement.dataset.theme = theme;
    document.body.dataset.theme = theme;
    document.documentElement.classList.toggle('portal-light', isLight);
    document.body.classList.toggle('portal-light', isLight);
    document.documentElement.classList.toggle('portal-dark', !isLight);
    document.body.classList.toggle('portal-dark', !isLight);
};

const applyTheme = (value = DARK_THEME) => {
    const theme = value === true || value === LIGHT_THEME ? LIGHT_THEME : DARK_THEME;
    isLightMode.value = theme === LIGHT_THEME;
    currentTheme.value = theme;
    setDocumentTheme(theme);

    try {
        if (typeof window !== 'undefined') window.localStorage.setItem(THEME_KEY, theme);
    } catch {
        // Theme remains active for the current session.
    }
};

const initTheme = () => {
    let stored = 'dark';

    try {
        if (typeof window !== 'undefined') stored = window.localStorage.getItem(THEME_KEY) || DARK_THEME;
    } catch {
        stored = DARK_THEME;
    }

    applyTheme(stored === LIGHT_THEME ? LIGHT_THEME : DARK_THEME);
};

initTheme();

export const useTheme = () => ({
    currentTheme,
    isLightMode,
    applyTheme,
    initTheme,
    toggleTheme: () => applyTheme(isLightMode.value ? DARK_THEME : LIGHT_THEME),
});
