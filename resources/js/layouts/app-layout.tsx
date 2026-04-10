import { AppSidebar } from '@/components/app-sidebar';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem as TypesBreadcrumbItem } from '@/types';
import { type PropsWithChildren, type ReactNode } from 'react';

interface AppLayoutProps extends PropsWithChildren {
    breadcrumbs?: TypesBreadcrumbItem[];
    header?: ReactNode;
    title?: string;
    description?: string;
    className?: string;
}

export default function AppLayout({
    breadcrumbs,
    header,
    title,
    description,
    className,
    children,
}: AppLayoutProps) {
    const safeTitle =
        typeof title === 'string'
            ? title
            : typeof title === 'number'
                ? String(title)
                : undefined;
    const safeDescription =
        typeof description === 'string'
            ? description
            : typeof description === 'number'
                ? String(description)
                : undefined;
    const headerContent =
        header ||
        (safeTitle || safeDescription ? (
            <div className="space-y-1">
                {safeTitle && <h1 className="text-xl font-semibold">{safeTitle}</h1>}
                {safeDescription && (
                    <p className="text-sm text-muted-foreground">{safeDescription}</p>
                )}
            </div>
        ) : null);

    return (
        <div className="flex min-h-screen bg-white">
            <AppSidebar />
            <main className="flex flex-1 flex-col">
                {(breadcrumbs || headerContent) && (
                    <div className="sticky top-0 z-10 border-b bg-white px-6 py-4">
                        <div className="flex items-center justify-between">
                            <div className="flex flex-col gap-2">
                                {breadcrumbs && breadcrumbs.length > 0 && (
                                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                                )}
                                {headerContent}
                            </div>
                        </div>
                    </div>
                )}
                <div className={cn('flex-1 p-6', className)}>{children}</div>
            </main>
        </div>
    );
}
