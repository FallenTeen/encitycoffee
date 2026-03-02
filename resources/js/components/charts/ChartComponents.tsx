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

// Common chart options
export const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top' as const,
    },
    tooltip: {
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      titleColor: '#fff',
      bodyColor: '#fff',
      borderColor: '#ddd',
      borderWidth: 1,
    },
  },
  scales: {
    x: {
      grid: {
        display: false,
      },
    },
    y: {
      grid: {
        color: 'rgba(0, 0, 0, 0.1)',
      },
    },
  },
};

// Bar Chart Component
export function BarChart({ data, title, height = 300 }: { data: any; title?: string; height?: number }) {
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
      },
    },
  };

  return (
    <div style={{ height }}>
      <Bar data={data} options={options} />
    </div>
  );
}

// Line Chart Component
export function LineChart({ data, title, height = 300 }: { data: any; title?: string; height?: number }) {
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
      },
    },
    elements: {
      line: {
        tension: 0.4,
      },
      point: {
        radius: 4,
        hoverRadius: 6,
      },
    },
  };

  return (
    <div style={{ height }}>
      <Line data={data} options={options} />
    </div>
  );
}

// Pie Chart Component
export function PieChart({ data, title, height = 300 }: { data: any; title?: string; height?: number }) {
  const options = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'right' as const,
      },
      title: {
        display: !!title,
        text: title,
        font: {
          size: 16,
          weight: 'bold' as const,
        },
      },
    },
  };

  return (
    <div style={{ height }}>
      <Pie data={data} options={options} />
    </div>
  );
}

// Doughnut Chart Component
export function DoughnutChart({ data, title, height = 300 }: { data: any; title?: string; height?: number }) {
  const options = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'right' as const,
      },
      title: {
        display: !!title,
        text: title,
        font: {
          size: 16,
          weight: 'bold' as const,
        },
      },
    },
  };

  return (
    <div style={{ height }}>
      <Doughnut data={data} options={options} />
    </div>
  );
}

// Utility functions for chart data formatting
export const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value);
};

export const formatNumber = (value: number) => {
  return new Intl.NumberFormat('id-ID').format(value);
};

// Color palette for charts
export const chartColors = {
  primary: ['#3b82f6', '#1d4ed8', '#1e40af', '#1e3a8a', '#172554'],
  success: ['#10b981', '#059669', '#047857', '#065f46', '#064e3b'],
  warning: ['#f59e0b', '#d97706', '#b45309', '#92400e', '#78350f'],
  danger: ['#ef4444', '#dc2626', '#b91c1c', '#991b1b', '#7f1d1d'],
  info: ['#06b6d4', '#0891b2', '#0e7490', '#155e75', '#164e63'],
  purple: ['#8b5cf6', '#7c3aed', '#6d28d9', '#5b21b6', '#4c1d95'],
  gray: ['#6b7280', '#4b5563', '#374151', '#1f2937', '#111827'],
};

// Generate random colors for charts
export function generateColors(count: number) {
  const allColors = [
    ...chartColors.primary,
    ...chartColors.success,
    ...chartColors.warning,
    ...chartColors.danger,
    ...chartColors.info,
    ...chartColors.purple,
  ];
  
  return allColors.slice(0, count);
}