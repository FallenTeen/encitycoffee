import { NavFooter } from './nav-footer';
import { NavMain } from './nav-main';
import { NavUser } from './nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from './ui/sidebar';
import { dashboard } from '../routes';
import admin from '../routes/admin';
import laporan from '../routes/laporan';
import transaksi from '../routes/transaksi';
import manager from '../routes/manager';
import produk from '../routes/produk';
import stok from '../routes/stok';
import supervisor from '../routes/supervisor';
import { usePage } from '@inertiajs/react';
import { type NavGroup } from '@/types';
import { Link } from '@inertiajs/react';
import {
    Shield,
    FileText,
    ShoppingCart,
    LayoutDashboard,
    Users,
    Building,
    TrendingUp,
    Package,
    Clock,
    Warehouse,
    FolderTree,
} from 'lucide-react';
import AppLogo from './app-logo';

function getMainNavItems(role?: string): NavGroup[] {
    const groups: NavGroup[] = [];
    if (!role) {
        groups.push({
            title: 'Menu',
            items: [
                { title: 'Dashboard', href: dashboard(), icon: LayoutDashboard },
            ],
        });
        return groups;
    }

    if (role === 'it_support') {
        groups.push({
            title: 'Admin',
            items: [
                { title: 'Dashboard', href: admin.dashboard(), icon: LayoutDashboard },
                { title: 'Users', href: admin.users.index(), icon: Users },
                { title: 'Cabang', href: admin.cabang.index(), icon: Building },
                { title: 'System Logs', href: admin.system.logs(), icon: FileText },
            ],
        });
        groups.push({
            title: 'Produk',
            items: [
                { title: 'Daftar Produk', href: produk.index(), icon: Package },
                { title: 'Kategori', href: produk.kategori.index(), icon: FolderTree },
            ],
        });
        groups.push({
            title: 'Stok',
            items: [
                { title: 'Daftar Stok', href: stok.index(), icon: Warehouse },
                { title: 'Stok Rendah', href: stok.rendah(), icon: Warehouse },
                { title: 'Mendekati Kadaluarsa', href: stok.kadaluarsa(), icon: Warehouse },
            ],
        });
        groups.push({
            title: 'Laporan',
            items: [
                { title: 'Shift', href: laporan.shift(), icon: Clock },
                { title: 'Harian', href: laporan.harian(), icon: FileText },
                { title: 'Penjualan Produk', href: laporan.penjualanProduk(), icon: FileText },
                { title: 'Stok', href: laporan.stok(), icon: Package },
                { title: 'Kinerja Kasir', href: laporan.kinerjaKasir(), icon: TrendingUp },
            ],
        });
        groups.push({
            title: 'Transaksi',
            items: [
                { title: 'Transaksi', href: transaksi.index(), icon: ShoppingCart },
            ],
        });
    } else if (role === 'manager') {
        groups.push({
            title: 'Manager',
            items: [
                { title: 'Dashboard', href: manager.dashboard(), icon: LayoutDashboard },
                { title: 'Supervisor', href: manager.supervisor.index(), icon: Users },
                { title: 'Laporan Cabang', href: manager.laporan.cabang(), icon: Building },
                { title: 'Performa Shift', href: manager.performa.shift(), icon: TrendingUp },
            ],
        });
        groups.push({
            title: 'Produk',
            items: [
                { title: 'Daftar Produk', href: produk.index(), icon: Package },
                { title: 'Kategori', href: produk.kategori.index(), icon: FolderTree },
            ],
        });
        groups.push({
            title: 'Stok',
            items: [
                { title: 'Daftar Stok', href: stok.index(), icon: Warehouse },
                { title: 'Stok Rendah', href: stok.rendah(), icon: Warehouse },
                { title: 'Mendekati Kadaluarsa', href: stok.kadaluarsa(), icon: Warehouse },
            ],
        });
        groups.push({
            title: 'Laporan',
            items: [
                { title: 'Shift', href: laporan.shift(), icon: Clock },
                { title: 'Harian', href: laporan.harian(), icon: FileText },
                { title: 'Penjualan Produk', href: laporan.penjualanProduk(), icon: FileText },
                { title: 'Stok', href: laporan.stok(), icon: Package },
                { title: 'Kinerja Kasir', href: laporan.kinerjaKasir(), icon: TrendingUp },
            ],
        });
        groups.push({
            title: 'Transaksi',
            items: [
                { title: 'Transaksi', href: transaksi.index(), icon: ShoppingCart },
            ],
        });
    } else if (role === 'supervisor') {
        groups.push({
            title: 'Supervisor',
            items: [
                { title: 'Dashboard', href: supervisor.dashboard(), icon: LayoutDashboard },
                { title: 'Monitoring Shift', href: supervisor.monitoring.shift(), icon: Clock },
                { title: 'Kasir', href: supervisor.kasir.index(), icon: Users },
            ],
        });
        groups.push({
            title: 'Stok',
            items: [
                { title: 'Daftar Stok', href: stok.index(), icon: Warehouse },
                { title: 'Stok Rendah', href: stok.rendah(), icon: Warehouse },
                { title: 'Mendekati Kadaluarsa', href: stok.kadaluarsa(), icon: Warehouse },
            ],
        });
        groups.push({
            title: 'Laporan',
            items: [
                { title: 'Shift', href: laporan.shift(), icon: Clock },
                { title: 'Harian', href: laporan.harian(), icon: FileText },
                { title: 'Penjualan Produk', href: laporan.penjualanProduk(), icon: FileText },
                { title: 'Stok', href: laporan.stok(), icon: Package },
                { title: 'Kinerja Kasir', href: laporan.kinerjaKasir(), icon: TrendingUp },
            ],
        });
        groups.push({
            title: 'Transaksi',
            items: [
                { title: 'Transaksi', href: transaksi.index(), icon: ShoppingCart },
            ],
        });
    } else if (role === 'kasir') {
        groups.push({
            title: 'Menu',
            items: [
                { title: 'Dashboard', href: dashboard(), icon: LayoutDashboard },
            ],
        });
    }

    return groups;
}


export function AppSidebar() {
    const { props } = usePage<any>();
    const role = props?.auth?.user?.role;
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()}>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain groups={getMainNavItems(role)} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
