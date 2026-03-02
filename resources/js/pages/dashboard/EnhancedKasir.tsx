import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import { BarChart, LineChart, KPICard, ChartCard } from '@/components/charts/EnhancedChartComponents';
import { DashboardLayout } from '@/components/dashboard/DashboardLayout';
import { DashboardNavigation, DashboardQuickActions } from '@/components/dashboard/DashboardNavigation';
import { useRealtimeData } from '@/hooks/useDashboardData';
import { formatCurrency, formatNumber } from '@/utils/formatters';

interface EnhancedKasirProps {
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
    today_transactions: number;
    month_revenue: number;
    avg_transaction: number;
    current_shift_revenue: number;
  };
  hourlyPerformance: Array<{
    hour: number;
    time: string;
    revenue: number;
    transactions: number;
  }>;
  topProducts: Array<{
    nama: string;
    total_sold: number;
    total_revenue: number;
  }>;
  shiftPerformance: Array<{
    id: number;
    waktu_buka: string;
    waktu_tutup: string | null;
    cabang: {
      id: number;
      nama: string;
    };
    total_revenue: number;
    transaction_count: number;
  }>;
  recentTransactions: Array<{
    id: number;
    nomor_invoice: string;
    total: number;
    status: string;
    created_at: string;
    cabang: {
      id: number;
      nama: string;
    };
  }>;
  performanceTips: string[];
  currentShift: {
    id: number;
    waktu_buka: string;
    waktu_tutup: string | null;
    status: string;
    cabang: {
      id: number;
      nama: string;
    };
  } | null;
}

export default function EnhancedKasir({ auth, ...props }: EnhancedKasirProps) {
  const [activeTab, setActiveTab] = useState('overview');
  const [refreshInterval, setRefreshInterval] = useState(60);
  
  // Real-time data hook
  const { data: realtimeData, isLoading: isRealtimeLoading } = useRealtimeData('kasir', 'performance', refreshInterval);

  // Navigation items for cashier dashboard
  const navigationItems = [
    { id: 'overview', label: 'Overview', icon: '📊' },
    { id: 'performance', label: 'Performance', icon: '📈' },
    { id: 'products', label: 'Products', icon: '📦' },
    { id: 'shifts', label: 'Shifts', icon: '⏰' },
    { id: 'tips', label: 'Tips', icon: '💡' },
  ];

  // Quick actions for cashier
  const quickActions = [
    { label: 'New Transaction', icon: '🛒', href: '/transaksi/create' },
    { label: 'Open Bills', icon: '🧾', href: '/transaksi/open-bill' },
    { label: 'View Products', icon: '📦', href: '/produk' },
    { label: 'My Profile', icon: '👤', href: '/profile' },
  ];

  // Prepare chart data
  const hourlyPerformanceData = {
    labels: props.hourlyPerformance.map(item => item.time),
    datasets: [
      {
        label: 'Revenue',
        values: props.hourlyPerformance.map(item => item.revenue),
        color: '#3b82f6',
        fill: true,
      },
      {
        label: 'Transactions',
        values: props.hourlyPerformance.map(item => item.transactions),
        color: '#10b981',
        fill: false,
      },
    ],
  };

  const topProductsData = {
    labels: props.topProducts.map(product => product.nama),
    values: props.topProducts.map(product => product.total_sold),
    colors: ['#3b82f6', '#1d4ed8', '#1e40af', '#1e3a8a', '#172554'],
  };

  const shiftPerformanceData = {
    labels: props.shiftPerformance.map(shift => new Date(shift.waktu_buka).toLocaleDateString()),
    values: props.shiftPerformance.map(shift => shift.total_revenue),
    colors: ['#3b82f6', '#1d4ed8', '#1e40af', '#1e3a8a', '#172554'],
  };

  // Real-time KPI updates
  const updatedKpis = {
    ...props.kpis,
    today_revenue: realtimeData?.today_revenue ?? props.kpis.today_revenue,
    today_transactions: realtimeData?.today_transactions ?? props.kpis.today_transactions,
    current_shift_revenue: realtimeData?.current_shift_revenue ?? props.kpis.current_shift_revenue,
  };

  return (
    <DashboardLayout user={auth.user}>
      <Head title="Cashier Dashboard" />
      
      <div className="space-y-6">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">Cashier Dashboard</h1>
            <p className="text-gray-600 mt-1">Welcome back, {auth.user.name}</p>
            {props.currentShift && (
              <p className="text-sm text-gray-500 mt-1">
                Current shift: {new Date(props.currentShift.waktu_buka).toLocaleTimeString()} - {props.currentShift.cabang.nama}
              </p>
            )}
          </div>
          <div className="flex items-center space-x-4">
            <select
              value={refreshInterval}
              onChange={(e) => setRefreshInterval(Number(e.target.value))}
              className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value={0}>No refresh</option>
              <option value={30}>30 seconds</option>
              <option value={60}>1 minute</option>
              <option value={300}>5 minutes</option>
            </select>
          </div>
        </div>

        {/* Navigation */}
        <DashboardNavigation
          items={navigationItems}
          activeTab={activeTab}
          onTabChange={setActiveTab}
        />

        {/* Quick Actions */}
        <DashboardQuickActions actions={quickActions} />

        {/* Main Content */}
        <div className="space-y-6">
          {activeTab === 'overview' && (
            <>
              {/* KPI Cards */}
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <KPICard
                  title="Today's Revenue"
                  value={formatCurrency(updatedKpis.today_revenue)}
                  change={{ value: 12.5, type: 'increase' }}
                  icon="💰"
                  color="primary"
                />
                <KPICard
                  title="Today's Transactions"
                  value={formatNumber(updatedKpis.today_transactions)}
                  change={{ value: 8.2, type: 'increase' }}
                  icon="🛒"
                  color="success"
                />
                <KPICard
                  title="Monthly Revenue"
                  value={formatCurrency(updatedKpis.month_revenue)}
                  change={{ value: 15.3, type: 'increase' }}
                  icon="📈"
                  color="info"
                />
                <KPICard
                  title="Avg Transaction"
                  value={formatCurrency(updatedKpis.avg_transaction)}
                  icon="💳"
                  color="warning"
                />
                <KPICard
                  title="Current Shift Revenue"
                  value={formatCurrency(updatedKpis.current_shift_revenue)}
                  icon="⏰"
                  color="purple"
                />
              </div>

              {/* Hourly Performance Chart */}
              <ChartCard title="Today's Performance" height={400}>
                <LineChart data={hourlyPerformanceData} />
              </ChartCard>

              {/* Top Products */}
              <ChartCard title="Top Products Today" height={350}>
                <BarChart data={topProductsData} />
              </ChartCard>
            </>
          )}

          {activeTab === 'performance' && (
            <>
              <ChartCard title="Hourly Performance (Today)" height={450}>
                <LineChart data={hourlyPerformanceData} />
              </ChartCard>

              <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <ChartCard title="Top Products" height={400}>
                  <BarChart data={topProductsData} />
                </ChartCard>
                <ChartCard title="Shift Performance" height={400}>
                  <BarChart data={shiftPerformanceData} />
                </ChartCard>
              </div>
            </>
          )}

          {activeTab === 'products' && (
            <>
              <div className="bg-white rounded-lg border border-gray-200">
                <div className="px-6 py-4 border-b border-gray-200">
                  <h3 className="text-lg font-semibold text-gray-900">Top Products Today</h3>
                </div>
                <div className="p-6">
                  <BarChart data={topProductsData} height={450} />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {props.topProducts.map((product, index) => (
                  <div key={index} className="bg-white rounded-lg border border-gray-200 p-6">
                    <h4 className="font-semibold text-gray-900">{product.nama}</h4>
                    <div className="mt-4 space-y-2">
                      <div className="flex justify-between">
                        <span className="text-sm text-gray-600">Sold:</span>
                        <span className="text-sm font-medium">{formatNumber(product.total_sold)}</span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-sm text-gray-600">Revenue:</span>
                        <span className="text-sm font-medium">{formatCurrency(product.total_revenue)}</span>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </>
          )}

          {activeTab === 'shifts' && (
            <>
              <ChartCard title="Shift Performance" height={400}>
                <BarChart data={shiftPerformanceData} />
              </ChartCard>

              <div className="bg-white rounded-lg border border-gray-200">
                <div className="px-6 py-4 border-b border-gray-200">
                  <h3 className="text-lg font-semibold text-gray-900">Recent Shifts</h3>
                </div>
                <div className="divide-y divide-gray-200">
                  {props.shiftPerformance.map((shift) => (
                    <div key={shift.id} className="px-6 py-4 flex items-center justify-between">
                      <div>
                        <p className="font-medium text-gray-900">{shift.cabang.nama}</p>
                        <p className="text-sm text-gray-500">
                          {new Date(shift.waktu_buka).toLocaleDateString()}
                        </p>
                      </div>
                      <div className="text-right">
                        <p className="font-semibold text-gray-900">{formatCurrency(shift.total_revenue)}</p>
                        <p className="text-sm text-gray-500">
                          {formatNumber(shift.transaction_count)} transactions
                        </p>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </>
          )}

          {activeTab === 'tips' && (
            <>
              <div className="bg-white rounded-lg border border-gray-200">
                <div className="px-6 py-4 border-b border-gray-200">
                  <h3 className="text-lg font-semibold text-gray-900">Performance Tips</h3>
                </div>
                <div className="p-6 space-y-4">
                  {props.performanceTips.map((tip, index) => (
                    <div key={index} className="flex items-start space-x-3">
                      <div className="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                        <span className="text-blue-600 text-sm font-bold">{index + 1}</span>
                      </div>
                      <div>
                        <p className="text-gray-700">{tip}</p>
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              <div className="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <h4 className="font-semibold text-blue-900 mb-2">💡 Quick Tips</h4>
                <ul className="space-y-2 text-blue-800">
                  <li>• Always confirm customer orders to avoid mistakes</li>
                  <li>• Keep your workspace clean and organized</li>
                  <li>• Offer additional products to increase transaction value</li>
                  <li>• Process transactions quickly to serve more customers</li>
                  <li>• Stay updated on product availability and prices</li>
                </ul>
              </div>
            </>
          )}
        </div>
      </div>
    </DashboardLayout>
  );
}