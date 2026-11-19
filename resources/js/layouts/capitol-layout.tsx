import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { AppearanceToggle } from '@/components/appearance-toggle';
import { WorkspaceStatus } from '@/components/capitol/workspace-status';
import { WorkspaceBreadcrumbs } from '@/components/capitol/workspace-breadcrumbs';
import { KitaLogo } from '@/components/kita-logo';
import { SimulatedLabel } from '@/components/privacy/simulated-label';
import {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { icons } from '@/lib/icons';
import { cn } from '@/lib/utils';

import {
    workspaceNavigation,
    workspaceDestination,
} from '@/lib/workspace-navigation';
import { logout } from '@/routes';
import { dashboard } from '@/routes/capitol';
import type { Workspace } from '@/types/workspace';

export default function CapitolLayout({ children }: { children: ReactNode }) {
    const { props, url } = usePage();
    const workspace = props.workspace as Workspace;
    const [open, setOpen] = useState(false);
    const items = workspaceNavigation(workspace);
    const current = workspaceDestination(url, workspace);
    useFlashToast();
    useEffect(() => setOpen(false), [url]);
    const Menu = open ? icons.close : icons.menu;
    const SignOut = icons.signOut;
    const Expand = icons.expand;
    const account = items.find((item) => item.group === 'Account')!;
    const Profile = account.icon;
    const groups = [
        ...new Set(
            items
                .filter((item) => item.group !== 'Account')
                .map((item) => item.group),
        ),
    ];

    return (
        <div
            data-theme-surface
            className="min-h-screen bg-background text-foreground lg:grid lg:grid-cols-[var(--workspace-sidebar-width)_minmax(0,1fr)]"
        >
            <a
                href="#workspace-content"
                className="sr-only focus:not-sr-only focus:fixed focus:z-50 focus:bg-background focus:p-3"
            >
                Skip to Content
            </a>
            <aside
                data-theme-surface
                className="border-b bg-card lg:fixed lg:inset-y-0 lg:w-(--workspace-sidebar-width) lg:border-r lg:border-b-0"
            >
                <div
                    data-theme-surface
                    className="flex h-(--workspace-header-height) items-center justify-between gap-3 border-b px-4"
                >
                    <Link
                        href={dashboard()}
                        className="rounded-sm focus-visible:outline-ring"
                    >
                        <KitaLogo label={props.name} className="h-6 w-auto" />
                    </Link>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="lg:hidden"
                        aria-label={
                            open ? 'Close navigation' : 'Open navigation'
                        }
                        aria-expanded={open}
                        aria-controls="workspace-navigation"
                        onClick={() => setOpen(!open)}
                    >
                        <Menu aria-hidden="true" />
                    </Button>
                </div>
                <div
                    id="workspace-navigation"
                    className={cn(
                        'flex-col gap-3 p-2 lg:flex lg:h-[calc(100dvh-3.5rem)]',
                        open ? 'flex' : 'hidden',
                    )}
                >
                    <nav aria-label="Workspace" className="flex flex-col gap-3">
                        {groups.map((group) => (
                            <div key={group} className="flex flex-col gap-1">
                                <p className="px-2 type-label text-muted-foreground">
                                    {group}
                                </p>
                                {items
                                    .filter((item) => item.group === group)
                                    .map(({ label, href, icon: Icon }) => (
                                        <Link
                                            key={label}
                                            href={href}
                                            aria-current={
                                                current?.href.url === href.url
                                                    ? 'page'
                                                    : undefined
                                            }
                                            className={cn(
                                                'flex min-h-10 items-center gap-2 rounded-sm px-2 py-1.5 type-body hover:bg-muted focus-visible:outline-ring lg:min-h-9',
                                                current?.href.url ===
                                                    href.url &&
                                                    'bg-muted font-medium',
                                            )}
                                        >
                                            <Icon
                                                className="size-4 shrink-0"
                                                aria-hidden="true"
                                            />
                                            {label}
                                        </Link>
                                    ))}
                            </div>
                        ))}
                    </nav>
                    <div className="mt-auto flex items-center gap-1 border-t pt-2">
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="ghost"
                                    className="min-w-0 flex-1 justify-start gap-2 px-2"
                                >
                                    <Profile
                                        className="size-4 shrink-0"
                                        aria-hidden="true"
                                    />
                                    <span className="flex-1 text-left">
                                        Account
                                    </span>
                                    <Expand
                                        className="size-4 shrink-0"
                                        aria-hidden="true"
                                    />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent
                                side="top"
                                align="start"
                                className="w-52 rounded-sm"
                            >
                                <DropdownMenuItem asChild>
                                    <Link href={account.href}>
                                        <Profile aria-hidden="true" />
                                        Profile
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem asChild>
                                    <Link
                                        href={logout()}
                                        as="button"
                                        className="w-full"
                                    >
                                        <SignOut aria-hidden="true" />
                                        Sign Out
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                        <AppearanceToggle />
                    </div>
                </div>
            </aside>
            <div className="min-w-0 lg:col-start-2">
                <div
                    data-theme-surface
                    className="flex min-h-12 items-center justify-between gap-3 border-b bg-card px-gutter md:px-6 lg:min-h-(--workspace-header-height)"
                >
                    <WorkspaceBreadcrumbs />
                    <SimulatedLabel />
                </div>
                <main
                    id="workspace-content"
                    className="flex min-w-0 flex-col gap-4 p-gutter md:p-6"
                >
                    <WorkspaceStatus workspace={workspace} />
                    {children}
                </main>
            </div>
        </div>
    );
}
