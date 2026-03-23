import React from 'react';
import { Head, Link } from '@inertiajs/react';

interface Category {
    id: number;
    nama: string;
    slug: string;
    produk: Product[];
}

interface Product {
    id: number;
    nama: string;
    harga_jual: number;
    deskripsi: string;
}

interface Cabang {
    id: number;
    kode: string;
    nama: string;
    alamat: string;
}

interface OutletMenuProps {
    cabang: Cabang;
    categories: Category[];
    selectedCategory?: Category;
    kategoriSlug?: string;
}

export default function OutletMenu({ cabang, categories, selectedCategory, kategoriSlug }: OutletMenuProps) {
    const displayedCategories = kategoriSlug 
        ? categories.filter(c => c.slug === kategoriSlug)
        : categories;

    return (
        <div className="min-h-screen bg-gray-100">
            <Head title={`Menu ${cabang.nama}`} />
            <header className="bg-white shadow">
                <div className="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Menu {cabang.nama}</h1>
                        <p className="text-sm text-gray-500">{cabang.alamat}</p>
                    </div>
                    <Link href="/menu" className="text-indigo-600 hover:text-indigo-800">Ganti Cabang</Link>
                </div>
            </header>
            <main>
                <div className="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                    {/* Category Filter */}
                    <div className="mb-8 flex flex-wrap gap-2">
                        <Link 
                            href={`/cabang/${cabang.kode}`}
                            className={`px-4 py-2 rounded-full text-sm font-medium ${!kategoriSlug ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-200'}`}
                        >
                            Semua
                        </Link>
                        {categories.map((cat) => (
                            <Link 
                                key={cat.id}
                                href={`/cabang/${cabang.kode}/${cat.slug}`}
                                className={`px-4 py-2 rounded-full text-sm font-medium ${kategoriSlug === cat.slug ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-200'}`}
                            >
                                {cat.nama}
                            </Link>
                        ))}
                    </div>

                    {/* Products by Category */}
                    {displayedCategories.map((category) => (
                        category.produk.length > 0 && (
                            <div key={category.id} className="mb-12">
                                <h2 className="text-2xl font-bold text-gray-900 mb-6 border-b pb-2">{category.nama}</h2>
                                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                    {category.produk.map((product) => (
                                        <div key={product.id} className="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                                            <div className="flex justify-between items-start mb-2">
                                                <h3 className="text-lg font-bold text-gray-900">{product.nama}</h3>
                                                <span className="text-indigo-600 font-bold">Rp {Number(product.harga_jual).toLocaleString('id-ID')}</span>
                                            </div>
                                            <p className="text-sm text-gray-500">{product.deskripsi}</p>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )
                    ))}
                </div>
            </main>
        </div>
    );
}
