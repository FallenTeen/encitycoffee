// components/app-sidebar.tsx
import { useEffect, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { NavMain } from './nav-main';
import { NavFavorites } from './nav-favorites';
import { NavRecent } from './nav-recent';
import { NavUser } from './nav-user';
import { useSidebarState } from '@/hooks/use-sidebar-state';
import { Sheet, SheetContent } from './ui/sheet';
import { Button } from './ui/button';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { cn, resolveUrl } from '@/lib/utils';
import type { NavGroup, NavItem } from '@/types';
import AppLogo from './app-logo';
import {
    LayoutDashboard,
    Users,
    Building,
    TrendingUp,
    Package,
    Clock,
    Warehouse,
    FolderTree,
    ShoppingCart,
    FileText,
} from 'lucide-react';

// Helper to get all menu items flat from groups
function getAllItems(groups: NavGroup[]): NavItem[] {
    return groups.flatMap(group => group.items);
}

// Helper to get current page title
function getCurrentPageTitle(url: string, groups: NavGroup[]): string {
    const allItems = getAllItems(groups);
    const currentItem = allItems.find(item => url.startsWith(resolveUrl(item.href)));
    return currentItem?.title || 'Page';
}

// Get main nav items based on role
function getMainNavItems(role?: string): NavGroup[] {
    const groups: NavGroup[] = [];
    if (!role) {
        groups.push({
            title: 'Menu',
            items: [
                { title: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
            ],
        });
        return groups;
    }

    if (role === 'it_support') {
        groups.push({
            title: 'Admin Menu',
            items: [
                { title: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard },
                { title: 'Cabang', href: '/admin/cabang', icon: Building },
                { title: 'Pengguna', href: '/admin/users', icon: Users },
            ],
        });
        groups.push({
            title: 'Coffeeshop',
            items: [
                { title: 'Kategori', href: '/produk/kategori', icon: FolderTree },
                { title: 'Daftar Produk', href: '/produk', icon: Package },
                { title: 'Transaksi', href: '/transaksi', icon: ShoppingCart },
                { title: 'Bill', href: '/transaksi/open-bill', icon: ShoppingCart },
                { title: 'Produk Favorit', href: '/laporan/produk-favorit', icon: TrendingUp },
            ],
        });
        groups.push({
            title: 'Report',
            items: [
                { title: 'Shift', href: '/laporan/shift', icon: Clock },
                { title: 'Harian', href: '/laporan/harian', icon: FileText },
                { title: 'Pendapatan Kategori', href: '/laporan/pendapatan-kategori', icon: TrendingUp },
                { title: 'Penjualan', href: '/laporan/penjualan-produk', icon: TrendingUp },
                { title: 'Stok', href: '/stok', icon: Warehouse },
            ],
        });
    } else if (role === 'manager') {
        groups.push({
            title: 'Admin Menu',
            items: [
                { title: 'Dashboard', href: '/manager/dashboard', icon: LayoutDashboard },
                { title: 'Manajemen Karyawan', href: '/manager/karyawan', icon: Users },
                { title: 'Laporan Cabang', href: '/manager/laporan-cabang', icon: Building },
            ],
        });
        groups.push({
            title: 'Coffeeshop',
            items: [
                { title: 'Kategori', href: '/produk/kategori', icon: FolderTree },
                { title: 'Daftar Produk', href: '/produk', icon: Package },
                { title: 'Transaksi', href: '/transaksi', icon: ShoppingCart },
                { title: 'Bill', href: '/transaksi/open-bill', icon: ShoppingCart },
                { title: 'Produk Favorit', href: '/laporan/produk-favorit', icon: TrendingUp },
            ],
        });
        groups.push({
            title: 'Report',
            items: [
                { title: 'Riwayat Transaksi', href: '/laporan/riwayat-transaksi', icon: Clock },
                { title: 'Analisis Penjualan', href: '/laporan/analisis-penjualan', icon: TrendingUp },
                { title: 'Stok', href: '/stok', icon: Warehouse },
            ],
        });
    } else if (role === 'supervisor') {
        groups.push({
            title: 'Admin Menu',
            items: [
                { title: 'Dashboard', href: '/supervisor/dashboard', icon: LayoutDashboard },
                { title: 'Kasir', href: '/supervisor/kasir', icon: Users },
            ],
        });
        groups.push({
            title: 'Coffeeshop',
            items: [
                { title: 'Kategori', href: '/produk/kategori', icon: FolderTree },
                { title: 'Daftar Produk', href: '/produk', icon: Package },
                { title: 'Transaksi', href: '/transaksi', icon: ShoppingCart },
                { title: 'Produk Favorit', href: '/laporan/produk-favorit', icon: TrendingUp },
            ],
        });
        groups.push({
            title: 'Report',
            items: [
                { title: 'Shift', href: '/laporan/shift', icon: Clock },
                { title: 'Harian', href: '/laporan/harian', icon: FileText },
                { title: 'Pendapatan Kategori', href: '/laporan/pendapatan-kategori', icon: TrendingUp },
                { title: 'Penjualan', href: '/laporan/penjualan-produk', icon: TrendingUp },
                { title: 'Stok', href: '/stok', icon: Warehouse },
            ],
        });
    } else if (role === 'kasir') {
        groups.push({
            title: 'Coffeeshop',
            items: [
                { title: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
                { title: 'Transaksi', href: '/transaksi', icon: ShoppingCart },
                { title: 'Bill', href: '/transaksi/open-bill', icon: ShoppingCart },
                { title: 'Produk Favorit', href: '/laporan/produk-favorit', icon: TrendingUp },
            ],
        });
        groups.push({
            title: 'Report',
            items: [
                { title: 'Shift', href: '/laporan/shift', icon: Clock },
                { title: 'Harian', href: '/laporan/harian', icon: FileText },
            ],
        });
    }

    return groups;
}

export function AppSidebar() {
    const { props, url } = usePage<any>();
    const role = props?.auth?.user?.role;
    const userId = props?.auth?.user?.id;

    const [isMobileOpen, setIsMobileOpen] = useState(false);
    const groups = getMainNavItems(role);
    const allItems = getAllItems(groups);

    const {
        expandedGroups,
        favorites,
        recentItems,
        isCollapsed,
        toggleGroup,
        expandGroup,
        toggleFavorite,
        addToRecent,
        toggleCollapsed,
    } = useSidebarState(userId);

    // Auto-expand group containing active page
    useEffect(() => {
        const activeGroup = groups.find(group =>
            group.items.some(item => url.startsWith(resolveUrl(item.href)))
        );
        if (activeGroup && !expandedGroups.includes(activeGroup.title)) {
            expandGroup(activeGroup.title);
        }
    }, [url, groups]);

    // Track recent items
    useEffect(() => {
        const currentTitle = getCurrentPageTitle(url, groups);
        const currentItem = allItems.find(item => url.startsWith(resolveUrl(item.href)));
        
        if (currentItem) {
            addToRecent({
                href: String(currentItem.href),
                title: currentItem.title,
            });
        }
    }, [url]);

    const favoriteItems = allItems.filter(item => favorites.includes(String(item.href)));

    const sidebarContent = (
        <div className="flex h-full flex-col bg-white border-r">
            {/* Header */}
            <div className="flex items-center justify-between p-4 border-b">
                <Link href="/dashboard" className={cn("flex items-center gap-2", isCollapsed && "mx-auto")}>
                    <AppLogo />
                </Link>
                {!isCollapsed && (
                    <Button
                        variant="ghost"
                        size="icon"
                        className="hidden md:flex h-8 w-8"
                        onClick={toggleCollapsed}
                    >
                        <ChevronLeft className="h-4 w-4" />
                    </Button>
                )}
            </div>

            {/* Scrollable Content */}
            <div className="flex-1 overflow-y-auto">
                {/* Favorites Section */}
                <NavFavorites
                    items={favoriteItems}
                    favorites={favorites}
                    isCollapsed={isCollapsed}
                />

                {/* Main Navigation */}
                <NavMain
                    groups={groups}
                    expandedGroups={expandedGroups}
                    favorites={favorites}
                    isCollapsed={isCollapsed}
                    onToggleGroup={toggleGroup}
                    onToggleFavorite={toggleFavorite}
                />
            </div>

            {/* Footer */}
            <div>
                {/* Recent Items Section - Collapsible in footer */}
                <NavRecent
                    items={recentItems}
                    isCollapsed={isCollapsed}
                />
                
                {/* User Menu */}
                <div className="border-t p-4">
                    <NavUser isCollapsed={isCollapsed} />
                </div>
            </div>
        </div>
    );

    return (
        <>
            {/* Desktop Sidebar */}
            <aside
                className={cn(
                    'hidden md:flex flex-col h-screen sticky top-0 transition-all duration-300',
                    isCollapsed ? 'w-16' : 'w-64'
                )}
            >
                {sidebarContent}
                {isCollapsed && (
                    <Button
                        variant="ghost"
                        size="icon"
                        className="absolute -right-3 top-20 h-6 w-6 rounded-full border bg-white shadow-md"
                        onClick={toggleCollapsed}
                    >
                        <ChevronRight className="h-4 w-4" />
                    </Button>
                )}
            </aside>

            {/* Mobile Drawer */}
            <Sheet open={isMobileOpen} onOpenChange={setIsMobileOpen}>
                <SheetContent side="left" className="p-0 w-64">
                    {sidebarContent}
                </SheetContent>
            </Sheet>

            {/* Mobile Trigger - Export untuk digunakan di header/layout */}
            <Button
                variant="ghost"
                size="icon"
                className="md:hidden fixed top-4 left-4 z-50"
                onClick={() => setIsMobileOpen(true)}
            >
                <svg
                    className="h-6 w-6"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M4 6h16M4 12h16M4 18h16"
                    />
                </svg>
            </Button>
        </>
    );
}
