import { ref, watch } from 'vue';

export const theme = ref(localStorage.getItem('theme') || 'light');
export const color = ref(localStorage.getItem('color') || 'zinc');
export const radius = ref(Number(localStorage.getItem('radius') || 0.75));

watch(theme, (val) => {
    document.documentElement.classList.toggle('dark', val === 'dark');
    localStorage.setItem('theme', val);
});

watch(color, (val) => {
  document.documentElement.style.setProperty('--primary', `var(--${val}-500)`)
  localStorage.setItem('color', val)
})

watch(radius, (val) => localStorage.setItem('radius', val.toString()));

export function useTheme() {
    function toggleTheme() {
        theme.value = theme.value === 'dark' ? 'light' : 'dark';
    }
    return { theme, color, radius, toggleTheme };
}
