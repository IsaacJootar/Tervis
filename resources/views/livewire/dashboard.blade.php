<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard - {{ config('app.name', 'Laravel') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="flex min-h-screen">
       

        <!-- Main Area -->
        <div class="flex-1 flex flex-col">
            <!-- Top Navigation -->
            <nav class="bg-white shadow-sm border-b border-gray-200">
                <div class="flex justify-between items-center h-16 px-6">
                    <h1 class="text-xl font-bold text-gray-900">Dashboard</h1>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-700">Welcome, {{ Auth::user()->name ?? Auth::user()->email }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-200">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="flex-1 py-10 px-8">
                <div class="bg-white shadow rounded-lg p-6 text-gray-900">
                    <div class="text-center">
                        <div class="mx-auto h-16 w-16 bg-gradient-to-r from-green-400 to-blue-500 rounded-full flex items-center justify-center mb-6">
                            <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h2 class="text-3xl font-bold mb-4">Login Successful!</h2>
                        <p
