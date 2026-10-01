import { createInertiaApp } from '@inertiajs/vue3';
import { defineAsyncComponent } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Ashok Barua';

const AuthLayout = defineAsyncComponent(
    () => import('@/layouts/AuthLayout.vue'),
);
const DashboardLayout = defineAsyncComponent(
    () => import('@/layouts/DashboardLayout.vue'),
);
const GuestLayout = defineAsyncComponent(
    () => import('@/layouts/GuestLayout.vue'),
);
const SettingsLayout = defineAsyncComponent(
    () => import('@/layouts/settings/Layout.vue'),
);

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name.startsWith('dashboard/settings/'):
                return [DashboardLayout, SettingsLayout];
            case name.startsWith('dashboard/'):
                return DashboardLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            default:
                return GuestLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
