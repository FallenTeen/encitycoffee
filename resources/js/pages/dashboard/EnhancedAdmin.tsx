import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    BarChart2,
    DollarSign,
    Store,
    Package,
    Users,
    Settings,
    TrendingUp,
    Clock,
    Hash,
    Zap,
    AlertTriangle,
} from 'lucide-react';
import { BarChart, LineChart, PieChart, DoughnutChart } from '@/components/charts/EnhancedChartComponents';
import { useRealtimeData } from '@/hooks/useDashboardData';
import { formatCurrency } from '@/utils/formatters';
import { Link } from '@inertiajs/react';
// import route from 'ziggy-js';

interface EnhancedAdminProps {
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
            role: string;
        };
    };
    kpis: {
        total_revenue: number;
        today_revenue: number;
        month_revenue: number;
        total_branches: number;
        active_branches: number;
        total_users: number;
        active_shifts: number;
        today_transactions: number;
        open_bills: number;
    };
    revenueTrends: Array<{ date: string; day: string; revenue: number }>;
    topBranches: Array<{ id: number; nama: string; total_revenue: number; transaction_count: number }>;
    transactionStatus: Array<{ status: string; count: number }>;
    paymentMethods: Array<{ metode_pembayaran: string; total: number; count: number }>;
    topProducts: Array<{ nama: string; total_sold: number; total_revenue: number }>;
    recentActivity: Array<{
        id: number;
        nomor_invoice: string;
        total: number;
        status: string;
        created_at: string;
        cabang: { nama: string };
        user: { name: string };
    }>;
    systemHealth: {
        low_stock_items: number;
        expiring_products: number;
        active_users: number;
        system_uptime: string;
    };
}

function KPICard({
    title,
    value,
    icon: Icon,
    description,
}: {
    title: string;
    value: string | number;
    icon: React.ElementType;
    description?: string;
}) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-sm font-medium text-muted-foreground">{title}</CardTitle>
                <Icon className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div className="text-2xl font-semibold">{value}</div>
                {description && <p className="text-xs text-muted-foreground mt-1">{description}</p>}
            </CardContent>
        </Card>
    );
}

export default function EnhancedAdmin({ auth, ...props }: EnhancedAdminProps) {
    const [dateRange, setDateRange] = useState('30d');
    const [refreshInterval, setRefreshInterval] = useState('60');

    const { data: realtimeData } = useRealtimeData('admin', 'general', Number(refreshInterval));

    const updatedKpis = {
        ...props.kpis,
        today_revenue: realtimeData?.today_revenue ?? props.kpis.today_revenue,
        active_shifts: realtimeData?.active_shifts ?? props.kpis.active_shifts,
    };

    const revenueTrendsData = {
        labels: props.revenueTrends.map((item) => item.day),
        datasets: [{ label: 'Revenue', values: props.revenueTrends.map((item) => item.revenue), color: '#3b82f6', fill: true }],
    };

    const topBranchesData = {
        labels: props.topBranches.map((b) => b.nama),
        values: props.topBranches.map((b) => b.total_revenue),
        colors: ['#3b82f6', '#1d4ed8', '#1e40af', '#1e3a8a', '#172554'],
    };

    const transactionStatusData = {
        labels: props.transactionStatus.map((i) => i.status),
        values: props.transactionStatus.map((i) => i.count),
        colors: ['#10b981', '#f59e0b', '#ef4444', '#6b7280'],
    };

    const paymentMethodsData = {
        labels: props.paymentMethods.map((i) => i.metode_pembayaran),
        values: props.paymentMethods.map((i) => i.total),
        colors: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
    };

    const topProductsData = {
        labels: props.topProducts.map((p) => p.nama),
        values: props.topProducts.map((p) => p.total_sold),
        colors: ['#3b82f6', '#1d4ed8', '#1e40af', '#1e3a8a', '#172554'],
    };

    return (
        <AppLayout title="Admin Dashboard">
            <Head title="Admin Dashboard" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Admin Dashboard</h1>
                        <p className="text-sm text-muted-foreground mt-1">Selamat datang, {auth.user.name}</p>
                    </div>
                    <div className="flex items-center gap-3">
                        <Select value={dateRange} onValueChange={setDateRange}>
                            <SelectTrigger className="w-36">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="7d">7 hari terakhir</SelectItem>
                                <SelectItem value="30d">30 hari terakhir</SelectItem>
                                <SelectItem value="90d">90 hari terakhir</SelectItem>
                                <SelectItem value="1y">1 tahun terakhir</SelectItem>
                            </SelectContent>
                        </Select>
                        <Select value={refreshInterval} onValueChange={setRefreshInterval}>
                            <SelectTrigger className="w-36">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="0">Tanpa refresh</SelectItem>
                                <SelectItem value="30">30 detik</SelectItem>
                                <SelectItem value="60">1 menit</SelectItem>
                                <SelectItem value="300">5 menit</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                {/* Quick Actions */}
                <div className="flex flex-wrap gap-2">
                    <Button variant="outline" size="sm" asChild>
                        <Link href="/admin/cabang/create"><Store className="h-4 w-4 mr-2" />Tambah Cabang</Link>
                    </Button>
                    <Button variant="outline" size="sm" asChild>
                        <Link href="/admin/users/create"><Users className="h-4 w-4 mr-2" />Tambah Pengguna</Link>
                    </Button>
                    <Button variant="outline" size="sm" asChild>
                        <Link href="/admin/laporan"><BarChart2 className="h-4 w-4 mr-2" />Lihat Laporan</Link>
                    </Button>
                </div>

                {/* Tabs */}
                <Tabs defaultValue="overview">
                    <TabsList>
                        <TabsTrigger value="overview"><BarChart2 className="h-4 w-4 mr-2" />Overview</TabsTrigger>
                        <TabsTrigger value="revenue"><DollarSign className="h-4 w-4 mr-2" />Revenue</TabsTrigger>
                        <TabsTrigger value="branches"><Store className="h-4 w-4 mr-2" />Cabang</TabsTrigger>
                        <TabsTrigger value="products"><Package className="h-4 w-4 mr-2" />Produk</TabsTrigger>
                        <TabsTrigger value="system"><Settings className="h-4 w-4 mr-2" />Sistem</TabsTrigger>
                    </TabsList>

                    {/* Overview */}
                    <TabsContent value="overview" className="space-y-6 mt-6">
                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                            <KPICard title="Total Revenue" value={formatCurrency(updatedKpis.total_revenue)} icon={DollarSign} />
                            <KPICard title="Revenue Hari Ini" value={formatCurrency(updatedKpis.today_revenue)} icon={TrendingUp} />
                            <KPICard
                                title="Cabang Aktif"
                                value={`${updatedKpis.active_branches}/${updatedKpis.total_branches}`}
                                icon={Store}
                            />
                            <KPICard title="Shift Aktif" value={updatedKpis.active_shifts} icon={Clock} />
                        </div>

                        <div className="grid gap-6 lg:grid-cols-2">
                            <Card>
                                <CardHeader><CardTitle className="text-sm font-medium">Tren Revenue (30 Hari)</CardTitle></CardHeader>
                                <CardContent><LineChart data={revenueTrendsData} /></CardContent>
                            </Card>
                            <Card>
                                <CardHeader><CardTitle className="text-sm font-medium">Cabang Terbaik</CardTitle></CardHeader>
                                <CardContent><BarChart data={topBranchesData} /></CardContent>
                            </Card>
                        </div>

                        <div className="grid gap-6 lg:grid-cols-2">
                            <Card>
                                <CardHeader><CardTitle className="text-sm font-medium">Distribusi Status Transaksi</CardTitle></CardHeader>
                                <CardContent><DoughnutChart data={transactionStatusData} /></CardContent>
                            </Card>
                            <Card>
                                <CardHeader><CardTitle className="text-sm font-medium">Metode Pembayaran</CardTitle></CardHeader>
                                <CardContent><PieChart data={paymentMethodsData} /></CardContent>
                            </Card>
                        </div>

                        {/* Recent Activity */}
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between">
                                <CardTitle className="text-sm font-medium">Aktivitas Terbaru</CardTitle>
                                <Button variant="ghost" size="sm" asChild>
                                    <Link href="/transaksi">Lihat Semua</Link>
                                </Button>
                            </CardHeader>
                            <CardContent className="p-0">
                                <div className="divide-y">
                                    {props.recentActivity.map((activity) => (
                                        <div key={activity.id} className="px-6 py-4 flex items-center justify-between">
                                            <div className="flex items-center gap-4">
                                                <div className="w-9 h-9 bg-muted rounded-full flex items-center justify-center">
                                                    <Hash className="h-4 w-4 text-muted-foreground" />
                                                </div>
                                                <div>
                                                    <p className="text-sm font-medium">{activity.nomor_invoice}</p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {activity.cabang.nama} &bull; {activity.user.name}
                                                    </p>
                                                </div>
                                            </div>
                                            <div className="text-right">
                                                <p className="text-sm font-semibold">{formatCurrency(activity.total)}</p>
                                                <Badge variant="outline" className="text-xs">{activity.status}</Badge>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Revenue */}
                    <TabsContent value="revenue" className="space-y-6 mt-6">
                        <div className="grid gap-6 lg:grid-cols-2">
                            <Card>
                                <CardHeader><CardTitle className="text-sm font-medium">Tren Revenue</CardTitle></CardHeader>
                                <CardContent><LineChart data={revenueTrendsData} /></CardContent>
                            </Card>
                            <Card>
                                <CardHeader><CardTitle className="text-sm font-medium">Revenue per Cabang</CardTitle></CardHeader>
                                <CardContent><BarChart data={topBranchesData} /></CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    {/* Branches */}
                    <TabsContent value="branches" className="mt-6">
                        <Card>
                            <CardHeader><CardTitle className="text-sm font-medium">Performa Cabang</CardTitle></CardHeader>
                            <CardContent><BarChart data={topBranchesData} /></CardContent>
                        </Card>
                    </TabsContent>

                    {/* Products */}
                    <TabsContent value="products" className="mt-6">
                        <Card>
                            <CardHeader><CardTitle className="text-sm font-medium">Produk Terlaris</CardTitle></CardHeader>
                            <CardContent><BarChart data={topProductsData} /></CardContent>
                        </Card>
                    </TabsContent>

                    {/* System */}
                    <TabsContent value="system" className="space-y-6 mt-6">
                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                            <KPICard title="Uptime Sistem" value={props.systemHealth.system_uptime} icon={Zap} />
                            <KPICard title="Stok Menipis" value={props.systemHealth.low_stock_items} icon={Package} />
                            <KPICard title="Produk Kadaluwarsa" value={props.systemHealth.expiring_products} icon={AlertTriangle} />
                            <KPICard title="Pengguna Aktif" value={props.systemHealth.active_users} icon={Users} />
                        </div>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
