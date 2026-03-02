import React from 'react';
import { Link } from '@inertiajs/react';

interface NavigationItem {
  id: string;
  label: string;
  icon: string;
}

interface DashboardNavigationProps {
  items: NavigationItem[];
  activeTab: string;
  onTabChange: (tab: string) => void;
}

export function DashboardNavigation({ items, activeTab, onTabChange }: DashboardNavigationProps) {
  return (
    <div className="bg-white rounded-lg border border-gray-200 p-4">
      <div className="flex space-x-1 overflow-x-auto">
        {items.map((item) => (
          <button
            key={item.id}
            onClick={() => onTabChange(item.id)}
            className={`flex items-center space-x-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap ${
              activeTab === item.id
                ? 'bg-blue-100 text-blue-700 border border-blue-200'
                : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
            }`}
          >
            <span className="text-lg">{item.icon}</span>
            <span>{item.label}</span>
          </button>
        ))}
      </div>
    </div>
  );
}

interface QuickAction {
  label: string;
  icon: string;
  href: string;
}

interface DashboardQuickActionsProps {
  actions: QuickAction[];
}

export function DashboardQuickActions({ actions }: DashboardQuickActionsProps) {
  return (
    <div className="bg-white rounded-lg border border-gray-200 p-6">
      <h3 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        {actions.map((action, index) => (
          <Link
            key={index}
            href={action.href}
            className="flex flex-col items-center p-4 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-colors"
          >
            <span className="text-2xl mb-2">{action.icon}</span>
            <span className="text-sm font-medium text-gray-700 text-center">{action.label}</span>
          </Link>
        ))}
      </div>
    </div>
  );
}

interface DashboardStatsProps {
  title: string;
  stats: Array<{
    label: string;
    value: string | number;
    change?: { value: number; type: 'increase' | 'decrease' };
  }>;
}

export function DashboardStats({ title, stats }: DashboardStatsProps) {
  return (
    <div className="bg-white rounded-lg border border-gray-200 p-6">
      <h3 className="text-lg font-semibold text-gray-900 mb-4">{title}</h3>
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {stats.map((stat, index) => (
          <div key={index} className="text-center">
            <p className="text-sm text-gray-600">{stat.label}</p>
            <p className="text-2xl font-bold text-gray-900">{stat.value}</p>
            {stat.change && (
              <div className="flex items-center justify-center mt-1">
                <span className={`text-sm font-medium ${
                  stat.change.type === 'increase' ? 'text-green-600' : 'text-red-600'
                }`}>
                  {stat.change.type === 'increase' ? '↗' : '↘'} {Math.abs(stat.change.value)}%
                </span>
              </div>
            )}
          </div>
        ))}
      </div>
    </div>
  );
}

interface FilterOption {
  value: string;
  label: string;
}

interface DashboardFilterControlsProps {
  dateRange: string;
  onDateRangeChange: (range: string) => void;
  refreshInterval: number;
  onRefreshIntervalChange: (interval: number) => void;
  dateRangeOptions?: FilterOption[];
  refreshIntervalOptions?: FilterOption[];
}

export function DashboardFilterControls({
  dateRange,
  onDateRangeChange,
  refreshInterval,
  onRefreshIntervalChange,
  dateRangeOptions = [
    { value: '7d', label: 'Last 7 days' },
    { value: '30d', label: 'Last 30 days' },
    { value: '90d', label: 'Last 90 days' },
    { value: '1y', label: 'Last year' },
  ],
  refreshIntervalOptions = [
    { value: 0, label: 'No refresh' },
    { value: 30, label: '30 seconds' },
    { value: 60, label: '1 minute' },
    { value: 300, label: '5 minutes' },
  ],
}: DashboardFilterControlsProps) {
  return (
    <div className="bg-white rounded-lg border border-gray-200 p-4">
      <div className="flex flex-col sm:flex-row gap-4">
        <div className="flex-1">
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Date Range
          </label>
          <select
            value={dateRange}
            onChange={(e) => onDateRangeChange(e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            {dateRangeOptions.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>
        </div>
        <div className="flex-1">
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Refresh Interval
          </label>
          <select
            value={refreshInterval}
            onChange={(e) => onRefreshIntervalChange(Number(e.target.value))}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            {refreshIntervalOptions.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>
        </div>
      </div>
    </div>
  );
}

export default {
  DashboardNavigation,
  DashboardQuickActions,
  DashboardStats,
  DashboardFilterControls,
};