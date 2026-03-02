import React from 'react';
import { Link } from '@inertiajs/react';

interface DashboardLayoutProps {
  children: React.ReactNode;
  user: {
    id: number;
    name: string;
    email: string;
    role: string;
  };
}

export function DashboardLayout({ children, user }: DashboardLayoutProps) {
  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white shadow-sm border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center">
              <h1 className="text-xl font-semibold text-gray-900">Dashboard</h1>
            </div>
            <div className="flex items-center space-x-4">
              <span className="text-sm text-gray-500">{user.role}</span>
              <div className="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                <span className="text-white text-sm font-medium">
                  {user.name.charAt(0).toUpperCase()}
                </span>
              </div>
            </div>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {children}
      </main>
    </div>
  );
}

interface KPICardProps {
  title: string;
  value: string | number;
  change?: { value: number; type: 'increase' | 'decrease' };
  icon?: React.ReactNode;
  color?: 'primary' | 'success' | 'warning' | 'danger' | 'info' | 'purple';
  className?: string;
}

export function KPICard({ title, value, change, icon, color = 'primary', className = '' }: KPICardProps) {
  const colorClasses = {
    primary: 'bg-blue-50 text-blue-700 border-blue-200',
    success: 'bg-green-50 text-green-700 border-green-200',
    warning: 'bg-yellow-50 text-yellow-700 border-yellow-200',
    danger: 'bg-red-50 text-red-700 border-red-200',
    info: 'bg-cyan-50 text-cyan-700 border-cyan-200',
    purple: 'bg-purple-50 text-purple-700 border-purple-200',
  };

  const iconColors = {
    primary: 'text-blue-600',
    success: 'text-green-600',
    warning: 'text-yellow-600',
    danger: 'text-red-600',
    info: 'text-cyan-600',
    purple: 'text-purple-600',
  };

  return (
    <div className={`p-6 rounded-lg border ${colorClasses[color]} transition-all hover:shadow-md ${className}`}>
      <div className="flex items-center justify-between">
        <div className="flex-1">
          <p className="text-sm font-medium text-gray-600">{title}</p>
          <p className="text-2xl font-bold text-gray-900">{value}</p>
          {change && (
            <div className="flex items-center mt-2">
              <span className={`text-sm font-medium ${
                change.type === 'increase' ? 'text-green-600' : 'text-red-600'
              }`}>
                {change.type === 'increase' ? '↗' : '↘'} {Math.abs(change.value)}%
              </span>
              <span className="text-sm text-gray-500 ml-2">vs yesterday</span>
            </div>
          )}
        </div>
        {icon && (
          <div className={`p-3 rounded-full ${iconColors[color]}`}>
            {icon}
          </div>
        )}
      </div>
    </div>
  );
}

interface ChartCardProps {
  title: string;
  children: React.ReactNode;
  height?: number;
  className?: string;
  loading?: boolean;
  error?: string;
  onRetry?: () => void;
}

export function ChartCard({ title, children, height = 400, className = '', loading = false, error, onRetry }: ChartCardProps) {
  if (loading) {
    return <LoadingCard title={title} height={height} className={className} />;
  }

  if (error) {
    return <ErrorCard title={title} error={error} onRetry={onRetry} className={className} />;
  }

  return (
    <div className={`bg-white rounded-lg border border-gray-200 p-6 ${className}`}>
      <h3 className="text-lg font-semibold text-gray-900 mb-4">{title}</h3>
      <div style={{ height: `${height}px` }}>
        {children}
      </div>
    </div>
  );
}

export function LoadingCard({ title, height = 400, className = '' }: { title: string; height?: number; className?: string }) {
  return (
    <div className={`bg-white rounded-lg border border-gray-200 p-6 ${className}`}>
      <h3 className="text-lg font-semibold text-gray-900 mb-4">{title}</h3>
      <div style={{ height: `${height}px` }} className="flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>
    </div>
  );
}

export function ErrorCard({ title, error, onRetry, className = '' }: { title: string; error: string; onRetry?: () => void; className?: string }) {
  return (
    <div className={`bg-white rounded-lg border border-red-200 p-6 ${className}`}>
      <h3 className="text-lg font-semibold text-gray-900 mb-4">{title}</h3>
      <div className="flex flex-col items-center justify-center text-center" style={{ minHeight: '200px' }}>
        <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
          <span className="text-red-600 text-2xl">⚠️</span>
        </div>
        <p className="text-red-600 mb-4">{error}</p>
        {onRetry && (
          <button
            onClick={onRetry}
            className="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors"
          >
            Try Again
          </button>
        )}
      </div>
    </div>
  );
}

export function EmptyState({ title, description, action, onAction }: {
  title: string;
  description: string;
  action?: string;
  onAction?: () => void;
}) {
  return (
    <div className="text-center py-12">
      <div className="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <span className="text-gray-400 text-2xl">📊</span>
      </div>
      <h3 className="text-lg font-medium text-gray-900 mb-2">{title}</h3>
      <p className="text-gray-500 mb-4">{description}</p>
      {action && onAction && (
        <button
          onClick={onAction}
          className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
        >
          {action}
        </button>
      )}
    </div>
  );
}

export default {
  DashboardLayout,
  KPICard,
  ChartCard,
  LoadingCard,
  ErrorCard,
  EmptyState,
};