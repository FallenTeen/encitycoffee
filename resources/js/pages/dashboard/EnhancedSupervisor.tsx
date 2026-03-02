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
    Users,
    Clock,
    Receipt,
    AlertTriangle,
    TrendingDown,
    Calendar,
    User,
} from 'lucide-react';
import { LineChart } from '@/components/charts/EnhancedChartComponents';
import { useRealtimeData } from '@/hooks/useDashboardData';
import { formatCurrency } from '@/utils/formatters';

interface EnhancedSupervisorProps {
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
            role: string;
        };
    };
    currentShifts: Array<{
        id: number;
        waktu_buka: string;
        waktu_tutup: string | null;
        status: string;
        cabang: { id: number; nama: string; alamat: string };
        user: { id: number; name: string };
    }>;
    staffStatus: Array<{
        id: number;
        name: string;
        role: string;
        aktif: boolean;
        cabang: { id: number; nama: string };
        shift: Array<{ id: number; status: string; waktu_buka: string }>;
    }>;
    hourlyPerformance: Array<{ hour: number; time: string; revenue: number; transactions: number }>;
    openBills: Array<{
        id: number;
        nomor_meja: string;
        total: number;
        status: string;
        created_at: string;
        cabang: { id: number; nama: string };
        user: { id: number; name: string };
    }>;
    recentTransactions: Array<{
        id: number;
        nomor_invoice: string;
        total: number;
        status: string;
        created_at: string;
        cabang: { id: number; nama: string };
        user: { id: number; name: string };
    }>;
    operationalMetrics: {
        active_staff: number;
        total_staff: number;
        open_bills_count: number;
        avg_bill_value: number;
    };
    performanceAlerts: {
        low_performance_branches: number;
        long_open_bills: number;
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

export default function EnhancedSupervisor({ auth, ...props }: EnhancedSupervisorProps) {
    const [refreshInterval, setRefreshInterval] = useState('60');

    const { data: realtimeData } = useRealtimeData('supervisor', 'shifts', Number(refreshInterval));

    const updatedOperationalMetrics = {
        ...props.operationalMetrics,
        active_staff: realtimeData?.active_shifts ?? props.operationalMetrics.active_staff,
    };

    const hourlyPerformanceData = {
        labels: props.hourlyPerformance.map((i) => i.time),
        datasets: [
            { label: 'Revenue', values: props.hourlyPerformance.map((i) => i.revenue), color: '#3b82f6', fill: true },
            { label: 'Transaksi', values: props.hourlyPerformance.map((i) => i.transactions), color: '#10b981', fill: false },
        ],
    };

    return (
        <AppLayout title="Supervisor Dashboard">
            <Head title="Supervisor Dashboard" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Supervisor Dashboard</h1>
                        <p className="text-sm text-muted-foreground mt-1">Selamat datang, {auth.user.name}</p>
                    </div>
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

                {/* Quick Actions - dihapus tautan yang tidak memiliki halaman */}

                {/* Tabs */}
                <Tabs defaultValue="overview">
                    <TabsList>
                        <TabsTrigger value="overview"><BarChart2 className="h-4 w-4 mr-2" />Overview</TabsTrigger>
                        <TabsTrigger value="shifts"><Clock className="h-4 w-4 mr-2" />Shift</TabsTrigger>
                        <TabsTrigger value="staff"><Users className="h-4 w-4 mr-2" />Staff</TabsTrigger>
                        <TabsTrigger value="bills"><Receipt className="h-4 w-4 mr-2" />Open Bills</TabsTrigger>
                        <TabsTrigger value="alerts"><AlertTriangle className="h-4 w-4 mr-2" />Peringatan</TabsTrigger>
                    </TabsList>

                    {/* Overview */}
                    <TabsContent value="overview" className="space-y-6 mt-6">
                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                            <KPICard title="Staff Aktif" value={updatedOperationalMetrics.active_staff} icon={Users} />
                            <KPICard title="Total Staff" value={updatedOperationalMetrics.total_staff} icon={User} />
                            <KPICard title="Open Bills" value={updatedOperationalMetrics.open_bills_count} icon={Receipt} />
                            <KPICard title="Rata-rata Nilai Bill" value={formatCurrency(updatedOperationalMetrics.avg_bill_value)} icon={DollarSign} />
                        </div>

                        <Card>
                            <CardHeader><CardTitle className="text-sm font-medium">Performa Per Jam (Hari Ini)</CardTitle></CardHeader>
                            <CardContent><LineChart data={hourlyPerformanceData} /></CardContent>
                        </Card>

                        <div className="grid gap-6 md:grid-cols-2">
                            <KPICard title="Cabang Performa Rendah" value={props.performanceAlerts.low_performance_branches} icon={TrendingDown} />
                            <KPICard title="Open Bills Lama" value={props.performanceAlerts.long_open_bills} icon={Clock} />
                        </div>
                    </TabsContent>

                    {/* Shifts */}
                    <TabsContent value="shifts" className="mt-6">
                        <Card>
                            <CardHeader><CardTitle className="text-sm font-medium">Shift Saat Ini</CardTitle></CardHeader>
                            <CardContent className="p-0">
                                <div className="divide-y">
                                    {props.currentShifts.map((shift) => (
                                        <div key={shift.id} className="px-6 py-4 flex items-center justify-between">
                                            <div>
                                                <p className="text-sm font-medium">{shift.cabang.nama}</p>
                                                <p className="text-xs text-muted-foreground">
                                                    {shift.user.name} &bull; {shift.waktu_buka}
                                                </p>
                                            </div>
                                            <div className="flex items-center gap-3">
                                                <Badge variant={shift.status === 'buka' ? 'default' : 'secondary'}>
                                                    {shift.status}
                                                </Badge>
                                                {shift.status === 'buka' && (
                                                    <span className="w-2 h-2 bg-green-500 rounded-full animate-pulse" />
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Staff */}
                    <TabsContent value="staff" className="mt-6">
                        <Card>
                            <CardHeader><CardTitle className="text-sm font-medium">Status Staff</CardTitle></CardHeader>
                            <CardContent className="p-0">
                                <div className="divide-y">
                                    {props.staffStatus.map((staff) => (
                                        <div key={staff.id} className="px-6 py-4 flex items-center justify-between">
                                            <div className="flex items-center gap-4">
                                                <div className="w-9 h-9 rounded-full bg-muted flex items-center justify-center">
                                                    {staff.role === 'kasir' ? (
                                                        <User className="h-4 w-4 text-muted-foreground" />
                                                    ) : (
                                                        <Users className="h-4 w-4 text-muted-foreground" />
                                                    )}
                                                </div>
                                                <div>
                                                    <p className="text-sm font-medium">{staff.name}</p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {staff.role} &bull; {staff.cabang.nama}
                                                    </p>
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-3">
                                                <Badge variant={staff.aktif ? 'default' : 'secondary'}>
                                                    {staff.aktif ? 'Aktif' : 'Nonaktif'}
                                                </Badge>
                                                {staff.shift.some((s) => s.status === 'buka') && (
                                                    <span className="w-2 h-2 bg-green-500 rounded-full animate-pulse" />
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Open Bills */}
                    <TabsContent value="bills" className="mt-6">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between">
                                <CardTitle className="text-sm font-medium">Open Bills</CardTitle>
                                <Badge variant="secondary">{props.openBills.length} open</Badge>
                            </CardHeader>
                            <CardContent className="p-0">
                                <div className="divide-y">
                                    {props.openBills.map((bill) => (
                                        <div key={bill.id} className="px-6 py-4 flex items-center justify-between">
                                            <div>
                                                <p className="text-sm font-medium">Meja {bill.nomor_meja}</p>
                                                <p className="text-xs text-muted-foreground">
                                                    {bill.cabang.nama} &bull; {bill.user.name}
                                                </p>
                                            </div>
                                            <div className="text-right">
                                                <p className="text-sm font-semibold">{formatCurrency(bill.total)}</p>
                                                <p className="text-xs text-muted-foreground">
                                                    {new Date(bill.created_at).toLocaleTimeString('id-ID')}
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Alerts */}
                    <TabsContent value="alerts" className="space-y-6 mt-6">
                        <div className="grid gap-6 md:grid-cols-2">
                            <KPICard title="Cabang Performa Rendah" value={props.performanceAlerts.low_performance_branches} icon={TrendingDown} />
                            <KPICard title="Open Bills Lama" value={props.performanceAlerts.long_open_bills} icon={Clock} />
                        </div>

                        <Card>
                            <CardHeader><CardTitle className="text-sm font-medium">Peringatan Performa</CardTitle></CardHeader>
                            <CardContent className="space-y-3">
                                {props.performanceAlerts.low_performance_branches > 0 && (
                                    <div className="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-lg">
                                        <TrendingDown className="h-4 w-4 text-red-600 mt-0.5 shrink-0" />
                                        <div>
                                            <p className="text-sm font-medium text-red-800">Performa Cabang Rendah</p>
                                            <p className="text-xs text-red-700 mt-1">
                                                {props.performanceAlerts.low_performance_branches} cabang berkinerja di bawah target hari ini.
                                            </p>
                                        </div>
                                    </div>
                                )}
                                {props.performanceAlerts.long_open_bills > 0 && (
                                    <div className="flex items-start gap-3 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                        <Clock className="h-4 w-4 text-yellow-600 mt-0.5 shrink-0" />
                                        <div>
                                            <p className="text-sm font-medium text-yellow-800">Open Bills Terlalu Lama</p>
                                            <p className="text-xs text-yellow-700 mt-1">
                                                {props.performanceAlerts.long_open_bills} bill telah terbuka lebih dari 2 jam.
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
