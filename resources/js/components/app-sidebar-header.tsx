import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/react';

export function AppSidebarHeader({
    breadcrumbs = [],
    title,
    description,
}: {
    breadcrumbs?: BreadcrumbItemType[];
    title?: string;
    description?: string;
}) {
    const page = usePage();

    const finalTitle = title ?? breadcrumbs.at(-1)?.title ?? titleFromUrl(page.url);
    return (
        <header className="flex min-h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/50 px-6 py-3 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <SidebarTrigger className="-ml-1" />
            <div className="flex min-w-0 flex-1 flex-col gap-1">
                <h1 className="truncate text-lg font-semibold leading-tight">
                    {finalTitle}
                </h1>
                {description && (
                    <p className="truncate text-sm text-muted-foreground">
                        {description}
                    </p>
                )}
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
        </header>
    );
}

function titleFromUrl(url: string): string {
    const clean = url.split('?')[0]?.split('#')[0] ?? url;
    const parts = clean.split('/').filter(Boolean);
    const last = parts.at(-1) ?? '';
    if (!last) return 'Dashboard';

    const decoded = decodeURIComponent(last);
    const words = decoded.replace(/[-_]+/g, ' ').trim().split(/\s+/).filter(Boolean);
    return words.map((w) => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
}
