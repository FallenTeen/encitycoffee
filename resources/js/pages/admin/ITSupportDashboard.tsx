import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Head, Link } from '@inertiajs/react';
import { Trash2, Users, BarChart3, Settings, Database, Shield, Activity } from 'lucide-react';

export default function ITSupportDashboard() {
  const managementCards = [
    {
      title: 'Manajemen Transaksi Terhapus',
      description: 'Lihat dan kembalikan transaksi yang dihapus secara soft delete',
      icon: Trash2,
      href: '/admin/deleted-transactions',
      color: 'text-red-600',
      bgColor: 'bg-red-50',
    },
    {
      title: 'Manajemen Bill Terhapus',
      description: 'Lihat dan kembalikan bill yang dihapus secara soft delete',
      icon: BarChart3,
      href: '/admin/deleted-bills',
      color: 'text-orange-600',
      bgColor: 'bg-orange-50',
    },
    {
      title: 'Manajemen User',
      description: 'Kelola pengguna sistem dan hak akses',
      icon: Users,
      href: '/admin/users',
      color: 'text-blue-600',
      bgColor: 'bg-blue-50',
    },
    {
      title: 'System Logs',
      description: 'Lihat log aktivitas sistem dan audit trail',
      icon: Activity,
      href: '/admin/system-logs',
      color: 'text-purple-600',
      bgColor: 'bg-purple-50',
    },
  ];

  const quickActions = [
    {
      title: 'Database Management',
      description: 'Backup dan maintenance database',
      icon: Database,
      href: '#',
      variant: 'outline' as const,
    },
    {
      title: 'Security Settings',
      description: 'Konfigurasi keamanan sistem',
      icon: Shield,
      href: '#',
      variant: 'outline' as const,
    },
    {
      title: 'System Configuration',
      description: 'Pengaturan sistem dan parameter',
      icon: Settings,
      href: '#',
      variant: 'outline' as const,
    },
  ];

  return (
    <AppLayout breadcrumbs={[{ title: 'IT Support Dashboard', href: '/admin' }]}>
      <Head title="IT Support Dashboard" />
      
      <div className="space-y-6">
        {/* Header */}
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-2xl font-bold tracking-tight">IT Support Dashboard</h1>
            <p className="text-muted-foreground">
              Panel administrasi sistem untuk manajemen data dan konfigurasi
            </p>
          </div>
          <div className="flex items-center gap-2">
            <Badge variant="outline" className="border-green-500 text-green-700">
              <div className="h-2 w-2 rounded-full bg-green-500 mr-2"></div>
              Sistem Aktif
            </Badge>
          </div>
        </div>

        {/* Main Management Cards */}
        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
          {managementCards.map((card, index) => {
            const Icon = card.icon;
            return (
              <Link key={index} href={card.href} className="group">
                <Card className="h-full transition-all hover:shadow-lg group-hover:scale-[1.02]">
                  <CardHeader className="pb-3">
                    <div className={`w-12 h-12 rounded-lg ${card.bgColor} flex items-center justify-center mb-2`}>
                      <Icon className={`h-6 w-6 ${card.color}`} />
                    </div>
                    <CardTitle className="text-lg">{card.title}</CardTitle>
                    <CardDescription>{card.description}</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <Button className="w-full group-hover:bg-primary/90" variant="default">
                      Akses Menu
                    </Button>
                  </CardContent>
                </Card>
              </Link>
            );
          })}
        </div>

        {/* Quick Actions */}
        <div className="grid gap-6 md:grid-cols-3">
          <div className="md:col-span-3">
            <h2 className="text-lg font-semibold mb-4">Aksi Cepat</h2>
          </div>
          {quickActions.map((action, index) => {
            const Icon = action.icon;
            return (
              <Card key={index} className="border-dashed">
                <CardHeader className="pb-3">
                  <div className="w-10 h-10 rounded-lg bg-muted flex items-center justify-center mb-2">
                    <Icon className="h-5 w-5 text-muted-foreground" />
                  </div>
                  <CardTitle className="text-base">{action.title}</CardTitle>
                  <CardDescription>{action.description}</CardDescription>
                </CardHeader>
                <CardContent>
                  <Button variant={action.variant} className="w-full" disabled>
                    Coming Soon
                  </Button>
                </CardContent>
              </Card>
            );
          })}
        </div>

        {/* System Status */}
        <Card>
          <CardHeader>
            <CardTitle>Status Sistem</CardTitle>
            <CardDescription>Informasi kesehatan dan performa sistem</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 md:grid-cols-3">
              <div className="flex items-center justify-between">
                <span className="text-sm text-muted-foreground">Database Status</span>
                <Badge variant="default" className="bg-green-500">Connected</Badge>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-sm text-muted-foreground">Cache Status</span>
                <Badge variant="default" className="bg-green-500">Active</Badge>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-sm text-muted-foreground">Queue Status</span>
                <Badge variant="default" className="bg-green-500">Running</Badge>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}