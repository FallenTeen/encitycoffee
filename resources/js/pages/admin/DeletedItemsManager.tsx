import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { AlertCircle, RefreshCw, Trash2, Eye, Search, Filter } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';

interface DeletedItem {
  id: number;
  nomor_invoice?: string;
  nomor_open_bill?: string;
  total?: string | number;
  status?: string;
  created_at?: string;
  deleted_at?: string;
  delete_reason?: string;
  cabang?: { id: number; kode?: string; nama?: string } | null;
  user?: { id: number; name?: string } | null;
  shift?: { id: number; status?: string } | null;
  deleted_by?: { id: number; name?: string } | null;
}

interface Props {
  type: 'transaksi' | 'open-bill';
}

function formatCurrency(value: string | number | undefined | null) {
  const n = typeof value === 'string' ? parseFloat(value) : value ?? 0;
  return n.toLocaleString('id-ID', { maximumFractionDigits: 0 });
}

function formatDateTime(value?: string | null) {
  if (!value) return '-';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleString('id-ID');
}

export default function DeletedItemsManager({ type }: Props) {
  const [deletedItems, setDeletedItems] = useState<DeletedItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedItem, setSelectedItem] = useState<DeletedItem | null>(null);
  const [restoreDialogOpen, setRestoreDialogOpen] = useState(false);
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);

  const fetchDeletedItems = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const endpoint = type === 'transaksi' 
        ? '/transaksi/deleted/list'
        : '/transaksi/open-bill/deleted/list';
      
      const response = await fetch(endpoint);
      const data = await response.json();
      
      if (response.ok) {
        setDeletedItems(data.data || []);
      } else {
        setError(data.error || 'Gagal memuat data');
      }
    } catch (err) {
      setError('Terjadi kesalahan saat memuat data');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchDeletedItems();
  }, [type]);

  const handleRestore = (item: DeletedItem) => {
    const endpoint = type === 'transaksi'
      ? `/transaksi/${item.id}/restore`
      : `/transaksi/open-bill/${item.id}/restore`;

    router.post(
      endpoint,
      {},
      {
        preserveScroll: true,
        onSuccess: () => {
          fetchDeletedItems();
          setRestoreDialogOpen(false);
        },
        onError: (errors) => {
          setError(Object.values(errors)[0] ?? 'Gagal mengembalikan data');
        },
      },
    );
  };

  const filteredItems = deletedItems.filter(item => {
    const searchLower = searchTerm.toLowerCase();
    const invoiceNumber = (item.nomor_invoice || item.nomor_open_bill || '').toLowerCase();
    const customerName = (item.nama_pelanggan || '').toLowerCase();
    const cabangName = (item.cabang?.nama || '').toLowerCase();
    
    return invoiceNumber.includes(searchLower) || 
           customerName.includes(searchLower) || 
           cabangName.includes(searchLower);
  });

  const title = type === 'transaksi' ? 'Transaksi Terhapus' : 'Bill Terhapus';
  const description = `Mengelola ${type === 'transaksi' ? 'transaksi' : 'bill'} yang telah dihapus secara soft delete`;

  return (
    <AppLayout breadcrumbs={[{ title: 'IT Support', href: '/admin' }, { title, href: '#' }]}>
      <Head title={title} />
      <div className="space-y-6">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-xl font-semibold">{title}</h1>
            <div className="text-sm text-muted-foreground">{description}</div>
          </div>
          <div className="flex gap-2">
            <Button variant="outline" size="sm" onClick={fetchDeletedItems} disabled={loading}>
              <RefreshCw className="mr-2 h-4 w-4" />
              Refresh
            </Button>
            <Link href="/admin">
              <Button variant="outline" size="sm">
                Kembali
              </Button>
            </Link>
          </div>
        </div>

        {error && (
          <Alert variant="destructive">
            <AlertCircle className="h-4 w-4" />
            <AlertDescription>{error}</AlertDescription>
          </Alert>
        )}

        <Card>
          <CardHeader>
            <CardTitle>Daftar {title}</CardTitle>
            <CardDescription>
              Total: {filteredItems.length} item{filteredItems.length !== 1 ? 's' : ''}
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="mb-4">
              <div className="relative">
                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                  placeholder={`Cari ${type === 'transaksi' ? 'nomor invoice' : 'nomor bill'}...`}
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="pl-10"
                />
              </div>
            </div>

            {loading ? (
              <div className="text-center py-8">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div>
                <p className="mt-2 text-sm text-muted-foreground">Memuat data...</p>
              </div>
            ) : filteredItems.length === 0 ? (
              <div className="text-center py-8">
                <p className="text-sm text-muted-foreground">
                  {searchTerm ? 'Tidak ada hasil pencarian' : 'Belum ada data yang dihapus'}
                </p>
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b text-left">
                      <th className="py-2 px-4">Nomor</th>
                      <th className="py-2 px-4">Cabang</th>
                      <th className="py-2 px-4">Total</th>
                      <th className="py-2 px-4">Status</th>
                      <th className="py-2 px-4">Dihapus Pada</th>
                      <th className="py-2 px-4">Alasan</th>
                      <th className="py-2 px-4">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredItems.map((item) => (
                      <tr key={item.id} className="border-b last:border-0">
                        <td className="py-2 px-4 font-mono">
                          {item.nomor_invoice || item.nomor_open_bill || `OB-${item.id}`}
                        </td>
                        <td className="py-2 px-4">{item.cabang?.nama || item.cabang?.kode || '-'}</td>
                        <td className="py-2 px-4">Rp {formatCurrency(item.total)}</td>
                        <td className="py-2 px-4">
                          <span className="inline-flex items-center rounded bg-muted px-2 py-0.5 text-muted-foreground">
                            {item.status}
                          </span>
                        </td>
                        <td className="py-2 px-4">{formatDateTime(item.deleted_at)}</td>
                        <td className="py-2 px-4 max-w-xs truncate">
                          {item.delete_reason || '-'}
                        </td>
                        <td className="py-2 px-4">
                          <div className="flex gap-1">
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => {
                                setSelectedItem(item);
                                setRestoreDialogOpen(true);
                              }}
                              title="Kembalikan"
                            >
                              <RefreshCw className="h-4 w-4" />
                            </Button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </CardContent>
        </Card>

        <Dialog open={restoreDialogOpen} onOpenChange={setRestoreDialogOpen}>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Konfirmasi Pengembalian</DialogTitle>
              <DialogDescription>
                Apakah Anda yakin ingin mengembalikan {type === 'transaksi' ? 'transaksi' : 'bill'} ini?
                <br />
                <strong>Nomor:</strong> {selectedItem?.nomor_invoice || selectedItem?.nomor_open_bill}
                <br />
                <strong>Total:</strong> Rp {formatCurrency(selectedItem?.total)}
              </DialogDescription>
            </DialogHeader>
            <DialogFooter>
              <Button variant="outline" onClick={() => setRestoreDialogOpen(false)}>
                Batal
              </Button>
              <Button onClick={() => selectedItem && handleRestore(selectedItem)}>
                Ya, Kembalikan
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>
    </AppLayout>
  );
}