import React from 'react';
import { Head } from '@inertiajs/react';

export default function About() {
    return (
        <div className="min-h-screen bg-gray-100">
            <Head title="About Us" />
            <header className="bg-white shadow">
                <div className="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <h1 className="text-3xl font-bold text-gray-900">About Encity Coffee</h1>
                </div>
            </header>
            <main>
                <div className="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-xl sm:rounded-lg p-8">
                        <div className="max-w-3xl mx-auto text-center">
                            <h2 className="text-3xl font-extrabold text-gray-900">Our Story</h2>
                            <p className="mt-6 text-lg text-gray-500">
                                Encity Coffee was founded with a passion for quality beans and local community. 
                                We believe in serving more than just coffee; we serve an experience.
                            </p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    );
}
