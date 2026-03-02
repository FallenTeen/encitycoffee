/**
 * Format currency in Indonesian Rupiah format
 * @param amount - The amount to format
 * @returns Formatted currency string
 */
export function formatCurrency(amount: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount);
}

/**
 * Format number with Indonesian locale
 * @param number - The number to format
 * @returns Formatted number string
 */
export function formatNumber(number: number): string {
  return new Intl.NumberFormat('id-ID').format(number);
}

/**
 * Format percentage
 * @param value - The percentage value
 * @param decimals - Number of decimal places
 * @returns Formatted percentage string
 */
export function formatPercentage(value: number, decimals: number = 1): string {
  return `${value.toFixed(decimals)}%`;
}

/**
 * Format date in Indonesian format
 * @param date - The date to format
 * @returns Formatted date string
 */
export function formatDate(date: Date | string): string {
  const d = new Date(date);
  return d.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  });
}

/**
 * Format time in Indonesian format
 * @param date - The date/time to format
 * @returns Formatted time string
 */
export function formatTime(date: Date | string): string {
  const d = new Date(date);
  return d.toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
  });
}

/**
 * Format datetime in Indonesian format
 * @param date - The date/time to format
 * @returns Formatted datetime string
 */
export function formatDateTime(date: Date | string): string {
  const d = new Date(date);
  return d.toLocaleString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

/**
 * Format relative time (e.g., "2 hours ago")
 * @param date - The date to format
 * @returns Relative time string
 */
export function formatRelativeTime(date: Date | string): string {
  const d = new Date(date);
  const now = new Date();
  const diffInSeconds = Math.floor((now.getTime() - d.getTime()) / 1000);

  if (diffInSeconds < 60) {
    return 'Baru saja';
  } else if (diffInSeconds < 3600) {
    const minutes = Math.floor(diffInSeconds / 60);
    return `${minutes} menit yang lalu`;
  } else if (diffInSeconds < 86400) {
    const hours = Math.floor(diffInSeconds / 3600);
    return `${hours} jam yang lalu`;
  } else {
    const days = Math.floor(diffInSeconds / 86400);
    return `${days} hari yang lalu`;
  }
}

/**
 * Truncate text to specified length
 * @param text - The text to truncate
 * @param maxLength - Maximum length before truncation
 * @returns Truncated text
 */
export function truncateText(text: string, maxLength: number): string {
  if (text.length <= maxLength) {
    return text;
  }
  return text.substring(0, maxLength) + '...';
}

/**
 * Convert number to words (simplified Indonesian)
 * @param number - The number to convert
 * @returns Number in words
 */
export function numberToWords(number: number): string {
  const units = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan'];
  const teens = ['sepuluh', 'sebelas', 'dua belas', 'tiga belas', 'empat belas', 'lima belas', 
                 'enam belas', 'tujuh belas', 'delapan belas', 'sembilan belas'];
  
  if (number === 0) return 'nol';
  if (number < 10) return units[number];
  if (number < 20) return teens[number - 10];
  if (number < 100) {
    const tens = Math.floor(number / 10);
    const unit = number % 10;
    return units[tens] + ' puluh' + (unit > 0 ? ' ' + units[unit] : '');
  }
  if (number < 1000) {
    const hundreds = Math.floor(number / 100);
    const remainder = number % 100;
    return units[hundreds] + ' ratus' + (remainder > 0 ? ' ' + numberToWords(remainder) : '');
  }
  
  return number.toString();
}

/**
 * Format file size
 * @param bytes - Size in bytes
 * @returns Formatted file size
 */
export function formatFileSize(bytes: number): string {
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
  if (bytes === 0) return '0 Bytes';
  const i = Math.floor(Math.log(bytes) / Math.log(1024));
  return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
}

/**
 * Generate random color for charts
 * @param seed - Seed for random color generation
 * @returns Hex color code
 */
export function generateRandomColor(seed?: string): string {
  const colors = [
    '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
    '#06B6D4', '#84CC16', '#F97316', '#EC4899', '#6366F1'
  ];
  
  if (seed) {
    // Simple hash function for seed
    let hash = 0;
    for (let i = 0; i < seed.length; i++) {
      hash = seed.charCodeAt(i) + ((hash << 5) - hash);
    }
    return colors[Math.abs(hash) % colors.length];
  }
  
  return colors[Math.floor(Math.random() * colors.length)];
}

/**
 * Generate gradient colors for charts
 * @param baseColor - Base color in hex format
 * @param count - Number of gradient colors to generate
 * @returns Array of gradient colors
 */
export function generateGradientColors(baseColor: string, count: number): string[] {
  const colors = [];
  const baseRgb = hexToRgb(baseColor);
  
  for (let i = 0; i < count; i++) {
    const factor = i / (count - 1);
    const r = Math.round(baseRgb.r + (255 - baseRgb.r) * factor * 0.5);
    const g = Math.round(baseRgb.g + (255 - baseRgb.g) * factor * 0.5);
    const b = Math.round(baseRgb.b + (255 - baseRgb.b) * factor * 0.5);
    colors.push(rgbToHex(r, g, b));
  }
  
  return colors;
}

/**
 * Convert hex to RGB
 * @param hex - Hex color code
 * @returns RGB values
 */
function hexToRgb(hex: string): { r: number; g: number; b: number } {
  const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
  return result ? {
    r: parseInt(result[1], 16),
    g: parseInt(result[2], 16),
    b: parseInt(result[3], 16)
  } : { r: 0, g: 0, b: 0 };
}

/**
 * Convert RGB to hex
 * @param r - Red value
 * @param g - Green value
 * @param b - Blue value
 * @returns Hex color code
 */
function rgbToHex(r: number, g: number, b: number): string {
  return "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
}