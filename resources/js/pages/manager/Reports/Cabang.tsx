import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tooltip, TooltipContent, TooltipTrigger, TooltipProvider } from '@/components/ui/tooltip';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    TrendingUp,
    TrendingDown,
    Package,
    AlertTriangle,
    Clock,
    DollarSign,
    ShoppingCart,
    FileText,
    FileSpreadsheet,
    Info,
    Store,
} from 'lucide-react';
import { formatCurrency, formatNumber } from '@/utils/formatters';
import { useState } from 'react';

interface BranchData {
    id: number;
    nama: string;
    kode: string;
    aktif: boolean;
    total_revenue: number;
    total_discount: number;
    transaction_count: number;
    avg_transaction: number;
    growth_percent: number;
    previous_revenue: number;
    top_products: Array<{ nama: string; qty: number; revenue: number }>;
    top_categories: Array<{ nama: string; revenue: number }>;
    daily_data: Array<{ date: string; revenue: number; transactions: number }>;
    total_shifts: number;
    avg_revenue_per_shift: number;
    low_stock_count: number;
    expiring_count: number;
}

interface Props {
    cabangs: BranchData[];
    filters: {
        range: string;
        tanggal_mulai: string;
        tanggal_selesai: string;
        cabang_id?: number | null;
    };
    metrics: {
        total_revenue: number;
        total_transactions: number;
        total_discount: number;
        avg_transaction: number;
    };
    branchComparison: Array<{
        id: number;
        nama: string;
        total_revenue: number;
        transaction_count: number;
        avg_transaction: number;
    }>;
    assignedBranches: Array<{ id: number; nama: string; kode: string; aktif: boolean }>;
    error?: string;
}

export default function ManagerCabangReport({ cabangs, filters, metrics, branchComparison, assignedBranches, error }: Props) {
    const [dateRange, setDateRange] = useState(filters?.range || '30d');
    const [customMulai, setCustomMulai] = useState(filters?.tanggal_mulai || '');
    const [customSelesai, setCustomSelesai] = useState(filters?.tanggal_selesai || '');
    const [selectedBranchId, setSelectedBranchId] = useState<string>(filters?.cabang_id ? String(filters.cabang_id) : 'all');
    const [expandedBranch, setExpandedBranch] = useState<number | null>(null);

    const getPeriodLabel = () => {
        if (filters?.tanggal_mulai && filters?.tanggal_selesai) {
            const start = new Date(filters.tanggal_mulai);
            const end = new Date(filters.tanggal_selesai);
            const formatDate = (d: Date) => d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
            return `${formatDate(start)} - ${formatDate(end)}`;
        }
        return '30 hari terakhir';
    };

    const handleFilterChange = (range: string) => {
        setDateRange(range);
        if (range !== 'custom') {
            router.get('/manager/laporan-cabang', {
                range,
                cabang_id: selectedBranchId !== 'all' ? selectedBranchId : undefined,
            }, {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            });
        }
    };

    const handleCustomFilter = () => {
        if (customMulai && customSelesai) {
            router.get('/manager/laporan-cabang', {
                range: 'custom',
                tanggal_mulai: customMulai,
                tanggal_selesai: customSelesai,
                cabang_id: selectedBranchId !== 'all' ? selectedBranchId : undefined,
            }, {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            });
        }
    };

    const handleBranchChange = (branchId: string) => {
        setSelectedBranchId(branchId);
        router.get('/manager/laporan-cabang', {
            range: dateRange,
            tanggal_mulai: dateRange === 'custom' ? customMulai : undefined,
            tanggal_selesai: dateRange === 'custom' ? customSelesai : undefined,
            cabang_id: branchId !== 'all' ? branchId : undefined,
        }, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    if (error) {
        return (
            <AppLayout breadcrumbs={[{ title: 'Laporan Cabang', href: '/manager/laporan-cabang' }]}>
                <Head title="Laporan Cabang" />
                <div className="flex items-center justify-center min-h-[400px]">
                    <Card className="w-full max-w-md">
                        <CardContent className="pt-6">
                            <div className="text-center space-y-4">
                                <AlertTriangle className="h-12 w-12 text-yellow-500 mx-auto" />
                                <h2 className="text-lg font-semibold">Informasi</h2>
                                <p className="text-sm text-muted-foreground">{error}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={[{ title: 'Laporan Cabang', href: '/manager/laporan-cabang' }]}>
            <Head title="Laporan Cabang" />
            <TooltipProvider>
                <div className="space-y-6">
                    {/* Header with filters */}
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 className="text-xl font-semibold">Laporan Performa Cabang</h1>
                            <p className="text-sm text-muted-foreground">
                                {getPeriodLabel()} &bull; {cabangs?.length || 0} cabang
                            </p>
                        </div>
                        <div className="flex flex-wrap items-center gap-2">
                            {/* Branch filter */}
                            {assignedBranches && assignedBranches.length > 1 && (
                                <div className="flex items-center gap-2">
                                    <Store className="h-4 w-4 text-muted-foreground" />
                                    <select
                                        value={selectedBranchId}
                                        onChange={(e) => handleBranchChange(e.target.value)}
                                        className="h-9 px-3 pr-8 text-sm border rounded-md bg-white focus:outline-none focus:ring-2 focus:ring-primary"
                                    >
                                        <option value="all">Semua Cabang</option>
                                        {assignedBranches.map((branch) => (
                                            <option key={branch.id} value={String(branch.id)}>
                                                {branch.kode ? `${branch.kode} - ` : ''}{branch.nama}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            )}

                            {/* Date range filter */}
                            <Select value={dateRange} onValueChange={handleFilterChange}>
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

                            {dateRange === 'custom' && (
                                <>
                                    <Input
                                        type="date"
                                        value={customMulai}
                                        onChange={(e) => setCustomMulai(e.target.value)}
                                        className="w-36"
                                    />
                                    <span className="text-muted-foreground">-</span>
                                    <Input
                                        type="date"
                                        value={customSelesai}
                                        onChange={(e) => setCustomSelesai(e.target.value)}
                                        className="w-36"
                                    />
                                    <Button size="sm" onClick={handleCustomFilter}>Terapkan</Button>
                                </>
                            )}

                            {/* Export buttons */}
                            <Button variant="outline" size="sm" asChild>
                                <Link href={`/laporan/export/pdf?type=cabang&range=${dateRange}&cabang_id=${selectedBranchId !== 'all' ? selectedBranchId : ''}`}>
                                    <FileText className="h-4 w-4 mr-2" />
                                    PDF
                                </Link>
                            </Button>
                            <Button variant="outline" size="sm" asChild>
                                <Link href={`/laporan/export/excel?type=cabang&range=${dateRange}&cabang_id=${selectedBranchId !== 'all' ? selectedBranchId : ''}`}>
                                    <FileSpreadsheet className="h-4 w-4 mr-2" />
                                    Excel
                                </Link>
                            </Button>
                        </div>
                    </div>

                    {/* Summary Metrics */}
                    <div className="grid gap-4 md:grid-cols-4">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">Total Revenue</CardTitle>
                                <DollarSign className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-semibold">{formatCurrency(metrics?.total_revenue || 0)}</div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">Total Transaksi</CardTitle>
                                <ShoppingCart className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-semibold">{formatNumber(metrics?.total_transactions || 0)}</div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">Rata-rata Transaksi</CardTitle>
                                <TrendingUp className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-semibold">{formatCurrency(metrics?.avg_transaction || 0)}</div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">Total Diskon</CardTitle>
                                <TrendingDown className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-semibold">{formatCurrency(metrics?.total_discount || 0)}</div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Branch Comparison Table */}
                    {(!cabangs || cabangs.length === 0) ? (
                        <Card>
                            <CardContent className="pt-6">
                                <div className="text-center text-muted-foreground">
                                    Belum ada data cabang untuk periode yang dipilih.
                                </div>
                            </CardContent>
                        </Card>
                    ) : (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm font-medium">
                                    Perbandingan Kinerja Antar Cabang
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full text-sm">
                                        <thead>
                                            <tr className="border-b">
                                                <th className="py-3 pr-4 text-left font-medium">Cabang</th>
                                                <th className="py-3 pr-4 text-right font-medium">Revenue</th>
                                                <th className="py-3 pr-4 text-right font-medium">Growth</th>
                                                <th className="py-3 pr-4 text-right font-medium">Transaksi</th>
                                                <th className="py-3 pr-4 text-right font-medium">Rata-rata</th>
                                                <th className="py-3 pr-4 text-center font-medium">Shift</th>
                                                <th className="py-3 pr-4 text-center font-medium">Alerts</th>
                                                <th className="py-3 pr-4 text-center font-medium">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {cabangs.map((branch) => (
                                                <>
                                                    <tr key={branch.id} className="border-b hover:bg-muted/50">
                                                        <td className="py-3 pr-4">
                                                            <div className="flex items-center gap-2">
                                                                <span className="font-medium">{branch.nama}</span>
                                                                {branch.kode && (
                                                                    <Badge variant="outline" className="text-xs">{branch.kode}</Badge>
                                                                )}
                                                                {!branch.aktif && (
                                                                    <Badge variant="secondary" className="text-xs">Nonaktif</Badge>
                                                                )}
                                                            </div>
                                                        </td>
                                                        <td className="py-3 pr-4 text-right font-medium">
                                                            {formatCurrency(branch.total_revenue)}
                                                        </td>
                                                        <td className="py-3 pr-4 text-right">
                                                            <div className="flex items-center justify-end gap-1">
                                                                {branch.growth_percent >= 0 ? (
                                                                    <TrendingUp className="h-4 w-4 text-green-500" />
                                                                ) : (
                                                                    <TrendingDown className="h-4 w-4 text-red-500" />
                                                                )}
                                                                <span className={branch.growth_percent >= 0 ? 'text-green-600' : 'text-red-600'}>
                                                                    {branch.growth_percent >= 0 ? '+' : ''}{branch.growth_percent}%
                                                                </span>
                                                            </div>
                                                        </td>
                                                        <td className="py-3 pr-4 text-right">
                                                            {formatNumber(branch.transaction_count)}
                                                        </td>
                                                        <td className="py-3 pr-4 text-right">
                                                            {formatCurrency(branch.avg_transaction)}
                                                        </td>
                                                        <td className="py-3 pr-4 text-center">
                                                            <div className="flex items-center justify-center gap-1">
                                                                <Clock className="h-4 w-4 text-muted-foreground" />
                                                                <span>{branch.total_shifts}</span>
                                                            </div>
                                                        </td>
                                                        <td className="py-3 pr-4 text-center">
                                                            <div className="flex items-center justify-center gap-2">
                                                                {branch.low_stock_count > 0 && (
                                                                    <Tooltip>
                                                                        <TooltipTrigger>
                                                                            <Badge variant="outline" className="text-xs bg-red-50 text-red-600 border-red-200">
                                                                                <Package className="h-3 w-3 mr-1" />
                                                                                {branch.low_stock_count}
                                                                            </Badge>
                                                                        </TooltipTrigger>
                                                                        <TooltipContent>
                                                                            <p>Stok menipis: {branch.low_stock_count} item</p>
                                                                        </TooltipContent>
                                                                    </Tooltip>
                                                                )}
                                                                {branch.expiring_count > 0 && (
                                                                    <Tooltip>
                                                                        <TooltipTrigger>
                                                                            <Badge variant="outline" className="text-xs bg-yellow-50 text-yellow-600 border-yellow-200">
                                                                                <AlertTriangle className="h-3 w-3 mr-1" />
                                                                                {branch.expiring_count}
                                                                            </Badge>
                                                                        </TooltipTrigger>
                                                                        <TooltipContent>
                                                                            <p>Produk kadaluwarsa: {branch.expiring_count} item</p>
                                                                        </TooltipContent>
                                                                    </Tooltip>
                                                                )}
                                                            </div>
                                                        </td>
                                                        <td className="py-3 pr-4 text-center">
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => setExpandedBranch(expandedBranch === branch.id ? null : branch.id)}
                                                            >
                                                                {expandedBranch === branch.id ? 'Tutup' : 'Detail'}
                                                            </Button>
                                                        </td>
                                                    </tr>
                                                    {expandedBranch === branch.id && (
                                                        <tr key={`${branch.id}-detail`}>
                                                            <td colSpan={8} className="bg-muted/30 p-4">
                                                                <div className="grid gap-4 md:grid-cols-2">
                                                                    {/* Top Products */}
                                                                    <div>
                                                                        <h4 className="font-medium mb-2">Top Produk</h4>
                                                                        <div className="space-y-2">
                                                                            {branch.top_products.map((product, idx) => (
                                                                                <div key={idx} className="flex justify-between text-sm">
                                                                                    <span>{idx + 1}. {product.nama}</span>
                                                                                    <span className="text-muted-foreground">
                                                                                        {formatNumber(product.qty)} unit | {formatCurrency(product.revenue)}
                                                                                    </span>
                                                                                </div>
                                                                            ))}
                                                                            {branch.top_products.length === 0 && (
                                                                                <p className="text-sm text-muted-foreground">Tidak ada data</p>
                                                                            )}
                                                                        </div>
                                                                    </div>
                                                                    {/* Top Categories */}
                                                                    <div>
                                                                        <h4 className="font-medium mb-2">Top Kategori</h4>
                                                                        <div className="space-y-2">
                                                                            {branch.top_categories.map((cat, idx) => (
                                                                                <div key={idx} className="flex justify-between text-sm">
                                                                                    <span>{idx + 1}. {cat.nama}</span>
                                                                                    <span className="text-muted-foreground">
                                                                                        {formatCurrency(cat.revenue)}
                                                                                    </span>
                                                                                </div>
                                                                            ))}
                                                                            {branch.top_categories.length === 0 && (
                                                                                <p className="text-sm text-muted-foreground">Tidak ada data</p>
                                                                            )}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                {/* Daily Data Summary */}
                                                                <div className="mt-4">
                                                                    <h4 className="font-medium mb-2">Tren Harian</h4>
                                                                    <p className="text-sm text-muted-foreground">
                                                                        {branch.daily_data.length} hari dengan data &bull; Rata-rata: {formatCurrency(branch.daily_data.length > 0 ? branch.total_revenue / branch.daily_data.length : 0)}/hari
                                                                    </p>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    )}
                                                </>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </TooltipProvider>
        </AppLayout>
    );
}
