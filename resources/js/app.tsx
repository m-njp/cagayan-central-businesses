import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { ComponentType } from 'react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import PublicLayout from '@/layouts/public-layout';
import CapitolLayout from '@/layouts/capitol-layout';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const pages = import.meta.glob<{ default: ComponentType }>(
    './pages/**/index.tsx',
);

void createInertiaApp({
    async resolve(name) {
        const page = await resolvePageComponent(`./pages/${name}.tsx`, pages);
        return page.default;
    },
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) =>
        name.startsWith('public/')
            ? PublicLayout
            : name.startsWith('capitol/')
              ? CapitolLayout
              : null,
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: 'var(--primary)',
    },
});

initializeTheme();
