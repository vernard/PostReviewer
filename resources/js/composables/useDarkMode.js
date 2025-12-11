import { ref, watch, onMounted } from 'vue';

const isDark = ref(document.documentElement.classList.contains('dark'));

// Track if user has explicitly set a preference
const hasUserPreference = ref(localStorage.getItem('darkMode') !== null);

export function useDarkMode() {
    const toggle = () => {
        isDark.value = !isDark.value;
        hasUserPreference.value = true;
    };

    const setDark = (value) => {
        isDark.value = value;
        hasUserPreference.value = true;
    };

    // Follow system preference if user hasn't set one
    const followSystem = () => {
        hasUserPreference.value = false;
        localStorage.removeItem('darkMode');
        isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
    };

    // Listen for system preference changes
    const setupSystemListener = () => {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

        const handleChange = (e) => {
            // Only follow system if user hasn't set a preference
            if (!hasUserPreference.value) {
                isDark.value = e.matches;
            }
        };

        mediaQuery.addEventListener('change', handleChange);

        // Return cleanup function
        return () => mediaQuery.removeEventListener('change', handleChange);
    };

    watch(isDark, (newValue) => {
        if (newValue) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        // Only save to localStorage if user has explicit preference
        if (hasUserPreference.value) {
            localStorage.setItem('darkMode', newValue ? 'true' : 'false');
        }
    });

    // Set up system preference listener on mount
    onMounted(() => {
        setupSystemListener();
    });

    return {
        isDark,
        hasUserPreference,
        toggle,
        setDark,
        followSystem,
    };
}
