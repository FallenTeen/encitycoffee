import React from 'react';
import { Head, Link } from '@inertiajs/react';

interface Cabang {
    id: number;
    kode: string;
    nama: string;
    alamat: string;
    aktif: boolean;
}

interface MenuProps {
    cabangList: Cabang[];
}

export default function Menu({ cabangList }: MenuProps) {
    return (
        <div className="min-h-screen bg-gray-100">
            <Head title="Pilih Cabang" />
            <header className="bg-white shadow">
                <div className="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <h1 className="text-3xl font-bold text-gray-900">Cabang Kami</h1>
                </div>
            </header>
            <main>
                <div className="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {cabangList.map((cabang) => (
                            <Link key={cabang.id} href={`/cabang/${cabang.kode}`} className="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 hover:shadow-2xl transition duration-300">
                                <h3 className="text-xl font-bold text-gray-900">{cabang.nama}</h3>
                                <p className="mt-2 text-gray-500">{cabang.alamat}</p>
                            </Link>
                        ))}
                    </div>
                </div>
            </main>
        </div>
    );
}
