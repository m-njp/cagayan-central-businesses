import { Link, usePage } from '@inertiajs/react';
import type { MouseEvent } from 'react';
import { icons } from '@/lib/icons';
import { pagePath, pageSearchParams } from '@/lib/navigation';
import { navigateDashboard } from '@/lib/workspace-map-navigation';
import { workspaceDestination } from '@/lib/workspace-navigation';
import { dashboard } from '@/routes/capitol';
import type { Workspace, WorkspaceMap } from '@/types/workspace';

export type BreadcrumbItem = { label: string; href?: string };

export function WorkspaceBreadcrumbs() {
    const { props, url } = usePage();
    const path = pagePath(url);
    const section = workspaceDestination(url, props.workspace as Workspace);
    const page = Number(pageSearchParams(url).get('page') ?? 1);
    const supplied = props.breadcrumbs as BreadcrumbItem[] | undefined;
    const map =
        path === dashboard().url
            ? (props.map as WorkspaceMap | undefined)
            : undefined;
    const town = map?.atlas.towns.find(
        (item) => item.code === pageSearchParams(url).get('town'),
    );
    const barangay = town
        ? map?.barangays.find(
              (item) => item.code === pageSearchParams(url).get('barangay'),
          )
        : undefined;
    const items: (BreadcrumbItem & { selectTown?: string | null })[] = [
        { label: 'Dashboard', href: dashboard().url, selectTown: null },
        ...(supplied ??
            (section && section.label !== 'Dashboard'
                ? [{ label: section.label, href: section.href.url }]
                : [])),
        ...(town
            ? [
                  {
                      label: town.name,
                      href: dashboard({ query: { town: town.code } }).url,
                      selectTown: town.code,
                  },
              ]
            : []),
        ...(barangay ? [{ label: barangay.name }] : []),
        ...(!supplied && section?.label !== 'Dashboard' && page > 1
            ? [{ label: `Page ${page}` }]
            : []),
    ];
    const Chevron = icons.breadcrumb;
    const select = (event: MouseEvent, code: string | null) => {
        if (
            path === dashboard().url &&
            !event.metaKey &&
            !event.ctrlKey &&
            !event.shiftKey &&
            !event.altKey
        ) {
            event.preventDefault();
            void navigateDashboard(code);
        }
    };

    return (
        <nav aria-label="Breadcrumb">
            <ol className="flex flex-wrap items-center gap-2 type-body">
                {items.map((item, index) => (
                    <li
                        key={`${index}-${item.label}`}
                        className="flex items-center gap-2"
                    >
                        {index > 0 && (
                            <Chevron className="size-3" aria-hidden="true" />
                        )}
                        {index === items.length - 1 || !item.href ? (
                            <span
                                aria-current={
                                    index === items.length - 1
                                        ? 'page'
                                        : undefined
                                }
                            >
                                {item.label}
                            </span>
                        ) : (
                            <Link
                                href={item.href}
                                className="underline underline-offset-4"
                                onClick={
                                    item.selectTown !== undefined
                                        ? (event) =>
                                              select(
                                                  event,
                                                  item.selectTown ?? null,
                                              )
                                        : undefined
                                }
                            >
                                {item.label}
                            </Link>
                        )}
                    </li>
                ))}
            </ol>
        </nav>
    );
}
