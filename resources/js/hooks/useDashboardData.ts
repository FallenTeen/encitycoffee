import { useState, useEffect } from 'react';
import axios from 'axios';

interface RealtimeDataOptions {
  interval?: number;
  enabled?: boolean;
}

interface RealtimeDataResult {
  data: any;
  isLoading: boolean;
  error: string | null;
  refetch: () => void;
}

/**
 * Custom hook for fetching real-time dashboard data
 * @param role - User role (admin, manager, supervisor, kasir)
 * @param type - Data type (general, revenue, system, etc.)
 * @param interval - Refresh interval in seconds
 */
export function useRealtimeData(
  role: string,
  type: string = 'general',
  interval: number = 60
): RealtimeDataResult {
  const [data, setData] = useState<any>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchData = async () => {
    if (interval === 0) return; // Don't fetch if interval is 0

    setIsLoading(true);
    setError(null);

    try {
      const response = await axios.get('/dashboard/realtime', {
        params: { type, interval },
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      setData(response.data.data);
    } catch (err) {
      if (axios.isAxiosError(err)) {
        setError(err.response?.data?.message || err.message);
      } else {
        setError('An unexpected error occurred');
      }
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    if (interval === 0) {
      setIsLoading(false);
      return;
    }

    fetchData();

    const timer = setInterval(() => {
      fetchData();
    }, interval * 1000);

    return () => {
      if (timer) {
        clearInterval(timer);
      }
    };
  }, [role, type, interval]);

  const refetch = () => {
    fetchData();
  };

  return {
    data,
    isLoading,
    error,
    refetch,
  };
}

/**
 * Utility function to format time for display
 */
export function formatTime(date: Date | string): string {
  const d = new Date(date);
  return d.toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
  });
}

/**
 * Utility function to format date for display
 */
export function formatDate(date: Date | string): string {
  const d = new Date(date);
  return d.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  });
}

/**
 * Utility function to format datetime for display
 */
export function formatDateTime(date: Date | string): string {
  const d = new Date(date);
  return d.toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}