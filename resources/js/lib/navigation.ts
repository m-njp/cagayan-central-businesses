import { router } from '@inertiajs/react';
import type { RouteDefinition } from '@/wayfinder';

export function pagePath(pageUrl: string): string {
    return pageUrl.split('?')[0];
}

export function pageSearchParams(pageUrl: string): URLSearchParams {
    return new URL(pageUrl, 'http://localhost').searchParams;
}

export function replaceLocalQuery(
    pageUrl: string,
    route: (options?: {
        query?: Record<string, string | undefined>;
    }) => RouteDefinition<'get'>,
    patch: Record<string, string | null | undefined>,
): void {
    const query = Object.fromEntries(pageSearchParams(pageUrl));
    for (const [key, value] of Object.entries(patch)) {
        if (value) query[key] = value;
        else delete query[key];
    }
    router.replace({
        url: route({ query }).url,
        preserveScroll: true,
        preserveState: true,
    });
}

// A section is current on its own page and on any page below it, ignoring the query string.
export function isCurrentSection(
    pageUrl: string,
    href: RouteDefinition<'get'>,
): boolean {
    const page = pagePath(pageUrl);
    const section = pagePath(href.url);

    return section === '/'
        ? page === '/'
        : page === section || page.startsWith(`${section}/`);
}
