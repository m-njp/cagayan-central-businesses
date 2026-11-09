import type { LucideIcon } from 'lucide-react';
import type { RouteDefinition } from '@/wayfinder';

export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
};

export type NavItem = {
    title: string;
    href: RouteDefinition<'get'>;
    icon: LucideIcon;
};
