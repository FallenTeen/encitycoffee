import React, { useEffect, useMemo, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Tooltip, TooltipContent, TooltipTrigger, TooltipProvider } from '@/components/ui/tooltip';
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
    Building2,
    Info,
    CalendarClock,
    AlertCircle,
    Coffee,
} from 'lucide-react';
import { BarChart, LineChart, PieChart } from '@/components/charts/EnhancedChartComponents';
import { useRealtimeData } from '@/hooks/useDashboardData';
import { formatCurrency, formatNumber } from '@/utils/formatters';
import { useOutlet } from '@/contexts/OutletContext';
import { cn } from '@/lib/utils';

interface EnhancedManagerProps {
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
            role: string;
        };
    };
    filters: {
        range: string;
        tanggal_mulai: string;
        tanggal_selesai: string;
        cabang_id?: number | null;
    };
    kpis: {
        period_revenue: number;
        period_transactions: number;
        today_revenue: number;
        today_transactions: number;
        month_revenue: number;
        active_shifts: number;
        low_stock_items: number;
        expiring_products: number;
    };
    dailyPerformance: Array<{ date: string; day: string; revenue: number; transactions: number }>;
    discountMetrics: {
        tanggal_mulai: string;
        tanggal_selesai: string;
        gross_revenue: number;
        net_revenue: number;
        discount_amount: number;
        discount_share_percent: number;
        discounted_transactions: number;
        non_discounted_transactions: number;
    };
    discountComparisonDaily: Array<{
        date: string;
        day: string;
        discount_amount: number;
        discounted_transactions: number;
        non_discounted_transactions: number;
        gross_revenue: number;
        net_revenue: number;
    }>;
    discountByCategory: Array<{ category: string; discount_amount: number }>;
    discountByCustomerType: Array<{ customer_type: string; discount_amount: number; transaction_count: number }>;
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

// Basic KPI Card Component
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

// Enhanced KPI Card with Icon, Tooltip and Smooth Transitions
function EnhancedKPICard({
    title,
    value,
    icon: Icon,
    description,
    tooltipContent,
    showIconOnTop = false,
    className,
}: {
    title: string;
    value: string | number;
    icon: React.ElementType;
    description?: string;
    tooltipContent?: string;
    showIconOnTop?: boolean;
    className?: string;
}) {
    return (
        <Card className={cn("relative overflow-hidden", className)}>
            {showIconOnTop && (
                <div className="absolute top-3 right-3">
                    <TooltipProvider delayDuration={200}>
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <button className="p-1 rounded-full hover:bg-muted transition-colors">
                                    <Info className="h-4 w-4 text-muted-foreground" />
                                </button>
                            </TooltipTrigger>
                            {tooltipContent && (
                                <TooltipContent side="left" className="max-w-xs">
                                    <p className="text-sm">{tooltipContent}</p>
                                </TooltipContent>
                            )}
                        </Tooltip>
                    </TooltipProvider>
                </div>
            )}
            <CardHeader className={cn("flex flex-row items-center justify-between pb-2", showIconOnTop && "pr-10")}>
                <CardTitle className="text-sm font-medium text-muted-foreground">{title}</CardTitle>
                {!showIconOnTop && <Icon className="h-4 w-4 text-muted-foreground" />}
            </CardHeader>
            <CardContent>
                {showIconOnTop && (
                    <div className="flex items-center gap-2 mb-2">
                        <Icon className="h-5 w-5 text-primary" />
                    </div>
                )}
                <div 
                    className={cn(
                        "text-2xl font-semibold transition-all duration-300 ease-in-out",
                    )}
                    key={String(value)}
                >
                    {value}
                </div>
                {description && <p className="text-xs text-muted-foreground mt-1">{description}</p>}
            </CardContent>
        </Card>
    );
}

// Compact, view-only status indicator (icon + number + tooltip).
// Used for operational signals that don't need a full-size card (Shift Aktif,
// Stok Menipis, Produk Kadaluarsa). Alert-type indicators (isAlert=true) are
// only rendered by the caller when their value is > 0.
function StatusIndicator({
    icon: Icon,
    value,
    tooltip,
    colorClass = 'text-muted-foreground',
}: {
    icon: React.ElementType;
    value: number | string;
    tooltip: string;
    colorClass?: string;
}) {
    return (
        <TooltipProvider delayDuration={200}>
            <Tooltip>
                <TooltipTrigger asChild>
                    <div className={cn('flex items-center gap-1.5 cursor-default', colorClass)}>
                        <Icon className="h-4 w-4" />
                        <span className="text-sm font-medium">{value}</span>
                    </div>
                </TooltipTrigger>
                <TooltipContent side="bottom" className="max-w-xs">
                    <p className="text-sm">{tooltip}</p>
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    );
}

// Helper to determine if filter is "today only"
function isTodayFilter(range: string, tanggalMulai?: string, tanggalSelesai?: string): boolean {
    if (range === 'today' || range === 'hari_ini') return true;
    if (tanggalMulai && tanggalSelesai) {
        return tanggalMulai === tanggalSelesai;
    }
    return range === '1d';
}

// Format date for display
function formatDateLabel(date: string): string {
    const d = new Date(date);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

export default function EnhancedManager({ auth, ...props }: EnhancedManagerProps) {
    const [dateRange, setDateRange] = useState(props.filters?.range || '14d');
    const [customMulai, setCustomMulai] = useState(props.filters?.tanggal_mulai || '');
    const [customSelesai, setCustomSelesai] = useState(props.filters?.tanggal_selesai || '');
    const [refreshInterval, setRefreshInterval] = useState('60');
    const [lastUpdatedAt, setLastUpdatedAt] = useState<string>('');
    const [pulse, setPulse] = useState(false);
    
    // Get selected outlet from context
    const { selectedOutlet, outlets, switchOutlet } = useOutlet();
    const [localOutletId, setLocalOutletId] = useState<number | null>(selectedOutlet?.id ?? null);
    
    // Update local outlet when context changes
    useEffect(() => {
        if (selectedOutlet?.id) {
            setLocalOutletId(selectedOutlet.id);
        }
    }, [selectedOutlet]);
    
    // Reload data when outlet changes
    useEffect(() => {
        if (localOutletId !== null) {
            router.get('/dashboard/manager', {
                range: dateRange,
                tanggal_mulai: customMulai || undefined,
                tanggal_selesai: customSelesai || undefined,
                cabang_id: localOutletId,
            }, { preserveScroll: true, preserveState: true });
        }
    }, [localOutletId]);
    
    // Handle outlet selection
    const handleOutletChange = (outletId: number) => {
        setLocalOutletId(outletId);
        switchOutlet(outletId);
    };

    const { data: realtimeData } = useRealtimeData('manager', 'revenue', Number(refreshInterval));
    const { data: realtimeDiscount } = useRealtimeData('manager', 'discount', Number(refreshInterval));

    const updatedKpis = {
        ...props.kpis,
        period_revenue: realtimeData?.period_revenue ?? props.kpis.period_revenue ?? props.kpis.today_revenue,
        period_transactions: realtimeData?.period_transactions ?? props.kpis.period_transactions ?? props.kpis.today_transactions,
        active_shifts: realtimeData?.active_shifts ?? props.kpis.active_shifts,
    };

    useEffect(() => {
        if (Number(refreshInterval) === 0) return;
        const now = new Date();
        setLastUpdatedAt(now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }));
        setPulse(true);
        const t = setTimeout(() => setPulse(false), 1200);
        return () => clearTimeout(t);
    }, [realtimeData, realtimeDiscount, refreshInterval]);

    useEffect(() => {
        if (dateRange !== 'custom') return;
        setCustomMulai(props.filters?.tanggal_mulai || '');
        setCustomSelesai(props.filters?.tanggal_selesai || '');
    }, [dateRange, props.filters?.tanggal_mulai, props.filters?.tanggal_selesai]);

    const periodLabel = useMemo(() => {
        const start = props.filters?.tanggal_mulai;
        const end = props.filters?.tanggal_selesai;
        if (!start || !end) return '';
        return start === end ? start : `${start} – ${end}`;
    }, [props.filters?.tanggal_mulai, props.filters?.tanggal_selesai]);

    // Number of days actually covered by the selected period (falls back to 1
    // to avoid division by zero). Used to turn "Revenue Bulan Ini" -- which
    // stops being meaningful once the selected range spans multiple months --
    // into a per-day average that stays relevant at any range length.
    const periodDaysCount = props.dailyPerformance.length || 1;
    const avgDailyRevenue = updatedKpis.period_revenue / periodDaysCount;

    const dailyPerformanceData = {
        labels: props.dailyPerformance.map((i) => i.day),
        datasets: [
            { label: 'Revenue', values: props.dailyPerformance.map((i) => i.revenue), color: '#3b82f6', fill: true },
            { label: 'Transaksi', values: props.dailyPerformance.map((i) => i.transactions), color: '#10b981', fill: false },
        ],
    };

    const discountTrendData = {
        labels: props.discountComparisonDaily.map((i) => i.day),
        datasets: [
            { label: 'Pendapatan Aktual', values: props.discountComparisonDaily.map((i) => i.net_revenue), color: '#3b82f6', fill: true },
            { label: 'Seharusnya (Tanpa Diskon)', values: props.discountComparisonDaily.map((i) => i.gross_revenue), color: '#10b981', fill: false },
            { label: 'Diskon', values: props.discountComparisonDaily.map((i) => i.discount_amount), color: '#f59e0b', fill: false },
        ],
    };

    const discountStatusPie = {
        labels: ['Berdiskon', 'Tanpa Diskon'],
        values: [props.discountMetrics.discounted_transactions, props.discountMetrics.non_discounted_transactions],
        colors: ['#10b981', '#6b7280'],
    };

    const discountByCategoryPie = {
        labels: props.discountByCategory.map((i) => i.category),
        values: props.discountByCategory.map((i) => Number(i.discount_amount) || 0),
    };

    const discountByCustomerTypePie = {
        labels: props.discountByCustomerType.map((i) => i.customer_type),
        values: props.discountByCustomerType.map((i) => Number(i.discount_amount) || 0),
        colors: ['#3b82f6', '#8b5cf6', '#6b7280', '#f59e0b'],
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
                            Selamat datang, {auth.user.name}
                            {selectedOutlet && <span> &bull; Outlet: {selectedOutlet.nama}</span>}
                            {!selectedOutlet && <span> &bull; Mengelola {props.assignedBranches.length} cabang</span>}
                        </p>
                    </div>
                    <div className="flex items-center gap-3">
                        {/* Outlet Switcher - Only show if user has multiple outlets */}
                        {outlets.length > 1 && (
                            <div className="flex items-center gap-2">
                                <Building2 className="h-4 w-4 text-muted-foreground" />
                                <select
                                    value={localOutletId?.toString() ?? ''}
                                    onChange={(e) => {
                                        const value = e.target.value;
                                        if (value) {
                                            handleOutletChange(parseInt(value, 10));
                                        }
                                    }}
                                    className="h-9 px-3 pr-8 text-sm border rounded-md bg-white focus:outline-none focus:ring-2 focus:ring-primary"
                                >
                                    <option value="">Semua Outlet</option>
                                    {outlets.map((outlet) => (
                                        <option key={outlet.id} value={outlet.id.toString()}>
                                            {outlet.kode ? `${outlet.kode} - ` : ''}{outlet.nama}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        )}
                        <Select value={dateRange} onValueChange={(v) => {
                            setDateRange(v);
                            if (v !== 'custom') {
                                router.get('/dashboard/manager', { range: v, cabang_id: localOutletId }, { preserveScroll: true, preserveState: true, replace: true });
                            }
                        }}>
                            <SelectTrigger className="w-36">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="7d">7 hari terakhir</SelectItem>
                                <SelectItem value="14d">14 hari terakhir</SelectItem>
                                <SelectItem value="30d">30 hari terakhir</SelectItem>
                                <SelectItem value="90d">90 hari terakhir</SelectItem>
                                <SelectItem value="custom">Custom</SelectItem>
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

                <div className="flex flex-wrap items-center gap-2">
                    <Badge variant="secondary" className={pulse ? 'ring-2 ring-primary/40' : ''}>
                        {refreshInterval === '0' ? 'Refresh mati' : `Realtime aktif${lastUpdatedAt ? ` • ${lastUpdatedAt}` : ''}`}
                    </Badge>
                    <Badge variant="outline">Periode: {periodLabel}</Badge>
                </div>

                {/* Custom date inputs: only rendered in Custom mode, kept compact/inline
                    so they don't eat vertical space when not needed */}
                {dateRange === 'custom' && (
                    <div className="flex flex-wrap items-end gap-3">
                        <div className="space-y-1">
                            <div className="text-xs font-medium text-muted-foreground">Tanggal Mulai</div>
                            <Input type="date" value={customMulai} onChange={(e) => setCustomMulai(e.target.value)} className="h-9 w-44" />
                        </div>
                        <div className="space-y-1">
                            <div className="text-xs font-medium text-muted-foreground">Tanggal Selesai</div>
                            <Input type="date" value={customSelesai} onChange={(e) => setCustomSelesai(e.target.value)} className="h-9 w-44" />
                        </div>
                        <Button
                            size="sm"
                            onClick={() => {
                                router.get(
                                    '/dashboard/manager',
                                    { range: 'custom', tanggal_mulai: customMulai, tanggal_selesai: customSelesai },
                                    { preserveScroll: true, preserveState: true, replace: true },
                                );
                            }}
                        >
                            Terapkan
                        </Button>
                    </div>
                )}

                {/* Quick Actions + compact operational status icons.
                    Shift Aktif is always shown (it's a routine metric).
                    Stok Menipis / Produk Kadaluarsa are alerts: only shown when > 0,
                    so a healthy branch shows a clean row with no icon clutter. */}
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Button variant="outline" size="sm" asChild>
                        <Link href="/stok"><Package className="h-4 w-4 mr-2" />Kelola Stok</Link>
                    </Button>
                    <div className="flex items-center gap-4">
                        <StatusIndicator
                            icon={Clock}
                            value={updatedKpis.active_shifts}
                            tooltip={`Shift aktif pada periode ${periodLabel || 'yang dipilih'}: ${updatedKpis.active_shifts} shift sedang berstatus 'buka'.`}
                            colorClass="text-blue-600"
                        />
                        {updatedKpis.low_stock_items > 0 && (
                            <StatusIndicator
                                icon={Package}
                                value={updatedKpis.low_stock_items}
                                tooltip={`${updatedKpis.low_stock_items} item etalase stoknya di bawah batas minimum. Segera lakukan restock.`}
                                colorClass="text-amber-600"
                            />
                        )}
                        {updatedKpis.expiring_products > 0 && (
                            <StatusIndicator
                                icon={AlertTriangle}
                                value={updatedKpis.expiring_products}
                                tooltip={`${updatedKpis.expiring_products} batch produk akan kadaluarsa dalam 30 hari ke depan. Perlu rotasi stok.`}
                                colorClass="text-red-600"
                            />
                        )}
                    </div>
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
                        {/* Primary KPI Cards - Dynamic Labels.
                            "Revenue Bulan Ini" is replaced with a per-day average, which
                            stays meaningful whether the selected period is 1 day or 90 days
                            (a fixed "this month" figure wasn't relevant once the range
                            spans multiple months). */}
                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            <EnhancedKPICard 
                                title={isTodayFilter(dateRange, props.filters?.tanggal_mulai, props.filters?.tanggal_selesai) 
                                    ? "Revenue Hari Ini" 
                                    : `Revenue ${props.filters?.tanggal_mulai === props.filters?.tanggal_selesai ? formatDateLabel(props.filters?.tanggal_mulai) : `${formatDateLabel(props.filters?.tanggal_mulai)} - ${formatDateLabel(props.filters?.tanggal_selesai)}`}`
                                } 
                                value={formatCurrency(updatedKpis.period_revenue)} 
                                icon={DollarSign} 
                            />
                            <EnhancedKPICard
                                title="Rata-rata Revenue Harian"
                                value={formatCurrency(avgDailyRevenue)}
                                icon={TrendingUp}
                                showIconOnTop={true}
                                tooltipContent="Total revenue pada periode terpilih dibagi jumlah hari dalam periode tersebut. Lebih relevan dibanding 'Revenue Bulan Ini' saat periode yang dipilih melewati satu bulan."
                                description={`${periodDaysCount} hari dalam periode`}
                            />
                            <EnhancedKPICard 
                                title={isTodayFilter(dateRange, props.filters?.tanggal_mulai, props.filters?.tanggal_selesai) 
                                    ? "Transaksi Hari Ini" 
                                    : `Transaksi ${props.filters?.tanggal_mulai === props.filters?.tanggal_selesai ? formatDateLabel(props.filters?.tanggal_mulai) : `${formatDateLabel(props.filters?.tanggal_mulai)} - ${formatDateLabel(props.filters?.tanggal_selesai)}`}`
                                } 
                                value={formatNumber(updatedKpis.period_transactions)} 
                                icon={ShoppingCart} 
                            />
                        </div>

                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                            <KPICard title="Pendapatan Seharusnya" value={formatCurrency(props.discountMetrics.gross_revenue)} icon={TrendingUp} description={periodLabel} />
                            <KPICard title="Pendapatan Aktual" value={formatCurrency(props.discountMetrics.net_revenue)} icon={DollarSign} description={periodLabel} />
                            <KPICard title="Pengurangan Diskon" value={formatCurrency(props.discountMetrics.discount_amount)} icon={Wrench} description={`${props.discountMetrics.discount_share_percent}% dari pendapatan seharusnya`} />
                            <KPICard title="Diskon Hari Ini" value={formatCurrency(realtimeDiscount?.today_discount_amount ?? 0)} icon={Calendar} description={`Berdiskon: ${realtimeDiscount?.today_discounted_transactions ?? 0} transaksi`} />
                        </div>

                        <Card>
                            <CardHeader><CardTitle className="text-sm font-medium">Performa Harian</CardTitle></CardHeader>
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

                        <div className="grid gap-6 lg:grid-cols-2">
                            <Card>
                                <CardHeader><CardTitle className="text-sm font-medium">Tren Diskon & Pendapatan</CardTitle></CardHeader>
                                <CardContent><LineChart data={discountTrendData} /></CardContent>
                            </Card>
                            <Card>
                                <CardHeader><CardTitle className="text-sm font-medium">Transaksi Berdiskon vs Tidak</CardTitle></CardHeader>
                                <CardContent><PieChart data={discountStatusPie} /></CardContent>
                            </Card>
                        </div>

                        <div className="grid gap-6 lg:grid-cols-2">
                            <Card>
                                <CardHeader><CardTitle className="text-sm font-medium">Breakdown Diskon per Kategori</CardTitle></CardHeader>
                                <CardContent><PieChart data={discountByCategoryPie} /></CardContent>
                            </Card>
                            <Card>
                                <CardHeader><CardTitle className="text-sm font-medium">Breakdown Diskon per Jenis Pelanggan</CardTitle></CardHeader>
                                <CardContent><PieChart data={discountByCustomerTypePie} /></CardContent>
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
                            <EnhancedKPICard 
                                title="Cabang Stok Rendah" 
                                value={props.operationalAlerts.low_stock_branches} 
                                icon={Package} 
                                showIconOnTop={true}
                                tooltipContent="Jumlah cabang yang memiliki minimal satu item stok menipis (stok di bawah batas minimum yang ditetapkan)."
                                description="Cabang dengan stok menipis"
                            />
                            <EnhancedKPICard 
                                title="Maintenance Terlambat" 
                                value={props.operationalAlerts.overdue_maintenance} 
                                icon={Wrench} 
                                showIconOnTop={true}
                                tooltipContent="Jumlah tugas maintenance peralatan atau fasilitas yang telah melewati jadwal perawatan yang ditetapkan."
                                description="Tugas maintenance overdue"
                            />
                            <EnhancedKPICard 
                                title="Staff Tidak Hadir" 
                                value={props.operationalAlerts.staff_absence} 
                                icon={Users} 
                                showIconOnTop={true}
                                tooltipContent="Jumlah staff yang tercatat tidak hadir atau belum melakukan shift pada hari ini."
                                description="Staff absent hari ini"
                            />
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