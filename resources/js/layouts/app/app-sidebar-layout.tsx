import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { OutletSwitcher } from '@/components/outlet-switcher';
import { type BreadcrumbItem } from '@/types';
import { type PropsWithChildren } from 'react';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
    title,
    description,
}: PropsWithChildren<{
    breadcrumbs?: BreadcrumbItem[];
    title?: string;
    description?: string;
}>) {
    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden p-4 md:p-6">
                <div className="flex items-center justify-between mb-4">
                    <AppSidebarHeader
                        breadcrumbs={breadcrumbs}
                        title={title}
                        description={description}
                    />
                    <OutletSwitcher />
                </div>
                {children}
            </AppContent>
        </AppShell>
    );
}
