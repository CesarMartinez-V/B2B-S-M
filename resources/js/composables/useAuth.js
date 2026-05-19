import { computed } from 'vue';
import { useLocalStorage } from './useLocalStorage.js';

const AUTH_KEY = 'portal-auth-session';
const session = useLocalStorage(AUTH_KEY, null);

export const useAuth = () => {
    const isAuthenticated = computed(() => Boolean(session.value?.token));

    const login = ({ username, password }) => {
        const now = new Date().toISOString();

        session.value = {
            token: `mock-${Date.now()}`,
            user: {
                name: username,
                username,
            },
            createdAt: now,
        };
    };

    const logout = () => {
        session.value = null;
    };

    return {
        session,
        isAuthenticated,
        login,
        logout,
    };
};
