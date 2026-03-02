import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  PointElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js';
import { Bar, Line, Pie, Doughnut } from 'react-chartjs-2';

// Register ChartJS components
ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  PointElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

// Color palettes for consistent theming
export const COLORS = {
  primary: ['#3b82f6', '#1d4ed8', '#1e40af', '#1e3a8a', '#172554'],
  success: ['#10b981', '#059669', '#047857', '#065f46', '#064e3b'],
  warning: ['#f59e0b', '#d97706', '#b45309', '#92400e', '#78350f'],
  danger: ['#ef4444', '#dc2626', '#b91c1c', '#991b1b', '#7f1d1d'],
  info: ['#06b6d4', '#0891b2', '#0e7490', '#155e75', '#164e63'],
  purple: ['#8b5cf6', '#7c3aed', '#6d28d9', '#5b21b6', '#4c1d95'],
  gray: ['#6b7280', '#4b5563', '#374151', '#1f2937', '#111827'],
};

// Common chart options
export const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top' as const,
      labels: {
        usePointStyle: true,
        padding: 20,
        font: {
          size: 12,
        },
      },
    },
    tooltip: {
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      titleColor: '#fff',
      bodyColor: '#fff',
      borderColor: '#ddd',
      borderWidth: 1,
      cornerRadius: 8,
      displayColors: true,
      callbacks: {
        label: function(context: any) {
          let label = context.dataset.label || '';
          if (label) {
            label += ': ';
          }
          if (context.parsed.y !== null) {
            label += new Intl.NumberFormat('id-ID', {
              style: 'currency',
              currency: 'IDR',
              minimumFractionDigits: 0,
            }).format(context.parsed.y);
          }
          return label;
        },
      },
    },
  },
  scales: {
    x: {
      grid: {
        display: false,
      },
      ticks: {
        font: {
          size: 11,
        },
      },
    },
    y: {
      beginAtZero: true,
      grid: {
        color: 'rgba(0, 0, 0, 0.1)',
      },
      ticks: {
        font: {
          size: 11,
        },
        callback: function(value: any) {
          if (value >= 1000000) {
            return 'Rp ' + (value / 1000000).toFixed(1) + 'M';
          } else if (value >= 1000) {
            return 'Rp ' + (value / 1000).toFixed(0) + 'K';
          }
          return 'Rp ' + value.toLocaleString();
        },
      },
    },
  },
};

// Bar chart component for performance metrics
export const BarChart = ({ data, title, height = 300 }: { data: any; title?: string; height?: number }) => {
  const chartData = {
    labels: data.labels || [],
    datasets: [
      {
        label: data.label || 'Data',
        data: data.values || [],
        backgroundColor: data.colors || COLORS.primary,
        borderColor: data.borderColors || COLORS.primary[0],
        borderWidth: 1,
        borderRadius: 4,
        barThickness: 30,
      },
    ],
  };

  const options = {
    ...chartOptions,
    plugins: {
      ...chartOptions.plugins,
      title: {
        display: !!title,
        text: title,
        font: {
          size: 16,
          weight: 'bold' as const,
        },
        padding: 20,
      },
    },
  };

  return (
    <div style={{ height: `${height}px` }}>
      <Bar data={chartData} options={options} />
    </div>
  );
};

// Line chart component for trends analysis
export const LineChart = ({ data, title, height = 300 }: { data: any; title?: string; height?: number }) => {
  const chartData = {
    labels: data.labels || [],
    datasets: data.datasets?.map((dataset: any, index: number) => ({
      label: dataset.label || `Series ${index + 1}`,
      data: dataset.values || [],
      borderColor: dataset.color || COLORS.primary[index % COLORS.primary.length],
      backgroundColor: dataset.fillColor || `${dataset.color || COLORS.primary[index % COLORS.primary.length]}20`,
      fill: dataset.fill !== false,
      tension: 0.4,
      pointRadius: 4,
      pointHoverRadius: 6,
      borderWidth: 2,
    })) || [],
  };

  const options = {
    ...chartOptions,
    plugins: {
      ...chartOptions.plugins,
      title: {
        display: !!title,
        text: title,
        font: {
          size: 16,
          weight: 'bold' as const,
        },
        padding: 20,
      },
    },
  };

  return (
    <div style={{ height: `${height}px` }}>
      <Line data={chartData} options={options} />
    </div>
  );
};

// Pie chart component for distribution data
export const PieChart = ({ data, title, height = 300 }: { data: any; title?: string; height?: number }) => {
  const chartData = {
    labels: data.labels || [],
    datasets: [
      {
        data: data.values || [],
        backgroundColor: data.colors || [
          COLORS.primary[0],
          COLORS.success[0],
          COLORS.warning[0],
          COLORS.danger[0],
          COLORS.info[0],
          COLORS.purple[0],
        ],
        borderColor: '#ffffff',
        borderWidth: 2,
        hoverOffset: 4,
      },
    ],
  };

  const options = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'right' as const,
        labels: {
          usePointStyle: true,
          padding: 20,
          font: {
            size: 12,
          },
        },
      },
      tooltip: {
        backgroundColor: 'rgba(0, 0, 0, 0.8)',
        titleColor: '#fff',
        bodyColor: '#fff',
        borderColor: '#ddd',
        borderWidth: 1,
        cornerRadius: 8,
        callbacks: {
          label: function(context: any) {
            const label = context.label || '';
            const value = context.parsed;
            const total = context.dataset.data.reduce((a: number, b: number) => a + b, 0);
            const percentage = ((value / total) * 100).toFixed(1);
            return `${label}: ${percentage}% (${value.toLocaleString()})`;
          },
        },
      },
      title: {
        display: !!title,
        text: title,
        font: {
          size: 16,
          weight: 'bold' as const,
        },
        padding: 20,
      },
    },
  };

  return (
    <div style={{ height: `${height}px` }}>
      <Pie data={chartData} options={options} />
    </div>
  );
};

// Doughnut chart component for completion rates
export const DoughnutChart = ({ data, title, height = 300 }: { data: any; title?: string; height?: number }) => {
  const chartData = {
    labels: data.labels || [],
    datasets: [
      {
        data: data.values || [],
        backgroundColor: data.colors || [
          COLORS.primary[0],
          COLORS.success[0],
          COLORS.warning[0],
          COLORS.danger[0],
        ],
        borderColor: '#ffffff',
        borderWidth: 3,
        cutout: '60%',
        hoverOffset: 4,
      },
    ],
  };

  const options = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'bottom' as const,
        labels: {
          usePointStyle: true,
          padding: 15,
          font: {
            size: 12,
          },
        },
      },
      tooltip: {
        backgroundColor: 'rgba(0, 0, 0, 0.8)',
        titleColor: '#fff',
        bodyColor: '#fff',
        borderColor: '#ddd',
        borderWidth: 1,
        cornerRadius: 8,
        callbacks: {
          label: function(context: any) {
            const label = context.label || '';
            const value = context.parsed;
            const total = context.dataset.data.reduce((a: number, b: number) => a + b, 0);
            const percentage = ((value / total) * 100).toFixed(1);
            return `${label}: ${percentage}%`;
          },
        },
      },
      title: {
        display: !!title,
        text: title,
        font: {
          size: 16,
          weight: 'bold' as const,
        },
        padding: 20,
      },
    },
  };

  return (
    <div style={{ height: `${height}px` }}>
      <Doughnut data={chartData} options={options} />
    </div>
  );
};

// Mixed chart component for complex data visualization
export const MixedChart = ({ data, title, height = 400 }: { data: any; title?: string; height?: number }) => {
  const chartData = {
    labels: data.labels || [],
    datasets: data.datasets?.map((dataset: any, index: number) => ({
      type: dataset.type || 'bar',
      label: dataset.label || `Series ${index + 1}`,
      data: dataset.values || [],
      borderColor: dataset.borderColor || COLORS.primary[index % COLORS.primary.length],
      backgroundColor: dataset.backgroundColor || `${COLORS.primary[index % COLORS.primary.length]}80`,
      yAxisID: dataset.yAxisID || 'y',
      fill: dataset.fill !== false,
      tension: dataset.tension || 0.4,
      borderWidth: dataset.borderWidth || 2,
      pointRadius: dataset.pointRadius || 4,
      pointHoverRadius: dataset.pointHoverRadius || 6,
    })) || [],
  };

  const options = {
    ...chartOptions,
    scales: {
      ...chartOptions.scales,
      y: {
        ...chartOptions.scales?.y,
        type: 'linear' as const,
        display: true,
        position: 'left' as const,
      },
      y1: {
        type: 'linear' as const,
        display: true,
        position: 'right' as const,
        grid: {
          drawOnChartArea: false,
        },
      },
    },
    plugins: {
      ...chartOptions.plugins,
      title: {
        display: !!title,
        text: title,
        font: {
          size: 16,
          weight: 'bold' as const,
        },
        padding: 20,
      },
    },
  };

  return (
    <div style={{ height: `${height}px` }}>
      {/* @ts-ignore */}
      <Bar data={chartData} options={options} />
    </div>
  );
};

// KPI Card component for dashboard metrics
export const KPICard = ({ title, value, change, icon, color = 'primary' }: {
  title: string;
  value: string | number;
  change?: { value: number; type: 'increase' | 'decrease' };
  icon?: React.ReactNode;
  color?: 'primary' | 'success' | 'warning' | 'danger' | 'info';
}) => {
  const colorClasses = {
    primary: 'bg-blue-50 text-blue-700 border-blue-200',
    success: 'bg-green-50 text-green-700 border-green-200',
    warning: 'bg-yellow-50 text-yellow-700 border-yellow-200',
    danger: 'bg-red-50 text-red-700 border-red-200',
    info: 'bg-cyan-50 text-cyan-700 border-cyan-200',
  };

  const iconColors = {
    primary: 'text-blue-600',
    success: 'text-green-600',
    warning: 'text-yellow-600',
    danger: 'text-red-600',
    info: 'text-cyan-600',
  };

  return (
    <div className={`p-6 rounded-lg border ${colorClasses[color]} transition-all hover:shadow-md`}>
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
};

// Chart Card component for dashboard charts
export const ChartCard = ({ title, children, height = 400, className = '' }: {
  title: string;
  children: React.ReactNode;
  height?: number;
  className?: string;
}) => {
  return (
    <div className={`bg-white rounded-lg border border-gray-200 p-6 ${className}`}>
      <h3 className="text-lg font-semibold text-gray-900 mb-4">{title}</h3>
      <div style={{ height: `${height}px` }}>
        {children}
      </div>
    </div>
  );
};

export default {
  BarChart,
  LineChart,
  PieChart,
  DoughnutChart,
  MixedChart,
  KPICard,
  ChartCard,
  COLORS,
};