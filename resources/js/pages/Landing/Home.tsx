import React from 'react';
import { Head } from '@inertiajs/react';

export default function Home() {
    return (
        <div className="min-h-screen bg-gray-100">
            <Head title="Home" />
            <header className="bg-white shadow">
                <div className="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <h1 className="text-3xl font-bold text-gray-900">Encity Coffee</h1>
                </div>
            </header>
            <main>
                <div className="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-xl sm:rounded-lg p-8">
                        <div className="text-center">
                            <h2 className="text-4xl font-extrabold tracking-tight text-gray-900 sm:text-6xl">
                                Welcome to Encity Coffee
                            </h2>
                            <p className="mt-4 text-xl text-gray-500">
                                Best coffee in town. Tagline goes here.
                            </p>
                            <div className="mt-8 flex justify-center">
                                <a href="/menu" className="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                    Browse Menu
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    );
}
