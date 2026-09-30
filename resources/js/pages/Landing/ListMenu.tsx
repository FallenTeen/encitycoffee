import React from 'react';
import { Head, Link } from '@inertiajs/react';

interface Cabang {
    id: number;
    kode: string;
    nama: string;
    alamat: string;
    aktif: boolean;
}

interface ListMenuProps {
    cabangList: Cabang[];
    selectedCabang?: Cabang | null;
    namaCabang?: string | null;
}

export default function ListMenu({ cabangList, selectedCabang, namaCabang }: ListMenuProps) {
    const handleSelectChange = (event: React.ChangeEvent<HTMLSelectElement>) => {
        const selectedNama = event.target.value;
        if (!selectedNama) {
            return;
        }
        // Hint: use encodeURIComponent when building a URL from a raw branch name.
        window.location.href = `/menupercabang/${encodeURIComponent(selectedNama)}`;
    };

    return (
        <>
            <Head title="Menu Per Cabang — Encity Company" />

            <div style={{ padding: '24px', maxWidth: '860px', margin: '0 auto' }}>
                <h1>Daftar Menu Per Cabang</h1>
                <p>
                    Pilih cabang menggunakan dropdown di bawah untuk melihat menu per cabang berdasarkan nama cabang.
                    Jika Anda ingin, tambahkan juga link ke halaman menu outlet yang sudah ada.
                </p>

                <label style={{ display: 'block', margin: '18px 0 8px' }}>
                    Cabang:
                </label>
                <select value={namaCabang ?? ''} onChange={handleSelectChange} style={{ width: '100%', padding: '10px', fontSize: '1rem' }}>
                    <option value="">-- Pilih cabang --</option>
                    {cabangList.map((cabang) => (
                        <option key={cabang.id} value={cabang.nama}>
                            {cabang.nama}
                        </option>
                    ))}
                </select>

                {selectedCabang ? (
                    <div style={{ marginTop: '24px' }}>
                        <h2>{selectedCabang.nama}</h2>
                        <p>{selectedCabang.alamat}</p>
                        <p>
                            Hint: tampilkan menu cabang di sini.
                            Contohnya, gunakan properti `selectedCabang` untuk mem-fetch produk
                            yang tersedia di cabang tersebut atau lanjutkan ke URL outlet menu.
                        </p>
                        <Link href={`/cabang/${selectedCabang.kode}`} style={{ display: 'inline-block', marginTop: '12px' }}>
                            Lihat menu lengkap cabang ini
                        </Link>
                    </div>
                ) : (
                    <p style={{ marginTop: '24px', color: '#333' }}>
                        {namaCabang
                            ? 'Cabang tidak ditemukan. Pastikan nama cabang sudah benar atau gunakan opsi dropdown di atas.'
                            : 'Belum ada cabang yang dipilih. Silakan pilih cabang dari daftar di atas.'}
                    </p>
                )}

                <div style={{ marginTop: '36px' }}>
                    <h3>Semua Cabang</h3>
                    <ul>
                        {cabangList.map((cabang) => (
                            <li key={cabang.id}>
                                <Link href={`/menupercabang/${encodeURIComponent(cabang.nama)}`}>
                                    {cabang.nama}
                                </Link>
                            </li>
                        ))}
                    </ul>
                    <p style={{ color: '#666', marginTop: '12px' }}>
                        Hint: jika nama cabang berisi spasi atau karakter khusus, gunakan `encodeURIComponent`
                        agar URL tetap valid.
                    </p>
                </div>
            </div>
        </>
    );
}
