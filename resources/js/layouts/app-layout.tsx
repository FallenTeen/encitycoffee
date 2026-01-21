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
    const headerContent =
        header ||
        (title || description ? (
            <div className="space-y-1">
                {title && <h1 className="text-xl font-semibold">{title}</h1>}
                {description && (
                    <p className="text-sm text-muted-foreground">{description}</p>
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
