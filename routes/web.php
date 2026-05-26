<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::view('/login', 'app')->name('login');

Route::view('/panel', 'app')->name('panel');
Route::view('/catalogo', 'app')->name('catalogo');
Route::view('/marcas', 'app')->name('marcas');
Route::view('/pedidos', 'app')->name('pedidos');
Route::view('/estado-de-cuenta', 'app')->name('estado-de-cuenta');
Route::view('/cotizaciones', 'app')->name('cotizaciones');
Route::view('/cotizaciones/nueva', 'app')->name('cotizaciones.nueva');
Route::view('/quotes/create', 'app')->name('quotes.create');
Route::view('/perfil', 'app')->name('perfil');
Route::view('/historial-facturas', 'app')->name('historial-facturas');
