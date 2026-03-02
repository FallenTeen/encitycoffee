import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
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
    Users,
    TrendingUp,
    ShoppingCart,
    Clock,
    Package,
    AlertTriangle,
    Calendar,
    Settings,
    Wrench,
} from 'lucide-react';
import { BarChart, LineChart, PieChart } from '@/components/charts/EnhancedChartComponents';
import { useRealtimeData } from '@/hooks/useDashboardData';
import { formatCurrency, formatNumber } from '@/utils/formatters';

interface EnhancedManagerProps {
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
            role: string;
        };
    };
    kpis: {
        today_revenue: number;
        month_revenue: number;
        today_transactions: number;
        active_shifts: number;
        low_stock_items: number;
        expiring_products: number;
    };
    dailyPerformance: Array<{ date: string; day: string; revenue: number; transactions: number }>;
    branchComparison: Array<{ id: number; nama: string; total_revenue: number; transaction_count: number; avg_transaction: number }>;
    categoryPerformance: Array<{ category: string; total_revenue: number; total_quantity: number }>;
    staffPerformance: Array<{ id: number; name: string; total_revenue: number; transaction_count: number; avg_transaction: number }>;
    operationalAlerts: {
        low_stock_branches: number;
        overdue_maintenance: number;
        staff_absence: number;
    };
    assignedBranches: Array<{ id: number; nama: string; alamat: string; aktif: boolean }>;
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

export default function EnhancedManager({ auth, ...props }: EnhancedManagerProps) {
    const [dateRange, setDateRange] = useState('14d');
    const [refreshInterval, setRefreshInterval] = useState('60');

    const { data: realtimeData } = useRealtimeData('manager', 'revenue', Number(refreshInterval));

    const updatedKpis = {
        ...props.kpis,
        today_revenue: realtimeData?.today_revenue ?? props.kpis.today_revenue,
        active_shifts: realtimeData?.active_shifts ?? props.kpis.active_shifts,
    };

    const dailyPerformanceData = {
        labels: props.dailyPerformance.map((i) => i.day),
        datasets: [
            { label: 'Revenue', values: props.dailyPerformance.map((i) => i.revenue), color: '#3b82f6', fill: true },
            { label: 'Transaksi', values: props.dailyPerformance.map((i) => i.transactions), color: '#10b981', fill: false },
        ],
    };

    const branchComparisonData = {
        labels: props.branchComparison.map((b) => b.nama),
        values: props.branchComparison.map((b) => b.total_revenue),
        colors: ['#3b82f6', '#1d4ed8', '#1e40af', '#1e3a8a', '#172554'],
    };

    const categoryPerformanceData = {
        labels: props.categoryPerformance.map((i) => i.category),
        values: props.categoryPerformance.map((i) => i.total_revenue),
        colors: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
    };

    const staffPerformanceData = {
        labels: props.staffPerformance.map((s) => s.name),
        values: props.staffPerformance.map((s) => s.total_revenue),
        colors: ['#3b82f6', '#1d4ed8', '#1e40af', '#1e3a8a', '#172554'],
    };

    return (
        <AppLayout title="Manager Dashboard">
            <Head title="Manager Dashboard" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Manager Dashboard</h1>
                        <p className="text-sm text-muted-foreground mt-1">
                            Selamat datang, {auth.user.name} &bull; Mengelola {props.assignedBranches.length} cabang
                        </p>
                    </div>
                    <div className="flex items-center gap-3">
                        <Select value={dateRange} onValueChange={setDateRange}>
                            <SelectTrigger className="w-36">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="7d">7 hari terakhir</SelectItem>
                                <SelectItem value="14d">14 hari terakhir</SelectItem>
                                <SelectItem value="30d">30 hari terakhir</SelectItem>
                                <SelectItem value="90d">90 hari terakhir</SelectItem>
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

                {/* Quick Actions - hanya tautan yang valid */}
                <div className="flex flex-wrap gap-2">
                    <Button variant="outline" size="sm" asChild>
                        <Link href="/stok"><Package className="h-4 w-4 mr-2" />Kelola Stok</Link>
                    </Button>
                </div>

                {/* Tabs */}
                <Tabs defaultValue="overview">
                    <TabsList>
                        <TabsTrigger value="overview"><BarChart2 className="h-4 w-4 mr-2" />Overview</TabsTrigger>
                        <TabsTrigger value="performance"><TrendingUp className="h-4 w-4 mr-2" />Performa</TabsTrigger>
                        <TabsTrigger value="branches"><Store className="h-4 w-4 mr-2" />Cabang</TabsTrigger>
                        <TabsTrigger value="staff"><Users className="h-4 w-4 mr-2" />Staff</TabsTrigger>
                        <TabsTrigger value="alerts"><AlertTriangle className="h-4 w-4 mr-2" />Peringatan</TabsTrigger>
                    </TabsList>

                    {/* Overview */}
                    <TabsContent value="overview" className="space-y-6 mt-6">
                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            <KPICard title="Revenue Hari Ini" value={formatCurrency(updatedKpis.today_revenue)} icon={DollarSign} />
                            <KPICard title="Revenue Bulan Ini" value={formatCurrency(updatedKpis.month_revenue)} icon={TrendingUp} />
                            <KPICard title="Transaksi Hari Ini" value={formatNumber(updatedKpis.today_transactions)} icon={ShoppingCart} />
                            <KPICard title="Shift Aktif" value={updatedKpis.active_shifts} icon={Clock} />
                            <KPICard title="Stok Menipis" value={updatedKpis.low_stock_items} icon={Package} />
                            <KPICard title="Produk Kadaluwarsa" value={updatedKpis.expiring_products} icon={AlertTriangle} />
                        </div>

                        <Card>
                            <CardHeader><CardTitle className="text-sm font-medium">Performa Harian (14 Hari)</CardTitle></CardHeader>
                            <CardContent><LineChart data={dailyPerformanceData} /></CardContent>
                        </Card>

                        <div className="grid gap-6 lg:grid-cols-2">
                            <Card>
                                <CardHeader><CardTitle className="text-sm font-medium">Perbandingan Revenue Cabang</CardTitle></CardHeader>
                                <CardContent><BarChart data={branchComparisonData} /></CardContent>
                            </Card>
                            <Card>
                                <CardHeader><CardTitle className="text-sm font-medium">Performa Kategori</CardTitle></CardHeader>
                                <CardContent><PieChart data={categoryPerformanceData} /></CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    {/* Performance */}
                    <TabsContent value="performance" className="space-y-6 mt-6">
                        <Card>
                            <CardHeader><CardTitle className="text-sm font-medium">Tren Performa Harian</CardTitle></CardHeader>
                            <CardContent><LineChart data={dailyPerformanceData} /></CardContent>
                        </Card>
                        <div className="grid gap-6 lg:grid-cols-2">
                            <Card>
                                <CardHeader><CardTitle className="text-sm font-medium">Performa Cabang</CardTitle></CardHeader>
                                <CardContent><BarChart data={branchComparisonData} /></CardContent>
                            </Card>
                            <Card>
                                <CardHeader><CardTitle className="text-sm font-medium">Performa Staff</CardTitle></CardHeader>
                                <CardContent><BarChart data={staffPerformanceData} /></CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    {/* Branches */}
                    <TabsContent value="branches" className="space-y-6 mt-6">
                        <Card>
                            <CardHeader><CardTitle className="text-sm font-medium">Detail Performa Cabang</CardTitle></CardHeader>
                            <CardContent><BarChart data={branchComparisonData} /></CardContent>
                        </Card>

                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            {props.assignedBranches.map((branch) => (
                                <Card key={branch.id}>
                                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                                        <CardTitle className="text-sm font-medium">{branch.nama}</CardTitle>
                                        <Badge variant={branch.aktif ? 'default' : 'secondary'}>
                                            {branch.aktif ? 'Aktif' : 'Nonaktif'}
                                        </Badge>
                                    </CardHeader>
                                    <CardContent>
                                        <p className="text-xs text-muted-foreground">{branch.alamat}</p>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </TabsContent>

                    {/* Staff */}
                    <TabsContent value="staff" className="space-y-6 mt-6">
                        <Card>
                            <CardHeader><CardTitle className="text-sm font-medium">Performa Staff</CardTitle></CardHeader>
                            <CardContent><BarChart data={staffPerformanceData} /></CardContent>
                        </Card>

                        <Card>
                            <CardHeader><CardTitle className="text-sm font-medium">Detail Performa Staff</CardTitle></CardHeader>
                            <CardContent className="p-0">
                                <div className="divide-y">
                                    {props.staffPerformance.map((staff) => (
                                        <div key={staff.id} className="px-6 py-4 flex items-center justify-between">
                                            <div>
                                                <p className="text-sm font-medium">{staff.name}</p>
                                                <p className="text-xs text-muted-foreground">{staff.transaction_count} transaksi</p>
                                            </div>
                                            <div className="text-right">
                                                <p className="text-sm font-semibold">{formatCurrency(staff.total_revenue)}</p>
                                                <p className="text-xs text-muted-foreground">Rata-rata: {formatCurrency(staff.avg_transaction)}</p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Alerts */}
                    <TabsContent value="alerts" className="space-y-6 mt-6">
                        <div className="grid gap-6 md:grid-cols-3">
                            <KPICard title="Cabang Stok Rendah" value={props.operationalAlerts.low_stock_branches} icon={Package} />
                            <KPICard title="Maintenance Terlambat" value={props.operationalAlerts.overdue_maintenance} icon={Wrench} />
                            <KPICard title="Staff Tidak Hadir" value={props.operationalAlerts.staff_absence} icon={Users} />
                        </div>

                        <Card>
                            <CardHeader><CardTitle className="text-sm font-medium">Peringatan Operasional</CardTitle></CardHeader>
                            <CardContent className="space-y-3">
                                {props.operationalAlerts.low_stock_branches > 0 && (
                                    <div className="flex items-start gap-3 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                        <Package className="h-4 w-4 text-yellow-600 mt-0.5 shrink-0" />
                                        <div>
                                            <p className="text-sm font-medium text-yellow-800">Peringatan Stok Rendah</p>
                                            <p className="text-xs text-yellow-700 mt-1">
                                                {props.operationalAlerts.low_stock_branches} cabang memiliki item dengan stok menipis.
                                            </p>
                                        </div>
                                    </div>
                                )}
                                {props.operationalAlerts.overdue_maintenance > 0 && (
                                    <div className="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-lg">
                                        <Wrench className="h-4 w-4 text-red-600 mt-0.5 shrink-0" />
                                        <div>
                                            <p className="text-sm font-medium text-red-800">Peringatan Maintenance</p>
                                            <p className="text-xs text-red-700 mt-1">
                                                {props.operationalAlerts.overdue_maintenance} tugas maintenance telah melewati jadwal.
                                            </p>
                                        </div>
                                    </div>
                                )}
                                {props.operationalAlerts.staff_absence > 0 && (
                                    <div className="flex items-start gap-3 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                        <Users className="h-4 w-4 text-blue-600 mt-0.5 shrink-0" />
                                        <div>
                                            <p className="text-sm font-medium text-blue-800">Peringatan Staff</p>
                                            <p className="text-xs text-blue-700 mt-1">
                                                {props.operationalAlerts.staff_absence} staff saat ini tidak hadir.
                                            </p>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
